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

namespace MittagQI\Translate5\ContentProtection\T5memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use editor_Services_OpenTM2_Connector as Connector;
use MittagQI\Translate5\ContentProtection\Model\LanguageRulesHash;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportTmFileExtension;
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\ExportService;
use ZfExtended_Worker_Abstract;

class ConverseMemoryWorker extends ZfExtended_Worker_Abstract
{
    private int $languageResourceId;

    private LanguageResource $languageResource;

    private array $memoriesBackup;

    private readonly TmConversionService $tmConversionService;

    private readonly LanguageResourceRepository $languageResourceRepository;

    private readonly ExportService $exportService;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('editor.content-protection.opentm2.conversion');
        $this->tmConversionService = TmConversionService::create();
        $this->languageResourceRepository = new LanguageResourceRepository();
        $this->exportService = ExportService::create();
    }

    private function restoreLangResourceMemories(): void
    {
        $this->languageResource->addSpecificData('memories', $this->memoriesBackup);
        $this->languageResourceRepository->save($this->languageResource);
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! array_key_exists('languageResourceId', $parameters)) {
            return false;
        }

        $this->languageResourceId = (int) $parameters['languageResourceId'];

        try {
            $this->languageResource = $this->languageResourceRepository->get($this->languageResourceId);
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            return false;
        }

        if (editor_Services_Manager::SERVICE_OPENTM2 !== $this->languageResource->getServiceType()) {
            return false;
        }

        $this->memoriesBackup = $this->languageResource->getSpecificData('memories', parseAsArray: true) ?? [];

        return true;
    }

    protected function handleWorkerException(\Throwable $workException): void
    {
        $this->workerException = $workException;

        $this->restoreLangResourceMemories();
        $this->resetConversionStarted();
    }

    protected function work(): bool
    {
        if ($this->tmConversionService->isTmConverted($this->languageResourceId)) {
            return true;
        }

        if (! $this->languageResource->isConversionInProgress()) {
            return false;
        }

        $connector = new Connector();

        $sourceLang = (int) $this->languageResource->getSourceLang();
        $targetLang = (int) $this->languageResource->getTargetLang();

        $connector->connectTo($this->languageResource, $sourceLang, $targetLang);

        if ($connector->isEmpty()) {
            $this->finaliseConversion($sourceLang, $targetLang);

            return true;
        }

        $mime = $connector->getValidExportTypes()['TMX'];

        $memoriesBuffer = $this->memoriesBackup;

        foreach ($this->memoriesBackup as $memory) {
            $exportFilename = $this->exportService->export(
                $this->languageResource,
                ExportTmFileExtension::TMX,
                $memory['filename']
            );

            if (null === $exportFilename || ! file_exists($exportFilename)) {
                $this->log->error(
                    'E1587',
                    'Conversion: TM was not exported. TMX file does not exists: {filename}',
                    [
                        'filename' => $exportFilename,
                        'languageResource' => $this->languageResource,
                    ]
                );

                $this->resetConversionStarted();

                return false;
            }

            $tuCountBefore = $this->countTusInMemory($exportFilename);

            $fileinfo = [
                'tmp_name' => $exportFilename,
                'type' => $mime,
                'name' => basename($exportFilename),
            ];

            if (! $connector->addTm($fileinfo, [
                'createNewMemory' => true,
            ])) {
                $this->log->error(
                    'E1588',
                    'Conversion: Failed to import file: {filename}',
                    [
                        'filename' => $exportFilename,
                        'languageResource' => $this->languageResource,
                    ]
                );

                $this->rollback($connector);

                return false;
            }

            $memoriesNow = $this->languageResource->getSpecificData('memories', true);

            $newMemory = $this->findNewMemory($memoriesBuffer, $memoriesNow);

            $exportFilenameAfter = $this->exportService->export(
                $this->languageResource,
                ExportTmFileExtension::TMX,
                $newMemory
            );

            $tuCountAfter = $this->countTusInMemory($exportFilenameAfter);

            @unlink($exportFilenameAfter);

            if ($tuCountBefore !== $tuCountAfter) {
                $this->log->error(
                    'E1590',
                    'Conversion: Memory [{filename}] was not imported correctly. TU count before: {before}, after: {after}. Changes are rolled back.',
                    [
                        'filename' => $newMemory,
                        'before' => $tuCountBefore,
                        'after' => $tuCountAfter,
                        'languageResource' => $this->languageResource,
                    ]
                );

                $this->rollback($connector);

                return false;
            }

            $memoriesBuffer = $memoriesNow;

            @unlink($exportFilename);
        }

        $onMemoryDeleted = fn ($filename) =>
            fn () => $this->languageResource->addSpecificData(
                'memories',
                array_values(
                    array_filter(
                        $this->languageResource->getSpecificData('memories', true),
                        fn ($memory) => $memory['filename'] !== $filename
                    )
                )
            );

        foreach ($this->memoriesBackup as $memory) {
            if (! $connector->deleteMemory($memory['filename'], $onMemoryDeleted($memory['filename']))) {
                $this->log->error(
                    'E1589',
                    'Conversion: Memory [{filename}] was not deleted in process of conversion',
                    array_merge($memory, [
                        'languageResource' => $this->languageResource,
                    ])
                );
            }
        }

        $this->finaliseConversion($sourceLang, $targetLang);

        return true;
    }

    private function rollback(Connector $connector): void
    {
        $this->deleteNewlyCreatedMemories($connector);
        $this->restoreLangResourceMemories();
        $this->resetConversionStarted();
    }

    private function findNewMemory(array $was, array $now): string
    {
        $wasNames = array_column($was, 'filename');
        $nowNames = array_column($now, 'filename');

        return array_values(array_diff($nowNames, $wasNames))[0];
    }

    private function countTusInMemory(string $file): int
    {
        $reader = new \XMLReader();

        $reader->open($file);

        // suppress: namespace error : Namespace prefix t5 on n is not defined
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        $count = 0;

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'tu') {
                $count++;
            }
        }

        $reader->close();

        error_reporting($errorLevel);

        return $count;
    }

    private function finaliseConversion(int $sourceLang, int $targetLang): void
    {
        $languageRulesHash = new LanguageRulesHash();
        $languageRulesHash->loadByLanguages($sourceLang, $targetLang);

        $this->languageResource->addSpecificData(LanguageResource::PROTECTION_HASH, $languageRulesHash->getHash());

        $this->resetConversionStarted();
    }

    private function resetConversionStarted(): void
    {
        $this->languageResource->addSpecificData(LanguageResource::PROTECTION_CONVERSION_STARTED, null);
        // set status to import to block interactions with the language resource
        $this->languageResource->setStatus(Status::NOTCHECKED);
        $this->languageResourceRepository->save($this->languageResource);
    }

    /**
     * delete newly created memories as import failed
     */
    private function deleteNewlyCreatedMemories(Connector $connector): void
    {
        $backupMemoryNames = array_column($this->memoriesBackup, 'filename');

        foreach ($this->languageResource->getSpecificData('memories', true) as $new) {
            if (! in_array($new['filename'], $backupMemoryNames, true)) {
                $connector->deleteMemory($new['filename']);
            }
        }
    }
}
