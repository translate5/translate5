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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_Okapi_Worker extends editor_Models_Import_Worker_Abstract {
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
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
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
            // in case of an exception we just ignore that file, log it, and proceed with the import
            $debug = [
                'fileId' => $fileId,
                'file' => $file->__toString(),
            ];
            $this->log->logError('Okapi Error: Error on converting a file. Task: '.$this->taskGuid.'; File: '.print_r($debug, 1).'; Error was: '.$e);
        }finally {
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
        
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        
        $sourceLang = $language->loadLangRfc5646($this->task->getSourceLang());
        $targetLang = $language->loadLangRfc5646($this->task->getTargetLang());
        $result = false;
        
        try {
            $api->createProject();
            $api->uploadOkapiConfig([$plugin->getExportBconf()]);
            
            $api->uploadInputFile('manifest.rkm', $manifestFile);
            $originalFile = $this->findOriginalFile($fileId);
            $api->uploadOriginalFile($originalFile, new SplFileInfo($this->getDataDir().'/'.$originalFile));
            $api->uploadWorkFile($originalFile.$api::OUTPUT_FILE_EXTENSION, $workFile);
            $api->executeTask($sourceLang, $targetLang);
            //the exported work file (containing xlf) must be renamed so that 
            // the merged file can be saved under the original file name 
            rename($workFile, $workFile.$api::OUTPUT_FILE_EXTENSION);
            $api->downloadMergedFile($originalFile, $workFile);
            $result = true;
        } catch (Exception $e){
            $debug = [
                'fileId' => $fileId,
                'file' => $workFile,
            ];
            $this->log->logError('Okapi Error: Error on converting a file. Task: '.$this->taskGuid.'; File: '.print_r($debug, 1).'; Error was: '.$e);
        }
        $api->removeProject();
        return $result; 
    }
    
    /**
     * returns the manifest.rkm file for a stored file
     * @param integer $fileId
     * @return string
     */
    protected function getManifestFile($fileId) {
        return sprintf(self::MANIFEST_FILE, $fileId);
    }
    
    /**
     * returns the original file for a stored file (stored in the okapi data dir)
     * @param integer $fileId
     * @return string
     */
    protected function findOriginalFile($fileId) {
        $regex = '/'.sprintf(self::ORIGINAL_FILE, $fileId.'\\', '.*$/');
        $files = preg_grep($regex, scandir($this->getDataDir()));
        return reset($files);
    }
    
    /**
     * copy the original files from proofRead folder to the referenceFiles folder,
     * keep original file and directory structure
     */
    protected function copyOriginalAsReference() {
        if(!$this->isAttachOriginalAsReference()){
            return;
        }
        $config=Zend_Registry::get('config')->runtimeOptions;
        $params = $this->workerModel->getParameters();
        $import = $config->import;
        
        $realFile = $params['file'];
        $refFolder = $params['importFolder'].'/'.$import->referenceDirectory;
        $proofReadFolder = $params['importFolder'].'/'.$import->proofReadDirectory;
        
        //cut off proofread folder from realfile:
        $relRealFile = str_replace('#'.realpath($proofReadFolder), '', '#'.realpath($realFile));
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
            throw new editor_Plugins_Okapi_Exception("Okapi Data dir not writeable: ".$okapiDataDir);
        }
        return $okapiDataDir;
    }
    
    /**
     * uses tikal export / merge as fallback for imports started before the okapi export usage
     * @param SplFileInfo $workfile
     */
    protected function doTikalFallback(SplFileInfo $workfile) {
        if(strtolower($workfile->getExtension()) !== 'xlf') {
            throw new editor_Plugins_Okapi_Exception('Okapi tikal fallback can not be used, workfile does not contain the XLF suffix: '.$workfile);
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
        $config=Zend_Registry::get('config')->runtimeOptions;
        $attachOriginalFileAsReference=isset($config->fileconvertors->attachOriginalFileAsReference) ? $config->fileconvertors->attachOriginalFileAsReference : false;
        
        if(!$attachOriginalFileAsReference){
            return true;
        }
        
        $attachOriginalFileAsReference=$attachOriginalFileAsReference->toArray();
        if(empty($attachOriginalFileAsReference)){
            return true;
        }
        
        //check the okapi config
        foreach ($attachOriginalFileAsReference as $fileconverter) {
            $obj=get_object_vars($fileconverter);
            if(isset($obj['Okapi']) && !boolval($obj['Okapi'])){
                return false;
            }
        }
        
        return true;
    }
}
