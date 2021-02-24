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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Default Model for Plugin SegmentStatistics
 * 
 * @method void setId() setId(int $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setSegmentId() setSegmentId(int $segmentid)
 * @method void setFileId() setFileId(int $fileid)
 * @method void setFieldName() setFieldName(string $name)
 * @method void setFieldType() setFieldType(string $type)
 * @method void setTermFound() setTermFound(int $count)
 * @method void setTermNotFound() setTermNotFound(int $count)
 * @method void setCharCount() setCharCount(int $count)
 * 
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method integer getSegmentId() getSegmentId()
 * @method integer getFileId() getFileId()
 * @method string getFieldType() getFieldType()
 * @method integer getTermFound() getTermFound()
 * @method integer getTermNotFound() getTermNotFound()
 * @method integer getCharCount() getCharCount()
 */
class editor_Plugins_SegmentStatistics_Models_Statistics extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_SegmentStatistics_Models_Db_Statistics';
    
    protected $columnsToGet = array(
        'colsAll' => array(
                'stat.fileId', 
                'stat.fieldName', 
                'charCount' => 'SUM(stat.charCount)', 
                'wordCount' => 'SUM(stat.wordCount)', 
                'termFoundCount' => 'SUM(stat.termFound)', 
                'segmentsPerFile' => 'COUNT(stat.id)',
        ),
        'colsFound' => array(
                'stat.fileId', 
                'stat.fieldName', 
                'charFoundCount' => 'SUM(stat.charCount)', 
                'wordFoundCount' => 'SUM(stat.wordCount)', 
                'termFoundCount' => 'SUM(stat.termFound)', 
                'segmentsPerFileFound' => 'COUNT(stat.id)',
        ),
        'colsNotFound' => array(
                'stat.fileId', 
                'stat.fieldName', 
                'charNotFoundCount' => 'SUM(stat.charCount)',
                'wordNotFoundCount' => 'SUM(stat.wordCount)', 
                'termNotFoundCount' => 'SUM(stat.termNotFound)', 
                'segmentsPerFileNotFound' => 'COUNT(stat.id)',
        ),
        'targetColsFound' => array(
                'stat.fileId', 
                'targetCharFoundCount' => 'SUM(stat.charCount)', 
                'targetWordFoundCount' => 'SUM(stat.wordCount)', 
                'targetSegmentsPerFileFound' => 'COUNT(stat.id)',
        ),
        'targetColsNotFound' => array(
                'stat.fileId', 
                'targetCharNotFoundCount' => 'SUM(stat.charCount)', 
                'targetWordNotFoundCount' => 'SUM(stat.wordCount)', 
                'targetSegmentsPerFileNotFound' => 'COUNT(stat.id)',
        ),
    );
    
    /**
     * returns the statistics summary for the given taskGuid and type
     * @param string $taskGuid
     * @param string $type (import or export)
     * @param int &$fileCount returned by reference
     * @return array
     */
    public function calculateSummary($taskGuid, $type, &$fileCount = 0) {
        $files = $this->getFiles($taskGuid);
        $fileCount = count($files);
        
        $db = $this->db;
        
        $meta = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin');
        /* @var $meta editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin */
        $meta->setTarget('stat');
        
        $select = function($cols, $where = null) use ($db, $taskGuid, $type, $meta) {
            $s = $db->select()
                ->from(array('stat' => $db->info($db::NAME)), $cols)
                ->where('stat.taskGuid = ?', $taskGuid)
                ->where('stat.type = ?', $type)
                ->group('stat.fileId')
                ->group('stat.fieldName')
                ->order('fileId ASC');
            if(!empty($where)) {
                $s->where($where);
            }
            return $meta->segmentsMetaJoin($s, $taskGuid);
        };
        
        $rowsAll = $this->db->fetchAll($select($this->columnsToGet['colsAll']));
        $rowsTermFound = $this->db->fetchAll($select($this->columnsToGet['colsFound'], 'termFound > 0'));
        $rowsTermNotFound = $this->db->fetchAll($select($this->columnsToGet['colsNotFound'], 'termNotFound > 0'));
        //gets the target found statistics only to segments where the source contains termNotFounds
        
        $s = $select($this->columnsToGet['targetColsFound'], 'sourceStat.termFound > 0 AND sourceStat.fieldName = "source" AND stat.fieldName = "target"')
            ->where('sourceStat.type = ?', $type)
            ->join(array('sourceStat' => $db->info($db::NAME)), 'sourceStat.segmentId = stat.segmentId', array());
        $targetRowsTermFound = $this->db->fetchAll($s);
        
        //gets the target notFound statistics only to segments where the source contains termNotFounds

        $s = $select($this->columnsToGet['targetColsNotFound'], 'sourceStat.termNotFound > 0 AND sourceStat.fieldName = "source" AND stat.fieldName = "target"')
            ->where('sourceStat.type = ?', $type)
            ->join(array('sourceStat' => $db->info($db::NAME)), 'sourceStat.segmentId = stat.segmentId', array());
        $targetRowsTermNotFound = $this->db->fetchAll($s);

        $rows = array_merge($rowsAll->toArray(), $rowsTermFound->toArray(), $rowsTermNotFound->toArray(), $targetRowsTermFound->toArray(), $targetRowsTermNotFound->toArray());

        $merged = array();
        $result = array();
        foreach($rows as $stat) {
            if(empty($stat['fieldName'])) {
                $stat['fieldName'] = 'source'; //fallback for above joined data
            }
            settype($result[$stat['fileId']], 'array');
            settype($result[$stat['fileId']][$stat['fieldName']], 'array');
            $stat['fileName'] = $files[$stat['fileId']];
            $result[$stat['fileId']][$stat['fieldName']] = array_merge($stat, $result[$stat['fileId']][$stat['fieldName']]);
            $merged[$stat['fileId'].'#'.$stat['fieldName']] = $result[$stat['fileId']][$stat['fieldName']];
        }
        return array_values($merged);
    }
    
    /**
     * returns the term[Not]Found counts grouped by segment states and fileIds
     * @param string $taskGuid
     * @param string $type
     * @return array
     */
    public function calculateStatsByState($taskGuid, $type) {
        $db = $this->db;
        $segments = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $segments editor_Models_Db_Segments */
        
        $meta = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin');
        /* @var $meta editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin */
        $meta->setTarget('p');
        
        $s = $db->select()
            ->from(array('p' => $db->info($db::NAME)), array('foundSum' => 'sum(p.termFound)' , 'notFoundSum' => 'sum(p.termNotFound)'))
            ->join(array('s' => $segments->info($db::NAME)), 's.id = p.segmentId', array('s.fileId', 's.stateId'))
            ->where('s.taskGuid = ?', $taskGuid)
            ->where('p.type = ?', $type)
            ->where('p.fieldType = ?', 'source')
            ->group('s.fileId')
            ->group('s.stateId');
        $meta->segmentsMetaJoin($s, $taskGuid);
        $s->setIntegrityCheck(false);
        $rowset = $this->db->fetchAll($s);
        $res = array();
        foreach($rowset as $row) {
            settype($res[$row->fileId], 'array');
            $res[$row->fileId][$row->stateId] = array(
                'foundSum' => $row->foundSum,
                'notFoundSum' => $row->notFoundSum,
            );
        }
        return $res;
    }
    
    /**
     * deletes the statistics to the given taskGuid and type
     * @param string $taskGuid
     * @param string $type
     */
    public function deleteType($taskGuid, $type) {
        $this->db->delete(array('taskGuid = ?' => $taskGuid, 'type = ?' => $type));
    }
    
    /**
     * returns a map between fileIds and filepaths for the desired task
     * @param string $taskGuid
     * @return [string]
     */
    protected function getFiles($taskGuid) {
        $filetree = ZfExtended_Factory::get ( 'editor_Models_Foldertree' );
        /* @var $filetree editor_Models_Foldertree */
        
        $files = $filetree->getPaths($taskGuid, $filetree::TYPE_FILE );
        $workfilesDirectory = editor_Models_Import_Configuration::getWorkfilesDirectoryName();
        foreach ( $files as $fileid => $file ) {
            $files [$fileid] = trim ( str_replace ( '#!#' . $workfilesDirectory, '', '#!#' . $file ), '/\\' );
        }
        return $files;
    }
    
    /**
     * returns the segment count of the given taskGuid
     * @param string $taskGuid
     * @param bool $editable
     * @return integer the segment count
     */
    public function calculateSegmentCountFiltered($taskGuid, $onlyEditable=false) {
        $meta = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin');
        /* @var $meta editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin */
        $meta->setTarget('seg');
        $meta->setSegmentIdColumn('id');
        
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $s = $db->select()
            ->from(array('seg' => $db->info($db::NAME)), array('segCount' => 'COUNT(seg.id)'))
            ->where('seg.taskGuid = ?', $taskGuid);
        if($onlyEditable){
            $s->where('seg.editable = 1');
        }
        $meta->segmentsMetaJoin($s, $taskGuid);
        $row = $this->db->fetchRow($s);
        return $row->segCount;
    }
    
    /**
     * The Plugin editor_Plugins_SegmentStatistics_BootstrapEditableOnly deleted the import statistics
     * Since we need them again for export statistics, we have to regenerate them. 
     * This is possible because only locked segment stats were deleted, therefore nothing was changed in this segments.
     * 
     * TODO This method can be removed after all live projects are not affected anymore by BootstrapEditableOnly
     * 
     * @param string $taskGuid
     */
    public function regenerateImportStats($taskGuid) {
        $db = $this->db;
        $table = $db->info($db::NAME);
        $adapter = $db->getAdapter();
        
        //count if import and export types differ:
        $s = $db->select()
            ->from($this->db, array('type', 'cnt' => 'COUNT(id)'))
            ->where('taskGuid = ?', $taskGuid)
            ->group('type');
        $rows = $this->db->fetchAll($s)->toArray();
        $foundStats = array();
        foreach($rows as $row) {
            $foundStats[$row['type']] = $row['cnt'];
        }
        settype($foundStats['import'], 'integer');
        settype($foundStats['export'], 'integer');
        if($foundStats['export'] == 0 || $foundStats['export'] == $foundStats['import']) {
            //if no export stats found, or if they are already equal, nothing to do
            return;
        }

        //rebuild import query:
        $sql = 'INSERT INTO '.$table.' (`taskGuid`,`segmentId`,`fileId`,`fieldName`,`fieldType`, `charCount`,`termNotFound`,`termFound`,`type`) ';
        $sql .= 'SELECT stat1.`taskGuid`,stat1.`segmentId`,stat1.`fileId`,stat1.`fieldName`,stat1.`fieldType`, stat1.`charCount`,stat1.`termNotFound`,stat1.`termFound`, \'import\' `type` ';
        $sql .= 'FROM '.$table.' stat1 WHERE NOT EXISTS ( ';
        $sql .= 'SELECT NULL FROM LEK_plugin_segmentstatistics stat2 WHERE stat1.segmentId = stat2.segmentId ';
        $sql .= 'AND stat1.taskGuid = stat2.taskGuid AND stat2.type = \'import\'';
        $sql .= ') AND stat1.taskGuid = ? AND stat1.type = \'export\'';
        $adapter->query($adapter->quoteInto($sql, $taskGuid));
        
        //fill termFound of existing import stats
        //this is an approximate value since it is only correct 
        //if no term was added or deleted in the segment!!!
        $sql = 'update '.$table.' import, '.$table.' export ';
        $sql .= 'set import.termFound = greatest(0, (export.termFound + export.termNotFound - import.termNotFound)) ';
        $sql .= 'where import.segmentId = export.segmentId and import.fieldName = export.fieldName ';
        $sql .= 'and import.taskGuid = ? and import.termFound < 0 and import.type = \'import\' and export.type = \'export\'';
        $adapter->query($adapter->quoteInto($sql, $taskGuid));
    }
}