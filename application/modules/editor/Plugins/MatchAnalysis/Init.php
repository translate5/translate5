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

class editor_Plugins_MatchAnalysis_Init extends ZfExtended_Plugin_Abstract {
    protected static $description = 'Provides the match-analysis and pre-translation against language-resources.';
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginMatchAnalysisMatchAnalysis' => 'Editor.plugins.MatchAnalysis.controller.MatchAnalysis'
    );
    
    protected $localePath = 'locales';
    
    /***
     *
     * @var array
     */
    protected $assocs = [];
    
    /***
     * Langauge resources which are supporting batch query
     * @var editor_Models_LanguageResources_LanguageResource[]
     */
    protected $batchAssocs = [];
    
    public function getFrontendControllers() {
        $result = array();
        $userSession = new Zend_Session_Namespace('user');
        if(empty($userSession) || empty($userSession->data)) {
            return $result;
        }
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if(!$acl->has('frontend')) {
            return $result;
        }
        foreach($this->frontendControllers as $right => $controller) {
            if($acl->isInAllowedRoles($userSession->data->roles, 'frontend', $right)) {
                $result[] = $controller;
            }
        }
        return $result;
    }
    
    /**
     * Initialize the plugn "Match Analysis"
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        $this->addController('MatchAnalysisController');
        $this->initEvents();
        $this->initRoutes();
    }
    
    /**
     * define all event listener
     */
    protected function initEvents() {
        //$this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleOnAfterImport'));
        //$this->eventManager->attach('Editor_SegmentController', 'afterPutAction', array($this, 'startTestCode'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        
        $this->eventManager->attach('editor_TaskController', 'analysisOperation', array($this, 'handleOnAnalysisOperation'));
        $this->eventManager->attach('editor_TaskController', 'pretranslationOperation', array($this, 'handleOnPretranslationOperation'));
        $this->eventManager->attach('Editor_CronController', 'afterDailyAction', array($this, 'handleAfterDailyAction'));

        $this->eventManager->attach('editor_LanguageresourcetaskpivotassocController', 'beforePivotPreTranslationQueue', array($this, 'handleBeforePivotPreTranslationQueue'));

    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
        $config = $this->getConfig();
        $view->Php2JsVars()->set('plugins.MatchAnalysis.calculateBasedOn', $config->calculateBasedOn);
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    public function handleOnAnalysisOperation(Zend_EventManager_Event $event){
        //if the task is in import state -> queue the worker, do not pretranslate
        //if the task is allready imported -> run the analysis directly, do not pretranslate
        $this->handleOperation($event, false);
    }
    
    public function handleOnPretranslationOperation(Zend_EventManager_Event $event){
        //if the task is in import state -> queue the worker, set pretranslate to true in the worker and from the worker in the analysis
        //if the task is allready imported -> run the analysis directly, set pretranslate to true
        $this->handleOperation($event, true);
    }
    
    /***
     * Cron controller daily action
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterDailyAction(Zend_EventManager_Event $event){
        $batchCache = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\Pretranslation\BatchResult');
        /* @var $batchCache MittagQI\Translate5\LanguageResource\Pretranslation\BatchResult */
        $batchCache->deleteOlderRecords();
    }

    /***
     * Operation action handler. Run analysis and pretranslate if $pretranslate is true.
     *
     * @param Zend_EventManager_Event $event
     * @param bool $pretranslate
     * @throws editor_Models_ConfigException
     */
    protected function handleOperation(Zend_EventManager_Event $event, bool $pretranslate){
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task */
        $params = $event->getParam('params');

        $config = $task->getConfig();

        // set the defaults from config if not provided as params
        if(!isset($params['internalFuzzy'])){
            $params['internalFuzzy'] = $config->runtimeOptions->plugins->MatchAnalysis->internalFuzzyDefault;
        }
        if(!isset($params['pretranslateTmAndTerm'])){
            $params['pretranslateTmAndTerm'] = $config->runtimeOptions->plugins->MatchAnalysis->pretranslateTmAndTermDefault;
        }
        if(!isset($params['pretranslateMt'])){
            $params['pretranslateMt'] = $config->runtimeOptions->plugins->MatchAnalysis->pretranslateMtDefault;
        }

        settype($params['internalFuzzy'], 'boolean');
        settype($params['pretranslateTmAndTerm'], 'boolean');
        settype($params['pretranslateMt'], 'boolean');
        settype($params['pretranslateMatchrate'], 'integer');
        settype($params['isTaskImport'], 'boolean');
        
        $params['pretranslate'] = $pretranslate;
        
        $taskGuids = [$task->getTaskGuid()];
        //if the requested operation is from project, queue analysis for each project task
        if($task->isProject()){
            $projects = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $projects editor_Models_Task */
            $projects = $projects->loadProjectTasks($task->getProjectId(), true);
            $taskGuids = array_column($projects, 'taskGuid');
        }
        
        foreach ($taskGuids as $taskGuid){
            $this->queueAnalysis($taskGuid, $params);
        }
        
        $wq = ZfExtended_Factory::get('ZfExtended_Worker_Queue');
        /* @var $wq ZfExtended_Worker_Queue */
        $wq->trigger();
    }

    /***
     * Add batch query worker for the resources used for pivot pre-translation before the pivot worker is queued
     * @param Zend_EventManager_Event $event
     * @return void
     */
    public function handleBeforePivotPreTranslationQueue(Zend_EventManager_Event $event): void
    {
        $task = $event->getParam('task');
        $pivotAssociations = $event->getParam('pivotAssociations');
        $parentWorkerId = $event->getParam('parentWorkerId');

        $batchResources = $this->getAvailableBatchResourceForPivotPreTranslation($task->getTaskGuid(),$pivotAssociations);
        if(!empty($batchResources)){
            $this->queueBatchWorkersForPivot($task , $parentWorkerId,$batchResources);
        }

    }

    /**
     * For each batch supported connector queue one batch worker
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     * @param array $batchResources
     */
    protected function queueBatchWorkersForPivot(editor_Models_Task $task, int $parentWorkerId, array $batchResources)
    {
        $workerParameters = [];
        if(!$task->isImporting()) {
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
        }
        foreach ($batchResources as $languageRessource){
            /* @var editor_Models_LanguageResources_LanguageResource $languageRessource */
            $batchWorker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_BatchWorker');
            /* @var $batchWorker editor_Plugins_MatchAnalysis_BatchWorker */

            $workerParameters['languageResourceId'] = $languageRessource->getId();
            $workerParameters['userGuid'] = editor_User::instance()->getGuid();

            $workerParameters['contentField'] = editor_Models_SegmentField::TYPE_RELAIS;

            if (!$batchWorker->init($task->getTaskGuid(), $workerParameters)) {
                //we log that fact, queue nothing and rely on the normal match analysis processing
                $this->addWarn($task,'MatchAnalysis-Error on batchWorker init(). Batch worker for pivot pre-translation could not be initialized');
                return;
            }

            //we may not trigger the queue here, just add the workers!
            $batchWorker->queue($parentWorkerId, null, false);
        }
    }

    /***
     * Check if the given pivot associations can be used for batch pre-translation
     * @param string $taskGuid
     * @return array
     */
    protected function getAvailableBatchResourceForPivotPreTranslation(string $taskGuid,array $assocs): array
    {

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        $batchAssocs = [];
        foreach ($assocs as $assoc){
            $languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageresource editor_Models_LanguageResources_LanguageResource  */

            $languageresource->load($assoc['languageResourceId']);

            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */

            $connector = $manager->getConnector($languageresource, $task->getSourceLang(), $task->getRelaisLang(), $task->getConfig());
            /* @var $connector editor_Services_Connector */
            //collect all connectors which are supporting batch query
            if($connector->isBatchQuery()){
                $batchAssocs[] = clone $languageresource;
            }

        }
        return $batchAssocs;
    }

    /***
     * Queue the match analysis worker
     *
     * @param string $taskGuid
     * @param array $workerParameters
     * @return boolean
     */
    protected function queueAnalysis(string $taskGuid, array $workerParameters) : bool {
        if(!$this->hasAssoc($taskGuid)){
            //you can not run analysis without resources to be associated to the task
            return false;
        }
        
        $valid = $this->checkLanguageResources($taskGuid);
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        if($task->isImporting()) {
            //on import we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
        } else {
            // crucial: add a different behaviour for the workers when performig an operation
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
            // this creates the operation start/finish workers
            $parentWorkerId = editor_Task_Operation::create(editor_Task_Operation::MATCHANALYSIS, $task);
        }
        
        if(empty($valid)){
            $this->addWarn($task,'MatchAnalysis Plug-In: No valid analysable language resources found.', ['invalid' => print_r($this->assocs, 1)]);
            return false;
        }
        
        $user = new Zend_Session_Namespace('user');
        $workerParameters['userGuid'] = $user->data->userGuid;
        $workerParameters['userName'] = $user->data->userName;

        //enable batch query via config
        $workerParameters['batchQuery'] = (boolean) Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
        if(!empty($this->batchAssocs) && $workerParameters['batchQuery']){
            $this->queueBatchWorkers($task, $workerParameters, $parentWorkerId);
        }
        // init worker and queue it
        $worker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Worker');
        /* @var $worker editor_Plugins_MatchAnalysis_Worker */
        if (!$worker->init($taskGuid, $workerParameters)) {
            $this->addWarn($task,'MatchAnalysis-Error on worker init(). Worker could not be initialized');
            return false;
        }
        $worker->queue($parentWorkerId, null, false);
        
        // if we are not importing we need to add the quality workers (which also include the termtagger)
        if(!$task->isImporting()){
            editor_Segment_Quality_Manager::instance()->queueOperation(editor_Segment_Processing::ANALYSIS, $task, $parentWorkerId);
        }
        
        return true;
    }
    
    /**
     * For each batch supported connector queue one batch worker
     * @param editor_Models_Task $task
     * @param array $eventParams
     */
    protected function queueBatchWorkers(editor_Models_Task $task, array $eventParams, int $parentWorkerId)
    {
        $isPretranslateMt = (boolean) $eventParams['pretranslateMt'];
        $isPretranslate = (boolean) $eventParams['pretranslate'];
        $workerParameters = [];
        if(!$task->isImporting()) {
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
        }
        foreach ($this->batchAssocs as $languageRessource){
            /* @var $lr editor_Models_LanguageResources_LanguageResource */
            $batchWorker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_BatchWorker');
            /* @var $batchWorker editor_Plugins_MatchAnalysis_BatchWorker */
            
            //do not use this resource when it is a mt and the pretranslateMt is disabled
            //if the pretranslation is disabled(the current request is for analysis only),
            //do not use this resource for batch query. For analysis only, the results from the resource
            //are not relevant, since always the same matchrate is received
            if((!$isPretranslateMt || !$isPretranslate) && $languageRessource->isMt()){
                continue;
            }
            
            $workerParameters['languageResourceId'] = $languageRessource->getId();
            $workerParameters['userGuid'] = $eventParams['userGuid'];
            
            if (!$batchWorker->init($task->getTaskGuid(), $workerParameters)) {
                //we log that fact, queue nothing and rely on the normal match analysis processing
                $this->addWarn($task,'MatchAnalysis-Error on batchWorker init(). Worker could not be initialized');
                return;
            }
            
            //we may not trigger the queue here, just add the workers!
            $batchWorker->queue($parentWorkerId, null, false);
        }
    }
    /**
     *
     * @param string $taskGuid
     * @return NULL|int
     */
    private function fetchImportWorkerId(string $taskGuid): ?int
    {
        $parent = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result = $parent->loadByState(ZfExtended_Models_Worker::STATE_PREPARE, 'editor_Models_Import_Worker', $taskGuid);
        if(count($result) > 0){
            return $result[0]['id'];
        }
        return 0;
    }
    /***
     * Check if the given task has associated language resources
     * @param string $taskGuid
     * @return boolean
     */
    protected function hasAssoc(string $taskGuid){
        $languageResources = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        $this->assocs = $languageResources->loadByAssociatedTaskGuid($taskGuid);
        return !empty($this->assocs);
    }
    
    /***
     * Check if the current associated language resources can be used for analysis
     * @param string $taskGuid
     * @return array
     */
    protected function checkLanguageResources(string $taskGuid){
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $this->resetBatchAssocs();
        
        $valid=[];
        foreach ($this->assocs as $assoc){
            $languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageresource editor_Models_LanguageResources_LanguageResource  */
            
            $languageresource->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $resource = $manager->getResource($languageresource);
            
            $connector = $manager->getConnector($languageresource, $task->getSourceLang(), $task->getTargetLang(), $task->getConfig());
            /* @var $connector editor_Services_Connector */
            //collect all connectors which are supporting batch query
            if($connector->isBatchQuery()){
                $this->batchAssocs[] = clone $languageresource;
            }
            
            //analysable language resource is found
            if(!empty($resource) && $resource->getAnalysable()){
                $valid[] = $assoc;
            }
            
        }
        return $valid;
    }
    
    /***
     * Reset the collected batch language resources
     */
    protected function resetBatchAssocs() {
        $this->batchAssocs = [];
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_matchanalysis_matchanalysis',
                ),
        ));
        $r->addRoute('plugins_matchanalysis_restdefault', $restRoute);
        
        $exportAnalysis = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_matchanalysis_matchanalysis/export',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_matchanalysis_matchanalysis',
                        'action' => 'export'
                ));
        $r->addRoute('plugins_matchanalysis_export', $exportAnalysis);
    }
    
    /***
     * Log analysis warning
     * @param string $taskGuid
     * @param array $extra
     * @param string $message
     */
    protected function addWarn(editor_Models_Task $task,string $message,array $extra=[]) {
        $extra['task']=$task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100',$message,$extra);
    }
}
