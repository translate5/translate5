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
use MittagQI\Translate5\Cronjob\CronEventTrigger;
use MittagQI\Translate5\LanguageResource\Pretranslation\BatchCleanupWorker;
use MittagQI\Translate5\LanguageResource\Pretranslation\BatchResult;
use MittagQI\Translate5\PauseWorker\PauseWorker;
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\Preset;
use MittagQI\Translate5\Plugins\MatchAnalysis\PauseMatchAnalysisProcessor;
use MittagQI\Translate5\Plugins\MatchAnalysis\PauseMatchAnalysisWorker;
use MittagQI\Translate5\Task\Import\ImportEventTrigger;
use MittagQI\ZfExtended\Worker\Queue;

class editor_Plugins_MatchAnalysis_Init extends ZfExtended_Plugin_Abstract
{
    protected static string $description = 'Provides the match-analysis and pre-translation against language-resources.';

    protected static bool $enabledByDefault = true;

    protected static bool $activateForTests = true;

    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = [
        Rights::PLUGIN_MATCH_ANALYSIS_MATCH_ANALYSIS => 'Editor.plugins.MatchAnalysis.controller.MatchAnalysis',
        Rights::PLUGIN_MATCH_ANALYSIS_PRICING_PRESET => 'Editor.plugins.MatchAnalysis.controller.admin.PricingPreset',
        Rights::PLUGIN_MATCH_ANALYSIS_CUSTOMER_PRICING_PRESET => 'Editor.plugins.MatchAnalysis.controller.admin.PricingPreset',
    ];

    protected $localePath = 'locales';

    protected array $assocs = [];

    /**
     * Langauge resources which are supporting batch query
     * @var editor_Models_LanguageResources_LanguageResource[]
     */
    protected array $batchAssocs = [];

    /**
     * @throws Zend_Acl_Exception
     */
    public function getFrontendControllers(): array
    {
        return $this->getFrontendControllersFromAcl();
    }

    /**
     * Initialize the plugn "Match Analysis"
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init()
    {
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
    protected function initEvents(): void
    {
        $this->eventManager->attach(
            Editor_IndexController::class,
            'afterLocalizedjsstringsAction',
            [$this, 'initJsTranslations']
        );
        $this->eventManager->attach(Editor_IndexController::class, 'afterIndexAction', [$this, 'injectFrontendConfig']);

        $this->eventManager->attach(editor_TaskController::class, 'afterIndexAction', [$this, 'addPresetInfo']);
        $this->eventManager->attach(
            editor_TaskController::class,
            'analysisOperation',
            [$this, 'handleOnAnalysisOperation']
        );
        $this->eventManager->attach(
            editor_TaskController::class,
            'pretranslationOperation',
            [$this, 'handleOnPretranslationOperation']
        );

        $this->eventManager->attach(
            CronEventTrigger::class,
            CronEventTrigger::DAILY,
            [$this, 'handleAfterDailyAction']
        );

        $this->eventManager->attach(
            'MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer',
            'beforePivotPreTranslationQueue',
            [$this, 'handleBeforePivotPreTranslationQueue']
        );

        // Adds the pricingPresetId to the task-meta
        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::INIT_TASK_META,
            [$this, 'handleInitTaskMeta']
        );

        $this->eventManager->attach(
            Editor_CustomerController::class,
            'afterIndexAction',
            [$this, 'handleCustomerAfterIndex']
        );

        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::IMPORT_WORKER_STARTED,
            [$this, 'handleImportWorkerQueued']
        );
    }

    /**
     * Hook that adds the pricingPresetId sent by the Import wizard to the task-meta
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function handleInitTaskMeta(Zend_EventManager_Event $event): void
    {
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
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function addPresetInfo(Zend_EventManager_Event $event): void
    {
        /** @var editor_TaskController $controller */
        $controller = $event->getParam('controller');

        // If current request has projectsOnly-param - return
        if ($controller->hasParam('projectsOnly')) {
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

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ReflectionException
     */
    public function injectFrontendConfig(Zend_EventManager_Event $event): void
    {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
        $config = $this->getConfig();
        $view->Php2JsVars()->set('plugins.MatchAnalysis.calculateBasedOn', $config->calculateBasedOn);

        // Add system default pricing preset info
        $pricing = ZfExtended_Factory::get(Preset::class);
        $view->Php2JsVars()->set('plugins.MatchAnalysis.pricing.systemDefaultPresetId', $pricing->getDefaultPresetId());
        $view->Php2JsVars()->set(
            'plugins.MatchAnalysis.pricing.systemDefaultPresetName',
            Preset::PRESET_SYSDEFAULT_NAME
        );
    }

    public function initJsTranslations(Zend_EventManager_Event $event): void
    {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     * @throws ZfExtended_Exception
     * @throws Throwable
     * @throws ReflectionException
     * @throws editor_Task_Operation_Exception
     */
    public function handleOnAnalysisOperation(Zend_EventManager_Event $event): void
    {
        //if the task is in import state -> queue the worker, do not pretranslate
        //if the task is allready imported -> run the analysis directly, do not pretranslate
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task */
        $params = $event->getParam('params');
        $params['pretranslate'] = false;
        $this->handleOperation($task, $params);
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     * @throws ZfExtended_Exception
     * @throws Throwable
     * @throws ReflectionException
     * @throws editor_Task_Operation_Exception
     */
    public function handleOnPretranslationOperation(Zend_EventManager_Event $event): void
    {
        // if the task is in import state -> queue the worker,
        // set pretranslate to true in the worker and from the worker in the analysis.
        // if the task is allready imported -> run the analysis directly, set pretranslate to true
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task */
        $params = $event->getParam('params');
        $params['pretranslate'] = true;
        $this->handleOperation($task, $params);
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws editor_Models_ConfigException
     * @throws ZfExtended_Exception
     * @throws Throwable
     * @throws ReflectionException
     * @throws editor_Task_Operation_Exception
     */
    public function handleImportWorkerQueued(Zend_EventManager_Event $event): void
    {
        /* @var editor_Models_Task $task */
        $task = $event->getParam('task');
        $config = $task->getConfig();
        if ($config->runtimeOptions->plugins?->MatchAnalysis?->autoPretranslateOnTaskImport) {
            $this->handleOperation($task, [
                'pretranslate' => true,
                'isTaskImport' => true,
            ]);
        }
    }

    /**
     * Cron controller daily action
     * @throws ReflectionException
     */
    public function handleAfterDailyAction(Zend_EventManager_Event $event): void
    {
        $batchCache = ZfExtended_Factory::get(BatchResult::class);
        $batchCache->deleteOlderRecords();
    }

    /**
     * Operation action handler. Run analysis and pretranslate if $pretranslate is true.
     *
     * @throws ReflectionException
     * @throws Throwable
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws editor_Task_Operation_Exception
     */
    protected function handleOperation(editor_Models_Task $task, array $params): void
    {
        $config = $task->getConfig();

        // set the defaults from config if not provided as params
        if (! isset($params['internalFuzzy'])) {
            $params['internalFuzzy'] = $config->runtimeOptions->plugins->MatchAnalysis->internalFuzzyDefault;
        }
        if (! isset($params['pretranslateTmAndTerm'])) {
            $params['pretranslateTmAndTerm'] = $config->runtimeOptions
                ->plugins->MatchAnalysis->pretranslateTmAndTermDefault;
        }
        if (! isset($params['pretranslateMt'])) {
            $params['pretranslateMt'] = $config->runtimeOptions->plugins->MatchAnalysis->pretranslateMtDefault;
        }
        if (! isset($params['pretranslateMatchrate'])) {
            $params['pretranslateMatchrate'] = $config->runtimeOptions->plugins->MatchAnalysis->pretranslateMatchRate;
        }

        settype($params['internalFuzzy'], 'boolean');
        settype($params['pretranslateTmAndTerm'], 'boolean');
        settype($params['pretranslateMt'], 'boolean');
        settype($params['pretranslateMatchrate'], 'integer');
        settype($params['isTaskImport'], 'boolean');

        $taskGuids = [$task->getTaskGuid()];
        //if the requested operation is from project, queue analysis for each project task
        if ($task->isProject()) {
            $taskmodel = ZfExtended_Factory::get(editor_Models_Task::class);
            $projects = $taskmodel->loadProjectTasks($task->getProjectId(), true);
            $taskGuids = array_column($projects, 'taskGuid');
        }

        // If it is a pretranslation operation - reset tbx hash, so that terminology will be refreshed
        if ($params['pretranslate'] ?? false) {
            $task->meta()->resetTbxHash($taskGuids);
        }

        foreach ($taskGuids as $taskGuid) {
            $this->queueAnalysis($taskGuid, $params);
        }

        ZfExtended_Factory::get(Queue::class)->trigger();
    }

    /**
     * Add batch query worker for the resources used for pivot pre-translation before the pivot worker is queued
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    public function handleBeforePivotPreTranslationQueue(Zend_EventManager_Event $event): void
    {
        $task = $event->getParam('task');
        $pivotAssociations = $event->getParam('pivotAssociations');
        $parentWorkerId = $event->getParam('parentWorkerId');

        $batchResources = $this->getAvailableBatchResourceForPivotPreTranslation(
            $task->getTaskGuid(),
            $pivotAssociations
        );
        if (! empty($batchResources)) {
            $this->queueBatchWorkersForPivot($task, $parentWorkerId, $batchResources);
        }
    }

    /**
     * For each batch supported connector queue one batch worker
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function queueBatchWorkersForPivot(
        editor_Models_Task $task,
        int $parentWorkerId,
        array $batchResources
    ): void {
        $workerParameters = [];
        if (! $task->isImporting()) {
            $workerParameters['workerBehaviour'] = ZfExtended_Worker_Behaviour_Default::class;
        }
        $workerState = ZfExtended_Models_Worker::STATE_SCHEDULED;

        /* @var editor_Models_LanguageResources_LanguageResource $languageResource */
        foreach ($batchResources as $languageResource) {
            $batchWorker = ZfExtended_Factory::get(editor_Plugins_MatchAnalysis_BatchWorker::class);
            /* @var $batchWorker editor_Plugins_MatchAnalysis_BatchWorker */

            $workerParameters['languageResourceId'] = $languageResource->getId();

            $user = ZfExtended_Authentication::getInstance()->getUser();
            $workerParameters['userGuid'] = $user?->getUserGuid() ?? ZfExtended_Models_User::SYSTEM_GUID;

            $workerParameters['contentField'] = editor_Models_SegmentField::TYPE_RELAIS;

            if (! $batchWorker->init($task->getTaskGuid(), $workerParameters)) {
                //we log that fact, queue nothing and rely on the normal match analysis processing
                $this->addWarn(
                    $task,
                    'MatchAnalysis-Error on batchWorker init().'
                    . ' Batch worker for pivot pre-translation could not be initialized'
                );

                return;
            }

            //we may not trigger the queue here, just add the workers!
            $workerId = $batchWorker->queue($parentWorkerId, $workerState, false);

            $this->queueBatchCleanUpWorker($workerId, $task->getTaskGuid(), $workerState);
        }
    }

    /**
     * Check if the given pivot associations can be used for batch pre-translation
     * @throws ReflectionException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    protected function getAvailableBatchResourceForPivotPreTranslation(string $taskGuid, array $assocs): array
    {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        $batchAssocs = [];
        foreach ($assocs as $assoc) {
            $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
            $languageResource->load($assoc['languageResourceId']);

            $manager = ZfExtended_Factory::get(editor_Services_Manager::class);

            $connector = $manager->getConnector(
                $languageResource,
                $task->getSourceLang(),
                $task->getRelaisLang(),
                $task->getConfig()
            );
            /* @var $connector editor_Services_Connector */
            //collect all connectors which are supporting batch query
            if ($connector->isBatchQuery()) {
                $batchAssocs[] = clone $languageResource;
            }
        }

        return $batchAssocs;
    }

    /**
     * Queue the match analysis worker
     *
     * @return boolean
     * @throws ReflectionException
     * @throws Throwable
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws editor_Task_Operation_Exception
     */
    protected function queueAnalysis(string $taskGuid, array $workerParameters): bool
    {
        if (! $this->hasAssoc($taskGuid)) {
            //you can not run analysis without resources to be associated to the task
            return false;
        }

        $valid = $this->checkLanguageResources($taskGuid);

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        // lock the task dedicated for analysis
        if ($task->lock(NOW_ISO, editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS)) {
            // else check if we are in import, then no separate lock is needed.
            // Therefor if we are not in import this is an error
        } elseif ($task->getState() != editor_Models_Task::STATE_IMPORT) {
            $this->addWarn($task, 'MatchAnalysis Plug-In: task can not be locked for analysis and pre-translation.');

            return false;
        }

        try {
            $queued = $this->finishQueueAnalysis($task, $valid, $workerParameters);

            if (! $queued) {
                $task->unlock();
            }

            return $queued;
        } catch (Throwable $e) {
            $task->unlock();

            throw $e;
        }
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws ReflectionException
     * @throws editor_Task_Operation_Exception
     */
    private function finishQueueAnalysis(editor_Models_Task $task, array $valid, array $workerParameters): bool
    {
        if (empty($valid)) {
            $this->addWarn(
                $task,
                'MatchAnalysis Plug-In: No valid analysable language resources found.',
                [
                    'invalid' => print_r($this->assocs, 1),
                ]
            );

            return false;
        }

        if ($task->isImporting()) {
            //on import we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
            //on import state scheduled is ok
            $workerState = ZfExtended_Models_Worker::STATE_SCHEDULED;

            $this->doQueueAnalysisWorkers($task, $parentWorkerId, $workerState, $workerParameters);
        } else {
            // this creates the operation start/finish workers
            $operation = editor_Task_Operation::create(editor_Task_Operation::MATCHANALYSIS, $task);

            try {
                $parentWorkerId = $operation->getWorkerId();
                //on operations we init with prepare, since the operation schedules the prepared ones
                $workerState = ZfExtended_Models_Worker::STATE_PREPARE;
                // add a different behaviour for the workers when performing an operation
                $workerParameters['workerBehaviour'] = ZfExtended_Worker_Behaviour_Default::class;

                $this->doQueueAnalysisWorkers($task, $parentWorkerId, $workerState, $workerParameters);
                // and start operation
                $operation->start();
            } catch (Throwable $e) {
                $operation->onQueueingError();

                throw $e;
            }
        }

        return true;
    }

    /**
     * Queues the Analysis and all other needed workers
     * @return false|void
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function doQueueAnalysisWorkers(
        editor_Models_Task $task,
        int $parentWorkerId,
        ?string $workerState,
        array $workerParameters = []
    ) {
        $workerParameters['userGuid'] = ZfExtended_Models_User::SYSTEM_GUID;
        $workerParameters['userName'] = ZfExtended_Models_User::SYSTEM_LOGIN;
        
        if (ZfExtended_Authentication::getInstance()->getUser()) {
            $workerParameters['userGuid'] = ZfExtended_Authentication::getInstance()->getUserGuid();
            $workerParameters['userName'] = ZfExtended_Authentication::getInstance()->getUser()->getUserName();
        }
        
        //enable batch query via config
        $workerParameters['batchQuery'] = (bool) Zend_Registry::get('config')
            ->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
        if (! empty($this->batchAssocs) && $workerParameters['batchQuery']) {
            $this->queueBatchWorkers($task, $workerParameters, $parentWorkerId, $workerState);
        }

        $worker = ZfExtended_Factory::get(PauseMatchAnalysisWorker::class);
        $worker->init($task->getTaskGuid(), [
            PauseWorker::PROCESSOR => PauseMatchAnalysisProcessor::class,
        ]);
        $worker->queue($parentWorkerId, $workerState);

        // init worker and queue it
        $worker = ZfExtended_Factory::get(editor_Plugins_MatchAnalysis_Worker::class);

        if (! $worker->init($task->getTaskGuid(), $workerParameters)) {
            throw new ZfExtended_Exception('MatchAnalysis-Error on worker init(). Worker could not be initialized');
        }

        $worker->queue($parentWorkerId, $workerState, false);

        // if we are not importing we need to add the quality workers (which also include the termtagger)
        if (! $task->isImporting()) {
            editor_Segment_Quality_Manager::instance()->queueOperation(
                editor_Segment_Processing::ANALYSIS,
                $task,
                $parentWorkerId,
                ZfExtended_Models_Worker::STATE_PREPARE
            );
        }
    }

    /**
     * For each batch supported connector queue one batch worker
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function queueBatchWorkers(
        editor_Models_Task $task,
        array $eventParams,
        int $parentWorkerId,
        string $workerState
    ): void {
        $isPretranslateMt = (bool) $eventParams['pretranslateMt'];
        $isPretranslate = (bool) $eventParams['pretranslate'];
        $workerParameters = [];

        if (! $task->isImporting()) {
            $workerParameters['workerBehaviour'] = ZfExtended_Worker_Behaviour_Default::class;
        }

        foreach ($this->batchAssocs as $languageRessource) {
            /* @var $lr editor_Models_LanguageResources_LanguageResource */
            $batchWorker = ZfExtended_Factory::get(editor_Plugins_MatchAnalysis_BatchWorker::class);

            //do not use this resource when it is a mt and the pretranslateMt is disabled
            //if the pretranslation is disabled(the current request is for analysis only),
            //do not use this resource for batch query. For analysis only, the results from the resource
            //are not relevant, since always the same matchrate is received
            if ((! $isPretranslateMt || ! $isPretranslate) && $languageRessource->isMt()) {
                continue;
            }

            $workerParameters['languageResourceId'] = $languageRessource->getId();
            $workerParameters['userGuid'] = $eventParams['userGuid'];

            if (! $batchWorker->init($task->getTaskGuid(), $workerParameters)) {
                //we log that fact, queue nothing and rely on the normal match analysis processing
                $this->addWarn($task, 'MatchAnalysis-Error on batchWorker init(). Worker could not be initialized');

                return;
            }

            //we may not trigger the queue here, just add the workers!
            $workerId = $batchWorker->queue($parentWorkerId, $workerState, false);

            $this->queueBatchCleanUpWorker($workerId, $task->getTaskGuid(), $workerState);
        }
    }

    /**
     * @throws ReflectionException
     */
    private function fetchImportWorkerId(string $taskGuid): int
    {
        $parent = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);

        try {
            $parent->loadFirstOf(editor_Models_Import_Worker::class, $taskGuid);

            return (int) $parent->getId();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            return 0;
        }
    }

    /**
     * Check if the given task has associated language resources
     * @return boolean
     * @throws ReflectionException
     */
    protected function hasAssoc(string $taskGuid): bool
    {
        $languageResources = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        $this->assocs = $languageResources->loadByAssociatedTaskGuid($taskGuid);

        return ! empty($this->assocs);
    }

    /**
     * Check if the current associated language resources can be used for analysis
     * @throws ReflectionException
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    protected function checkLanguageResources(string $taskGuid): array
    {
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        $this->resetBatchAssocs();

        $valid = [];
        foreach ($this->assocs as $assoc) {
            $languageresource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);

            $languageresource->load($assoc['id']);

            $manager = ZfExtended_Factory::get(editor_Services_Manager::class);
            $resource = $manager->getResource($languageresource);

            $connector = $manager->getConnector(
                $languageresource,
                $task->getSourceLang(),
                $task->getTargetLang(),
                $task->getConfig()
            );
            /* @var $connector editor_Services_Connector */
            //collect all connectors which are supporting batch query
            if ($connector->isBatchQuery()) {
                $this->batchAssocs[] = clone $languageresource;
            }

            //analysable language resource is found
            if (! empty($resource) && $resource->getAnalysable()) {
                $valid[] = $assoc;
            }
        }

        return $valid;
    }

    /**
     * Queue batch cleanup worker. This will clean the batch results after the batch results are used.
     *
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function queueBatchCleanUpWorker(int $parent, string $taskGuid, string $workerState): void
    {
        $batchCleanupWorker = ZfExtended_Factory::get(BatchCleanupWorker::class);

        if (! $batchCleanupWorker->init($taskGuid, [
            'taskGuid' => $taskGuid,
        ])) {
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->loadByTaskGuid($taskGuid);

            //we log that fact, queue nothing and rely on the normal match analysis processing
            $this->addWarn(
                $task,
                'MatchAnalysis-Error on BatchCleanupWorker init(). BatchCleanupWorker can not be initialized'
            );

            return;
        }

        //we may not trigger the queue here, just add the workers!
        $batchCleanupWorker->queue($parent, $workerState, false);
    }

    /**
     * Reset the collected batch language resources
     */
    protected function resetBatchAssocs(): void
    {
        $this->batchAssocs = [];
    }

    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes(): void
    {
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
            [
                'module' => 'editor',
                'controller' => 'plugins_matchanalysis_matchanalysis',
                'action' => 'export',
            ]
        );
        $r->addRoute('plugins_matchanalysis_export', $exportAnalysis);

        $r->addRoute(
            'presetClone',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_matchanalysis_pricingpreset/clone/*',
                [
                    'module' => 'editor',
                    'controller' => 'plugins_matchanalysis_pricingpreset',
                    'action' => 'clone',
                ]
            )
        );

        $r->addRoute(
            'presetDefault',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_matchanalysis_pricingpreset/setdefault/*',
                [
                    'module' => 'editor',
                    'controller' => 'plugins_matchanalysis_pricingpreset',
                    'action' => 'setdefault',
                ]
            )
        );

        $r->addRoute(
            'presetpricesClone',
            new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_matchanalysis_pricingpresetprices/clone/*',
                [
                    'module' => 'editor',
                    'controller' => 'plugins_matchanalysis_pricingpresetprices',
                    'action' => 'clone',
                ]
            )
        );
    }

    /**
     * Log analysis warning
     * @throws Zend_Exception
     */
    protected function addWarn(editor_Models_Task $task, string $message, array $extra = []): void
    {
        $extra['task'] = $task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100', $message, $extra);
    }

    /**
     * @throws ReflectionException
     * @see ZfExtended_RestController::afterActionEvent
     */
    public function handleCustomerAfterIndex(Zend_EventManager_Event $event): void
    {
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
