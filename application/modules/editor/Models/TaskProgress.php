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

use editor_Models_Segment_AutoStates as AutoStates;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Task\Events\TaskProgressUpdatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Class encapsulating things for counting task/user progress
 */
class editor_Models_TaskProgress
{
    private readonly EventDispatcherInterface $eventDispatcher;

    public function __construct()
    {
        // TODO: place it into __construct parameter
        $this->eventDispatcher = EventDispatcher::create();
    }

    /**
     * Get blocked either locked segments count for given taskGuid
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getNonEditableSegmentsCount(string $taskGuid): int
    {
        return (int) Zend_Db_Table::getDefaultAdapter()->query("
            SELECT COUNT(*) FROM `LEK_segments`
            WHERE `taskGuid` = ?
              AND `autoStateId` IN (?, ?)
        ", [$taskGuid, ...AutoStates::$nonEditableStates])->fetchColumn();
    }

    /**
     * Get quantity of segments that are matching $customWHERE in a certain task
     * and at the same time are within a certain $range, if given
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function countCustom(string $taskGuid, string $customWHERE, ?string $range = null): int
    {
        // Get db adapter
        $db = Zend_Db_Table::getDefaultAdapter();

        // Prepare WHERE clause for segmentNrInTask-column
        $segmentNrInTask = $range
            ? $db->quoteInto('`segmentNrInTask` IN (?)', editor_Models_TaskUserAssoc_Segmentrange::getNumbers($range))
            : 'TRUE';

        // Get count
        return (int) $db->query(
            "
            SELECT COUNT(*) 
            FROM `LEK_segments` 
            WHERE `taskGuid` = ?
              AND $segmentNrInTask
              AND $customWHERE",
            $taskGuid,
        )->fetchColumn();
    }

    /**
     * Load progress of all users assigned to all tasks having guids mentioned in $list
     *
     * [
     *   taskGuid1 => [
     *      userGuid1 => 0
     *      userGuid2 => 0.5,
     *      userGuid3 => true
     *   ],
     *   taskGuid2 => [
     *      userGuid1 => 0.11
     *      userGuid3 => 0.99
     *   ]
     * ]
     *
     * @param bool $trueForNoRange If certain assoc-record have no segment range defined return true
     *                             instead of actual user progress due to that user progress is always
     *                             equal to task overall progress in such case
     */
    public function loadProgressByTaskGuidList(array $list, bool $trueForNoRange = false): array
    {
        // Load assoc data
        $assocA = ZfExtended_Factory
            ::get('editor_Models_TaskUserAssoc')
                ->loadByTaskGuidList($list) ?: [];

        // Progress will be collected here
        $userProgress = [];

        // Foreach assoc-record
        foreach ($assocA as $assocI) {
            // If no role it means this user opened the task despite he is not assigned to the task, so skip this assoc-record
            if (! $assocI['role']) {
                continue;
            }

            // If no segments range defined for the user and $trueForNoRange flag is true
            if (! $assocI['segmentrange'] && $trueForNoRange) {
                // Setup progress as true to indicate that user specific progress is equal to task overall progress
                $value = true;

                // Else apply ordinary logic
            } else {
                // Setup shortcuts
                $e = $assocI['segmentEditableCount'];
                $f = $assocI['segmentFinishCount'];

                // Calc progress
                $value = $e ? round($f / $e, 2) : 0;
            }

            // Collect progress
            $userProgress[$assocI['taskGuid']][$assocI['userGuid']] = $value;
        }

        // Return progress-per-userGuid, grouped by taskGuid-s
        return $userProgress;
    }

    /**
     * This method is called from TaskController->indexAction()
     */
    public function loadForRows(array &$rows): void
    {
        // Get user progress data
        $userProgress = $this->loadProgressByTaskGuidList(
            array_column($rows, 'taskGuid'),
            true
        );

        // Get userGuid of current user
        $userGuid = ZfExtended_Authentication::getInstance()->getUserGuid();

        // Foreach task
        foreach ($rows as &$row) {
            // Setup shortcuts
            $e = $row['segmentEditableCount'];
            $f = $row['segmentFinishCount'];

            // Calc progress
            $row['taskProgress'] = $e ? round($f / $e, 2) : 0;
            $row['userProgress'] = $userProgress[$row['taskGuid']][$userGuid] ?? false;
        }
    }

    /**
     * Recount values for segmentEditableCount and segmentFinishCount fields
     * of an editor_Models_TaskUserAssoc instance given as $tua arg
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function recountEditableAndFinished(editor_Models_TaskUserAssoc $tua): void
    {
        // Load task
        $taskM = ZfExtended_Factory::get(editor_Models_Task::class);
        $taskM->loadByTaskGuid($tua->getTaskGuid());

        // Recount editable segments
        $tua->setSegmentEditableCount(
            $this->countEditableSegmentsForRange(
                $taskM,
                $tua->getSegmentrange()
            )
        );

        $workflow = $taskM->getTaskActiveWorkflow();

        // Prepare states
        $states = $taskM->getTaskRoleAutoStates() ?: [];

        // Check if workflow is ended
        $isWorkflowEnded = $workflow->isEnded($taskM);

        // If workflow is not ended, and we do not have any states to the current steps' role, we do not update anything
        if (! $isWorkflowEnded && ! $states) {
            return;
        }

        // Recount finished segments among editable
        $tua->setSegmentFinishCount(
            $this->countEditableSegmentsForRange(
                $taskM,
                $tua->getSegmentrange(),
                $isWorkflowEnded ?: $states
            )
        );
    }

    /**
     * Check whether given segment is within the range
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function isSegmentInRange(string $taskGuid, int $segmentId, ?string $range = null): bool
    {
        return (bool) $this->countCustom(
            taskGuid: $taskGuid,
            customWHERE: "`id` = '$segmentId'",
            range: $range,
        );
    }

    /**
     * Adjust task editable segments count on bulk autoState change
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function adjustTaskEditableSegmentsCount(
        string $taskGuid,
        int $affectedSegmentsQty,
        int $segmentsOldState,
        int $segmentsNewState
    ): void {
        // Setup flags indicating segment was/is editable
        $wasEditable = ! in_array($segmentsOldState, AutoStates::$nonEditableStates);
        $nowEditable = ! in_array($segmentsNewState, AutoStates::$nonEditableStates);

        // If editable state was not changed - do nothing
        if ($wasEditable === $nowEditable) {
            return;
        }

        // Load task
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        // Get current quantity of editable segments
        $was = (int) $task->getSegmentEditableCount();

        // Get sign indicating whether affected segments stopped/started being blocked
        $sign = $wasEditable === true && $nowEditable === false ? -1 : +1;

        // Deduct/append affected segments qty from/to the current quantity of editable
        // segments based on whether affected segments stopped/started being blocked
        $task->setSegmentEditableCount($was + $affectedSegmentsQty * $sign);

        // Save task
        $task->save();

        // Recount value of segmentEditableCount for users associated with current task
        $this->updateSegmentEditableCountForUsers($task);
    }

    /**
     * Setup/recount value for segmentEditableCount-field for the given $task
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function updateSegmentEditableCount(editor_Models_Task $task): int
    {
        // Get quantity of (b)locked segments
        $nonEditableQty = $this->getNonEditableSegmentsCount($task->getTaskGuid());

        // Update value in segmentEditableCount field
        $task->setSegmentEditableCount((int) $task->getSegmentCount() - $nonEditableQty);
        $task->save();

        // Return fresh value
        return (int) $task->getSegmentEditableCount();
    }

    /**
     * Setup/recount value of segmentEditableCount-field for each user associated with current task
     *
     * @return array<string, int>
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function updateSegmentEditableCountForUsers(editor_Models_Task $task): array
    {
        // Get all users associated with task
        $tuaM = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);

        // Array of [userGuid => segmentEditableCount] pairs
        $count = [];

        // Foreach user
        foreach ($tuaM->loadAllOfATask($task->getTaskGuid()) as $tua) {
            // If no role it means this user opened the task despite he is not assigned to the task
            if (! $tua['role']) {
                continue;
            }

            // Update count
            $tuaM->load($tua['id']);
            $tuaM->setSegmentEditableCount(
                $this->countEditableSegmentsForRange($task, $tuaM->getSegmentrange())
            );
            $tuaM->save();

            // Collect result
            $count[$tuaM->getUserGuid()] = (int) $tuaM->getSegmentEditableCount();
        }

        // Return editable segments quantity-per-user
        return $count;
    }

    /**
     * Setup/recount value of segmentFinishCount-field based on the current task workflow step valid autoStates
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function updateSegmentFinishCount(editor_Models_Task $task): int
    {
        // Get finish info
        if (! $info = $task->getWorkflowEndedOrFinishedAutoStates()) {
            return 0;
        }

        // Pick $isWorkflowEnded flag and $autoStates from $info
        list($isWorkflowEnded, $autoStates) = $info;

        // If workflow is ended  - set finished the count to 100% (segmentFinishCount = segmentEditableCount)
        // Else assume finished are the segments having autostates valid for the task workflow step
        $segmentFinishCount = $isWorkflowEnded
            ? $task->getSegmentEditableCount()
            : $this->countCustom(
                taskGuid: $task->getTaskGuid(),
                customWHERE: 'autoStateId IN (' . join(',', $autoStates) . ')'
            );

        // Do update
        $task->setSegmentFinishCount($segmentFinishCount);
        $task->save();

        // Return fresh count
        return $segmentFinishCount;
    }

    /**
     * Setup/recount value of segmentFinishCount-field for each user associated with current task
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function updateSegmentFinishCountForUsers(editor_Models_Task $task): array
    {
        // Get finish info
        if (! $info = $task->getWorkflowEndedOrFinishedAutoStates()) {
            return [];
        }

        // Pick $isWorkflowEnded flag and $autoStates from $info
        list($isWorkflowEnded, $autoStates) = $info;

        // Get task user assoc model
        $tuaM = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);

        // Array of [userGuid => segmentFinishCount] pairs
        $count = [];

        // Foreach user - re-count finished segments
        foreach ($tuaM->loadAllOfATask($task->getTaskGuid()) as $tua) {
            // If no role it means this user opened the task despite he is not assigned to the task
            if (! $tua['role']) {
                continue;
            }

            // Update count
            $tuaM->load($tua['id']);
            $tuaM->setSegmentFinishCount(
                $this->countEditableSegmentsForRange($task, $tuaM->getSegmentrange(), $isWorkflowEnded ?: $autoStates)
            );
            $tuaM->save();

            // Collect result
            $count[$tuaM->getUserGuid()] = (int) $tuaM->getSegmentFinishCount();
        }

        // Return finished segments quantity-per-user
        return $count;
    }

    /**
     * Setup/recount values for both segmentEditableCount and segmentFinishCount fields of current task
     * If user identified by $userGuid is among the users assigned to current task - progress specific to
     * that user is returned as well
     *
     * @return int[]
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function refreshProgress(editor_Models_Task $task, ?string $userGuid = null, bool $fireEvent = false): array
    {
        // Update task progress
        $taskProgress = [
            'taskEditable' => $te = $this->updateSegmentEditableCount($task),
            'taskFinished' => $tf = $this->updateSegmentFinishCount($task),
            'taskProgress' => $te ? round($tf / $te, 2) : 0,
        ];

        // Update user progress
        $userProgress = [
            'userEditable' => $this->updateSegmentEditableCountForUsers($task),
            'userFinished' => $this->updateSegmentFinishCountForUsers($task),
        ];

        // If $userGuid arg is not given or is null
        if ($userGuid === null) {
            // Return current task progress and progress for all users associated with this task
            $result = $taskProgress + $userProgress;

            // Else if $userGuid arg is given, but such user is not assigned to current task
        } elseif (! isset($userProgress['userEditable'][$userGuid])) {
            // Return task progress with no users specific progress
            $result = $taskProgress + [
                'userProgress' => false,
            ];

            // Else return task progress plus progress of that specific user
        } else {
            $result = $taskProgress + [
                'userEditable' => $ue = $userProgress['userEditable'][$userGuid],
                'userFinished' => $uf = $userProgress['userFinished'][$userGuid],
                'userProgress' => $ue ? round($uf / $ue, 2) : 0,
            ];
        }

        if ($fireEvent) {
            $this->eventDispatcher->dispatch(TaskProgressUpdatedEvent::fromArray($task->getTaskGuid(), $result));
        }

        return $result;
    }

    /**
     * Increment or decrement the segmentFinishCount value based on the given state logic
     *
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function changeSegmentEditableAndFinishCount(editor_Models_Task $task, int $newAutoState, int $oldAutoState, int $segmentId): void
    {
        // Setup flags indicating editable state before and after state change
        $wasEditable = ! in_array($oldAutoState, AutoStates::$nonEditableStates);
        $nowEditable = ! in_array($newAutoState, AutoStates::$nonEditableStates);

        // If editable state was changed
        if ($wasEditable !== $nowEditable) {
            // Counter prop to be updated
            $counterProp = 'segmentEditableCount';

            // Get diff
            $counterDiff = $wasEditable === false && $nowEditable === true ? '+1' : '-1';

            // Update counter on task level
            $task->db->update(
                [
                    $counterProp => new Zend_Db_Expr("$counterProp$counterDiff"),
                ],
                [
                    'taskGuid = ?' => $task->getTaskGuid(),
                ]
            );

            // Update for each associated user
            $this->changeSegmentFinishOrEditableCountForUsers(
                $task->getTaskGuid(),
                $counterProp,
                (int) $counterDiff,
                $segmentId
            );
        }

        // If no finished states - return
        if (! $stateRoles = $task->getTaskRoleAutoStates()) {
            return;
        }

        // Setup flags indicating whether segments was/is finished
        $wasFinished = in_array($oldAutoState, $stateRoles);
        $nowFinished = in_array($newAutoState, $stateRoles);

        // If finished state was changed
        if ($wasFinished !== $nowFinished) {
            // Counter prop to be updated
            $counterProp = 'segmentFinishCount';

            // Get diff
            $counterDiff = $wasFinished === false && $nowFinished === true ? '+1' : '-1';

            // Update counter on task level
            $task->db->update(
                [
                    $counterProp => new Zend_Db_Expr("$counterProp$counterDiff"),
                ],
                [
                    'taskGuid = ?' => $task->getTaskGuid(),
                ]
            );

            // Update for each associated user
            $this->changeSegmentFinishOrEditableCountForUsers(
                $task->getTaskGuid(),
                $counterProp,
                (int) $counterDiff,
                $segmentId
            );
        }
    }

    /**
     * Increase/decrease value of segmentFinishedCount for each user for whom given segment is editable
     *
     * @param string $counterProp 'segmentEditableCount' or 'segmentFinishCount'
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function changeSegmentFinishOrEditableCountForUsers(string $taskGuid, string $counterProp, int $counterDiff, int $segmentId): void
    {
        // Get task user assoc model
        $tuaM = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);

        // Foreach user - increase/decrease editable/finished segments counter, if affected segment is in range
        foreach ($tuaM->loadAllOfATask($taskGuid) as $tua) {
            $tuaM->load($tua['id']);
            if ($this->isSegmentInRange($taskGuid, $segmentId, $tuaM->getSegmentrange())) {
                if ($counterProp === 'segmentEditableCount') {
                    $tuaM->setSegmentEditableCount((int) $tuaM->getSegmentEditableCount() + $counterDiff);
                } elseif ($counterProp === 'segmentFinishCount') {
                    $tuaM->setSegmentFinishCount((int) $tuaM->getSegmentFinishCount() + $counterDiff);
                }
                $tuaM->save();
            }
        }
    }

    /**
     * Count non-(b)locked segments for a task among the $range, if given.
     * If range if NOT given - current value of segmentEditableCount is returned
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function countEditableSegmentsForRange(
        editor_Models_Task $task,
        ?string $range = null,
        bool|array $autoStates = false
    ): int {
        // If $autoStates is true (which can be only if workflow ended) - return segmentEditableCount
        if ($autoStates === true) {
            return (int) $task->getSegmentEditableCount();
        }

        // Prepare WHERE clause for autoStateId-column
        $customWHERE = $autoStates
            ? '`autoStateId`     IN (' . join(',', $autoStates) . ')'
            : '`autoStateId` NOT IN (' . join(',', AutoStates::$nonEditableStates) . ')';

        // Do count
        return $this->countCustom(
            taskGuid: $task->getTaskGuid(),
            customWHERE: $customWHERE,
            range: $range,
        );
    }
}
