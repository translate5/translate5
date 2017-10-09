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

/**
 */
class editor_Plugins_Okapi_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
    );
    
    private $okapyFileTypes = array(
            'html'
    );
    
    private $okapyBconf= array(
            'bconf'
    );
    
    
    const OKAPI_BCONF_DEFAULT_NAME='okapi_default_bconf.bconf';
    
    const OKAPI_DIRECTORY_NAME='OkapiDirectory';

    private $task;
    /* @var $task editor_Models_Task */

    protected $localePath = 'locales';
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'Okapi')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $this->initEvents();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('editor_Models_Import', 'beforeImport', array($this, 'handleBeforeImport'));
    }

    public function handleBeforeImport(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $importFolder=$params['importFolder'];
        $task=$params['task'];
        $this->task=$task;
        //$this->moveFilesToTempImport($importFolder);
        $this->handleFiles($importFolder);
    }

    public function handleFiles($importFolder){
        // /var/www/translate5/application/../data/editorImportedTasks/c7105c57-f270-4c05-b79a-43756141e3f2/_tempImport
        // proofRead
        $proofRead='proofRead';
        $proofReadFolder=$importFolder.DIRECTORY_SEPARATOR.$proofRead;
        
        $taskFolder=str_replace("_tempImport","",$importFolder);
        $okapiDir=$taskFolder.DIRECTORY_SEPARATOR.self::OKAPI_DIRECTORY_NAME.DIRECTORY_SEPARATOR;
        
        //create the okapi directory on the disk in the task folder
        if (!is_dir($okapiDir)) {
            mkdir($okapiDir, 0777, true);
        }
        
        
        $it = new FilesystemIterator($proofReadFolder);
        
        $matchFilesName=[];
        $bconfFilePath=[];
        
        //find all files supported by okapi and move them in the okapi directory
        foreach ($it as $fileinfo) {
            if(!$fileinfo->isFile()){
                continue;
            }
            
            if (in_array($fileinfo->getExtension(),$this->okapyFileTypes)) {
                //$matchFiles[]=$fileinfo->getFilename();
                //move the files in the okapi directory
                rename($fileinfo->getPathname(),$okapiDir.$fileinfo->getFilename());
                
                //add the match file in the matches array
                $matchFilesName[]=$fileinfo->getFilename();
                continue;
            }
            
            if (in_array($fileinfo->getExtension(),$this->okapyBconf)) {
                //$matchFiles[]=$fileinfo->getFilename();
                rename($fileinfo->getPathname(),$importFolder.DIRECTORY_SEPARATOR.$fileinfo->getFilename());
                $bconfFilePath[]=$importFolder.DIRECTORY_SEPARATOR.$fileinfo->getFilename();
                continue;
            }
        }
        
        //if no files are found do nothing
        if(empty($matchFilesName)){
            return;
        }
        
        if(empty($bconfFilePath)){
            $bconfFilePath=$this->getDefaultBconf();
        }
        
        foreach ($matchFilesName as $fileName) {
            $this->queueWorker($fileName,$bconfFilePath,$okapiDir);
        }
    }

    /***
     * Run for each file a separate worker, the worker will upload the file to the okapi, convert the file, and download the 
     * result
     * 
     * @param string $fileName - the name of the file
     * @param string $bconfFilePath - the path of the bconf file
     * @param string $okapiDir - the path of the okapi dir on the in the task folder
     * @return boolean
     */
    public function queueWorker($fileName,$bconfFilePath,$okapiDir){
        $worker = ZfExtended_Factory::get('editor_Plugins_Okapi_Worker');
        /* @var $worker editor_Plugins_Okapi_Worker */
        
        $params=[
            'fileName'=>$fileName,
            'bconfFilePath'=>$bconfFilePath,
            'okapiDir'=>$okapiDir
        ];
        
        // init worker and queue it
        if (!$worker->init($this->task->getTaskGuid(), $params)) {
            $this->log->logError('Okapi-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue(null);
    }

    private function getDefaultBconf(){
        $dataDir=APPLICATION_PATH.'/../data';
        return $dataDir.'/'.self::OKAPI_BCONF_DEFAULT_NAME;
    }
 
}
