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

class editor_Plugins_Okapi_Worker extends editor_Models_Import_Worker_Abstract {
    

    /**
     * @var editor_Plugins_Okapi_Connector
     */
    protected $api;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    } 
    
    /**
     * Uploads one file to Okapi to convert it to an XLIFF file importable by translate5
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $params = $this->workerModel->getParameters();
        $file=$params['file'];
        $refFolder=$params['refFolder'];
        $proofReadFolder=$params['proofReadFolder'];
        $taskGuid=$params['taskGuid'];
        
        $this->api = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        $this->api->setBconfFilePath($params['bconfFilePath']);
        
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        
        $this->api->setSourceLang($language->loadLangRfc5646($this->task->getSourceLang()));
        $this->api->setTargetLang($language->loadLangRfc5646($this->task->getTargetLang()));
        $this->api->setInputFile($file);
        
        try {
            $this->api->createProject();
            $this->api->uploadOkapiConfig();
            $this->api->uploadSourceFile();
            $this->api->executeTask();
            $this->api->downloadFile();

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
            $this->api->removeProject();
        }
        return true;
    }
}
