<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * SegmentHistory Entity Model
 */
class editor_Models_SegmentHistory extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_SegmentsHistory';

    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager = null;
    
    /**
     * @var array
     */
    protected $historydata     = array();
    
    
    protected $fieldsToUpdate=array(
            'taskGuid',
            'userGuid',
            'userName',
            'timestamp',
            'editable',
            'pretrans',
            'qmId',
            'stateId',
            'autoStateId',
            'workflowStep',
            'workflowStepNr',
            'matchRate',
            'matchRateType'
    );
    
    /**
     * loads the history entries to one segment, DESC sorted by id (creation), can be limited with $limit parameter
     * @param int $id
     * @param number $limit
     * @return array
     */
    public function loadBySegmentId($id, $limit = 0) {
        $s = $this->db->select();
        $s->where('segmentId = ?', $id)
            ->order('id DESC');
        if($limit > 0) {
            $s->limit($limit);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * sets the field manager
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function setSegmentFieldManager(editor_Models_SegmentFieldManager $sfm) {
        $this->segmentFieldManager = $sfm;
    }
    
    /**
     * loads the segment data hunks for this segment history entry
     * @param $segmentHistoryId
     */
    protected function initData($segmentHistoryId) {
        $this->historydata = array();
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentsHistoryData');
        /* @var $db editor_Models_Db_SegmentsHistoryData */
        $s = $db->select()->where('segmentHistoryId = ?', $segmentHistoryId);
        $datas = $db->fetchAll($s);
        foreach($datas as $data) {
            $this->historydata[$data['name']] = $data;
        }
    }
    
    /**
     * filters the fluent fields and stores them separatly
     * @param string $name
     * @param mixed $value
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::set()
     */
    protected function set($name, $value) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc === false) {
            return parent::set($name, $value);
        }
        $name = $loc['column'];
        if(empty($this->historydata[$loc['field']])) {
            $db = ZfExtended_Factory::get('editor_Models_Db_SegmentsHistoryData');
            /* @var $db editor_Models_Db_SegmentsHistoryData */
            $this->historydata[$loc['field']] = $db->createRow(array(
                            'name' => $loc['field'],
                            'segmentHistoryId' => $this->getId(),
                            'segmentId' => $this->getSegmentId(),
                            'taskGuid' => $this->getTaskGuid()
                            ));
        }
        return $this->historydata[$loc['field']]->__set($name, $value);
    }

    /**
     * filters the fluent fields and gets them from a separate store
     * @param string $name
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::get()
     */
    protected function get($name) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc === false) {
            return parent::get($name);
        }
        if(empty($this->historydata[$loc['field']])) {
            return null;
        }
        return $this->historydata[$loc['field']]->__get('edited');
    }
    
    /**
     * integrates the segment fields into the hasfield check
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::hasField()
     */
    public function hasField($field) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($field);
        return $loc !== false || parent::hasField($field);
    }
    
    /**
     * save the segment and the associated segmentd data hunks
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        $segmentHistoryId = parent::save();
        foreach($this->historydata as $data) {
            /* @var $data editor_Models_Db_SegmentDataRow */
            if(empty($data->segmentHistoryId)) {
                $data->segmentHistoryId = $segmentHistoryId;
            }
            $data->save();
        }
    }
    
    /**
     * since the duration field is stored in the HistoryData Object but is not
     * used transparently like the other alternate fields, we have to store it separtly
     * (duration is not needed in daily business in the segment grid, so does not exist in the MV!)
     * @param array $durations keys → fieldnames; values → durations
     */
    public function setTimeTrackData(array $durations) {
        $sfm = $this->segmentFieldManager;
        foreach($durations as $field => $duration) {
            if(isset($this->historydata[$field])) {
                $this->historydata[$field]->duration = $duration;
            }
        }
    }
    
    public function getFieldsToUpdate(){
        return $this->fieldsToUpdate;
    }
    
    /***
     * Insert record(s) in segment history table for autostates and taskguid as condition
     * Attention: no record in segment data history is inserted, because usage is where no related data was changed for the data table
     * @param string $taskGuid
     * @param array $autoStates
     */
    public function createHistoryByAutoState($taskGuid,array $autoStates){
        //get the updatable fields for LEK_segment_history table
        $fieldsHistory = implode('`,`',$this->getFieldsToUpdate());
        $fieldsSegments = implode('`,seg.`',$this->getFieldsToUpdate());
        
        $sql = 'INSERT INTO LEK_segment_history
                   (segmentId, `'.$fieldsHistory.'`)
              SELECT seg.id, seg.`'.$fieldsSegments.'`
              FROM LEK_segments as seg
              WHERE seg.taskGuid = ?
              AND '.$this->db->getAdapter()->quoteInto('seg.autoStateId IN(?)', $autoStates);
        $this->db->getAdapter()->query($sql, [$taskGuid]);
    }
}
