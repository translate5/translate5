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

class editor_Models_Segment_AutoStates_BulkUpdater
{
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager = null;

    /**
     * @var editor_Models_Db_Segments
     */
    protected $db = null;

    /**
     * The user which should be used as bulk updater, does not update user info if null
     * @var ZfExtended_Models_User
     */
    protected $user = null;

    /**
     * @param ZfExtended_Models_User $user OPTIONAL, if given the user on whom the bulk changes behalf of
     */
    public function __construct(ZfExtended_Models_User $user = null)
    {
        $this->db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->user = $user;
    }

    /**
     * Bulk updating a specific autoState of a task
     */
    public function updateAutoState(string $taskGuid, int $oldState, int $newState)
    {
        $sfm = $this->segmentFieldManager;
        $sfm->initFields($taskGuid);

        $bind = [$newState, $oldState, $taskGuid];
        $sql_tpl = $this->prepareSqlTpl($bind);

        $sql = sprintf($sql_tpl, $this->db->info($this->db::NAME));
        $sql_view = sprintf($sql_tpl, $sfm->getView()->getName());

        $this->db->getAdapter()->beginTransaction();

        //updates the view (if existing)
        $this->queryViewIfExists($sql_view, $bind);
        //updates LEK_segments directly
        $affectedSegmentsQty = $this->db->getAdapter()->query($sql, $bind)->rowCount();
        ZfExtended_Factory
            ::get(editor_Models_TaskProgress::class)
            ->adjustTaskEditableSegmentsCount(
                $taskGuid, $affectedSegmentsQty, $oldState, $newState
            );
        $this->db->getAdapter()->commit();
    }

    /**
     * Bulk updating a specific autoState of a task to status "not translated", affects only non edited segments
     */
    public function updateAutoStateNotTranslated(string $taskGuid, int $oldState)
    {
        $sfm = $this->segmentFieldManager;
        $sfm->initFields($taskGuid);

        $bind = [AutoStates::NOT_TRANSLATED, $oldState, $taskGuid];
        $sql_tpl = $this->prepareSqlTpl($bind);

        $sql_view = sprintf($sql_tpl, $sfm->getView()->getName());

        $this->db->getAdapter()->beginTransaction();

        $fields = $sfm->getFieldList();
        $affectedFieldNames = [];
        foreach ($fields as $field) {
            if ($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                $sql_view .= ' and ' . $sfm->getEditIndex($field->name) . " = ''";
                $affectedFieldNames[] = $field->name;
            }
        }
        //updates the view (if existing)
        $this->queryViewIfExists($sql_view, $bind);

        //the below SQL needs a prepended taskGuid
        $bind = array_unshift($bind, $taskGuid);

        //updates LEK_segments directly, but only where all above requested fields are empty
        $sql = 'UPDATE `%s` segment, %s subquery set segment.autoStateId = ? ';

        if (! empty($this->user)) {
            $sql .= ', segment.userGuid = ?, segment.userName = ? ';
        }

        $sql .= 'where segment.autoStateId = ? and segment.taskGuid = ? ';
        $sql .= 'and subquery.segmentId = segment.id and subquery.cnt = %s';

        //subQuery to get the count of empty fields, fields as requested above
        //if empty field count equals the the count of requested fiels,
        //that means all fields are empty and the corresponding segment has to be changed.
        $subQuery = '(select segmentId, count(*) cnt from LEK_segment_data where taskGuid = ? and ';
        $subQuery .= "edited = '' and name in ('" . join("','", $affectedFieldNames) . "') group by segmentId)";

        $sql = sprintf($sql, $this->db->info($this->db::NAME), $subQuery, count($affectedFieldNames));
        $affectedSegmentsQty = $this->db->getAdapter()->query($sql, $bind)->rowCount();
        ZfExtended_Factory
            ::get(editor_Models_TaskProgress::class)
            ->adjustTaskEditableSegmentsCount(
                $taskGuid, $affectedSegmentsQty, $oldState, AutoStates::NOT_TRANSLATED
            );
        $this->db->getAdapter()->commit();
    }

    /**
     * returns the SQL TPL and modified the array with the given bound variables
     * @param array $bind must be given as newState, oldState, taskGuid, is adjusted as needed for the SQL
     */
    protected function prepareSqlTpl(array &$bind): string
    {
        if (empty($this->user)) {
            // do not change bindings here
            return 'UPDATE `%s` set autoStateId = ? where autoStateId = ? and taskGuid = ?';
        }
        $bind = [$bind[0], $this->user->getUserGuid(), $this->user->getUserName(), $bind[1], $bind[2]];

        return 'UPDATE `%s` set autoStateId = ?, userGuid = ?, userName = ? where autoStateId = ? and taskGuid = ?';
    }

    /***
     * Find last editor from segment history, and update it in the lek segment table
     * @param string $taskGuid
     * @param int $autoState
     */
    public function resetUntouchedFromHistory(string $taskGuid, int $autoState)
    {
        if (empty($taskGuid) || empty($autoState)) {
            return;
        }

        //basically sets back to NOT_TRANSLATED, PRETRANSLATED, TRANSLATED
        // this should be the only one which were converted to an untouched value and the corresponding last author

        $this->segmentFieldManager->initFields($taskGuid);
        $this->db->getAdapter()->beginTransaction();

        $sql = 'UPDATE LEK_segments as seg,
            (
                SELECT hist.id, hist.autoStateId, hist.userGuid, hist.userName, hist.segmentId
                FROM LEK_segment_history hist
                INNER JOIN LEK_segments s
                ON s.id = hist.segmentId
                WHERE s.taskGuid = ?
                AND s.autoStateId = ?
                AND hist.id = (
                    SELECT id
                    FROM LEK_segment_history
                    WHERE segmentId = s.id
                    AND autoStateId != ?
                    ORDER BY id DESC LIMIT 1
                )
            ) as h
            SET seg.userGuid = h.userGuid, seg.userName = h.userName, seg.autoStateId = h.autoStateId
            WHERE seg.id = h.segmentId';

        //sync segmentview
        $this->db->getAdapter()->query($sql, [$taskGuid, $autoState, $autoState]);
        $view = $this->segmentFieldManager->getView()->getName();

        $sql = 'UPDATE ' . $view . ' as v, LEK_segments as seg
                SET v.userGuid = seg.userGuid, v.userName = seg.userName, v.autoStateId = seg.autoStateId
                WHERE v.id = seg.id and seg.taskGuid = ?';

        // updates the view (if existing)
        $this->queryViewIfExists($sql, [$taskGuid]);

        $this->db->getAdapter()->commit();
    }

    /**
     * shortcut to db->query catching errors complaining missing segment view
     * returns true if query was successfull, returns false if view was missing
     * @param string $sql
     */
    protected function queryViewIfExists($sql, array $bind)
    {
        try {
            $this->db->getAdapter()->query($sql, $bind);
        } catch (Zend_Db_Statement_Exception $e) {
            //ignore missing view errors, throw all others
            if (! $this->segmentFieldManager->isMissingViewException($e)) {
                throw $e;
            }
        }
    }

    /***
     * Update the $edit100PercentMatch flag for all segments in the task.
     * See https://confluence.translate5.net/display/MI/TRANSLATE-1643++A+separate+autostatus+pretranslated+is+missing+for+pretranslation
     *  for auto status change matrix
     * @param editor_Models_Task $task
     * @param bool $edit100PercentMatch
     * @throws editor_Models_Segment_Exception
     */
    public function updateSegmentsEdit100PercentMatch(editor_Models_Task $task, bool $edit100PercentMatch)
    {
        /** @var editor_Models_Segment_AutoStates $autoState */
        $autoState = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');

        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        //  since the first segment is loaded on construction, the construction must be directly before usage!
        /** @var editor_Models_Segment_Iterator $segments */
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);

        /** @var editor_Models_Segment_InternalTag $internalTag */
        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');

        foreach ($segments as $segment) {
            //we can ignore segments where the editable state is already as the desired $edit100PercentMatch state
            // or where the matchrate is lower as 100% since such segments should always be editable and no locked change is needed
            if ($autoState->isBlocked($segment->getAutoStateId()) || $segment->getEditable() == $edit100PercentMatch || $segment->getMatchRate() < 100) {
                continue;
            }

            $autoStateId = null;
            $editable = null;
            $history = $segment->getNewHistoryEntity();

            //is locked config has precendence over all other calculations!
            $isLocked = $segment->meta()->getLocked() && (bool) $task->getLockLocked();

            //if we want editable 100% matches, the segment should be not ediable before, which is checked in the foreach head
            if ($edit100PercentMatch) {
                $hasText = $internalTag->hasText($segment->getSource());

                // calc and change new autoState only if it is not hard locked and hasText
                if (! $isLocked && $hasText) {
                    $autoStateId = $autoState->recalculateUnLockedState($segment);
                    $editable = true;
                }
            } else {
                //all other pretrans values mean that it was either modified (PRETRANS_TRANSLATED) or it was not pre-translated at all so it could not be a 100% match
                $initialPretrans = $segment->getPretrans() == $segment::PRETRANS_INITIAL;

                $wasFromTM = editor_Models_Segment_MatchRateType::isFromTM($segment->getMatchRateType());

                //if we do NOT want editable 100% matches, the segment should be editable before, which is checked outside
                // and not explicitly unlocked with autopropagation:
                $allowToBlock = (! $segment->meta()->getAutopropagated() || $isLocked);
                if ($allowToBlock && $initialPretrans && $wasFromTM) {
                    //if segment.pretrans = 1 and matchrate >= 100% (checked in head) and matchtype ^= import;tm
                    // then
                    // TRANSLATED → LOCKED
                    // REVIEWED_UNTOUCHED → LOCKED
                    // REVIEWED_UNCHANGED → LOCKED
                    // REVIEWED_UNCHANGED_AUTO → LOCKED
                    // REVIEWED_PM_UNCHANGED → LOCKED
                    // REVIEWED_PM_UNCHANGED_AUTO → LOCKED
                    // PRETRANSLATED → LOCKED
                    $autoStateId = $autoState->recalculateLockedState($segment);
                    $editable = $autoStateId != $autoState::LOCKED;
                }
            }

            if (! is_null($autoStateId)) {
                $segment->setAutoStateId($autoStateId);
                $segment->setEditable($editable);
                $history->save();
                $segment->save();
            }
        }

        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        //update task word count when 100% matches editable is changed
        $task->setWordCount($meta->getWordCountSum($task));
    }
}
