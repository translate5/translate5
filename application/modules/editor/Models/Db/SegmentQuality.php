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

class editor_Models_Db_SegmentQuality extends Zend_Db_Table_Abstract {
    
    /**
     * Deletes all existing entries for the given segmentIds
     * @param array $segmentIds
     */
    public static function deleteForSegments(array $segmentIds){
        if(count($segmentIds) > 0){
            $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
            /* @var $table editor_Models_Db_SegmentQuality */
            $db = $table->getAdapter();
            $where = (count($segmentIds) > 1) ? $db->quoteInto('segmentId IN (?)', $segmentIds) : $db->quoteInto('segmentId = ?', $segmentIds[0]);
            $db->delete($table->getName(), $where);
        }
    }
    /**
     * 
     * @param editor_Models_Db_SegmentQualityRow[] $rows
     */
    public static function saveRows(array $rows){
        if(count($rows) > 1){
            $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
            /* @var $table editor_Models_Db_SegmentQuality */
            $db = $table->getAdapter();
            $cols = [];
            foreach($table->info(Zend_Db_Table_Abstract::COLS) as $col){
                if($col != 'id'){
                    $cols[] = $col;
                }
            }
            $rowvals = [];
            foreach($rows as $row){ /* @var $row editor_Models_Db_SegmentQualityRow */
                $vals = [];
                foreach($cols as $col){
                    $vals[] = ($row->$col === NULL) ? 'NULL' : $db->quote($row->$col);
                }
                $rowvals[] = '('.implode(',', $vals).')';
            }
            $db->query('INSERT INTO '.$db->quoteIdentifier($table->getName()).' (`'.implode('`,`', $cols).'`) VALUES '.implode(',', $rowvals));
        } else if(count($rows) > 0){
            $rows[0]->save();
        }
    }
    /**
     * Checks whether a certain quality of the given type and category exists for he task. If category is not provided checks only for type
     * @param string $taskGuid
     * @param string $type
     * @param string $category
     * @return bool
     */
    public static function hasTypeCategoryForTask(string $taskGuid, string $type, string $category=NULL) : bool {
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $where = $table->select()
            ->from($table->getName(), ['id'])
            ->where('taskGuid = ?', $taskGuid)
            ->where('type = ?', $type);
        if($category != NULL){
            $where->where('category = ?', $category);
        }
        return (count($table->fetchAll($where)) > 0);
    }
    /**
     * Generates a list of segmentIds to be used as filter in the segment controller's quality filtering
     * @param editor_Models_Quality_RequestState $state
     * @param string $taskGuid
     * @return int[]
     */
    public static function getSegmentIdsForQualityFilter(editor_Models_Quality_RequestState $state, string $taskGuid) : array {
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $adapter = $table->getAdapter();
        $select = $adapter->select();
        $select
            ->from(['qualities' => $table->getName()], 'qualities.segmentId')
            ->where('qualities.taskGuid = ?', $taskGuid);
        // if the state has no editable restriction this means, that the editable restriction must be applied here but not for internal tag faults
        if(!$state->hasEditableRestriction()){
            $faultyType = "'".editor_Segment_Tag::TYPE_INTERNAL."'";
            $faultyCat = "'".editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY."'";
            $select
                ->from(['segments' => 'LEK_segments'], [])
                ->where('qualities.segmentId = segments.id');
            // here it's where it get's really finnicky: we have to evaluate the editable-category only, if it can't be applied in editor_Models_Filter_SegmentSpecific
            // that means, we do have other categories apart of the non-editable faulty tags, but that may also includes the editable faulty-tags
            if($state->hasCategoryEditableInternalTagFaults()){
                $select->where('(segments.editable = 1 OR (qualities.type = '.$faultyType.' AND qualities.category = '.$faultyCat.'))');
            } else {
                $select->where(
                    '((segments.editable = 1 AND NOT (qualities.type = '.$faultyType.' AND qualities.category = '.$faultyCat.')) '
                    .'OR (segments.editable = 0 AND qualities.type = '.$faultyType.' AND qualities.category = '.$faultyCat.'))');
            }
        }
        if($state->hasCheckedCategoriesByType()){
            $nested = $table->select();
            foreach($state->getCheckedCategoriesByType() as $type => $categories){
                $condition = $adapter->quoteInto('type = ?', $type).' AND ';
                $condition .= (count($categories) == 1) ? $adapter->quoteInto('category = ?', $categories[0]) : $adapter->quoteInto('category IN (?)', $categories);
                $nested->orWhere($condition);
            }
            $select->where(implode(' ', $nested->getPart(Zend_Db_Select::WHERE)));
            // false positives only if filtered at all
            if($state->hasFalsePositiveRestriction()){
                $select->where('falsePositive = ?', $state->getFalsePositiveRestriction(), Zend_Db::INT_TYPE);
            }
        }
        $segmentIds = [];
        // DEBUG
        // error_log('FETCH SEGMENT-IDS FOR QUALITY FILTER: '.$select->__toString());
        foreach($adapter->fetchAll($select, [], Zend_Db::FETCH_ASSOC) as $row){
            $segmentIds[] = $row['segmentId'];
        }
        return $segmentIds;
    }
    /**
     * Generates a list of segmentIds of all faulty segments (= Segments with internal tag errors) off a task
     * @param string $taskGuid
     * @return array
     */
    public static function getFaultySegmentIds(string $taskGuid) : array {
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $adapter = $table->getAdapter();
        $select = $adapter->select();
        $select
            ->from($table->getName(), 'segmentId')
            ->where('taskGuid = ?', $taskGuid)
            ->where('type = ?', editor_Segment_Tag::TYPE_INTERNAL)
            ->where('category = ?', editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY);
        $segmentIds = [];
        foreach($adapter->fetchAll($select, [], Zend_Db::FETCH_ASSOC) as $row){
            $segmentIds[] = $row['segmentId'];
        }
        return $segmentIds;
    }
    /**
     * 
     * @param string $taskGuid
     * @param int $segmentId
     * @param int $qmCategoryIndex
     * @param string $action
     * @return stdClass
     */
    public static function addOrRemoveQmForSegment(editor_Models_Task $task, int $segmentId, int $qmCategoryIndex, string $action) : stdClass {
        $result = new stdClass();
        $result->success = false;
        $result->qualityId = null;
        $result->qualityRow = null;
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $category = editor_Segment_Qm_Provider::createCategoryVal($qmCategoryIndex);
        if($action == 'remove'){
            $rows = $table->fetchFiltered($task->getTaskGuid(), $segmentId, editor_Segment_Tag::TYPE_QM, false, $category);
            if(count($rows) == 1){
                $result->qualityId = $rows[0]->id;
                $result->success = true;
                $rows[0]->delete();                
                return $result;
            }
        } else {
            $row = $table->createRow();
            /* @var $row editor_Models_Db_SegmentQualityRow */
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
     * @param string $taskGuid
     * @return stdClass: model with the two props numQualities & numFaults
     */
    public static function getNumQualitiesAndFaultsForTask(string $taskGuid) : stdClass {
        $result = new stdClass();
        $result->numQualities = 0;
        $result->numFaults = 0;
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $db = $table->getAdapter();
        $sql = $db->quoteInto('SELECT `type`, `category`, `falsePositive` FROM '.$db->quoteIdentifier($table->getName()).' WHERE taskGuid = ?', $taskGuid);
        foreach($db->fetchAll($sql, [], Zend_Db::FETCH_ASSOC) as $row){
            if($row['falsePositive'] == 0){
                $result->numQualities++;
            }
            if(editor_Segment_Internal_TagComparision::isFault($row['type'], $row['category'])){
                $result->numFaults++;
            }
        }
        return $result;
    }    

    protected $_name  = 'LEK_segment_quality';
    
    protected $_rowClass = 'editor_Models_Db_SegmentQualityRow';
    
    public $_primary = 'id';

    /**
     * Apart from ::fetchForFrontend Central API to fetch quality rows, mostly for frontend purposes
     * @param string $taskGuid
     * @param int|array $segmentIds
     * @param string|array $types
     * @param bool $typesAreBlacklist
     * @param string|array $categories
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchFiltered(string $taskGuid=NULL, $segmentIds=NULL, $types=NULL, bool $typesAreBlacklist=false, $categories=NULL) : Zend_Db_Table_Rowset_Abstract {
        $select = $this->select();
        if(!empty($taskGuid)){
            $select->where('taskGuid = ?', $taskGuid);
        }
        if($segmentIds !== NULL){
            if(is_array($segmentIds) && count($segmentIds) > 1){
                $select->where('segmentId IN (?)', $segmentIds, Zend_Db::INT_TYPE);
            } else if(!is_array($segmentIds) || count($segmentIds) == 1){
                $segmentId = is_array($segmentIds) ? $segmentIds[0] : $segmentIds;
                $select->where('segmentId = ?', $segmentId, Zend_Db::INT_TYPE);
            }
        }
        if(!empty($types)){ // $types can not be "0"...
            if(is_array($types) && count($types) > 1){
                $operator = ($typesAreBlacklist) ? 'NOT IN' : 'IN';
                $select->where('type '.$operator.' (?)', $types);
            } else {
                $type = is_array($types) ? $types[0] : $types;
                $operator = ($typesAreBlacklist) ? '!=' : '=';
                $select->where('type '.$operator.' ?', $type);
            }
        }
        if(!empty($categories)){ // $categories can not be "0"...
            if(is_array($categories) && count($categories) > 1){
                $select->where('category IN (?)', $categories);
            } else {
                $category = is_array($categories) ? $categories[0] : $categories;
                $select->where('category = ?', $category);
            }
        }
        $order = [ 'type ASC', 'category ASC' ];
        // DEBUG
        // error_log('FETCH FILTERD QUALITIES: '.$select->__toString().' / order: '.implode(', ', $order)); 
        return $this->fetchAll($select, $order);
    }
    /**
     * The main selection of qualities for frontend purposes
     * In the frontend, qualities for non-editable segments will not be shown. Only structural internal tag errors must be shown even for non-editable segments
     * @param string $taskGuid
     * @param array $typesBlacklist
     * @param array $segmentNrRestriction
     * @param boolean $falsePositiveRestriction
     * @param string $field
     * @return array: array of assoc array with all columns of LEK_segment_quality plus a key "editable"
     */
    public function fetchForFrontend(string $taskGuid=NULL, array $typesBlacklist=NULL, array $segmentNrRestriction=NULL, bool $falsePositiveRestriction=NULL, string $field=NULL) : array {
        $select = $this->getAdapter()->select();
        $select
            ->from(['qualities' => $this->getName()], 'qualities.*')
            ->from(['segments' => 'LEK_segments'], 'segments.editable') // we need the editable prop for assigning structural faults of non-editable segments a virtual category
            ->where('qualities.segmentId = segments.id')
            // we want qualities from editable segments, only exception are structural internal tag errors
            // as usual, Zend Selects do not provide proper bracketing, so we're crating this manually here
            ->where('segments.editable = 1 OR (qualities.type = \''.editor_Segment_Tag::TYPE_INTERNAL.'\' AND qualities.category = \''.editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY.'\')');
        if($segmentNrRestriction !== NULL){
            if(count($segmentNrRestriction) > 0){
                if(count($segmentNrRestriction) > 1){
                    $select->where('segments.segmentNrInTask IN (?)', $segmentNrRestriction);
                } else {
                    $select->where('segments.segmentNrInTask = ?', $segmentNrRestriction[0]);
                }
            } else {
                // an empty array means the user has no segments to edit and thus disables the filter
                $select->where('0 = 1');
            }
        }
        if(!empty($taskGuid)){
            $select->where('qualities.taskGuid = ?', $taskGuid);
        }
        if($field != NULL){
            // a quality with no field set applies for all fields !
            $select->where('qualities.field = ? OR '.'qualities.field = \'\'', $field);
        }
        if(!empty($typesBlacklist)){ // $typesBlacklist can not be "0"...
            if(is_array($typesBlacklist) && count($typesBlacklist) > 1){
                $select->where('qualities.type NOT IN (?)', $typesBlacklist);
            } else {
                $type = is_array($typesBlacklist) ? $typesBlacklist[0] : $typesBlacklist;
                $select->where('qualities.type != ?', $type);
            }
        }
        if($falsePositiveRestriction !== NULL){
            $select->where('qualities.falsePositive = ?', $falsePositiveRestriction, Zend_Db::INT_TYPE);
        }
        $select->order([ 'qualities.type ASC', 'qualities.category ASC' ]);
        // DEBUG
        // error_log('FETCH QUALITIES FOR FRONTEND: '.$select->__toString());
        return $this->getAdapter()->fetchAll($select, [], Zend_Db::FETCH_ASSOC);
    }
    /**
     * Deletes quality-entries by their ID
     * @param array $qualityIds
     */
    public function deleteByIds(array $qualityIds){
        if(count($qualityIds) > 0){
            $db = $this->getAdapter();
            $where = (count($qualityIds) > 1) ? $db->quoteInto('id IN (?)', $qualityIds) : $db->quoteInto('id = ?', $qualityIds[0]);
            $db->delete($this->getName(), $where);
        }
    }
    /**
     * 
     * @param int $segmentId
     * @param string $type
     * @return int
     */
    public function removeBySegmentAndType(int $segmentId, string $type, array $categories = []) : int {
        $where = [];
        $where[] = $this->getAdapter()->quoteInto('segmentId = ?', $segmentId);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);
        if ($categories) $where[] = $this->getAdapter()->quoteInto('category IN (?)', $categories);
        return $this->delete($where);
    }
    /**
     * Removes all qualities for a task and a certain type
     * @param string $taskGuid
     * @param string $type
     * @return int
     */
    public function removeByTaskGuidAndType(string $taskGuid, string $type) : int {
        $where = [];
        $where[] = $this->getAdapter()->quoteInto('taskGuid = ?', $taskGuid);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);
        return $this->delete($where);
    }
    /**
     * Removes all qualities for a task
     * @param string $taskGuid
     * @return int
     */
    public function removeByTaskGuid(string $taskGuid) : int {
        return $this->delete([$this->getAdapter()->quoteInto('taskGuid = ?', $taskGuid)]);
    }
    /**
     * 
     * @return string
     */
    public function getName() : string {
        return $this->_name;
    }
}
