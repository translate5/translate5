<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

    protected static $description = 'Provides Okapi pre-convertion and import of non bilingual data formats.';
    
    /**
     * @var string
     */
    const BCONF_TARGET_IMPORT = 'import';
    /**
     *
     * @var string
     */
    const BCONF_TARGET_EXPORT = 'export';
    /**
     *
     * @var int
     */
    const BCONF_VERSION_INDEX = 0;
    
    
    private static $bconfExtensions = ['bconf'];
    
    /***
     * Return the default bconf import/export file-name from the task configuration
     *
     * @param editor_Models_Task $task
     * @param string $target : it can be import or export
     * @throws editor_Plugins_Okapi_Exception
     * @return string
     */
    public static function createDefaultBconfPath(editor_Models_Task $task,string $target) {
        $config = $task->getConfig();
        $okapiBconfDefaultName = $config->runtimeOptions->plugins->Okapi->$target->okapiBconfDefaultName ?? null;
        if(empty($okapiBconfDefaultName)){
            throw new editor_Plugins_Okapi_Exception('E1340');
        }
        return self::getOkapiDataFilePath().$okapiBconfDefaultName;
    }

    /***
     * Return the okapi data directory file path.
     * @return string
     */
    public static function getOkapiDataFilePath() {
        return APPLICATION_PATH.'/modules/editor/Plugins/Okapi/data/';
    }
    /**
     * Finds bconf-files in the given directory and returns them as array for the Okapi Import.At least the default import file is returned
     * @param string $dir
     * @return array
     */
    public static function findImportBconfFilesinDir(string $dir): array {
        $bconfPathes = array();
        $directory = new DirectoryIterator($dir);
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (in_array(strtolower($fileinfo->getExtension()), self::$bconfExtensions)) {
                $bconfPathes[] = $fileinfo->getPathname();
            }
        }
        return $bconfPathes;
    }
    
    protected $localePath = 'locales';
    
    /**
     * Supported file-types by okapi
     *
     * @var array
     */
    private $okapiFileTypes = array(
        'okapi', //currently needed, see TRANSLATE-1019
        
        'pdf',
        
        'html',
        'htm',
        'xml',
        //'csv' => ['text/csv'], disabled due our own importer
        'txt',
        'dita',
        'ditamap',
        'c',
        'h',
        'cpp',
        'dtd',
        'wcml',
        'idml',
        'strings',
        'properties',
        'json',
        'catkeys',
        'md',
        'xlsx',
        'xlsm',
        'xltx',
        'xltm',
        'pptx',
        'pptm',
        'potx',
        'potm',
        'ppsx',
        'ppsm',
        'docx',
        'docm',
        'dotx',
        'dotm',
        'vsdx',
        'vsdm',
        'mif',
        'ods',
        'ots',
        'odg',
        'otg',
        'odp',
        'otp',
        'odt',
        'ott',
        'pentm',
        'php',
        'po',
        'rkm',
        'rdf',
        'resx',
        'lang',
        'srt',
        'tsv',
        'tmx',
        'txp',
        'rtf',
        'tbx',
        'ts',
        'ttx',
        'txml',
        'vrsz',
        'wix',
        'yml',
        'yaml',
    );
    /**
     * Ignored filetypes if a custom bconf is provided (which skips the check for supported files)
     *
     * @var array
     */
    private $okapiCustomBconfIgnoredFileTypes = array(
        'xsl',
        'xslt'
    );
    /**
     * Internal flag if custom bconf files were provided or not. In the latter case the internal default bconf is used.
     * @var string
     */
    private $useCustomBconf = true;
    
    /**
     * Container for the found bconf files in the import package
     * @var array
     */
    private $bconfFilePaths = [];
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var array
     */
    protected $frontendControllers = array(
        'pluginOkapiBconfPrefs' => 'Editor.plugins.Okapi.controller.BconfPrefs'

    );
    
    /**
     * @var editor_Models_Import_SupportedFileTypes
     */
    protected $fileTypes;
    
    public function init() {
        $this->fileTypes = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
        /* @var $fileTypes editor_Models_Import_SupportedFileTypes */
        foreach ($this->okapiFileTypes as $ext) {
            $this->fileTypes->register($ext);
        }
        $this->initEvents();
        $this->addController('BconfController');
        $this->addController('BconfFilterController');
        $this->initRoutes();
    }
    
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        // route for bconf
        $restRoute = new Zend_Rest_Route($f, array(), array(
            'editor' => array('plugins_okapi_bconf'),
        ));
        $r->addRoute('plugins_okapibconf_restdefault', $restRoute);
        // New get route to export the bconf file.
        $exportbconf = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/exportbconf',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'exportBconf'
            ));
        $r->addRoute('plugins_okapi_bconf_exportbconf', $exportbconf);
        
        // New post route to import the bconf file.
        $importbconf = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/importbconf',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'importbconf'
            ));
        $r->addRoute('plugins_okapi_bconf_importbconf', $importbconf);
        // New post route to import the SRX file.
        $uploadSRX = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadSRX',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'uploadSRX'
            ));
        $r->addRoute('plugins_okapi_bconf_uploadsrx', $uploadSRX);
        // New route to download the SRX file.
        $downloadSRX = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadSRX',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'downloadSRX'
            ));
        $r->addRoute('plugins_okapi_bconf_downloadsrx', $downloadSRX);
        $cloneBconf = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/clone',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'clone'
            ));
        $r->addRoute('plugins_okapi_bconf_clone', $cloneBconf);
        
        // route for bconf filter
        $restRoute = new Zend_Rest_Route($f, array(), array(
            'editor' => array('plugins_okapi_bconffilter'),
        ));
        $r->addRoute('plugins_okapibconffilter_restdefault', $restRoute);
    }
    
    public function getFrontendControllers(){
        return $this->getFrontendControllersFromAcl();
    }
    
    protected function initEvents() {
        //checks if import contains files for okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'beforeDirectoryParsing', array($this, 'handleBeforeDirectoryParsing'));
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'afterDirectoryParsing', array($this, 'handleAfterDirectoryParsing'));
        
        //invokes in the handleFile method of the relais filename match check.
        // Needed since relais files are bilingual (ending on .xlf) and the
        // imported files for Okapi are in the source format and do not end on .xlf.
        // Therefore the filenames do not match, this is corrected here.
        $this->eventManager->attach('editor_Models_RelaisFoldertree', 'customHandleFile', array($this, 'handleCustomHandleFileForRelais'));
        
        //Archives the temporary data folder again after converting the files with okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', array($this, 'handleAfterImport'));
        
        //allows the manipulation of the export fileparser configuration
        $this->eventManager->attach('editor_Models_Export', 'exportFileParserConfiguration', [$this, 'handleExportFileparserConfig']);
        
        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'handleApplicationState'));

        //attach to the config after index to check the confgi values
        $this->eventManager->attach('editor_ConfigController', 'afterIndexAction', [$this, 'handleAfterConfigIndexAction']);

        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));

        $this->eventManager->attach('editor_Plugins_Okapi_BconfController', 'beforeSetDataInEntity', ['editor_Plugins_Okapi_Controllers_BconfController', 'handleCustomerDefaultBconf']);
    }
    
    /**
     * Hook on the before import event and check the import files
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeDirectoryParsing(Zend_EventManager_Event $event) {
        $config = $event->getParam('importConfig');
        /* @var $config editor_Models_Import_Configuration */
        if(empty($this->task)) {
            $this->task = $event->getParam('task');
        }
        /* @var $task editor_Models_Task */
        $this->findBconfFiles($event->getParam('importFolder'));
        if($this->useCustomBconf){
            $config->checkFileType = false;
            $config->ignoredUncheckedExtensions = implode(',', $this->okapiCustomBconfIgnoredFileTypes);
        } else {
            $config->checkFileType = true;
        }
    }

    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * Hook on the before import event and check the import files
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterDirectoryParsing(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $filelist = $params['filelist'];
        $importFolder = $params['importFolder'];
        
        $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
        /* @var $fileFilter editor_Models_File_FilterManager */
        
        foreach($filelist as $fileId => $filePath) {
            $fileInfo = new SplFileInfo($importFolder.'/'.ZfExtended_Utils::filesystemEncode($filePath));
            
            //if there is a filefilter or a fileparser (isProcessable) we do not process the file with Okapi
            if($fileFilter->hasFilter($fileId, $fileFilter::TYPE_IMPORT) || !$this->isProcessable($fileInfo)) {
                continue;
            }
            $this->queueWorker($fileId, $fileInfo, $params);
        }
    }
    
    /**
     * Needed since relais files are bilingual (ending on .xlf) and the
     * imported files for Okapi are in the source format (example docx) and do not end on .xlf.
     * Therefore the filenames do not match to the relais files, this is corrected here.
     * @param Zend_EventManager_Event $event
     */
    public function handleCustomHandleFileForRelais(Zend_EventManager_Event $event) {
        $suffix = '.xlf'; //TODO should come from the worker. there the suffix is determined from the okapi output
        $child = $event->getParam('fileChild'); //children stdClass, containing several information about the file to be parsed
        $fullpath = $event->getParam('fullPath'); //absolute path to the relais file to be parsed
        
        $importConfig = $event->getParam('importConfig');//INFO:(TRANSLATE-1596) this is the workfiles directory (for now this can be proofRead or workfiles). Afte we remove the depricate support for proofRead this can be removed
        /* @var $importConfig editor_Models_Import_Configuration */
        if(empty($this->task)) {
            $this->task = ZfExtended_Factory::get('editor_Models_Task');
            $this->task->loadByTaskGuid($event->getParam('taskGuid'));
        }
        if($child->relaisFileStatus == editor_Models_RelaisFoldertree::RELAIS_NOT_FOUND) {
            $config = Zend_Registry::get('config');
            $workfiles = '/'.trim($importConfig->getFilesDirectory(),'/').'/';
            $relaisDirectory = '/'.trim($config->runtimeOptions->import->relaisDirectory,'/').'/';
            $fullpath = $fullpath.$suffix;
            $bilingualSourceFile = str_replace($relaisDirectory, $workfiles, $fullpath);
            
            //check for manifest file, to ensure that the file was processed via Okapi:
            if(file_exists($fullpath) && file_exists($bilingualSourceFile) && $this->wasImportedWithOkapi($this->task, $child->id)) {
                $child->filename .= $suffix;
                $child->relaisFileStatus = editor_Models_RelaisFoldertree::RELAIS_NOT_IMPORTED;
            }
        }
    }
    
    /**
     * Checks if the file was imported via Okapi (via existence of the manifest file).
     * This gives no information if the import via Okapi was successful!
     *
     * @param editor_Models_Import $task
     * @param int $fileId
     * @return bool
     */
    protected function wasImportedWithOkapi(editor_Models_Task $task, int $fileId): bool {
        $path = $task->getAbsoluteTaskDataPath().'/'.editor_Plugins_Okapi_Worker::OKAPI_REL_DATA_DIR.'/';
        $okapiManifestFile = new SplFileInfo($path.sprintf(editor_Plugins_Okapi_Worker::MANIFEST_FILE, $fileId));
        return $okapiManifestFile->isReadable();
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
            $directoryProvider->checkAndPrepare($task);
            $directoryProvider->archiveImportedData('OkapiArchive.zip');
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //no okapi worker -> do nothing
        }
        
    }
    
    /**
     * looks for bconf files in the import root folder and returns them
     * @param string $importFolder
     */
    protected function findBconfFiles(string $importFolder) {
        $this->bconfFilePaths = self::findImportBconfFilesinDir($importFolder);
        $this->useCustomBconf = true;
        if(count($this->bconfFilePaths) == 0){
            $this->useCustomBconf = false;
            //use the task-import default file when there is no custom bconf in use
            $this->bconfFilePaths[] = self::createDefaultBconfPath($this->task,self::BCONF_TARGET_IMPORT);
        }
    }
    
    /***
     * Find all available import bconf files in the okapy data directory
     * @return string[]
     */
    protected function findDefaultImportBconfFiles(){
        $filenames = [];
        $directory = new DirectoryIterator(self::getOkapiDataFilePath());
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (in_array(strtolower($fileinfo->getExtension()), self::$bconfExtensions)) {
                $filenames[] = $fileinfo->getFilename();
            }
        }
        return $filenames;
    }
    
    /**
     * Checks if the given file should be processed by okapi
     * @param SplFileInfo $fileinfo
     * @return boolean
     */
    protected function isProcessable(SplFileInfo $fileinfo) {
        $extension = strtolower($fileinfo->getExtension());
        if(!$fileinfo->isFile()){
            return false;
        }
        
        $infoMsg = '';
        $parsers = $this->fileTypes->getParser($extension);
        // loop over all registered parsers by the given extension
        $fileObject = $fileinfo->openFile('r', false);
        $fileHead = $fileObject->fread(512);
        foreach($parsers as $parser) {
            if($parser::isParsable($fileHead, $infoMsg)) {
                // if one of the registered parsers may parse the file, then we don't need Okapi
                return false;
            }
        }
        
        //if there is a custom bconf, this bconf can contain "new" allowed file types.
        // Since we currently can not get the information from the bconf which additional types are allowed,
        // we just pass all filetypes (expect the ones with a native fileparser) to be parsed via Okapi
        // If the user uploads invalid files, they are ignored and listed in the event log
        if($this->useCustomBconf) {
            if(in_array($extension, $this->okapiCustomBconfIgnoredFileTypes)){
                return false;
            }
            if(!$this->fileTypes->hasParser($extension)){
                return true;
            }
        }
        
        return in_array($extension, $this->okapiFileTypes);
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
        $importFolder = $params['importFolder'];
        $task = $params['task'];
        $workerParentId = $params['workerParentId'];
        
        $worker = ZfExtended_Factory::get('editor_Plugins_Okapi_Worker');
        /* @var $worker editor_Plugins_Okapi_Worker */
        
        $params=[
            'type' => editor_Plugins_Okapi_Worker::TYPE_IMPORT,
            'fileId' => $fileId,
            'file' => (string) $file,
            'importFolder' => $importFolder,
            'bconfFilePath' => $this->bconfFilePaths,
            'importConfig' => $params['importConfig']
        ];
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), $params)) {
            return false;
        }
        $worker->queue($workerParentId);
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
    
    /**
     * Sets additional file parser configurations if file was imported with Okapi
     * @param Zend_EventManager_Event $event
     */
    public function handleExportFileparserConfig(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        $file = $event->getParam('file');
        /* @var $file editor_Models_File */
        $config = $event->getParam('config');
        /* @var $config stdClass */
        
        if($this->wasImportedWithOkapi($task, $file->getId())) {
            //files imported with okapi have always source to empty target on (TRANSLATE-2384)
            $config->options['sourcetoemptytarget'] = true;
        }
    }
    
    /***
     * After config index action event handler. This will check if runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName
     *  are up to date with the files on the disk
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterConfigIndexAction(Zend_EventManager_Event $event) {
        $rows = $event->getParam('view')->rows ?? [];
        if(empty($rows)){
            return;
        }
        
        $toUpdate = [];
        //find the default import/export configs
        $toUpdate[] = array_search('runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName', array_column($rows, 'name'));
        $toUpdate[] = array_search('runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName', array_column($rows, 'name'));

        $files = null;
        //for each config, update the defaults in the database and also in the rows array
        foreach ($toUpdate as $index){
            if($index === false){
                continue;
            }
            $config = $rows[$index];
            
            if(empty($files)){
                //find all import bconf files
                $files = $this->findDefaultImportBconfFiles();
                $files = implode(',',$files);
            }
            //the config has the same files as defaults
            if($config['defaults'] == $files){
                continue;
            }
            
            $model = ZfExtended_Factory::get('editor_Models_Config');
            /* @var $model editor_Models_Config */
            $model->loadByName($config['name']);
            $model->setDefaults($files);
            $model->save();
            
            //opdate the view rows
            $event->getParam('view')->rows[$index]['defaults'] = $files;
        }
    }
}
