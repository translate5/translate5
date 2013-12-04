<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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

    public function __construct() {
        $this->loadAuthenticatedUser();
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
     * loads the system user as authenticatedUser, if no user is logged in
     */
    protected function loadAuthenticatedUser(){
        $userSession = new Zend_Session_Namespace('user');
        $config = Zend_Registry::get('config');
        $this->authenticatedUserModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        if(!isset($userSession->data->userGuid)&& 
                $config->runtimeOptions->cronIP === $_SERVER['REMOTE_ADDR']){
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
     * 
     * @param string $taskGuid
     * @param string $userGuid
     * @param boolean $checkReadable checks in addition if task is readable for user and only returns true if yes
     * @param boolean $checkWriteable checks in addition if task is writeable for user and only returns true if yes
     * @return boolean
     * FIXME wo wird diese Methode überall verwendet? Zwecks PM editing
     */
    public function isTaskOfUser(string $taskGuid, string $userGuid,$checkReadable = false, $checkWriteable = false) {
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $s = $tua->db->select()
                    ->where('taskGuid = ?', $taskGuid)
                    ->where('userGuid = ?', $userGuid);
        $roles = array();
        $states = array();
        if($checkReadable){
            $roles = $this->getReadableRoles();
            $states = $this->getReadableStates();
        }
        if($checkWriteable){
            $roles = array_merge($roles,$this->getWriteableRoles());
            $states = array_merge($states,$this->getWriteableStates());
        }
        //var_dump($roles);
        //var_dump($states);
        if(count($roles)>0){
            $sql = array();
            $qValues = array();
            foreach ($roles as $key => $value) {
                $sql[] = $tua->db->getAdapter()->quoteInto('role = ?', $value);
            }
            $s->where(implode(' or ', $sql), $qValues);
        }
        if(count($states)>0){
            $sql = array();
            $qValues = array();
            foreach ($states as $key => $value) {
                $sql[] = $tua->db->getAdapter()->quoteInto('state = ?', $value);
            }
            $s->where(implode(' or ', $sql));
        }
        //var_dump($s->assemble());exit;
        $tuas = $tua->db->fetchAll($s)->toArray();
        if(count($tuas)>0)
            return true;
        return false;
    }
    //does not deliver task-states, only workflow-states
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
     * @param boolean $sourceEditing defines if source editing is used on the current task or not
     */
    public function beforeSegmentSave(editor_Models_Segment $segmentToSave, $sourceEditing) {
        $updateAutoStates = function($autostates, $segment, $tua) use($sourceEditing) {
            //sets the calculated autoStateId
            $segment->setAutoStateId($autostates->calculateSegmentState($segment, $tua, $sourceEditing));
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
        
        try {
            $tua->loadByParams($sessionUser->data->userGuid,$session->taskGuid);
            
            //sets the actual workflow step
            $segmentToSave->setWorkflowStepNr($session->taskWorkflowStepNr);
            
            //sets the actual workflow step name, does currently depend only on the userTaskRole!
            $roles2Step = array_flip($this->steps2Roles);
            $segmentToSave->setWorkflowStep($roles2Step[$tua->getRole()]);
        }
        //if no assoc entry is found, we have to check if its an editAllTasks request
        catch(ZfExtended_NotFoundException $e) {
            $acl = ZfExtended_Acl::getInstance();
            if(!$acl->isInAllowedRoles($sessionUser->data->roles,'editAllTasks')) {
                throw $e;
            }
            //set only the workflow step, the stepNr is not changed 
            $segmentToSave->setWorkflowStep(self::STEP_PM_CHECK);
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

        if($newState == $oldState) {
            return; //saved some other attributes, do nothing
        }
        switch($newState) {
            case $newTask::STATE_OPEN:
                if($oldState == $newTask::STATE_END) {
                    $this->doReopen();
                }
                break;
            case $newTask::STATE_END: 
                $this->doEnd();
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
        
        $oldState = $oldTua->getState();
        $newState = $newTua->getState();
        if($oldState == $newState) {
            return;
        }
        
        if($oldState == self::STATE_FINISH && $newState != self::STATE_FINISH) {
            $this->doUnfinish();
        }
        
        switch($newState) {
            case $this::STATE_OPEN:
                $this->doOpen();
                break;
            case self::STATE_FINISH: 
                $this->doFinish();
                break;
            case self::STATE_WAITING: 
                $this->doWait();
                break;
        }
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
     * is called when a task assoc state gets OPEN again
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