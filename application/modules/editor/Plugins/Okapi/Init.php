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

/**
 */
class editor_Plugins_Okapi_Init extends ZfExtended_Plugin_Abstract {
    /***
     * Name of the default okapi config file
     *
     * @var string
     */
    const OKAPI_BCONF_DEFAULT_NAME='okapi_default_import.bconf';
    const OKAPI_BCONF_EXPORT_NAME='okapi_default_export.bconf';
    
    /***
     * Supported file-types by okapi
     *
     * @var array
     */
    private $okapiFileTypes = array(
            
            //FIXME WARNING: Only suffixes are used. MimeTypes are not needed anymore, since mimetype check was deactivated in UploadProcessor
            // but since there is currently no time to refactor the stuff, we leave it as it is and refactor it later
            
            'okapi' => ['application/xml'], //currently needed, see TRANSLATE-1019
            
            'pdf' => ['text/html'],
            
            'html' => ['text/html'],
            'htm' => ['text/html'],
            'xml' => ['application/xml'],
            //'csv' => ['text/csv'], disabled due our own importer
            'txt' => ['text/plain'],
            'dita' => ['application/dita+xml'],
            'ditamap' => ['application/dita+xml'],
            'c' => ['text/plain', 'text/c-x'],
            'h' => ['text/plain', 'text/h-x'],
            'cpp' => ['text/plain', 'text/c-x'],
            'dtd' => ['application/xml-dtd'],
            'wcml' => ['application/octet-stream'],
            'idml' => ['application/octet-stream'],
            'strings' => ['application/octet-stream'],
            'properties' => ['application/octet-stream'],
            'properties' => ['application/octet-stream'],
            'json' => ['application/json'],
            'catkeys' => ['application/octet-stream'], //guessed
            'catkeys' => ['application/octet-stream'],
            'md' => ['text/markdown'],
            'xlsx' => ['application/octet-stream', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats'],
            'xlsm' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats'],
            'xltx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats'],
            'xltm' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.openxmlformats'],
            'pptm' => ['application/vnd.ms-powerpoint.presentation.macroEnabled.12', 'application/vnd.openxmlformats'],
            'potx' => ['application/vnd.openxmlformats-officedocument.presentationml.template', 'application/vnd.openxmlformats'],
            'potm' => ['application/vnd.ms-powerpoint.template.macroEnabled.12', 'application/vnd.openxmlformats'],
            'ppsx' => ['application/vnd.openxmlformats-officedocument.presentationml.slideshow', 'application/vnd.openxmlformats'],
            'ppsm' => ['application/vnd.ms-powerpoint.slideshow.macroEnabled.12', 'application/vnd.openxmlformats'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats'],
            'docm' => ['application/vnd.ms-word.document.macroEnabled.12', 'application/vnd.openxmlformats'],
            'dotx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'application/vnd.openxmlformats'],
            'dotm' => ['application/vnd.ms-word.template.macroEnabled.12', 'application/vnd.openxmlformats'],
            'vsdx' => ['application/vnd.visio'],
            'vsdm' => ['application/vnd.visio'],
            'mif' => ['application/vnd.visio'],
            'ods' => ['application/octet-stream'],
            'ots' => ['application/octet-stream'],
            'odg' => ['application/octet-stream'],
            'otg' => ['application/octet-stream'],
            'odp' => ['application/octet-stream'],
            'otp' => ['application/octet-stream'],
            'odt' => ['application/octet-stream'],
            'ott' => ['application/octet-stream'],
            'pentm' => ['application/octet-stream'],
            'php' => ['application/octet-stream'],
            'po' => ['application/octet-stream'],
            'rkm' => ['application/octet-stream'],
            'rdf' => ['application/octet-stream'],
            'resx' => ['application/octet-stream'],
            'lang' => ['application/octet-stream'],
            'srt' => ['application/octet-stream'],
            'tsv' => ['application/octet-stream'],
            'tmx' => ['application/octet-stream'],
            'txp' => ['application/octet-stream'],
            'rtf' => ['application/octet-stream'],
            'ts' => ['application/octet-stream'],
            'ttx' => ['application/octet-stream'],
            'txml' => ['application/octet-stream'],
            'vrsz' => ['application/octet-stream'],
            'wix' => ['application/octet-stream'],
            'yml' => ['application/octet-stream'],
            'yaml' => ['application/octet-stream'], 
    );
    
    /***
     * The okapi config file-types
     * @var array
     */
    private $okapiBconf= array(
            'bconf'
    );
    
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'Okapi')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $fileTypes = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
        /* @var $fileTypes editor_Models_Import_SupportedFileTypes */
        foreach ($this->okapiFileTypes as $ext => $mimes) {
            $fileTypes->register($ext, $mimes);
        }
        $this->initEvents();
    }
    
    protected function initEvents() {
        //checks if import contains files for okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'afterDirectoryParsing', array($this, 'handleAfterDirectoryParsing'));
        
        //Archives the temporary data folder again after converting the files with okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', array($this, 'handleAfterImport'));
        
        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'handleApplicationState'));
    }
    
    /***
     * Hook on the before import event and check the import files
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterDirectoryParsing(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $filelist = $params['filelist'];
        $importFolder = $params['importFolder'];
        foreach($filelist as $fileId => $filePath) {
            $fileInfo = new SplFileInfo($importFolder.'/'.ZfExtended_Utils::filesystemEncode($filePath));
            if(!$this->isProcessable($fileInfo)) {
                continue;
            }
            $this->queueWorker($fileId, $fileInfo, $params);
        }
    }
    
    /**
     * Archives the temporary data folder again after converting the files with okapi
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterImport(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        $config = $event->getParam('importConfig');
        /* @var $config editor_Models_Import_Configuration */
        
        try {
            $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
            /* @var $worker ZfExtended_Models_Worker */
            $worker->loadFirstOf('editor_Plugins_Okapi_Worker', $task->getTaskGuid());
            
            //proceed with the archive only, if a okapi worker was found for the current task
            $directoryProvider = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Directory', [$config->importFolder]);
            /* @var $directoryProvider editor_Models_Import_DataProvider_Directory */
            $directoryProvider->setTask($task);
            $directoryProvider->archiveImportedData('OkapiArchive.zip');
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //no okapi worker -> do nothing 
        }
        
    }
    
    /**
     * looks for bconf files in the import root folder and returns them
     * @param string $importFolder
     * @return string[]
     */
    protected function getBconfFiles($importFolder) {
        $bconfFilePath=[];
        $directory = new DirectoryIterator($importFolder);
        
        foreach ($directory as $fileinfo) {
            if (in_array(strtolower($fileinfo->getExtension()),$this->okapiBconf)) {
                $bconfFilePath[]=$fileinfo->getPathname();
                continue;
            }
        }
        
        if(empty($bconfFilePath)){
            $bconfFilePath[]=$this->getDefaultBconf();
        }
        return $bconfFilePath;
    }
    
    /**
     * Checks if the given file processable by okapi
     * @param SplFileInfo $fileinfo
     * @return boolean
     */
    protected function isProcessable(SplFileInfo $fileinfo) {
        $extension = strtolower($fileinfo->getExtension());
        if(!$fileinfo->isFile()){
            return false;
        }
            
        $isXml = $extension == 'xml';
        if($extension && editor_Models_Import_FileParser_Xml::isParsable(file_get_contents($fileinfo))) {
            //Okapi supports XML, but if it is XLIFF in the XML file we don't need Okapi:
            return false;
        }
            
        return in_array($extension,array_keys($this->okapiFileTypes));
    }
    
    /**
     * Run for each file a separate worker, the worker will upload the file to the okapi, convert the file, and download the
     * result
     *
     * @param int $fileId
     * @param SplFileInfo $file
     * @param array $params
     * @return boolean
     */
    protected function queueWorker($fileId, SplFileInfo $file, array $params){
        $filelist = $params['filelist'];
        $importFolder = $params['importFolder'];
        $task = $params['task'];
        $bconfFilePath = $this->getBconfFiles($importFolder);
        $workerParentId = $params['workerParentId'];
        
        $worker = ZfExtended_Factory::get('editor_Plugins_Okapi_Worker');
        /* @var $worker editor_Plugins_Okapi_Worker */
        
        $params=[
                'type' => editor_Plugins_Okapi_Worker::TYPE_IMPORT,
                'fileId' => $fileId,
                'file' => (string) $file,
                'bconfFilePath' => $bconfFilePath,
                'importFolder' => $importFolder,
        ];
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->logError('Okapi-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($workerParentId);
    }
    
    /**
     * Return the default bconf file for the import
     * @return string
     */
    private function getDefaultBconf(){
        return APPLICATION_PATH.'/'.$this->getPluginPath().'/'.'data'.'/'.self::OKAPI_BCONF_DEFAULT_NAME;
    }
    
    /**
     * Return the default bconf file for the export
     * @return string
     */
    public function getExportBconf(){
        return APPLICATION_PATH.'/'.$this->getPluginPath().'/'.'data'.'/'.self::OKAPI_BCONF_EXPORT_NAME;
    }
    
    /**
     * Checks if the configured okapi instance is reachable
     * @param Zend_EventManager_Event $event
     */
    public function handleApplicationState(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->okapi = new stdClass();
        $connector = ZfExtended_Factory::get('editor_Plugins_Okapi_Connector');
        /* @var $connector editor_Plugins_Okapi_Connector */
        $applicationState->okapi->server = $connector->ping();
    }
}
