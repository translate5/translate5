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
        $this->checkFiles($importFolder);
    }

    public function checkFiles($importFolder){
        // /var/www/translate5/application/../data/editorImportedTasks/c7105c57-f270-4c05-b79a-43756141e3f2/_tempImport
        // proofRead
        $proofRead='proofRead';
        $proofReadFolder=$importFolder.'/'.$proofRead;

        $it = new FilesystemIterator($proofReadFolder);
        
        $matchFiles=[];
        
        foreach ($it as $fileinfo) {
            if ($fileinfo->isFile() && in_array($fileinfo->getExtension(),$this->okapyFileTypes)) {
                //$matchFiles[]=$fileinfo->getFilename();
                $matchFiles[]=$fileinfo->getPathname();
            }
        }

        if(empty($matchFiles)){
            return;
        }

        $this->handleFiles($matchFiles,$importFolder);
    }

    public function handleFiles($matchFiles,$importFolder){
        $worker = ZfExtended_Factory::get('editor_Plugins_Okapi_Worker');
        /* @var $worker editor_Plugins_Okapi_Worker */
        
        $params=[
            'matchFiles'=>$matchFiles,
            'importFolder'=>$importFolder
        ];
        
        // init worker and queue it
        if (!$worker->init($this->task->getTaskGuid(), $params)) {
            $this->log->logError('Okapi-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue(null);
    }

     /***
     * Move the uploaded review file to the tempImport directory (single upload only)
     * @param string $importFolder - temp import directory path
     */
     private function moveFilesToTempImport($importFolder){
         error_log($importFolder);
         return;
        
        $tmpFileInfo = pathinfo($this->visualReviewFile['visualReview']['tmp_name']);
        
        // nothing to to because no review files are submitted
        if (!isset($tmpFileInfo['dirname']) || empty($tmpFileInfo['filename'])) {
            return;
        }
        
        $dest = $importFolder."/".self::VISUAL_REVIEW_FOLDER_NAME;
        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest);
        }
        //applay the filename
        $dest.="/".$this->visualReviewFile['visualReview']['name'];
        $source=$tmpFileInfo['dirname']."/".$tmpFileInfo['filename'];
        
        rename($source, $dest);
    }
 
}
