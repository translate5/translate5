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
 * Segment TermTag Recreator
 */
class editor_Models_Segment_MaterializedView {
    const VIEW_PREFIX = 'LEK_segment_view_';
    
    /**
     * @var string
     */
    protected $taskGuid;
    
    /**
     * @var string
     */
    protected $viewName;
    
    /**
     */
    public function __construct($taskGuid = null) {
        $this->config = Zend_Registry::get('config');
        if(!empty($taskGuid)) {
            $this->setTaskGuid($taskGuid);
        }
    }
    
    /**
     * sets the taskguid to be used internally
     * @param string $taskGuid
     */
    public function setTaskGuid($taskGuid) {
        $this->taskGuid = $taskGuid;
        $this->viewName = $this->makeViewName($taskGuid);
    }
    
    /**
     * generates the view name out of the taskGuid
     * @param string $taskGuid
     */
    protected function makeViewName($taskGuid) {
        return self::VIEW_PREFIX.md5($taskGuid);
    }
    
    /**
     * returns the name of the data view
     * @param string $taskGuid
     * @return string
     */
    public function getName() {
        $this->checkTaskGuid();
        return $this->viewName;
    }
    
    /**
     * creates a temporary table used as materialized view
     */
    public function create() {
        $this->checkTaskGuid();
        //$start = microtime(true);
        if($this->createMutexed()) {
            $this->getTask()->logger('editor.task.mv')->info('E1348', 'The tasks materialized view {matView} was created.', ['matView' => $this->viewName]);
            $this->addFields();
            $this->fillWithData();
            return;
        }
        //the following check call is to avoid using a not completly filled MV in a second request accessing this task
        $this->checkMvFillState();
    }

    /**
     * ensure that a taskGuid is set
     * @throws LogicException
     */
    protected function checkTaskGuid() {
        if(empty($this->taskGuid)) {
            throw new LogicException('You have to provide a taskGuid!');
        }
    }
    
    /**
     * created the MV table mutexed, if it already exists return false, if created return true.
     * @return boolean true if table was created, false if it already exists
     */
    protected function createMutexed() {
        $createSql = 'CREATE TABLE `'.$this->viewName.'` LIKE `LEK_segments`; ALTER TABLE `'.$this->viewName.'` ENGINE=MyISAM;';
        $createSql .= 'ALTER TABLE `'.$this->viewName.'` ADD KEY (`segmentNrInTask`);';
        $db = Zend_Db_Table::getDefaultAdapter();
        try {
            $db->query($createSql);
            return true;
        }
        catch(Zend_Db_Statement_Exception $e) {
            $m = $e->getMessage();
            //the second string check must be case insensitive for windows usage
            if(strpos($m,'SQLSTATE') !== 0 || stripos($m,'Base table or view already exists: 1050 Table \''.$this->viewName.'\' already exists') === false) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Adds the fluent field names to the materialized view
     */
    protected function addFields() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $data = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $data editor_Models_Db_SegmentData */
        $md = $data->info($data::METADATA);
        
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($this->taskGuid);
        $baseCols = $sfm->getBaseColumns();
        
        
        //define the add column states based on the field type stored in the DB
        $addColTpl = array();
        foreach($baseCols as $v) {
            if(empty($md[$v])) {
                throw new Zend_Exception('Missing Column '.$v.' in LEK_segment_data on creating the materialized view!');
            }
            $sql = 'ADD COLUMN `%s%s` '.strtoupper($md[$v]['DATA_TYPE']);
            if(!empty($md[$v]['LENGTH'])) {
                $sql .= '('.$md[$v]['LENGTH'].')';
            }
            if(empty($md[$v]['NULLABLE'])) {
                $sql .= ' NOT NULL';
            }
            
            //searching in our text fields should be searched binary, see TRANSLATE-646 
            if($v == 'original' || $v == 'edited') {
                $sql .= ' COLLATE utf8mb4_bin';
            }
            
            $addColTpl[$v] = $sql;
        }
        
        //loop over all available segment fields for this task and create the SQL for
        $walker = function($name, $suffix, $realCol) use ($addColTpl) {
            return sprintf($addColTpl[$realCol],$name, $suffix);
        };
        
        $addColSql = $sfm->walkFields($walker);
        $addColSql[] = 'ADD COLUMN `metaCache` longtext NOT NULL';
        
        $sql = 'ALTER TABLE `'.$this->viewName.'` '.join(', ', $addColSql).';';
        $db->query($sql);
    }
    
    /**
     * checks if the MV is already filled up, if not, wait a maximum of 28 seconds.
     * @throws Zend_Exception
     */
    protected function checkMvFillState() {
        $fillQuery = 'select mv.cnt mvCnt, tab.cnt tabCnt from (select count(*) cnt from LEK_segments where taskGuid = ?) mv, ';
        $fillQuery .= '(select count(*) cnt from '.$this->viewName.' where taskGuid = ?) tab;';
        $db = Zend_Db_Table::getDefaultAdapter();
        //we assume a maximum of 28 seconds to wait on the MV
        for($i=1;$i<8;$i++) {
            //if the MV was already created, wait until it is already completly filled 
            $res = $db->fetchRow($fillQuery, array($this->taskGuid,$this->taskGuid));
            if($res && $res['mvCnt'] == $res['tabCnt']) {
                return;
            }
            sleep($i);
        }
        //here throw exception
        throw new Zend_Exception('TimeOut on waiting for the following materialized view to be filled (Task '.$this->taskGuid.'): '.$this->viewName);
    }
    
    /**
     * prefills the materialized view
     */
    protected function fillWithData() {
        $this->metaCacheCreateTempTable();
        $selectSql = array('INSERT INTO '.$this->viewName.' SELECT s.*');

        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($this->taskGuid);
        $walker = function($name, $suffix, $realCol) use (&$selectSql) {
            $selectSql[] = sprintf('MAX(IF(d.name = \'%s\', d.%s, NULL)) AS %s%s', $name, $realCol, $name, $suffix);
        };
        //loop over all available segment fields for this task and create SQL for
        $sfm->walkFields($walker);
        $selectSql = join(',', $selectSql).', ';
        
        //build up the segment meta cache query
        $selectSql .= $this->buildMetaCacheSql();
        
        $db = Zend_Db_Table::getDefaultAdapter();
//error_log($selectSql);
//error_log(print_r([$this->taskGuid, $this->taskGuid],1));
        $db->query($selectSql, [$this->taskGuid]);
    }
    
    /**
     * Updates the Materialized View Data Object with the saved data.
     * @param editor_Models_Segment $segment
     */
    public function updateSegment(editor_Models_Segment $segment) {
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments', array(array(), $this->viewName));
        /* @var $db editor_Models_Db_Segments */
        $data = $segment->getDataObject();
        $id = $data->id;
        unset($data->id);
        unset($data->isWatched);
        unset($data->segmentUserAssocId);
        $db->update((array) $data, array('id = ?' => $id));
    }
    
    /**
     * Updates the view metaCache for the given segment and its siblings in the same transunit 
     * @param editor_Models_Segment $segment
     */
    public function updateSiblingMetaCache(editor_Models_Segment $segment) {
        $groupId = $segment->meta()->getTransunitId();
        //using two selects to force the optimizer to run first the very inner SELECT and after that make a join with the outer view. 
        // without that, it can happen, that MySQL first runs over each view entry, and over each segments content, which is then very long 
        $sql = 'update '.$this->viewName.' view, (SELECT * FROM (SELECT m.segmentId,';
        $sql .= $this->buildMetaCacheSql($segment->getId());
        $sql .= ') innerData ) data';
        $sql .= ' SET view.metaCache = data.metaCache';
        $sql .= ' WHERE view.id = data.segmentId';
        $db = Zend_Db_Table::getDefaultAdapter();
//error_log($sql);
//error_log(print_r([$this->taskGuid, $this->taskGuid, $groupId],1));
        $db->query($sql, [$this->taskGuid, $this->taskGuid, $groupId]);
    }
    
    /**
     * creates a reusable SQL fragment for updating the mat view metaCache field for a whole task or a given groupId/transunitId (including fileId)
     * @param bool $forWholeTask
     * @return string
     */
    protected function buildMetaCacheSql($segmentId = null) {
        //integer cast is also save, no need for binding
        $segmentId = (int)$segmentId;
        $selectSql = '';
        $selectSql .= ' CONCAT(\'{"minWidth":\', ifnull(m.minWidth, \'null\'), \',"maxWidth":\', ifnull(m.maxWidth, \'null\'), \',"maxNumberOfLines":\', ifnull(m.maxNumberOfLines, \'null\'), ';
        $selectSql .= '\',"sizeUnit":"\', m.sizeUnit, \'","font":"\', m.font, \'","fontSize":\', m.fontSize, ';
        $selectSql .= '\',"additionalUnitLength":\', m.additionalUnitLength, \',"additionalMrkLength":\', m.additionalMrkLength, ';
        $selectSql .= '\',"siblingData":{\', ifnull(siblings.siblingData,\'\'), \'}}\') metaCache';
        $selectSql .= ' FROM LEK_segment_data d, LEK_segments s';
        $selectSql .= ' LEFT JOIN LEK_segments_meta m ON m.taskGuid = s.taskGuid AND m.segmentId = s.id ';
        if(empty($segmentId)) {
            $selectSql .= ' LEFT JOIN `siblings` ON `siblings`.`transunitId` = m.`transunitId`';
            $selectSql .= ' WHERE d.taskGuid = ? and s.taskGuid = d.taskGuid and d.segmentId = s.id';
        }
        else {
            $selectSql .= ' LEFT JOIN '.$this->metaCacheInnerSql($segmentId).' siblings ON siblings.transunitId = m.transunitId';
            $selectSql .= ' WHERE s.taskGuid = ? and m.transunitId = ? and d.segmentId = s.id';
        }
        $selectSql .= ' GROUP BY d.segmentId';
        return $selectSql;
    }
    
    protected function metaCacheCreateTempTable() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $sql = 'DROP TEMPORARY TABLE IF EXISTS `siblings`;';
        $sql .= 'CREATE TEMPORARY TABLE siblings (INDEX (`transunitId`)) AS '.$this->metaCacheInnerSql().';';
        $db->query($sql, [$this->taskGuid]);
    }
    
    protected function metaCacheInnerSql($segmentId = null) {
        // when ever this SQL is used, we have to increase group_concat_max_len
        // otherwise long segments with many mrks could produce invalid JSON
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query('SET SESSION group_concat_max_len = 100000;');
        
        //integer cast is also save, no need for binding
        $segmentId = (int)$segmentId;
        $sql  = '(SELECT m1.transunitId, GROUP_CONCAT(CONCAT(\'"\',m1.segmentId,\'": \',m1.siblingData) SEPARATOR ",") siblingData ';
        $sql .= ' FROM LEK_segments_meta m1';
        if(empty($segmentId)) {
            $sql .= ' WHERE m1.taskGuid = ? ';
        }
        else {
            $sql .= ' LEFT JOIN LEK_segments_meta m2 ON m2.segmentId = '.$segmentId;
            $sql .= ' WHERE m1.taskGuid = ? AND m1.transunitId = m2.transunitId ';
        }
        $sql .= ' GROUP BY transunitId)';
        return $sql;
    }
    
    /**
     * returns the metaCache of a specific segment
     * @param editor_Models_Segment $segment
     * @return NULL|string
     */
    public function getMetaCache(editor_Models_Segment $segment) {
        $db = Zend_Db_Table::getDefaultAdapter();
        $s = $db->select()
        ->from($this->viewName, ['metaCache'])
        ->where('id = ?', $segment->get('id'));
        $row = $db->fetchRow($s);
        if(empty($row)) {
            return null;
        }
        return $row['metaCache'];
    }
    
    /**
     * returns boolean if the materialized view exists in the DB or not
     * @return boolean
     */
    public function exists() {
        $db = Zend_Db_Table::getDefaultAdapter();
        try{
            $result = $db->describeTable($this->viewName);
            return ! empty($result);
        }catch(Exception $e){
            return false;
        }
    }
    
    /**
     * drops the segment data view to the given taskguid
     * @param string $taskGuid
     */
    public function drop() {
        $this->getTask()->logger('editor.task.mv')->info('E1349', 'The tasks materialized view {matView} was dropped.', ['matView' => $this->viewName]);
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query("DROP TABLE IF EXISTS " . $this->viewName);
    }

    /**
     * returns the current task
     * @return editor_Models_Task
     */
    protected function getTask(): editor_Models_Task {
        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($this->taskGuid);
        return $task;
    }
    
    /**
     * drops unused materialized views. Unused means LEK_tasks modified field is older as X days, 
     * where X can be configured in app.ini (resources.db.matViewLifetime)
     */
    public function cleanUp() {
        $config = Zend_Registry::get('config');
        $lifeTime = (int) $config->resources->db->matViewLifetime;
        $db = Zend_Db_Table::getDefaultAdapter();
        
        //find all affected views 
        //If this is older than lifetime, and mat view was not used, then drop it.
        $viewLike = self::VIEW_PREFIX.'%';
        $sql = 'select table_name from INFORMATION_SCHEMA.TABLES t where t.TABLE_SCHEMA = database() and t.TABLE_NAME like ? and t.create_time < (CURRENT_TIMESTAMP - INTERVAL ? DAY);';
        $viewToDelete = $db->fetchAll($sql, array($viewLike, $lifeTime), Zend_Db::FETCH_COLUMN);
        
        $sql = 'select t.taskGuid from LEK_task t WHERE modified > (CURRENT_TIMESTAMP - INTERVAL ? DAY);';
        $tasksInUse = $db->fetchAll($sql, array($lifeTime), Zend_Db::FETCH_COLUMN);
        $viewsInUse = array_map(array($this, 'makeViewName'),$tasksInUse);
        
        foreach($viewToDelete as $view) {
            if(in_array($view, $viewsInUse)) {
                continue;
            }
            $sql = 'DROP TABLE IF EXISTS `'.$view.'`';
            $db->query($sql);
        }
    }
}