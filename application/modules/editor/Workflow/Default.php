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
 * Default Workflow Class, contains the workflow definition and a reference to the handlers
 * Default roles are:
 * - translator
 * - reviewer
 * - translatorCheck
 * - visitor
 * Default states are waiting, finished, open, edit, view and unconfirmed
 * Basic steps (always available) are
 * - 'no workflow' as initial step
 * - pmCheck for PM usage
 * - workflowEnded as final step
 * All other steps are loaded from the database step configuration list
 */
class editor_Workflow_Default {
    /*
     * STATES: states describe on the one side the actual state between a user and a task
     *         on the other side changing a state can trigger specific actions on the server
     * currently we have 3 places to define userStates: IndexController
     * for translation, JS Task Model and workflow for programmatic usage
     */
    //the user cant access the task yet
    const STATE_WAITING         = 'waiting';
    //the user has finished his work on this task, and cant access it anymore
    const STATE_FINISH          = 'finished';
    //the user can access the task editable and writeable,
    //setting this state releases the lock if the user had locked the task
    const STATE_OPEN            = 'open';
    //this state must be set on editing a task, it locks the task for the user
    const STATE_EDIT            = 'edit';
    //setting this state opens the task readonly
    const STATE_VIEW            = 'view';
    //the user can access the task readable, must confirm it before usage
    const STATE_UNCONFIRMED     = 'unconfirmed';
    
    const ROLE_TRANSLATOR       = 'translator';
    const ROLE_REVIEWER         = 'reviewer';
    const ROLE_TRANSLATORCHECK  = 'translatorCheck';
    const ROLE_VISITOR          = 'visitor';
    
    /*
     ** The following hard coded steps are always needed / or are out of workflow:
     */
    const STEP_NO_WORKFLOW      = 'no workflow';
    const STEP_PM_CHECK         = 'pmCheck';
    const STEP_WORKFLOW_ENDED   = 'workflowEnded';
    
    /**
     * The workflow name
     * @var string
     */
    protected $name;
    
    /**
     * The workflow label (untranslated)
     * @var string
     */
    protected $label;
    
    /**
     * labels of the states, roles and steps. Can be changed / added in constructor
     * @var array
     */
    protected $labels = array(
        'STATE_IMPORT' => 'import',
        'STATE_WAITING' => 'wartend',
        'STATE_UNCONFIRMED' => 'unbestätigt',
        'STATE_FINISH' => 'abgeschlossen',
        'STATE_OPEN' => 'offen',
        'STATE_EDIT' => 'selbst in Arbeit',
        'STATE_VIEW' => 'selbst geöffnet',
        'ROLE_TRANSLATOR' => 'Übersetzer',
        'ROLE_REVIEWER' => 'Lektor',
        'ROLE_TRANSLATORCHECK' => 'Zweiter Lektor',
        'ROLE_VISITOR' => 'Besucher',
        'STEP_NO_WORKFLOW' => 'Kein Workflow',
        'STEP_PM_CHECK' => 'PM Prüfung',
        'STEP_WORKFLOW_ENDED' => 'Workflow abgeschlossen',
    );
    
    /**
     * This part is very ugly: in the frontend we are working only with all states expect the ones listed here.
     * The states listed here are only used in the frontend grid for rendering purposes,
     * they are not used to be activly set to a user, or to be filtered etc. pp.
     * So we define them as "pending" states, which have to be delivered in a separate matter to the frontend
     * The values are a subset of the above STATE_CONSTANTs
     * @var array
     */
    protected $pendingStates = array(self::STATE_EDIT, self::STATE_VIEW);
    
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
     * Container for new User Task State provided by doWithUserAssoc
     * @var string
     */
    protected $newUtaState;
    
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
     * lists all roles with read access to tasks
     * @var array
     */
    protected $readableRoles = array(
        self::ROLE_VISITOR,
        self::ROLE_REVIEWER,
        self::ROLE_TRANSLATOR,
        self::ROLE_TRANSLATORCHECK,
    );
    /**
     * lists all roles with write access to tasks
     * @var array
     */
    protected $writeableRoles = array(
        self::ROLE_REVIEWER,
        self::ROLE_TRANSLATOR,
        self::ROLE_TRANSLATORCHECK,
    );
    /**
     * lists all states which allow read access to tasks
     * @todo readableStates and writeableStates have to be changed/extended to a modelling of state transitions
     * @var array
     */
    protected $readableStates = array(
        self::STATE_UNCONFIRMED,
        self::STATE_WAITING,
        self::STATE_FINISH,
        self::STATE_OPEN,
        self::STATE_EDIT,
        self::STATE_VIEW
    );
    /**
     * lists all states which allow write access to tasks
     * @var array
     */
    protected $writeableStates = array(
        //although the task is readonly in state unconfirmed, we have to add the state here,
        // to allow changing from unconfirmed to edit (which then means to confirm)
        self::STATE_UNCONFIRMED,
        self::STATE_OPEN,
        self::STATE_EDIT,
    );
    
    /**
     * workflow steps which are part of the workflow chain (in this order)
     * @var array
     */
    protected $stepChain = [];
    
    /**
     * Mapping between workflowSteps and roles
     * @var array
     */
    protected $steps2Roles = [];
    
    /**
     * Loaded steps from DB, key is STEP_STEPNAME value is the step value (similar to the STEP_ constants)
     * @var array
     */
    protected $steps = [];
    
    /**
     * list of steps with flag flagInitiallyFiltered on
     * @var array
     */
    protected $stepsWithFilter = [];
    
    /**
     * Valid state / role combination for each step
     * the first state of the states array is also the default state for that step and role
     * @var array
     */
    protected $validStates = [
//         self::STEP_TRANSLATION => [
//             self::ROLE_TRANSLATOR => [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW, self::STATE_UNCONFIRMED],
//             self::ROLE_REVIEWER => [self::STATE_WAITING, self::STATE_UNCONFIRMED],
//             self::ROLE_TRANSLATORCHECK => [self::STATE_WAITING, self::STATE_UNCONFIRMED],
//         ],
//         self::STEP_REVIEWING => [
//             self::ROLE_TRANSLATOR => [self::STATE_FINISH],
//             self::ROLE_REVIEWER => [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW, self::STATE_UNCONFIRMED],
//             self::ROLE_TRANSLATORCHECK => [self::STATE_WAITING, self::STATE_UNCONFIRMED],
//         ],
//         self::STEP_TRANSLATORCHECK => [
//             self::ROLE_TRANSLATOR => [self::STATE_FINISH],
//             self::ROLE_REVIEWER => [self::STATE_FINISH],
//             self::ROLE_TRANSLATORCHECK => [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW, self::STATE_UNCONFIRMED],
//         ],
        self::STEP_WORKFLOW_ENDED => [
            self::ROLE_TRANSLATOR => [self::STATE_FINISH],
            self::ROLE_REVIEWER => [self::STATE_FINISH],
            self::ROLE_TRANSLATORCHECK => [self::STATE_FINISH],
        ],
    ];
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /**
     * determines if calls were done by cronjob
     * @var boolean
     */
    protected $isCron = false;
    
    protected $validDirectTrigger = [
            'notifyAllUsersAboutTaskAssociation',
    ];
    
    protected $nextStepWasSet = [];

    /**
     * Holds workflow log instance per affected task
     * Accesable via doDebug method
     * @var array
     */
    protected $log = [];
    
    
    /***
     * the defined steps can not be assigned as workflow step
     * @var array
     */
    protected $notAssignableSteps=[self::STEP_PM_CHECK,self::STEP_NO_WORKFLOW,self::STEP_WORKFLOW_ENDED];
    
    protected $handler;
    
    public function __construct($name) {
    //FIXME use $name to load workflow and steps, store them internally cached somehow. As zend cache?
        $workflow = $this->initWorkflow($name);
        $this->initWorkflowSteps($workflow);
        $this->handler = ZfExtended_Factory::get('editor_Workflow_DefaultHandler',[$this]);
        
        $this->loadAuthenticatedUser();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        
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
     * loads the workflow entity by name and stores name and label internally
     * @param string $name
     * @return editor_Models_Workflow
     */
    protected function initWorkflow(string $name): editor_Models_Workflow {
        /* @var $workflow editor_Models_Workflow */
        $workflow = ZfExtended_Factory::get('editor_Models_Workflow');
        $workflow->loadByName($name);
        
        $this->name = $workflow->getName();
        $this->label = $workflow->getLabel();
        return $workflow;
    }
    
    /**
     * loads all workflow steps and stores them in the chain and in the steps to roles mapping
     * @param editor_Models_Workflow $workflow
     */
    protected function initWorkflowSteps(editor_Models_Workflow $workflow) {
        /* @var $step editor_Models_Workflow_Step */
        $step = ZfExtended_Factory::get('editor_Models_Workflow_Step');
        $steps = $step->loadByWorkflow($workflow);
        
        //if position is null, the step is not in the chain!
        //the workflow starts always with no_workflow and ends with workflow ended
        //the step2roles array contains all configured steps, assignable to users
        $this->stepChain[] = self::STEP_NO_WORKFLOW;
        foreach($steps as $step) {
            if(!is_null($step['position'])) {
                $this->stepChain[] = $step['name'];
            }
            $this->steps2Roles[$step['name']] = $step['role'];
            
            if($step['flagInitiallyFiltered']) {
                $this->stepsWithFilter[] = $step['name'];
            }
            $constName = 'STEP_'.strtoupper($step['name']);
            $this->labels[$constName] = $step['label'];
            $this->steps[$constName] = $step['name'];
        }
        $this->stepChain[] = self::STEP_WORKFLOW_ENDED;
    }
    
    /**
     * returns a reference to the instance containing all the workflow handler functions
     * @return editor_Workflow_DefaultHandler
     */
    public function getHandler(): editor_Workflow_DefaultHandler {
        return $this->handler;
    }
    
    /**
     * returns the workflow name used in translate5
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
    
    /**
     * returns the workflow label for the workflow (untranslated)
     * @return string
     */
    public function getLabel(): string {
        return $this->label;
    }
    
    /**
     * returns true if the workflow methods were triggered by a cron job and no direct user/API interaction
     * @return boolean
     */
    public function isCalledByCron() {
        return $this->isCron;
    }
    
    /**
     * returns true if the given task is ended regarding its workflow
     * @param editor_Models_Task $task
     * @return bool
     */
    public function isEnded(editor_Models_Task $task): bool {
        return $task->getWorkflowStepName() == self::STEP_WORKFLOW_ENDED;
    }
    
    /**
     * returns the step to roles mapping
     * @return array
     */
    public function getSteps2Roles() {
        return $this->steps2Roles;
    }
    
    /**
     * returns the workflow steps which should have initially an activated segment filter
     * @return string[]
     */
    public function getStepsWithFilter() {
        return $this->stepsWithFilter;
    }
    
    /**
     * returns the initial states of the different roles in the different steps
     * @return string[][]
     */
    public function getInitialStates() {
        $result = [];
        foreach($this->validStates as $step => $statesToRoles) {
            $result[$step] = [];
            foreach($statesToRoles as $role => $states) {
                //the initial state per role is just the first defined state per role
                $result[$step][$role] = reset($states);
            }
        }
        return $result;
    }
    
    /**
     * Returns next step in stepChain, or STEP_WORKFLOW_ENDED if for nextStep no users are associated
     * @param mixed $step string or null
     * @return string $role OR false if step does not exist
     */
    public function getNextStep($step) {
        $stepChain = $this->getStepChain();
        
        //if no next step is found, we return false
        $nextStep = false;
        
        $position = array_search($step, $stepChain);
        if (isset($stepChain[$position + 1])) {
            $nextStep = $stepChain[$position + 1];
        }
        
        //1. get used roles in task:
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $tuas = $tua->loadByTaskGuidList([$this->newTask->getTaskGuid()]);
        if(!empty($tuas)) {
            $steps = array_column($tuas, 'workflowStepName');
            $steps = array_unique($steps);
            // 2. check if roles of given nextStep are associated to the task
            if(! in_array($nextStep, $steps)) {
                //3. if not, set nextStep to workflow ended
                return self::STEP_WORKFLOW_ENDED;
            }
        }
        
        return $nextStep;
    }
    
    /**
     * @param mixed $step string
     * @return string $role OR false if step does not exist
     */
    public function getRoleOfStep(string $step) {
        $steps2Roles = $this->getSteps2Roles();
        if(isset($steps2Roles[$step])) {
            return $steps2Roles[$step];
        }
        return false;
    }
    
    /**
     * returns the available step values
     * @return array
     */
    public function getStepChain() {
        return $this->stepChain;
    }
    
    /**
     * return the states defined as pending (is a subset of the getStates result)
     * @return array
     */
    public function getPendingStates() {
        return array_intersect($this->getStates(), $this->pendingStates);
    }

    /**
     * returns true if the given step is of one of the given roles
     * @param string $step
     * @param array $roles
     * @return boolean
     */
    public function isStepOfRole(string $step, array $roles): bool {
        return in_array($this->getRoleOfStep($step), $roles);
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
     *
     * @return array of available step constants (keys are constants, valus are constant-values)
     */
    public function getSteps(){
        return array_merge($this->getFilteredConstants('STEP_'), $this->steps);
    }
    
    /**
     * Return only the assignable workflow steps.
     * @return array
     */
    public function getAssignableSteps(){
        return array_diff($this->getSteps(), $this->notAssignableSteps);
    }
    
    /**
     *
     * @return array of available role constants (keys are constants, valus are constant-values)
     */
    public function getRoles(){
        return $this->getFilteredConstants('ROLE_');
    }
    
    /**
     * returns an array of wf roles which are allowed by the current user to be used in task user associations
     * @return array of for the authenticated user usable role constants (keys are constants, valus are constant-values)
     */
    public function getAddableRoles(){
        $roles = $this->getRoles();
        //FIXME instead of checking the roles a user have,
        //this must come from ACL table analogous to setaclrole, use a setwfrole then
        // check sub classes on refactoring too!
        $user = new Zend_Session_Namespace('user');
        if(in_array(ACL_ROLE_PM, $user->data->roles)) {
            return $roles;
        }
        return [];
    }
    
    /**
     * returns the already translated labels as assoc array
     * @var boolean $translated optional, defaults to true
     * @return array
     */
    public function getLabels($translated = true) {
        if(!$translated) {
            return $this->labels;
        }
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        return array_map(function($label) use ($t) {
            return $t->_($label);
        }, $this->labels);
    }
    
    /**
     *
     * @param string $role
     * @return boolean
     */
    public function isRole(string $role){
        $roles = $this->getRoles();
        return in_array($role, $roles);
    }
    /**
     *
     * @param string $state
     * @return boolean
     */
    public function isState(string $state){
        $states = $this->getStates();
        return in_array($state, $states);
    }
    /**
     *
     * @return array of available state constants (keys are constants, valus are constant-values)
     */
    public function getStates(){
        return $this->getFilteredConstants('STATE_');
    }
    /**
     *
     * @param string $filter
     * @return array values are all constant values which names match filter
     */
    protected function getFilteredConstants(string $filter){
        $refl = new ReflectionClass($this);
        $consts = $refl->getConstants();
        $filtered = array();
        foreach ($consts as $const => $val) {
            if(strpos($const, $filter)!==FALSE){
                $filtered[$const] = $val;
            }
        }
        return $filtered;
    }
    /**
     * FIXME auf sinnvolle Weise umsetzen, dass workflowrechte ins frontend kommen
     * FIXME WorkflowRollen-Rechte-Mapping auf verallgemeinerte Weise umsetzen
     * @return array of role constants (keys are constants, valus are constant-values)
     */
    public function getReadableRoles() {
        return $this->readableRoles;
    }
    /**
     *
     * @return array of state constants (keys are constants, valus are constant-values)
     */
    public function getReadableStates() {
        return $this->readableStates;
    }
    /**
     *
     * @return array of role constants (keys are constants, valus are constant-values)
     */
    public function getWriteableRoles() {
        return $this->writeableRoles;
    }
    /**
     *
     * @return array of state constants (keys are constants, valus are constant-values)
     */
    public function getWriteableStates() {
        return $this->writeableStates;
    }
    
    /**
     * Returns the initial usage state to a workflow state
     * @param editor_Models_TaskUserAssoc $tua
     * @return string
     */
    public function getInitialUsageState(editor_Models_TaskUserAssoc $tua): string {
        if(in_array($tua->getState(), [self::STATE_UNCONFIRMED, self::STATE_WAITING, self::STATE_FINISH])) {
            return self::STATE_VIEW;
        }
        return self::STATE_EDIT;
    }

    /**
     * checks if the given TaskUserAssoc Instance allows reading of the task according to the Workflow Definitions
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param bool $useUsedState optional, per default false means using TaskUserAssoc field state, otherwise TaskUserAssoc field usedState
     * @return boolean
     */
    public function isReadable(editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        return $this->isTuaAllowed($this->getReadableRoles(), $this->getReadableStates(), $tua, $useUsedState);
    }
    
    /**
     * checks if the given TaskUserAssoc Instance allows writing to the task according to the Workflow Definitions
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param bool $useUsedState optional, per default false means using TaskUserAssoc field state, otherwise TaskUserAssoc field usedState
     * @return boolean
     */
    public function isWriteable(editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        return $this->isTuaAllowed($this->getWriteableRoles(), $this->getWriteableStates(), $tua, $useUsedState);
    }
    
    /**
     * FIXME this is a small ugly workaround for that fact that we do not differ
     * between state transitions and "whats allowed" in a state.
     * The isWriteable and isReadable methods are only used in conjunction with state
     * transitions, and so cannot be used with the desired behaviour here.
     * Here we want to know if a task can be written in the given state (which
     * is currently only edit). See TRANSLATE-7 and TRANSLATE-18.
     * @param string $userState
     * @return boolean
     */
    public function isWritingAllowedForState($userState) {
        return $userState == self::STATE_EDIT;
    }
    
    /**
     * helper function for isReadable and isWriteable
     * @param array $roles
     * @param array $states
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param bool $useUsedState
     * @return boolean
     */
    protected function isTuaAllowed(array $roles, array $states, editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        if(empty($tua)) {
            return false;
        }
        $state = $useUsedState ? $tua->getUsedState() : $tua->getState();
        return in_array($tua->getRole(), $roles) && in_array($state, $states);
    }
    
    /**
     * returns true if a normal user can change the state of this assoc, false otherwise.
     * false means that the user has finished this task already or the user is still waiting.
     * $userAssumedStateHeHas: should be the same as $taskUserAssoc->state, but comes in via API and represents the state which the client has.
     *  This may differ from the state in the TUA out of DB. Basicly this means the user should refresh his data.
     *
     * - does not look for the state of a task, only for state of taskUserAssoc
     *
     * @param editor_Models_TaskUserAssoc $taskUserAssoc
     * @param string $userAssumedStateHeHas
     * @return boolean
     */
    public function isStateChangeable(editor_Models_TaskUserAssoc $taskUserAssoc, $userAssumedStateHeHas) {
        $state = $taskUserAssoc->getState();
        //setting from unconfirmed to edit means implicitly that the user confirms the job
        // both values TUA.state in DB and state which has the user must be unconfirmed
        // this prevents that a user which as an old task overview (where he is not yet in unconfirmed mode)
        // automatically confirms the task by opening it via edit
        if($state == self::STATE_UNCONFIRMED) {
            // all other non edit states must leave the unconfirmed state
            // if the client in the GUI also was not on unconfirmed we have to leave it
            return $state == $userAssumedStateHeHas && $taskUserAssoc->getUsedState() == self::STATE_EDIT;
        }
        return !($state == self::STATE_FINISH || $state == self::STATE_WAITING);
    }

    /**
     * returns the possible start states for a transition to the target state
     * @param string $targetState
     * @return array
     */
    public function getAllowedTransitionStates($targetState) {
        if($targetState == self::STATE_OPEN){
            return array(self::STATE_EDIT, self::STATE_VIEW);
        }
        return array();
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
            $this->events->trigger("doTaskChange", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
            return; //saved some other attributes, do nothing
        }
        switch($newState) {
            case $newTask::STATE_OPEN:
                if($oldState == $newTask::STATE_END) {
                    $this->doReopen();
                    $this->events->trigger("doReopen", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                if($oldState == $newTask::STATE_UNCONFIRMED) {
                    $this->doTaskConfirm();
                    $this->events->trigger("doTaskConfirm", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                break;
            case $newTask::STATE_END:
                $this->doEnd();
                $this->events->trigger("doEnd", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
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
            $this->events->trigger($state, __CLASS__, array('oldTua' => $oldTua, 'newTua' => $newTua, 'task' => $task));
        }
        $this->handleUserAssociationEdited();
        $this->recalculateWorkflowStep($newTua);
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
        $actions = $actions->loadByTrigger([$this->name], $trigger, $step, $role, $state);
        $this->actionDebugMessage([$this->name], $debugData);
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
        $config->workflow = $this;
        $config->newTua = $this->newTaskUserAssoc;
        $config->oldTua = $this->oldTaskUserAssoc;
        $config->oldTask = $this->oldTask;
        $config->task = $this->newTask;
        $config->importConfig = $this->importConfig;
        $config->authenticatedUser = $this->authenticatedUserModel;
        return $config;
    }
    
    /**
     * recalculates the workflow step by the given task user assoc combinations
     * If the combination of roles and states are pointing to an specific workflow step, this step is used
     * If the states and roles does not match any valid combination, no step is changed.
     * @param editor_Models_TaskUserAssoc $tua
     *
     *
     */
    protected function recalculateWorkflowStep(editor_Models_TaskUserAssoc $tua) {
        $sendNotice = function($step) {
            $msg = ZfExtended_Factory::get('ZfExtended_Models_Messages');
            /* @var $msg ZfExtended_Models_Messages */
            $labels = $this->getLabels();
            $steps = $this->getSteps();
            $step = $labels[array_search($step, $steps)];
            $msg->addNotice('Der Workflow Schritt der Aufgabe wurde zu "{0}" geändert!', 'core', null, $step);
        };
        
        $taskGuid = $tua->getTaskGuid();
        
        //if the step was recalculated due setNextStep in internal workflow calculations,
        // we may not recalculate it here again!
        if(!empty($this->nextStepWasSet[$taskGuid])) {
            $sendNotice($this->nextStepWasSet[$taskGuid]['newStep']);
            return;
        }
        
        $tuas = $tua->loadByTaskGuidList([$taskGuid]);
        
        $areTuasSubset = function($toCompare, $currentStep) use ($tuas){
            $hasRoleToCurrentStep = false;
            foreach($tuas as $tua) {
                if(empty($toCompare[$tua['role']])) {
                    return false;
                }
                if(!in_array($tua['state'], $toCompare[$tua['role']])) {
                    return false;
                }
                $hasRoleToCurrentStep = $hasRoleToCurrentStep || (($this->steps2Roles[$currentStep] ?? '') == $tua['role']);
            }
            //we can only return true, if the Tuas contain at least one role belonging to the currentStep,
            // in other words we can not reset the task to reviewing, if we do not have a reviewer
            return $hasRoleToCurrentStep;
        };
        
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
            $matchingSteps[]=self::STEP_NO_WORKFLOW;
        }else{
            foreach($this->validStates as $step => $roleStates) {
                if(!$areTuasSubset($roleStates, $step)) {
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
        $sendNotice($step);
        return;
    }
    
    /**
     * triggers a beforeSTATE event
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     */
    public function triggerBeforeEvents(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua) {
        $state = $this->getTriggeredState($oldTua, $newTua, 'before');
        $this->events->trigger($state, __CLASS__, array('oldTua' => $oldTua, 'newTua' => $newTua));
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
        
        if($oldState == self::STATE_FINISH && $newState != self::STATE_FINISH) {
            return $prefix.'Unfinish';
        }
        
        if($oldState == self::STATE_UNCONFIRMED && $newState == self::STATE_EDIT) {
            return $prefix.'Confirm';
        }
        
        switch($newState) {
            case $this::STATE_OPEN:
                return $prefix.'Open';
            case $this::STATE_VIEW:
                return $prefix.'View';
            case $this::STATE_EDIT:
                return $prefix.'Edit';
            case self::STATE_FINISH:
                return $prefix.'Finish';
            case self::STATE_WAITING:
                return $prefix.'Wait';
        }
        return null;
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
     * Inits the workflow step in the given task
     * @param editor_Models_Task $task
     * @param string $stepName
     */
    protected function initWorkflowStep(editor_Models_Task $task, $stepName) {
        $this->doDebug('workflow init step to "{step}"', ['step' => $stepName], true);
        $task->updateWorkflowStep($stepName, false);
    }
    
    /*
    //DO Methods.
     the do.. methods
    - are called by doWithTask and doWithTaskUserAssoc, according to the changed states
    - can contain further logic to call different "handle" Methods, can also been overwritten
     */
    
    /**
     * is called directly after import
     * @param editor_Models_Task $importedTask
     */
    public function doImport(editor_Models_Task $importedTask, editor_Models_Import_Configuration $importConfig) {
        $this->newTask = $importedTask;
        $this->importConfig = $importConfig;
        $this->handleImport();
    }
    
    /**
     * is called after a user association is added
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function doUserAssociationAdd(editor_Models_TaskUserAssoc $tua) {
        $this->newTaskUserAssoc = $tua;
        $this->handleUserAssociationAdded();
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
        $wasNotFinished = ($originalState !== self::STATE_FINISH);
        $stat = $this->calculateFinish();
        $this->doDebug(__FUNCTION__. ' OriginalState: '.$originalState.'; Finish Stat: '.print_r($stat,1));
        if($wasNotFinished && $stat['roleAllFinished']) {
            //in order to trigger the actions correctly we have to assume that the deleted one was "finished"
            $tua->setState(self::STATE_FINISH);
            $this->handleAllFinishOfARole($stat);
        }
        if($wasNotFinished && $stat['allFinished']) {
            //in order to trigger the actions correctly we have to assume that the deleted one was "finished"
            $tua->setState(self::STATE_FINISH);
            $this->handleAllFinish($stat);
        }
        $tua->setState($originalState);
        $this->handleUserAssociationDeleted();
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
     * is called on finishin a task
     * evaluates the role and states of the User Task Association and calls the matching handlers:
     */
    protected function doFinish() {
        $stat = $this->calculateFinish();
        $this->doDebug(__FUNCTION__.print_r($stat,1));
        
        if($stat['roleFirstFinished']) {
            $this->handleFirstFinishOfARole($stat);
        }
        if($stat['firstFinished']) {
            $this->handleFirstFinish($stat);
        }
        if($stat['roleAllFinished']) {
            $this->handleAllFinishOfARole($stat);
        }
        if($stat['allFinished']) {
            $this->handleAllFinish($stat);
        }
        $this->handleFinish($stat);
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
            $isFinish = $entry['state'] === self::STATE_FINISH;
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
            $isUnconfirmed = $entry['state'] === self::STATE_UNCONFIRMED;
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
}