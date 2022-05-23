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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Klasse zur Portalinitialisierung
 *
 * - In initApplication können Dinge zur Portalinitialisierung aufgerufen werden
 * - Alles für das Portal nötige ist jedoch in Resource-Plugins ausgelagert und
 *   wird über die application.ini definiert und dann über Zend_Application
 *   automatisch initialisert
 *
 */
class Editor_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected $front;

    public function __construct($application) {
        parent::__construct($application);
        
        //Binding the worker clean up to the after import event, since import
        // is currently the main use case for workers
        /** @var Zend_EventManager_StaticEventManager $eventManager */
        $eventManager = Zend_EventManager_StaticEventManager::getInstance();
        
        $eventManager->attach('editor_Models_Import', 'afterImport', function(){
            $worker = ZfExtended_Factory::get('ZfExtended_Worker_GarbageCleaner');
            /* @var $worker ZfExtended_Worker_GarbageCleaner */
            $worker->init();
            $worker->queue(); // not parent ID here, since the GarbageCleaner should run without a parent relation
        }, 0);
        
        $cleanUp = function(){
            // first clean up jobs
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */
            $tua->cleanupLocked();

            // second clean up tasks, jobs must be before in order to clean also not used multiuser tasks anymore
            /** @var editor_Models_Task $task */
            $task = ZfExtended_Factory::get('editor_Models_Task');
            $task->cleanupLockedJobs();

            //clean up dangling (hanging) imports
            $import = ZfExtended_Factory::get('editor_Models_Import');
            /** @var editor_Models_Import $import */
            $import->cleanupDanglingImports();

            $config = ZfExtended_Factory::get('editor_Models_UserConfig');
            /* @var $config editor_Models_UserConfig */
            $config->cleanUpThemeTemporary();
        };
        
        $eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', $cleanUp);
        $eventManager->attach('LoginController', 'afterLogoutAction', $cleanUp);
        $eventManager->attach('editor_SessionController', 'afterDeleteAction', $cleanUp);
        $eventManager->attach('ZfExtended_Session', 'afterSessionCleanForUser', $cleanUp);
        $eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'handleApplicationState'));
    }
    
    
    public function _initController()
    {
        $this->front = Zend_Controller_Front::getInstance();
    }
    
    
    public function _initREST()
    {
        $this->front->setRequest(new REST_Controller_Request_Http);

        // register the RestHandler plugin
        $this->front->registerPlugin(new ZfExtended_Controllers_Plugins_RegisterRestControllerPluginRestHandler());

        // add REST contextSwitch helper
        $contextSwitch = new REST_Controller_Action_Helper_ContextSwitch();
        Zend_Controller_Action_HelperBroker::addHelper($contextSwitch);

        // add restContexts helper
        $restContexts = new REST_Controller_Action_Helper_RestContexts();
        Zend_Controller_Action_HelperBroker::addHelper($restContexts);
    }
    
    
    public function _initRestRoutes()
    {
        
        $restRoute = new Zend_Rest_Route($this->front, array(), array(
            'editor' => [
                'file', 'segment', 'alikesegment', 'customer', 'referencefile', 'comment', 'attributedatatype',
                'task', 'user', 'taskuserassoc', 'segmentfield', 'workflowuserpref', 'worker','taskmeta',
                'config', 'segmentuserassoc', 'session', 'language','termcollection',
                'languageresourceresource','languageresourcetaskassoc',
                'languageresourceinstance','taskusertracking', 'term', 'attribute', 'termattribute', 'category',
                'quality','userassocdefault', 'log'
            ],
        ));
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
            array(
                'module' => 'editor',
                'action' => '',
            )
        ));
        $this->front->getRouter()->addRoute('editorBatchHandler', new ZfExtended_Controller_RestLikeRoute(
            'editor/:controller/:operation/batch',
            array(
                'module' => 'editor',
                'action' => '',
            )
        ));
        
        //FIXME convert to RestLikeRoute (remove echo json_encode in action then)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/filemap/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'filemap'
            ));
        $this->front->getRouter()->addRoute('editorFilemap', $filemapRoute);
        
        //must be added before the default RestRoutes
        $this->front->getRouter()->addRoute('editorSegmentPosition', new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/:segmentNrInTask/position',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'position'
            )));
        
        $this->front->getRouter()->addRoute('editorTaskTriggerWorkflow', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/workflow',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'workflow'
            )
        ));
        
        $this->front->getRouter()->addRoute('editorTaskClone', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/clone',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'clone'
            )
        ));
        
        $this->front->getRouter()->addRoute('editorTaskEvents', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/events',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'events'
            )
        ));
        
        $this->front->getRouter()->addRoute('editorTaskImport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/import',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'import'
            )
        ));
        
        // Excel Ex- + Reimport
        $this->front->getRouter()->addRoute('editorTaskExcelExport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/excelexport',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'excelexport'
            )
        ));
        $this->front->getRouter()->addRoute('editorTaskExcelReimport', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/excelreimport',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'excelreimport'
            )
        ));
        $this->front->getRouter()->addRoute('editorTaskUserlist', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/userlist',[
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'userlist'
        ]));
        
        $this->front->getRouter()->addRoute('editorTaskPosition', new ZfExtended_Controller_RestLikeRoute(
            'editor/task/:id/position',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'position'
        )));

        $this->front->getRouter()->addRoute('editorTaskUserAssocProject', new ZfExtended_Controller_RestLikeRoute(
            'editor/taskuserassoc/project',
            array(
                'module' => 'editor',
                'controller' => 'taskuserassoc',
                'action' => 'project'
        )));

        $this->front->getRouter()->addRoute('editorTaskCommentNav', new ZfExtended_Controller_RestLikeRoute(
            'editor/commentnav',[
                'module' => 'editor',
                'controller' => 'commentnav',
                'action' => 'index'
            ]));

        //FIXME convert me to RestLikeRoute (see filemap)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/nextsegments/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'nextsegments'
            ));
        $this->front->getRouter()->addRoute('editorNextSegments', $filemapRoute);

        //FIXME convert me to RestLikeRoute (see filemap)
        $filemapRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/matchratetypes/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'matchratetypes'
            ));
        $this->front->getRouter()->addRoute('editorMatchratetypes', $filemapRoute);

        $searchRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/segment/search/*',
                array(
                        'module' => 'editor',
                        'controller' => 'segment',
                        'action' => 'search'
                ));
        $this->front->getRouter()->addRoute('editorSearchSegment', $searchRoute);
        
        $replaceAllRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/segment/replaceall/*',
                array(
                        'module' => 'editor',
                        'controller' => 'segment',
                        'action' => 'replaceall'
                ));
        $this->front->getRouter()->addRoute('editorReplaceallSegment', $replaceAllRoute);
        
        $replaceAllRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/segment/stateid/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'stateid'
            ));
        $this->front->getRouter()->addRoute('editorSegmentStateId', $replaceAllRoute);

        $authUserRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/user/authenticated/*',
            array(
                'module' => 'editor',
                'controller' => 'user',
                'action' => 'authenticated'
            ));
        $this->front->getRouter()->addRoute('editorAuthUser', $authUserRoute);

        $pmRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/user/pm/*',
                array(
                        'module' => 'editor',
                        'controller' => 'user',
                        'action' => 'pm'
                ));
        $this->front->getRouter()->addRoute('editorUserPm', $pmRoute);
        
        $termsRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/segment/terms/*',
            array(
                'module' => 'editor',
                'controller' => 'segment',
                'action' => 'terms'
            ));
        $this->front->getRouter()->addRoute('editorTerms', $termsRoute);

        $exportRoute = new ZfExtended_Controller_RestFakeRoute(
            'editor/task/export/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'export'
            ));
        $this->front->getRouter()->addRoute('editorExport', $exportRoute);
        
        $taskStat = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/statistics/*',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'statistics'
            ));
        $this->front->getRouter()->addRoute('editorTaskStat', $taskStat);
        
        $taskKpi = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/kpi',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'kpi'
            ));
        $this->front->getRouter()->addRoute('editorTaskKpi', $taskKpi);
        
        $importprogress = new ZfExtended_Controller_RestLikeRoute(
            'editor/task/importprogress',
            array(
                'module' => 'editor',
                'controller' => 'task',
                'action' => 'importprogress'
            ));
        $this->front->getRouter()->addRoute('editorTaskImportprogress', $importprogress);
        
        $workerRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/worker/queue/*',
            array(
                'module' => 'editor',
                'controller' => 'worker',
                'action' => 'queue'
            ));
        $this->front->getRouter()->addRoute('editorQueue', $workerRoute);
        
        $termCollectionImport = new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/import/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'import'
                ));
        $this->front->getRouter()->addRoute('termCollectionImport', $termCollectionImport);
        
        $termCollectionExport = new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/export/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'export'
                ));
        $this->front->getRouter()->addRoute('termCollectionExport', $termCollectionExport);
        
        
        $getCollectionAttributes= new ZfExtended_Controller_RestLikeRoute(
                'editor/termcollection/testgetattributes/*',
                array(
                        'module' => 'editor',
                        'controller' => 'termcollection',
                        'action' => 'testgetattributes'
                ));
        $this->front->getRouter()->addRoute('testgetattributes', $getCollectionAttributes);

        $searchTermExists = new ZfExtended_Controller_RestLikeRoute(
            'editor/termcollection/searchtermexists/*',
            array(
                'module' => 'editor',
                'controller' => 'termcollection',
                'action' => 'searchtermexists'
            ));
        $this->front->getRouter()->addRoute('searchtermexists', $searchTermExists);
        
        
        $lastusedapp = new ZfExtended_Controller_RestLikeRoute(
            'editor/apps/lastusedapp/*',
            array(
                'module' => 'editor',
                'controller' => 'apps',
                'action' => 'lastusedapp'
            ));
        $this->front->getRouter()->addRoute('lastusedapp', $lastusedapp);
        
        # Language resources rutes start
        //WARNING: Order of the route definition is important!
        // the catchall like download route must be defined before the more specific query/search routes!
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/:type',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'download'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_download', $queryRoute);

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:languageResourceId/query',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'query'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_query', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:languageResourceId/search',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'search'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_search', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/import',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'import'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_import', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/tasks',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'tasks'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_tasks', $queryRoute);
        

        $this->front->getRouter()->addRoute('editorLanguageResourcesEvents', new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/:id/events',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'events'
            )
        ));

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/export',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'export'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_export', $queryRoute);

        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_tbxexport', new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/tbxexport',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'tbxexport'
            )
        ));

        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_xlsxexport', new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/xlsxexport',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'xlsxexport'
            )
        ));

        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/languageresourceinstance/testexport',
            array(
                'module' => 'editor',
                'controller' => 'languageresourceinstance',
                'action' => 'testexport'
            ));
        $this->front->getRouter()->addRoute('languageresources_languageresourceinstance_testexport', $queryRoute);
        #Language resource rutes end
        
        
        $customerResourceExport = new ZfExtended_Controller_RestLikeRoute(
            'editor/customer/exportresource',
            array(
                'module' => 'editor',
                'controller' => 'customer',
                'action' => 'exportresource'
            ));
        $this->front->getRouter()->addRoute('customer_resourceexport', $customerResourceExport);

        $sessionImpersonate = new ZfExtended_Controller_RestLikeRoute(
            'editor/session/impersonate',
            array(
                'module' => 'editor',
                'controller' => 'session',
                'action' => 'impersonate'
            ));
        $this->front->getRouter()->addRoute('editorSessionImpersonate', $sessionImpersonate);
        
        // quality subroutes
        $this->front->getRouter()->addRoute(
            'editorQualityDownloadStatistics',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/downloadstatistics/*',
                array(
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'downloadstatistics'
                )));
        $this->front->getRouter()->addRoute(
            'editorSegmentQuality',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/segment/*',
                array(
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'segment'
                )));
        $this->front->getRouter()->addRoute(
            'editorTaskQuality',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/task/*',
                array(
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'task'
                )));
        $this->front->getRouter()->addRoute(
            'editorTaskQualityTooltip',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/tasktooltip/*',
                array(
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'tasktooltip'
                )));
        $this->front->getRouter()->addRoute(
            'editorQualityFalsepositive',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/falsepositive/*',
                array(
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'falsepositive'
                )));
        $this->front->getRouter()->addRoute(
            'editorQualitySegmentQm',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/quality/segmentqm/*',
                array(
                    'module' => 'editor',
                    'controller' => 'quality',
                    'action' => 'segmentqm'
                )));
    }
    
    
    public function _initOtherRoutes()
    {
        $localizedJsRoute = new Zend_Controller_Router_Route(
            'editor/js/app-localized.js',
            array(
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'localizedjsstrings',
            ));
        $this->front->getRouter()->addRoute('editorLocalizedJs', $localizedJsRoute);
        
        $pluginJs = new Zend_Controller_Router_Route_Regex(
            'editor/plugins/([^/]+)/([a-z0-9_\-./]*)',
            array(
                'module' => 'editor',
                'controller' => 'index',
                'action' => 'pluginpublic'
            ));
        $this->front->getRouter()->addRoute('editorPluginPublic', $pluginJs);
    }
    
    /**
     * Checks if the configured okapi instance is reachable
     * @param Zend_EventManager_Event $event
     */
    public function handleApplicationState(Zend_EventManager_Event $event) {
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