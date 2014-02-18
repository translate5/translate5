<?php
/**
 * Created by PhpStorm.
 * User: kkolesnikov
 * Date: 2/10/14
 * Time: 10:16 AM
 */

class editor_Models_Segmentfield extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Segmentfield';

    public function loadBytaskGuid($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        return $this->db->getAdapter()->fetchAll($s);
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

        $sView_data_name         = "data_" . md5($taskguid);
        $sView_segment_name      = "segment_" . md5($taskguid);

        $sStmt_drop_view_data       = "DROP VIEW IF EXISTS " . $sView_data_name;
        $sStmt_drop_view_segment    = "DROP VIEW IF EXISTS " . $sView_segment_name;


        $sStmt_create_view_data = "CREATE VIEW " . $sView_data_name . " AS ";
        $sStmt_create_view_data .= "
        SELECT
            LEK_segment_data.segmentId,
            LEK_segment_data.origina,
            LEK_segment_data.originalMd5,
            LEK_segment_data.originalToSort,
            LEK_segment_data.edited,
            LEK_segment_data.editedMd5,
            LEK_segment_data.editedToSort,

            LEK_segment_field.label,
            LEK_segment_field.rankable,
            LEK_segment_field.editable,
             ";
        foreach($this->_segmentdata as $value){
            $sStmt_create_view_data .= "MAX(IF(LEK_segment_data.name = '".$value['name']."', edited, NULL)) AS '".$value['name']."',";
        }
        $sStmt_create_view_data = substr($sStmt_create_view_data, 0, (strlen($sStmt_create_view_data)-1));
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
            LEK_segment_history_data.origina,
            LEK_segment_history_data.originalMd5,
            LEK_segment_history_data.originalToSort,
            LEK_segment_history_data.edited,
            LEK_segment_history_data.editedMd5,
            LEK_segment_history_data.editedToSort,
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