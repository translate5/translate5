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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Acl\TaskCustomField;
use MittagQI\Translate5\Applet\AppletAbstract;
use MittagQI\Translate5\Applet\Dispatcher;
use MittagQI\Translate5\CrossSynchronization\Events\EventListener;
use MittagQI\Translate5\DbConfig\ActionsEventHandler;
use MittagQI\Translate5\Segment\UpdateLanguageResourcesWorker;
use MittagQI\Translate5\Service\SystemCheck;
use MittagQI\Translate5\Task\Deadline\TaskDeadlineEventHandler;
use MittagQI\Translate5\Task\Import\DanglingImportsCleaner;
use MittagQI\Translate5\Task\TaskEventTrigger;
use MittagQI\Translate5\Workflow\DeleteOpenidUsersAction;
use MittagQI\ZfExtended\Acl\AutoSetRoleResource;
use MittagQI\ZfExtended\Acl\ResourceManager as AclResourceManager;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use ZfExtended_Plugin_Manager as PluginManager;

/**
 * Klasse zur Portalinitialisierung
 *
 * - In initApplication können Dinge zur Portalinitialisierung aufgerufen werden
 * - Alles für das Portal nötige ist jedoch in Resource-Plugins ausgelagert und
 *   wird über die application.ini definiert und dann über Zend_Application
 *   automatisch initialisert
 */
class Editor_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected $front;

    public function __construct($application)
    {
        parent::__construct($application);

        //Binding the worker clean up to the after import event, since import
        // is currently the main use case for workers
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();

        EventListener::create($eventManager)->attachAll();

        $cleanUp = function () {
            // first clean up jobs
            ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class)->cleanupLocked();

            // second clean up tasks, jobs must be before in order to clean also not used multiuser tasks anymore
            ZfExtended_Factory::get(editor_Models_Task::class)->cleanupLockedJobs();

            (new DanglingImportsCleaner())->cleanup();

            ZfExtended_Factory::get(editor_Models_UserConfig::class)->cleanUpThemeTemporary();
        };

        $eventManager->attach(ZfExtended_Resource_GarbageCollector::class, 'cleanUp', $cleanUp);
        $eventManager->attach(LoginController::class, 'afterLogoutAction', $cleanUp);
        $eventManager->attach(editor_SessionController::class, 'afterDeleteAction', $cleanUp);
        $eventManager->attach(ZfExtended_Session::class, 'afterSessionCleanForUser', $cleanUp);
        $eventManager->attach(ZfExtended_Debug::class, 'applicationState', [$this, 'handleApplicationState']);

        // Binding the quality Worker queuing to the "afterDirectoryParsing" event of the filetree worker.
        // some qualities have workers that depend on the imported files (e.g. TBX import).
        // also this needs to be a point in the import-process after the languuege-resources in the wizard have been set
        // and it should be as early as possible to ensure the progress-bar does not flutter
        $eventManager->attach(
            editor_Models_Import_Worker_FileTree::class,
            'afterDirectoryParsing',
            function (Zend_EventManager_Event $event) {
                /* @var editor_Models_Task $task */
                $task = $event->getParam('task');
                // this represents the id of the import worker, see ProjectWorkersHandler::queueImportWorkers
                $parentId = (int) $event->getParam('workerParentId');
                editor_Segment_Quality_Manager::instance()->queueImport($task, $parentId);
            }
        );

        $eventHandler = new ActionsEventHandler();

        $eventManager->attach(
            editor_ConfigController::class,
            'afterIndexAction',
            $eventHandler->addDefaultsForNonZeroQualityErrorsSettingOnIndexAction()
        );
        $eventManager->attach(
            editor_ConfigController::class,
            'afterPutAction',
            $eventHandler->addDefaultsForNonZeroQualityErrorsSettingOnPutAction()
        );

        $eventManager->attach(
            TaskEventTrigger::class,
            TaskEventTrigger::AFTER_SEGMENT_UPDATE,
            function (Zend_EventManager_Event $event) {
                $worker = ZfExtended_Factory::get(UpdateLanguageResourcesWorker::class);
                $worker->init(parameters: [
                    'segmentId' => $event->getParam('segment')->getId(),
                ]);
                $worker->queue();
            }
        );

        $eventManager->attach(
            editor_ConfigController::class,
            'afterIndexAction',
            $eventHandler->addDefaultPMUsersOnIndexAction(DeleteOpenidUsersAction::FALLBACK_PM_CONFIG)
        );
        $eventManager->attach(
            editor_ConfigController::class,
            'afterPutAction',
            $eventHandler->addDefaultPMUsersOnPutAction(DeleteOpenidUsersAction::FALLBACK_PM_CONFIG)
        );

        $taskDeadlineDateEventHandler = new TaskDeadlineEventHandler($eventManager);
        $taskDeadlineDateEventHandler->register();
    }

    public static function initModuleSpecific()
    {
        ZfExtended_Models_SystemRequirement_Validator::addModule(SystemCheck::CHECK_NAME, SystemCheck::class);

        // add the default applet editor, if this will change move the register into editor bootstrap
        Dispatcher::getInstance()->registerApplet('editor', new class() extends AppletAbstract {
            protected int $weight = 100; //editor should have the heighest weight

            protected string $urlPathPart = '/editor/';

            protected string $initialPage = Rights::APPLET_EDITOR;
        });

        //configure the Role based resources with the current roleset:
        SetAclRoleResource::$roleDefinition = Roles::class;
        AutoSetRoleResource::$roleDefinition = Roles::class;

        //add the module based ACL resources:
        AclResourceManager::registerResource(Rights::class);
        AclResourceManager::registerResource(TaskCustomField::class);
        AclResourceManager::registerResource(Dispatcher::class, true);

        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        $eventManager->attach(PluginManager::class, PluginManager::EVENT_AFTER_PLUGIN_BOOTSTRAP, function () {
            AclResourceManager::registerResource(editor_Task_Type::class, true);
        });
    }

    public function _initController()
    {
        $this->front = Zend_Controller_Front::getInstance();
    }

    public function _initREST()
    {
        $this->front->setRequest(new ZfExtended_Sanitized_HttpRequest());

        // register the RestHandler plugin
        $this->front->registerPlugin(new ZfExtended_Controllers_Plugins_RegisterRestControllerPluginRestHandler());

        // add REST contextSwitch helper
        $contextSwitch = new REST_Controller_Action_Helper_ContextSwitch();
        Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

        // add restContexts helper
        $restContexts = new REST_Controller_Action_Helper_RestContexts();
        Zend_Controller_Action_HelperBroker::addHelper($restContexts);
    }

    /**
     * @uses editor_FileController::packageAction()
     */
    public function _initRestRoutes()
    {
        $restRoute = new Zend_Rest_Route($this->front, [], [
            'editor' => [
                'file', 'filetree', 'segment', 'alikesegment', 'customer', 'customermeta', 'referencefile', 'comment', 'attributedatatype',
                'task', 'user', 'taskuserassoc', 'segmentfield', 'workflowuserpref', 'worker', 'taskmeta',
                'config', 'segmentuserassoc', 'session', 'language', 'termcollection', 'taskcustomfield',
                'languageresourceresource', 'languageresourcetaskassoc', 'languageresourcetaskpivotassoc',
                'languageresourceinstance', 'taskusertracking', 'term', 'attribute', 'termattribute', 'category',
                'quality', 'userassocdefault', 'log', 'collectionattributedatatype', 'token',
                'contentprotectioncontentrecognition', 'contentprotectioninputmapping', 'contentprotectionoutputmapping',
                'languageresourcesyncconnection', 'lsp',
            ],
        ]);
        $this->front->getRouter()->addRoute('editorRestDefault', $restRoute);

        /**
         * Operation routes:
         * - operations extend the RESTful approach of the API to provide operations triggered on an entity or on a set of entities instead of doing all by CRUD actions only
         * - operations|batch may exist either as fooOperation or fooBatch method in the controller, or as an attached event to the controller
         * - operation is on one entity
         * - batch is the same on the current entity filter set
         */
        $this->front->getRouter()->addRoute('editorOperationHandler', new ZfExtended_Controller_RestLikeRoute(
            'editor/:controller/:id/:operation/operation',
            [
                'module' => 'editor',
                'action' => '',
            ]
        ));
        $this->front->getRouter()->addRoute('editorBatchHandler', new ZfExtended_Controller_RestLikeRoute(
            'editor/:controller/:operation/batch',
            [
                'module' => 'editor',
                'action' => '',
            ]
        ));

        $this->front->getRouter()->addRoute('editorFiletreeRootRoute', new ZfExtended_Controller_RestLikeRoute(
            'editor/filetree/root',
            [
                'module' => 'editor',
                'controller' => 'filetree',
                'action' => 'root',
            ]
        ));

        $this->front->getRouter()->addRoute('editorFilePackagetRoute', new ZfExtended_Controller_RestLikeRoute(
            'editor/file/package',
            [
                'module' => 'editor',
                'controller' => 'file',
                'action' => 'package',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskPackagestatustRoute', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/packagestatus',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'packagestatus',
            ]
        ));

        $this->front->getRouter()->addRoute(
            'editor.queuedexport.view',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/queuedexport/:token',
                [
                    'module' => 'editor',
                    'controller' => 'queuedexport',
                    'action' => 'view',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'editor.queuedexport.status',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/queuedexport/:token/status',
                [
                    'module' => 'editor',
                    'controller' => 'queuedexport',
                    'action' => 'status',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'editor.queuedexport.download',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/queuedexport/:token/download',
                [
                    'module' => 'editor',
                    'controller' => 'queuedexport',
                    'action' => 'download',
                ]
            )
        );

        //FIXME convert to RestLikeRoute (remove echo json_encode in action then)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/filemap/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'filemap',
            ]
        );
        $this->front->getRouter()->addRoute('editorFilemap', $filemapRoute);

        //must be added before the default RestRoutes
        $this->front->getRouter()->addRoute('editorSegmentPosition', new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/:segmentNrInTask/position',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'position',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskTriggerWorkflow', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/workflow',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'workflow',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskClone', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/clone',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'clone',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskEvents', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/events',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'events',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskImport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/import',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'import',
            ]
        ));

        // Excel Ex- + Reimport
        $this->front->getRouter()->addRoute('editorTaskExcelExport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/excelexport',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'excelexport',
            ]
        ));
        $this->front->getRouter()->addRoute('editorTaskExcelReimport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/excelreimport',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'excelreimport',
            ]
        ));
        $this->front->getRouter()->addRoute('editorTaskUserlist', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/userlist',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'userlist',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskPosition', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/position',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'position',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskUserAssocProject', new ZfExtended_Controller_RestLikeRoute(
            'editor/taskuserassoc/project',
            [
                'module' => 'editor',
                'controller' => 'taskuserassoc',
                'action' => 'project',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTaskCommentNav', new ZfExtended_Controller_RestLikeRoute(
            'editor/commentnav',
            [
                'module' => 'editor',
                'controller' => 'commentnav',
                'action' => 'index',
            ]
        ));

        //FIXME convert me to RestLikeRoute (see filemap)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/nextsegments/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'nextsegments',
            ]
        );
        $this->front->getRouter()->addRoute('editorNextSegments', $filemapRoute);

        //FIXME convert me to RestLikeRoute (see filemap)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/matchratetypes/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'matchratetypes',
            ]
        );
        $this->front->getRouter()->addRoute('editorMatchratetypes', $filemapRoute);

        $searchRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/search/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'search',
            ]
        );
        $this->front->getRouter()->addRoute('editorSearchSegment', $searchRoute);

        $replaceAllRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/replaceall/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'replaceall',
            ]
        );
        $this->front->getRouter()->addRoute('editorReplaceallSegment', $replaceAllRoute);

        $replaceAllRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/stateid/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'stateid',
            ]
        );
        $this->front->getRouter()->addRoute('editorSegmentStateId', $replaceAllRoute);

        $authUserRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/user/authenticated/*',
            [
                'module' => 'editor',
                'controller' => 'user',
                'action' => 'authenticated',
            ]
        );
        $this->front->getRouter()->addRoute('editorAuthUser', $authUserRoute);

        $pmRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/user/pm/*',
            [
                'module' => 'editor',
                'controller' => 'user',
                'action' => 'pm',
            ]
        );
        $this->front->getRouter()->addRoute('editorUserPm', $pmRoute);

        $termsRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/terms/*',
            [
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'terms',
            ]
        );
        $this->front->getRouter()->addRoute('editorTerms', $termsRoute);

        $exportRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/task/export/*',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'export',
            ]
        );
        $this->front->getRouter()->addRoute('editorExport', $exportRoute);

        $taskStat = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/statistics/*',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'statistics',
            ]
        );
        $this->front->getRouter()->addRoute('editorTaskStat', $taskStat);

        $taskKpi = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/kpi',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'kpi',
            ]
        );
        $this->front->getRouter()->addRoute('editorTaskKpi', $taskKpi);

        $importprogress = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/importprogress',
            [
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'importprogress',
            ]
        );
        $this->front->getRouter()->addRoute('editorTaskImportprogress', $importprogress);

        $workerRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/worker/queue/*',
            [
                'module' => 'editor',
                'controller' => 'worker',
                'action' => 'queue',
            ]
        );
        $this->front->getRouter()->addRoute('editorQueue', $workerRoute);

        $termCollectionImport = new ZfExtended_Controller_RestLikeRoute(
            'editor/termcollection/import/*',
            [
                'module' => 'editor',
                'controller' => 'termcollection',
                'action' => 'import',
            ]
        );
        $this->front->getRouter()->addRoute('termCollectionImport', $termCollectionImport);

        $termCollectionExport = new ZfExtended_Controller_RestLikeRoute(
            'editor/termcollection/export/*',
            [
                'module' => 'editor',
                'controller' => 'termcollection',
                'action' => 'export',
            ]
        );
        $this->front->getRouter()->addRoute('termCollectionExport', $termCollectionExport);

        $getCollectionAttributes = new ZfExtended_Controller_RestLikeRoute(
            'editor/termcollection/testgetattributes/*',
            [
                'module' => 'editor',
                'controller' => 'termcollection',
                'action' => 'testgetattributes',
            ]
        );
        $this->front->getRouter()->addRoute('testgetattributes', $getCollectionAttributes);

        $searchTermExists = new ZfExtended_Controller_RestLikeRoute(
            'editor/termcollection/searchtermexists/*',
            [
                'module' => 'editor',
                'controller' => 'termcollection',
                'action' => 'searchtermexists',
            ]
        );
        $this->front->getRouter()->addRoute('searchtermexists', $searchTermExists);

        $lastusedapp = new ZfExtended_Controller_RestLikeRoute(
            'editor/apps/lastusedapp/*',
            [
                'module' => 'editor',
                'controller' => 'apps',
                'action' => 'lastusedapp',
            ]
        );
        $this->front->getRouter()->addRoute('lastusedapp', $lastusedapp);

        # Language resources rutes start
        // WARNING: Order of the route definition is important!
        // the catchall like download route must be defined before the more specific query/search routes!

        $this->front->getRouter()->addRoute(
            'languageresources_languageresourcesync_availableforconnection',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/languageresourcesync/:id/available-for-connection',
                [
                    'module' => 'editor',
                    'controller' => 'languageresourcesync',
                    'action' => 'availableforconnection',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'languageresources_languageresourcesync_queue_synchronize_all',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/languageresourcesync/:id/queue-synchronize-all',
                [
                    'module' => 'editor',
                    'controller' => 'languageresourcesync',
                    'action' => 'queuesynchronizeall',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'languageresources_languageresourcesyncconnection_queue_synchronize',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/languageresourcesyncconnection/:id/queue-synchronize',
                [
                    'module' => 'editor',
                    'controller' => 'languageresourcesyncconnection',
                    'action' => 'queuesynchronize',
                ]
            )
        );

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/:type',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'download',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_download', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:languageResourceId/query',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'query',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_query', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:languageResourceId/search',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'search',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_search', $queryRoute);

        $translateRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:languageResourceId/translate',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'translate',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_translate', $translateRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/import',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'import',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_import', $queryRoute);

        $this->front->getRouter()->addRoute(
            'languageresources_languageresourceinstance_defaulttmneedsconversion',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/languageresourceinstance/defaulttmneedsconversion',
                [
                    'module' => 'editor',
                    'controller' => 'languageresourceinstance',
                    'action' => 'defaulttmneedsconversion',
                ]
            )
        );

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/tasks',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'tasks',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_tasks', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/synchronizetm',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'synchronizetm',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_synchronizetm', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/synchronizetm/batch',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'synchronizetmbatch',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_synchronizetm_batch', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceresource/:resourceType/engines',
            [
                'module' => 'editor',
                'controller' => 'languageresourceresource',
                'action' => 'engines',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceresource_esngines', $queryRoute);

        $this->front->getRouter()->addRoute('editorLanguageResourcesEvents', new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/events',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'events',
            ]
        ));

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/export',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'export',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_export', $queryRoute);

        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_tbxexport', new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/tbxexport',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'tbxexport',
            ]
        ));

        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_xlsxexport', new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/xlsxexport',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'xlsxexport',
            ]
        ));

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/testexport',
            [
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'testexport',
            ]
        );
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_testexport', $queryRoute);
        #Language resource rutes end

        $customerResourceExport = new ZfExtended_Controller_RestLikeRoute(
            'editor/customer/exportresource',
            [
                'module' => 'editor',
                'controller' => 'customer',
                'action' => 'exportresource',
            ]
        );
        $this->front->getRouter()->addRoute('customer_resourceexport', $customerResourceExport);

        $sessionImpersonate = new ZfExtended_Controller_RestLikeRoute(
            'editor/session/impersonate',
            [
                'module' => 'editor',
                'controller' => 'session',
                'action' => 'impersonate',
            ]
        );
        $this->front->getRouter()->addRoute('editorSessionImpersonate', $sessionImpersonate);

        // quality subroutes
        $this->front->getRouter()->addRoute(
            'editorQualityDownloadStatistics',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/downloadstatistics/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'downloadstatistics',
                ]
            )
        );
        $this->front->getRouter()->addRoute(
            'editorSegmentQuality',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/segment/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'segment',
                ]
            )
        );
        $this->front->getRouter()->addRoute(
            'editorTaskQuality',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/task/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'task',
                ]
            )
        );
        $this->front->getRouter()->addRoute(
            'editorTaskQualityTooltip',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/tasktooltip/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'tasktooltip',
                ]
            )
        );
        $this->front->getRouter()->addRoute(
            'editorQualityFalsepositive',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/falsepositive/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'falsepositive',
                ]
            )
        );
        $this->front->getRouter()->addRoute(
            'editorQualityFalsepositivespread',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/falsepositivespread/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'falsepositivespread',
                ]
            )
        );
        $this->front->getRouter()->addRoute(
            'editorQualitySegmentQm',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/segmentqm/*',
                [
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'segmentqm',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'termTransfer',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/term/transfer/*',
                [
                    'module' => 'editor',
                    'controller' => 'term',
                    'action' => 'transfer',
                ]
            )
        );

        $this->front->getRouter()->addRoute('editorAttributeHistory', new ZfExtended_Controller_RestLikeRoute(
            'editor/attribute/:id/history',
            [
                'module' => 'editor',
                'controller' => 'attribute',
                'action' => 'history',
            ]
        ));

        $this->front->getRouter()->addRoute('editorTermHistory', new ZfExtended_Controller_RestLikeRoute(
            'editor/term/:id/history',
            [
                'module' => 'editor',
                'controller' => 'term',
                'action' => 'history',
            ]
        ));

        $this->front->getRouter()->addRoute(
            'contentprotection.outputMapping.nameCombo',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/contentprotection/outputmapping/namecombo',
                [
                    'module' => 'editor',
                    'controller' => 'contentprotectionoutputmapping',
                    /** @see \editor_ContentprotectionoutputmappingController::namecomboAction */
                    'action' => 'namecombo',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'contentprotection.contentrecognition.testformat',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/contentprotection/contentrecognition/testformat',
                [
                    'module' => 'editor',
                    'controller' => 'contentprotectioncontentrecognition',
                    /** @see \editor_ContentprotectioncontentrecognitionController::testformatAction */
                    'action' => 'testformat',
                ]
            )
        );

        $this->front->getRouter()->addRoute(
            'contentprotection.inputMapping.nameCombo',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/contentprotection/inputmapping/namecombo',
                [
                    'module' => 'editor',
                    'controller' => 'contentprotectioninputmapping',
                    /** @see \editor_ContentprotectioninputmappingController::namecomboAction */
                    'action' => 'namecombo',
                ]
            )
        );
        // special endpoint to provide configs for API-Tests. Must only be added when serving API-tests
        if (defined('APPLICATION_APITEST') && APPLICATION_APITEST) {
            $this->front->getRouter()->addRoute('editorConfigApiTest', new ZfExtended_Controller_RestLikeRoute(
                'editor/config/apitest',
                [
                    'module' => 'editor',
                    'controller' => 'config',
                    'action' => 'apitest',
                ]
            ));
        }
    }

    public function _initOtherRoutes()
    {
        $localizedJsRoute = new Zend_Controller_Router_Route(
            'editor/js/app-localized.js',
            [
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'localizedjsstrings',
            ]
        );
        $this->front->getRouter()->addRoute('editorLocalizedJs', $localizedJsRoute);

        $pluginJs = new Zend_Controller_Router_Route_Regex(
            'editor/plugins/([^/]+)/([a-z0-9_\-./]*)',
            [
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'pluginpublic',
            ]
        );
        $this->front->getRouter()->addRoute('editorPluginPublic', $pluginJs);
    }

    /**
     * Checks if the configured okapi instance is reachable
     */
    public function handleApplicationState(Zend_EventManager_Event $event)
    {
        $applicationState = $event->getParam('applicationState');
        $applicationState->tasks = new stdClass();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $applicationState->tasks->overview = $task->getSummary();

        $jobs = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $jobs editor_Models_TaskUserAssoc */
        $applicationState->tasks->jobs = $jobs->getSummary();
    }
}
