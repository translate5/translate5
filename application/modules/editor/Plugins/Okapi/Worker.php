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

use MittagQI\Translate5\Plugins\Okapi\OkapiAdapter;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;

class editor_Plugins_Okapi_Worker extends editor_Models_Task_AbstractWorker
{
    public const TYPE_IMPORT = 'import';

    public const TYPE_EXPORT = 'export';

    public const OKAPI_REL_DATA_DIR = 'okapi-data';

    /**
     * filename template for storing the manifest files
     * @var string
     */
    public const MANIFEST_FILE = 'manifest-%s.rkm';

    /**
     * filename template for storing the original files
     * @var string
     */
    public const ORIGINAL_FILE = 'original-%s.%s';

    private bool $hadEmptyTargets = false;

    /**
     * Api for accessing the saved Manifest file from other contexts
     */
    public static function createManifestFilePath(string $absoluteTaskDataPath, int $fileId): string
    {
        return $absoluteTaskDataPath . '/'
            . self::OKAPI_REL_DATA_DIR . '/'
            . sprintf(self::MANIFEST_FILE, $fileId);
    }

    /**
     * Api for accessing the saved original file from other contexts
     */
    public static function createOriginalFilePath(string $absoluteTaskDataPath, int $fileId, string $extension): string
    {
        return $absoluteTaskDataPath . '/'
            . self::OKAPI_REL_DATA_DIR . '/'
            . sprintf(self::ORIGINAL_FILE, $fileId, $extension);
    }

    /**
     * Api for accessing the saved  converted file from other contexts
     */
    public static function createConvertedFilePath(string $absoluteTaskDataPath, int $fileId, string $extension): string
    {
        return self::createOriginalFilePath($absoluteTaskDataPath, $fileId, $extension)
            . OkapiAdapter::OUTPUT_FILE_EXTENSION;
    }

    protected ZfExtended_Logger $logger;

    protected function validateParameters(array $parameters): bool
    {
        if (empty($parameters['type'])
                || ! ($parameters['type'] == self::TYPE_IMPORT
                || $parameters['type'] == self::TYPE_EXPORT)
        ) {
            return false;
        }

        return true;
    }

    public function onInit(array $parameters): bool
    {
        if (parent::onInit($parameters)) {
            if ($parameters['type'] === self::TYPE_EXPORT) {
                // on export we just use normal maintenance check, not the extended one for imports
                $this->behaviour->setConfig([
                    'isMaintenanceScheduled' => true,
                ]);
            }

            return true;
        }

        return false;
    }

    /**
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    public function work(): bool
    {
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.okapi');
        $params = $this->workerModel->getParameters();
        if ($params['type'] == self::TYPE_IMPORT) {
            return $this->doImport();
        }

        return $this->doExport();
    }

    /**
     * Uploads one file to Okapi to convert it to an XLIFF file importable by translate5
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    protected function doImport(): bool
    {
        $params = $this->workerModel->getParameters();

        $file = new SplFileInfo($params['file']);
        $suffix = $file->getExtension();
        $fileId = (int) $params['fileId'];
        $fileName = sprintf(self::ORIGINAL_FILE, $fileId, $suffix);
        $manifestFile = $this->getManifestFile($fileId);
        $okapiDataDir = $this->getDataDir();

        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());

        try {
            $taskConfig = $this->task->getConfig();
            $api = new OkapiAdapter($taskConfig);
            $okapiConfig = $taskConfig->runtimeOptions->plugins->Okapi;
            $serverUsed = $okapiConfig->serverUsed ?? 'not set';
            $this->logger->info(
                'E1444',
                'Okapi Plug-In: File "{fileName}" (id: {fileId}) was imported with Okapi "{okapi}"',
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
            $filterManager = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
            $filterManager->addFilter(
                $filterManager::TYPE_IMPORT,
                $this->taskGuid,
                $fileId,
                editor_Plugins_Okapi_FileFilter::class
            );
            $filterManager->addFilter(
                $filterManager::TYPE_EXPORT,
                $this->taskGuid,
                $fileId,
                editor_Plugins_Okapi_FileFilter::class
            );
        } catch (Exception $e) {
            $this->handleException($e, $file, $fileId, true);
        } finally {
            $api->removeProject();
        }

        return true;
    }

    /**
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    protected function doExport(): bool
    {
        $params = $this->workerModel->getParameters();
        $fileId = (int) $params['fileId'];
        $workFile = new SplFileInfo($params['file']);
        $manifestFile = new SplFileInfo($this->getDataDir() . '/' . $this->getManifestFile($fileId));

        $taskConfig = $this->task->getConfig();
        $api = new OkapiAdapter($taskConfig);

        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());
        $result = false;

        try {
            $plugin = $this->getOkapiPlugin();
            $api->createProject();

            $api->uploadOkapiConfig($plugin::getExportBconfPath($this->task), false);

            $api->uploadInputFile('manifest.rkm', $manifestFile);
            $originalFile = $this->findOriginalFile($fileId);
            $api->uploadOriginalFile($originalFile, new SplFileInfo($this->getDataDir() . '/' . $originalFile));
            $this->xliffExportPreValidation($workFile);

            //if a file with source in empty targets exists, take that for okapi reconvertion
            $workfile2 = new SplFileInfo($workFile . editor_Models_Export_FileParser_Xlf::SOURCE_TO_EMPTY_TARGET_SUFFIX);
            if ($workfile2->isFile()) {
                $api->uploadWorkFile($originalFile . $api::OUTPUT_FILE_EXTENSION, $workfile2);
                if (ZfExtended_Debug::hasLevel('plugin', 'OkapiKeepIntermediateFiles')) {
                    $this->saveIntermediateWorkFile($workfile2, $fileId);
                }
                unlink($workfile2);
                //workfile (.xlf) is kept in export for further processing of the XLF
            } else {
                $api->uploadWorkFile($originalFile . $api::OUTPUT_FILE_EXTENSION, $workFile);
                if (ZfExtended_Debug::hasLevel('plugin', 'OkapiKeepIntermediateFiles')) {
                    $this->saveIntermediateWorkFile($workFile, $fileId);
                }
            }
            $api->executeTask($sourceLang, $targetLang);

            //the exported work file (containing xlf) must be renamed so that
            // the merged file can be saved under the original file name
            if ($taskConfig->runtimeOptions->plugins->Okapi->preserveGeneratedXlfFiles) {
                rename($workFile, $workFile . $api::OUTPUT_FILE_EXTENSION);
            }
            $api->downloadMergedFile($originalFile, $workFile);
            //TRANSLATE-2002: Currently Okapi can not reconvert PDF files,
            // therefore it provides a txt file, so we have to rename the file though
            if (strtolower($workFile->getExtension()) === 'pdf'
                && mime_content_type((string) $workFile) == 'text/plain') {
                rename($workFile, $workFile . '.txt');
            }

            $result = true;
        } catch (Exception $e) {
            $event = $this->handleException($e, $workFile, $fileId, false);
            $this->logEmptyTargets($fileId, $workFile);

            if (file_exists($workFile)) {
                //we add the XLF file suffix, since the workfile is now still a XLF file.
                rename($workFile, $workFile . $api::OUTPUT_FILE_EXTENSION);
            }
            // add an export-error file to notify users
            file_put_contents(
                dirname($workFile) . '/export-error.txt',
                'The file "' . basename($workFile) . '" could not be exported due to errors in Okapi:' .
                    "\n " . (is_null($event) ? $e->getMessage() : $event->oneLine()),
                FILE_APPEND
            );
        } finally {
            $api->removeProject();
        }

        return $result;
    }

    /**
     * Logs the occured exception
     * @param bool $import true on import, false on export
     * @return ZfExtended_Logger_Event|null the resulting event of the thrown exception
     * @throws Zend_Exception
     */
    protected function handleException(
        Exception $e,
        SplFileInfo $file,
        int $fileId,
        bool $import,
    ): ?ZfExtended_Logger_Event {
        $event = $this->logger->exception($e, [
            'extra' => [
                'task' => $this->task,
            ],
            'level' => ZfExtended_Logger::LEVEL_DEBUG,
        ]);

        if ($import) {
            $params = [
                'errorCode' => 'E1058',
                'errorMsg' => 'Okapi Plug-In: Error in converting file "{file}" on import: {message}',
                'errorExtra' => [
                    'message' => $e->getMessage(),
                    'fileId' => $fileId,
                    'file' => basename($file->__toString()),
                ],
            ];
            // this will trigger an exception when the filter is resolved in the main application on import/export finish
            $filterManager = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
            $filterManager->addFilter(
                $filterManager::TYPE_IMPORT,
                $this->taskGuid,
                $fileId,
                editor_Plugins_Okapi_FileFilter::class,
                json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } else {
            // this will create an error in the task-events
            $this->logger->error('E1151', 'Okapi Plug-In: Error in converting file "{file}" on export: {message}', [
                'task' => $this->task,
                'message' => $e->getMessage(),
                'fileId' => $fileId,
                'file' => basename($file->__toString()),
            ]);
        }

        return $event;
    }

    /**
     * Does some validation of the XLIFF file to improve debugging
     */
    protected function xliffExportPreValidation(SplFileInfo $workFile): void
    {
        $content = file_get_contents($workFile);
        $this->hadEmptyTargets = (bool) preg_match('#<target[^>]*/>#', $content);
    }

    /**
     * returns the manifest.rkm file for a stored file
     */
    protected function getManifestFile(int $fileId): string
    {
        return sprintf(self::MANIFEST_FILE, $fileId);
    }

    /**
     * returns the original file for a stored file (stored in the okapi data dir)
     * @throws OkapiException
     */
    protected function findOriginalFile(int $fileId): string
    {
        $regex = '/' . sprintf(self::ORIGINAL_FILE, $fileId . '\\', '.*$/');
        $files = preg_grep($regex, scandir($this->getDataDir()));
        $file = reset($files);
        if ($file === false) {
            throw new OkapiException('E1058', [
                'message' => 'Original file could not be found',
                'file' => sprintf(self::ORIGINAL_FILE, $fileId, '*'),
                'task' => $this->task,
            ]);
        }

        return (string) $file;
    }

    /**
     * copy the original files from review folder to the referenceFiles folder,
     * keep original file and directory structure
     * @throws Zend_Exception
     */
    protected function copyOriginalAsReference(): void
    {
        if (! $this->isAttachOriginalAsReference()) {
            return;
        }
        $params = $this->workerModel->getParameters();
        $importConfig = $params['importConfig'];
        /* @var $importConfig editor_Models_Import_Configuration */

        $realFile = $params['file'];
        $refFolder = $params['importFolder'] . '/' . editor_Models_Import_DirectoryParser_ReferenceFiles::getDirectory();
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
     * returns the path to the okapi data dir
     * @throws OkapiException
     */
    protected function getDataDir(): SplFileInfo
    {
        $okapiDataDir = new SplFileInfo($this->task->getAbsoluteTaskDataPath() . '/' . self::OKAPI_REL_DATA_DIR);
        if (! $okapiDataDir->isDir()) {
            mkdir((string) $okapiDataDir, 0777, true);
        }
        if (! $okapiDataDir->isWritable()) {
            //Okapi Plug-In: Data dir not writeable
            throw new OkapiException('E1057', [
                'okapiDataDir' => $okapiDataDir,
            ]);
        }

        return $okapiDataDir;
    }

    /***
     * Is configured the original files to be attached as reference files.
     * When no config is provided the original will be attached as reference.
     * @return boolean
     * @throws Zend_Exception
     */
    protected function isAttachOriginalAsReference(): bool
    {
        return (bool) Zend_Registry::get('config')->runtimeOptions
            ->plugins->Okapi->import->fileconverters->attachOriginalFileAsReference;
    }

    /***
     * The batch worker takes approximately 5% of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int
    {
        return 5;
    }

    /**
     * @throws Zend_Exception
     * @throws ZfExtended_Plugin_Exception
     * @throws OkapiException
     */
    private function getOkapiPlugin(): editor_Plugins_Okapi_Init
    {
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $pluginName = $pm::getPluginNameByClass(get_class($this));
        if (! $pm->isActive($pluginName) || is_null($plugin = $pm->get($pluginName))) {
            throw new OkapiException('E1474');
        }

        return $plugin;
    }

    private function logEmptyTargets(mixed $fileId, SplFileInfo $workFile): void
    {
        if ($this->hadEmptyTargets) {
            $this->logger->warn(
                'E1150',
                'Okapi Plug-In: The exported XLIFF "{file}" contains empty targets,' .
                    ' the Okapi process will probably fail then.',
                [
                    'task' => $this->task,
                    'fileId' => $fileId,
                    'file' => basename($workFile),
                ]
            );
        }
    }

    /**
     * Saves the workfile for further investigtion when debugging
     * @throws OkapiException
     */
    protected function saveIntermediateWorkFile(string $workFilePath, int $fileId): void
    {
        $targetFileName = 'workfile-' . $fileId . '.' . pathinfo($workFilePath, PATHINFO_EXTENSION);
        copy($workFilePath, $this->getDataDir() . '/' . $targetFileName);
    }
}
