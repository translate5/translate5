<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\TmxImportPreprocessorInterface;
use MittagQI\Translate5\T5Memory\Api\Response\ImportStatusResponse;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Enum\ImportStatusEnum;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\Exception\ImportResultedInErrorException;
use MittagQI\Translate5\T5Memory\Exception\ImportResultedInReorganizeException;
use MittagQI\Translate5\T5Memory\Import\CutOffTmx;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;

class ImportService
{
    public function __construct(
        private readonly Zend_Config $config,
        private readonly ZfExtended_Logger $logger,
        private readonly T5MemoryApi $t5MemoryApi,
        private readonly TmxImportPreprocessorInterface $tmxImportPreprocessor,
        private readonly PersistenceService $persistenceService,
        private readonly FlushMemoryService $flushMemoryService,
        private readonly CreateMemoryService $createMemoryService,
        private readonly RetryService $waitingService,
        private readonly WipeMemoryService $wipeMemoryService,
        private readonly CutOffTmx $cutOffTmx,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('config'),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.import'),
            T5MemoryApi::create(),
            TmxImportPreprocessor::create(),
            PersistenceService::create(),
            FlushMemoryService::create(),
            CreateMemoryService::create(),
            RetryService::create(),
            WipeMemoryService::create(),
            CutOffTmx::create(),
        );
    }

    public function importTmx(
        LanguageResource $languageResource,
        iterable $files,
        ImportOptions $importOptions,
        ?string $tmName = null,
    ): void {
        foreach ($files as $file) {
            try {
                $importFilename = $this->tmxImportPreprocessor->process(
                    $file,
                    (int) $languageResource->getSourceLang(),
                    (int) $languageResource->getTargetLang(),
                    $importOptions
                );
            } catch (RuntimeException $e) {
                $this->logger->error(
                    'E1590',
                    'Conversion: Error in process of TMX file conversion',
                    [
                        'file' => $file,
                        'reason' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'languageResource' => $languageResource,
                    ]
                );
                $languageResource->setStatus(LanguageResourceStatus::NOTCHECKED);
                $languageResource->save();

                throw $e;
            }

            if (! $tmName) {
                try {
                    $tmName = $this->persistenceService->getLastWritableMemory($languageResource);
                } catch (\editor_Services_Connector_Exception $e) {
                    // If there is no writable memory (E1564), we create a new one
                    if (1564 !== $e->getCode()) {
                        throw $e;
                    }

                    $tmName = $this->createMemoryService->createEmptyMemoryWithRetry($languageResource);
                    $this->persistenceService->addMemoryToLanguageResource($languageResource, $tmName);
                }
            }

            $this->importTmxInMemory(
                $languageResource,
                $importFilename,
                $tmName ?: $this->persistenceService->getWritableMemory($languageResource),
                $importOptions,
            );

            unlink($importFilename);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws Exception\UnableToCreateMemoryException
     * @throws ImportResultedInErrorException
     */
    public function importTmxInMemory(
        LanguageResource $languageResource,
        string $importFilename,
        string $tmName,
        ImportOptions $importOptions,
        int $attempts = 0,
    ): string {
        $maxAttempts = $this->config->runtimeOptions->LanguageResources->t5memory->maxReorganizeAttempts;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $this->importTmxIntoMemory(
                    $languageResource,
                    $importFilename,
                    $tmName,
                    $importOptions,
                );

                return $tmName;
            } catch (ImportResultedInReorganizeException) {
                $tmName = $this->wipeMemoryService->wipeMemory($languageResource, $tmName);
            }
        }

        throw new ImportResultedInReorganizeException();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws Exception\UnableToCreateMemoryException
     * @throws ImportResultedInErrorException
     * @throws ImportResultedInReorganizeException
     */
    private function importTmxIntoMemory(
        LanguageResource $languageResource,
        string $importFilename,
        string $tmName,
        ImportOptions $importOptions,
    ): void {
        $gotLock = false;
        $tmName = $this->persistenceService->addTmPrefix($tmName);
        $baseUrl = $languageResource->getResource()->getUrl();

        do {
            $response = $this->t5MemoryApi->importTmx(
                $baseUrl,
                $tmName,
                $importFilename,
                $importOptions,
            );

            if ($response->isLockingTimeoutOccurred()) {
                sleep($this->waitingService->getRetryDelaySeconds());

                continue;
            }

            if ($response->needsReorganizing($this->config)) {
                throw new ImportResultedInReorganizeException();
            }

            $gotLock = true;
        } while (! $gotLock);

        if (! $response->successful()) {
            $this->logger->error('E1303', 't5memory: could not add TMX data to TM', [
                'languageResource' => $languageResource,
                'apiError' => $response->getBody(),
            ]);
        }

        $waitNonImportStatus = function () use ($baseUrl, $tmName) {
            $status = $this->t5MemoryApi->getImportStatus($baseUrl, $tmName);

            return $status->status === ImportStatusEnum::Importing
                ? [WaitCallState::Retry, $status]
                : [WaitCallState::Done, $status];
        };

        /** @var ImportStatusResponse|null $status */
        $status = $this->waitingService->callAwaiting($waitNonImportStatus, $importOptions->forceLongWait);

        if (null === $status) {
            $this->logger->error('E1302', 't5memory: Waiting timed out in process of import status retrieving', [
                'languageResource' => $languageResource,
            ]);
        }

        switch ($status->status) {
            case ImportStatusEnum::Terminated:
                $this->logger->warn('E1304', 't5memory: TMX import was terminated. Retrying.', [
                    'languageResource' => $languageResource,
                    'apiError' => $status->getBody(),
                ]);

                $this->importTmxIntoMemory($languageResource, $importFilename, $tmName, $importOptions);

                break;

            case ImportStatusEnum::Error:
                if ($status->isMemoryOverflown($this->config)) {
                    break;
                }

                $this->logger->error('E1304', 't5memory: could not import TMX', [
                    'languageResource' => $languageResource,
                    'apiError' => $status->getBody(),
                    'importFilename' => $importFilename,
                ]);

                throw new ImportResultedInErrorException();
            default:
                break;
        }

        // At current point LR is in Import status to prevent race conditions
        // Here we are resetting state to fetch the actual status from t5memory
        $languageResource->setStatus(LanguageResourceStatus::NOTCHECKED);

        // In case we've got memory overflow error we need to create another memory and import further
        if ($status->isMemoryOverflown($this->config)) {
            $this->addOverflowLog($languageResource, $status);
            $this->flushMemoryService->flush($languageResource, $tmName);

            $newName = $this->createMemoryService->createEmptyMemoryWithRetry($languageResource);

            $this->persistenceService->addMemoryToLanguageResource($languageResource, $newName);

            // Filter TMX data from already imported segments
            $this->cutOffTmx->cutOff(
                $importFilename,
                $this->getOverflowSegmentNumberFromStatus($languageResource, $status)
            );

            // Import further
            $this->importTmxIntoMemory($languageResource, $importFilename, $newName, $importOptions);
        }
    }

    private function getOverflowSegmentNumberFromStatus(
        LanguageResource $languageResource,
        ResponseInterface $statusResponse,
    ): int {
        $body = $statusResponse->getBody();

        if (! isset($body['tmxSegmentCount']) || 0 === (int) $body['tmxSegmentCount']) {
            $this->logger->error(
                'E1313',
                't5memory responded with memory overflow error, ' .
                'but we were unable to distinguish the segment number for reimport',
                [
                    'languageResource' => $languageResource,
                ]
            );

            throw new \editor_Services_Connector_Exception('E1313', [
                'error' => $statusResponse->getErrorMessage(),
            ]);
        }

        return (int) $body['tmxSegmentCount'];
    }

    private function addOverflowLog(LanguageResource $languageResource, ResponseInterface $response): void
    {
        $params = [
            'name' => $languageResource->getName(),
            'apiError' => $response->getBody(),
        ];

        $this->logger->info(
            'E1603',
            'Language Resource [{name}] current writable memory is overflown, creating a new one',
            $params
        );
    }
}
