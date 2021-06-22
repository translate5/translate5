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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * TaskUserAssoc Object Instance as needed in the application
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method string getUserGuid() getUserGuid()
 * @method string getState() getState()
 * @method string getRole() getRole()
 * @method string getWorkflowStepName() getWorkflowStepName()
 * @method string getWorkflow() getWorkflow()
 * @method string getSegmentrange() getSegmentrange()
 * @method string getUsedState() getUsedState()
 * @method string getUsedInternalSessionUniqId() getUsedInternalSessionUniqId()
 * @method boolean getIsPmOverride() getIsPmOverride()
 * @method void setId() setId(int $id)
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method void setState() setState(string $state)
 * @method void setRole() setRole(string $role)
 * @method void setWorkflowStepName() setWorkflowStepName(string $step)
 * @method void setWorkflow() setWorkflow(string $workflow)
 * @method void setSegmentrange() setSegmentrange(string $segmentrange)
 * @method void setUsedState() setUsedState(string $state)
 * @method void setUsedInternalSessionUniqId() setUsedInternalSessionUniqId(string $sessionId)
 * @method void setIsPmOverride() setIsPmOverride(bool $isPmOverride)
 * @method string getStaticAuthHash() getStaticAuthHash()
 * @method void setStaticAuthHash() setStaticAuthHash(string $hash)
 * @method string getAssignmentDate() getAssignmentDate()
 * @method void setAssignmentDate() setAssignmentDate(string $assignment)
 * @method string getFinishedDate() getFinishedDate()
 * @method void setFinishedDate() setFinishedDate(string $datetime)
 * @method string getDeadlineDate() getDeadlineDate()
 * @method void setDeadlineDate() setDeadlineDate(string $datetime)
 *
 */
class editor_Models_TaskUserAssoc extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskUserAssoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_TaskUserAssoc';


    /**
     * returns all users to the taskGuid and role of the given TaskUserAssoc
     * @param string $taskGuid
     * @param string $role string or null, if empty returns no users, since needed as filter
     * @param array $assocFields optional, column names of the assoc table to be added in the result set
     * @param string $state string or null, additional filter for state of the job
     * @return [array] list with user arrays
     */
    public function loadUsersOfTaskWithRole(string $taskGuid, $role, array $assocFields = [], $state = null){
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $user->db->select()
        ->setIntegrityCheck(false)
        ->from(array('u' => $user->db->info($db::NAME)))
        ->join(array('tua' => $db->info($db::NAME)), 'tua.userGuid = u.userGuid', $assocFields)
        ->where('tua.isPmOverride = 0')
        ->where('tua.taskGuid = ?', $taskGuid);
        if(!empty($role)) {
            $s->where('tua.role = ?', $role);
        }
        if(!empty($state)){
            $s->where('tua.state = ?', $state);
        }
        return $user->db->fetchAll($s)->toArray();
    }

    /**
     * loads all tasks to the given user guid
     * @param string $userGuid
     * @return array|null
     */
    public function loadByUserGuid(string $userGuid){
        try {
            $s = $this->db->select()->where('userGuid = ?', $userGuid);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }

    /**
     * loads a assoc by given auth hash
     * @param string $hash
     * @return Zend_Db_Table_Row_Abstract
     */
    public function loadByHash(string $hash){
        try {
            $s = $this->db->select();
            $s->where('not staticAuthHash is null and staticAuthHash = ?', $hash);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound('#staticAuthHash', $hash);
        }
        return $this->row = $row;
    }

    /**
     * loads the assocs regardless isPmOverride is set or not
     * @param array $list
     * @return array
     */
    public function loadByTaskGuidList(array $list) {
        try {
            if(empty($list)) {
                return [];
            }
            $s = $this->db->select()->where('taskGuid in (?)', $list);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        return null;
    }

    /**
     * Load single task user assoc for the given task#user#role params.
     * @param string $userGuid
     * @param string $taskGuid
     * @param string $role | null
     * @param string $state | null
     * @return array
     */
    public function loadByParams(string $userGuid,string $taskGuid,string $role, $state = null) {
        try {
            $s = $this->db->select()
                ->where('userGuid = ?', $userGuid)
                ->where('taskGuid = ?', $taskGuid)
                ->where('(role= ? OR isPmOverride=1)', $role);//load the given state or load pmoveride (pmoveride is when for the given task#user#role no record is found)
            if(!is_null($state)) {
                $s->where('state= ?', $state);
            }
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#taskGuid + userGuid + role', $taskGuid.' + '.$userGuid.' + '.$role);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
        return $this->row->toArray();
    }
    
    /**
     * Returns the task user assoc matching a role, or if nothing found the one with the most useful state.
     * The state loading order is: edit, view, unconfirmed, open, waiting, finished
     * @param string $userGuid
     * @param string $taskGuid
     * @param string $role
     * @return array
     */
    public function loadByRoleOrSortedState(string $userGuid, string $taskGuid, string $role): array {
        
        //order first by matching role, then by the states as defined
        $order = $this->db->getAdapter()->quoteInto('role=? DESC, state="edit" DESC,state="view" DESC,state="unconfirmed" DESC,state="open" DESC,state="waiting" DESC,state="finished" DESC', $role);
        
        $s =$this->db->select()
        ->where('userGuid = ?', $userGuid)
        ->where('taskGuid = ?', $taskGuid)
        ->order(new Zend_Db_Expr($order));
        
        $row = $this->db->fetchRow($s);
        //no assocs, throw entity not found exception
        if(empty($row)){
            $this->notFound(__CLASS__ . '#taskGuid + userGuid', $taskGuid.' + '.$userGuid);
        }

        //load implies loading one Row, so use only the first row
        $this->row = $row;
        return $this->row->toArray();
    }

    /**
     * Updates the stored user states of an given taskGuid (may exclude the current user if enabled by third parameter)
     * @param string $state
     * @param string $role
     * @param boolean $expectMySelf if true, the internally loaded userGuid is excluded from the the update
     */
    public function setStateForRoleAndTask(string $state, string $role, $expectMySelf = false) {
        $where = [
            'role = ?' => $role,
            'taskGuid = ?' => $this->getTaskGuid(),
        ];
        if($expectMySelf) {
            $where['userGuid != ?'] = $this->getUserGuid();
        }
        $this->db->update(['state' => $state], $where);
    }

    /**
     * returns a matrix with the usage counts for all state,
     * role combinations of the actually loaded assoc's task (exclude pmOverrides)
     * @return array
     */
    public function getUsageStat() {
        $sql = 'select state, role, count(userGuid) cnt from LEK_taskUserAssoc where taskGuid = ? and isPmOverride = 0 group by state, role;';
        $res = $this->db->getAdapter()->query($sql, array($this->getTaskGuid()));
        return $res->fetchAll();
    }

    /**
     * loads the TaskUserAssoc Content joined with userinfos (currently only login)
     * loads only assocs where isPmOverride not set
     * @return array
     */
    public function loadAllWithUserInfo() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $db->select()
        ->setIntegrityCheck(false)
        ->from(array('tua' => $db->info($db::NAME)))
        ->join(array('u' => $user->db->info($db::NAME)), 'tua.userGuid = u.userGuid', array('login', 'surName', 'firstName', 'parentIds'))
        ->where('tua.isPmOverride = 0');
        //->where('tua.taskGuid = ?', $this->getTaskGuid()); kommt per filter aktuell!

        //default sort:
        if(!$this->filter->hasSort()) {
            $this->filter->addSort('surName');
            $this->filter->addSort('firstName');
            $this->filter->addSort('login');
        }
        return $this->loadFilterdCustom($s);
    }

    /***
     * Load all user assoc for all tasks in a project. This will load also the single task projects.
     *
     * @param int $projectId
     * @param string $workflow
     * @return array
     * @throws Zend_Db_Table_Exception
     */
    public function loadProjectWithUserInfo(int $projectId, string $workflow){
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from(array('tua' => $db->info($db::NAME)))
            ->join(array('u' => $user->db->info($db::NAME)), 'tua.userGuid = u.userGuid', array('login', 'surName', 'firstName', 'parentIds'))
            ->join(['t'=>'LEK_task'],'t.taskGuid = tua.taskGuid',['t.sourceLang','t.targetLang'])
            ->where('tua.isPmOverride = 0')
            ->where('tua.workflow = ?',$workflow)
            ->where('t.projectId = ?',$projectId)
            ->where('t.taskType != ?',editor_Models_Task::INITIAL_TASKTYPE_PROJECT);

        //default sort:
        if(!$this->filter->hasSort()) {
            $this->filter->addSort('surName');
            $this->filter->addSort('firstName');
            $this->filter->addSort('login');
        }
        return $db->fetchAll($s)->toArray();
    }


    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        $taskGuid = $this->get('taskGuid');
        $result = parent::save();
        $this->updateTask($taskGuid);
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::delete()
     */
    public function delete() {
        $taskGuid = $this->get('taskGuid');
        $task = ZfExtended_Factory::get('editor_Models_Task');

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1061' => 'The job can not be removed, since the user is using the task.',
            'E1062' => 'The job can not be removed, since the task is locked by the user.',
        ]);

        if($this->isUsed()) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1061', [
                'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da der Benutzer diese aktuell benutzt.'
            ], ['job' => $this]);
        }

        /* @var $task editor_Models_Task */
        if($task->isLocked($taskGuid, $this->getUserGuid())) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1062', [
                'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da die Aufgabe durch den Benutzer gesperrt ist.'
            ], ['job' => $this]);
        }

        $result = parent::delete();
        $this->updateTask($taskGuid);
        return $result;
    }

    /**
     * deletes the actual loaded assoc if it is a pmOverride assoc
     */
    public function deletePmOverride() {
        $this->db->delete(array(
            'id = ?' => $this->getId(),
            'taskGuid = ?' => $this->getTaskGuid(),
            'userGuid = ?' => $this->getUserGuid(),
            'isPmOverride = 1',
        ));
        $this->init();
    }

    /**
     * deletes all other users to a task expect the given one, optionally filtered by role.
     * Mainly needed for dealing with competitive users
     * @param string $taskGuid
     * @param string $userGuid
     * @param string $role
     * @return boolean|array returns the deleted tuas as array or false if the tua list was modified by other users
     */
    public function deleteOtherUsers(string $taskGuid, string $userGuid, string $role = null): array {
        $delete = [
            'taskGuid = ?' => $taskGuid,
            'userGuid != ?' => $userGuid,
            'isPmOverride = ?' => 0,
        ];
        if(!empty($role)) {
            $delete['role = ?'] = $role;
        }

        $s = $this->db->select();
        foreach($delete as $sql => $value) {
            $s->where($sql, $value);
        }
        $otherTuas = $this->db->fetchAll($s)->toArray();
        $this->db->getAdapter()->beginTransaction();
        $deleted = $this->db->delete($delete);
        //something was changed, roll back the delete and return false
        if(count($otherTuas) !== $deleted) {
            $this->db->getAdapter()->rollBack();
            return false;
        }
        $this->db->getAdapter()->commit();
        $this->updateTask($taskGuid);
        return $otherTuas;
    }

    /**
     * deletes all assoc entries for this userGuid, and updates the users counter in the Task Entity
     * @param string $userGuid
     */
    public function deleteByUserguid($userGuid) {
        $list = $this->loadByUserGuid($userGuid);
        foreach($list as $assoc) {
            $this->init($assoc);
            $this->delete();
        }
    }

    /**
     * updates the task table count field
     * @todo this method is a perfect example for the usage in events!
     */
    protected function updateTask($taskGuid) {
        $sql = 'update `LEK_task` t, (select count(*) cnt, ? taskGuid from `LEK_taskUserAssoc` where taskGuid = ? and isPmOverride = 0) tua
            set t.userCount = tua.cnt where t.taskGuid = tua.taskGuid';
        $db = $this->db->getAdapter();
        $sql = $db->quoteInto($sql, $taskGuid, 'string', 2);
        $db->query($sql);
    }

    /**
     * set all associations of the given taskGuid (or for all tasks if null) to unused where the session is expired
     * sets also the state to open where allowed
     * @param string $taskGuid optional, if omitted cleanup all taskUserAssocs
     * @param string $forced optional, default false. if true cleanup also taskUserAssocs with validSessionsIds, only usable with given taskGuid!
     */
    public function cleanupLocked($taskGuid = null, $forced = false) {
        try {
            $this->_cleanupLocked($taskGuid, $forced);
        }
        catch (PDOException | Zend_Db_Statement_Exception $e) {
            if(strpos($e->getMessage(), 'Serialization failure: 1213 Deadlock found when trying to get lock;') === false) {
                throw $e;
            }
            $log = Zend_Registry::get('logger');
            /* @var $log ZfExtended_Logger */
            //since a deadlock is not critical here but can happen, we just log it as info
            $log->exception($e, ['level' => $log::LEVEL_INFO]);
            return;
        }
    }

    protected function _cleanupLocked($taskGuid = null, $forced = false) {
        if(empty($taskGuid)){
            $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->get('default');
            /* @var $workflow editor_Workflow_Default */
        }
        else {
            $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($taskGuid);
            /* @var $workflow editor_Workflow_Default */
        }

        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        $where = array('not usedState is null and (usedInternalSessionUniqId not in ('.$validSessionIds.') or usedInternalSessionUniqId is null)');
        if(!empty($taskGuid)) {
            if($forced) {
                //since with force = true we throw out all users we allow this only with a given taskguid
                $where = array();
            }
            $where['taskGuid = ?'] = $taskGuid;
        }

        //FIXME this is not correct here, we should loop over all affected tuas, and should load the workflow then by taskGuid of the associated task
        // since until writing this comment a bug in getActive always returns the Default Workflow, we just keep that (getActive returns Default Workflow if no taskGuid given)
        if(!empty($workflow)) {
            //updates the workflow state back to open if allowed
            $where2 = $where;
            $where2['state in (?)'] = $workflow->getAllowedTransitionStates($workflow::STATE_OPEN);
            $this->db->update(array('state' => $workflow::STATE_OPEN), $where2);
        }

        //delete all pmEditAll fake entries
        $where3 = $where;
        $where3[] = 'isPmOverride = 1';
        $this->db->delete($where3);

        //unuse the associations where the using sessionId was expired, this update must be performed after the other!
        $this->db->update(array('usedState' => null,'usedInternalSessionUniqId' => null), $where);
    }

    /**
     * returns true if user of the currently loaded taskUserAssoc uses the associated task
     * @return boolean
     */
    public function isUsed() {
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        $validSessionIds .= ' AND internalSessionUniqId = ?';
        $res = $this->db->getAdapter()->query($validSessionIds, array($this->getUsedInternalSessionUniqId()));
        $validSessions = $res->fetchAll();
        //if usedInternalSessionUniqId not exists in the session table reset it,
        //  also the usedState value and return false
        if(empty($validSessions)){
            $this->db->update(array('usedState' => null, 'usedInternalSessionUniqId' => null), 'id = '.(int)$this->getId());
            return false;
        }
        $usedState = $this->getUsedState();
        // if usedState is set and sessionId is valid return true
        return !empty($usedState);
    }

    /**
     * loads and returns the currently used associations of the given taskGuid.
     * @param string $taskGuid
     * @return array
     */
    public function loadUsed(string $taskGuid) : array {
        $this->cleanupLocked($taskGuid);
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('usedState IS NOT NULL')
            ->where('usedInternalSessionUniqId IS NOT NULL');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * Check if the given user is in use in one of the tasks
     * @param string $taskGuid
     * @param string $userGuid
     * @return array
     */
    public function isUserInUse(string $userGuid) : array {
        $this->cleanupLocked();
        $s = $this->db->select()
        ->where('userGuid = ?', $userGuid)
        ->where('usedState IS NOT NULL')
        ->where('usedInternalSessionUniqId IS NOT NULL');
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Load the Key Point Indicators data for the given taskGuids and states
     * @param array $taskGuids
     * @param array $states
     * @return array
     */
    public function loadKpiData(array $taskGuids,array $states=[]){
        if(empty($taskGuids)){
            return [];
        }
        //if the states are not set uset the default states for kpi
        if(empty($states)){
            $states=[editor_Workflow_Default::ROLE_REVIEWER,editor_Workflow_Default::ROLE_TRANSLATOR,editor_Workflow_Default::ROLE_TRANSLATORCHECK];
        }
        $s = $this->db->select()
        ->where('taskGuid IN(?)', $taskGuids)
        ->where('role IN (?)',$states)
        ->where('assignmentDate IS NOT NULL')
        ->where('finishedDate IS NOT NULL');
        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * calculates a random GUID and sets it as staticAuthHash
     */
    public function createStaticAuthHash() {
        $this->setStaticAuthHash(ZfExtended_Utils::uuid());
    }

    /**
     * generates a task overview statistics summary
     * @return array
     */
    public function getSummary() {
        $stmt = $this->db->getAdapter()->query('select state, role, usedstate, count(*) jobCount from LEK_taskUserAssoc group by state,role, usedstate');
        return $stmt->fetchAll();
    }

    public function updateReviewersFinishDate(string $taskGuid,string $date){
        $this->db->update(['finishedDate'=>$date],
            ['taskGuid=?' => $taskGuid,'role=?' => editor_Workflow_Default::ROLE_REVIEWER]);
    }
    
    /**
     * What roles are assigned to a task at all?
     * @param string $taskGuid
     * @return array
     */
    public function getAllAssignedRolesByTask($taskGuid) {
        $s = $this->db->select()
            ->from($this->db, array('role'))
            ->distinct()
            ->where('isPmOverride = 0')
            ->where('taskGuid = ?', $taskGuid);
        return $this->db->fetchAll($s)->toArray();
    }
    
    // ---------------------- segmentrange: ------------------------
    /**
     * If
     * (1) a task is in sequential-mode,
     * (2) not in PM-override, and
     * (3) and ANY segments are assigned to ANY user of the given user's role
     *     in the current workflow-step,
     * then the editable-status of the segments will have to be checked for
     * ALL segments for ALL users of this role.
     * @param editor_Models_Task $task
     * @param string $role
     * @return bool
     */
    public function isSegmentrangedTaskForRole(editor_Models_Task $task, string $role) : bool {
        if ($task->getUsageMode() !== $task::USAGE_MODE_SIMULTANEOUS) {
            return false;
        }
        if($this->getIsPmOverride()) {
            return false;
        }
        $assignedSegments = $this->getAllAssignedSegmentsByRole($task->getTaskGuid(), $role);
        return count($assignedSegments) > 0;
    }
    /**
     * Return an array with all segments in given task for the given user in the given role.
     * @param string $taskGuid
     * @param string $userGuid
     * @param string $role
     * @return array
     */
    public function getAllAssignedSegmentsByUserAndRole(string $taskGuid, string $userGuid, string $role) : array {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('userGuid = ?', $userGuid)
            ->where('role = ?', $role)
            ->where('segmentrange IS NOT NULL');
        $tuaRows = $this->db->fetchAll($s)->toArray();
        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }
    /**
     * Return an array with the numbers of all segments in the task
     * that are assigned to any user of the given role.
     * @param string $taskGuid
     * @param string $role
     * @return array
     */
    public function getAllAssignedSegmentsByRole(string $taskGuid, string $role) : array {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('role = ?', $role)
            ->where('segmentrange IS NOT NULL');
        $tuaRows = $this->db->fetchAll($s)->toArray();
        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }
    
    /**
     * Get all assigned segments for task and role but exclude the given user from the select
     * @param string $taskGuid
     * @param String $role
     * @param string $userGuid
     * @return array
     */
    public function getNotForUserAssignedSegments(string $taskGuid, String $role, string $userGuid): array {
        $s = $this->db->select()
        ->where('taskGuid = ?', $taskGuid)
        ->where('role = ?', $role)
        ->where('userGuid != ?', $userGuid)
        ->where('segmentrange IS NOT NULL');
        $tuaRows = $this->db->fetchAll($s)->toArray();
        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }
    
    /**
     * Return an array with the numbers of the segments in the task
     * that are NOT assigned to any user although other segments ARE
     * already assigned to users, sorted by role.
     * @param string $taskGuid
     * @return array
     */
    public function getAllNotAssignedSegments(string $taskGuid) : array {
        // Example for a task with 10 segments:
        // - translator {94ff4a53-dae0-4793-beae-1f09968c3c93}: "1-3,5"
        // - translator {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "7-8"
        // - translatorCheck {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "8-10"
        // $notAssignedSegments = [
        //   translator => [4,6,9-10],
        //   translatorCheck => [1-7]
        // ]
        $notAssignedSegments = [];
        $allRoles = $this->getAllAssignedRolesByTask($taskGuid);
        foreach ($allRoles as $role) {
            $rolename = $role['role'];
            $notAssignedSegments[] = array('role' => $rolename, 'missingSegments' => $this->getAllNotAssignedSegmentsByRole($taskGuid, $rolename));
        }
        return $notAssignedSegments;
    }
    
    /**
     * Return an string with the ranges of the segments in the task
     * that are NOT assigned to any user of the given role.
     * @param string $taskGuid
     * @param string $role
     * @return string
     */
    private function getAllNotAssignedSegmentsByRole(string $taskGuid, string $role) : string {
        // Example for a task with 10 segments:
        // - translator {94ff4a53-dae0-4793-beae-1f09968c3c93}: "1-3,5"
        // - translator {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "7-8"
        // $notAssignedSegments = [4,6,9,10]
        $notAssignedSegments = [];
        $segmentModel = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segmentModel editor_Models_Segment */
        $segmentsNr = $segmentModel->getTotalSegmentsCount($taskGuid);
        $assignedSegments = $this->getAllAssignedSegmentsByRole($taskGuid, $role);
        for ($i = 1; $i <= $segmentsNr; $i++) {
            if (!in_array($i, $assignedSegments)) {
                $notAssignedSegments[] = $i;
            }
        }
        return editor_Models_TaskUserAssoc_Segmentrange::getRanges($notAssignedSegments);
    }
    
    /**
     * returns the tua data with removed auth hash
     * @return stdClass
     */
    public function getSanitizedEntityForLog(): stdClass {
        $tua = $this->getDataObject();
        unset($tua->staticAuthHash);
        unset($tua->usedInternalSessionUniqId);
        return $tua;
    }
}