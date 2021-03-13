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
     * Deletes all existing entries for the given segmentId
     * @param int $segmentId
     */
    public static function deleteForSegment(int $segmentId){
        self::deleteForSegments([$segmentId]);
    }
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
    
    protected $_name  = 'LEK_segment_quality';
    
    protected $_rowClass = 'editor_Models_Db_SegmentQualityRow';
    
    public $_primary = 'id';
    
    /**
     * 
     * @param string $field
     * @return string
     */
    public function createFieldCondition($field) : string {
        $adapter = $this->getAdapter();
        return
            '(fields = '.$adapter->quote($field)
            .' OR fields LIKE '.$adapter->quote($field.',%')
            .' OR fields LIKE '.$adapter->quote('%,'.$field)
            .' OR fields LIKE '.$adapter->quote('%,'.$field.',%').')';
    }
    /**
     * 
     * @param string $taskGuid
     * @param int|array $segmentIds
     * @param string $field
     * @param string|array $types
     * @param bool $typesIsBlacklist
     * @param string|array $categories
     * @param string|array $order
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function fetchFiltered(string $taskGuid=NULL, $segmentIds=NULL, string $field=NULL, $types=NULL, bool $typesIsBlacklist=false, $categories=NULL, $order=NULL) : Zend_Db_Table_Rowset_Abstract {
        $select = $this->select();
        if(!empty($taskGuid)){
            $select->where('taskGuid = ?', $taskGuid);
        }
        if($segmentIds !== NULL){
            if(is_array($segmentIds) && count($segmentIds) > 1){
                $select->where('segmentId IN (?)', $segmentIds);
            } else if(!is_array($segmentIds) || count($segmentIds) == 1){
                $segmentId = is_array($segmentIds) ? $segmentIds[0] : $segmentIds;
                $select->where('segmentId = ?', $segmentId);
            }
        }
        if($field != NULL){
            $select->where($this->createFieldCondition($field));
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
        if($order == NULL){
            $order = [ 'type ASC', 'category ASC' ];
        }
        return $this->fetchAll($select, $order);
    }
    /**
     * 
     * @param int $segmentId
     * @param string $type
     * @return int
     */
    public function removeQualitiesBySegmentAndType(int $segmentId, string $type) : int {
        $where = array();
        $where[] = $this->getAdapter()->quoteInto('segmentId = ?', $segmentId);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);
        return $this->delete($where);
    }
    /**
     * adding a general quality. To add a quality without segment-quality contraint, set segmentId to -1
     * 
     * @param string $taskGuid
     * @param int $segmentId
     * @param string $field
     * @param string $type
     * @param string $category
     * @param int $mqmType
     * @param string $severity
     * @param string $comment
     * @param int $startIndex
     * @param int $endIndex
     * @param int $falsePositive
     * @return editor_Models_Db_SegmentQualityRow
     */
    public function addQuality(string $taskGuid, int $segmentId, string $field, string $type, string $category=NULL, int $mqmType=-1, string $severity=NULL, string $comment=NULL, int $startIndex=0, int $endIndex=-1, int $falsePositive=0) : editor_Models_Db_SegmentQualityRow {
        $row = $this->createRow();
        /* @var $row editor_Models_Db_SegmentQualityRow */
        
        $row->taskGuid = $taskGuid;
        $row->segmentId = ($segmentId == -1) ? NULL : $segmentId;
        $row->setField($field);
        $row->type = $type;
        $row->category = $category;
        $row->startIndex = $startIndex;
        $row->endIndex = $endIndex;
        $row->falsePositive = $falsePositive;
        $row->mqmType = $mqmType;
        $row->severity = $severity;
        $row->comment = $comment;
        $row->save();
        
        return $row;
    }
    /**
     * adding a MQM quality. To add a quality without segment-quality contraint, set segmentId to -1
     * 
     * @param string $taskGuid
     * @param int $segmentId
     * @param string $field
     * @param int $typeIndex
     * @param string $severity
     * @param string $comment
     * @param int $startIndex
     * @param int $endIndex
     * @return editor_Models_Db_SegmentQualityRow
     */
    public function addMqm(string $taskGuid, int $segmentId, string $field, int $typeIndex, string $severity, string $comment, int $startIndex=0, int $endIndex=-1) : editor_Models_Db_SegmentQualityRow {
         return $this->addQuality(
            $taskGuid,
            $segmentId,
            $field,
            editor_Segment_Tag::TYPE_MQM,
            editor_Segment_Tag::TYPE_MQM.'_'.strval($typeIndex),
            $typeIndex,
            $severity,
            $comment,
            $startIndex,
            $endIndex);
    }
    /**
     * 
     * @return string
     */
    public function getName() : string {
        return $this->_name;
    }
}
