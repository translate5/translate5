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
 * Default Workflow Class, contains the workflow definition and workflow state, all handlers/actions etc are in the handler instance
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
    protected $validStates = [];
    
    /***
     * the defined steps can not be assigned as workflow step
     * @var array
     */
    protected $notAssignableSteps = [self::STEP_PM_CHECK,self::STEP_NO_WORKFLOW,self::STEP_WORKFLOW_ENDED];
    
    /**
     * the default workflow handler instance
     * @var editor_Workflow_DefaultHandler
     */
    protected $handler;
    
    public function __construct($name) {
    //FIXME use $name to load workflow and steps, store them internally cached somehow. As zend cache?
        $workflow = $this->initWorkflow($name);
        $this->initWorkflowSteps($workflow);
        $this->handler = ZfExtended_Factory::get('editor_Workflow_DefaultHandler',[$this]);
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
        
        //calculate the valid states
        foreach($this->stepChain as $step) {
            $this->validStates[$step] = [];
            foreach($this->getAssignableSteps() as $assignableStep) {
                if(!in_array($assignableStep, $this->stepChain)) {
                    //for steps not in the chain we can not calculate valid states, they always have to be configured manually in the GUI
                    continue;
                }
                $compared = $this->compareSteps($step, $assignableStep);
                if($compared === 0) {
                    $this->validStates[$step][$assignableStep] = [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW, self::STATE_UNCONFIRMED];
                }
                elseif($compared > 0) {
                    $this->validStates[$step][$assignableStep] = [self::STATE_WAITING, self::STATE_UNCONFIRMED];
                }
                else {
                    $this->validStates[$step][$assignableStep] = [self::STATE_FINISH];
                }
            }
        }
    }
    
    /**
     * returns a reference to the instance containing all the workflow handler functions
     * @return editor_Workflow_DefaultHandler
     */
    public function getHandler(): editor_Workflow_DefaultHandler {
        return $this->handler;
    }
    
    /**
     * @return array
     */
    public function getValidStates(): array {
        return $this->validStates;
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
        foreach($this->validStates as $step => $statesToSteps) {
            $result[$step] = [];
            foreach($statesToSteps as $stepInner => $states) {
                //the initial state per role is just the first defined state per role
                $result[$step][$stepInner] = reset($states);
            }
        }
        return $result;
    }
    
    /**
     * Returns next step in stepChain, or STEP_WORKFLOW_ENDED if for nextStep no users are associated
     * @param mixed $step string or null
     * @return string $role OR false if step does not exist
     */
    public function getNextStep(editor_Models_Task $task, string $step) {
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
        $tuas = $tua->loadByTaskGuidList([$task->getTaskGuid()]);
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
     * returns a subset of getAssignableSteps, respecting the rights of the current user filtering the steps which are not allowed to be used by the current user in task user associations
     * @return array
     */
    public function getUsableSteps(): array {
        $steps = $this->getAssignableSteps();
        //FIXME instead of checking the roles a user have,
        //this must come from ACL table analogous to setaclrole, use a setwfrole then
        $user = new Zend_Session_Namespace('user');
        if(in_array(ACL_ROLE_PM, $user->data->roles)) {
            return $steps;
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
     * return <0 if stepOne is before stepTwo in the stepChain, >0 if stepOne is after stepTwo and 0 if the steps are equal.
     * @param string $stepOne
     * @param string $stepTwo
     * @return integer
     */
    public function compareSteps(string $stepOne, string $stepTwo): int {
        if($stepOne === $stepTwo) {
            return 0;
        }
        return array_search($stepTwo, $this->stepChain, true) - array_search($stepOne, $this->stepChain, true);
    }
}