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

use MittagQI\Translate5\Task\Import\ImportEventTrigger;
use MittagQI\Translate5\Cronjob\Cronjobs;

/**
 * Hook In functions for the Default Workflow.
 */
class editor_Workflow_Default_Hooks {
    const HANDLE_IMPORT_BEFORE    = 'handleBeforeImport';
    const HANDLE_IMPORT           = 'handleImport';
    const HANDLE_IMPORT_AFTER     = 'handleAfterImport';
    const HANDLE_IMPORT_COMPLETED = 'handleImportCompleted';
    
    const HANDLE_CRON_DAILY       = 'doCronDaily';
    const HANDLE_CRON_PERIODICAL  = 'doCronPeriodical';

    const HANDLE_PROJECT_CREATED  = 'handleProjectCreated';
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /**
     * Container for the old Task Model provided by doWithTask
     * (task as loaded from DB)
     * @var editor_Models_Task
     */
    protected $oldTask;
    
    /**
     * Container for the new Task Model provided by doWithTask
     * (task as going into DB, means not saved yet!)
     * @var editor_Models_Task
     */
    protected $newTask;
    
    /**
     * Container for the old User Task Assoc Model provided by doWithUserAssoc
     * @var editor_Models_TaskUserAssoc
     */
    protected $oldTaskUserAssoc;
    
    /**
     * Container for the new Task User Assoc Model provided by doWithUserAssoc
     * @var editor_Models_TaskUserAssoc
     */
    protected $newTaskUserAssoc;
    
    /**
     * @var ZfExtended_Models_User|null
     */
    protected ?ZfExtended_Models_User $authenticatedUser = null;

    /**
     * Import config, only available on workflow stuff triggerd in the context of an import
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig = null;
    
    /**
     * determines if calls were done by cronjob
     * @var boolean
     */
    protected $isCron = false;
    
    protected $validDirectTrigger = [
        'notifyAllUsersAboutTaskAssociation',
    ];
    
    public function __construct(editor_Workflow_Default $workflow)
    {
        $this->workflow = $workflow;
        $this->loadAuthenticatedUser();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $this->events->addIdentifiers(get_class($workflow));

        $events = Zend_EventManager_StaticEventManager::getInstance();
        $events->attach(
            Editor_TaskuserassocController::class,
            'afterPostAction',
            function (Zend_EventManager_Event $event) {
                $tua = $event->getParam('entity');
                //if entity could not be saved no ID was given, so check for it
                if ($tua->getId() > 0) {
                    $this->newTaskUserAssoc = $tua;
                    $jobHandler = ZfExtended_Factory::get(editor_Workflow_Default_JobHandler::class);
                    /* @var $jobHandler editor_Workflow_Default_JobHandler */
                    $jobHandler->execute($this->getActionConfig($jobHandler::HANDLE_JOB_ADD));
                    $this->workflow->getStepRecalculation()->recalculateWorkflowStep($tua);
                }
            }
        );

        $events->attach(
            Editor_TaskuserassocController::class,
            'afterDeleteAction',
            function (Zend_EventManager_Event $event) {
                $this->newTaskUserAssoc = $event->getParam('entity');
                $jobHandler = ZfExtended_Factory::get(editor_Workflow_Default_JobHandler::class);
                /* @var $jobHandler editor_Workflow_Default_JobHandler */
                $jobHandler->execute($this->getActionConfig($jobHandler::HANDLE_JOB_DELETE));
                $this->workflow->getStepRecalculation()->recalculateWorkflowStep($this->newTaskUserAssoc);
            }
        );

        $events->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::BEFORE_IMPORT,
            function (Zend_EventManager_Event $event) {
                $this->newTask = $event->getParam('task');
                $this->handleBeforeImport();
            }
        );

        $events->attach(
            editor_Models_Import_Worker_FinalStep::class,
            'importCompleted',
            function (Zend_EventManager_Event $event) {
                $this->newTask = $event->getParam('task');
                $this->importConfig = $event->getParam('importConfig');
                $this->handleImportCompleted();
            }
        );
    }

    /**
     * loads the system user as authenticatedUser, if no user is logged in
     * @throws ZfExtended_NotAuthenticatedException
     */
    protected function loadAuthenticatedUser(){
        if(Zend_Session::isDestroyed()) {
            //if there is no session anymore (in the case of garbage cleanup) we can not load any authenticated user
            // but this should be no problem since on garbage collection no user specific stuff is done
            return;
        }
        $auth = ZfExtended_Authentication::getInstance();
        $this->authenticatedUser = $auth->getUser();

        $isWorker = defined('ZFEXTENDED_IS_WORKER_THREAD');

        if (is_null($this->authenticatedUser)) {
            //if cron or worker set session user data with system user
            if ((Cronjobs::isRunning() || $isWorker)
                && $auth->authenticateByLogin(ZfExtended_Models_User::SYSTEM_LOGIN)) {
                $this->authenticatedUser = $auth->getUser();
            } else {
                throw new ZfExtended_NotAuthenticatedException("Cannot authenticate the system user!");
            }
        }
    }
    
    /**
     * will be called directly before import is started, task is already created and available
     */
    protected function handleBeforeImport(){
        $this->doDebug(self::HANDLE_IMPORT_BEFORE);
        $this->workflow->getStepRecalculation()->initWorkflowStep($this->newTask, $this->workflow::STEP_NO_WORKFLOW);
        $this->newTask->load($this->newTask->getId()); //reload task with new workflowStepName and new calculated workflowStepNr
        $this->callActions(self::HANDLE_IMPORT_BEFORE, $this->workflow::STEP_NO_WORKFLOW);
    }

    /**
     * will be called after import (in set task to open worker) after the task is opened and the import is complete.
     */
    protected function handleImportCompleted(){
        $this->doDebug(self::HANDLE_IMPORT_COMPLETED);
        $this->callActions(self::HANDLE_IMPORT_COMPLETED);
    }

    
    /**
     * checks the delivery dates, if a task is overdue, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     * will be called daily
     */
    public function doCronDaily() {
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(self::HANDLE_CRON_DAILY);
    }
    
    /**
     * will be called periodically between every 5 to 15 minutes, depending on the traffic on the installation.
     */
    public function doCronPeriodical(){
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(self::HANDLE_CRON_PERIODICAL);
    }
    
    /**
     * task change hook in for the workflow
     * @param editor_Models_Task $oldTask task as loaded from DB
     * @param editor_Models_Task $newTask task as going into DB (means not saved yet!)
     */
    public function doWithTask(editor_Models_Task $oldTask, editor_Models_Task $newTask) {
        $this->oldTask = $oldTask;
        $this->newTask = $newTask;
        
        /* @var $taskHandler editor_Workflow_Default_TaskHandler */
        $taskHandler = ZfExtended_Factory::get('editor_Workflow_Default_TaskHandler');
        $taskHandler->execute($this->getActionConfig());
    }
    
    /**
     * Method should be called every time a TaskUserAssoc is updated. Must be called after doWithTask if both methods are called.
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     * @param Callable $saveCallback Optional callback which is triggered after the beforeEvents and before doWithUserAssoc code - normally for persisting the new tua
     */
    public function doWithUserAssoc(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua, Callable $saveCallback = null) {
        $this->oldTaskUserAssoc = $oldTua;
        $this->newTaskUserAssoc = $newTua;
        
        if(empty($this->newTask)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($newTua->getTaskGuid());
            $this->newTask = $task;
        }

        if($this->newTask->isImporting()) {
            //when task is importing, we may not trigger workflow stuff
            $saveCallback();
            return;
        }

        /* @var $jobHandler editor_Workflow_Default_JobHandler */
        $jobHandler = ZfExtended_Factory::get('editor_Workflow_Default_JobHandler');
        $jobHandler->executeSave($this->getActionConfig(), $saveCallback);
    }
    
    /**
     * is called directly after import
     * @param editor_Models_Task $importedTask
     */
    public function doImport(editor_Models_Task $importedTask, editor_Models_Import_Configuration $importConfig) {
        $this->newTask = $importedTask;
        $this->importConfig = $importConfig;
        $this->doDebug(self::HANDLE_IMPORT);
        $this->workflow->getStepRecalculation()->setupInitialWorkflow($this->newTask);
        $this->callActions(self::HANDLE_IMPORT);
    }
    
    /**
     * is called after whole import, after task was successfully opened for usage
     * @param editor_Models_Task $importedTask
     */
    public function doAfterImport(editor_Models_Task $importedTask) {
        $this->newTask = $importedTask;
        $this->doDebug(self::HANDLE_IMPORT_AFTER);
        $this->callActions(self::HANDLE_IMPORT_AFTER);
    }
    
    /**
     * Is called after project has been created but before import workers started
     *
     * @param editor_Models_Task $project
     */
    public function doHandleProjectCreated(editor_Models_Task $project) {
        $this->newTask = $project;
        $this->doDebug(self::HANDLE_PROJECT_CREATED);
        $this->callActions(self::HANDLE_PROJECT_CREATED);
    }

    /**
     * can be triggered via API, valid triggers are currently
     * @param editor_Models_Task $task
     * @param string $trigger
     */
    public function doDirectTrigger(editor_Models_Task $task, $trigger) {
        if(!in_array($trigger, $this->validDirectTrigger)) {
            return false;
        }
        $this->newTask = $task;
        
        try {
            //try to load an user assoc between current user and task
            $this->newTaskUserAssoc =editor_Models_Loaders_Taskuserassoc::loadByTask($this->authenticatedUser->getUserGuid(), $task);
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->newTaskUserAssoc = null;
        }
        $this->callActions('handleDirect::'.$trigger, $task->getWorkflowStepName());
        return true;
    }
    
    /**
     * returns the valid direct trigger
     * @return string[]
     */
    public function getDirectTrigger() {
        return $this->validDirectTrigger;
    }
    
    /**
     * calls the actions configured to the trigger with given role and state
     * @param string $trigger
     * @param string $step can be empty
     * @param string $role can be empty
     * @param string $state can be empty
     *
     * FIXME add the other usages too
     * @uses editor_Workflow_Notification::notifyAllFinishOfARole()
     */
    protected function callActions($trigger, $step = null, $role = null, $state = null) {
        $actions = ZfExtended_Factory::get('editor_Models_Workflow_Action');
        /* @var $actions editor_Models_Workflow_Action */
        $debugData = [
            'trigger' => $trigger,
            'step' => $step,
            'role' => $role,
            'state' => $state,
        ];
        $actions = $actions->loadByTrigger([$this->workflow->getName()], $trigger, $step, $role, $state);
        $this->actionDebugMessage([$this->workflow->getName()], $debugData);
        $instances = [];
        $config = $this->getActionConfig($trigger);
        foreach($actions as $action) {
            $class = $action['actionClass'];
            $method = $action['action'];
            //FIXME UGLY: The parameters are changing by reference! Must be removed again, and unified, see below FIXME
            $config->parameters = $this->decodeParameters($config, $action);
            if(empty($instances[$class])) {
                $instance = ZfExtended_Factory::get($class);
                /* @var $instance editor_Workflow_Actions_Abstract */
                //FIXME unify this callActions with AbstractHandler::callActions
                $instance->init($config);
                $instances[$class] = $instance;
            }
            else {
                $instance = $instances[$class];
            }
            
            $this->actionDebugMessage($action, $debugData);
            if (is_null($config->parameters)) {
                call_user_func([$instance, $method]);
            } else {
                call_user_func([$instance, $method], $config->parameters);
            }
        }
    }

    protected function decodeParameters(editor_Workflow_Actions_Config $config, array $action): ?stdClass
    {
        if (empty($action['parameters'])) {
            return null;
        }
        try {
            return json_decode($action['parameters'], flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $config->workflow->getLogger($config->task)->error('E1171', 'Workflow Action: JSON Parameters for workflow action call could not be parsed with message: {msg}', [
                'msg' => $e->getMessage(),
                'action' => $action
            ]);
        }
        return null;
    }
    
    /**
     * generates a debug message for called actions
     * @param array $action
     * @param array $data
     * @return string
     */
    protected function actionDebugMessage(array $action, array $data) {
        if(!empty($action) && empty($action['actionClass'])) {
            //called in context before action load
            $msg = ' Try to load actions for workflow(s) "'.join(', ', $action).'" through trigger {trigger}';
        }
        else {
            //called in context after action loaded
            $msg = ' Workflow called action '.$action['actionClass'].'::'.$action['action'].'() through trigger {trigger}';
        }
        if(!empty($action['parameters'])) {
            $data['parameters'] = $action['parameters'];
        }
        $this->doDebug($msg, $data);
    }
    
    /**
     * prepares a config object for workflow actions
     * @param string $trigger
     * @return editor_Workflow_Actions_Config
     */
    protected function getActionConfig(string $trigger = null): editor_Workflow_Actions_Config {
        $config = ZfExtended_Factory::get('editor_Workflow_Actions_Config');
        /* @var $config editor_Workflow_Actions_Config */
        $config->trigger = $trigger;
        $config->events = $this->events;
        $config->workflow = $this->workflow;
        $config->newTua = $this->newTaskUserAssoc;
        $config->oldTua = $this->oldTaskUserAssoc;
        $config->oldTask = $this->oldTask;
        $config->task = $this->newTask;
        $config->importConfig = $this->importConfig;
        $config->authenticatedUser = $this->authenticatedUser;
        $config->isCalledByCron = $this->isCron;
        return $config;
    }
    
    /**
     * debugging workflow
     * @param string $msg
     * @param array $data optional debuggin data
     * @param bool $levelInfo optional, if true log in level info instead debug
     */
    protected function doDebug($msg, array $data = [], $levelInfo = false) {
        $log = $this->workflow->getLogger($this->newTask);
        
        //add the job / tua
        if(!empty($this->newTaskUserAssoc)) {
            $data['job'] = $this->newTaskUserAssoc;
        }
        if($levelInfo) {
            $log->info('E1013', $msg, $data);
        }
        else {
            $log->debug('E1013', $msg, $data);
        }
    }
}
