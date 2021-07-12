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
    /**
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
    
    const CACHE_KEY             = 'workflow_definitions_';
    
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
    
    /***
     * the defined steps can not be assigned as workflow step
     * @var array
     */
    protected $notAssignableSteps = [self::STEP_PM_CHECK,self::STEP_NO_WORKFLOW,self::STEP_WORKFLOW_ENDED];
    
    /**
     * the default workflow handler instance
     * @var editor_Workflow_Default_Hooks
     */
    protected $hookin;
    
    /**
     * the segment handler instance
     * @var editor_Workflow_Default_SegmentHandler
     */
    protected $segmentHandler;
    
    /**
     * The workflow definition in a cachable manner
     * @var editor_Workflow_CachableDefinition
     */
    protected $definition;
    
    /**
     * Holds workflow log instance per affected task
     * Accesable via doDebug method
     * @var array
     */
    protected $log = [];
    
    public function __construct($name) {
        
        $cache = Zend_Registry::get('cache');
        $this->definition = $cache->load(self::CACHE_KEY.$name);
        if($this->definition === false) {
            /* @var $def editor_Workflow_CachableDefinition */
            $this->definition = ZfExtended_Factory::get('editor_Workflow_CachableDefinition');
            $workflow = $this->initWorkflow($name);
            $this->initWorkflowSteps($workflow);
            $cache->save($this->definition, self::CACHE_KEY.$name);
            $this->checkForMissingConfiguration();
        }
        
        $this->hookin = ZfExtended_Factory::get('editor_Workflow_Default_Hooks',[$this]);
        $this->segmentHandler = ZfExtended_Factory::get('editor_Workflow_Default_SegmentHandler',[$this]);
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
        
        $this->definition->name = $workflow->getName();
        $this->definition->label = $workflow->getLabel();
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
        $this->definition->stepChain[] = self::STEP_NO_WORKFLOW;

        foreach($steps as $step) {
            if(!is_null($step['position'])) {
                $this->definition->stepChain[] = $step['name'];
            }
            $this->definition->steps2Roles[$step['name']] = $step['role'];
            
            if($step['flagInitiallyFiltered']) {
                $this->definition->stepsWithFilter[] = $step['name'];
            }
            $constName = 'STEP_'.strtoupper($step['name']);
            $this->definition->labels[$constName] = $step['label'];
            $this->definition->steps[$constName] = $step['name'];
        }
        $this->definition->stepChain[] = self::STEP_WORKFLOW_ENDED;
        
        //calculate the valid states
        $this->initValidStates();
    }
    
    /**
     * initializes the workflows valid states
     */
    protected function initValidStates() {
        foreach($this->definition->stepChain as $step) {
            $this->definition->validStates[$step] = [];
            foreach($this->getAssignableSteps() as $assignableStep) {
                if(!in_array($assignableStep, $this->definition->stepChain)) {
                    //for steps not in the chain we can not calculate valid states, they always have to be configured manually in the GUI
                    continue;
                }
                $compared = $this->compareSteps($step, $assignableStep);
                if($compared === 0) {
                    $this->definition->validStates[$step][$assignableStep] = [self::STATE_OPEN, self::STATE_EDIT, self::STATE_VIEW, self::STATE_UNCONFIRMED];
                }
                elseif($compared > 0) {
                    $this->definition->validStates[$step][$assignableStep] = [self::STATE_FINISH];
                }
                else {
                    $this->definition->validStates[$step][$assignableStep] = [self::STATE_WAITING, self::STATE_UNCONFIRMED];
                }
            }
        }
    }

    /**
     * checks if the configuration for the current workflow definition does exist, if not it is created
     */
    protected function checkForMissingConfiguration() {
        /* @var $config Zend_Config */
        $config = Zend_Registry::get('config');
        /* @var $configModel editor_Models_Config */
        $configModel = ZfExtended_Factory::get('editor_Models_Config');

        foreach($this->definition->steps as $key => $step) {
            if(isset($config->runtimeOptions->workflow->{$this->definition->name}->$step)){
               continue;
            }
            $configModel->init([
                'name' => join('.', [
                    'runtimeOptions.workflow',
                    $this->definition->name,
                    $step,
                    'defaultDeadlineDate',
                ]),
                'confirmed' => 1,
                'module' => 'editor',
                'category' => 'workflow',
                'type' => 'float',
                'description' => 'The config defines, how many days the deadline should be in the future based on the order date',
                'level' => 4,
                'guiName' => sprintf('Default deadline date: workflow:%1$s,step:%2$s', $this->definition->label, $this->definition->labels[$key]),
                'guiGroup' => 'Workflow',
            ]);
            try {
                $configModel->save();
            }
            catch(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
                //do nothing if the config does exist already (probably created by a concurrent process)
            }
        }
    }
    
    /**
     * returns a reference to the instance containing all the workflow hook in functions
     * @return editor_Workflow_Default_Hooks
     */
    public function hookin(): editor_Workflow_Default_Hooks {
        return $this->hookin;
    }
    
    /**
     * returns a reference to the instance containing all the workflow handler functions
     * @return editor_Workflow_Default_SegmentHandler
     */
    public function getSegmentHandler(): editor_Workflow_Default_SegmentHandler {
        return $this->segmentHandler;
    }
    
    /**
     * @return array
     */
    public function getValidStates(): array {
        return $this->definition->validStates;
    }
    
    /**
     * returns the workflow name used in translate5
     * @return string
     */
    public function getName(): string {
        return $this->definition->name;
    }
    
    /**
     * returns the workflow label for the workflow (untranslated)
     * @return string
     */
    public function getLabel(): string {
        return $this->definition->label;
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
        return $this->definition->steps2Roles;
    }
    
    /**
     * returns the workflow steps which should have initially an activated segment filter
     * @return string[]
     */
    public function getStepsWithFilter() {
        return $this->definition->stepsWithFilter;
    }
    
    /**
     * returns the initial states of the different roles in the different steps
     * @return string[][]
     */
    public function getInitialStates() {
        $result = [];
        foreach($this->definition->validStates as $step => $statesToSteps) {
            $result[$step] = [];
            foreach($statesToSteps as $stepInner => $states) {
                //the initial states for NO WORKFLOW is always OPEN so that the first assigned job is added as open,
                //  then the workflow changes and all following jobs are getting their calculated state then
                if($step === self::STEP_NO_WORKFLOW) {
                    $result[$step][$stepInner] = self::STATE_OPEN;
                }
                else {
                    //the initial state per role is just the first defined state per role
                    $result[$step][$stepInner] = reset($states);
                }
            }
        }
        return $result;
    }
    
    /**
     * Returns next step in stepChain, or STEP_WORKFLOW_ENDED if for nextStep no users are associated
     * @param mixed $step string or null
     * @return string $step or null if the step does not exist
     */
    public function getNextStep(editor_Models_Task $task, string $step): ?string {
        /* @var $tua editor_Models_TaskUserAssoc */
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        
        //get used roles in task:
        $tuas = $tua->loadByTaskGuidList([$task->getTaskGuid()]);
        
        $stepChain = array_values($this->getStepChain());
        $stepCount = count($stepChain);
        
        $position = array_search($step, $stepChain);
        
        // if the current step is not found in the chain or
        // if there are no jobs the workflow should be ended then
        // (normally we never reach here since to change the workflow at least one job is needed)
        if($position === false || empty($tuas)) {
            return self::STEP_WORKFLOW_ENDED;
        }
        
        //get just the associated steps from the jobs
        $stepsAssociated = array_column($tuas, 'workflowStepName');
        $stepsAssociated = array_unique($stepsAssociated);
        
        //we want the position of the next step, not the current one:
        $position++;
        
        //loop over all steps after the current one
        for ($position; $position < $stepCount; $position++) {
            if(in_array($stepChain[$position], $stepsAssociated)) {
                //the first one with associated users is returned
                return $stepChain[$position];
            }
        }
        
        //if no next step is found, it is ended by definition
        return self::STEP_WORKFLOW_ENDED;
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
        return $this->definition->stepChain;
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
        return array_merge($this->getFilteredConstants('STEP_'), $this->definition->steps);
    }
    
    /**
     * Return only the assignable workflow steps.
     * @return array
     */
    public function getAssignableSteps(): array {
        $result = array_diff($this->getSteps(), $this->notAssignableSteps);
        uasort($result, [$this, 'compareSteps']);
        return $result;
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
            return $this->definition->labels;
        }
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        return array_map(function($label) use ($t) {
            return $t->_($label);
        }, $this->definition->labels);
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
    
    public function getStepRecalculation(): editor_Workflow_Default_StepRecalculation {
        return ZfExtended_Factory::get('editor_Workflow_Default_StepRecalculation', [$this]);
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
     * return <0 if stepTwo is before stepOne in the stepChain, >0 if stepTwo is after stepOne and 0 if the steps are equal.
     * @param string $stepOne
     * @param string $stepTwo
     * @return integer
     */
    public function compareSteps(string $stepOne, string $stepTwo): int {
        if($stepOne === $stepTwo) {
            return 0;
        }
        return array_search($stepOne, $this->definition->stepChain, true) - array_search($stepTwo, $this->definition->stepChain, true);
    }
    
    /**
     * uses given array as keys and adds the corresponding internal labels as values
     */
    public function labelize(array $data) {
        $labels = $this->getLabels();
        $usedLabels = array_intersect_key($labels, $data);
        ksort($usedLabels);
        ksort($data);
        if(count($data) !== count($usedLabels)) {
            // {className}::$labels has to much / or missing labels!',
            throw new editor_Workflow_Exception('E1253', ['data'=>$data,'usedLabels'=>$usedLabels,'workflowId' => $this->definition->name]);
        }
        return array_combine($data, $usedLabels);
    }
    
    /**
     * returns either a task specific workflow logger or the native one
     * @return ZfExtended_Logger
     */
    public function getLogger(editor_Models_Task $task = null): ZfExtended_Logger {
        if(empty($task)) {
            return Zend_Registry::get('logger')->cloneMe('editor.workflow');
        }
        $taskGuid = $task->getTaskGuid();
        //without that data no loggin is possible
        if(empty($taskGuid)) {
            return Zend_Registry::get('logger')->cloneMe('editor.workflow');
        }
        //get the logger for the task
        if(empty($this->log[$taskGuid])) {
            $this->log[$taskGuid] = ZfExtended_Factory::get('editor_Logger_Workflow', [$task]);
        }
        return $this->log[$taskGuid];
    }
}