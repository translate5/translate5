<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Abstract Workflow Class
 */
abstract class editor_Workflow_Abstract {
    /*
     * STATES: states describe on the one side the actual state between a user and a task
     *         on the other side changing a state can trigger specific actions on the server
     * currently we have 3 places to define userStates: IndexController
     * for translation, JS Task Model and workflow for programmatic usage
     */
    //the user cant access the task yet
    const STATE_WAITING = 'waiting'; 
    //the user has finished his work on this task, and cant access it anymore
    const STATE_FINISH = 'finished'; 
    //the user can access the task editable and writeable, 
    //setting this state releases the lock if the user had locked the task
    const STATE_OPEN = 'open'; 
    //this state must be set on editing a task, it locks the task for the user
    const STATE_EDIT = 'edit'; 
    //setting this state opens the task readonly 
    const STATE_VIEW = 'view'; 
    
    //currently we have 2 places to define userRoles: IndexController for 
    //translation and PHP editor_Workflow_Default for programmatic usage
    const ROLE_VISITOR = 'visitor';
    const ROLE_LECTOR = 'lector';
    const ROLE_TRANSLATOR = 'translator';
    
    //currently we have 2 places to define userRoles: IndexController for 
    //translation and PHP editor_Workflow_Default for programmatic usage
    const STEP_LECTORING = 'lectoring';
    const STEP_TRANSLATORCHECK = 'translatorCheck';
    const STEP_PM_CHECK = 'pmCheck';
    
    //const WORKFLOW_ID = ''; this is the internal used name for this workflow, it has to be defined in each subclass!
    
    /**
     * labels of the states, roles and steps. Can be changed / added in constructor
     * @var array
     */
    protected $labels = array(
        'WORKFLOW_ID' => 'Standard Ablaufplan', 
        'STATE_IMPORT' => 'import', 
        'STATE_WAITING' => 'wartend', 
        'STATE_FINISH' => 'abgeschlossen', 
        'STATE_OPEN' => 'offen', 
        'STATE_EDIT' => 'selbst in Arbeit', 
        'STATE_VIEW' => 'selbst geöffnet', 
        'ROLE_VISITOR' => 'Besucher',
        'ROLE_LECTOR' => 'Lektor',
        'ROLE_TRANSLATOR' => 'Übersetzer',
        'STEP_LECTORING' => 'Lektorat',
        'STEP_TRANSLATORCHECK' => 'Übersetzer Prüfung',
        'STEP_PM_CHECK' => 'PM Prüfung',
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
     * latest LogEntry (actual workflow step)
     * @var editor_Workflow_Log
     */
    protected $latestWorkflowLogEntry;
    
    /**
     * enables / disables debugging (logging)
     * 0 => disabled
     * 1 => log called handler methods (logging must be manually implemented in the handler methods by usage of $this->doDebug)
     * 2 => log also $this
     * @var integer
     */
    protected $debug = 0;
    
    /**
     * @var stdClass
     */
    protected $authenticatedUser;
    
    /**
     * @var ZfExtended_Models_User
     */
    protected $authenticatedUserModel;
    
    /**
     * lists all roles with read access to tasks
     * @var array 
     */
    protected $readableRoles = array(
        self::ROLE_VISITOR,
        self::ROLE_LECTOR,
        self::ROLE_TRANSLATOR
    );
    /**
     * lists all roles with write access to tasks
     * @var array 
     */
    protected $writeableRoles = array(
        self::ROLE_LECTOR,
        self::ROLE_TRANSLATOR
    );
    /**
     * lists all states which allow read access to tasks
     * @todo readableStates and writeableStates have to be changed/extended to a modelling of state transitions
     * @var array 
     */
    protected $readableStates = array(
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
        self::STATE_OPEN,
        self::STATE_EDIT,
        self::STATE_VIEW,
    );
    /**
     * roles which are part of the workflow chain (in this order)
     * @todo currently only used in notification. For extending of workflow system 
     *      or use of a workflow engine extend the use of roleChain to whereever applicable
     * @var array 
     */
    protected $stepChain = array(
        self::STEP_LECTORING,
        self::STEP_TRANSLATORCHECK,
    );
    
    /**
     * Mapping between roles and workflowSteps. 
     * @todo Since this must not be a 1 to 1 relation, we have to save the step also in userTaskAssoc
     * @var array
     */
    protected $steps2Roles = array(
        self::STEP_LECTORING=>self::ROLE_LECTOR,
        self::STEP_TRANSLATORCHECK=>self::ROLE_TRANSLATOR
    );
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    
    public function __construct() {
        $this->loadAuthenticatedUser();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
    }
    
    /**
     * returns the workflow ID used in translate5
     * if parameter $className is given return the ID of the given classname,
     * if no $className is given, the current class is used
     * @param string $className optional
     */
    public static function getId($className = null) {
        if(empty($className)) {
            return static::WORKFLOW_ID;
        }
        return call_user_func(array($className, __METHOD__));
    }
    
    /**
     * returns the step to roles mapping
     * @return array
     */
    public function getSteps2Roles() {
        return $this->steps2Roles;
    }
    /**
     * @param mixed $step string or null
     * @return string $role OR false if step does not exist
     */
    public function getNextStep(string $step) {
        $stepChain = $this->getStepChain();
        $position = array_search($step, $stepChain);
        if (isset($stepChain[$position + 1])) {
            return $stepChain[$position + 1];
        }
        return false;
    }
    /**
     * @param mixed $step string
     * @return string $role OR false if step does not exist
     */
    public function getRoleOfStep(string $step) {
        $steps2Roles = $this->getSteps2Roles();
        if(isset($steps2Roles[$step]))
            return $steps2Roles[$step];
        return false;
    }
    
    /**
     * returns the step of a role
     * @param string $role
     * @return boolean
     */
    public function getStepOfRole(string $role) {
        $roles2steps = array_flip($this->steps2Roles);
        return isset($roles2steps[$role]) ? $roles2steps[$role] : false;
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
     * loads the system user as authenticatedUser, if no user is logged in
     */
    protected function loadAuthenticatedUser(){
        $userSession = new Zend_Session_Namespace('user');
        if(isset($userSession->data) && isset($userSession->data->userGuid)) {
            $userGuid = $userSession->data->userGuid;
        }
        else {
            $userGuid = false;
        }
        $config = Zend_Registry::get('config');
        $isCron = $config->runtimeOptions->cronIP === $_SERVER['REMOTE_ADDR'];
        $this->authenticatedUserModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        
        if($userGuid === false){
            if(!$isCron) {
                throw new ZfExtended_NotAuthenticatedException("Cannot authenticate the system user!");
            }
            //set session user data with system user
            $this->authenticatedUserModel->setUserSessionNamespaceWithoutPwCheck('system');
        }
        $this->authenticatedUserModel->loadByGuid($userSession->data->userGuid);
        $this->authenticatedUser = $userSession->data;
        
    }
    /**
     * 
     * @return array of available step constants (keys are constants, valus are constant-values)
     */
    public function getSteps(){
        return $this->getFilteredConstants('STEP_');
    }
    /**
     * 
     * @return array of available role constants (keys are constants, valus are constant-values)
     */
    public function getRoles(){
        return $this->getFilteredConstants('ROLE_');
    }
    
    /**
     * returns the already translated labels as assoc array
     * @return array
     */
    public function getLabels() {
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
    public function getFilteredConstants(string $filter){
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
     * returns the TaskUserAssoc Entity to the given combination of $taskGuid and $userGuid, 
     * returns null if nothing found
     * @param string $taskGuid
     * @param string $userGuid
     * @return editor_Models_TaskUserAssoc returns null if nothing found
     */
    public function getTaskUserAssoc(string $taskGuid, string $userGuid) {
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        try {
            $tua->loadByParams($userGuid, $taskGuid);
            return $tua;
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            return null;
        }
    }
    
    /**
     * checks if the given TaskUserAssoc Instance allows reading of the task according to the Workflow Definitions
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param boolean $useUsedState optional, per default false means using TaskUserAssoc field state, otherwise TaskUserAssoc field usedState
     * @return boolean
     */
    public function isReadable(editor_Models_TaskUserAssoc $tua = null, $useUsedState = false) {
        return $this->isTuaAllowed($this->getReadableRoles(), $this->getReadableStates(), $tua, $useUsedState);
    }
    
    /**
     * checks if the given TaskUserAssoc Instance allows writing to the task according to the Workflow Definitions
     * @param editor_Models_TaskUserAssoc $tua (default null is only to allow null as value)
     * @param boolean $useUsedState optional, per default false means using TaskUserAssoc field state, otherwise TaskUserAssoc field usedState
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
     * @param boolean $useUsedState
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
     * 
     * - does not look for the state of a task, only for state of taskUserAssoc
     * 
     * @param editor_Models_TaskUserAssoc $taskUserAssoc 
     * @return boolean
     */
    public function isStateChangeable(editor_Models_TaskUserAssoc $taskUserAssoc) {
        $state = $taskUserAssoc->getState();
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
     * simple debugging
     * @param string $name
     */
    protected function doDebug($name) {
        if(empty($this->debug)) {
            return;
        }
        if($this->debug == 1) {
            error_log($name);
            return;
        }
        if($this->debug == 2) {
            error_log($name);
            error_log(print_r($this, 1));
        }
    }
    
    /**
     * manipulates the segment as needed by workflow after updated by user
     * @param editor_Models_Segment $segmentToSave
     */
    public function beforeSegmentSave(editor_Models_Segment $segmentToSave) {
        $updateAutoStates = function($autostates, $segment, $tua) {
            //sets the calculated autoStateId
            $segment->setAutoStateId($autostates->calculateSegmentState($segment, $tua));
        };
        $this->commonBeforeSegmentSave($segmentToSave, $updateAutoStates);
    }
    
    /**
     * manipulates the segment as needed by workflow after user has add or edit a comment of the segment
     */
    public function beforeCommentedSegmentSave(editor_Models_Segment $segmentToSave) {
        $updateAutoStates = function($autostates, $segment, $tua) {
            $autostates->updateAfterCommented($segment, $tua);
        };
        $this->commonBeforeSegmentSave($segmentToSave, $updateAutoStates);
    }
    
    /**
     * internal used method containing all common logic happend on a segment before saving it
     * @param editor_Models_Segment $segmentToSave
     * @param Closure $updateStates
     */
    protected function commonBeforeSegmentSave(editor_Models_Segment $segmentToSave, Closure $updateStates) {
        $session = new Zend_Session_Namespace();
        $sessionUser = new Zend_Session_Namespace('user');
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        //we assume that on editing a segment, every user (also not associated pms) have a assoc, so no notFound must be handled
        $tua->loadByParams($sessionUser->data->userGuid,$session->taskGuid);
        if($tua->getIsPmOverride() == 1){
            $segmentToSave->setWorkflowStep(self::STEP_PM_CHECK);
        }
        else {
            //sets the actual workflow step
            $segmentToSave->setWorkflowStepNr($session->taskWorkflowStepNr);
            
            //sets the actual workflow step name, does currently depend only on the userTaskRole!
            $roles2Step = array_flip($this->steps2Roles);
            $segmentToSave->setWorkflowStep($roles2Step[$tua->getRole()]);
        }

        $autostates = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        
        //set the autostate as defined in the given Closure
        /* @var $autostates editor_Models_SegmentAutoStates */
        $updateStates($autostates, $segmentToSave, $tua);
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
            $this->events->trigger("doNothing", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
            return; //saved some other attributes, do nothing
        }
        switch($newState) {
            case $newTask::STATE_OPEN:
                if($oldState == $newTask::STATE_END) {
                    $this->doReopen();
                    $this->events->trigger("doReopen", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
                }
                break;
            case $newTask::STATE_END:
                $this->doEnd();
                $this->events->trigger("doEnd", $this, array('oldTask' => $oldTask, 'newTask' => $newTask));
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
        
        //ensure that segment MV is createad
        if(empty($this->newTask)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($newTua->getTaskGuid());
        }
        else {
            $task = $this->newTask;
        }
        $task->createMaterializedView();
        
        $state = $this->getTriggeredState($oldTua, $newTua);
        if(!empty($state)) {
            if(method_exists($this, $state)) {
                $this->{$state}();
            } 
            $this->events->trigger($state, __CLASS__, array('oldTua' => $oldTua, 'newTua' => $newTua));
        }
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
     * doUnfinish, doView, doEdit, doFinish, doWait
     * beforeUnfinish, beforeView, beforeEdit, beforeFinish, beforeWait
     * 
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     * @param $prefix optional, defaults to "do"
     * @return string
     */
    public function getTriggeredState(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua, $prefix = 'do') {
        $oldState = $oldTua->getState();
        $newState = $newTua->getState();
        if($oldState == $newState) {
            return null;
        }
        
        if($oldState == self::STATE_FINISH && $newState != self::STATE_FINISH) {
            return $prefix.'Unfinish';
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
    public function doImport(editor_Models_Task $importedTask) {
        $this->newTask = $importedTask;
        $this->handleImport();
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
     * is called on finishin a task
     * evaluates the role and states of the User Task Association and calls the matching handlers:
     */
    protected function doFinish() {
        $userTaskAssoc = $this->newTaskUserAssoc;
        $stat = $userTaskAssoc->getUsageStat();
        $allFinished = true;
        $roleAllFinished = true;
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
                $this->handleFirstFinishOfARole(); 
            }
            if($isFinish) {
                $sum += (int)$entry['cnt'];
            }
        }
        if($sum === 1) {
            $this->handleFirstFinish();
        }
        if($roleAllFinished) {
            $this->handleAllFinishOfARole(); 
        }
        if($allFinished) {
            $this->handleAllFinish(); 
        }
        $this->handleFinish();
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
     * will be called after task import, the imported task is available in $this->newTask
     * @abstract
     */
    abstract protected function handleImport();
    
    /**
     * will be called after a user has finished a task
     * @abstract
     */
    abstract protected function handleFinish();
    
    /**
     * will be called after first user of a role has finished a task
     * @abstract
     */
    abstract protected function handleFirstFinishOfARole();
    
    /**
     * will be called after all users of a role has finished a task
     * @abstract
     */
    abstract protected function handleAllFinishOfARole();
    
    /**
     * will be called after a user has finished a task
     * @abstract
     */
    abstract protected function handleFirstFinish();
    
    /**
     * will be called after all associated users of a task has finished a task
     * @abstract
     */
    abstract protected function handleAllFinish();
    
    /**
     * will be called after a task has been ended
     * @abstract
     */
    abstract protected function handleEnd();
    
    /**
     * will be called after a task has been reopened (after was ended - task-specific)
     * @abstract
     */
    abstract protected function handleReopen();
    
    /**
     * will be called after a task has been unfinished (after was finished - taskassoc-specific)
     * @abstract
     */
    abstract protected function handleUnfinish();
    
    /**
     * will be called daily
     */
    abstract public function doCronDaily();
}