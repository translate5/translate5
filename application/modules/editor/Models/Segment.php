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
    protected $_lengthToTruncateSegmentsToSort = null;
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager = null;
    /**
     * @var [editor_Models_Db_SegmentDataRow]
     */
    protected $_segmentdata     = array();
    
    /**
     * init the internal segment field and the DB object
     */
    public function __construct()
    {
        $session = new Zend_Session_Namespace();
        $this->lengthToTruncateSegmentsToSort = $session->runtimeOptions->lengthToTruncateSegmentsToSort;
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        parent::__construct();
    }
    
    /**
     * @param $segment
     * @return string
     */
    protected function _truncateSegmentsToSort($segment)
    {
        //FIXME this should be done in the Controller!
        if(!is_string($segment)){
            return $segment;
        }
        return mb_substr($segment,0,$this->lengthToTruncateSegmentsToSort,'utf-8');
    }
    
    /**
     * loads the segment data hunks for this segment
     * @param $segmentId
     */
    protected function initData($segmentId)
    {
        $this->_segmentdata = array();
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        $s = $db->select()->where('segmentId = ?', $segmentId);
        $datas = $db->fetchAll($s);
        foreach($datas as $data) {
            $this->_segmentdata[$data['name']] = $data;
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
        if($loc !== false) {
            return $this->_segmentdata[$loc['field']]->__set($loc['column'], $value);
        }
        return parent::set($name, $value);
    }

    /**
     * filters the fluent fields and gets them from a separate store
     * @param string $name
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::get()
     */
    protected function get($name) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc !== false) {
            return $this->_segmentdata[$loc['field']]->__get($loc['column']);
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
        foreach ($this->_segmentdata as $data) {
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
     * @return FIXME what returns?
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

        $fields = array('userGuid', 'userName', 'timestamp', 'editable', 'pretrans', 'qmId', 'stateId', 'autoStateId', 'workflowStep', 'workflowStepNr');
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
     * @param array $segmentData
     */
    public function setFieldContents(editor_Models_SegmentFieldManager $sfm, array $segmentData) {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        foreach($segmentData as $name => $data) {
            $row = $db->createRow($data);
            /* @var $row editor_Models_Db_SegmentDataRow */
            $row->name = $name;
            $field = $sfm->getByName($name);
            $row->originalToSort = $this->_truncateSegmentsToSort($row->original);
            $row->taskGuid = $this->getTaskGuid();
            $row->mid = $this->getMid();
            if($field->editable) {
                $row->edited = $row->original;
                $row->editedMd5 = $row->originalMd5;
                $row->editedToSort = $row->originalToSort;
            }
            /* @var $row editor_Models_Db_SegmentDataRow */
            $this->_segmentdata[] = $row;
        }
    }
    
    /**
     * save the segment and the associated segmentd data hunks
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        $segmentId = parent::save();
        foreach($this->_segmentdata as $data) {
            /* @var $data editor_Models_Db_SegmentDataRow */
            if(empty($data->segmentId)) {
                $data->segmentId = $segmentId;
            }
            $data->save();
        }
    }
    
    /**
     * merges the segment data into the result set
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::getDataObject()
     */
    public function getDataObject() {
        $res = parent::getDataObject();
        $this->segmentFieldManager->mergeData($this->_segmentdata, $res);
        return $res;
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
        $this->segmentFieldManager->updateView();
        return $this->_loadByTaskGuid($taskGuid);
    }
    
    /**
     * encapsulate the load by taskGuid code.
     * @param string $taskGuid
     * @return array
     */
    protected function _loadByTaskGuid($taskGuid) {
        $this->db = ZfExtended_Factory::get($this->dbInstanceClass, array(array(), $this->segmentFieldManager->getDataViewName($taskGuid)));
        
        $this->initDefaultSort();

        $s = $this->db->select(false);
        $db = $this->db;
        $cols = $this->db->info($db::COLS);

        /**
         * FIXME should we implement this filter in SegmentFieldManager?
         * Filtering out unused cols is needed for TaskManagement Feature (user dependent cols) 
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
     * @param string $taskguid
     * @param string $workflowStep
     */
    public function loadByWorkflowStep(string $taskguid, string $workflowStep) {
        $fields = array('id', 'mid', 'segmentNrInTask', 'source', 'sourceEdited', 'relais', 'target', 'edited', 'stateId', 'autoStateId', 'matchRate', 'qmId', 'comments');
        $this->initDefaultSort();
        $s = $this->db->select(false);
        $db = $this->db;
        $s->from($this->db, $fields);
        $s->where('taskGuid = ?', $taskguid)->where('workflowStep = ?', $workflowStep);
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
        $this->initDefaultSort();
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
     * holt die Wiederholungen des aktuell geladenene Segments
     * @return array
     */
    public function getAlikes($taskGuid) {
        $segmentsTableName = $this->db->info(Zend_Db_Table_Abstract::NAME);
        $sql = $this->_getAlikesSql();
        $stmt = $this->db->getAdapter()->query($sql, array(
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $taskGuid));
        $alikes = $stmt->fetchAll();
        //gefilterte Segmente bestimmen und flag setzen
        $hasIdFiltered = $this->getIdsAfterFilter($segmentsTableName, $taskGuid);
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
     * Gibt ein assoziatives Array mit den Segment IDs zurück, die nach Anwendung des Filters noch da sind.
     * ArrayKeys: SegmentId, ArrayValue immer true
     * @param string $segmentsTableName
     * @param string $taskGuid
     * @return array
     */
    protected function getIdsAfterFilter(string $segmentsTableName, string $taskGuid) {
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
      select id, source, target,
      case when sourceMd5 like '?' then 1 else 0 end as sourceMatch,
      case when targetMd5 like '?' then 1 else 0 end as targetMatch
      from LEK_segments where (sourceMd5 like '?' or targetMd5 like '?')
      and taskGuid = ? and editable = 1 order by fileOrder, id;
     *
     * @return string
     */
    protected function _getAlikesSql() {
        return 'select id, segmentNrInTask, source, target, sourceMd5=? sourceMatch, targetMd5=? targetMatch
    from LEK_segments 
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
     */
    public function recreateTermTags($dataindex) {
//FIXME remove the isSource thing! replace it with the alternates!
        return;
        $segmentContent = $this->get($dataindex);
        $this->config = Zend_Registry::get('config');
        //gibt alle Terme und zugehörige MetaDaten zu den im Segment verwendeten Terminstanzen zurück
        //sortiert nach String Länge, und bearbeitet die längsten Strings zuerst. 
        $sql = 'select i.term, i.projectTerminstanceId, t.mid, t.status, s2t.transFound, t.definition
          from LEK_terminstances i, LEK_terms t, LEK_segments2terms s2t
          where i.segmentId = ? and i.segmentId = s2t.segmentId and t.id = i.termId
              and s2t.termId = i.termId and s2t.used and s2t.isSource = ? order by length(i.term) desc';
        $isSource = $useSource ? 1 : 0;
        $stmt = $this->db->getAdapter()->query($sql, array($this->getId(), $isSource));
        $terms = $stmt->fetchAll();

        $this->termNr = 0; //laufende Nummer der Term Tags
        $this->replacements = array();

        foreach ($terms as $term) {
            $termData = new editor_Models_TermTagData();
            $termData->isSource = $useSource;
            foreach ($term as $key => $value) {
                $termData->$key = $value;
            }
            $searchLength = mb_strlen($term['term']);
            $segmentContent = $this->findTerminstancesTagAware($termData, $segmentContent, $searchLength);
        }
        //Im zweiten Schritt die Platzhalter durch die Terminstanzen ersetzen.
        //Der Umweg über die uniquen Platzhalter ist nötig, da ansonsten gleichlautende Terminstanzen mehrfach mit sich selbst ersetzt und damit mehrere divs hinzugefügt werden
        $this->set($dataindex, str_replace(array_keys($this->replacements), $this->replacements, $segmentContent));
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
     * ensures, that a term does not match content inside internal tags
     * 
     * @param editor_Models_TermTagData $termData 
     * @param string $segment
     * @param integer $searchLength
     * @return string $segment
     */
    protected function findTerminstancesTagAware($termData, $segment, $searchLength) {
        //if there is an internal tag in the term, the term will not match strings inside internal tags
        if (preg_match($this->config->runtimeOptions->editor->segment->recreateTermTags->regexInternalTags, $termData->term)) {
            return $this->findTerminstances($termData, $segment, $searchLength);
        }
        //otherwhise we ensure, that internal tags will not be matched by termRecreation
        $segmentArr = preg_split($this->config->runtimeOptions->editor->segment->recreateTermTags->regexInternalTags, $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($segmentArr);
        for ($i = 0; $i < $count; $i = $i + 2) {//the uneven numbers are the tags, we skip them
            $segmentArr[$i] = $this->findTerminstances($termData, $segmentArr[$i], $searchLength);
        }
        return implode('', $segmentArr);
    }

    /**
     * Durchsucht das Segment nach dem Term und ordnet die Fundstellen nach einer Priorisierung ob diese Fundstelle ein eigenständiges Wort ist, oder aber durch andere Zeichen abgegrenzt ist. 
     * Handelt es sich um eine Teilzeichenkette anstatt einem eigenständige Wort, wird die Fundstelle ignoriert.
     * 
     * @param editor_Models_TermTagData $termData 
     * @param string $segment
     * @param integer $searchLength
     * @param integer $offset
     * @return string $segment
     */
    protected function findTerminstances($termData, $segment, $searchLength, $offset = 0) {
        $term = $termData->term;
        $pos = mb_strpos($segment, $term, $offset);
        if ($pos === false) {
            $pos = mb_stripos($segment, $term, $offset);
        }
        $offset = $pos + $searchLength;
        if ($searchLength > 1) {
            $offset--;
        }
        $segLength = mb_strlen($segment);
        if ($pos === false || $offset >= $segLength) {
            return $segment;
        }
        //holt Term für die Ersetzung und Rückgabe
        $term2Mark = mb_substr($segment, $pos, $searchLength);

        //holt die Zeichen vor und nach dem gesuchten Term
        if ($pos === 0) {
            $leftChar = '';
        } else {
            $leftChar = trim(mb_substr($segment, $pos - 1, 1));
        }
        $rightChar = trim(mb_substr($segment, $pos + $searchLength, 1));

        /**
         * Im folgenden werden die linken und rechten Zeichen nicht per RegEx oder gegen Zeichenliste verglichen,
         * sondern mit trim eine Zeichenliste angewendet und dann das Resultat analysiert (im Regelfall Ergebnis == Leerstring.
         * Das erscheint mir pragmatischer und schneller als per RegEx o.ä.
         * Im folgenden werden die linken und rechten Zeichen in drei Prios eingeteilt 0,1,2 in dieser Reihenfolge werden Sie dann später auch ersetzt.
         * Das heißt wenn der Term "foo" ist, dann wird im String "bar foo<lala> bar foo dada" das zweite "foo" zuerst ersetzt, 
         * da ein whitespace als Wortgrenze höher als ein Tag angesehen wird.    
         */
        if ($rightChar === '' && $leftChar === '') {
            $placeholder = '#~<~#' . $this->termNr++ . '#~>~#';
            $segment = mb_substr($segment, 0, $pos) . $placeholder . mb_substr($segment, $pos + $searchLength);
            $offset = $pos + strlen($placeholder) - 1;
            if ($offset < $segLength) {
                $segment = $this->findTerminstances($termData, $segment, $searchLength, $offset);
            }
            $termData->term = $term2Mark;
            $this->replacements[$placeholder] = $this->getGeneratedTermTag($termData);
            return $segment;
        }

        $rightChar = trim($rightChar, '<');
        $leftChar = trim($leftChar, '>');

        if ($rightChar === '' && $leftChar === '') {
            $placeholder = '#~<~#' . $this->termNr++ . '#~>~#';
            $segment = mb_substr($segment, 0, $pos) . $placeholder . mb_substr($segment, $pos + $searchLength);
            $offset = $pos + strlen($placeholder) - 1;
            if ($offset < $segLength) {
                $segment = $this->findTerminstances($termData, $segment, $searchLength, $offset);
            }
            $termData->term = $term2Mark;
            $this->replacements[$placeholder] = $this->getGeneratedTermTag($termData);
            return $segment;
        }

        $wordspacers = '.,&;:?!„“\'"…·|「」『』»«›‹¡’‚';
        $rightChar = trim($rightChar, $wordspacers);
        $leftChar = trim($leftChar, $wordspacers);

        if ($rightChar === '' && $leftChar === '') {
            $placeholder = '#~<~#' . $this->termNr++ . '#~>~#';
            $segment = mb_substr($segment, 0, $pos) . $placeholder . mb_substr($segment, $pos + $searchLength);
            $offset = $pos + strlen($placeholder) - 1;
            if ($offset < $segLength) {
                $segment = $this->findTerminstances($termData, $segment, $searchLength, $offset);
            }
            $termData->term = $term2Mark;
            $this->replacements[$placeholder] = $this->getGeneratedTermTag($termData);
            return $segment;
        }
        //weiter suchen, aber die Fundstelle nicht erfassen.
        return $this->findTerminstances($termData, $segment, $searchLength, $offset);
    }

    /**
     * erstellt den term div tag anhand den gegebene Daten
     * @param editor_Models_TermTagData $termData
     * @param boolean $transFound
     */
    public function getGeneratedTermTag(editor_Models_TermTagData $termData) {
        $class = array('term', $termData->status);
        if ($termData->isSource) {
            $class[] = ($termData->transFound ? 'transFound' : 'transNotFound');
        }
        $class = join(' ', $class);
        $id = join('-', array($termData->mid, $termData->projectTerminstanceId));
        return sprintf('<div class="%1$s" id="%2$s" title="%4$s">%3$s</div>', $class, $id, $termData->term, htmlspecialchars($termData->definition));
    }

    /**
     * Bulk updating a specific autoState of a task 
     * @param string $taskGuid
     * @param integer $oldState
     * @param integer $newState
     * @param boolean $emptyEditedOnly
     */
    public function updateAutoState(string $taskGuid, integer $oldState, integer $newState, $emptyEditedOnly = false) {
        $where = array(
            'autoStateId = ?' => $oldState,
            'taskGuid = ?' => $taskGuid
        );
        if($emptyEditedOnly)
            $where['edited = ?'] = '';
        $this->db->update(array('autoStateId' => $newState), $where);
    }

}
