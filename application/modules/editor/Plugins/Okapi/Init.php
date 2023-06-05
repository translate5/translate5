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

use MittagQI\Translate5\Plugins\Okapi\ImportFilter;
use MittagQI\Translate5\Plugins\Okapi\Service;

/**
 * OKAPI file converter and segmenter plugin
 *
 * There are several debug options for this Plugin:
 * runtimeOptions.debug.plugin.OkapiBconfPackUnpack => Turns general debugging on for the packing/unpacking of bconfs
 * runtimeOptions.debug.plugin.OkapiBconfProcessing => Turns debugging on for the processing of bconfs
 * runtimeOptions.debug.plugin.OkapiBconfValidation => Turns debugging on for validating bconfs, filters & srx
 * runtimeOptions.debug.plugin.OkapiExtensionMapping => Turns debugging on for the processing of the extension-mapping
 * runtimeOptions.debug.plugin.OkapiKeepIntermediateFiles => All the files that are created in the various processing steps are kept
 *
 * for documentation of the BCONF management, see editor_Plugins_Okapi_Bconf_Entity
 */
class editor_Plugins_Okapi_Init extends ZfExtended_Plugin_Abstract {

    /**
     * The current internal version index of the bconf's
     * This must be increased each time, a git-based fprm or srx is changed
     * @var int
     */
    const BCONF_VERSION_INDEX = 4;

    /**
     * The filename of the system default import bconf
     * @var string
     */
    const BCONF_SYSDEFAULT_IMPORT = 'okapi_default_import.bconf';

    /**
     * The GUI-name of the system default import bconf
     * @var string
     */
    const BCONF_SYSDEFAULT_IMPORT_NAME = 'Translate5-Standard';

    /**
     * @var string
     */
    const BCONF_SYSDEFAULT_EXPORT = 'okapi_default_export.bconf';

    const SUPPORTED_OKAPI_VERSION = [
        'okapi-longhorn-037',
        'okapi-longhorn-139',
        'okapi-longhorn-141',
        'okapi-longhorn-143',
        'okapi-longhorn-144-snapshot',
    ];

    /**
     * Retrieves the config-based path to the default export bconf
     * @param editor_Models_Task $task
     * @return string
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function getExportBconfPath(editor_Models_Task $task): string
    {
        $config = $task->getConfig();
        $defaultExportBconf = $config->runtimeOptions->plugins->Okapi->export->okapiBconfDefaultName ?? null;
        if(empty($defaultExportBconf)){
            throw new editor_Plugins_Okapi_Exception('E1340');
        }
        return self::getDataDir().$defaultExportBconf;
    }

    /**
     * Retrieves the bconf to use for a task
     * This requiers the task to be saved and thus having valid meta entries!
     * @param editor_Models_Task $task
     * @return editor_Plugins_Okapi_Bconf_Entity
     * @throws Zend_Exception
     */
    public static function getImportBconf(editor_Models_Task $task): editor_Plugins_Okapi_Bconf_Entity
    {
        $meta = $task->meta(true);
        // If it's a termtranslation task - use system default bconf
        if($task->getTaskType() == editor_Task_Type_TermTranslationTask::ID){
            return self::getSystemDefaultBconf();
        }
        return self::getImportBconfById($task, $meta->getBconfId());
    }

    /**
     * Retrieves the system default bconf
     * @return editor_Plugins_Okapi_Bconf_Entity
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public static function getSystemDefaultBconf(): editor_Plugins_Okapi_Bconf_Entity
    {
        if(static::$cachedSysBconf == null){
            static::$cachedSysBconf = editor_Plugins_Okapi_Bconf_Entity::getSystemDefaultBconf();
        }
        return static::$cachedSysBconf;
    }

    /**
     * Fetches the import BCONF to use by id
     * @param editor_Models_Task $task
     * @param int|null $bconfId
     * @param bool $addWarning
     * @param string|null $orderer
     * @return editor_Plugins_Okapi_Bconf_Entity
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    private static function getImportBconfById(editor_Models_Task $task, int $bconfId = null, bool $addWarning = false, string $orderer = null): editor_Plugins_Okapi_Bconf_Entity
    {
        // this may be called multiple times when processing the import upload, so we better cache it
        if(!empty($bconfId) && static::$cachedBconf != NULL && static::$cachedBconf->getId() === $bconfId){
            return static::$cachedBconf;
        }
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        // empty covers "not set" and also invalid id '0'
        // somehow dirty: unit tests pass a virtual" bconf-id of "0" to signal to use the system default bconf
        if(empty($bconfId)){
            // a bconfId may not be given when the API is used via an unittest or the termportal
            // otherwise this is very unlikely if not impossible: no bconf-id set for the task. In that case we use the default one and add a warning
            if($addWarning && $orderer != 'unittest' && $orderer != 'termportal'){
                $task->logger('editor.task.okapi')->warn('E1055', 'Okapi Plug-In: Bconf not given or not found: {bconfFile}', ['bconfFile' => 'No bconf-id was set for task meta']);
            }
            $bconfId = $bconf->getDefaultBconfId($task->getCustomerId());
        }
        $bconf->load($bconfId);
        // we update outdated bconfs when accessing them
        $bconf->repackIfOutdated();
        static::$cachedBconf = $bconf;
        return $bconf;
    }

    /***
     * Return the okapi data directory file path with trailing slash.
     * @return string
     */
    public static function getDataDir(): string {
        return APPLICATION_PATH.'/modules/editor/Plugins/Okapi/data/';
    }
    /**
     * Finds bconf-files in the given directory and returns them as array for the Okapi Import.
     * This API is outdated and only used for the aligned XML/XSLT import in the visual
     * @param string $dir
     * @return string|null
     */
    public static function findImportBconfFileInDir(string $dir): ?string {
        $directory = new DirectoryIterator($dir);
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (strtolower($fileinfo->getExtension()) === editor_Plugins_Okapi_Bconf_Entity::EXTENSION) {
                return $fileinfo->getPathname();
            }
        }
        return null;
    }

    protected static string $description = 'Provides Okapi pre-convertion and import of non bilingual data formats.';

    protected static bool $activateForTests = true;

    /**
     * The services we use
     * @var string[]
     */
    protected static array $services = [
        'okapi' => Service::class
    ];

    private static ?editor_Plugins_Okapi_Bconf_Entity $cachedBconf = null;

    private static ?editor_Plugins_Okapi_Bconf_Entity $cachedSysBconf = null;

    protected $localePath = 'locales';

    protected $frontendControllers = array(
        'pluginOkapiBconfPrefs' => 'Editor.plugins.Okapi.controller.BconfPrefs'
    );

    /**
     * Holdes the file-types during import
     * @var editor_Models_Import_SupportedFileTypes
     */
    private editor_Models_Import_SupportedFileTypes $fileTypes;

    /**
     * Holdes the file-filter during import
     * @var ImportFilter
     */
    private ImportFilter $importFilter;

    #region Plugin Init
    public function init() {
        $this->initEvents();
        $this->addController('BconfController');
        $this->addController('BconfFilterController');
        $this->addController('BconfDefaultFilterController');
        $this->initRoutes();
    }

    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();

        // routes for bconfs
        $route = new Zend_Rest_Route($f, [], [
            'editor' => ['plugins_okapi_bconf'],
        ]);
        $r->addRoute('plugins_okapi_bconf_restdefault', $route);

        // route to export the bconf file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadbconf',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action'     => 'downloadbconf'
            ]);
        $r->addRoute('plugins_okapi_bconf_downloadbconf', $route);
        // post route to upload a bconf file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadbconf',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action'     => 'uploadbconf'
            ]);
        $r->addRoute('plugins_okapi_bconf_uploadbconf', $route);
        // post route to upload the SRX file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadsrx',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action'     => 'uploadsrx'
            ]);
        $r->addRoute('plugins_okapi_bconf_uploadsrx', $route);
        // route to download the SRX file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadsrx',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action'     => 'downloadsrx'
            ]);
        $r->addRoute('plugins_okapi_bconf_downloadsrx', $route);
        // clone bconf
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/clone',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action'     => 'clone'
            ]);
        $r->addRoute('plugins_okapi_bconf_clone', $route);
        // route to set the non-customer default
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/setdefault',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action'     => 'setdefault'
            ]);
        $r->addRoute('plugins_okapi_bconf_setdefault', $route);

        // routes for bconf filters
        $route = new Zend_Rest_Route($f, [], [
            'editor' => ['plugins_okapi_bconffilter'],
        ]);
        $r->addRoute('plugins_okapi_bconffilter_restdefault', $route);

        // get fprm settings file content route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconffilter/getfprm',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconffilter',
                'action'     => 'getfprm'
            ]
        );
        $r->addRoute('plugins_okapi_bconffilter_getfprm', $route);

        // save fprm settings file content route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconffilter/savefprm',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconffilter',
                'action'     => 'savefprm'
            ]
        );
        $r->addRoute('plugins_okapi_bconffilter_savefprm', $route);


        // routes for default bconf filters
        $route = new Zend_Rest_Route($f, [], [
            'editor' => ['plugins_okapi_bconfdefaultfilter'],
        ]);
        $r->addRoute('plugins_okapi_bconfdefaultfilter_restdefault', $route);

        // default filters list route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconfdefaultfilter/getall',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconfdefaultfilter',
                'action'     => 'getall'
            ]
        );
        $r->addRoute('plugins_okapi_bconfdefaultfilter_getall', $route);

        // save extensions for a non-custom filter route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconfdefaultfilter/setextensions',
            [
                'module'     => 'editor',
                'controller' => 'plugins_okapi_bconfdefaultfilter',
                'action'     => 'setextensions'
            ]
        );
        $r->addRoute('plugins_okapi_bconfdefaultfilter_setextensions', $route);
    }

    public function getFrontendControllers(): array {
        return $this->getFrontendControllersFromAcl();
    }

    protected function initEvents() {

        // plugin basics
        $this->eventManager->attach('Editor_IndexController', 'beforeIndexAction', [$this, 'handleBeforeIndex']);
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', [$this, 'handleAfterIndex']);
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', [$this, 'handleJsTranslations']);

        // adds the used bconf to the import-archive. At this point, the task is not yet saved and the bconfId sent by request has to be used
        $this->eventManager->attach('editor_Models_Import', 'afterUploadPreparation', [$this, 'handleAfterUploadPreparation']);

        // sets the correct supported file-types
        $this->eventManager->attach('editor_TaskController', 'beforeValidateUploads', [$this, 'handleBeforeValidateUploads']);
        // adds the bconfId to the task-meta
        $this->eventManager->attach('editor_TaskController', 'beforeProcessUploadedFile', [$this, 'handleBeforeProcessUploadedFile']);
        
        //checks if import contains files for okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'beforeDirectoryParsing', [$this, 'handleBeforeDirectoryParsing']);
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'afterDirectoryParsing', [$this, 'handleAfterDirectoryParsing']);

        //invokes in the handleFile method of the relais filename match check.
        // Needed since relais files are bilingual (ending on .xlf) and the
        // imported files for Okapi are in the source format and do not end on .xlf.
        // Therefore the filenames do not match, this is corrected here.
        $this->eventManager->attach('editor_Models_RelaisFoldertree', 'customHandleFile', [$this, 'handleCustomFileForRelais']);

        //Archives the temporary data folder again after converting the files with okapi:
        $this->eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', [$this, 'handleAfterImport']);

        //allows the manipulation of the export fileparser configuration
        $this->eventManager->attach('editor_Models_Export', 'exportFileParserConfiguration', [$this, 'handleExportFileparserConfig']);

        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', [$this, 'handleApplicationState']);

        //attach to the config after index to check the config values
        $this->eventManager->attach('editor_ConfigController', 'afterIndexAction', [$this, 'handleAfterConfigIndexAction']);
        $this->eventManager->attach('Editor_CustomerController', 'afterIndexAction', [$this, 'handleCustomerAfterIndex']);
    }

    /**
     * @param Zend_EventManager_Event $event
     * @return void
     * @throws ZfExtended_Exception
     * @throws editor_Plugins_Okapi_Exception
     */
    public function handleBeforeIndex(Zend_EventManager_Event $event) {
        // This sets the supported file-extension for the IndexController, which are used to filter the supported file-types for the import wizard in the frontend
        // TODO FIXME: This should be set dynamically according the selected bconf!
        foreach(self::getSystemDefaultBconf()->getSupportedExtensions() as $extension){
            $this->getFileTypes()->register($extension);
        }
    }

    /**
     * Adds the system default bconf-id to the global JS scope
     * This is needed in the bconf-management
     * @param Zend_EventManager_Event $event
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function handleAfterIndex(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /** @var $view ZfExtended_View */
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        $view->Php2JsVars()->set('plugins.Okapi.systemDefaultBconfId', $bconf->getDefaultBconfId());
        $view->Php2JsVars()->set('plugins.Okapi.systemStandardBconfName', self::BCONF_SYSDEFAULT_IMPORT_NAME);
    }

    /**
     * Adds our plugin specific translations
     * @param Zend_EventManager_Event $event
     */
    public function handleJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }

    #endregion

    /**
     * Sets the supported extensions according the sent bconf-ID, before an import or before an clone
     * Called in the same request as handleBeforeValidateUploads, handleBeforeProcessUploadedFile, handleAfterUploadPreparation
     *
     * @param Zend_EventManager_Event $event
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function handleBeforeValidateUploads(Zend_EventManager_Event $event)
    {
        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        /* @var $requestData array */
        $requestData = $event->getParam('data');
        $bconfId = array_key_exists('bconfId', $requestData) ? intval($requestData['bconfId']) : null;
        $fileTypes = $this->getFileTypes();
        foreach(self::getImportBconfById($task, $bconfId)->getSupportedExtensions() as $extension){
            $fileTypes->register($extension);
        }
    }

    /**
     * Hook that adds the bconfId sent by the Import wizard to the task-meta
     * Called in the same request as handleBeforeValidateUploads, handleBeforeProcessUploadedFile, handleAfterUploadPreparation
     *
     * @param Zend_EventManager_Event $event
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function handleBeforeProcessUploadedFile(Zend_EventManager_Event $event){
        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        /* @var $meta editor_Models_Task_Meta */
        $meta = $event->getParam('meta');
        /* @var $requestData array */
        $requestData = $event->getParam('data');
        $bconfId = array_key_exists('bconfId', $requestData) ? $requestData['bconfId'] : NULL;
        // empty makes sense here since we anly accept an bconf-id > 0
        if(empty($bconfId)){
            $bconf = new editor_Plugins_Okapi_Bconf_Entity();
            $bconfId = $bconf->getDefaultBconfId($task->getCustomerId());
        }
        $meta->setBconfId($bconfId);
    }

    /**
     * Hook that adds the used bconf to the ImportArchive as a long-term reference which bconf was used
     * Called in the same request as handleBeforeValidateUploads, handleBeforeProcessUploadedFile, handleAfterUploadPreparation
     *
     * @param Zend_EventManager_Event $event
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function handleAfterUploadPreparation(Zend_EventManager_Event $event) {

        /* @var $dataProvider editor_Models_Import_DataProvider_Abstract */
        $dataProvider = $event->getParam('dataProvider');
        // UGLY: this replicates the logic in ::handleBeforeDirectoryParsing. But it's too late to add sth. to the archive there
        $bconfInZip = self::findImportBconfFileinDir($dataProvider->getAbsImportPath());
        // if a bconf is provided in the import zip it will be part of the archive anyway
        if($bconfInZip == NULL){
            /* @var $task editor_Models_Task */
            $task = $event->getParam('task');
            /* @var $requestData array */
            $requestData = $event->getParam('requestData');
            $bconfId = array_key_exists('bconfId', $requestData) ? intval($requestData['bconfId']) : NULL;
            $orderer = array_key_exists('orderer', $requestData) ? $requestData['orderer'] : NULL;
            $bconf = self::getImportBconfById($task, $bconfId, true, $orderer);
            // we add the bconf with it's visual name as filename to the archive for easier maintainability
            $dataProvider->addAdditonalFileToArchive($bconf->getPath(), $bconf->getDownloadFilename());
        }
    }

    /**
     * Hook on the before import event and check the import files
     * Called in the same request as handleAfterDirectoryParsing, handleAfterDirectoryParsing
     *
     * @param Zend_EventManager_Event $event
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function handleBeforeDirectoryParsing(Zend_EventManager_Event $event) {

        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        // DEPRECATED compatibility code: enabling import BCONF to be supplied via import folder
        $bconfInZip = self::findImportBconfFileinDir($event->getParam('importFolder'));
        if($bconfInZip != null){
            // DEPRECATED import of BCONF via ZIP: create a warning about using the deprecated API
            $task->logger('editor.task.okapi')->warn('E1387', 'Okapi Plug-In: Providing the BCONF to use in the import ZIP is deprecated', [
                'bconf' => basename($bconfInZip),
            ]);
            $this->importFilter = new ImportFilter(null, $bconfInZip);

        } else {
            // the normal behaviour: bconf is defined via task and set in import wizard
            $bconfId = $task->meta()->getBconfId();
            $bconf = new editor_Plugins_Okapi_Bconf_Entity();
            $bconf->load($bconfId);
            $this->importFilter = new ImportFilter($bconf, null);
        }
        // register only the import file-types defined by Okapi ... the ones set in init() must be reset therefore
        $fileTypes = $this->getFileTypes();
        foreach($this->importFilter->getSupportedExtensions() as $extension){
            $fileTypes->register($extension);
        }
    }

    /**
     * Hook on the before import event and check the import files
     * Called in the same request as handleAfterDirectoryParsing, handleAfterDirectoryParsing
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterDirectoryParsing(Zend_EventManager_Event $event) {

        $params = $event->getParams();
        /* @var $filelist string[] */
        $filelist = $params['filelist'];
        /* @var $importFolder string */
        $importFolder = $params['importFolder'];

        $fileFilter = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
        foreach($filelist as $fileId => $filePath) {
            $fileInfo = new SplFileInfo($importFolder.'/'.ZfExtended_Utils::filesystemEncode($filePath));
            //if there is a filefilter or a fileparser we do not process the file with Okapi
            if($fileFilter->hasFilter($fileId, $fileFilter::TYPE_IMPORT) || !$this->isProcessableFile($fileInfo)) {
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
    public function handleCustomFileForRelais(Zend_EventManager_Event $event) {

        $suffix = '.xlf'; //TODO should come from the worker. there the suffix is determined from the okapi output
        $child = $event->getParam('fileChild'); //children stdClass, containing several information about the file to be parsed
        $fullpath = $event->getParam('fullPath'); //absolute path to the relais file to be parsed
        /* @var $importConfig editor_Models_Import_Configuration */
        $importConfig = $event->getParam('importConfig');//INFO:(TRANSLATE-1596) this is the workfiles directory (for now this can be proofRead or workfiles). Afte we remove the depricate support for proofRead this can be removed
        $task = editor_ModelInstances::taskByGuid($event->getParam('taskGuid'));

        if($child->relaisFileStatus == editor_Models_RelaisFoldertree::RELAIS_NOT_FOUND) {
            $config = Zend_Registry::get('config');
            $workfiles = '/'.trim($importConfig->getFilesDirectory(),'/').'/';
            $relaisDirectory = '/'.trim($config->runtimeOptions->import->relaisDirectory,'/').'/';
            $fullpath = $fullpath.$suffix;
            $bilingualSourceFile = str_replace($relaisDirectory, $workfiles, $fullpath);

            //check for manifest file, to ensure that the file was processed via Okapi:
            if(file_exists($fullpath) && file_exists($bilingualSourceFile) && $this->wasImportedWithOkapi($task, $child->id)) {
                $child->filename .= $suffix;
                $child->relaisFileStatus = editor_Models_RelaisFoldertree::RELAIS_NOT_IMPORTED;
            }
        }
    }

    /**
     * Archives the temporary data folder again after converting the files with okapi
     * @param Zend_EventManager_Event $event
     * @throws editor_Models_Import_DataProvider_Exception
     */
    public function handleAfterImport(Zend_EventManager_Event $event) {

        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        /* @var $config editor_Models_Import_Configuration */
        $config = $event->getParam('importConfig');

        try {
            $worker = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);
            $worker->loadFirstOf(editor_Plugins_Okapi_Worker::class, $task->getTaskGuid());

            //proceed with the archive only, if a okapi worker was found for the current task
            $directoryProvider = ZfExtended_Factory::get(editor_Models_Import_DataProvider_Directory::class, [$config->importFolder]);
            $directoryProvider->checkAndPrepare($task);
            $directoryProvider->archiveImportedData('OkapiArchive.zip');
        } catch(ZfExtended_Models_Entity_NotFoundException) {
            //no okapi worker -> do nothing
        }
    }

    /**
     * Checks if the configured okapi instance is reachable
     * @param Zend_EventManager_Event $event
     */
    public function handleApplicationState(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->okapi = new stdClass();
        $connector = ZfExtended_Factory::get(editor_Plugins_Okapi_Connector::class);
        // the default current configured serverUsed will be checked
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
        $context = $event->getParam('context');

        if ($this->wasImportedWithOkapi($task, $file->getId()) && $context !== editor_Models_Export::EXPORT_PACKAGE) {
            //files imported with okapi have always source to empty target on (TRANSLATE-2384)
            $config->options['sourcetoemptytarget'] = true;
        }
    }

    /***
     * After config index action event handler.
     * This will check if runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName are up to date with the files on the disk
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
        //find the default export configs
        $index = array_search('runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName', array_column($rows, 'name'));
        // update the defaults in the database if existing & neccessary
        if($index !== false){

            $defaultBconfs = implode(',', $this->findDefaultBconfFiles());
            $config = $rows[$index];
            //the config has the same files as defaults
            if($config['defaults'] != $defaultBconfs){
                $model = ZfExtended_Factory::get(editor_Models_Config::class);
                $model->loadByName($config['name']);
                $model->setDefaults($defaultBconfs);
                $model->save();
            }
            // update the view row
            $event->getParam('view')->rows[$index]['defaults'] = $defaultBconfs;
        }
    }

    /**
     * @param Zend_EventManager_Event $event
     * @see ZfExtended_RestController::afterActionEvent
     */
    public function handleCustomerAfterIndex(Zend_EventManager_Event $event) {
        /** @var ZfExtended_View $view */
        $view = $event->getParam('view');
        $meta = new editor_Models_Db_CustomerMeta();
        $metas = $meta->fetchAll('defaultBconfId IS NOT NULL')->toArray();
        $bconfIds = array_column($metas, 'defaultBconfId', 'customerId');
        foreach ($view->rows as &$customer) {
            if(array_key_exists($customer['id'], $bconfIds)){
                $customer['defaultBconfId'] = (int) $bconfIds[$customer['id']];
            } else {
                $customer['defaultBconfId'] = null;
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

    /***
     * Find all available import bconf files in the okapi data directory
     * @return string[]
     */
    protected function findDefaultBconfFiles(): array {
        $filenames = [];
        $directory = new DirectoryIterator(self::getDataDir());
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (strtolower($fileinfo->getExtension()) === editor_Plugins_Okapi_Bconf_Entity::EXTENSION) {
                $filenames[] = $fileinfo->getFilename();
            }
        }
        return $filenames;
    }

    /**
     * Run for each file a separate worker, the worker will upload the file to the okapi, convert the file, and download the result
     * Called when importFilter is set
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

        $params = [
            'type' => editor_Plugins_Okapi_Worker::TYPE_IMPORT,
            'fileId' => $fileId,
            'file' => (string) $file,
            'importFolder' => $importFolder,
            'importConfig' => $params['importConfig'],
            'bconfFilePath' => $this->importFilter->getBconfPath(),
            'bconfName' => $this->importFilter->getBconfDisplayName()
        ];

        // init worker and queue it
        $worker = ZfExtended_Factory::get(editor_Plugins_Okapi_Worker::class);
        if (!$worker->init($task->getTaskGuid(), $params)) {
            return false;
        }
        $worker->queue($workerParentId);
    }

    /**
     * Checks if the given file should be processed by okapi
     * Called when importFilter is set
     * @param SplFileInfo $fileinfo
     * @return boolean
     */
    private function isProcessableFile(SplFileInfo $fileinfo): bool
    {

        $extension = strtolower($fileinfo->getExtension());
        if(!$fileinfo->isFile()){
            return false;
        }
        $parser = $this->getFileTypes()->hasSupportedParser($extension, $fileinfo);
        if (!is_null($parser)) {
            // if one of the registered parsers may parse the file, then we don't need Okapi
            return false;
        }
        // is the extension is supported by the used bconf ?
        return $this->importFilter->isExtensionSupported($extension);
    }

    private function getFileTypes(): editor_Models_Import_SupportedFileTypes
    {
        if(!isset($this->fileTypes)){
            $this->fileTypes = ZfExtended_Factory::get(editor_Models_Import_SupportedFileTypes::class);
        }
        return $this->fileTypes;
    }
}
