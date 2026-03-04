<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_T5Memory_Service as Service;
use Http\Client\Exception\HttpException;
use MittagQI\Translate5\Integration\ActionLockService;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\ConcordanceSearchService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Enum\SearchMode;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Exception\ExportException;
use MittagQI\Translate5\T5Memory\Exception\ImportResultedInErrorException;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateFileForTmxPreprocessingException;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateMemoryException;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\TMX\Filter\TmxFilter;
use MittagQI\Translate5\TMX\TransUnitParser;
use Translate5\MaintenanceCli\FixScript\FixScriptAbstract;

class Mittagqi416_CleanupT5memory extends FixScriptAbstract
{
    private ImportService $importService;

    private ExportService $exportService;

    private ConcordanceSearchService $concordanceSearchService;

    private Zend_Config $config;

    private LanguageRepository $languageRepository;

    private TmxFilter $tmxFilter;

    private TransUnitParser $transunitParser;

    private ActionLockService $actionLockService;

    public function fix(): void
    {
        $this->importService = ImportService::create();
        $this->exportService = ExportService::create();
        $this->concordanceSearchService = ConcordanceSearchService::create();
        $this->config = Zend_Registry::get('config');
        $this->languageRepository = LanguageRepository::create();
        $this->tmxFilter = TmxFilter::create();
        $this->transunitParser = new TransUnitParser();
        $this->actionLockService = ActionLockService::create();

        $languageResourceRepository = LanguageResourceRepository::create();
        $languageResources = $languageResourceRepository->getAllByServiceName(Service::NAME);

        $checkedMemoriesCount = 0;
        $affectedMemoriesCount = 0;

        foreach ($languageResources as $languageResource) {
            $checkedMemoriesCount++;

            $this->io->writeln('Checking language resource ' . $languageResource->getName());
            $affected = $this->checkIfLanguageResourceAffected($languageResource);

            if (! $affected) {
                $this->io->info('Language resource ' . $languageResource->getName() . ' is not affected');

                continue;
            }

            $affectedMemoriesCount++;

            if (! $this->doFix) {
                $this->io->warning('Language resource is affected, would fix ' . $languageResource->getName());

                continue;
            }

            $this->io->info('Language resource is affected, clearing ' . $languageResource->getName());

            $backupMemories = $languageResource->getSpecificData('memories');

            try {
                $this->cleanupLanguageResource($languageResource);
            } catch (\Throwable $th) {
                $this->io->warning('Failed to cleanup ' . $languageResource->getName() . ': ' . $th->getMessage());

                $languageResource->addSpecificData('memories', $backupMemories);
                $languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
                $languageResource->save();
            }
        }

        $this->info('Checked ' . $checkedMemoriesCount . ' language resources, ' . $affectedMemoriesCount . ' were affected');

        if (! $this->doFix) {
            $this->info('Run with --fix to fix affected language resources');
        }
    }

    private function checkIfLanguageResourceAffected(LanguageResource $languageResource): bool
    {
        $searchData = [
            'source' => '',
            'sourceMode' => SearchMode::Contains,
            'target' => '',
            'targetMode' => SearchMode::Contains,
            'sourceLanguage' => '',
            'targetLanguage' => '',
            'author' => '',
            'authorMode' => SearchMode::Contains,
            'creationDateFrom' => (new \DateTimeImmutable('20250107T000000Z'))->getTimestamp(),
            'creationDateTo' => (new \DateTimeImmutable('20250130T235959Z'))->getTimestamp(),
            'additionalInfo' => '',
            'additionalInfoMode' => SearchMode::Contains,
            'document' => '',
            'documentMode' => SearchMode::Contains,
            'context' => '',
            'contextMode' => SearchMode::Contains,
            'onlyCount' => false,
            'caseSensitive' => false,
        ];

        $sourceSearchDTO = SearchDTO::fromArray(array_merge($searchData, [
            'source' => '\\',
        ]));
        $targetSearchDTO = SearchDTO::fromArray(array_merge($searchData, [
            'target' => '\\',
        ]));
        $documentSearchDTO = SearchDTO::fromArray(array_merge($searchData, [
            'document' => '\\',
        ]));

        foreach ([$sourceSearchDTO, $targetSearchDTO, $documentSearchDTO]  as $dto) {
            try {
                $result = $this->concordanceSearchService->query(
                    $languageResource,
                    $dto,
                    null,
                    $this->config,
                    1
                );
            } catch (\Throwable $th) {
                $this->io->error('Failed to search ' . $languageResource->getName() . ': ' . $th->getMessage());

                return false;
            }

            if (! empty($result->getResult())) {
                return true;
            }
        }

        return false;
    }

    private function cleanupLanguageResource(
        LanguageResource $languageResource,
    ): void {
        $languageResource->setStatus(LanguageResourceStatus::IMPORT);
        $languageResource->save();

        $exportFilePath = $this->export($languageResource);

        $languageResource->removeSpecificData('memories');
        $languageResource->save();

        $resultFilepath = dirname($exportFilePath) . '/' . basename($exportFilePath, '.tmx') . '.cleared.tmx';

        $lock = $this->actionLockService->getWriteLock($languageResource->getLangResUuid());

        try {
            if (! $lock->acquire(true)) {
                $this->error(
                    'ExportMemoryWorker: Can not acquire lock for language resource with id ' . $languageResource->getId(),
                );

                return;
            }

            $this->clear($languageResource, $exportFilePath, $resultFilepath);
            $this->import($languageResource, $resultFilepath);
        } finally {
            $lock->release();

            unlink($resultFilepath);
            unlink($exportFilePath);
        }
    }

    private function export(LanguageResource $languageResource): string
    {
        $dateTime = new DateTime();
        $exportDir = APPLICATION_DATA . '/repair/' . date_format($dateTime, 'Y-m-d');
        $exportFilePath = $exportDir . '/'
            . $languageResource->getId() . '_' . date_format($dateTime, 'His') . '.tmx';

        if (! mkdir($exportDir, 0777, true) && ! is_dir($exportDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $exportDir));
        }

        $file = $this->exportService->export(
            $languageResource,
            TmFileExtension::TMX,
            null,
            false,
        );

        if (null === $file || ! file_exists($file)) {
            throw new ExportException('Report failed: Nothing was exported for language resource ' . $languageResource->getName());
        }

        rename($file, $exportFilePath);

        if (! file_exists($exportFilePath)) {
            throw new ExportException(sprintf('Report failed: Moving file [%s] to export dir failed', $file));
        }

        return $exportFilePath;
    }

    private function import(LanguageResource $languageResource, string $resultFilepath): void
    {
        try {
            $this->importService->importTmx(
                $languageResource,
                [$resultFilepath],
                new ImportOptions(
                    stripFramingTags: StripFramingTags::None,
                    tmxFilterOptions: TmxFilterOptions::fromConfig($this->config),
                    protectContent: false,
                    forceLongWait: true,
                ),
            );

            $languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
            $languageResource->save();
        } catch (HttpException|UnableToCreateMemoryException|ImportResultedInErrorException $e) {
            throw new Exception('Repairing failed: ' . $e->getMessage(), previous: $e);
        }
    }

    private function clear(LanguageResource $languageResource, string $exportFilePath, string $resultFilepath): void
    {
        $writer = new XMLWriter();

        if (! $writer->openURI($resultFilepath)) {
            throw new UnableToCreateFileForTmxPreprocessingException($resultFilepath);
        }

        $sourceLang = $this->languageRepository->get((int) $languageResource->getSourceLang());
        $targetLang = $this->languageRepository->get((int) $languageResource->getTargetLang());

        $fromTimestamp = (new \DateTimeImmutable('20250107T000000Z'))->getTimestamp();
        $toTimestamp = (new \DateTimeImmutable('20250130T235959Z'))->getTimestamp();

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        foreach ($this->tmxFilter->filter($exportFilePath, TmxFilterOptions::fromConfig($this->config)) as [$node, $isTu]) {
            if (! $isTu) {
                $writer->writeRaw($node);

                continue;
            }

            // <tu tuid="1" creationdate="20000101T120000Z" creationid="manager">
            if (! preg_match('/creationdate="(\d{8}T\d{6}Z)"/', $node, $matches)) {
                $writer->writeRaw($node);

                continue;
            }

            $creationDateStr = $matches[1];

            $creationDate = DateTime::createFromFormat('Ymd\THis\Z', $creationDateStr);

            if ($creationDate === false) {
                $writer->writeRaw($node);

                continue;
            }

            $creationTimestamp = $creationDate->getTimestamp();

            if ($creationTimestamp < $fromTimestamp || $creationTimestamp > $toTimestamp) {
                $writer->writeRaw($node);

                continue;
            }

            $transunit = $this->transunitParser->extractStructure($node, $sourceLang, $targetLang);

            if (str_ends_with($transunit->source, '\\') || str_ends_with($transunit->target, '\\')) {
                continue;
            }

            if (preg_match('/<prop type="tmgr:docname">([^<]*)\\\<\/prop>/', $node)) {
                continue;
            }

            $writer->writeRaw($node);
        }

        $writer->flush();
    }
}
