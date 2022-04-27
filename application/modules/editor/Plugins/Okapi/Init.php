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

/**
 */
class editor_Plugins_Okapi_Init extends ZfExtended_Plugin_Abstract {

    protected static $description = 'Provides Okapi pre-convertion and import of non bilingual data formats.';

    /**
     *
     * @var int
     */
    const BCONF_VERSION_INDEX = 0;
    
    
    private static array $bconfExtensions = ['bconf'];

    /**
     * @param Zend_EventManager_Event $event
     * @see ZfExtended_RestController::afterActionEvent
     */
    public static function addCustomersDefaultBconfIds(Zend_EventManager_Event $event)
    {
        /** @var ZfExtended_View $view */
        $view = $event->getParam('view');
        $meta = new editor_Models_Db_CustomerMeta();
        $metas = $meta->fetchAll('defaultBconfId IS NOT NULL')->toArray();
        $bconfIds = array_column($metas, 'defaultBconfId', 'customerId');
        foreach ($view->rows as &$customer) {
            $customer['defaultBconfId'] = (int)$bconfIds[$customer['id']] ?? null;
        }
    }

    /**
     * Retrieves the config-based path to the default export bconf
     * @param editor_Models_Task $task
     * @return string
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function getExportBconfPath(editor_Models_Task $task): string {
        $config = $task->getConfig();
        $defaultExportBconf = $config->runtimeOptions->plugins->Okapi->export->okapiBconfDefaultName ?? null;
        if(empty($defaultExportBconf)){
            throw new editor_Plugins_Okapi_Exception('E1340');
        }
        return self::getOkapiDataFilePath().$defaultExportBconf;
    }

    /**
     * Retrieves the database-based path to the default import bconf
     * TODO FIXME: add version check here
     * @param editor_Models_Task $task
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function getImportBconfPath(editor_Models_Task $task): string {
        $meta = $task->meta(true);
        $bconfId = $meta->getBconfId();
        $bconf = new editor_Plugins_Okapi_Models_Bconf();
        $bconf->load($bconfId);
        return $bconf->getFilePath();
    }

    /***
     * Return the okapi data directory file path with trailing slash.
     * @return string
     * @throws editor_Plugins_Okapi_Exception|editor_Models_ConfigException
     */
    public static function getOkapiDataFilePath(): string {
        return APPLICATION_PATH.'/modules/editor/Plugins/Okapi/data/';
    }
    /**
     * Finds bconf-files in the given directory and returns them as array for the Okapi Import.
     * This API is outdated and only used for the aligned XML/XSLT import in he visual
     * @param string $dir
     * @return string
     */
    public static function findImportBconfFileInDir(string $dir): ?string {
        $directory = new DirectoryIterator($dir);
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (in_array(strtolower($fileinfo->getExtension()), self::$bconfExtensions)) {
                return $fileinfo->getPathname();
            }
        }
        return NULL;
    }
    
    protected $localePath = 'locales';
    
    /**
     * Supported file-types by okapi
     *
     * @var array
     */
    private array $okapiFileTypes = array(
        'okapi', //currently needed, see TRANSLATE-1019

        'c',
        'catkeys',
        //'csv' => ['text/csv'], disabled due our own importer
        'cpp',
        'dita',
        'ditamap',
        'docm',
        'docx',
        'dotm',
        'dotx',
        'dtd',
        'h',
        'htm',
        'html',
        'idml',
        'json',
        'lang',
        'md',
        'mif',
        'odg',
        'odp',
        'ods',
        'odt',
        'otg',
        'otp',
        'ots',
        'ott',
        'pdf',
        'pentm',
        'php',
        'po',
        'potm',
        'potx',
        'ppsm',
        'ppsx',
        'pptm',
        'pptx',
        'properties',
        'rdf',
        'resx',
        'rkm',
        'rtf',
        'srt',
        'strings',
        'tbx',
        'tmx',
        'ts',
        'tsv',
        'ttx',
        'txml',
        'txp',
        'txt',
        'vrsz',
        'vsdm',
        'vsdx',
        'wcml',
        'wix',
        'xlsm',
        'xlsx',
        'xltm',
        'xltx',
        'xml',
        'yaml',
        'yml',
    );
    /**
     * Ignored filetypes if a custom bconf is provided (which skips the check for supported files)
     *
     * @var array
     */
    private array $okapiCustomBconfIgnoredFileTypes = array(
        'xsl',
        'xslt'
    );
    /**
     * Internal flag if custom bconf files were provided or not. In the latter case the internal default bconf is used.
     * @var bool
     */
    private bool $useCustomBconf = true;

    /**
     * @var editor_Models_Task
     */
    protected editor_Models_Task $task;
    
    /**
     * @var array
     */
    protected $frontendControllers = array(
        'pluginOkapiBconfPrefs' => 'Editor.plugins.Okapi.controller.BconfPrefs'

    );
    
    /**
     * @var editor_Models_Import_SupportedFileTypes
     */
    protected editor_Models_Import_SupportedFileTypes $fileTypes;
    
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
        $route = new Zend_Rest_Route($f, [], array(
            'editor' => ['plugins_okapi_bconf'],
        ));
        $r->addRoute('plugins_okapibconf_restdefault', $route);
        // New get route to export the bconf file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadbconf',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'downloadbconf'
            ));
        $r->addRoute('plugins_okapi_bconf_downloadbconf', $route);
        
        // New post route to upload a bconf file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadbconf',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'uploadbconf'
            ));
        $r->addRoute('plugins_okapi_bconf_uploadbconf', $route);
        // New post route to upload the SRX file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadsrx',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'uploadsrx'
            ));
        $r->addRoute('plugins_okapi_bconf_uploadsrx', $route);
        // New route to download the SRX file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadsrx',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'downloadsrx'
            ));
        $r->addRoute('plugins_okapi_bconf_downloadsrx', $route);
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/clone',
            array(
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'clone'
            ));
        $r->addRoute('plugins_okapi_bconf_clone', $route);
        
        // route for bconf filter
        $route = new Zend_Rest_Route($f, [], array(
            'editor' => ['plugins_okapi_bconffilter'],
        ));
        $r->addRoute('plugins_okapibconffilter_restdefault', $route);
    }
    
    public function getFrontendControllers(): array {
        return $this->getFrontendControllersFromAcl();
    }
    
    protected function initEvents() {
        //checks if import contains files for okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'beforeDirectoryParsing', [$this, 'handleBeforeDirectoryParsing']);
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'afterDirectoryParsing', [$this, 'handleAfterDirectoryParsing']);
        
        //invokes in the handleFile method of the relais filename match check.
        // Needed since relais files are bilingual (ending on .xlf) and the
        // imported files for Okapi are in the source format and do not end on .xlf.
        // Therefore the filenames do not match, this is corrected here.
        $this->eventManager->attach('editor_Models_RelaisFoldertree', 'customHandleFile', [$this, 'handleCustomHandleFileForRelais']);
        
        //Archives the temporary data folder again after converting the files with okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', [$this, 'handleAfterImport']);
        
        //allows the manipulation of the export fileparser configuration
        $this->eventManager->attach('editor_Models_Export', 'exportFileParserConfiguration', [$this, 'handleExportFileparserConfig']);
        
        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', [$this, 'handleApplicationState']);

        //attach to the config after index to check the confgi values
        $this->eventManager->attach('editor_ConfigController', 'afterIndexAction', [$this, 'handleAfterConfigIndexAction']);

        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', [$this, 'initJsTranslations']);

        $this->eventManager->attach('editor_TaskController', 'afterPostAction', [$this, 'addBconfIdToTaskMeta']);

        $this->eventManager->attach('Editor_CustomerController', 'afterIndexAction', [$this, 'addCustomersDefaultBconfIds']);
        $this->eventManager->attach('Editor_CustomerController', 'beforePutAction', [$this, 'handleCustomerMeta']);
    }

    /**
     * Hook on the before import event and check the import files
     *
     * @param Zend_EventManager_Event $event
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function handleBeforeDirectoryParsing(Zend_EventManager_Event $event) {
        /** @var editor_Models_Import_Configuration $config */
        $config = $event->getParam('importConfig');
        if(empty($this->task)) {
            $this->task = $event->getParam('task');
        }
        $bconfId = $this->task->meta()->getBconfId();
        $bconfName = (new editor_Plugins_Okapi_Models_Bconf())->load($bconfId)['name'];
        $this->useCustomBconf = $bconfName != editor_Plugins_Okapi_Models_Bconf::SYSTEM_BCONF_NAME;
        // TODO: use extension mapping from bconf
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
     * @param Zend_EventManager_Event $event
     * @see ZfExtended_RestController::afterActionEvent
     */
    public function addBconfIdToTaskMeta(Zend_EventManager_Event $event){

        /** @var editor_Models_Task $task */
        $task = $event->getParam('entity');
        $data = $event->getParam('data');
        $meta = $task->meta();
        if(empty($data['bconfId'])){
            $data['bconfId'] = (new editor_Plugins_Okapi_Models_Bconf)->getDefaultBconfId($data['customerId']);
        }
        $meta->setBconfId($data['bconfId']);
        $meta->save();
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
     * @throws Zend_Exception
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
     * @param editor_Models_Task $task
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
     * @throws editor_Models_Import_DataProvider_Exception
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
        catch(ZfExtended_Models_Entity_NotFoundException) {
            //no okapi worker -> do nothing
        }
        
    }

    /***
     * Find all available import bconf files in the okapy data directory
     * @return string[]
     */
    protected function findDefaultImportBconfFiles(): array {
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
    protected function isProcessable(SplFileInfo $fileinfo): bool {
        $extension = strtolower($fileinfo->getExtension());
        if(!$fileinfo->isFile()){
            return false;
        }
        
        $infoMsg = '';
        $parsers = $this->fileTypes->getParser($extension);
        // loop over all registered parsers by the given extension
        $fileObject = $fileinfo->openFile();
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
     * @return false|void
     */
    protected function queueWorker(int $fileId, SplFileInfo $file, array $params){
        $importFolder = $params['importFolder'];
        /** @var editor_Models_Task $task */
        $task = $params['task'];
        $workerParentId = $params['workerParentId'];
        $bconfFilePath = static::getImportBconfPath($task);

        $params = [
            'type' => editor_Plugins_Okapi_Worker::TYPE_IMPORT,
            'fileId' => $fileId,
            'file' => (string) $file,
            'importFolder' => $importFolder,
            'importConfig' => $params['importConfig'],
            'bconfFilePath' => $bconfFilePath,
        ];

        // init worker and queue it
        /** @var editor_Plugins_Okapi_Worker $worker */
        $worker = ZfExtended_Factory::get('editor_Plugins_Okapi_Worker');
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
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
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

    /**
     * @see ZfExtended_RestController::beforeActionEvent
     * @param Zend_EventManager_Event $event
     * @return void
     */
    public static function handleCustomerMeta(Zend_EventManager_Event $event) {
        $data = json_decode($event->getParam('params')['data'],true);
        @['id' =>$customerId, 'defaultBconfId' => $bconfId] = $data;

        if($bconfId){
            $customerMeta = new editor_Models_Customer_Meta();
            try {
                $customerMeta->loadByCustomerId($customerId);
            } catch(ZfExtended_Models_Entity_NotFoundException){
                $customerMeta->init(['customerId' => $customerId]); // new entity
            }
            $customerMeta->setDefaultBconfId($bconfId);
            $customerMeta->save(); // QUIRK: triggers SELECT after UPDATE
            if(count($data) === 2) exit;
        }
    }

}
