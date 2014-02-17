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
     * @var array
     */
    protected $_segmentfield    = array();
    /**
     * @var array
     */
    protected $_segmentdata     = array();
    /**
     *
     */
    public function __construct()
    {
        $session = new Zend_Session_Namespace();
        $this->lengthToTruncateSegmentsToSort = $session->runtimeOptions->lengthToTruncateSegmentsToSort;
    }
    /**
     * @param $segment
     * @return string
     */
    protected function _truncateSegmentsToSort($segment)
    {
        if(!is_string($segment)){
            return $segment;
        }
        return mb_substr($segment,0,$this->lengthToTruncateSegmentsToSort,'utf-8');
    }
    /**
     * @param $name
     * @param $value
     */
    public function setField($name, $value)
    {
        $this->_segmentdata[$name]['original'] = $value;
        $this->_segmentdata[$name]['originalMd5'] = md5($value);
        $this->_segmentdata[$name]['originalToSort'] = $this->_truncateSegmentsToSort($value);
    }
    /**
     * @param $name
     * @param $value
     */
    public function setFieldEdited($name, $value)
    {
        $this->_segmentdata[$name]['edited'] = $value;
        $this->_segmentdata[$name]['editedMd5'] = md5($value);
        $this->_segmentdata[$name]['editedToSort'] = $this->_truncateSegmentsToSort($value);
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getField($name)
    {
        return $this->_segmentdata[$name]['original'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldMd5($name)
    {
        return $this->_segmentdata[$name]['originalMd5'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldToSort($name)
    {
        return $this->_segmentdata[$name]['originalToSort'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldEdited($name)
    {
        return $this->_segmentdata[$name]['edited'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldEditedMd5($name)
    {
        return $this->_segmentdata[$name]['editedMd5'];
    }
    /**
     * @param $name
     * @return mixed
     */
    public function getFieldEditedToSort($name)
    {
        return $this->_segmentdata[$name]['editedToSort'];
    }
    /**
     * @param $TaskGuid
     */
    protected function initField($TaskGuid)
    {
        $segmentfield = new editor_Models_Segmentfield();
        $this->_segmentfield = $segmentfield->loadBytaskGuid($TaskGuid);
    }
    /**
     * @param $TaskGuid
     */
    protected function initData($TaskGuid)
    {
        $segmentdata = new editor_Models_Segmentdata();
        $this->_segmentdata = $segmentdata->loadBytaskGuid($TaskGuid);

    }
    /**
     * @param $TaskGuid
     */
    protected function initHistoryData($TaskGuid)
    {
        $segmentdata = new editor_Models_SegmentHistoryData();
        $this->_segmentdata = $segmentdata->loadByuserGuid($TaskGuid);
    }

    /**
     * erzeugt ein neues, ungespeichertes SegmentHistory Entity
     * @return editor_Models_SegmentHistory
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('editor_Models_SegmentHistory');

        $fields = array('sourceEdited', 'edited', 'userGuid', 'userName', 'timestamp', 'editable', 'pretrans', 'qmId', 'stateId', 'autoStateId', 'workflowStep', 'workflowStepNr');
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
     * create / update View
     * @param string $taskguid
     */
    protected function updateView($taskguid)
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
     * check the given fields against the really available fields for this task.
     */
    public function setFieldContents(editor_Models_Segmentfield $field)
    {
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
    /**
     * Load segments by taskguid. Second Parameter decides if SourceEdited column should be provided
     * @param string $taskguid
     * @param boolean $loadSourceEdited
     */
    public function loadByTaskGuid($taskguid, $loadSourceEdited = false) {
        $this->initDefaultSort();

//        $this->initData($taskguid);
//        exit;

        $s = $this->db->select(false);
        $db = $this->db;
        $cols = $this->db->info($db::COLS);

        //dont load sourceEdited* Cols if not needed
        if (!$loadSourceEdited) {
            $cols = array_filter($cols, function($val) {
                        return strpos($val, 'sourceEdited') === false;
                    });
        }
        $s->from($this->db, $cols);
        $s->where('taskGuid = ?', $taskguid);

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
     * recreates term tags in target / edited fields
     */
    public function recreateTermTagsTarget() {
        $this->setEdited($this->recreateTermTags($this->getEdited()));
    }

    /**
     * recreates term tags in source / sourceEdited fields
     */
    public function recreateTermTagsSource() {
        $this->setSourceEdited($this->recreateTermTags($this->getSourceEdited(), true));
    }

    /**
     * recreates the term markup in the given segment content
     * @param string $segmentContent textuall segment content
     * @param boolean $useSource optional, default false, if true terms of source column are used (instead of target)
     * @return string segment with recreated terms
     */
    protected function recreateTermTags($segmentContent, $useSource = false) {
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
        return str_replace(array_keys($this->replacements), $this->replacements, $segmentContent);
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
