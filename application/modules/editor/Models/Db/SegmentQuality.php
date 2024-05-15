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

final class editor_Models_Db_SegmentQuality extends Zend_Db_Table_Abstract
{
    /**
     * Removes all qualities for a task
     * If the type is given, it removes only for the given type
     */
    public static function deleteForTask(string $taskGuid, string $qualityType = null)
    {
        $table = new self();
        if (empty($qualityType)) {
            $table->removeByTaskGuid($taskGuid);
        } else {
            $table->removeByTaskGuidAndType($taskGuid, $qualityType);
        }
    }

    /**
     * Deletes all existing entries for the given segmentIds
     */
    public static function deleteForSegments(array $segmentIds)
    {
        if (count($segmentIds) > 0) {
            $table = new self();
            $db = $table->getAdapter();
            $where = (count($segmentIds) > 1) ? $db->quoteInto('segmentId IN (?)', $segmentIds) : $db->quoteInto('segmentId = ?', $segmentIds[0]);
            $db->delete($table->getName(), $where);
        }
    }

    /**
     * @param editor_Models_Db_SegmentQualityRow[] $rows
     */
    public static function saveRows(array $rows)
    {
        if (count($rows) > 1) {
            $table = new self();
            $db = $table->getAdapter();
            $cols = [];
            foreach ($table->info(Zend_Db_Table_Abstract::COLS) as $col) {
                if ($col != 'id') {
                    $cols[] = $col;
                }
            }
            $rowvals = [];
            foreach ($rows as $row) { /* @var $row editor_Models_Db_SegmentQualityRow */
                $vals = [];
                foreach ($cols as $col) {
                    $vals[] = ($row->$col === null) ? 'NULL' : $db->quote($row->$col);
                }
                $rowvals[] = '(' . implode(',', $vals) . ')';
            }
            $db->query('INSERT INTO ' . $db->quoteIdentifier($table->getName()) . ' (`' . implode('`,`', $cols) . '`) VALUES ' . implode(',', $rowvals));
        } elseif (count($rows) > 0) {
            $rows[0]->save();
        }
    }

    /**
     * Checks whether a certain quality of the given type and category exists for he task. If category is not provided checks only for type
     */
    public static function hasTypeCategoryForTask(string $taskGuid, string $type, string $category = null): bool
    {
        $table = new self();
        $where = $table->select()
            ->from($table->getName(), ['id'])
            ->where('taskGuid = ?', $taskGuid)
            ->where('type = ?', $type);
        if ($category != null) {
            $where->where('category = ?', $category);
        }

        return (count($table->fetchAll($where)) > 0);
    }

    /**
     * Generates a list of segmentIds to be used as filter in the segment controller's quality filtering
     * @return int[]
     */
    public static function getSegmentIdsForQualityFilter(editor_Models_Quality_RequestState $state, string $taskGuid): array
    {
        $table = new self();
        $adapter = $table->getAdapter();
        $select = $adapter->select();
        $select
            ->from([
                'qualities' => $table->getName(),
            ], 'qualities.segmentId')
            ->where('qualities.taskGuid = ?', $taskGuid);

        // if the state has no editable restriction this means, that the editable restriction must be applied here but not for internal tag faults
        if (! $state->hasEditableRestriction()) {
            // Shortcuts
            $internal = editor_Segment_Tag::TYPE_INTERNAL;
            $faulty = editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY;
            $consistent = editor_Segment_Consistent_QualityProvider::qualityType();

            // Query chunks
            $isTagFaulty = "(qualities.type = '$internal' AND qualities.category = '$faulty')";
            $isNotConsistent = "qualities.type = '$consistent'";
            $isEditable = "segments.editable = 1";
            $isNotEditable = "segments.editable = 0";
            $isTagFaultyOrNotConsistent = "($isTagFaulty OR $isNotConsistent)";

            $select->from([
                'segments' => 'LEK_segments',
            ], [])->where('qualities.segmentId = segments.id');

            // here it's where it get's really finnicky: we have to evaluate the editable-category only, if it can't be applied in editor_Models_Filter_SegmentSpecific
            // that means, we do have other categories apart of the non-editable faulty tags, but that may also includes the editable faulty-tags
            if ($state->hasCategoryEditableInternalTagFaults()) {
                if ($state->hasNonBlockedRestriction()) {
                    $select->where("($isEditable OR $isTagFaultyOrNotConsistent)");
                } else {
                    $select->where("($isEditable OR $isTagFaulty)");
                }
            } else {
                if ($state->hasNonBlockedRestriction()) {
                    $select->where("(($isEditable AND NOT $isTagFaulty) OR ($isNotEditable AND $isTagFaultyOrNotConsistent))");
                } else {
                    $select->where("(($isEditable AND NOT $isTagFaulty) OR ($isNotEditable AND $isTagFaulty))");
                }
            }
        }
        if ($state->hasCheckedCategoriesByType()) {
            $nested = $table->select();
            foreach ($state->getCheckedCategoriesByType() as $type => $categories) {
                $condition = $adapter->quoteInto('type = ?', $type) . ' AND ';
                $condition .= (count($categories) == 1) ? $adapter->quoteInto('category = ?', $categories[0]) : $adapter->quoteInto('category IN (?)', $categories);
                $nested->orWhere($condition);
            }
            $select->where(implode(' ', $nested->getPart(Zend_Db_Select::WHERE)));
            // false positives only if filtered at all
            if ($state->hasFalsePositiveRestriction()) {
                $select->where('falsePositive = ?', $state->getFalsePositiveRestriction(), Zend_Db::INT_TYPE);
            }
        }
        $segmentIds = [];
        // DEBUG
        // error_log('FETCH SEGMENT-IDS FOR QUALITY FILTER: '.$select->__toString());
        foreach ($adapter->fetchAll($select, [], Zend_Db::FETCH_ASSOC) as $row) {
            $segmentIds[] = $row['segmentId'];
        }

        return $segmentIds;
    }

    /**
     * Generates a list of segmentIds of all faulty segments (= Segments with internal tag errors) off a task
     */
    public static function getFaultySegmentIds(string $taskGuid): array
    {
        $table = new self();
        $adapter = $table->getAdapter();
        $select = $adapter->select();
        $select
            ->from($table->getName(), 'segmentId')
            ->where('taskGuid = ?', $taskGuid)
            ->where('type = ?', editor_Segment_Tag::TYPE_INTERNAL)
            ->where('category = ?', editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY);
        $segmentIds = [];
        foreach ($adapter->fetchAll($select, [], Zend_Db::FETCH_ASSOC) as $row) {
            $segmentIds[] = $row['segmentId'];
        }

        return $segmentIds;
    }

    public static function addOrRemoveQmForSegment(editor_Models_Task $task, int $segmentId, int $qmCategoryIndex, string $action): stdClass
    {
        $result = new stdClass();
        $result->success = false;
        $result->qualityId = null;
        $result->qualityRow = null;
        $table = new self();
        $category = editor_Segment_Qm_Provider::createCategoryVal($qmCategoryIndex);
        if ($action == 'remove') {
            $rows = $table->fetchFiltered($task->getTaskGuid(), $segmentId, editor_Segment_Tag::TYPE_QM, false, $category);
            if (count($rows) == 1) {
                $result->qualityId = $rows[0]->id;
                $result->success = true;
                $rows[0]->delete();

                return $result;
            }
        } else {
            /* @var editor_Models_Db_SegmentQualityRow $row */
            $row = $table->createRow();
            $row->segmentId = $segmentId;
            $row->taskGuid = $task->getTaskGuid();
            $row->type = editor_Segment_Tag::TYPE_QM;
            $row->category = $category;
            $row->categoryIndex = $qmCategoryIndex;
            $row->save();
            // this will be the base for the returned data model in the quality controller
            $result->qualityId = $row->id;
            $result->qualityRow = $row;
            $result->success = true;

            return $result;
        }

        return $result;
    }

    /**
     * Retrieves the important quality props for a task as relevant for the task overview
     * This is the amount of non false-positive qualities and the number of faults
     * @return stdClass: model with the two props numQualities & numFaults
     */
    public static function getNumQualitiesAndFaultsForTask(string $taskGuid): stdClass
    {
        $result = new stdClass();
        $result->numQualities = 0;
        $result->numFaults = 0;
        $table = new self();
        $db = $table->getAdapter();
        $sql = $db->quoteInto('SELECT `type`, `category`, `falsePositive` FROM ' . $db->quoteIdentifier($table->getName()) . ' WHERE taskGuid = ?', $taskGuid);
        foreach ($db->fetchAll($sql, [], Zend_Db::FETCH_ASSOC) as $row) {
            if ($row['falsePositive'] == 0) {
                $result->numQualities++;
            }
            if (editor_Segment_Internal_TagComparision::isFault($row['type'], $row['category'])) {
                $result->numFaults++;
            }
        }

        return $result;
    }

    protected $_name = 'LEK_segment_quality';

    protected $_rowClass = 'editor_Models_Db_SegmentQualityRow';

    public $_primary = 'id';

    /**
     * Apart from ::fetchForFrontend Central API to fetch quality rows, mostly for frontend purposes
     * @param int|array $segmentIds
     * @param string|array $types
     * @param string|array $categories
     */
    public function fetchFiltered(string $taskGuid = null, $segmentIds = null, $types = null, bool $typesAreBlacklist = false, $categories = null): Zend_Db_Table_Rowset_Abstract
    {
        $select = $this->select();
        $select->where('hidden = 0');

        if (! empty($taskGuid)) {
            $select->where('taskGuid = ?', $taskGuid);
        }
        if ($segmentIds !== null) {
            if (is_array($segmentIds) && count($segmentIds) > 1) {
                $select->where('segmentId IN (?)', $segmentIds, Zend_Db::INT_TYPE);
            } elseif (! is_array($segmentIds) || count($segmentIds) == 1) {
                $segmentId = is_array($segmentIds) ? $segmentIds[0] : $segmentIds;
                $select->where('segmentId = ?', $segmentId, Zend_Db::INT_TYPE);
            }
        }
        if (! empty($types)) { // $types can not be "0"...
            if (is_array($types) && count($types) > 1) {
                $operator = ($typesAreBlacklist) ? 'NOT IN' : 'IN';
                $select->where('type ' . $operator . ' (?)', $types);
            } else {
                $type = is_array($types) ? $types[0] : $types;
                $operator = ($typesAreBlacklist) ? '!=' : '=';
                $select->where('type ' . $operator . ' ?', $type);
            }
        }
        if (! empty($categories)) { // $categories can not be "0"...
            if (is_array($categories) && count($categories) > 1) {
                $select->where('category IN (?)', $categories);
            } else {
                $category = is_array($categories) ? $categories[0] : $categories;
                $select->where('category = ?', $category);
            }
        }
        $order = ['type ASC', 'category ASC'];

        // DEBUG
        // error_log('FETCH FILTERD QUALITIES: '.$select->__toString().' / order: '.implode(', ', $order));
        return $this->fetchAll($select, $order);
    }

    /**
     * The main selection of qualities for frontend purposes
     * In the frontend, qualities for non-editable segments will not be shown.
     * Only structural internal tag errors must be shown even for non-editable segments
     * @param boolean $falsePositiveRestriction
     * @return array: array of assoc array with all columns of LEK_segment_quality plus a key "editable"
     */
    public function fetchForFrontend(
        ?string $taskGuid,
        array $typesBlacklist,
        ?array $segmentNrRestriction = null,
        ?bool $falsePositiveRestriction = null,
        ?string $field = null
    ): iterable {
        $select = $this->getAdapter()->select();

        // Shortcuts
        $internal = editor_Segment_Tag::TYPE_INTERNAL;
        $faulty = editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY;
        $consistent = editor_Segment_Consistent_QualityProvider::qualityType();

        $select
            ->from([
                'qualities' => $this->getName(),
            ], 'qualities.*')
            // we need the editable prop for assigning structural faults of non-editable segments a virtual category
            ->from([
                'segments' => 'LEK_segments',
            ], 'segments.editable')
            ->where('qualities.segmentId = segments.id')
            ->where('qualities.hidden = 0')
            // we want qualities from editable segments, only exception are structural internal tag errors
            // as usual, Zend Selects do not provide proper bracketing, so we're crating this manually here
            ->where("segments.editable = 1 OR (
                (qualities.type = '$internal' AND qualities.category = '$faulty')
                OR
                (qualities.type = '$consistent')
            )");

        if (null !== $segmentNrRestriction) {
            if (! empty($segmentNrRestriction)) {
                if (count($segmentNrRestriction) > 1) {
                    $select->where('segments.segmentNrInTask IN (?)', $segmentNrRestriction);
                } else {
                    $select->where('segments.segmentNrInTask = ?', $segmentNrRestriction[0]);
                }
            } else {
                // an empty array means the user has no segments to edit and thus disables the filter
                $select->where('0 = 1');
            }
        }

        if (! empty($taskGuid)) {
            $select->where('qualities.taskGuid = ?', $taskGuid);
        }

        if ($field) {
            // a quality with no field set applies for all fields !
            $select->where('qualities.field = ? OR ' . 'qualities.field = \'\'', $field);
        }

        if (! empty($typesBlacklist)) { // $typesBlacklist can not be "0"...
            if (count($typesBlacklist) > 1) {
                $select->where('qualities.type NOT IN (?)', $typesBlacklist);
            } else {
                $type = is_array($typesBlacklist) ? $typesBlacklist[0] : $typesBlacklist;
                $select->where('qualities.type != ?', $type);
            }
        }
        if ($falsePositiveRestriction !== null) {
            $select->where('qualities.falsePositive = ?', $falsePositiveRestriction, Zend_Db::INT_TYPE);
        }
        $select->order(['qualities.type ASC', 'qualities.category ASC']);
        // DEBUG
        // error_log('FETCH QUALITIES FOR FRONTEND: '.$select->__toString());
        $stmt = $this->getAdapter()->query($select);

        while ($row = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * Deletes quality-entries by their ID
     */
    public function deleteByIds(array $qualityIds)
    {
        if (count($qualityIds) > 0) {
            $db = $this->getAdapter();
            $where = (count($qualityIds) > 1) ? $db->quoteInto('id IN (?)', $qualityIds) : $db->quoteInto('id = ?', $qualityIds[0]);
            $db->delete($this->getName(), $where);
        }
    }

    public function removeBySegmentAndType(int $segmentId, string $type, array $categories = []): int
    {
        $where = [];
        $where[] = $this->getAdapter()->quoteInto('segmentId = ?', $segmentId);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);
        if ($categories) {
            $where[] = $this->getAdapter()->quoteInto('category IN (?)', $categories);
        }

        return $this->delete($where);
    }

    /**
     * Removes all qualities for a task and a certain type
     */
    public function removeByTaskGuidAndType(string $taskGuid, string $type): int
    {
        $where = [];
        $where[] = $this->getAdapter()->quoteInto('taskGuid = ?', $taskGuid);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);

        return $this->delete($where);
    }

    /**
     * Removes all qualities for a task
     */
    public function removeByTaskGuid(string $taskGuid): int
    {
        return $this->delete([$this->getAdapter()->quoteInto('taskGuid = ?', $taskGuid)]);
    }

    public function getName(): string
    {
        return $this->_name;
    }
}
