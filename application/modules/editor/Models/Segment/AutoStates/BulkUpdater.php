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
    protected editor_Models_SegmentFieldManager $segmentFieldManager;

    protected editor_Models_Db_Segments $db;

    /**
     * The user which should be used as bulk updater, does not update user info if null
     */
    protected ?ZfExtended_Models_User $user = null;

    /**
     * @param ZfExtended_Models_User|null $user OPTIONAL, if given the user on whom the bulk changes behalf of
     * @throws ReflectionException
     */
    public function __construct(ZfExtended_Models_User $user = null)
    {
        $this->db = ZfExtended_Factory::get(editor_Models_Db_Segments::class);
        $this->segmentFieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
        $this->user = $user;
    }

    /**
     * Bulk updating a specific autoState of a task
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function updateAutoState(string $taskGuid, int $oldState, int $newState): void
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
                    $taskGuid,
                    $affectedSegmentsQty,
                    $oldState,
                    $newState
                );
        $this->db->getAdapter()->commit();
    }

    /**
     * Bulk updating a specific autoState of a task to status "not translated", affects only non edited segments
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function updateAutoStateNotTranslated(string $taskGuid, int $oldState): void
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
                    $taskGuid,
                    $affectedSegmentsQty,
                    $oldState,
                    AutoStates::NOT_TRANSLATED
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

    /**
     * Find last editor from segment history, and update it in the lek segment table
     * @throws Zend_Db_Statement_Exception
     */
    public function resetUntouchedFromHistory(string $taskGuid, int $autoState): void
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
     * returns true if query was successful, returns false if view was missing
     * @throws Zend_Db_Statement_Exception
     */
    protected function queryViewIfExists(string $sql, array $bind): void
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
}
