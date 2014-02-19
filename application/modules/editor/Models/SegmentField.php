<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * Entity Model for segment fields
 */
class editor_Models_SegmentField extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_SegmentField';

    /**
     * @param string $taskGuid
     * @return array
     */
    public function loadByTaskGuid($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        return $this->db->getAdapter()->fetchAll($s);
    }
    
    /**
     * creates the nam of the data view
     * @param string $taskGuid
     * @return string
     */
    public function getDataViewName($taskGuid) {
        return "data_" . md5($taskGuid);
    }
    
    /**
     * create / update View
     * @param string $taskguid
     */
    public function updateView($taskguid)
    {
        if(empty($taskguid)) {
            // TODO add error handling
            return;
        }
        $cols = "*";
        // TODO get columns

        $this->initField($taskguid);
        $this->initData($taskguid);

        $sView_data_name         = $this->getDataViewName($taskguid);
        $sView_segment_name      = "segment_" . md5($taskguid);

        $sStmt_drop_view_data       = "DROP VIEW IF EXISTS " . $sView_data_name;
        $sStmt_drop_view_segment    = "DROP VIEW IF EXISTS " . $sView_segment_name;

        $sStmt_create_view_data = "CREATE VIEW " . $sView_data_name . " AS ";
        $sStmt_create_view_data .= "
        SELECT
            LEK_segment_data.segmentId,
            LEK_segment_data.original,
            LEK_segment_data.originalMd5,
            LEK_segment_data.originalToSort,
            LEK_segment_data.edited,
            LEK_segment_data.editedMd5,
            LEK_segment_data.editedToSort,

            LEK_segment_field.label,
            LEK_segment_field.rankable,
            LEK_segment_field.editable,
             ";

        $sStmt_create_view_data = join(',', array_map(function($value) {
            $name = $this->db->getAdapter()->quote($value['name']);
            return "MAX(IF(LEK_segment_data.name = '".$name."', edited, NULL)) AS '".$name."'";
        }, $this->_segmentdata));
        
        $sStmt_create_view_data .= " FROM LEK_segment_data
        JOIN LEK_segment_field
        ON LEK_segment_data.name = LEK_segment_field.name
        GROUP BY segmentId
        ";

        $sStmt_create_view_segment = "CREATE VIEW " . $sView_segment_name . " AS ";
        $sStmt_create_view_segment .= "
            SELECT LEK_segments." . $cols . "
            FROM `LEK_segments`
            join " . $sView_data_name . "
            AS data ON data.segmentId = LEK_segments.id
            WHERE `taskGuid` = '".$taskguid."'
        ";

        $this->db->getAdapter()->getConnection()->exec($sStmt_drop_view_data);
        $this->db->getAdapter()->getConnection()->exec($sStmt_drop_view_segment);
        $this->db->getAdapter()->getConnection()->exec($sStmt_create_view_data);
        $this->db->getAdapter()->getConnection()->exec($sStmt_create_view_segment);
        return;
    }
    /**
     * create / update View
     * @param string $userguid
     */
    public function updateHistoryView($userguid)
    {
        if(empty($userguid)) {
            // TODO add error handling
            return;
        }
        $cols = "*";
        // TODO get columns
        $this->initHistoryData($userguid);

        $sView_data_history_name         = "data_history_" . md5($userguid);
        $sView_segment_history_name      = "segment_history_" . md5($userguid);

        $sStmt_drop_view_data_history       = "DROP VIEW IF EXISTS " . $sView_data_history_name;
        $sStmt_drop_view_segment_history    = "DROP VIEW IF EXISTS " . $sView_segment_history_name;


        $sStmt_create_view_data_history = "CREATE VIEW " . $sView_data_history_name . " AS ";
        $sStmt_create_view_data_history .= "
        SELECT
            LEK_segment_history_data.segmentId,
            LEK_segment_history_data.edited,
             ";
        foreach($this->_segmentdata as $value){
            $sStmt_create_view_data_history .= "MAX(IF(LEK_segment_history_data.name = '".$value['name']."', edited, NULL)) AS '".$value['name']."',";
        }
        $sStmt_create_view_data_history = substr($sStmt_create_view_data_history, 0, (strlen($sStmt_create_view_data_history)-1));
        $sStmt_create_view_data_history .= " FROM LEK_segment_history_data GROUP BY segmentId";

        $sStmt_create_view_segment_history = "CREATE VIEW " . $sView_segment_history_name . " AS ";
        $sStmt_create_view_segment_history .= "
            SELECT  LEK_segment_history." . $cols . "
            FROM `LEK_segment_history`
            join " . $sView_data_history_name . "
            AS data ON data.segmentId = LEK_segment_history.id
            WHERE `userGuid` = '".$userguid."'
        ";

        $this->db->getAdapter()->getConnection()->exec($sStmt_drop_view_data_history);
        $this->db->getAdapter()->getConnection()->exec($sStmt_drop_view_segment_history);
        $this->db->getAdapter()->getConnection()->exec($sStmt_create_view_data_history);
        $this->db->getAdapter()->getConnection()->exec($sStmt_create_view_segment_history);
        return;
    }
} 