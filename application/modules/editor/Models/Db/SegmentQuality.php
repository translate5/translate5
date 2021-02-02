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
            /* @var $table editor_Models_Db_SegmentQuality[] */
            $db = $table->getAdapter();
            $where = (count($segmentIds) > 1) ? $db->quoteInto('segmentId IN (?)', $segmentIds) : $db->quoteInto('segmentId = ?', $segmentIds[0]);
            $db->delete($table->getName(), $where);
        }
    }
    /**
     * 
     * @param Zend_Db_Table_Row_Abstract[] $rows
     */
    public static function saveRows(array $rows){
        if(count($rows) == 1) {
            $rows[0]->save();
        } else if(count($rows) > 1){
            $table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
            /* @var $table editor_Models_Db_SegmentQuality[] */
            $db = $table->getAdapter();
            $cols = [];
            foreach($table->info(Zend_Db_Table_Abstract::COLS) as $col){
                if($col != 'id'){
                    $cols[] = $col;
                }
            }
            $rowvals = [];
            foreach($rows as $row){ /* @var $row Zend_Db_Table_Row_Abstract */
                $vals = [];
                foreach($cols as $col){
                    $vals[] = $db->quote($row->$col);
                }
                $rowvals[] = '('.implode(',', $vals).')';
            }
            $db->query('INSERT INTO '.$db->quoteIdentifier($table->getName()).' (`'.implode('`,`', $cols).'`) VALUES '.implode(',', $rowvals));
        }
    }

    protected $_name  = 'LEK_segment_quality';
    protected $_rowClass = 'editor_Models_Db_SegmentQualityRow';
    public $_primary = 'id';
    
    /**
     * 
     * @return string
     */
    protected function getName(){
        return $this->_name;
    }
}
