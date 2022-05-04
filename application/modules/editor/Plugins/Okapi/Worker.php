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

class editor_Plugins_Okapi_Worker extends editor_Models_Task_AbstractWorker {
    
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    const OKAPI_REL_DATA_DIR = 'okapi-data';
    /**
     * filename template for storing the manifest files
     * @var string
     */
    const MANIFEST_FILE = 'manifest-%s.rkm';
    /**
     * filename template for storing the original files
     * @var string
     */
    const ORIGINAL_FILE = 'original-%s.%s';
    
    /**
     * Api for accessing the saved Manifest file from other contexts
     * @param string $absoluteTaskDataPath
     * @param string $fileId
     * @return string
     */
    public static function createManifestFilePath($absoluteTaskDataPath, $fileId){
        return $absoluteTaskDataPath.'/'.self::OKAPI_REL_DATA_DIR.'/'.sprintf(self::MANIFEST_FILE, $fileId);
    }
    /**
     * Api for accessing the saved original file from other contexts
     * @param string $absoluteTaskDataPath
     * @param string $fileId
     * @param string $extension
     * @return string
     */
    public static function createOriginalFilePath($absoluteTaskDataPath, $fileId, $extension){
        return $absoluteTaskDataPath.'/'.self::OKAPI_REL_DATA_DIR.'/'.sprintf(self::ORIGINAL_FILE, $fileId, $extension);
    }
    /**
     * Api for accessing the saved  converted file from other contexts
     * @param string $absoluteTaskDataPath
     * @param string $fileId
     * @param string $extension
     * @return string
     */
    public static function createConvertedFilePath($absoluteTaskDataPath, $fileId, $extension){
        $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        /* @var $api editor_Plugins_Okapi_Connector */
        return self::createOriginalFilePath($absoluteTaskDataPath, $fileId, $extension).$api::OUTPUT_FILE_EXTENSION;
    }
    /**
     * @var ZfExtended_Logger
     */
    protected $logger;

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['type'])
                || !($parameters['type'] == self::TYPE_IMPORT || $parameters['type'] == self::TYPE_EXPORT)){
            return false;
        }
        return true;
    }

    public function init($taskGuid = NULL, $parameters = array()) {
        $result = parent::init($taskGuid, $parameters);
        if($result && $parameters['type'] === self::TYPE_EXPORT) {
            //on export we just use normal maintenance check, not the extended one for imports
            $this->behaviour->setConfig(['isMaintenanceScheduled' => true]);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.okapi');
        $params = $this->workerModel->getParameters();
        if($params['type'] == self::TYPE_IMPORT) {
            return $this->doImport();
        }
        return $this->doExport();
    }

    /**
     * Uploads one file to Okapi to convert it to an XLIFF file importable by translate5
     */
    protected function doImport() {
        $params = $this->workerModel->getParameters();
        
        $file = new SplFileInfo($params['file']);
        $suffix = $file->getExtension();
        $fileId = $params['fileId'];
        $fileName = sprintf(self::ORIGINAL_FILE, $fileId, $suffix);
        $manifestFile = $this->getManifestFile($fileId);
        $okapiDataDir = $this->getDataDir();
        
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());
        
        try {
            $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
            /* @var $api editor_Plugins_Okapi_Connector */
            $api->createProject();
            $api->uploadOkapiConfig($params['bconfFilePath']);
            $api->uploadInputFile($fileName, $file);
            $api->executeTask($sourceLang, $targetLang);
            $convertedFile = $api->downloadFile($fileName, $manifestFile, $okapiDataDir);

            //copy original into data dir for export
            copy($file, $okapiDataDir.'/'.$fileName);
            //copy original to reference files
            $this->copyOriginalAsReference();
            //copy generated XLF into importFolder
            copy($convertedFile, $file.$api::OUTPUT_FILE_EXTENSION);
            
            //add okapi export file filter for that file
            $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
            /* @var $fileFilter editor_Models_File_FilterManager */
            $fileFilter->addFilter($fileFilter::TYPE_IMPORT, $this->taskGuid, $fileId, 'editor_Plugins_Okapi_FileFilter');
            $fileFilter->addFilter($fileFilter::TYPE_EXPORT, $this->taskGuid, $fileId, 'editor_Plugins_Okapi_FileFilter');
        }catch (Exception $e){
            $this->handleException($e, $file, $fileId, true);
        } finally {
            $api->removeProject();
        }
        return true;
    }
    
    /**
     * @return boolean
     */
    protected function doExport() {
        $params = $this->workerModel->getParameters();
        $fileId = $params['fileId'];
        $workFile = new SplFileInfo($params['file']);
        
        $manifestFile = new SplFileInfo($this->getDataDir().'/'.$this->getManifestFile($fileId));
        //if we don't have a manifest.rkm file, the import was before we changed the export,
        // so use tikal export there again
        if(!$manifestFile->isFile()) {
            $this->doTikalFallback($workFile); //should be removed in the future
            return true;
        }
        
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $plugin = $pm->get($pm->classToName(get_class($this)));
        
        $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        /* @var $api editor_Plugins_Okapi_Connector */
        
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());
        $result = false;
        
        try {
            $api->createProject();

            $api->uploadOkapiConfig($plugin::getExportBconfPath($this->task));
            
            $api->uploadInputFile('manifest.rkm', $manifestFile);
            $originalFile = $this->findOriginalFile($fileId);
            $api->uploadOriginalFile($originalFile, new SplFileInfo($this->getDataDir().'/'.$originalFile));
            $this->xliffExportPreValidation($workFile, $fileId);
            
            //if a file with source in empty targets exists, take that for okapi reconvertion
            $workfile2 = new SplFileInfo($workFile.editor_Models_Export_FileParser_Xlf::SOURCE_TO_EMPTY_TARGET_SUFFIX);
            if($workfile2->isFile()) {
                $api->uploadWorkFile($originalFile.$api::OUTPUT_FILE_EXTENSION, $workfile2);
                unlink($workfile2); //we remove that file from the export, bad for debugging but keep things clean
                //workfile (.xlf) is kept in export for further processing of the XLF
            }
            else {
                $api->uploadWorkFile($originalFile.$api::OUTPUT_FILE_EXTENSION, $workFile);
            }
            
            $api->executeTask($sourceLang, $targetLang);
            //the exported work file (containing xlf) must be renamed so that
            // the merged file can be saved under the original file name
            rename($workFile, $workFile.$api::OUTPUT_FILE_EXTENSION);
            $api->downloadMergedFile($originalFile, $workFile);
            
            //TRANSLATE-2002: Currently Okapi can not reconvert PDF files, therefore it provides a txt file, so we have to rename the file though
            if(strtolower($workFile->getExtension()) === 'pdf' && mime_content_type((string)$workFile) == 'text/plain') {
                rename($workFile, $workFile.'.txt');
            }
            
            $result = true;
        } catch (Exception $e){
            $this->handleException($e, $workFile, $fileId, false);
            if(file_exists($workFile)) {
                //we add the XLF file suffix, since the workfile is now still a XLF file.
                rename($workFile, $workFile.$api::OUTPUT_FILE_EXTENSION);
            }
            //add a export-error file, pointing into the right direction
            file_put_contents(dirname($workFile).'/export-error.txt', basename($workFile).': could not be exported due errors in Okapi. See task event log for more details.'."\n", FILE_APPEND);
        } finally {
            $api->removeProject();
        }
        
        return $result;
    }
    
    /**
     * Logs the occured exception
     * @param Exception $e
     * @param SplFileInfo $file
     * @param integer $fileId
     * @param boolean $import true on import, false on export
     */
    protected function handleException(Exception $e, SplFileInfo $file, $fileId, bool $import) {
        $this->logger->exception($e, [
            'extra' => ['task' => $this->task],
            'level' => ZfExtended_Logger::LEVEL_DEBUG,
        ]);
        
        $absFile = $file->__toString();
        if($import) {
            $tmpImport = editor_Models_Import_DataProvider_Abstract::TASK_TEMP_IMPORT;
            $relFile = mb_strpos($absFile, $tmpImport);
            $relFile = mb_substr($absFile, $relFile + strlen($tmpImport));
            $code = 'E1058';
            $msg = 'Okapi Plug-In: Error in converting file {file} on import. See log details for more information.';
        }
        else {
            $relFile = mb_strpos($absFile, $this->task->getTaskGuid());
            $relFile = mb_substr($absFile, $relFile + strlen($this->task->getTaskGuid()));
            $code = 'E1151';
            $msg = 'Okapi Plug-In: Error in converting file {file} on export. See log details for more information.';
        }
        
        // in case of an exception we just ignore that file, log it, and proceed with the import/export
        $this->logger->warn($code, $msg, [
            'task' => $this->task,
            'message' => get_class($e).': '.$e->getMessage(),
            'fileId' => $fileId,
            'file' => $relFile,
            'filePath' => $absFile,
        ]);
    }

    /**
     * Does some validation of the XLIFF file to improve debugging
     */
    protected function xliffExportPreValidation(SplFileInfo $workFile, $fileId) {
        $content = file_get_contents($workFile);
        
        if(preg_match('#<target[^>]*/>#', $content)) {
            // in case of an exception we just ignore that file, log it, and proceed with the import/export
            $this->logger->warn('E1150', 'Okapi Plug-In: The exported XLIFF {file} contains empty targets, the Okapi process will probably fail then.', [
                'task' => $this->task,
                'fileId' => $fileId,
                'file' => basename($workFile),
            ]);
        }
    }
    
    
    /**
     * returns the manifest.rkm file for a stored file
     * @param int $fileId
     * @return string
     */
    protected function getManifestFile($fileId) {
        return sprintf(self::MANIFEST_FILE, $fileId);
    }
    
    /**
     * returns the original file for a stored file (stored in the okapi data dir)
     * @param int $fileId
     * @return string
     */
    protected function findOriginalFile($fileId) {
        $regex = '/'.sprintf(self::ORIGINAL_FILE, $fileId.'\\', '.*$/');
        $files = preg_grep($regex, scandir($this->getDataDir()));
        return reset($files);
    }
    
    /**
     * copy the original files from review folder to the referenceFiles folder,
     * keep original file and directory structure
     */
    protected function copyOriginalAsReference() {
        if(!$this->isAttachOriginalAsReference()){
            return;
        }
        $config=Zend_Registry::get('config')->runtimeOptions;
        $params = $this->workerModel->getParameters();
        $import = $config->import;
        $importConfig = $params['importConfig'];
        /* @var $importConfig editor_Models_Import_Configuration */
        
        $realFile = $params['file'];
        $refFolder = $params['importFolder'].'/'.$import->referenceDirectory;
        $workfilesDirectory = $params['importFolder'].'/'.$importConfig->getFilesDirectory();
        
        //cut off review folder from realfile:
        $relRealFile = str_replace('#'.realpath($workfilesDirectory), '', '#'.realpath($realFile));
        $absRefFile = $refFolder.'/'.$relRealFile;
        $absRefDir = dirname($absRefFile);
        
        //create directory if needed
        if (!is_dir($absRefDir)) {
            mkdir($absRefDir, 0777, true);
        }
        
        //we copy the file and keep the original file via fileId addressable for export (TRANSLATE-1138)
        rename($realFile, $absRefFile);
    }
    
    /**
     * returns the path to the okapi data dir
     */
    protected function getDataDir() {
        $okapiDataDir = new SplFileInfo($this->task->getAbsoluteTaskDataPath().'/'.self::OKAPI_REL_DATA_DIR);
        if(!$okapiDataDir->isDir()) {
            mkdir((string) $okapiDataDir, 0777, true);
        }
        if(!$okapiDataDir->isWritable()) {
            //Okapi Plug-In: Data dir not writeable
            throw new editor_Plugins_Okapi_Exception('E1057', ['okapiDataDir' => $okapiDataDir]);
        }
        return $okapiDataDir;
    }
    
    /**
     * uses tikal export / merge as fallback for imports started before the okapi export usage
     * @param SplFileInfo $workfile
     */
    protected function doTikalFallback(SplFileInfo $workfile) {
        if(strtolower($workfile->getExtension()) !== 'xlf') {
            // Okapi Plug-In: tikal fallback can not be used, workfile does not contain the XLF suffix',
            throw new editor_Plugins_Okapi_Exception('E1056', ['workfile' => $workfile]);
        }
        $tikal = ZfExtended_Factory::get('editor_Plugins_Okapi_Tikal_Connector', [$this->task]);
        /* @var $tikal editor_Plugins_Okapi_Tikal_Connector */
        $tikal->merge($workfile->__toString());
    }
    
    /***
     * Is configured the original files to be attached as reference files.
     * When no config is provided the original will be attached as reference.
     * @return boolean
     */
    protected function isAttachOriginalAsReference() {
        return (boolean)Zend_Registry::get('config')->runtimeOptions->plugins->Okapi->import->fileconverters->attachOriginalFileAsReference;
    }
    
    /***
     * The batch worker takes approximately 5% of the import time
     * 
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int {
        return 5;
    }
}
