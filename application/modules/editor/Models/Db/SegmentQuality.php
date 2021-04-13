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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

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
     * 
     * @param string $taskGuid
     * @param string $type
     * @return boolean
     */
    public static function hasTypeForTask(string $taskGuid, string $type) : bool {
        $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        /* @var $table editor_Models_Db_SegmentQuality */
        $where = $table->select()
            ->from($table->getName(), ['id'])
            ->where('taskGuid = ?', $taskGuid)
            ->where('type = ?', $type);
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
        $select = $table->select()
            ->from($table->getName(), ['segmentId'])
            ->where('taskGuid = ?', $taskGuid);
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
        foreach($table->fetchAll($select, 'segmentId')->toArray() as $row){
            $segmentIds[] = $row['segmentId'];
        }
        return $segmentIds;
    }
    
    protected $_name  = 'LEK_segment_quality';
    
    protected $_rowClass = 'editor_Models_Db_SegmentQualityRow';
    
    public $_primary = 'id';

    /**
     * 
     * @param string $taskGuid
     * @param int|array $segmentIds
     * @param string $field
     * @param string|array $types
     * @param bool $typesIsBlacklist
     * @param string|array $categories
     * @param int $falsePositive
     * @param string|array $order
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchFiltered(string $taskGuid=NULL, $segmentIds=NULL, string $field=NULL, $types=NULL, bool $typesIsBlacklist=false, $categories=NULL, int $falsePositive=NULL, $order=NULL) : Zend_Db_Table_Rowset_Abstract {
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
        if($field != NULL){
            $select->where('field = ?', $field);
        }
        if(!empty($types)){ // $types can not be "0"...
            if(is_array($types) && count($types) > 1){
                $operator = ($typesIsBlacklist) ? 'NOT IN' : 'IN';
                $select->where('type '.$operator.' (?)', $types);
            } else {
                $type = is_array($types) ? $types[0] : $types;
                $operator = ($typesIsBlacklist) ? '!=' : '=';
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
        if($falsePositive !== NULL){
            $select->where('falsePositive = ?', $falsePositive, Zend_Db::INT_TYPE);
        }
        if($order == NULL){
            $order = [ 'type ASC', 'category ASC' ];
        }
        return $this->fetchAll($select, $order);
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
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchBySegment(int $segmentId) : Zend_Db_Table_Rowset_Abstract {
        return $this->fetchFiltered(NULL, $segmentId);
    }
    /**
     * 
     * @param int $segmentId
     * @param string $type
     * @return int
     */
    public function removeBySegmentAndType(int $segmentId, string $type) : int {
        $where = array();
        $where[] = $this->getAdapter()->quoteInto('segmentId = ?', $segmentId);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);
        return $this->delete($where);
    }
    /**
     * 
     * @return string
     */
    public function getName() : string {
        return $this->_name;
    }
}
