<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Handler functions for the Default Workflow.
 */
class editor_Workflow_Default_Handler {
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;
    
    /**
     * Holds workflow log instance per affected task
     * Accesable via doDebug method
     * @var array
     */
    protected $log = [];
    
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
     * @var stdClass
     */
    protected $authenticatedUser;
    
    /**
     * @var ZfExtended_Models_User
     */
    protected $authenticatedUserModel;
    
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
    
    protected $nextStepWasSet = [];
    
    public function __construct(editor_Workflow_Default $workflow) {
        $this->workflow = $workflow;
        $this->loadAuthenticatedUser();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $this->events->addIdentifiers(get_class($workflow));

        $events = Zend_EventManager_StaticEventManager::getInstance();
        $events->attach('Editor_TaskuserassocController', 'afterPostAction', function(Zend_EventManager_Event $event){
            $tua = $event->getParam('entity');
            //if entity could not be saved no ID was given, so check for it
            if($tua->getId() > 0) {
                $this->doUserAssociationAdd($tua);
                $this->recalculateWorkflowStep($tua);
            }
        });

        $events->attach('Editor_TaskuserassocController', 'afterDeleteAction', function(Zend_EventManager_Event $event){
            $this->doUserAssociationDelete($event->getParam('entity'));
            $this->recalculateWorkflowStep($event->getParam('entity'));
        });

        $events->attach('editor_Models_Import', 'beforeImport', function(Zend_EventManager_Event $event){
            $this->newTask = $event->getParam('task');
            $this->handleBeforeImport();
        });

        $events->attach('editor_Models_Import_Worker_FinalStep', 'importCompleted', function(Zend_EventManager_Event $event){
            $this->newTask = $event->getParam('task');
            $this->importConfig = $event->getParam('importConfig');
            $this->handleImportCompleted();
        });
    }
    
    /**
     * recalculates the workflow step by the given task user assoc combinations
     * If the combination of roles and states are pointing to an specific workflow step, this step is used
     * If the states and roles does not match any valid combination, no step is changed.
     * @param editor_Models_TaskUserAssoc $tua
     */
    protected function recalculateWorkflowStep(editor_Models_TaskUserAssoc $tua) {
        $taskGuid = $tua->getTaskGuid();
        
        //if the step was recalculated due setNextStep in internal workflow calculations,
        // we may not recalculate it here again!
        if(!empty($this->nextStepWasSet[$taskGuid])) {
            $this->sendFrontEndNotice($this->nextStepWasSet[$taskGuid]['newStep']);
            return;
        }
        
        $tuas = $tua->loadByTaskGuidList([$taskGuid]);
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $matchingSteps = [];
        $pmOvverideCount=0;
        foreach($tuas as $tua) {
            if($tua['isPmOverride']==1){
                $pmOvverideCount++;
            }
        }
        if(empty($tuas) && count($tuas)==$pmOvverideCount){
            $matchingSteps[]=$this->workflow::STEP_NO_WORKFLOW;
        }else{
            foreach($this->workflow->getValidStates() as $step => $roleStates) {
                if(!$this->areTuasSubset($roleStates, $step, $tuas)) {
                    continue;
                }
                $matchingSteps[] = $step;
            }
        }
        
        //if the current step is one of the possible steps for the tua configuration
        // then everything is OK,
        // or if no valid configuration is found, then we also could not change the step
        if(empty($matchingSteps) || in_array($task->getWorkflowStepName(), $matchingSteps)) {
            return;
        }
        //set the first found valid step to the current workflow step
        $step = reset($matchingSteps);
        $this->doDebug('recalculate workflow to step {step} ', ['step' => $step], true);
        $task->updateWorkflowStep($step, false);
        //set $step as new workflow step if different to before!
        $this->sendFrontEndNotice($step);
    }
    
    /**
     * Checks if the given Jobs (tuas) are a subset of the list be compared
     * @param array $toCompare
     * @param string $currentStep
     * @param array $tuas
     * @return bool
     */
    protected function areTuasSubset(array $toCompare, string $currentStep, array $tuas): bool {
        $hasStepToCurrentTaskStep = false;
        foreach($tuas as $tua) {
            if(empty($toCompare[$tua['workflowStepName']])) {
                return false;
            }
            if(!in_array($tua['state'], $toCompare[$tua['workflowStepName']])) {
                return false;
            }
            $hasStepToCurrentTaskStep = $hasStepToCurrentTaskStep || ($currentStep == $tua['workflowStepName']);
        }
        //we can only return true, if the Tuas contain at least one role belonging to the currentStep,
        // in other words we can not reset the task to reviewing, if we do not have a reviewer
        return $hasStepToCurrentTaskStep;
    }
    
    protected function sendFrontEndNotice(string $step) {
        $msg = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        /* @var $msg ZfExtended_Models_Messages */
        $labels = $this->workflow->getLabels();
        $steps = $this->workflow->getSteps();
        $step = $labels[array_search($step, $steps)];
        $msg->addNotice('Der Workflow Schritt der Aufgabe wurde zu "{0}" geändert!', 'core', null, $step);
    }
    
    /**
     * loads the system user as authenticatedUser, if no user is logged in
     */
    protected function loadAuthenticatedUser(){
        if(Zend_Session::isDestroyed()) {
            //if there is no session anymore (in the case of garbage cleanup) we can not load any authenticated user
            // but this should be no problem since on garbace collection no user specific stuff is done
            return;
        }
        $userSession = new Zend_Session_Namespace('user');
        if(isset($userSession->data) && isset($userSession->data->userGuid)) {
            $userGuid = $userSession->data->userGuid;
        }
        else {
            $userGuid = false;
        }
        $config = Zend_Registry::get('config');
        $isCron = $config->runtimeOptions->cronIP === ($_SERVER['REMOTE_ADDR'] ?? null);
        $isWorker = defined('ZFEXTENDED_IS_WORKER_THREAD');
        $this->authenticatedUserModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        
        if($userGuid === false){
            if(!$isCron && !$isWorker) {
                throw new ZfExtended_NotAuthenticatedException("Cannot authenticate the system user!");
            }
            //set session user data with system user
            $this->authenticatedUserModel->setUserSessionNamespaceWithoutPwCheck(ZfExtended_Models_User::SYSTEM_LOGIN);
        }
        $this->authenticatedUserModel->loadByGuid($userSession->data->userGuid);
        $this->authenticatedUser = $userSession->data;
    }
    
    /**
     * will be called directly before import is started, task is already created and available
     */
    protected function handleBeforeImport(){
        $this->doDebug(__FUNCTION__);
        $this->initWorkflowStep($this->newTask, $this->workflow::STEP_NO_WORKFLOW);
        $this->newTask->load($this->newTask->getId()); //reload task with new workflowStepName and new calculated workflowStepNr
        $this->callActions(__FUNCTION__, $this->workflow::STEP_NO_WORKFLOW);
    }
    
    /**
     * Inits the workflow step in the given task
     * @param editor_Models_Task $task
     * @param string $stepName
     */
    protected function initWorkflowStep(editor_Models_Task $task, $stepName) {
        $this->doDebug('workflow init step to "{step}"', ['step' => $stepName], true);
        $task->updateWorkflowStep($stepName, false);
    }

    /**
     * will be called after import (in set task to open worker) after the task is opened and the import is complete.
     */
    protected function handleImportCompleted(){
        $this->doDebug(__FUNCTION__);
        $this->callActions(__FUNCTION__);
    }

    
    /**
     * will be called after all users of a role has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleAllFinishOfARole() {
        $newTua = $this->newTaskUserAssoc;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //this remains as default behaviour
        $nextStep = $newTua->getWorkflowStepName();
        $this->doDebug(__FUNCTION__." Next Step: ".$nextStep.' to role '.$newTua->getRole().' with step '.$nextStep."; Old Step in Task: ".$oldStep);
        if($nextStep) {
            //Next step triggert ebenfalls eine callAction → aber irgendwie so, dass der neue Wert verwendet wird! Henne Ei!
            $this->setNextStep($task, $nextStep);
            $nextRole = $this->workflow->getRoleOfStep($nextStep);
            $this->doDebug(__FUNCTION__." Next Role: ".$nextRole);
            if($nextRole) {
                $isComp = $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE;
                $newTua->setStateForRoleAndTask($isComp ? $this->workflow::STATE_UNCONFIRMED : $this->workflow::STATE_OPEN, $nextRole);
            }
        }
        
        //provide here oldStep, since this was the triggering one. The new step is given to handleNextStep trigger
        $this->callActions(__FUNCTION__, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * will be called after a user has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFinish() {
        $this->doDebug(__FUNCTION__);
        $newTua = $this->newTaskUserAssoc;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //set the finished date when the user finishes a role
        if($newTua->getState()==editor_Workflow_Default::STATE_FINISH){
            $newTua->setFinishedDate(NOW_ISO);
            $newTua->save();
        }
        
        $this->callActions(__FUNCTION__, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * will be called after all associated users of a task has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleAllFinish() {
        $this->doDebug(__FUNCTION__);
    }

    /**
     * will be called after a task has been ended
     */
    protected function handleEnd() {
        $this->newTask->dropMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * will be called after first user of a role has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFirstFinishOfARole(){
        $taskState = $this->newTask->getState();
        if($taskState == editor_Models_Task::STATE_UNCONFIRMED) {
            //we have to confirm the task and retrigger task workflow triggers
            // if task was unconfirmed but a lektor is set to finish, this implies confirming
            $oldTask = clone $this->newTask;
            $this->newTask->setState(editor_Models_Task::STATE_OPEN);
            $this->doWithTask($oldTask, $this->newTask);
            $this->newTask->save();
            $this->newTask->setState(editor_Models_Task::STATE_OPEN);
        }
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * will be called after a user has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFirstFinish(){
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * reopen an ended task (task-specific reopening in contrast to taskassoc-specific unfinish)
     * will be called after a task has been reopened (after was ended - task-specific)
     */
    protected function handleReopen(){
        $this->newTask->createMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * unfinish a finished task (taskassoc-specific unfinish in contrast to task-specific reopening)
     * Set all REVIEWED_UNTOUCHED segments to TRANSLATED
     * will be called after a task has been unfinished (after was finished - taskassoc-specific)
     */
    protected function handleUnfinish(){
        $this->doDebug(__FUNCTION__);
        $newTua = $this->newTaskUserAssoc;
        /* @var $actions editor_Workflow_Actions */
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * returns true if the workflow methods were triggered by a cron job and no direct user/API interaction
     * @return boolean
     */
    public function isCalledByCron() {
        return $this->isCron;
    }
    
    /**
     * checks the delivery dates, if a task is overdue, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     * will be called daily
     */
    public function doCronDaily() {
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * will be called periodically between every 5 to 15 minutes, depending on the traffic on the installation.
     */
    public function doCronPeriodical(){
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * will be called when a new task user association is created
     */
    protected function handleUserAssociationChanged(string $handler) {
        $this->doDebug($handler);
        $tua = $this->newTaskUserAssoc;
        if(empty($this->newTask)) {
            $this->newTask = ZfExtended_Factory::get('editor_Models_Task');
            $this->newTask->loadByTaskGuid($tua->getTaskGuid());
        }
        $this->callActions($handler, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
    }
    
    /**
     - Methode wird beim PUT vom Task aufgerufen
     - bekommt den alten und den neuen Task, sowie den Benutzer übergeben
     - setzt die übergebenen Task und User Objekte zur weiteren Verwendung als Objekt Attribute
     - Anhand von der Statusänderung ergibt sich welche ""do"" Methode aufgerufen wird
     - Anhand der Statusänderung kann auch der TaskLog Eintrag erzeugt werden
     - Hier lässt sich zukünftig auch eine Zend_Acl basierte Rechteüberprüfung integrieren, ob der Benutzer die ermittelte Aktion überhaupt durchführen darf.
     - Hier lassen sich zukünftig auch andere Änderungen am Task abfangen"	1.6		x
     *
     * @param editor_Models_Task $oldTask task as loaded from DB
     * @param editor_Models_Task $newTask task as going into DB (means not saved yet!)
     */
    public function doWithTask(editor_Models_Task $oldTask, editor_Models_Task $newTask) {
        $this->oldTask = $oldTask;
        $this->newTask = $newTask;
        $newState = $newTask->getState();
        $oldState = $oldTask->getState();
        //a segment mv creation is currently not needed, since doEnd deletes it, and doReopen creates it implicitly!
        
        if($newState == $oldState) {
            $this->doTaskChange();
            $this->events->trigger("doTaskChange", $this->workflow, array('oldTask' => $oldTask, 'newTask' => $newTask));
            return; //saved some other attributes, do nothing
        }
        switch($newState) {
            case $newTask::STATE_OPEN:
                if($oldState == $newTask::STATE_END) {
                    $this->doReopen();
                    $this->events->trigger("doReopen", $this->workflow, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                if($oldState == $newTask::STATE_UNCONFIRMED) {
                    $this->doTaskConfirm();
                    $this->events->trigger("doTaskConfirm", $this->workflow, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                break;
            case $newTask::STATE_END:
                $this->doEnd();
                $this->events->trigger("doEnd", $this->workflow, array('oldTask' => $oldTask, 'newTask' => $newTask));
                break;
            case $newTask::STATE_UNCONFIRMED:
                //doing currently nothing
                break;
        }
    }
    
    /**
     * Method should be called every time a TaskUserAssoc is updated. Must be called after doWithTask if both methods are called.
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     */
    public function doWithUserAssoc(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua) {
        $this->oldTaskUserAssoc = $oldTua;
        $this->newTaskUserAssoc = $newTua;
        
        if(empty($this->newTask)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($newTua->getTaskGuid());
            $this->newTask = $task;
        }
        else {
            $task = $this->newTask;
        }
        $this->doDebug(__FUNCTION__);
        //ensure that segment MV is createad
        $task->createMaterializedView();
        $state = $this->getTriggeredState($oldTua, $newTua);
        if(!empty($state)) {
            if(method_exists($this, $state)) {
                $this->{$state}();
            }
            $this->events->trigger($state, $this->workflow, array('oldTua' => $oldTua, 'newTua' => $newTua, 'task' => $task));
        }
        $this->handleUserAssociationChanged('handleUserAssociationEdited');
        $this->recalculateWorkflowStep($newTua);
    }
    
    /**
     * is called directly after import
     * @param editor_Models_Task $importedTask
     */
    public function doImport(editor_Models_Task $importedTask, editor_Models_Import_Configuration $importConfig) {
        $this->newTask = $importedTask;
        $this->importConfig = $importConfig;
        $this->doDebug('handleImport');
        $this->setupInitialWorkflow();
        $this->callActions('handleImport'); //FIXME convert all handler to their doer functions
    }
    
    /**
     * - cleans the not needed automatically added task user associations from the job list
     * - sets the tasks workflow step depending the associated jobs
     * - sets the initial states depending on the workflow step of the task and task usage mode
     */
    protected function setupInitialWorkflow() {
        /* @var $job editor_Models_TaskUserAssoc */
        $job = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        $jobs = $job->loadByTaskGuidList([$this->newTask->getTaskGuid()]);
        
        $usedSteps = [];
        //delete jobs created by default which are not belonging to the tasks workflow and collect used steps
        foreach($jobs as $rawJob) {
            if($rawJob['workflow'] !== $this->newTask->getWorkflow()) {
                $job->db->delete(['id = ?' => $rawJob['id']]);
            }
            $usedSteps[] = $rawJob['workflowStepName'];
        }
        if(empty($usedSteps)) {
            return;
        }
        
        //sort the found steps regarding the step chain
        $usedSteps = array_unique($usedSteps);
        usort($usedSteps, [$this->workflow, 'compareSteps']);
        
        //we set the tasks workflow step to the first found step of the assigned users, respecting the order of the step chain
        $currentStep = array_shift($usedSteps);
        $this->newTask->updateWorkflowStep($currentStep, false);
        
        $isComp = $this->newTask->getUsageMode() == $this->newTask::USAGE_MODE_COMPETITIVE;
        foreach($jobs as $rawJob) {
            //currentstep jobs are open
            if($currentStep === $rawJob['workflowStepName']) {
                $state = $isComp ? $this->workflow::STATE_UNCONFIRMED : $this->workflow::STATE_OPEN;
            }
            else {
            //all other steps are coming later in the chain, so they are waiting
                $state = $this->workflow::STATE_WAITING;
            }
            $job->db->update(['state' => $state], ['id = ?' => $rawJob['id']]);
        }
    }
    
    /**
     * is called after a user association is added
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function doUserAssociationAdd(editor_Models_TaskUserAssoc $tua) {
        $this->newTaskUserAssoc = $tua;
        $this->handleUserAssociationChanged('handleUserAssociationAdded');
    }
    
    /**
     * is called after a user association is added
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function doUserAssociationDelete(editor_Models_TaskUserAssoc $tua) {
        $this->newTaskUserAssoc = $tua; //"new" is basicly wrong, but with that entity all calculation is done
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($tua->getTaskGuid());
        $this->newTask = $task;
        
        $originalState = $tua->getState();
        //if the deleted tua was not finished, we have to recheck the allFinished events after deleting it!
        $wasNotFinished = ($originalState !== $this->workflow::STATE_FINISH);
        $stat = $this->calculateFinish();
        $this->doDebug(__FUNCTION__. ' OriginalState: '.$originalState.'; Finish Stat: '.print_r($stat,1));
        if($wasNotFinished && $stat['roleAllFinished']) {
            //in order to trigger the actions correctly we have to assume that the deleted one was "finished"
            $tua->setState($this->workflow::STATE_FINISH);
            $this->handleAllFinishOfARole();
        }
        if($wasNotFinished && $stat['allFinished']) {
            //in order to trigger the actions correctly we have to assume that the deleted one was "finished"
            $tua->setState($this->workflow::STATE_FINISH);
            $this->handleAllFinish();
        }
        $tua->setState($originalState);
        $this->handleUserAssociationChanged('handleUserAssociationDeleted');
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
            $this->newTaskUserAssoc =editor_Models_Loaders_Taskuserassoc::loadByTask($this->authenticatedUser->userGuid, $task);
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
        foreach($actions as $action) {
            $class = $action['actionClass'];
            $method = $action['action'];
            if(empty($instances[$class])) {
                $instance = ZfExtended_Factory::get($class);
                /* @var $instance editor_Workflow_Actions_Abstract */
                $instance->init($this->getActionConfig());
                $instance->setTrigger($trigger);
                $instances[$class] = $instance;
            }
            else {
                $instance = $instances[$class];
            }
            
            $this->actionDebugMessage($action, $debugData);
            if(empty($action['parameters'])) {
                call_user_func([$instance, $method]);
                continue;
            }
            call_user_func([$instance, $method], json_decode($action['parameters']));
            if(json_last_error() != JSON_ERROR_NONE) {
                $this->getLogger()->error('E1171', 'Workflow Action: JSON Parameters for workflow action call could not be parsed with message: {msg}', [
                    'msg' => json_last_error_msg(),
                    'action' => $action
                ]);
            }
        }
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
     * @return editor_Workflow_Actions_Config
     */
    protected function getActionConfig() {
        $config = ZfExtended_Factory::get('editor_Workflow_Actions_Config');
        /* @var $config editor_Workflow_Actions_Config */
        $config->workflow = $this->workflow;
        $config->newTua = $this->newTaskUserAssoc;
        $config->oldTua = $this->oldTaskUserAssoc;
        $config->oldTask = $this->oldTask;
        $config->task = $this->newTask;
        $config->importConfig = $this->importConfig;
        $config->authenticatedUser = $this->authenticatedUserModel;
        return $config;
    }
    
    /**
     * is called on ending
     */
    protected function doEnd() {
        $this->handleEnd();
    }
    
    /**
     * is called on re opening a task
     */
    protected function doReopen() {
        $this->handleReopen();
    }
    
    /**
     * is called when a task assoc state gets OPEN
     */
    protected function doOpen() {
    }
    
    /**
     * is called when a task is opened coming from state unconfirmed
     */
    protected function doTaskConfirm() {
    }
    
    /**
     * is called when a user confirms his job (job was unconfirmed and is set to edit)
     * No handler functions for confirm available, everything is handled via actions
     */
    protected function doConfirm() {
        $stat = $this->calculateConfirm();
        $this->doDebug(__FUNCTION__.print_r($stat,1));
        
        $toTrigger = [];
        if($stat['roleFirstConfirmed']) {
            $toTrigger[] = 'handleFirstConfirmOfARole';
        }
        if($stat['firstConfirmed']) {
            $toTrigger[] = 'handleFirstConfirm';
        }
        if($stat['roleAllConfirmed']) {
            $toTrigger[] = 'handleAllConfirmOfARole';
        }
        if($stat['allConfirmed']) {
            $toTrigger[] = 'handleAllConfirm';
        }
        $toTrigger[] = 'handleConfirm';
        
        $newTua = $this->newTaskUserAssoc;
        $oldStep = $this->newTask->getWorkflowStepName();
        foreach($toTrigger as $trigger) {
            $this->doDebug($trigger);
            $this->callActions($trigger, $oldStep, $newTua->getRole(), $newTua->getState());
        }
    }
    
    /**
     * is called when a task changes via API
     */
    protected function doTaskChange() {
        $function = 'handleTaskChange';
        $this->doDebug($function);
        try {
            $tua =editor_Models_Loaders_Taskuserassoc::loadByTask($this->authenticatedUser->userGuid, $this->newTask);
            $this->callActions($function, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->callActions($function, $this->newTask->getWorkflowStepName());
        }
    }
    
    /**
     * Sets the new workflow step in the given task and increases by default the workflow step nr
     * @param editor_Models_Task $task
     * @param string $stepName
     */
    protected function setNextStep(editor_Models_Task $task, $stepName) {
        //store the nextStepWasSet per taskGuid,
        // so this mechanism works also when looping over different tasks with the same workflow instance
        $this->nextStepWasSet[$task->getTaskGuid()] = [
            'oldStep' => $task->getWorkflowStepName(),
            'newStep' => $stepName,
        ];
        $this->doDebug(__FUNCTION__.': workflow next step "{newStep}"; oldstep: "{oldStep}"', $this->nextStepWasSet[$task->getTaskGuid()], true);
        $task->updateWorkflowStep($stepName, true);
        //call action directly without separate handler method
        $newTua = $this->newTaskUserAssoc;
        $this->callActions('handleSetNextStep', $stepName, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * is called on finishin a task
     * evaluates the role and states of the User Task Association and calls the matching handlers:
     */
    protected function doFinish() {
        $stat = $this->calculateFinish();
        $this->doDebug(__FUNCTION__.print_r($stat,1));
        
        if($stat['roleFirstFinished']) {
            $this->handleFirstFinishOfARole();
        }
        if($stat['firstFinished']) {
            $this->handleFirstFinish();
        }
        if($stat['roleAllFinished']) {
            $this->handleAllFinishOfARole();
        }
        if($stat['allFinished']) {
            $this->handleAllFinish();
        }
        $this->handleFinish();
    }
    
    /**
     * calculates which of the "finish" handlers can be called accordingly to the currently existing tuas of a task
     * @return boolean[]
     */
    protected function calculateFinish() {
        $userTaskAssoc = $this->newTaskUserAssoc;
        $stat = $userTaskAssoc->getUsageStat();
        //we have to initialize $allFinished with true for proper working but with false if there is no tua at all
        $allFinished = !empty($stat);
        
        //we have to initialize $roleAllFinished with true for proper working but with false if there is no tua with the current tuas role
        $usedRoles = array_column($stat, 'role');
        $roleAllFinished = in_array($userTaskAssoc->getRole(), $usedRoles);
        $roleFirstFinished = false;
        $sum = 0;
        foreach($stat as $entry) {
            $isRole = $entry['role'] === $userTaskAssoc->getRole();
            $isFinish = $entry['state'] === $this->workflow::STATE_FINISH;
            if($isRole && $roleAllFinished && ! $isFinish) {
                $roleAllFinished = false;
            }
            if($allFinished && ! $isFinish) {
                $allFinished = false;
            }
            if($isRole && $isFinish && (int)$entry['cnt'] === 1) {
                $roleFirstFinished = true;
            }
            if($isFinish) {
                $sum += (int)$entry['cnt'];
            }
        }
        return [
            'allFinished' => $allFinished,
            'roleAllFinished' => $roleAllFinished,
            'roleFirstFinished' => $roleFirstFinished,
            'firstFinished' => $sum === 1,
        ];
    }
    
    /**
     * Calculates the workflow step confirmation status
     * Warning: this function may only be called in doConfirm (which is called if there was a state unconfirmed which is now set to edit)
     *  For all other usages the calculation will not be correct, since we don't know if a state was unconfirmed before,
     *  we see only that all states are now not unconfirmed.
     */
    protected function calculateConfirm() {
        $userTaskAssoc = $this->newTaskUserAssoc;
        $stat = $userTaskAssoc->getUsageStat();
        $sum = 0;
        $roleSum = 0;
        $otherSum = 0;
        $roleUnconfirmedSum = 0;
        foreach($stat as $entry) {
            $sum += (int)$entry['cnt'];
            $isRole = $entry['role'] === $userTaskAssoc->getRole();
            $isUnconfirmed = $entry['state'] === $this->workflow::STATE_UNCONFIRMED;
            if($isRole) {
                $roleSum += (int)$entry['cnt'];
                if($isUnconfirmed) {
                    $roleUnconfirmedSum += (int)$entry['cnt'];
                }
            }
            if(!$isUnconfirmed) {
                $otherSum += (int)$entry['cnt'];
            }
        }
        return [
            'allConfirmed' => $sum > 0 && $otherSum === $sum,
            'roleAllConfirmed' => $roleUnconfirmedSum === 0,
            'roleFirstConfirmed' => $roleSum - 1 === $roleUnconfirmedSum,
            //firstConfirmed is working only if really all other jobs are unconfirmed, what is seldom, since the other states will be waiting / finished etc.
            'firstConfirmed' => $otherSum === 1,
        ];
    }
    
    /**
     * is called on wait for a task
     */
    protected function doWait() {
        
    }
    
    /**
     * is called on reopening / unfinishing a task
     */
    protected function doUnfinish() {
        $this->handleUnfinish();
    }
    
    /**
     * debugging workflow
     * @param string $msg
     * @param array $data optional debuggin data
     * @param bool $levelInfo optional, if true log in level info instead debug
     */
    protected function doDebug($msg, array $data = [], $levelInfo = false) {
        $log = $this->getLogger();
        
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
    
    /**
     * returns either a task specific workflow logger or the native one
     * @return ZfExtended_Logger
     */
    protected function getLogger(): ZfExtended_Logger {
        if(empty($this->newTask)) {
            return Zend_Registry::get('logger')->cloneMe('editor.workflow');
        }
        $taskGuid = $this->newTask->getTaskGuid();
        //without that data no loggin is possible
        if(empty($taskGuid)) {
            return Zend_Registry::get('logger')->cloneMe('editor.workflow');
        }
        //get the logger for the task
        if(empty($this->log[$taskGuid])) {
            $this->log[$taskGuid] = ZfExtended_Factory::get('editor_Logger_Workflow', [$this->newTask]);
        }
        return $this->log[$taskGuid];
    }
    
    /**
     * triggers a beforeSTATE event
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     */
    public function triggerBeforeEvents(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua) {
        $state = $this->getTriggeredState($oldTua, $newTua, 'before');
        $this->events->trigger($state, $this->workflow, array('oldTua' => $oldTua, 'newTua' => $newTua));
    }
    
    /**
     * method returns the triggered state as string ready to use in events, these are mainly:
     * doUnfinish, doView, doEdit, doFinish, doWait, doConfirm
     * beforeUnfinish, beforeView, beforeEdit, beforeFinish, beforeWait
     *
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     * @param string $prefix optional, defaults to "do"
     * @return string
     */
    protected function getTriggeredState(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua, $prefix = 'do') {
        $oldState = $oldTua->getState();
        $newState = $newTua->getState();
        if($oldState == $newState) {
            return null;
        }
        
        if($oldState == $this->workflow::STATE_FINISH && $newState != $this->workflow::STATE_FINISH) {
            return $prefix.'Unfinish';
        }
        
        if($oldState == $this->workflow::STATE_UNCONFIRMED && $newState == $this->workflow::STATE_EDIT) {
            return $prefix.'Confirm';
        }
        
        switch($newState) {
            case $this->workflow::STATE_OPEN:
                return $prefix.'Open';
            case $this->workflow::STATE_VIEW:
                return $prefix.'View';
            case $this->workflow::STATE_EDIT:
                return $prefix.'Edit';
            case $this->workflow::STATE_FINISH:
                return $prefix.'Finish';
            case $this->workflow::STATE_WAITING:
                return $prefix.'Wait';
        }
        return null;
    }
}