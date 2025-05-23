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

use editor_Workflow_Default as Workflow;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\ZfExtended\Logger\CustomFileLogger;
use MittagQI\ZfExtended\Session\SessionInternalUniqueId;

/**
 * @method string getId()
 * @method string getTaskGuid()
 * @method string getUserGuid()
 * @method string getState()
 * @method string getRole()
 * @method string getWorkflowStepName()
 * @method string getWorkflow()
 * @method string getSegmentrange()
 * @method string getSegmentEditableCount()
 * @method string getSegmentFinishCount()
 * @method string getUsedState()
 * @method string getUsedInternalSessionUniqId()
 * @method string getIsPmOverride()
 * @method string getStaticAuthHash()
 * @method string getAssignmentDate()
 * @method string getFinishedDate()
 * @method string getDeadlineDate()
 * @method string getTrackchangesShow()
 * @method string getTrackchangesShowAll()
 * @method string getTrackchangesAcceptReject()
 * @method string|null getCoordinatorGroupJobId()
 *
 * @method void setId(int $id)
 * @method void setTaskGuid(string $taskGuid)
 * @method void setUserGuid(string $userGuid)
 * @method void setState(string $state)
 * @method void setRole(string $role)
 * @method void setWorkflowStepName(string $step)
 * @method void setWorkflow(string $workflow)
 * @method void setSegmentrange(string $segmentrange)
 * @method void setSegmentEditableCount(int $segmentEditableCount)
 * @method void setSegmentFinishCount(int $segmentFinishCount)
 * @method void setUsedState(string $state)
 * @method void setUsedInternalSessionUniqId(string $sessionId)
 * @method void setIsPmOverride(bool $isPmOverride)
 * @method void setStaticAuthHash(string $hash)
 * @method void setAssignmentDate(string $assignment)
 * @method void setFinishedDate(string $datetime)
 * @method void setDeadlineDate(string $datetime)
 * @method void setTrackchangesShow(int $isAllowed)
 * @method void setTrackchangesShowAll(int $isAllowed)
 * @method void setTrackchangesAcceptReject(int $isAllowed)
 * @method void setCoordinatorGroupJobId(int|null $id)
 */
class editor_Models_TaskUserAssoc extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_TaskUserAssoc';

    protected $validatorInstanceClass = 'editor_Models_Validator_TaskUserAssoc';

    public function setType(TypeEnum $type): void
    {
        $this->set('type', $type->value);
    }

    public function getType(): TypeEnum
    {
        return TypeEnum::from((int) $this->get('type'));
    }

    public function isCoordinatorGroupJob(): bool
    {
        return TypeEnum::Coordinator === $this->getType();
    }

    public function isCoordinatorGroupUserJob(): bool
    {
        return TypeEnum::Coordinator !== $this->getType() && ! empty($this->getCoordinatorGroupJobId());
    }

    public function isConfirmed(): bool
    {
        return in_array($this->getState(), [Workflow::STATE_WAITING, Workflow::STATE_OPEN]);
    }

    /***
     * @param string $taskGuid
     * @return array|null
     */
    public function loadAllOfATask(string $taskGuid)
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid);

        return $this->db->getAdapter()->fetchAll($s);
    }

    /**
     * returns all users to the taskGuid and role of the given TaskUserAssoc
     * @param string $workflowStepName string or null, if empty returns no users, since needed as filter
     * @param array $assocFields optional, column names of the assoc table to be added in the result set
     * @param string $state string or null, additional filter for state of the job
     */
    public function loadUsersOfTaskWithStep(string $taskGuid, $workflowStepName, array $assocFields = [], $state = null): array
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $db = $this->db;
        $s = $user->db->select()
            ->setIntegrityCheck(false)
            ->from([
                'u' => $user->db->info($db::NAME),
            ])
            ->join([
                'tua' => $db->info($db::NAME),
            ], 'tua.userGuid = u.userGuid', $assocFields)
            ->where('tua.isPmOverride = 0')
            ->where('tua.taskGuid = ?', $taskGuid);
        if (! empty($workflowStepName)) {
            $s->where('tua.workflowStepName = ?', $workflowStepName);
        }
        if (! empty($state)) {
            $s->where('tua.state = ?', $state);
        }

        return $user->db->fetchAll($s)->toArray();
    }

    /**
     * loads all tasks to the given user guid
     * @return array|null
     */
    public function loadByUserGuid(string $userGuid)
    {
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
     * @return Zend_Db_Table_Row_Abstract
     */
    public function loadByHash(string $hash)
    {
        try {
            $s = $this->db->select();
            $s->where('not staticAuthHash is null and staticAuthHash = ?', $hash);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (! $row) {
            $this->notFound('#staticAuthHash', $hash);
        }

        return $this->row = $row;
    }

    /**
     * loads the assocs regardless isPmOverride is set or not
     * Optionally filtered by Workflow And WorkflowStep
     */
    public function loadByTaskGuidList(array $list): array
    {
        if (empty($list)) {
            return [];
        }

        try {
            $s = $this->db->select()->where('taskGuid in (?)', $list)->where('type != ?', TypeEnum::Coordinator->value);

            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }

        return [];
    }

    /**
     * Load single task user assoc for the given task#user#step params.
     * @param string $state | null optional state filter
     * @return array
     */
    public function loadByStep(string $userGuid, string $taskGuid, string $workflowStepName, $state = null)
    {
        try {
            $s = $this->db->select()
                ->where('userGuid = ?', $userGuid)
                ->where('taskGuid = ?', $taskGuid)
                ->where('(workflowStepName = ? OR isPmOverride = 1)', $workflowStepName); //load the given state or load pmoveride (pmoveride is when for the given task#user#role no record is found)
            if (! is_null($state)) {
                $s->where('state = ?', $state);
            }
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (! $row) {
            $this->notFound(__CLASS__ . '#taskGuid + userGuid + workflowStepName', $taskGuid . ' + ' . $userGuid . ' + ' . $workflowStepName);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;

        return $this->row->toArray();
    }

    /**
     * Returns the task user assoc matching a step, or if nothing found the one with the most useful state.
     * The state loading order is: edit, view, unconfirmed, open, waiting, finished
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByStepOrSortedState(string $userGuid, string $taskGuid, string $workflowStepName): array
    {
        //order first by matching role, then by the states as defined
        $order = $this->db->getAdapter()->quoteInto('workflowStepName = ? DESC, state="edit" DESC,state="view" DESC,state="unconfirmed" DESC,state="open" DESC,state="waiting" DESC,state="finished" DESC', $workflowStepName);

        $s = $this->db->select()
            ->where('userGuid = ?', $userGuid)
            ->where('taskGuid = ?', $taskGuid)
            ->order(new Zend_Db_Expr($order));

        $row = $this->db->fetchRow($s);
        //no assocs, throw entity not found exception
        if (empty($row)) {
            $this->notFound(__CLASS__ . '#taskGuid + userGuid', $taskGuid . ' + ' . $userGuid);
        }

        //load implies loading one Row, so use only the first row
        $this->row = $row;

        return $this->row->toArray();
    }

    /**
     * Updates the stored user states of an given taskGuid (may exclude the current user if enabled by third parameter)
     * @param boolean $exceptMySelf if true, the internally loaded userGuid is excluded from the the update
     */
    public function setStateForStepAndTask(string $state, string $step, $exceptMySelf = false)
    {
        $where = [
            'workflowStepName = ?' => $step,
            'taskGuid = ?' => $this->getTaskGuid(),
        ];
        if ($exceptMySelf) {
            $where['userGuid != ?'] = $this->getUserGuid();
        }
        $this->db->update([
            'state' => $state,
        ], $where);
    }

    /**
     * returns a matrix with the usage counts for all state,
     * role combinations of the actually loaded assoc's task (exclude pmOverrides)
     * @return array
     */
    public function getUsageStat()
    {
        $sql = 'select state, workflowStepName, count(userGuid) cnt from LEK_taskUserAssoc where taskGuid = ? and isPmOverride = 0 group by state, workflowStepName;';
        $res = $this->db->getAdapter()->query($sql, [$this->getTaskGuid()]);

        return $res->fetchAll();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save()
    {
        $taskGuid = $this->get('taskGuid');
        $result = parent::save();
        $this->updateTask($taskGuid);

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::delete()
     */
    public function delete()
    {
        if ($this->isCoordinatorGroupJob()) {
            throw new LogicException('LSP Job should be delete using DeleteLspJobAssignmentOperation');
        }

        $taskGuid = $this->get('taskGuid');
        $task = ZfExtended_Factory::get('editor_Models_Task');

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1061' => 'The job can not be removed, since the user is using the task.',
            'E1062' => 'The job can not be removed, since the task is locked by the user.',
        ]);

        if ($this->isUsed()) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1061', [
                'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da der Benutzer diese aktuell benutzt.',
            ], [
                'job' => $this,
            ]);
        }

        /* @var $task editor_Models_Task */
        if ($task->isLocked($taskGuid, $this->getUserGuid())) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1062', [
                'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da die Aufgabe durch den Benutzer gesperrt ist.',
            ], [
                'job' => $this,
            ]);
        }

        $result = parent::delete();
        $this->updateTask($taskGuid);

        return $result;
    }

    /**
     * deletes the actual loaded assoc if it is a pmOverride assoc
     */
    public function deletePmOverride()
    {
        $this->db->delete([
            'id = ?' => $this->getId(),
            'taskGuid = ?' => $this->getTaskGuid(),
            'userGuid = ?' => $this->getUserGuid(),
            'isPmOverride = 1',
        ]);
        $this->init();
    }

    /**
     * updates the task table count field
     */
    protected function updateTask($taskGuid)
    {
        // TODO: REMOVE ME LATER! It is here for not to duplicate code.
        // TODO: Task update should be done explicitly and not as side effect of a TUA save or delete
        \MittagQI\Translate5\Repository\TaskRepository::create()->updateTaskUserCount($taskGuid);
    }

    /**
     * set all associations of the given taskGuid (or for all tasks if null) to unused where the session is expired
     * sets also the state to open where allowed
     * @param string $taskGuid optional, if omitted cleanup all taskUserAssocs
     * @param string $forced optional, default false. if true cleanup also taskUserAssocs with validSessionsIds, only
     *     usable with given taskGuid!
     */
    public function cleanupLocked($taskGuid = null, $forced = false)
    {
        try {
            $this->_cleanupLocked($taskGuid, $forced);
        } catch (PDOException | Zend_Db_Statement_Exception $e) {
            if (strpos($e->getMessage(), 'Serialization failure: 1213 Deadlock found when trying to get lock;') === false) {
                throw $e;
            }
            $log = Zend_Registry::get('logger');
            /* @var $log ZfExtended_Logger */
            //since a deadlock is not critical here but can happen, we just log it as info
            $log->exception($e, [
                'level' => $log::LEVEL_INFO,
            ]);

            return;
        }
    }

    protected function _cleanupLocked($taskGuid = null, $forced = false)
    {
        $sessions = new ZfExtended_Models_Db_Session();
        $validSessionIds = $sessions->getValidSessionsSql();

        // TODO: REMOVE ME LATER WHEN WE HAVE INFO ABOUT THE NOACCESS ERROR. THIS IS ONLY TEMP DEBUG CODE TO COLLECT
        // MORE INFO ABOUT THE BUG
        $customFileLogger = ZfExtended_Factory::get(CustomFileLogger::class);
        $customFileLogger->log('Request url: ' . $_SERVER['REQUEST_URI']);
        $customFileLogger->log('Found validSessionIds sql : ' . $validSessionIds);

        //load all used jobs where the usage is not valid anymore
        $where = [
            'not usedState is null and (usedInternalSessionUniqId not in (' . $validSessionIds . ') or usedInternalSessionUniqId is null)' => null,
        ];
        if (! empty($taskGuid)) {
            if ($forced) {
                //since with force = true we throw out all users we allow this only with a given taskguid
                $where = [];
            }
            $where['taskGuid = ?'] = $taskGuid;
        }

        $s = $this->db->select()->from($this->db, ['taskGuid', 'userGuid']);
        foreach ($where as $condition => $valToQuote) {
            $s->where($condition, $valToQuote);
        }
        $taskUserAssoc = $this->db->fetchAll($s)->toArray();

        // TODO: REMOVE ME LATER WHEN WE HAVE INFO ABOUT THE NOACCESS ERROR. THIS IS ONLY TEMP DEBUG CODE TO COLLECT
        // MORE INFO ABOUT THE BUG
        $customFileLogger->log('TaskUserAssocs query: ' . $s->assemble());
        $customFileLogger->log('TaskUserAssocs query results : ' . print_r($taskUserAssoc, true));

        //reopen each found job, keeping workflow transition check
        $taskGuids = array_unique(array_column($taskUserAssoc, 'taskGuid'));
        foreach ($taskGuids as $jobTaskGuid) {
            $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($jobTaskGuid);
            /* @var $workflow Workflow */
            if (! empty($workflow)) {
                //updates the workflow state back to open if allowed
                $where2 = $where;
                $where2['state in (?)'] = $workflow->getAllowedTransitionStates($workflow::STATE_OPEN);
                if (! empty($taskGuid)) {
                    $where2['taskGuid = ?'] = $jobTaskGuid;
                }
                $this->db->update([
                    'state' => $workflow::STATE_OPEN,
                ], $where2);
            }
        }

        //delete all pmEditAll fake entries
        $where3 = $where;
        $where3['isPmOverride = 1'] = null;
        $this->db->delete($where3);

        //unuse the associations where the using sessionId was expired, this update must be performed last on the jobs
        $this->db->update([
            'usedState' => null,
            'usedInternalSessionUniqId' => null,
        ], $where);

        //finally unlock also the tasks
        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        foreach ($taskUserAssoc as $job) {
            $task->unlockForUser($job['userGuid'], $job['taskGuid']);
        }

        // TODO: REMOVE ME LATER WHEN WE HAVE INFO ABOUT THE NOACCESS ERROR. THIS IS ONLY TEMP DEBUG CODE TO COLLECT
        // MORE INFO ABOUT THE BUG
        if (Zend_Session::isStarted() && ! Zend_Session::isDestroyed()) {
            $customFileLogger->log('My current internalSessionUniqId : ' . SessionInternalUniqueId::getInstance()->get());
            $customFileLogger->log('My current sessionId : ' . Zend_Session::getId());
        }

        $customFileLogger->write();
    }

    /**
     * returns true if user of the currently loaded taskUserAssoc uses the associated task
     * @return boolean
     */
    public function isUsed()
    {
        $validSessionIds = ZfExtended_Models_Db_Session::GET_VALID_SESSIONS_SQL;
        $validSessionIds .= ' AND s.internalSessionUniqId = ?';
        $res = $this->db->getAdapter()->query($validSessionIds, [$this->getUsedInternalSessionUniqId()]);
        $validSessions = $res->fetchAll();
        //if usedInternalSessionUniqId not exists in the session table reset it,
        //  also the usedState value and return false
        if (empty($validSessions)) {
            $this->db->update([
                'usedState' => null,
                'usedInternalSessionUniqId' => null,
            ], 'id = ' . (int) $this->getId());

            return false;
        }
        $usedState = $this->getUsedState();

        // if usedState is set and sessionId is valid return true
        return ! empty($usedState);
    }

    /**
     * loads and returns the currently used associations of the given taskGuid.
     * @throws Zend_Db_Statement_Exception
     */
    public function loadUsed(string $taskGuid, string $userGuid = null): array
    {
        $this->cleanupLocked($taskGuid);
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('usedState IS NOT NULL')
            ->where('usedInternalSessionUniqId IS NOT NULL');
        if (! empty($userGuid)) {
            $s->where('userGuid = ?', $userGuid);
        }

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Load the Key Point Indicators data for the given taskGuids and roles/steps
     * If 'workflowSteps' filter is specified, it has priority over 'roles'
     */
    public function loadKpiData(array $taskGuids, array $roles = [], array $workflowSteps = []): array
    {
        if (empty($taskGuids)) {
            return [];
        }

        $col = ! empty($workflowSteps) ? 'workflowStepName' : 'role';
        $s = $this->db->select()
            ->from($this->db, [$col . ' AS timeBy', new Zend_Db_Expr('SUM(DATEDIFF(finishedDate, assignmentDate))/COUNT(*) AS time')])
            ->where('taskGuid IN(?)', $taskGuids)
            ->where('assignmentDate IS NOT NULL')
            ->where('finishedDate IS NOT NULL');

        if ($col === 'workflowStepName') {
            $s = $s->where('workflowStepName IN (?)', $workflowSteps);
        } else {
            //if the roles are not set, use the default roles for kpi
            if (empty($roles)) {
                $roles = [Workflow::ROLE_REVIEWER, Workflow::ROLE_TRANSLATOR, Workflow::ROLE_TRANSLATORCHECK];
            }
            $s = $s->where('role IN (?)', $roles);
        }
        $s = $s->group($col);

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * calculates a random GUID and sets it as staticAuthHash
     */
    public function createStaticAuthHash()
    {
        $this->setStaticAuthHash(ZfExtended_Utils::uuid());
    }

    /**
     * generates a task overview statistics summary
     * @return array
     */
    public function getSummary()
    {
        $stmt = $this->db->getAdapter()->query('select state, role, usedstate, count(*) jobCount from LEK_taskUserAssoc group by state,role, usedstate');

        return $stmt->fetchAll();
    }

    /**
     * What roles are assigned to a task at all?
     * @param string $taskGuid
     * @return array
     */
    private function getAllAssignedStepsByTask($taskGuid)
    {
        $s = $this->db->select()
            ->from($this->db, ['workflowStepName'])
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
     * (3) and ANY segments are assigned to ANY user of the given user's step
     *     in the current workflow-step,
     * then the editable-status of the segments will have to be checked for
     * ALL segments for ALL users of this role.
     */
    public function isSegmentrangedTaskForStep(editor_Models_Task $task, string $step): bool
    {
        if ($task->getUsageMode() !== $task::USAGE_MODE_SIMULTANEOUS) {
            return false;
        }
        if ($this->getIsPmOverride()) {
            return false;
        }
        $assignedSegments = $this->getAllAssignedSegmentsByStep($task->getTaskGuid(), $step);

        return count($assignedSegments) > 0;
    }

    /**
     * Return an array with all segments in given task for the given user in the given role.
     */
    public function getAllAssignedSegmentsByUserAndStep(string $taskGuid, string $userGuid, string $step): array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('userGuid = ?', $userGuid)
            ->where('workflowStepName = ?', $step)
            ->where('segmentrange IS NOT NULL');
        $tuaRows = $this->db->fetchAll($s)->toArray();

        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }

    /**
     * Return an array with the numbers of all segments in the task
     * that are assigned to any user of the given step.
     */
    protected function getAllAssignedSegmentsByStep(string $taskGuid, string $step): array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('workflowStepName = ?', $step)
            ->where('segmentrange IS NOT NULL');
        $tuaRows = $this->db->fetchAll($s)->toArray();

        return editor_Models_TaskUserAssoc_Segmentrange::getSegmentNumbersFromRows($tuaRows);
    }

    /**
     * Get all assigned segments for task and role but exclude the given user from the select
     */
    public function getNotForUserAssignedSegments(string $taskGuid, String $role, string $userGuid): array
    {
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
     */
    public function getAllNotAssignedSegments(string $taskGuid): array
    {
        // Example for a task with 10 segments:
        // - translator {94ff4a53-dae0-4793-beae-1f09968c3c93}: "1-3,5"
        // - translator {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "7-8"
        // - translatorCheck {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "8-10"
        // $notAssignedSegments = [
        //   translator => [4,6,9-10],
        //   translatorCheck => [1-7]
        // ]
        $notAssignedSegments = [];
        $allSteps = $this->getAllAssignedStepsByTask($taskGuid);
        foreach ($allSteps as $step) {
            $stepname = $step['workflowStepName'];
            $notAssignedSegments[] = [
                'workflowStepName' => $stepname,
                'missingSegments' => $this->getAllNotAssignedSegmentsByStep($taskGuid, $stepname),
            ];
        }

        return $notAssignedSegments;
    }

    /**
     * Return an string with the ranges of the segments in the task
     * that are NOT assigned to any user of the given role.
     */
    private function getAllNotAssignedSegmentsByStep(string $taskGuid, string $step): string
    {
        // Example for a task with 10 segments:
        // - translator {94ff4a53-dae0-4793-beae-1f09968c3c93}: "1-3,5"
        // - translator {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "7-8"
        // $notAssignedSegments = [4,6,9,10]
        $notAssignedSegments = [];
        $segmentModel = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segmentModel editor_Models_Segment */
        $segmentsNr = $segmentModel->getTotalSegmentsCount($taskGuid);
        $assignedSegments = $this->getAllAssignedSegmentsByStep($taskGuid, $step);
        for ($i = 1; $i <= $segmentsNr; $i++) {
            if (! in_array($i, $assignedSegments)) {
                $notAssignedSegments[] = $i;
            }
        }

        return editor_Models_TaskUserAssoc_Segmentrange::getRanges($notAssignedSegments);
    }

    /**
     * returns the tua data with removed auth hash
     */
    public function getSanitizedEntityForLog(): stdClass
    {
        $tua = $this->getDataObject();
        unset($tua->staticAuthHash);
        unset($tua->usedInternalSessionUniqId);

        return $tua;
    }

    /**
     * Check whether segmentrange-prop is modified, and if yes - recount editable and finished segments
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function onBeforeSave(): void
    {
        // If segmentrange-prop is modified or it's new assoc-record
        if ($this->isModified('segmentrange') || ! $this->getId()) {
            // Recount values for segmentEditableCount and segmentFinishCount fields
            ZfExtended_Factory
                ::get(editor_Models_TaskProgress::class)
                    ->recountEditableAndFinished($this);
        }
    }
}
