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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfInvalidException;
use MittagQI\Translate5\Plugins\Okapi\ImportFilter;
use MittagQI\Translate5\Plugins\Okapi\OkapiAdapter;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\Translate5\Plugins\Okapi\OkapiService;
use MittagQI\Translate5\Task\FileTranslation\FileTranslation;
use MittagQI\Translate5\Task\FileTypeSupport;
use MittagQI\Translate5\Task\Import\ImportEventTrigger;
use MittagQI\ZfExtended\ApiRequestDTO;

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
 * for documentation of the BCONF management, see BconfEntity
 *
 * @method static OkapiService createService(string $serviceName, Zend_Config $config = null)
 */
class editor_Plugins_Okapi_Init extends ZfExtended_Plugin_Abstract
{
    public const CONFIG_PRESERVE_XLF_FILES = 'runtimeOptions.plugins.Okapi.preserveGeneratedXlfFiles';

    /**
     * The current internal version index of the bconf's
     * This must be increased each time, a git-based fprm or srx is changed
     * @var int
     */
    public const BCONF_VERSION_INDEX = 10;

    /**
     * The filename of the system default import bconf
     * @var string
     */
    public const BCONF_SYSDEFAULT_IMPORT = 'okapi_default_import.bconf';

    /**
     * The GUI-name of the system default import bconf
     * @var string
     */
    public const BCONF_SYSDEFAULT_IMPORT_NAME = 'Translate5-Standard';

    public const SUPPORTED_OKAPI_VERSION = [
        'okapi-longhorn-037',
        'okapi-longhorn-139',
        'okapi-longhorn-141',
        'okapi-longhorn-143',
        'okapi-longhorn-144-snapshot',
        'okapi-longhorn-147',
        'okapi-longhorn-148-snapshot-1',
    ];

    protected static string $description = 'Provides Okapi pre-convertion and import of non bilingual data formats.';

    protected static bool $activateForTests = true;

    protected static bool $enabledByDefault = true;

    private static bool $doDebug = false;

    /**
     * Retrieves path to task bconf if it exists (or config-based path to the default export bconf otherwise)
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public static function getExportBconfPath(editor_Models_Task $task): string
    {
        $meta = $task->meta(true); // copied with this note from getImportBconf(): TODO FIXME: why reinit ?
        if (! empty($meta->getBconfInZip())) {
            return self::getDataDir() . 'okapi_default_export.bconf';
        }

        $bconf = new BconfEntity();
        $bconf->load((int) $meta->getBconfId());

        return $bconf->getPath(true);
    }

    /**
     * Retrieves the bconf to use for a task
     * This requiers the task to be saved and thus having valid meta entries!
     * @throws Zend_Exception
     */
    public static function getImportBconf(editor_Models_Task $task): BconfEntity
    {
        // may the task-type is set to use the system-default
        if ($task->getTaskType()->useSystemDefaultFileFormatSettings()) {
            return self::getSystemDefaultBconf();
        }
        $meta = $task->meta(true); // TODO FIXME: why reinit ?

        return self::getImportBconfById($task, (int) $meta->getBconfId());
    }

    /**
     * Retrieves the system default bconf
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public static function getSystemDefaultBconf(): BconfEntity
    {
        if (! isset(static::$cachedSysBconf)) {
            static::$cachedSysBconf = BconfEntity::getSystemDefaultBconf();
        }

        return static::$cachedSysBconf;
    }

    /**
     * Retrieves the default-customer bconf
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public static function getDefaultCustomerBconf(): BconfEntity
    {
        if (! isset(static::$cachedDefaultBconf)) {
            $defaultCustomer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
            $defaultCustomer->loadByDefaultCustomer();
            $bconf = new BconfEntity();
            static::$cachedDefaultBconf = $bconf->getDefaultBconf($defaultCustomer->getId());
        }

        return static::$cachedDefaultBconf;
    }

    /**
     * Fetches the import BCONF to use by id
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    private static function getImportBconfById(
        editor_Models_Task $task,
        int $bconfId = null,
        bool $addWarning = false,
        string $orderer = null,
    ): BconfEntity {
        // this may be called multiple times when processing the import upload, so we better cache it
        if (! empty($bconfId) && isset(static::$cachedBconf) && (int) static::$cachedBconf->getId() === $bconfId) {
            return static::$cachedBconf;
        }
        $bconf = new BconfEntity();
        // empty covers "not set" and also invalid id '0'
        // somehow dirty: unit tests pass a virtual" bconf-id of "0" to signal to use the system default bconf
        if (empty($bconfId)) {
            // a bconfId may not be given when the API is used via an unittest or the termportal
            // otherwise this is very unlikely if not impossible: no bconf-id set for the task.
            // In that case we use the default one and add a warning
            if ($addWarning && $orderer != 'unittest' && $orderer != 'termportal') {
                $task->logger('editor.task.okapi')->info(
                    'E1055',
                    'Okapi Plug-In: Bconf not given or not found: {bconfFile}',
                    [
                        'bconfFile' => 'No bconf-id was set for task meta',
                    ]
                );
            }
            $bconf = $bconf->getDefaultBconf((int) $task->getCustomerId());
        } else {
            $bconf->load($bconfId);
        }
        // we update outdated bconfs when accessing them
        $bconf->repackIfOutdated();
        static::$cachedBconf = $bconf;

        return $bconf;
    }

    /**
     * Return the okapi data directory file path with trailing slash.
     */
    public static function getDataDir(): string
    {
        return APPLICATION_PATH . '/modules/editor/Plugins/Okapi/data/';
    }

    /**
     * Finds bconf-files in the given directory and returns them as array for the Okapi Import.
     * This API is outdated and only used for the aligned XML/XSLT import in the visual
     */
    public static function findImportBconfFileInDir(string $dir): ?string
    {
        $directory = new DirectoryIterator($dir);
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (strtolower($fileinfo->getExtension()) === BconfEntity::EXTENSION) {
                return $fileinfo->getPathname();
            }
        }

        return null;
    }

    /**
     * The services we use
     * @var string[]
     */
    protected static array $services = [
        OkapiService::ID => OkapiService::class,
    ];

    private static BconfEntity $cachedBconf;

    private static BconfEntity $cachedSysBconf;

    private static BconfEntity $cachedDefaultBconf;

    protected $localePath = 'locales';

    protected $frontendControllers = [
        Rights::PLUGIN_OKAPI_BCONF_PREFS => 'Editor.plugins.Okapi.controller.BconfPrefs',
        Rights::PLUGIN_OKAPI_BCONF_CUSTOMER_PREFS => 'Editor.plugins.Okapi.controller.BconfPrefs',
    ];

    #region Plugin Init

    /**
     * @throws Zend_Exception
     */
    public function init()
    {
        $this->initEvents();
        $this->addController('BconfController');
        $this->addController('BconfFilterController');
        $this->addController('BconfDefaultFilterController');
        $this->initRoutes();
    }

    /**
     * @throws Zend_Exception
     */
    protected function initRoutes()
    {
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
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'downloadbconf',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_downloadbconf', $route);
        // post route to upload a bconf file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadbconf',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'uploadbconf',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_uploadbconf', $route);
        // post route to upload the SRX file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadsrx',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'uploadsrx',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_uploadsrx', $route);
        // route to download the SRX file.
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadsrx',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'downloadsrx',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_downloadsrx', $route);
        // post route to upload pipeline file
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/uploadpipeline',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'uploadpipeline',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_uploadpipeline', $route);
        // route to download pipeline file
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/downloadpipeline',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'downloadpipeline',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_downloadpipeline', $route);
        // clone bconf
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/clone',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'clone',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_clone', $route);
        // route to set the non-customer default
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/setdefault',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'setdefault',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_setdefault', $route);
        // route to check support for file-types
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconf/filetypesupport',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconf',
                'action' => 'filetypesupport',
            ]
        );
        $r->addRoute('plugins_okapi_bconf_filetypesupport', $route);

        // routes for bconf filters
        $route = new Zend_Rest_Route($f, [], [
            'editor' => ['plugins_okapi_bconffilter'],
        ]);
        $r->addRoute('plugins_okapi_bconffilter_restdefault', $route);

        // get fprm settings file content route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconffilter/getfprm',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconffilter',
                'action' => 'getfprm',
            ]
        );
        $r->addRoute('plugins_okapi_bconffilter_getfprm', $route);

        // save fprm settings file content route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconffilter/savefprm',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconffilter',
                'action' => 'savefprm',
            ]
        );
        $r->addRoute('plugins_okapi_bconffilter_savefprm', $route);

        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconffilter/downloadfprm',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconffilter',
                'action' => 'downloadfprm',
            ]
        );
        $r->addRoute('plugins_okapi_bconffilter_downloadfprm', $route);

        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconffilter/uploadfprm',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconffilter',
                'action' => 'uploadfprm',
            ]
        );
        $r->addRoute('plugins_okapi_bconffilter_uploadfprm', $route);

        // routes for default bconf filters
        $route = new Zend_Rest_Route($f, [], [
            'editor' => ['plugins_okapi_bconfdefaultfilter'],
        ]);
        $r->addRoute('plugins_okapi_bconfdefaultfilter_restdefault', $route);

        // default filters list route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconfdefaultfilter/getall',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconfdefaultfilter',
                'action' => 'getall',
            ]
        );
        $r->addRoute('plugins_okapi_bconfdefaultfilter_getall', $route);

        // save extensions for a non-custom filter route
        $route = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_okapi_bconfdefaultfilter/setextensions',
            [
                'module' => 'editor',
                'controller' => 'plugins_okapi_bconfdefaultfilter',
                'action' => 'setextensions',
            ]
        );
        $r->addRoute('plugins_okapi_bconfdefaultfilter_setextensions', $route);
    }

    /**
     * @throws Zend_Acl_Exception
     */
    public function getFrontendControllers(): array
    {
        return $this->getFrontendControllersFromAcl();
    }

    protected function initEvents(): void
    {
        // adds the task's bconf filetypes to the global registry
        $this->eventManager->attach(
            FileTypeSupport::class,
            'registerSupportedFileTypes',
            [$this, 'handleRegisterSupportedFileTypes']
        );
        $this->eventManager->attach(
            Editor_IndexController::class,
            'afterIndexAction',
            [$this, 'handleAfterIndex']
        );
        $this->eventManager->attach(
            Editor_IndexController::class,
            'afterLocalizedjsstringsAction',
            [$this, 'handleJsTranslations']
        );

        // adds the used bconf to the import-archive. At this point,
        // the task is not yet saved and the bconfId sent by request has to be used
        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::AFTER_UPLOAD_PREPARATION,
            [$this, 'handleAfterUploadPreparation']
        );

        // adds the bconfId to the task-meta
        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::INIT_TASK_META,
            [$this, 'handleInitTaskMeta']
        );
        // adds the bconfId to the file-translation
        $this->eventManager->attach(
            FileTranslation::class,
            'beforeFileTranslation',
            [$this, 'handleBeforeFileTranslation']
        );

        $this->eventManager->attach(
            editor_Models_Import_Worker_FileTree::class,
            'afterDirectoryParsing',
            [$this, 'handleAfterDirectoryParsing']
        );

        //invokes in the handleFile method of the relais filename match check.
        // Needed since relais files are bilingual (ending on .xlf) and the
        // imported files for Okapi are in the source format and do not end on .xlf.
        // Therefore the filenames do not match, this is corrected here.
        $this->eventManager->attach(
            editor_Models_RelaisFoldertree::class,
            'customHandleFile',
            [$this, 'handleCustomFileForRelais']
        );

        //Archives the temporary data folder again after converting the files with okapi:
        $this->eventManager->attach(
            editor_Models_Import_Worker_Import::class,
            'importCleanup',
            [$this, 'handleAfterImport']
        );

        //allows the manipulation of the export fileparser configuration
        $this->eventManager->attach(
            editor_Models_Export::class,
            'exportFileParserConfiguration',
            [$this, 'handleExportFileparserConfig']
        );

        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach(
            ZfExtended_Debug::class,
            'applicationState',
            [$this, 'handleApplicationState']
        );

        //attach to the config after index to check the config values
        $this->eventManager->attach(
            editor_ConfigController::class,
            'afterIndexAction',
            [$this, 'handleAfterConfigIndexAction']
        );
        $this->eventManager->attach(
            Editor_CustomerController::class,
            'afterIndexAction',
            [$this, 'handleCustomerAfterIndex']
        );
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws BconfInvalidException
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function handleRegisterSupportedFileTypes(Zend_EventManager_Event $event): void
    {
        /* @var FileTypeSupport $fileTypeSupport */
        $fileTypeSupport = $event->getTarget();
        /* @var editor_Models_Task|null $task */
        $task = $event->getParam('task');
        /* @var int $customerId */
        $customerId = $event->getParam('customerId');

        if (static::$doDebug) {
            error_log('OKAPI::handleRegisterSupportedFileTypes: task: '
                . ($task === null ? 'null' : $task->getTaskGuid())
                . ', customerId: ' . $customerId);
        }

        $okapiDataDirExists = false;

        if (! empty($task)) {
            $meta = $task->meta();
            $bconf = (empty($meta->getBconfInZip())) ? self::getImportBconfById($task, $meta->getBconfId()) : null;
            $importFilter = new ImportFilter($bconf, $meta->getBconfInZip());
            $okapiDataDir = $task->getAbsoluteTaskDataPath() . '/' . editor_Plugins_Okapi_Worker::OKAPI_REL_DATA_DIR;
            $okapiDataDirExists = is_dir($okapiDataDir);
        } elseif ($customerId > 0) {
            // if a customer-id is given (usually by file-translation), we use the customers default bconf
            $bconf = new BconfEntity();
            $bconf = $bconf->getDefaultBconf($customerId);
            $importFilter = new ImportFilter($bconf, null);
        } else {
            // since all tasks are by default generated with the "defaultcustomer" we use this bconf as the system std.
            // this case normally can not happen
            $bconf = self::getDefaultCustomerBconf();
            $importFilter = new ImportFilter($bconf, null);
        }
        // we may need the ImportFilter in other functions
        $fileTypeSupport->registerPluginData($importFilter, $this->pluginName);

        $skipCoreParserSet = [];
        // we support XLIFF-via-OKAPI only with BCONFs from Version 10 on to enable full compatibility with older task-archives
        if (! $okapiDataDirExists && $bconf !== null && $bconf->getPipeline()->getBconfVersion() >= 10) {
            $extensionMapping = $bconf->getExtensionMapping();
            foreach (editor_Models_Import_FileParser_Xlf::getFileExtensions() as $extension) {
                if ($extensionMapping->hasExtension($extension)) {
                    $skipCoreParserSet[$extension] = 1;
                }
            }
        }

        // This sets the supported file-extension for the requested task (if given) or the used default bconf
        // These are e.g. used to filter the supported file-types for the import wizard in the frontend
        // and filter the workfile-imports
        foreach ($importFilter->getSupportedExtensions() as $extension) {
            $fileTypeSupport->register($extension, $this->pluginName, isset($skipCoreParserSet[$extension]));
        }
    }

    /**
     * Adds the system default bconf-id to the global JS scope
     * This is needed in the bconf-management
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function handleAfterIndex(Zend_EventManager_Event $event): void
    {
        if (static::$doDebug) {
            error_log('OKAPI::handleAfterIndex');
        }

        $view = $event->getParam('view');
        /** @var $view ZfExtended_View */
        $bconf = new BconfEntity();
        $view->Php2JsVars()->set('plugins.Okapi.defaultBconfId', $bconf->getDefaultBconfId());
        $view->Php2JsVars()->set('plugins.Okapi.systemDefaultBconfName', self::BCONF_SYSDEFAULT_IMPORT_NAME);
    }

    /**
     * Adds our plugin specific translations
     */
    public function handleJsTranslations(Zend_EventManager_Event $event)
    {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }

    #endregion

    /**
     * Hook that adds the bconfId sent by the Import wizard to the task-meta
     * Called in the same request as handleInitTaskMeta, handleAfterUploadPreparation
     * Take care: the task is not yet saved here
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function handleInitTaskMeta(Zend_EventManager_Event $event)
    {
        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        /* @var $meta editor_Models_Task_Meta */
        $meta = $event->getParam('meta');
        /* @var $requestData array */
        $requestData = $event->getParam('data');
        $bconfId = array_key_exists('bconfId', $requestData) ? $requestData['bconfId'] : null;

        if (static::$doDebug) {
            error_log('OKAPI::handleInitTaskMeta: task ' . $task->getTaskGuid() . ', requestBconfId: '
                . ($bconfId === null ? 'null' : $bconfId));
        }

        // empty makes sense here since we only accept an bconf-id > 0
        if (empty($bconfId)) {
            $bconf = new BconfEntity();
            $bconfId = $bconf->getDefaultBconfId($task->getCustomerId());
        }
        $meta->setBconfId($bconfId);
    }

    /**
     * Hook before a file-translation is requested as an internal-api request
     * We add the bconf-id then as the bconf-id is neccessary to properly evaluate the file-types
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     */
    public function handleBeforeFileTranslation(Zend_EventManager_Event $event)
    {
        /* @var ApiRequestDTO $requestDto */
        $requestDto = $event->getParam('requestDto');
        /* @var int $customerId */
        $customerId = $event->getParam('customerId');

        if (! array_key_exists('bconfId', $requestDto->params)) {
            // get the customer default-id and add it ...
            $bconf = new BconfEntity();
            $bconf = $bconf->getDefaultBconf($customerId);
            $requestDto->params['bconfId'] = $bconf->getId();
        }
    }

    /**
     * Hook that adds the used bconf to the ImportArchive as a long-term reference which bconf was used
     * Called in the same request as handleInitTaskMeta, handleAfterUploadPreparation
     * Take care: the task is not yet saved here
     *
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function handleAfterUploadPreparation(Zend_EventManager_Event $event)
    {
        /* @var editor_Models_Import_DataProvider_Abstract $dataProvider */
        $dataProvider = $event->getParam('dataProvider');
        /* @var editor_Models_Task $task */
        $task = $event->getParam('task');

        $bconfInZip = self::findImportBconfFileinDir($dataProvider->getAbsImportPath());

        if (static::$doDebug) {
            error_log('OKAPI::handleAfterUploadPreparation: task ' . $task->getTaskGuid() . ', bconfInZip: '
                . ($bconfInZip === null ? 'null' : basename($bconfInZip)));
        }

        if ($bconfInZip === null) {
            /* @var $requestData array */
            $requestData = $event->getParam('requestData');
            $bconfId = array_key_exists('bconfId', $requestData) ? intval($requestData['bconfId']) : null;
            $orderer = array_key_exists('orderer', $requestData) ? $requestData['orderer'] : null;
            $bconf = self::getImportBconfById($task, $bconfId, true, $orderer);
            // we add the bconf with it's visual name as filename to the archive for easier maintainability
            $dataProvider->addAdditonalFileToArchive($bconf->getPath(), $bconf->getDownloadFilename());
        } else {
            // if a bconf is provided in the import zip it will be part of the archive anyway
            $bconfInZip = realpath($bconfInZip);
            // ugly: we truncate the bconf's filename if it's name is too long
            if (strlen($bconfInZip) > 255) {
                $dir = dirname($bconfInZip);
                $extension = pathinfo($bconfInZip, PATHINFO_EXTENSION);
                $bconfFilename =
                    substr(pathinfo($bconfInZip, PATHINFO_FILENAME), 0, 255 - (strlen($dir) + strlen($extension) + 2))
                    . '.' . $extension;
                rename($bconfInZip, $dir . DIRECTORY_SEPARATOR . $bconfFilename);
                $bconfInZip = $dir . DIRECTORY_SEPARATOR . $bconfFilename;
            }
            $task->meta()->setBconfInZip($bconfInZip);
        }
    }

    /**
     * Hook on the before import event and check the import files & queue our worker if needed
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function handleAfterDirectoryParsing(Zend_EventManager_Event $event)
    {
        /* @var string[] $filelist */
        $filelist = $event->getParam('filelist');
        /* @var string $importFolder */
        $importFolder = $event->getParam('importFolder');
        /* @var editor_Models_Task $task */
        $task = $event->getParam('task');

        $fileTypeSupport = $task->getFileTypeSupport();
        /* @var ImportFilter $importFilter */
        $importFilter = $fileTypeSupport->getRegisteredPluginData($this->pluginName);

        if (static::$doDebug) {
            error_log('OKAPI::handleAfterDirectoryParsing: task ' . $task->getTaskGuid() . ', bconf: '
                . $importFilter->getBconfDisplayName());
        }

        // TRANSLATE-4240 Deactivated: there should be a warning if the deprecated bconf in import-zip was used
        /*
        if ($importFilter->hasEmbeddedBconf()) {
            $task->logger('editor.task.okapi')->warn(
                'E1387',
                'Okapi Plug-In: Providing the BCONF to use in the import ZIP is deprecated',
                [
                    'bconf' => $importFilter->getBconfName(),
                ]
            );
        }
        */

        $fileFilter = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
        foreach ($filelist as $fileId => $filePath) {
            $fileInfo = new SplFileInfo($importFolder . '/' . ZfExtended_Utils::filesystemEncode($filePath));
            //if there is a filefilter or we cannot convert the file we do not process the file with Okapi
            if ($fileFilter->hasFilter($fileId, $fileFilter::TYPE_IMPORT) ||
                ! $this->isProcessableFile($fileInfo, $fileTypeSupport, $importFilter)
            ) {
                continue;
            }
            $this->queueWorker($fileId, $fileInfo, $importFilter, $event->getParams());
        }
    }

    /**
     * Needed since relais files are bilingual (ending on .xlf) and the
     * imported files for Okapi are in the source format (example docx) and do not end on .xlf.
     * Therefore the filenames do not match to the relais files, this is corrected here.
     * @throws Zend_Exception
     */
    public function handleCustomFileForRelais(Zend_EventManager_Event $event)
    {
        $suffix = '.xlf'; //TODO should come from the worker. there the suffix is determined from the okapi output
        $child = $event->getParam('fileChild'); //children stdClass, contains informations about the file to be parsed
        $fullpath = $event->getParam('fullPath'); //absolute path to the relais file to be parsed
        $task = editor_ModelInstances::taskByGuid($event->getParam('taskGuid'));

        if (static::$doDebug) {
            error_log('OKAPI::handleCustomFileForRelais: task ' . $task->getTaskGuid()
                . ', fileChild relaisFileStatus ' . $child->relaisFileStatus);
        }

        if ($child->relaisFileStatus == editor_Models_RelaisFoldertree::RELAIS_NOT_FOUND) {
            //INFO:(TRANSLATE-1596) this is the workfiles directory (for now this can be proofRead or workfiles).
            // After we removed the deprecated support for proofRead this can be removed
            /** @var editor_Models_Import_Configuration $importConfig */
            $importConfig = $event->getParam('importConfig');

            $config = Zend_Registry::get('config');
            $workfiles = '/' . trim($importConfig->getFilesDirectory(), '/') . '/';
            $relaisDirectory = '/' . trim($config->runtimeOptions->import->relaisDirectory, '/') . '/';
            $fullpath = $fullpath . $suffix;
            $bilingualSourceFile = str_replace($relaisDirectory, $workfiles, $fullpath);

            //check for manifest file, to ensure that the file was processed via Okapi:
            if (file_exists($fullpath) && file_exists($bilingualSourceFile) &&
                $this->wasImportedWithOkapi($task, $child->id)
            ) {
                $child->filename .= $suffix;
                $child->relaisFileStatus = editor_Models_RelaisFoldertree::RELAIS_NOT_IMPORTED;
            }
        }
    }

    /**
     * Archives the temporary data folder again after converting the files with okapi
     * @throws ReflectionException
     * @throws editor_Models_Import_DataProvider_Exception
     */
    public function handleAfterImport(Zend_EventManager_Event $event)
    {
        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        /* @var $config editor_Models_Import_Configuration */
        $config = $event->getParam('importConfig');

        if (static::$doDebug) {
            error_log('OKAPI::handleAfterImport: task ' . $task->getTaskGuid());
        }

        try {
            $worker = new ZfExtended_Models_Worker();
            $worker->loadFirstOf(editor_Plugins_Okapi_Worker::class, $task->getTaskGuid());

            //proceed with the archive only, if a okapi worker was found for the current task
            $directoryProvider = ZfExtended_Factory::get(
                editor_Models_Import_DataProvider_Directory::class,
                [$config->importFolder]
            );
            $directoryProvider->checkAndPrepare($task);
            $directoryProvider->archiveImportedData('OkapiArchive.zip');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            //no okapi worker -> do nothing
        }
    }

    /**
     * Checks if the configured okapi instance is reachable
     */
    public function handleApplicationState(Zend_EventManager_Event $event): void
    {
        if (static::$doDebug) {
            error_log('OKAPI::handleApplicationState');
        }

        $applicationState = $event->getParam('applicationState');
        $applicationState->okapi = new stdClass();
        $connector = new OkapiAdapter();
        // the default current configured serverUsed will be checked
        $applicationState->okapi->server = $connector->ping();
    }

    /**
     * Sets additional file parser configurations if file was imported with Okapi
     */
    public function handleExportFileparserConfig(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        $file = $event->getParam('file');
        /* @var $file editor_Models_File */
        $config = $event->getParam('config');
        /* @var $config stdClass */
        $context = $event->getParam('context');

        if (static::$doDebug) {
            error_log('OKAPI::handleExportFileparserConfig: task ' . $task->getTaskGuid() . ', file '
                . $file->getId());
        }

        if ($this->wasImportedWithOkapi($task, $file->getId()) && $context !== editor_Models_Export::EXPORT_PACKAGE) {
            // files imported with okapi have always source to empty target on (TRANSLATE-2384)
            $config->options['sourcetoemptytarget'] = true;
        }
    }

    /***
     * After config index action event handler.
     * This will check if runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName are up to date with the files on the disk
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function handleAfterConfigIndexAction(Zend_EventManager_Event $event): void
    {
        if (static::$doDebug) {
            error_log('OKAPI::handleAfterConfigIndexAction');
        }

        $rows = $event->getParam('view')->rows ?? [];
        if (empty($rows)) {
            return;
        }
        //find the default export configs
        $index = array_search(
            'runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName',
            array_column($rows, 'name')
        );
        // update the defaults in the database if existing & neccessary
        if ($index !== false) {
            $defaultBconfs = implode(',', $this->findDefaultBconfFiles());
            $config = $rows[$index];
            //the config has the same files as defaults
            if ($config['defaults'] != $defaultBconfs) {
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
     * @see ZfExtended_RestController::afterActionEvent
     */
    public function handleCustomerAfterIndex(Zend_EventManager_Event $event)
    {
        if (static::$doDebug) {
            error_log('OKAPI::handleCustomerAfterIndex');
        }

        /** @var ZfExtended_View $view */
        $view = $event->getParam('view');
        $meta = new editor_Models_Db_CustomerMeta();
        $metas = $meta->fetchAll('defaultBconfId IS NOT NULL')->toArray();
        $bconfIds = array_column($metas, 'defaultBconfId', 'customerId');
        foreach ($view->rows as &$customer) {
            if (array_key_exists($customer['id'], $bconfIds)) {
                $customer['defaultBconfId'] = (int) $bconfIds[$customer['id']];
            } else {
                $customer['defaultBconfId'] = null;
            }
        }
    }

    /**
     * Checks if the file was imported via Okapi (via existence of the manifest file).
     * This gives no information if the import via Okapi was successful!
     */
    protected function wasImportedWithOkapi(editor_Models_Task $task, int $fileId): bool
    {
        $path = $task->getAbsoluteTaskDataPath() . '/' . editor_Plugins_Okapi_Worker::OKAPI_REL_DATA_DIR . '/';
        $okapiManifestFile = new SplFileInfo(
            $path . sprintf(editor_Plugins_Okapi_Worker::MANIFEST_FILE, $fileId)
        );

        return $okapiManifestFile->isReadable();
    }

    /***
     * Find all available import bconf files in the okapi data directory
     * @return string[]
     */
    protected function findDefaultBconfFiles(): array
    {
        $filenames = [];
        $directory = new DirectoryIterator(self::getDataDir());
        foreach ($directory as $fileinfo) {
            /* @var $fileinfo SplFileInfo */
            if (strtolower($fileinfo->getExtension()) === BconfEntity::EXTENSION) {
                $filenames[] = $fileinfo->getFilename();
            }
        }

        return $filenames;
    }

    /**
     * Run for each file a separate worker, the worker will upload the file to the okapi,
     * convert the file and download the result
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    protected function queueWorker(int $fileId, SplFileInfo $file, ImportFilter $importFilter, array $params): void
    {
        /* @var editor_Models_Task $task */
        $task = $params['task'];
        /* @var int $workerParentId */
        $workerParentId = $params['workerParentId'];

        $params = [
            'type' => editor_Plugins_Okapi_Worker::TYPE_IMPORT,
            'fileId' => $fileId,
            'file' => (string) $file,
            'importFolder' => $params['importFolder'],
            'importConfig' => $params['importConfig'],
            'bconfFilePath' => $importFilter->getBconfPath(),
            'bconfName' => $importFilter->getBconfDisplayName(),
        ];

        // init worker and queue it
        $worker = ZfExtended_Factory::get(editor_Plugins_Okapi_Worker::class);
        if (! $worker->init($task->getTaskGuid(), $params)) {
            return;
        }
        $worker->queue($workerParentId);
    }

    /**
     * Checks if the given file should be processed by okapi
     */
    private function isProcessableFile(
        SplFileInfo $fileinfo,
        FileTypeSupport $fileTypeSupport,
        ImportFilter $importFilter,
    ): bool {
        $extension = strtolower($fileinfo->getExtension());
        if (! $fileinfo->isFile()) {
            return false;
        }
        $parser = $fileTypeSupport->hasSupportedParser($extension, $fileinfo);
        if (! is_null($parser)) {
            // if one of the registered parsers may parse the file, then we don't need Okapi
            return false;
        }

        // is the extension is supported by the used bconf ?
        return $importFilter->isExtensionSupported($extension);
    }
}
