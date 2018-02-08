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
        $manifestFile = sprintf(self::MANIFEST_FILE, $fileId);
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
            $api->uploadSourceFile($fileName, $file);
            $api->executeTask($sourceLang, $targetLang);
            $convertedFile = $api->downloadFile($fileName, $manifestFile, $okapiDataDir);

            $this->copyOriginalAsReference();
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
        error_log("WORKER STARTED");
        
        /*
          #!/usr/bin/env python
import requests
from xml.dom import minidom

url = 'http://localhost:8080/okapi-longhorn/'

r = requests.post(url+'projects/new')
print r.text

r = requests.get(url+'projects/')
xmlstring = minidom.parseString(r.text)
itemlist = xmlstring.getElementsByTagName('e')
lastproject = len(itemlist)

payload = open('/home/marcstandard/Downloads/okapi-test-rueckweg/work/Schnittstellen _ Across.html.xlf', 'rb')
r = requests.post(url+'projects/'+str(lastproject)+'/inputFiles/work/Schnittstellen _ Across.html.xlf', files=dict(inputFile=payload))
         * */
        
        $params = $this->workerModel->getParameters();
        error_log(print_r($params,1));
        
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */
        $plugin = $pm->get($pm->classToName(get_class($this)));
        
        $api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        $api->setBconfFilePath($plugin->getExportBconf());
        return true;
        
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        
        $api->setSourceLang($language->loadLangRfc5646($this->task->getSourceLang()));
        $api->setTargetLang($language->loadLangRfc5646($this->task->getTargetLang()));
        $api->setInputFile($file);
        
        try {
            $api->createProject();
            $api->uploadOkapiConfig();
            $api->uploadSourceFile();
            $api->executeTask();
            $api->downloadFile();

            //move the original files in the referenceFiles dir, in the same filestrucutre
            //as thay were durring the import
            $fileDir=dirname($file['filePath']);
            $folders=str_replace($proofReadFolder,"",$fileDir);
            if(!empty($folders)){
                $refFolder=$refFolder.$folders;
            }
            
            if (!is_dir($refFolder)) {
                mkdir($refFolder, 0777, true);
            }
            $originalFile = $file['filePath'];
            $referenceFile = $refFolder.'/'.$file['fileName'];
            rename($originalFile, $referenceFile);
        }catch (Exception $e){
            $this->log->logError('Okapi Error: Error on converting a file. Task: '.$taskGuid.'; File: '.print_r($file, 1).'; Error was: '.$e);
        }finally {
            $api->removeProject();
        }
        return true;
    }
    
    /**
     * copy the original files from proofRead folder to the referenceFiles folder,
     * keep original file and directory structure
     */
    protected function copyOriginalAsReference() {
        $params = $this->workerModel->getParameters();
        $import = Zend_Registry::get('config')->runtimeOptions->import;
        
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
}
