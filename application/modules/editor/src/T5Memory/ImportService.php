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

use DOMDocument;
use DOMXPath;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use LogicException;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\ConstantApi;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;
use MittagQI\Translate5\T5Memory\Api\Response\ImportStatusResponse;
use MittagQI\Translate5\T5Memory\Enum\ImportStatusEnum;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\Exception\ImportResultedInErrorException;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Zend_Config;
use ZfExtended_Logger;

class ImportService
{
    public function __construct(
        private readonly Zend_Config $config,
        private readonly ZfExtended_Logger $logger,
        private readonly VersionService $versionService,
        private readonly TmConversionService $conversionService,
        private readonly Api\VersionedApiFactory $versionedApiFactory,
        private readonly PersistenceService $persistenceService,
        private readonly ReorganizeService $reorganizeService,
        private readonly ConstantApi $constantApi,
        private readonly MemoryNameGenerator $memoryNameGenerator,
        private readonly FlushMemoryService $flushMemoryService,
        private readonly CreateMemoryService $createMemoryService,
        private readonly RetryService $waitingService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('config'),
            \Zend_Registry::get('logger')->cloneMe('editor.t5memory.import'),
            VersionService::create(),
            TmConversionService::create(),
            Api\VersionedApiFactory::create(),
            PersistenceService::create(),
            ReorganizeService::create(),
            ConstantApi::create(),
            new MemoryNameGenerator(),
            FlushMemoryService::create(),
            CreateMemoryService::create(),
            RetryService::create(),
        );
    }

    public function importTmx(
        LanguageResource $languageResource,
        iterable $files,
        StripFramingTags $stripFramingTags,
        ?string $tmName = null,
    ): void {
        foreach ($files as $file) {
            try {
                $importFilename = $this->conversionService->convertTMXForImport(
                    $file,
                    (int) $languageResource->getSourceLang(),
                    (int) $languageResource->getTargetLang(),
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

            $this->importTmxIntoMemory(
                $languageResource,
                $importFilename,
                $tmName ?: $this->persistenceService->getWritableMemory($languageResource),
                $stripFramingTags,
            );

            unlink($importFilename);
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws Exception\UnableToCreateMemoryException
     */
    public function importTmxIntoMemory(
        LanguageResource $languageResource,
        string $importFilename,
        string $tmName,
        StripFramingTags $stripFramingTags,
    ): void {
        $gotLock = false;

        do {
            $response = $this->importTmxFile(
                $languageResource,
                $tmName,
                $importFilename,
                $stripFramingTags
            );

            if ($this->isLockingTimeoutOccurred($response)) {
                sleep($this->waitingService->getRetryDelaySeconds());

                continue;
            }

            if ($this->reorganizeService->needsReorganizing($response, $languageResource, $tmName)) {
                $this->reorganizeService->reorganizeTm($languageResource, $tmName);

                continue;
            }

            $gotLock = true;
        } while (! $gotLock);

        if (! $response->successful()) {
            $this->logger->error('E1303', 't5memory: could not add TMX data to TM', [
                'languageResource' => $languageResource,
                'apiError' => $response->getBody(),
            ]);
        }

        $waitNonImportStatus = function () use ($languageResource, $tmName) {
            $status = $this->constantApi->getImportStatus(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName)
            );

            return $status->status === ImportStatusEnum::Importing
                ? [WaitCallState::Retry, $status]
                : [WaitCallState::Done, $status];
        };

        /** @var ImportStatusResponse|null $status */
        $status = $this->waitingService->callAwaiting($waitNonImportStatus);

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

                $this->importTmxIntoMemory($languageResource, $importFilename, $tmName, $stripFramingTags);

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

            $newName = $this->memoryNameGenerator->generateNextMemoryName($languageResource);

            $newName = $this->createMemoryService->createEmptyMemoryWithRetry($languageResource, $newName);

            $this->persistenceService->addMemoryToLanguageResource($languageResource, $newName);

            // Filter TMX data from already imported segments
            $this->cutOffTmx(
                $importFilename,
                $this->getOverflowSegmentNumberFromStatus($languageResource, $status)
            );

            // Import further
            $this->importTmxIntoMemory($languageResource, $importFilename, $newName, $stripFramingTags);
        }
    }

    private function cutOffTmx(string $importFilename, int $segmentToStartFrom): void
    {
        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $doc = new DOMDocument();
        $doc->load($importFilename);

        if (! $doc->hasChildNodes()) {
            $error = libxml_get_last_error();

            if (false !== $error) {
                throw new RuntimeException(
                    'Error while loading TMX file: ' . $error->message,
                    $error->code
                );
            }
        }

        // Create an XPath to query the document
        $xpath = new DOMXPath($doc);

        // Find all 'tu' elements
        $tuNodes = $xpath->query('/tmx/body/tu');

        // Remove 'tu' elements before the segment index
        for ($i = 0; $i < $segmentToStartFrom; $i++) {
            $tuNodes->item($i)->parentNode->removeChild($tuNodes->item($i));
        }

        $doc->save($importFilename);

        error_reporting($errorLevel);
    }

    private function getOverflowSegmentNumberFromStatus(
        LanguageResource $languageResource,
        ResponseInterface $statusResponse
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

    private function isLockingTimeoutOccurred(ResponseInterface $response): bool
    {
        return $response->getCode() === 506;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws LogicException
     */
    private function importTmxFile(
        LanguageResource $languageResource,
        string $tmName,
        string $filePath,
        StripFramingTags $stripFramingTags,
    ): ResponseInterface {
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        if (Api\V6\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->importTmx(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    $filePath,
                    $stripFramingTags,
                );
        }

        if (Api\V5\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V5\VersionedApi::class)
                ->import(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    file_get_contents($filePath),
                    $stripFramingTags,
                );
        }

        throw new LogicException('Unsupported T5Memory version: ' . $version);
    }
}
