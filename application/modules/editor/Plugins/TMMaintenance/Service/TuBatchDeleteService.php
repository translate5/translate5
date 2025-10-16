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

namespace MittagQI\Translate5\Plugins\TMMaintenance\Service;

use DateTime;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\Plugins\TMMaintenance\Exception\BatchDeleteException;
use MittagQI\Translate5\Plugins\TMMaintenance\TmxFilter\SearchFilter;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use Zend_Registry;
use ZfExtended_Logger;

class TuBatchDeleteService
{
    public const PROCESSING_DIR = APPLICATION_DATA . '/tu-batch-delete';

    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly ExportService $exportService,
        private readonly ImportService $importService,
        private readonly SearchFilter $searchFilter,
        private readonly \Zend_Config $config,
        private readonly T5MemoryApi $api,
        private readonly CreateMemoryService $createMemoryService,
        private readonly PersistenceService $persistenceService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.tu-batch-delete'),
            ExportService::create(),
            ImportService::create(),
            SearchFilter::create(),
            Zend_Registry::get('config'),
            T5MemoryApi::create(),
            CreateMemoryService::create(),
            PersistenceService::create(),
        );
    }

    /**
     * @throws BatchDeleteException
     */
    public function deleteBatch(
        LanguageResource $languageResource,
        SearchDTO $searchDTO,
    ): void {
        $file = $this->exportTmx($languageResource);
        $tmxFilePath = $this->moveFileToProcessingDir($file, $languageResource);

        try {
            $this->searchFilter->filter($tmxFilePath, $searchDTO);
        } catch (\Exception $e) {
            $this->failProcess($languageResource);

            $this->logger->warn(
                'E1688',
                'Batch delete failed: Could not delete entries from TMX file',
                [
                    'languageResource' => $languageResource,
                    'file' => $tmxFilePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw new BatchDeleteException(
                'Batch delete failed: Could not delete entries from TMX file: ' . $e->getMessage(),
                previous: $e
            );
        }

        $specificDataMemoriesBackup = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        $newMemory = $this->createMemoryService->createEmptyMemoryWithRetry($languageResource);

        // reset memories, so that only the new empty memory is used for import
        $languageResource->addSpecificData('memories', []);

        $this->persistenceService->addMemoryToLanguageResource($languageResource, $newMemory);

        $tmpDir = APPLICATION_DATA . '/' . bin2hex(random_bytes(8));
        @mkdir($tmpDir, 0777, true);
        $importFile = $tmpDir . '/' . basename($tmxFilePath);

        copy($tmxFilePath, $importFile);

        $saveDifferentTargetsForSameSource = (bool) $this->config
            ->runtimeOptions
            ->LanguageResources
            ->t5memory
            ->saveDifferentTargetsForSameSource;

        try {
            $this->importService->importTmx(
                $languageResource,
                [$importFile],
                new ImportOptions(
                    stripFramingTags: StripFramingTags::None,
                    saveDifferentTargetsForSameSource: $saveDifferentTargetsForSameSource,
                    protectContent: false,
                    forceLongWait: true,
                ),
            );

            $languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
            $languageResource->save();

            // delete old memories
            $this->deleteMemories($languageResource, array_column($specificDataMemoriesBackup, 'filename'));
        } catch (\Throwable $e) {
            $this->logger->warn(
                'E1688',
                'Batch delete failed: Could not import TMX.',
                [
                    'languageResource' => $languageResource,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            $this->revertMemories($languageResource, $specificDataMemoriesBackup);
            $this->failProcess($languageResource);

            throw new BatchDeleteException('Batch delete failed: Could not import TMX', previous: $e);
        } finally {
            if (file_exists($importFile)) {
                unlink($importFile);
            }

            if (file_exists($tmpDir)) {
                rmdir($tmpDir);
            }
        }
    }

    private function failProcess(LanguageResource $languageResource): void
    {
        $languageResource->setStatus(LanguageResourceStatus::NOTCHECKED);
        $languageResource->save();
    }

    private function deleteMemories(LanguageResource $languageResource, array $memories): void
    {
        $url = $languageResource->getResource()->getUrl();

        foreach ($memories as $memory) {
            $this->api->deleteTm($url, $memory);
        }
    }

    /**
     * @throws BatchDeleteException
     */
    private function exportTmx(LanguageResource $languageResource): string
    {
        $file = $this->exportService->export(
            $languageResource,
            TmFileExtension::TMX,
            null,
            false,
        );

        if (null === $file || ! file_exists($file)) {
            $this->failProcess($languageResource);

            $this->logger->warn(
                'E1688',
                'Batch delete failed: Nothing was exported',
                [
                    'languageResource' => $languageResource,
                ]
            );

            throw new BatchDeleteException('Reorganize failed: Nothing was exported');
        }

        return $file;
    }

    /**
     * @throws BatchDeleteException
     */
    private function moveFileToProcessingDir(string $file, LanguageResource $languageResource): string
    {
        $dateTime = new DateTime();
        $processingDir = self::PROCESSING_DIR . '/' . date_format($dateTime, 'Y-m-d');
        $tmxFilePath = $processingDir . '/'
            . $languageResource->getId() . '_' . date_format($dateTime, 'His') . '.tmx';

        @mkdir($processingDir, 0777, true);

        rename($file, $tmxFilePath);

        if (! file_exists($tmxFilePath)) {
            $this->failProcess($languageResource);

            $this->logger->warn(
                'E1688',
                'Batch delete failed: Moving file [{file}] to export dir failed',
                [
                    'languageResource' => $languageResource,
                    'file' => $file,
                ]
            );

            throw new BatchDeleteException(
                sprintf('Batch delete failed: Moving file [%s] to export dir failed', $file)
            );
        }

        return $tmxFilePath;
    }

    private function revertMemories(LanguageResource $languageResource, mixed $specificDataMemoriesBackup): void
    {
        $currentMemoriesData = $languageResource->getSpecificData('memories', parseAsArray: true) ?? [];
        $currentMemories = array_column($currentMemoriesData, 'filename');

        // delete the newly created memories, keep the backups
        $this->deleteMemories($languageResource, $currentMemories);

        // restore the backed up memories
        $languageResource->addSpecificData('memories', $specificDataMemoriesBackup);
        $languageResource->save();
    }
}
