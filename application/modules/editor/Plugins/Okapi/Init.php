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
     * Supported file-types by okapi
     *
     * @var array
     */
    private $okapiFileTypes = array(
            
            //FIXME WARNING: MimeTypes ware not needed anymore, since check was deactivated in UploadProcessor
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
    
    /***
     * Name of the default okapi config file
     *
     * @var string
     */
    const OKAPI_BCONF_DEFAULT_NAME='okapi_default_import.bconf';
    
    
    /**
     * @var editor_Models_Task
     */
    private $task;
    
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
        $this->eventManager->attach('editor_Models_Import', 'beforeImport', array($this, 'handleBeforeImport'));
        $this->eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', array($this, 'handleAfterImport'));
        
        //Both following invocations into the file handling stuff are currently needed due the bad architecture of the File Data handling
        // as it is implemented right now (with .okapi file ending), it was the easiest way to communicate from one worker to another
        // that this file was created with okapi and for the export there should be added a file filter
        // Better solution would be to split DirectoryParsing from Import, see TRANSLATE-1019
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
        $okapiExt = editor_Plugins_Okapi_Connector::OKAPI_FILE_EXTENSION;
        
        //check if the file has .okapi extenssion
        if($this->isOkapiGeneratedFile($node->filename)){
            
            $filePath=$params['filePath'];
            
            $replacePath=str_replace($okapiExt, '', $filePath);
            $newFileName=str_replace($okapiExt, '', $node->filename);
            
            //remove the .okapi from the filename:
            rename($filePath, $replacePath);
            
            $node->isOkapiFile=true;
            $node->filename=$newFileName;
            $node->path=$replacePath;
        }
    }
    
    /***
     * Hook on the before import event and check the import files
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeImport(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $importFolder=$params['importFolder'];
        $task=$params['task'];
        $this->task=$task;
        //$this->moveFilesToTempImport($importFolder);
        $this->handleFiles($importFolder);
    }
    
    /**
     * Archives the temporary data folder again after converting the files with okapi
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterImport(Zend_EventManager_Event $event) {
        $config = $event->getParam('importConfig');
        /* @var $config editor_Models_Import_Configuration */
        $directoryProvider = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Directory', [$config->importFolder]);
        /* @var $directoryProvider editor_Models_Import_DataProvider_Directory */
        $directoryProvider->setTask($event->getParam('task'));
        $directoryProvider->archiveImportedData('OkapiArchive.zip');
    }
    
    /***
     * Add export file filter to files with okapi extension
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
        
        $fileFilter->addFilter($fileFilter::TYPE_EXPORT, $file->getTaskGuid(), $file->getId(), 'editor_Plugins_Okapi_Tikal_Filter');
    }
    
    /***
     * Find all files which can be handled by okapi and start a worker so they are converted by okapi
     *
     * @param string $importFolder - tmp import directory path on disk
     */
    protected function handleFiles($importFolder){
        $import = Zend_Registry::get('config')->runtimeOptions->import;
        
        //proofread folder
        $proofReadFolder=$importFolder.'/'.$import->proofReadDirectory;
        
        //reference files directory path
        $refFolder = $importFolder.'/'.$import->referenceDirectory;
        
        $directory = new RecursiveDirectoryIterator($proofReadFolder);
        $it= new RecursiveIteratorIterator($directory);
        
        $matchFiles=[];
        //find all files supported by okapi and move them in the okapi directory
        foreach ($it as $fileinfo) {
            if(!$this->isProcessable($fileinfo)) {
                continue;
            }
            //add the match file in the matches array
            $matchFiles[]=[
                    'fileName'=>$fileinfo->getFilename(),
                    'filePath'=>$fileinfo->getPathname(),
            ];
        }
        
        //if no files are found do nothing
        if(empty($matchFiles)){
            return;
        }
        
        $bconfFilePath = $this->getBconfFiles($importFolder);
        
        //for each match file, run a okapi worker
        foreach ($matchFiles as $file) {
            $this->queueWorker($file,$bconfFilePath,$refFolder,$proofReadFolder);
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
                'proofReadFolder'=>$proofReadFolder,
                'taskGuid'=>$this->task->getTaskGuid()
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
    
    protected function isOkapiGeneratedFile($filename){
        $retVal=substr($filename, -strlen(editor_Plugins_Okapi_Connector::OKAPI_FILE_EXTENSION));
        return $retVal=== editor_Plugins_Okapi_Connector::OKAPI_FILE_EXTENSION;
    }
}
