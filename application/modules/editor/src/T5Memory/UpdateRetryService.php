<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use DatetimeImmutable;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Connector_Abstract;
use editor_Services_Connector_Exception;
use editor_Services_Exceptions_NoService;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentErroneousException;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentTooLongException;
use MittagQI\Translate5\T5Memory\Api\Response\UpdateResponse;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\Exception\SegmentUpdateCheckException;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateMemoryException;
use Zend_Config;
use ZfExtended_Logger;

class UpdateRetryService
{
    public function __construct(
        private readonly PersistenceService $persistenceService,
        private readonly ReorganizeService $reorganizeService,
        private readonly RetryService $waitingService,
        private readonly T5MemoryApi $api,
        private readonly CreateMemoryService $createMemoryService,
        private readonly FlushMemoryService $flushMemoryService,
        private readonly MemoryNameGenerator $memoryNameGenerator,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            PersistenceService::create(),
            ReorganizeService::create(),
            RetryService::create(),
            T5MemoryApi::create(),
            CreateMemoryService::create(),
            FlushMemoryService::create(),
            new MemoryNameGenerator(),
            \Zend_Registry::get('logger')->cloneMe('editor.t5memory.update'),
        );
    }

    public function supports(LanguageResource $languageResource): bool
    {
        return \editor_Services_OpenTM2_Service::NAME === $languageResource->getServiceName();
    }

    /**
     * @throws editor_Services_Connector_Exception
     * @throws editor_Services_Exceptions_NoService
     * @throws SegmentUpdateException
     * @throws SegmentUpdateCheckException
     */
    public function updateWithRetry(
        LanguageResource $languageResource,
        UpdateSegmentDTO $dto,
        UpdateOptions $updateOptions,
        Zend_Config $config,
    ): void {
        if ($languageResource->isConversionStarted()) {
            throw new editor_Services_Connector_Exception('E1512', [
                'status' => Status::CONVERTING,
                'service' => $languageResource->getResource()->getName(),
                'languageResource' => $languageResource,
            ]);
        }

        $tmName = $this->persistenceService->getWritableMemory($languageResource);

        $this->updateWithRetryInMemory(
            $languageResource,
            $tmName,
            $dto,
            $updateOptions,
            $config,
        );
    }

    /**
     * @throws editor_Services_Connector_Exception
     * @throws editor_Services_Exceptions_NoService
     * @throws SegmentUpdateException
     * @throws SegmentUpdateCheckException
     */
    public function updateWithRetryInMemory(
        LanguageResource $languageResource,
        string $tmName,
        UpdateSegmentDTO $dto,
        UpdateOptions $updateOptions,
        Zend_Config $config,
    ): void {
        // TODO This is a huge domain leak - refactor needed
        $isInternalFuzzy = str_contains($tmName, editor_Services_Connector_Abstract::FUZZY_SUFFIX);

        if ($this->reorganizeService->isReorganizingAtTheMoment($languageResource, $tmName)) {
            throw new editor_Services_Connector_Exception('E1512', [
                'status' => Status::REORGANIZE_IN_PROGRESS,
                'service' => $languageResource->getResource()->getName(),
                'languageResource' => $languageResource,
            ]);
        }

        $elapsedTime = 0;
        $maxWaitingTime = $this->waitingService->getMaxWaitingTimeSeconds();
        $response = null;

        while ($elapsedTime < $maxWaitingTime) {
            try {
                $response = $this->api->update(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    $dto,
                    $languageResource->getSourceLangCode(),
                    $languageResource->getTargetLangCode(),
                    $updateOptions->saveDifferentTargetsForSameSource,
                    $updateOptions->saveToDisk,
                );
            } catch (SegmentTooLongException|SegmentErroneousException $e) {
                throw new SegmentUpdateException($e->getMessage(), previous: $e);
            }

            if ($response->successful()) {
                $this->checkUpdatedSegmentIfNeeded(
                    $languageResource,
                    $tmName,
                    $dto,
                    $response,
                    $config,
                    $updateOptions->recheckOnUpdate
                );

                return;
            }

            if ($this->reorganizeService->needsReorganizing($response, $languageResource, $tmName)) {
                $options = new ReorganizeOptions($updateOptions->saveDifferentTargetsForSameSource);
                $this->reorganizeService->reorganizeTm(
                    $languageResource,
                    $tmName,
                    $options,
                    $isInternalFuzzy,
                );
            } elseif ($response->isMemoryOverflown($config)) {
                if (! $response->isBlockOverflown($config)) {
                    $this->persistenceService->setMemoryReadonly(
                        $languageResource,
                        $tmName,
                        $isInternalFuzzy
                    );
                }

                $newName = $this->persistenceService->getNextWritableMemory($languageResource, $tmName);

                if ($newName) {
                    $tmName = $newName;

                    continue;
                }

                $this->addOverflowLog($languageResource, $response->getErrorMessage());

                if (! $isInternalFuzzy) {
                    $this->flushMemoryService->flush($languageResource, $tmName);
                }

                $newName = $this->memoryNameGenerator->generateNextMemoryName($languageResource);

                try {
                    $newName = $this->createMemoryService->createEmptyMemoryWithRetry(
                        $languageResource,
                        $newName,
                    );
                } catch (UnableToCreateMemoryException) {
                    break;
                }

                $this->persistenceService->addMemoryToLanguageResource(
                    $languageResource,
                    $newName,
                    $isInternalFuzzy,
                );
                $tmName = $newName;
            } elseif ($response->isLockingTimeoutOccurred()) {
                // Wait before retrying
                sleep($this->waitingService->getRetryDelaySeconds());
            } else {
                // If no specific error handling is applicable, break the loop
                break;
            }

            $elapsedTime = $this->waitingService->getRetryDelaySeconds();
        }

        throw new SegmentUpdateException($response?->getErrorMessage() ?? '');
    }

    /**
     * @throws SegmentUpdateCheckException
     */
    private function checkUpdatedSegmentIfNeeded(
        LanguageResource $languageResource,
        string $tmName,
        UpdateSegmentDTO $updateSegmentDto,
        UpdateResponse $updateResponse,
        Zend_Config $config,
        bool $recheckOnUpdate,
    ): void {
        if (! in_array(
            $languageResource->getResource()->getUrl(),
            $config->runtimeOptions->LanguageResources->checkSegmentsAfterUpdate->toArray(),
            true
        )
            || ! $recheckOnUpdate
        ) {
            // Checking segment after update is disabled in config or in parameter, nothing to do
            return;
        }

        $this->checkUpdatedSegment(
            $languageResource,
            $tmName,
            $updateSegmentDto,
            $updateResponse
        );
    }

    /**
     * @throws SegmentUpdateCheckException
     */
    private function checkUpdatedSegment(
        LanguageResource $languageResource,
        string $tmName,
        UpdateSegmentDTO $updateSegmentDto,
        UpdateResponse $updateResponse,
    ): void {
        $entryResponse = $this->api->getEntry(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName),
            $updateResponse->getInternalKey(),
        );

        if (! $entryResponse->successful()) {
            throw new SegmentUpdateCheckException(
                'Segment was not saved to TM: ' . $entryResponse->getErrorMessage(),
                json_encode($updateResponse->getBody(), JSON_PRETTY_PRINT),
            );
        }

        // Decode html entities
        $targetReceived = html_entity_decode($entryResponse->getTarget());
        // Decode unicode symbols
        $targetReceived = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $targetReceived);
        // Replacing \r\n to \n back because t5memory replaces \n to \r\n
        $targetReceived = str_replace("\r\n", "\n", $targetReceived);
        $targetSent = str_replace("\r\n", "\n", $updateSegmentDto->target);
        // Finally compare target that we've sent for saving with the one we retrieved from TM, they should be the same
        // html_entity_decode() is used because sometimes t5memory returns target with decoded
        // html entities regardless of the original target
        $targetIsTheSame = $targetReceived === $targetSent
            || html_entity_decode($targetReceived) === html_entity_decode($targetSent);

        $resultDate = DatetimeImmutable::createFromFormat('Y-m-d H:i:s T', $entryResponse->getTimestamp());
        // Timestamp should be not older than 1 minute otherwise it is an old segment which wasn't updated
        $isResultFresh = $resultDate >= new DateTimeImmutable('-1 minute');

        if (! $targetIsTheSame || ! $isResultFresh) {
            throw new SegmentUpdateCheckException(
                match (false) {
                    $targetIsTheSame => 'Saved segment target differs with provided',
                    $isResultFresh => 'Got old result',
                },
                json_encode($updateResponse->getBody(), JSON_PRETTY_PRINT),
            );
        }
    }

    private function addOverflowLog(LanguageResource $languageResource, ?string $error): void
    {
        $params = [
            'name' => $languageResource->getName(),
            'apiError' => $error,
        ];

        $this->logger->info(
            'E1603',
            'Language Resource [{name}] current writable memory is overflown, creating a new one',
            $params
        );
    }
}
