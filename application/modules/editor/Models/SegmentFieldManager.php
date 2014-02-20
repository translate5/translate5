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
 * Intelligent Container for the segment fields of a Task
 */
class editor_Models_SegmentFieldManager {
    /**
     * This are the default labels, they are translated on sending the output (in SegmentfieldController)
     */
    const LABEL_SOURCE = 'Ausgangstext';
    const LABEL_TARGET = 'Zieltext'; 
    const LABEL_RELAIS = 'Relaissprache';
    
    /**
     * @var array
     */
    protected $segmentfields;
    
    /**
     * @var array
     */
    protected $firstNameOfType = array();
    
    /**
     * affected taskGuid
     * @var string
     */
    protected $taskGuid;
    
    /**
     * initiates the task specific segment fields
     * @param $taskGuid
     */
    public function initFields($taskGuid) {
        $this->taskGuid = $taskGuid;
        $segmentfield = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $segmentfield editor_Models_SegmentField */
        $fields = $segmentfield->loadByTaskGuidAsRowset($taskGuid);

        $this->segmentfields = array();
        foreach($fields as $field) {
            if(empty($this->firstNameOfType[$field->type])){
                $this->firstNameOfType[$field->type] = $field->name;
            }
            $this->segmentfields[$field->name] = $field;
        }
    }
    
    /**
     * returns a field instance by given name
     * @param string $name
     * @return Zend_Db_Table_Row_Abstract of type SegmentField
     */
    public function getByName($name) {
        return $this->segmentfields[$name];
    }
    
    /**
     * Add the given segment field (for the internally stored taskGuid)
     * @param $label string any string as label
     * @param $type one of the editor_Models_SegmentField::TYPE_... consts
     * @return string returns the fieldname to be used by the segment data instances for this field
     */
    public function addField($label, $type) {
        $maxFieldCnt = 0;
        $fieldCnt = 0;
        foreach($this->segmentfields as $field) {
            //label already exists, so we take this fields name
            if($label === $field->label) {
                return $field->name;
            }
            if($field->type === $type) {
                $fieldCnt = (int) substr($field->name, strlen($field->type));
                error_log(__FILE__.' '.__LINE__." This should be a integer bigger as 0 for multi targets: ".$fieldCnt);
            }
            $maxFieldCnt = max($maxFieldCnt,$fieldCnt);
        }
        $name = $type;
        if($maxFieldCnt > 0) {
            $name .= ($maxFieldCnt + 1);
        }
        $field = ZfExtended_Factory::get('editor_Models_SegmentField');
        /* @var $field editor_Models_SegmentField */
        
        $isTarget = ($type === $field::TYPE_TARGET);
        //FIXME what about translations of the label? → solution: we set the default strings for source target relais as consts in fileparser. Translation is done in place of output. Passt das mit dem CSV Konzept?
        $field->setLabel($label);
        $field->setName($name);
        $field->setTaskGuid($this->taskGuid);
        $field->setType($type);
        $field->setEditable($isTarget); // FIXME or is sourceEditing = true
        $field->setRankable($isTarget);
        $field->save();
        if(empty($this->firstNameOfType[$type])){
            $this->firstNameOfType[$type] = $name;
        }
        $this->segmentfields[$name] = $field->getRowObject();
        return $name;
    }
    
    /**
     * returns the first field name of the desired type
     * @param string $type
     * @return string
     */
    protected function getFirstName($type) {
        if(empty($this->firstNameOfType[$type])) {
            throw new Zend_Exception('Desired Segment Field Type '.$type.' not set!');
        }
        return $this->firstNameOfType[$type];
    }
    
    /**
     * returns the first field name of the desired type
     * @return string
     */
    public function getFirstSourceName() {
        return $this->getFirstName(editor_Models_SegmentField::TYPE_SOURCE);
    }
    
    /**
     * returns the first field name of the desired type
     * @return string
     */
    public function getFirstTargetName() {
        return $this->getFirstName(editor_Models_SegmentField::TYPE_TARGET);
    }
    
    /**
     * returns the first field name of the desired type
     * @return string
     */
    public function getFirstRelaisName() {
        return $this->getFirstName(editor_Models_SegmentField::TYPE_RELAIS);
    }
    
    /**
     * creates the nam of the data view
     * @param string $taskGuid
     * @return string
     */
    public function getDataViewName($taskGuid) {
        return "LEK_segment_" . md5($taskGuid);
    }
    
    /**
     * creates / updates the View of the internal stored taskGuid
     */
    public function updateView() {
        $viewName         = $this->getDataViewName($this->taskGuid);
        $createViewSql = array('CREATE VIEW '.$viewName.' as SELECT s.*');

        foreach($this->segmentfields as $field) {
            $name = $field->name;
            $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.original, NULL)) AS %s', $name, $name);
            $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.originalMd5, NULL)) AS %sMd5', $name, $name);
            $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.originalToSort, NULL)) AS %sToSort', $name, $name);
            if($field->editable) {
                $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.edited, NULL)) AS %sEdit', $name, $name);
                $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.editedMd5, NULL)) AS %sEditMd5', $name, $name);
                $createViewSql[] = sprintf('MAX(IF(d.name = \'%s\', d.editedToSort, NULL)) AS %sEditToSort', $name, $name);
            }
        }
        $createViewSql = join(',', $createViewSql);
        $createViewSql .= ' FROM LEK_segment_data d, LEK_segments s';
        $createViewSql .= ' WHERE d.taskGuid = ? and s.taskGuid = d.taskGuid and d.segmentId = s.id';
        $createViewSql .= ' GROUP BY d.segmentId';

        $db = Zend_Db_Table::getDefaultAdapter();
        $this->_dropView($viewName, $db);
        $db->query($createViewSql, $this->taskGuid);
    }
    
    /**
     * drops the segment data view to the given taskguid
     * @param string $taskGuid
     */
    public function dropView($taskGuid) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $this->_dropView($this->getDataViewName($taskGuid), $db);
    }
    
    /**
     * reusable internal delete view function
     * @param string $viewname
     * @param Zend_Db_Adapter_Abstract $db
     */
    protected function _dropView($viewname, Zend_Db_Adapter_Abstract $db) {
        $db->query("DROP VIEW IF EXISTS " . $viewname);
    }
    
    /**
     * FIXME rework me along above updateview
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