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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Segment Entity Objekt
 * 
 * @method integer getAutoStateId() getAutoStateId()
 * @method void setAutoStateId() setAutoStateId(integer $id)
 * @method integer getWorkflowStepNr() getWorkflowStepNr()
 * @method void setWorkflowStepNr() setWorkflowStepNr(integer $stepNr)
 * @method string getWorkflowStep() getWorkflowStep()
 * @method void setWorkflowStep() setWorkflowStep(string $name)
 */
class editor_Models_Segment extends ZfExtended_Models_Entity_Abstract {

    protected $dbInstanceClass          = 'editor_Models_Db_Segments';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Segment';
    /**
     *
     * @var type Zend_Config
     */
    protected $config           = null;
    /**
     * @var null
     */
    protected $lengthToSort = null;
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager = null;
    /**
     * @var [editor_Models_Db_SegmentDataRow]
     */
    protected $segmentdata     = array();
    
    /**
     * init the internal segment field and the DB object
     */
    public function __construct()
    {
        $session = new Zend_Session_Namespace();
        $this->lengthToSort = $session->runtimeOptions->lengthToTruncateSegmentsToSort;
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        parent::__construct();
    }
    
    /**
     * updates the toSort attribute of the given attribute name (only if toSort exists!)
     * @param string $field
     */
    public function updateToSort($name) {
        $toSort = $name.'ToSort';
        if(!$this->hasField($toSort)) {
            return;
        }
        $v = $this->__call('get'.ucfirst($name), array());
        $this->__call('set'.ucfirst($toSort), array($this->truncateSegmentsToSort($v)));
    }
    
    /**
     * truncates the given segment content and strips tags for the toSort fields
     * truncation is needed for sorting in MSSQL
     * @param $segment
     * @return string
     */
    protected function truncateSegmentsToSort($segment) {
        if(!is_string($segment)){
            return $segment;
        }
        return mb_substr(strip_tags($segment),0,$this->lengthToSort,'utf-8');
    }
    
    /**
     * loads the segment data hunks for this segment as Row Objects in segmentdata
     * @param $segmentId
     */
    protected function initData($segmentId)
    {
        $this->segmentdata = array();
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        $s = $db->select()->where('segmentId = ?', $segmentId);
        $datas = $db->fetchAll($s);
        foreach($datas as $data) {
            $this->segmentdata[$data['name']] = $data;
        }
    }

    /**
     * sets segment attributes, filters the fluent fields and stores them separatly
     * @param string $name
     * @param mixed $value
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::set()
     */
    protected function set($name, $value) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc !== false) {
            if(empty($this->segmentdata[$loc['field']])) {
                $this->segmentdata[$loc['field']] = $this->createData($loc['field']);
            }
            return $this->segmentdata[$loc['field']]->__set($loc['column'], $value);
        }
        return parent::set($name, $value);
    }

    /**
     * gets segment attributes, filters the fluent fields and gets them from a different location
     * @param string $name
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::get()
     */
    protected function get($name) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc !== false) {
            //if we have a missing index here, that means, 
            //the data field ist not existing yet, since the field itself was defined by another file!
            //so returning an empty string is OK here. 
            if(empty($this->segmentdata[$loc['field']])) {
                return '';
            }
            return $this->segmentdata[$loc['field']]->__get($loc['column']);
        }
        return parent::get($name);
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
     * Loops over all data fields and checks if at least one of them was changed (compare by original and edited content)
     * @param string $typeFilter optional, checks only data fields of given type
     */
    public function isDataModified($typeFilter = null) {
        foreach ($this->segmentdata as $data) {
            if(!empty($typeFilter) && $data->type !== $typeFilter) {
                continue;
            }
            if($data->edited !== $data->original) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * loads the Entity by Primary Key Id
     * @param integer $id
     * @return Zend_Db_Table_Row
     */
    public function load($id) {
        $row = parent::load($id);
        $this->segmentFieldManager->initFields($this->getTaskGuid());
        $this->initData($id);
        return $row;
    }

    /**
     * erzeugt ein neues, ungespeichertes SegmentHistory Entity
     * @return editor_Models_SegmentHistory
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $history editor_Models_SegmentHistory */
        $history->setSegmentFieldManager($this->segmentFieldManager);

        $fields = array('taskGuid', 'userGuid', 'userName', 'timestamp', 'editable', 'pretrans', 'qmId', 'stateId', 'autoStateId', 'workflowStep', 'workflowStepNr');
        $fields = array_merge($fields, $this->segmentFieldManager->getEditableDataIndexList());
        $history->setSegmentId($this->getId());

        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }
        return $history;
    }

    public function setQmId($qmId) {
        return parent::setQmId(trim($qmId, ';'));
    }

    /**
     * gets the data from import, sets it into the data fields
     * check the given fields against the really available fields for this task.
     * @param editor_Models_SegmentFieldManager $sfm
     * @param array $segmentData key: fieldname; value: array with original and originalMd5
     */
    public function setFieldContents(editor_Models_SegmentFieldManager $sfm, array $segmentData) {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        foreach($segmentData as $name => $data) {
            $row = $db->createRow($data);
            /* @var $row editor_Models_Db_SegmentDataRow */
            $row->name = $name;
            $field = $sfm->getByName($name);
            $row->originalToSort = $this->truncateSegmentsToSort($row->original);
            $row->taskGuid = $this->getTaskGuid();
            $row->mid = $this->getMid();
            if($field->editable) {
                $row->edited = $row->original;
                $row->editedToSort = $row->originalToSort;
            }
            /* @var $row editor_Models_Db_SegmentDataRow */
            $this->segmentdata[] = $row;
        }
    }
    
    /**
     * adds one single field content ([original => TEXT, originalMd5 => HASH]) to a given segment, 
     * identified by MID and fileId. taskGuid MUST be given by setTaskGuid before!
     * 
     * @param Zend_Db_Table_Row_Abstract $field
     * @param integer $fileId
     * @param string $mid
     * @param array $data
     */
    public function addFieldContent(Zend_Db_Table_Row_Abstract $field, $fileId, $mid, array $data) {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */

        $segmentId = new Zend_Db_Expr('('.$this->db->select()
                            ->from($this->db->info($db::NAME), array('id'))
                            ->where('taskGuid = ?', $this->getTaskGuid())
                            ->where('fileId = ?', $fileId)
                            ->where('mid = ?', $mid).')');
        
        $data = array(
            'taskGuid' => $this->getTaskGuid(),
            'name' => $field->name,
            'segmentId' => $segmentId,
            'mid' => $mid,
            'original' => $data['original'],
            'originalMd5' => $data['originalMd5'],
            'originalToSort' => $this->truncateSegmentsToSort($data['original']),
        );
        if($field->editable) {
            $data['edited'] = $data['original'];
            $data['editedToSort'] = $this->truncateSegmentsToSort($data['original']);
        }
        
        $db->insert($data);
    }
    
    /**
     * method to add a data hunk later on 
     * (edit a alternate which was defined by another file, and is therefore empty in this segment)
     * @param string $field the field name
     * @return editor_Models_Db_SegmentDataRow
     */
    protected function createData($field) {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        $row = $db->createRow();
        /* @var $row editor_Models_Db_SegmentDataRow */
        $row->taskGuid = $this->get('taskGuid');
        $row->name = $field;
        $row->segmentId = $this->get('id');
        $row->mid = $this->get('mid');
        $row->original = '';
        $row->originalMd5 = 'd41d8cd98f00b204e9800998ecf8427e'; //empty string md5 hash
        $row->originalToSort = '';
        $row->edited = '';
        $row->editedToSort = '';
        $row->save();
        return $row;
    }
    
    /**
     * save the segment and the associated segmentd data hunks
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        $oldIdValue = $this->getId();
        $segmentId = parent::save();
        foreach($this->segmentdata as $data) {
            /* @var $data editor_Models_Db_SegmentDataRow */
            if(empty($data->segmentId)) {
                $data->segmentId = $segmentId;
            }
            $data->save();
        }
        //only update the mat view if the segment was already in DB (so do not save mat view on import!)
        if(!empty($oldIdValue)) {
            $this->segmentFieldManager->getView()->updateSegment($this);
        }
        return $segmentId;
    }
    
    /**
     * merges the segment data into the result set
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::getDataObject()
     */
    public function getDataObject() {
        $res = parent::getDataObject();
        $this->segmentFieldManager->mergeData($this->segmentdata, $res);
        return $res;
    }

    /**
     * returns the original content of a field 
     * @param string $field Fieldname
     */
    public function getFieldOriginal($field) {
        //since fields can be merged from different files, data for a field can be empty
        if(empty($this->segmentdata[$field])) {
            return '';
        }
        return $this->segmentdata[$field]->original;
    }

    /**
     * returns the edited content of a field 
     * @param string $field Fieldname
     */
    public function getFieldEdited($field) {
        //since fields can be merged from different files, data for a field can be empty
        if(empty($this->segmentdata[$field])) {
            return '';
        }
        return $this->segmentdata[$field]->edited;
    }

    /**
     * returns a list with editable dataindex
     * @return array
     */
    public function getEditableDataIndexList() {
        return $this->segmentFieldManager->getEditableDataIndexList();
    }
    
    /**
     * Load segments by taskGuid.
     * @param string $taskGuid
     * @param boolean $loadSourceEdited
     * @return array
     */
    public function loadByTaskGuid($taskGuid) {
        try {
            return $this->_loadByTaskGuid($taskGuid);
        }
        catch(Zend_Db_Statement_Exception $e) {
            $m = $e->getMessage();
            if(strpos($m,'SQLSTATE') !== 0 || strpos($m,'Base table or view not found') === false) {
                throw $e;
            }
        }
        //fallback mechanism for not existing views. If not exists, we are trying to create it.
        $this->segmentFieldManager->initFields($taskGuid);
        $this->segmentFieldManager->getView()->create();
        return $this->_loadByTaskGuid($taskGuid);
    }
    
    /**
     * encapsulate the load by taskGuid code.
     * @param string $taskGuid
     * @return array
     */
    protected function _loadByTaskGuid($taskGuid) {
        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);
        
        $this->initDefaultSort();

        $s = $this->db->select(false);
        $db = $this->db;
        $cols = $this->db->info($db::COLS);

        /**
         * FIXME reminder for TRANSLATE-113: Filtering out unused cols is needed for TaskManagement Feature (user dependent cols)
         * This is a example for field filtering. 
         if (!$loadSourceEdited) {
            $cols = array_filter($cols, function($val) {
                        return strpos($val, 'sourceEdited') === false;
                    });
        }
         */
        $s->from($this->db, $cols);
        $s->where('taskGuid = ?', $taskGuid);

        return parent::loadFilterdCustom($s);
    }
    

    /**
     * @param $taskguid
     * @return int
     */
    public function getTotalCountByTaskGuid($taskguid) {
        $s = $this->db->select();
        $s->where('taskGuid = ?', $taskguid);
        return parent::computeTotalCount($s);
    }

    /**
     * Loads segments by a specific workflowStep, fetch only specific fields.
     * @param string $taskGuid
     * @param string $workflowStep
     */
    public function loadByWorkflowStep(string $taskGuid, string $workflowStep) {
        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);
        
        $fields = array('id', 'mid', 'segmentNrInTask', 'stateId', 'autoStateId', 'matchRate', 'qmId', 'comments', 'fileId');
        $fields = array_merge($fields, $this->segmentFieldManager->getDataIndexList());
        
        $this->initDefaultSort();
        $s = $this->db->select(false);
        $db = $this->db;
        $s->from($this->db, $fields);
        $s->where('taskGuid = ?', $taskGuid)->where('workflowStep = ?', $workflowStep);
        return parent::loadFilterdCustom($s);
    }

    /**
     * Gibt zurück ob das Segment editiertbar ist
     * @return boolean
     */
    public function isEditable() {
        $flag = $this->getEditable();
        return !empty($flag);
    }

    /**
     * returns a list with the mapping of fileIds to the segment Row Index. The Row Index is generated considering the given Filters
     * @param string $taskGuid
     * @return array
     */
    public function getFileMap($taskGuid) {
        $this->loadByTaskGuid($taskGuid);
        $s = $this->db->select()
                ->from($this->db, 'fileId')
                ->where('taskGuid = ?', $taskGuid);
        if (!empty($this->filter)) {
            $this->filter->applyToSelect($s);
        }

        $rowindex = 0;
        $result = array();
        $dbResult = $this->db->fetchAll($s)->toArray();
        foreach ($dbResult as $row) {
            if (!isset($result[$row['fileId']])) {
                $result[$row['fileId']] = $rowindex;
            }
            $rowindex++;
        }
        return $result;
    }

    protected function initDefaultSort() {
        if (!empty($this->filter) && !$this->filter->hasSort()) {
            $this->filter->addSort('fileOrder');
            $this->filter->addSort('id');
        }
    }

    /**
     * Syncs the Files fileorder to the Segments Table, for faster sorted reading from segment table
     * @param string $taskguid
     */
    public function syncFileOrderFromFiles(string $taskguid) {
        $infokey = Zend_Db_Table_Abstract::NAME;
        $segmentsTableName = $this->db->info($infokey);
        $filesTableName = ZfExtended_Factory::get('editor_Models_Db_Files')->info($infokey);
        $sql = $this->_syncFilesortSql($segmentsTableName, $filesTableName);
        $this->db->getAdapter()->query($sql, array($taskguid));
        //do the resort also for the view!
        $this->segmentFieldManager->initFields($taskguid);
        $segmentsViewName = $this->segmentFieldManager->getView()->getName();
        $sql = $this->_syncFilesortSql($segmentsViewName, $filesTableName);
        $this->db->getAdapter()->query($sql, array($taskguid));
    }

    /**
     * internal function, returns specific sql. To be overridden if needed.
     * @param string $segmentsTable
     * @param string $filesTable
     * @return string
     */
    protected function _syncFilesortSql(string $segmentsTable, string $filesTable) {
        return 'update ' . $segmentsTable . ' s, ' . $filesTable . ' f set s.fileOrder = f.fileOrder where s.fileId = f.id and f.taskGuid = ?';
    }

    /**
     * fetch the alikes of the actually loaded segment
     * 
     * cannot handle alternate targets! can only handle source and target field! actually not refactored!
     * 
     * @return array
     */
    public function getAlikes($taskGuid) {
        $this->segmentFieldManager->initFields($taskGuid);
        //if we are using alternates we cant use change alikes, that means we return an empty list here
        if(!$this->segmentFieldManager->isDefaultLayout()) {
            return array(); 
        }
        $segmentsViewName = $this->segmentFieldManager->getView()->getName();
        $sql = $this->_getAlikesSql($segmentsViewName);
        //since alikes are only usable with segment field default layout we can use the following hardcoded methods
        $stmt = $this->db->getAdapter()->query($sql, array(
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $taskGuid));
        $alikes = $stmt->fetchAll();
        //gefilterte Segmente bestimmen und flag setzen
        $hasIdFiltered = $this->getIdsAfterFilter($segmentsViewName, $taskGuid);
        foreach ($alikes as $key => $alike) {
            $alikes[$key]['infilter'] = isset($hasIdFiltered[$alike['id']]);
            //das aktuelle eigene Segment, zu dem die Alikes gesucht wurden, aus der Liste entfernen
            if ($alike['id'] == $this->get('id')) {
                unset($alikes[$key]);
            }
        }
        return array_values($alikes); //neues numerisches Array für JSON Rückgabe, durch das unset oben macht json_decode ein Object draus
    }
    
    /**
     * reset the internal used db object to the view to the given taskGuid
     * @param string $taskGuid
     */
    protected function reInitDb($taskGuid) {
        $mv = $this->segmentFieldManager->getView();
        /* @var $mv editor_Models_Segment_MaterializedView */
        $this->db = ZfExtended_Factory::get($this->dbInstanceClass, array(array(), $mv->getName()));
    }

    /**
     * overwrite for segment field integration
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::validatorLazyInstatiation()
     */
    protected function validatorLazyInstatiation() {
        $taskGuid = $this->getTaskGuid();
        if(empty($taskGuid)) {
            throw new Zend_Exception("For using the editor_Models_Validator_Segment Validator a taskGuid must be set in the segment!");
        }
        $this->segmentFieldManager->initFields($taskGuid);
        if(empty($this->validator)) {
            $this->validator = ZfExtended_Factory::get($this->validatorInstanceClass, array($this->segmentFieldManager));
        }
    }
    
    /**
     * For ChangeAlikes: Gibt ein assoziatives Array mit den Segment IDs zurück, die nach Anwendung des Filters noch da sind.
     * ArrayKeys: SegmentId, ArrayValue immer true
     * @param string $segmentsTableName
     * @param string $taskGuid
     * @return array
     */
    protected function getIdsAfterFilter(string $segmentsTableName, string $taskGuid) {
        $this->reInitDb($taskGuid);
        $s = $this->db->select()
                ->from($segmentsTableName, array('id'))
                ->where('taskGuid = ?', $taskGuid)
                //Achtung: die Klammerung von (source = ? or target = ?) beachten!
                ->where('(sourceMd5 ' . $this->_getSqlTextCompareOp() . ' ?', (string) $this->getSourceMd5())
                ->orWhere('targetMd5 ' . $this->_getSqlTextCompareOp() . ' ?)', (string) $this->getTargetMd5());
        $filteredIds = parent::loadFilterdCustom($s);
        $hasIdFiltered = array();
        foreach ($filteredIds as $ids) {
            $hasIdFiltered[$ids['id']] = true;
        }
        return $hasIdFiltered;
    }

    /**
     * Gibt das SQL (mysql) für die Abfrage der Alikes eines Segmentes zurück.
     * Muss für MSSQL überschrieben werden!
     *
     * Für MSSQL (getestet mit konkreten Werten und ohne die letzte Zeile in MSSQL direkt):
     * select id, source, target,
     * case when sourceMd5 like '?' then 1 else 0 end as sourceMatch,
     * case when targetMd5 like '?' then 1 else 0 end as targetMatch
     * from LEK_segments where (sourceMd5 like '?' or targetMd5 like '?')
     * and taskGuid = ? and editable = 1 order by fileOrder, id;
     *
     * @param string $viewName
     * @return string
     */
    protected function _getAlikesSql(string $viewName) {
        return 'select id, segmentNrInTask, source, target, sourceMd5=? sourceMatch, targetMd5=? targetMatch
    from '.$viewName.' 
    where (sourceMd5 = ? 
        or (targetMd5 = ? and target != \'\' and target IS NOT NULL)) 
        and taskGuid = ? and editable = 1
    order by fileOrder, id';
    }

    /**
     * Muss für MSSQL überschrieben werden und like anstatt = zurückgeben
     * @return string
     */
    protected function _getSqlTextCompareOp() {
        return ' = ';
        //return ' like ' bei MSSQL
    }

    /**
     * recreates the term markup in the data field with the given dataindex
     * @param string $dataindex dataindex of the segment field to be processed
     * @param boolean $useSource optional, default false, if true terms of source column are used (instead of target)
     */
    public function recreateTermTags($dataindex, $useSource = false) {
        $termTag = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        /* @var $termTag editor_Models_Segment_TermTag */
        $withTerms = $termTag->recreate($this->getId(), $this->get($dataindex), $useSource);
        $this->set($dataindex, $withTerms);
    }

    /**
     * Updates - if enabled - the QM Sub Segments with correct IDs in the given String and stores it with the given Method in the entity
     * @param string $field
     */
    public function updateQmSubSegments(string $dataindex) {
        $field = $this->segmentFieldManager->getDataLocationByKey($dataindex);
        $config = Zend_Registry::get('config');
        if(! $config->runtimeOptions->editor->enableQmSubSegments) {
            return;
        }
        $qmsubsegments = ZfExtended_Factory::get('editor_Models_Qmsubsegments');
        /* @var $qmsubsegments editor_Models_Qmsubsegments */
        $withQm = $qmsubsegments->updateQmSubSegments($this->get($dataindex), (int)$this->getId(), $field['field']);
        $this->set($dataindex, $withQm);
    }

    /**
     * Bulk updating a specific autoState of a task 
     * @param string $taskGuid
     * @param integer $oldState
     * @param integer $newState
     * @param boolean $emptyEditedOnly
     */
    public function updateAutoState(string $taskGuid, integer $oldState, integer $newState, $emptyEditedOnly = false) {
        $sfm = $this->segmentFieldManager;
        $sfm->initFields($taskGuid);
        $db = $this->db;
        $sql = 'UPDATE `%s` d, `%s` v set d.autoStateId = ? where d.id = v.id and v.autoStateId = ? and v.taskGuid = ?';
        $sql = sprintf($sql, $db->info($db::NAME), $sfm->getView()->getName());
        $bind = array($newState, $oldState, $taskGuid);
        
        if(!$emptyEditedOnly) {
            $this->db->getAdapter()->query($sql, $bind);
            return;
        }
        $sfm->initFields($taskGuid);
        $fields = $sfm->getFieldList();
        foreach($fields as $field) {
            if($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                $sql .= ' and '.$sfm->getEditIndex($field->name).' = ?';
                $bind[] = '';
            }
        }
        $this->db->getAdapter()->query($sql, $bind);
    }

    /**
     * includes the fluent segment data
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::getModifiedData()
     */
    public function getModifiedData() {
        $result = parent::getModifiedData(); //assoc mit key = dataindex und value = modValue
        $modKeys = array_keys($result);
        $modFields = array_unique(array_diff($this->modified, $modKeys));
        foreach($modFields as $field) {
            if($this->segmentFieldManager->getDataLocationByKey($field) !== false) {
                $result[$field] = $this->get($field);
            }
        }
        return $result;
    }
}
