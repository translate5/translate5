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

use MittagQI\Translate5\PauseWorker\PauseWorker;
use MittagQI\Translate5\LanguageResource\Pretranslation\BatchCleanupWorker;
use MittagQI\Translate5\Plugins\MatchAnalysis\PauseMatchAnalysisProcessor;
use MittagQI\Translate5\Plugins\MatchAnalysis\PauseMatchAnalysisWorker;
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\Preset;

class editor_Plugins_MatchAnalysis_Init extends ZfExtended_Plugin_Abstract {
    protected static string $description = 'Provides the match-analysis and pre-translation against language-resources.';
    protected static bool $activateForTests = true;
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginMatchAnalysisMatchAnalysis' => 'Editor.plugins.MatchAnalysis.controller.MatchAnalysis',
        'pluginMatchAnalysisPricingPreset' => 'Editor.plugins.MatchAnalysis.controller.admin.PricingPreset'
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
    
    public function getFrontendControllers(): array
    {
        return $this->getFrontendControllersFromAcl();
    }
    
    /**
     * Initialize the plugn "Match Analysis"
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        $this->addController('MatchAnalysisController');
        $this->addController('PricingpresetController');
        $this->addController('PricingpresetrangeController');
        $this->addController('PricingpresetpricesController');
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
        $this->eventManager->attach('editor_TaskController', 'afterIndexAction', array($this, 'addPresetInfo'));
        $this->eventManager->attach('editor_TaskController', 'pretranslationOperation', array($this, 'handleOnPretranslationOperation'));
        $this->eventManager->attach('Editor_CronController', 'afterDailyAction', array($this, 'handleAfterDailyAction'));

        // Adds the pricingPresetId to the task-meta
        $this->eventManager->attach('editor_TaskController', 'beforeProcessUploadedFile', [$this, 'handleBeforeProcessUploadedFile']);

        $this->eventManager->attach('MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer', 'beforePivotPreTranslationQueue', array($this, 'handleBeforePivotPreTranslationQueue'));
        $this->eventManager->attach('Editor_CustomerController', 'afterIndexAction', [$this, 'handleCustomerAfterIndex']);

    }

    /**
     * Hook that adds the pricingPresetId sent by the Import wizard to the task-meta
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeProcessUploadedFile(Zend_EventManager_Event $event){

        /* @var $meta editor_Models_Task_Meta */
        $meta = $event->getParam('meta');

        /* @var $requestData array */
        $requestData = $event->getParam('data');

        // Get pricingPresetId: either given within request, or default for the customer, or system default
        $pricingPresetId = $requestData['pricingPresetId']
            ?? ZfExtended_Factory::get(Preset::class)
                ->getDefaultPresetId($requestData['customerId'] ?? null);

        // Save to meta
        $meta->setPricingPresetId($pricingPresetId);
    }

    /**
     * Add presetId and presetUnitType props to each row
     *
     * @param Zend_EventManager_Event $event
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function addPresetInfo(Zend_EventManager_Event $event) {

        /** @var editor_TaskController $controller */
        $controller = $event->getParam('controller');

        // If current request has projectsOnly-param - return
        if ($controller->hasParam('projectsOnly')) {
            return;
        }

        // If current request has no filter-param - return
        if (!$filter = $controller->getParam('filter')) {
            return;
        }

        // If there is no projectId-filter among current request's filter-param - return
        if (!in_array('projectId', array_column(json_decode($filter), 'property'))) {
            return;
        }

        // Get task and preset models
        $task = $event->getParam('entity');
        $preset = ZfExtended_Factory::get(Preset::class);

        // Foreach task inside the project
        foreach ($event->getParam('view')->rows as &$row) {

            // Load task
            $task->load($row['id']);

            // Load preset
            $presetId = $task->meta()->getPricingPresetId();
            $preset->load($presetId);

            // Add props
            $row['presetId'] = $preset->getId();
            $row['presetUnitType'] = $preset->getUnitType();
        }
    }

    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
        $config = $this->getConfig();
        $view->Php2JsVars()->set('plugins.MatchAnalysis.calculateBasedOn', $config->calculateBasedOn);

        // Add system default pricing preset info
        $pricing = ZfExtended_Factory::get(Preset::class);
        $view->Php2JsVars()->set('plugins.MatchAnalysis.pricing.systemDefaultPresetId', $pricing->getDefaultPresetId());
        $view->Php2JsVars()->set('plugins.MatchAnalysis.pricing.systemDefaultPresetName', Preset::PRESET_SYSDEFAULT_NAME);

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
        if(!isset($params['pretranslateMatchrate'])){
            $params['pretranslateMatchrate'] = $config->runtimeOptions->plugins->MatchAnalysis->pretranslateMatchRate;
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
            $taskmodel = ZfExtended_Factory::get(editor_Models_Task::class);
            $projects = $taskmodel->loadProjectTasks($task->getProjectId(), true);
            $taskGuids = array_column($projects, 'taskGuid');
        }

        // If it is a pretranslation operation - reset tbx hash, so that terminology will be refreshed
        if ($pretranslate){
            $task->meta()->resetTbxHash($taskGuids);
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

            $user = ZfExtended_Authentication::getInstance()->getUser();
            $workerParameters['userGuid'] = $user?->getUserGuid() ?? ZfExtended_Models_User::SYSTEM_GUID;

            $workerParameters['contentField'] = editor_Models_SegmentField::TYPE_RELAIS;

            if (!$batchWorker->init($task->getTaskGuid(), $workerParameters)) {
                //we log that fact, queue nothing and rely on the normal match analysis processing
                $this->addWarn($task,'MatchAnalysis-Error on batchWorker init(). Batch worker for pivot pre-translation could not be initialized');
                return;
            }

            //we may not trigger the queue here, just add the workers!
            $workerId = $batchWorker->queue($parentWorkerId, null, false);

            $this->queueBatchCleanUpWorker($workerId,$task->getTaskGuid());
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
    protected function queueAnalysis(string $taskGuid, array $workerParameters): bool
    {
        if (!$this->hasAssoc($taskGuid)) {
            //you can not run analysis without resources to be associated to the task
            return false;
        }

        $valid = $this->checkLanguageResources($taskGuid);

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        // lock the task dedicated for analysis
        if ($task->lock(NOW_ISO, editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS)) {
            // else check if we are in import, then no separate lock is needed. Therefore if we are not in import this is an error
        } else if ($task->getState() != editor_Models_Task::STATE_IMPORT) {
            $this->addWarn($task, 'MatchAnalysis Plug-In: task can not be locked for analysis and pre-translation.');

            return false;
        }

        try {
            $queued = $this->finishQueueAnalysis($task, $valid, $workerParameters);

            if (!$queued) {
                $task->unlock();
            }

            return $queued;
        } catch (Throwable $e) {
            $task->unlock();

            throw $e;
        }
    }

    private function finishQueueAnalysis(editor_Models_Task $task, array $valid, array $workerParameters): bool
    {
        if ($task->isImporting()) {
            //on import we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
        } else {
            // crucial: add a different behaviour for the workers when performig an operation
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
            // this creates the operation start/finish workers
            $parentWorkerId = editor_Task_Operation::create(editor_Task_Operation::MATCHANALYSIS, $task);
        }

        if (empty($valid)) {
            $this->addWarn($task, 'MatchAnalysis Plug-In: No valid analysable language resources found.', ['invalid' => print_r($this->assocs, 1)]);
            return false;
        }

        $user = new Zend_Session_Namespace('user');
        $workerParameters['userGuid'] = $user->data->userGuid;
        $workerParameters['userName'] = $user->data->userName;

        //enable batch query via config
        $workerParameters['batchQuery'] = (boolean)Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
        if (!empty($this->batchAssocs) && $workerParameters['batchQuery']) {
            $this->queueBatchWorkers($task, $workerParameters, $parentWorkerId);
        }

        $worker = ZfExtended_Factory::get(PauseMatchAnalysisWorker::class);
        $worker->init($task->getTaskGuid(), [PauseWorker::PROCESSOR => PauseMatchAnalysisProcessor::class]);
        $worker->queue($parentWorkerId);

        // init worker and queue it
        $worker = ZfExtended_Factory::get(editor_Plugins_MatchAnalysis_Worker::class);

        if (!$worker->init($task->getTaskGuid(), $workerParameters)) {
            $this->addWarn($task, 'MatchAnalysis-Error on worker init(). Worker could not be initialized');
            return false;
        }

        $worker->queue($parentWorkerId, null, false);

        // if we are not importing we need to add the quality workers (which also include the termtagger)
        if (!$task->isImporting()) {
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
            $workerId = $batchWorker->queue($parentWorkerId, null, false);

            $this->queueBatchCleanUpWorker($workerId,$task->getTaskGuid());
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
     * Queue batch cleanup worker. This will clean the batch results after the batch results are used.
     *
     * @param int $parent
     * @param string $taskGuid
     * @return void
     */
    protected function queueBatchCleanUpWorker(int $parent, string $taskGuid): void
    {
        /** @var BatchCleanupWorker $batchCleanupWorker */
        $batchCleanupWorker = ZfExtended_Factory::get(BatchCleanupWorker::class);

        if (!$batchCleanupWorker->init($taskGuid, ['taskGuid' => $taskGuid])) {

            /** @var editor_Models_Task $task */
            $task = ZfExtended_Factory::get('editor_Models_Task');
            $task->loadByTaskGuid($taskGuid);

            //we log that fact, queue nothing and rely on the normal match analysis processing
            $this->addWarn($task,'MatchAnalysis-Error on BatchCleanupWorker init(). BatchCleanupWorker can not be initialized');
            return;
        }

        //we may not trigger the queue here, just add the workers!
        $batchCleanupWorker->queue($parent, null, false);
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

        $r->addRoute('plugins_matchanalysis_restdefault', new Zend_Rest_Route($f, [], [
            'editor' => [
                'plugins_matchanalysis_matchanalysis',
                'plugins_matchanalysis_pricingpreset',
                'plugins_matchanalysis_pricingpresetprices',
                'plugins_matchanalysis_pricingpresetrange',
            ],
        ]));

        $exportAnalysis = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_matchanalysis_matchanalysis/export',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_matchanalysis_matchanalysis',
                        'action' => 'export'
                ));
        $r->addRoute('plugins_matchanalysis_export', $exportAnalysis);

        $r->addRoute('presetClone', new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchanalysis_pricingpreset/clone/*', [
            'module' => 'editor',
            'controller' => 'plugins_matchanalysis_pricingpreset',
            'action' => 'clone'
        ]));

        $r->addRoute('presetDefault', new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchanalysis_pricingpreset/setdefault/*', [
            'module' => 'editor',
            'controller' => 'plugins_matchanalysis_pricingpreset',
            'action' => 'setdefault'
        ]));

        $r->addRoute('presetpricesClone', new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchanalysis_pricingpresetprices/clone/*', [
            'module' => 'editor',
            'controller' => 'plugins_matchanalysis_pricingpresetprices',
            'action' => 'clone'
        ]));
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

    /**
     * @param Zend_EventManager_Event $event
     * @see ZfExtended_RestController::afterActionEvent
     */
    public function handleCustomerAfterIndex(Zend_EventManager_Event $event) {
        $meta = ZfExtended_Factory::get(editor_Models_Db_CustomerMeta::class);
        $metas = $meta->fetchAll('defaultPricingPresetId IS NOT NULL')->toArray();
        $pricingPresetIds = array_column($metas, 'defaultPricingPresetId', 'customerId');
        foreach ($event->getParam('view')->rows as &$customer) {
            if (array_key_exists($customer['id'], $pricingPresetIds)) {
                $customer['defaultPricingPresetId'] = (int) $pricingPresetIds[$customer['id']];
            } else {
                $customer['defaultPricingPresetId'] = null;
            }
        }
    }
}
