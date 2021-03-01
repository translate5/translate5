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
    public static function hasTypeForTask(string $taskGuid, string $type){
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
     * @param int $segmentId
     * @param string $type
     * @return int
     */
    public function removeQualitiesBySegmentAndType(int $segmentId, string $type){
        $where = array();
        $where[] = $this->getAdapter()->quoteInto('segmentId = ?', $segmentId);
        $where[] = $this->getAdapter()->quoteInto('type = ?', $type);
        return $this->delete($where);
    }
    /**
     * 
     * @param int $segmentId
     * @param string $taskGuid
     * @param string $field
     * @param string $type
     * @param string $category
     * @param int $mqmType
     * @param string $severity
     * @param string $comment
     * @param int $startIndex
     * @param int $endIndex
     * @param int $falsePositive
     * @return number
     */
    public function saveQuality(int $segmentId, string $taskGuid, string $field, string $type, string $category=NULL, int $mqmType=-1, string $severity=NULL, string $comment=NULL, int $startIndex=0, int $endIndex=-1, int $falsePositive=0){
        $row = $this->createRow();
        /* @var $row editor_Models_Db_SegmentQualityRow */
        $row->segmentId = $segmentId;
        $row->taskGuid = $taskGuid;
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
        
        return $row->id;
    }
    /**
     * 
     * @return string
     */
    protected function getName(){
        return $this->_name;
    }
}
