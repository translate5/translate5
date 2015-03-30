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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Default Model for Plugin SegmentStatistics
 * 
 * @method void setId() setId(integer $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setSegmentId() setSegmentId(integer $segmentid)
 * @method void setFileId() setFileId(integer $fileid)
 * @method void setFieldName() setFieldName(string $name)
 * @method void setFieldType() setFieldType(string $type)
 * @method void setTermFound() setTermFound(integer $count)
 * @method void setTermNotFound() setTermNotFound(integer $count)
 * @method void setCharCount() setCharCount(integer $count)
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
        'colsAll' => array('fileId', 'fieldName', 'charCount' => 'SUM(charCount)', 'termFoundCount' => 'SUM(termFound)', 'segmentsPerFile' => 'COUNT(id)'),
        'colsFound' => array('fileId', 'fieldName', 'charFoundCount' => 'SUM(charCount)', 'termFoundCount' => 'SUM(termFound)', 'segmentsPerFileFound' => 'COUNT(id)'),
        'colsNotFound' => array('fileId', 'fieldName', 'charNotFoundCount' => 'SUM(charCount)', 'termNotFoundCount' => 'SUM(termNotFound)', 'segmentsPerFileNotFound' => 'COUNT(id)'),
        'targetColsFound' => array('stat.fileId', 'targetCharFoundCount' => 'SUM(stat.charCount)', 'targetSegmentsPerFileFound' => 'COUNT(stat.id)'),
        'targetColsNotFound' => array('stat.fileId', 'targetCharNotFoundCount' => 'SUM(stat.charCount)', 'targetSegmentsPerFileNotFound' => 'COUNT(stat.id)'),
    );
    
    /**
     * returns the statistics summary for the given taskGuid and type
     * @param string $taskGuid
     * @param string $type
     * @return array
     */
    public function calculateSummary($taskGuid, $type) {
        $files = $this->getFiles($taskGuid);
        $db = $this->db;
        
        $select = function($cols, $where = null) use ($db, $taskGuid, $type) {
            $s = $db->select()
                ->from(array('stat' => $db->info($db::NAME)), $cols)
                ->where('stat.taskGuid = ?', $taskGuid)
                ->where('stat.type = ?', $type)
                ->group('stat.fileId')
                ->group('stat.fieldName');
            if(!empty($where)) {
                $s->where($where);
            }
            return $s;
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
        $config = Zend_Registry::get('config');
        $filetree = ZfExtended_Factory::get ( 'editor_Models_Foldertree' );
        /* @var $filetree editor_Models_Foldertree */
        
        $files = $filetree->getPaths($taskGuid, $filetree::TYPE_FILE );
        $proofRead = $config->runtimeOptions->import->proofReadDirectory;
        foreach ( $files as $fileid => $file ) {
            $files [$fileid] = trim ( str_replace ( '#!#' . $proofRead, '', '#!#' . $file ), '/\\' );
        }
        return $files;
    }
    
    /**
     * The Plugin editor_Plugins_SegmentStatistics_BootstrapEditableOnly deleted the import statistics
     * Since we need them again for export statistics, we have to regenerate them. 
     * This is possible because only locked segment stats were deleted, therefore nothing was changed in this segments.
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