<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Worker;

use editor_Models_Import_DirectoryParser_ReferenceFiles as ReferenceFiles;
use editor_Models_Languages;
use editor_Models_Task_AbstractWorker;
use editor_Plugins_Okapi_FileFilter;
use MittagQI\Translate5\File\Filter\Manager;
use MittagQI\Translate5\File\Filter\Type;
use MittagQI\Translate5\Plugins\Okapi\OkapiAdapter;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use SplFileInfo;
use Throwable;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

class OkapiImportWorker extends editor_Models_Task_AbstractWorker
{
    private ZfExtended_Logger $logger;

    protected function validateParameters(array $parameters): bool
    {
        return true;
    }

    /**
     * @throws OkapiException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \ZfExtended_BadGateway
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \editor_Models_ConfigException
     */
    public function work(): bool
    {
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.okapi.import');
        $params = $this->workerModel->getParameters();

        $file = new SplFileInfo($params['file']);
        $fileId = (int) $params['fileId'];
        $fileName = OkapiWorkerHelper::createOriginalFileName($fileId, strtolower($file->getExtension()));
        $manifestFile = OkapiWorkerHelper::createManifestFileName($fileId);
        $okapiDataDir = OkapiWorkerHelper::getDataDir($this->task);

        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());

        $taskConfig = $this->task->getConfig();
        $okapiConfig = $taskConfig->runtimeOptions->plugins->Okapi;
        $serverUsed = $okapiConfig->serverUsed ?? 'not set';
        $api = new OkapiAdapter($taskConfig);

        XmlEntitiesPatcher::patchBeforeImport((int) $this->task->meta()->getBconfId(), $file);

        try {
            $this->logger->info(
                'E1444',
                'Okapi Plug-In: File "{fileName}" (id: {fileId}) will be imported with Okapi "{okapi}"',
                [
                    'task' => $this->task,
                    'okapi' => $serverUsed,
                    'fileId' => $fileId,
                    'fileName' => $file->getBasename(),
                    'okapiUrl' => $okapiConfig->server?->$serverUsed ?? 'server used not found',
                    'usedBconf' => $params['bconfName'],
                ],
                ['tasklog', 'ecode']
            );

            $api->createProject();
            // upload the BCONF set by worker-params
            $api->uploadOkapiConfig($params['bconfFilePath'], true);
            $api->uploadInputFile($fileName, $file);
            $api->executeTask($sourceLang, $targetLang);
            $convertedFile = $api->downloadFile($fileName, $manifestFile, $okapiDataDir);

            //copy original into data dir for export
            copy($file, $okapiDataDir . '/' . $fileName);
            //copy original to reference files
            $this->copyOriginalAsReference();
            //copy generated XLF into importFolder
            copy($convertedFile, $file . $api::OUTPUT_FILE_EXTENSION);

            //add okapi export file filter for that file
            $filterManager = ZfExtended_Factory::get(Manager::class);
            $filterManager->addFilter(
                Type::Import,
                $this->taskGuid,
                $fileId,
                editor_Plugins_Okapi_FileFilter::class
            );
            $filterManager->addFilter(
                Type::Export,
                $this->taskGuid,
                $fileId,
                editor_Plugins_Okapi_FileFilter::class
            );
        } catch (Throwable $e) {
            $params = [
                'errorCode' => 'E1058',
                'errorMsg' => 'Okapi Plug-In: Error in converting file "{file}" on import: {message}',
                'errorExtra' => [
                    'message' => $e->getMessage(),
                    'fileId' => $fileId,
                    'file' => basename($file->__toString()),
                ],
            ];
            // this will trigger an exception when the filter
            // is resolved in the main application on import finish
            $filterManager = ZfExtended_Factory::get(Manager::class);
            $filterManager->addFilter(
                Type::Import,
                $this->taskGuid,
                $fileId,
                editor_Plugins_Okapi_FileFilter::class,
                json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } finally {
            $api->removeProject();
        }

        return true;
    }

    /**
     * copy the original files from review folder to the referenceFiles folder,
     * keep original file and directory structure
     * @throws \Zend_Exception
     */
    private function copyOriginalAsReference(): void
    {
        if (! $this->isAttachOriginalAsReference()) {
            return;
        }
        $params = $this->workerModel->getParameters();
        $importConfig = $params['importConfig'];
        /* @var $importConfig \editor_Models_Import_Configuration */

        $realFile = $params['file'];
        $refFolder = $params['importFolder'] . '/' . ReferenceFiles::getDirectory();
        $workfilesDirectory = $params['importFolder'] . '/' . $importConfig->getWorkfilesDirName();

        //cut off review folder from realfile:
        $relRealFile = str_replace('#' . realpath($workfilesDirectory), '', '#' . realpath($realFile));
        $absRefFile = $refFolder . '/' . $relRealFile;
        $absRefDir = dirname($absRefFile);

        //create directory if needed
        if (! is_dir($absRefDir)) {
            mkdir($absRefDir, 0777, true);
        }

        //we copy the file and keep the original file via fileId addressable for export (TRANSLATE-1138)
        rename($realFile, $absRefFile);
    }

    /**
     * Is configured the original files to be attached as reference files.
     * When no config is provided the original will be attached as reference.
     * @return boolean
     * @throws \Zend_Exception
     */
    private function isAttachOriginalAsReference(): bool
    {
        return (bool) Zend_Registry::get('config')->runtimeOptions
            ->plugins->Okapi->import->fileconverters->attachOriginalFileAsReference;
    }

    /**
     * The okapi import worker takes approximately 5% of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int
    {
        return 5;
    }
}
