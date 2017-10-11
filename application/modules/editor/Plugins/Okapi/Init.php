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
    
    /***
     * Supported file-types by okapi
     * 
     * @var array
     */
    private $okapiFileTypes = array(
            'html'
    );
    
    /***
     * The okapi config file-types
     * @var array
     */
    private $okapiBconf= array(
            'bconf'
    );

    /***
     * Name of the default okapi config file
     * 
     * @var string
     */
    const OKAPI_BCONF_DEFAULT_NAME='okapi_default_import.bconf';
    
    
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
        $this->eventManager->attach('editor_Models_Import_DirectoryParser_WorkingFiles', 'beforeFileNodeCreate', array($this, 'handleBeforeFileNodeCreate'));
        $this->eventManager->attach('editor_Models_Foldertree_SyncToFiles', 'afterImportfileSave', array($this, 'handleAfterImportfileSave'));
    }

    /***
     * Find the files from type okapi, so the okapi file extenssion is removed from the file on the disk.
     * This is done so we can know for which files we need to add export/import post processing
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeFileNodeCreate(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $node=$params['node'];
        
        //check if the file has .okapi extenssion
        if($this->isOkapiGeneratedFile($node->filename)){
            
            $filePath=$params['filePath'];
            
            $replacePath=str_replace('.okapi', '', $filePath);
            $newFileName=str_replace('.okapi', '', $node->filename);
            
            //rename the file
            rename($filePath, $replacePath);
            
            $node->isOkapiFile=true;
            $node->filename=$newFileName;
            $node->path=$replacePath;
        }
    }
    
    public function handleBeforeImport(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $importFolder=$params['importFolder'];
        $task=$params['task'];
        $this->task=$task;
        //$this->moveFilesToTempImport($importFolder);
        $this->handleFiles($importFolder);
    }
    
    /***
     * For files from okapi filetype add a filefilter
     * 
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterImportfileSave(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $node=$params['node'];
        $file=$params['file'];
        
        if(!isset($node->isOkapiFile) || !$node->isOkapiFile){
            return;
        }
        
        $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
        /* @var $fileFilter editor_Models_File_FilterManager */

        $fileFilter->addFilter($fileFilter::TYPE_EXPORT, $file->getTaskGuid(), $file->getId(), 'editor_Plugins_Okapi_FileFilter');
    }

    public function handleFiles($importFolder){
        $import = Zend_Registry::get('config')->runtimeOptions->import;
        
        //proofread folder
        $proofReadFolder=$importFolder.'/'.$import->importFilesDirectory;
        
        //the task folder
        $taskFolder=str_replace("_tempImport","",$importFolder);
        
        //reference files directory path
        $refFolder = $taskFolder.$import->referenceDirectory;
        
        $directory = new RecursiveDirectoryIterator($taskFolder);
        $it= new RecursiveIteratorIterator($directory);
        
        $matchFiles=[];
        $bconfFilePath=[];
        
        //find all files supported by okapi and move them in the okapi directory
        foreach ($it as $fileinfo) {
            if(!$fileinfo->isFile()){
                continue;
            }
            
            if (in_array($fileinfo->getExtension(),$this->okapiFileTypes)) {
                //add the match file in the matches array
                $matchFiles[]=[
                        'fileName'=>$fileinfo->getFilename(),
                        'filePath'=>$fileinfo->getPathname(),
                ];
                continue;
            }
            
            if (in_array($fileinfo->getExtension(),$this->okapiBconf)) {
                $bconfFilePath[]=$fileinfo->getPathname();
                continue;
            }
        }
        
        //if no files are found do nothing
        if(empty($matchFiles)){
            return;
        }
        
        if(empty($bconfFilePath)){
            $bconfFilePath[]=$this->getDefaultBconf();
        }
        
        foreach ($matchFiles as $file) {
            //FIXME in the worker, we move the files from the okapi dir, to hhe reference files dir, the file need to be on the first level of the refernece files dir
            $this->queueWorker($file,$bconfFilePath,$refFolder,$proofReadFolder);
        }
    }

    /***
     * Run for each file a separate worker, the worker will upload the file to the okapi, convert the file, and download the 
     * result
     * 
     * @param string $file - the source file
     * @param string $bconfFilePath - the path of the bconf file
     * @param string $refFolder - referenceFiles directory of the current task 
     * @param string $proofReadFolder - prooofRead folder of the current task
     * @return boolean
     */
    public function queueWorker($file,$bconfFilePath,$refFolder,$proofReadFolder){
        $worker = ZfExtended_Factory::get('editor_Plugins_Okapi_Worker');
        /* @var $worker editor_Plugins_Okapi_Worker */
        
        $params=[
            'file'=>$file,
            'bconfFilePath'=>$bconfFilePath,
            'refFolder'=>$refFolder,
            'proofReadFolder'=>$proofReadFolder
        ];
        
        // init worker and queue it
        if (!$worker->init($this->task->getTaskGuid(), $params)) {
            $this->log->logError('Okapi-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue(null);
    }

    /**
     * Return the default bconf file for the import
     * @return string
     */
    private function getDefaultBconf(){
        return APPLICATION_PATH.'/'.$this->getPluginPath().'/'.'data'.'/'.self::OKAPI_BCONF_DEFAULT_NAME;
    }
    
    public function isOkapiGeneratedFile($filename){
        $retVal=substr($filename, -strlen(editor_Plugins_Okapi_Connector::OKAPI_FILE_EXTENSION));
        return $retVal=== editor_Plugins_Okapi_Connector::OKAPI_FILE_EXTENSION;
    }
 
}
