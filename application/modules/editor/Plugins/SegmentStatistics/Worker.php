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
 * editor_Plugins_SegmentStatistics_Worker Class
 */
class editor_Plugins_SegmentStatistics_Worker extends editor_Models_Task_AbstractWorker {
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    
    /**
     * contains the stat type
     * @var string
     */
    protected $type;
    
    /**
     * @var array
     */
    protected $taskFieldsStat = array();
    
    /**
     * @var editor_Plugins_SegmentStatistics_Bootstrap
     */
    protected $plugin;
    
    /**
     * @var editor_Models_Term
     */
    protected $term;
    
    /**
     * @var array
     */
    protected $termFoundCounter = array();
    
    /**
     * @var array
     */
    protected $termNotFoundCounter = array();
    
    /**
     * contains a mid => termContent mapping
     * @var array
     */
    protected $termContent = array();
    
    /**
     * @var editor_Plugins_SegmentStatistics_Models_Statistics
     */
    protected $stat;
    
    /**
     * @var editor_Plugins_SegmentStatistics_Models_TermStatistics
     */
    protected $termStat;
    
    /**
     * internal file counter
     * @var integer
     */
    protected $fileCount = 0;
    
    public function __construct() {
        parent::__construct();
        $this->stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['type'])) {
            error_log('Missing Parameter "type" in '.__CLASS__);
            return false;
        }
        return true;
    } 
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $this->task->createMaterializedView();
        $data = ZfExtended_Factory::get('editor_Models_Segment_Iterator', array($this->taskGuid));
        /* @var $data editor_Models_Segment_Iterator */
        if ($data->isEmpty()) {
            return false;
        }
        $this->term = ZfExtended_Factory::get('editor_Models_Term');
        $this->termStat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_TermStatistics');
        
        $this->setType();
        $this->prepareIfExport();
        
        $sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $sfm editor_Models_SegmentFieldManager */
        $sfm->initFields($this->taskGuid);
        
        $fields = $sfm->getFieldList();
        
        //walk over segments and fields and get and store statistics data
        foreach($data as $segment) {
            /* @var $segment editor_Models_Segment */
            foreach($fields as $field) {
                $segmentContent = $this->getSegmentContent($sfm, $segment, $field);
                $termCount = $this->termCounter($segmentContent, $field->name);
                
                $this->storeSegmentStats($segment, $segmentContent, $field, $termCount);
                $this->storeTermStats($segment, $field, $termCount);
            }
        }
        
        //regenerate missing import Stats if needed:
        //copy exports nach import, wo es kein import passend zum export gibt!
        $this->stat->regenerateImportStats($this->taskGuid);
        return true;
    }
    
    /**
     * Counts the [not]Found terms in segment content, counts also over all segments for each term mid
     * @param string $segmentContent
     * @param string $fieldType
     * @return multitype:number
     */
    protected function termCounter($segmentContent, $fieldName) {
        $termCount = array(
            'found' => array(),
            'notFound' => array(),
        );
        
        $termInfo = $this->term->getTermInfosFromSegment($segmentContent);
        
        foreach($termInfo as $term) {
            $this->findTermContent($term['mid']);
            settype($termCount['found'][$term['mid']], 'integer');
            settype($termCount['notFound'][$term['mid']], 'integer');
            if(in_array('transNotFound', $term['classes'])) {
                $idx = 'notFound';
            } elseif (in_array('transFound', $term['classes'])) {
                $idx = 'found';
            } else {
                continue;
            }
            $termCount[$idx][$term['mid']]++;
        }
        
        return $termCount;
    }
    
    /**
     * Finds the term to a given mid and stores it internally
     * @param array $term
     */
    protected function findTermContent($mid) {
        if(!empty($this->termContent[$mid])) {
            return;
        }
        try {
            $assoc=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $assoc editor_Models_TermCollection_TermCollection */
            $collectionIds=$assoc->getCollectionsForTask($this->taskGuid);
            if(empty($collectionIds)) {
                return;
            }
            $t = $this->term->loadByMid($mid, $collectionIds);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e){
            $this->termContent[$mid] = "Term not found in DB! Mid: ".$mid;
            return;
        }
        $this->termContent[$mid] = $t->term;
    }
    
    /**
     * Stores the segment stats in the DB
     * @param editor_Models_Segment $segment
     * @param string $segmentContent
     * @param Zend_Db_Table_Row $field
     * @param array $termCount
     */
    protected function storeSegmentStats(editor_Models_Segment $segment, $segmentContent, Zend_Db_Table_Row $field, array $termCount) {
        $stat = $this->stat;
        $stat->init();
        $stat->setTaskGuid($this->taskGuid);
        $stat->setSegmentId($segment->getId());
        $stat->setFieldName($field->name);//always the name without "Edit"!
        $stat->setFieldType($field->type);
        $stat->setType($this->type);
        $stat->setFileId($segment->getFileId());
        $stat->setCharCount($segment->textLengthByChar($segmentContent));
        $stat->setWordCount($segment->wordCount($segmentContent));
        $stat->setTermNotFound(array_sum($termCount['notFound']));
        $stat->setTermFound(array_sum($termCount['found']));
        $stat->save();
    }
    
    /**
     * Stores the term usage in the DB (on export only!)
     * @param editor_Models_Segment $segment
     * @param Zend_Db_Table_Row $field
     * @param array $termCount
     */
    protected function storeTermStats(editor_Models_Segment $segment, Zend_Db_Table_Row $field, array $termCount) {
        //since the term stat are generated on export only, they have to be deleted and regenerated on each export:
        foreach($termCount['found'] as $mid => $found) {
            $this->termStat->init(array(
                'taskGuid' => $this->taskGuid,
                'mid' => $mid,
                'segmentId' => $segment->getId(),
                'fileId' => $segment->getFileId(),
                'fieldName' => $field->name,
                'fieldType' => $field->type,
                'term' => $this->termContent[$mid],
                'type' => $this->type,
                'notFoundCount' => $termCount['notFound'][$mid],
                'foundCount' => $found,
            ));
            $this->termStat->save();
        }
    }
    
    /**
     * returns the affected segmentContent (which is the edited field for editable ones)
     * @param editor_Models_SegmentFieldManager $sfm
     * @param editor_Models_Segment $segment
     * @param Zend_Db_Table_Row $field
     */
    protected function getSegmentContent(editor_Models_SegmentFieldManager $sfm, editor_Models_Segment $segment, Zend_Db_Table_Row $field) {
        //on export respect edited field:
        $useEditable = $field->editable && $this->type == self::TYPE_EXPORT;
        $fieldName = ($useEditable ? $sfm->getEditIndex($field->name) : $field->name);
        return $segment->getDataObject()->$fieldName;
    }
    
    /**
     * sets the internal type from the models parameters
     */
    protected function setType() {
        $parameters = $this->workerModel->getParameters();
        $this->type = $parameters['type'];
    }
    
    /**
     * removes existing export stats, since they may exist only once in DB
     */
    protected function prepareIfExport() {
        if($this->type != self::TYPE_EXPORT) {
            return;
        }
        $this->stat->deleteType($this->taskGuid, self::TYPE_EXPORT);
        $this->termStat->deleteType($this->taskGuid, self::TYPE_EXPORT);
    }
        
    /**
     * Method to write statistics to task data directory
     * parameter decides if configured filter should be used or not
     * @param bool $filtered
     */
    protected function writeToDisk($filtered = false) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $this->taskFieldsStat = array();
        
        
        $metaJoin = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin');
        /* @var $metaJoin editor_Plugins_SegmentStatistics_Models_SegmentMetaJoin */
        
        $metaJoin::setEnabled($filtered);
        
        $statistics = $task->getStatistics();
        if($filtered) {
            $statistics->filtered = $metaJoin->getFilterConditions();
        }
        else {
            $statistics->filtered = array();
        }
        
        //If we try to create a filtered stat without filters we dont create it
        if($filtered && empty($statistics->filtered)) {
            return;
        }
        
        $this->fillStatistics($statistics);
        
        $filename = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.$this->getFileName();
        if($filtered) {
            //overwrite segment counts with filtered values
            $statistics->segmentCount = $this->stat->calculateSegmentCountFiltered($this->taskGuid);
            $statistics->segmentCountEditable = $this->stat->calculateSegmentCountFiltered($this->taskGuid, true);
            $filename .= '-filtered';
        }
        
        $this->export($task, $statistics, $filename);
    }
    
    /**
     * @param stdClass $statistics
     */
    protected function fillStatistics($statistics) {
        if($this->type == self::TYPE_IMPORT) {
            $statistics->filesImport = $this->getFileStatistics(self::TYPE_IMPORT);
        }
        else {
            $statistics->filesImport = $this->getFileStatistics(self::TYPE_IMPORT);
            $statistics->filesExport = $this->getFileStatistics(self::TYPE_EXPORT);
        }
        $statistics->fileCount = $this->fileCount;
        $statistics->taskFields = $this->taskFieldsStat;
    }
    
    /**
     * @param editor_Models_Task $task
     * @param stdClass $statistics
     * @param string $filename
     */
    protected function export(editor_Models_Task $task, stdClass $statistics, $filename) {
        $exporters = array(
                'editor_Plugins_SegmentStatistics_Models_Export_Xml',
                'editor_Plugins_SegmentStatistics_Models_Export_Xls',
        );
        foreach ($exporters as $cls) {
            $exporter = ZfExtended_Factory::get($cls);
            /* @var $exporter editor_Plugins_SegmentStatistics_Models_Export_Abstract */
            $exporter->init($task, $statistics, $this->workerModel->getParameters());
            $exporter->writeToDisk($filename);
        }
    }
    
    /**
     * returns the file statistics for the given type
     * @param string $type
     * @return array
     */
    protected function getFileStatistics($type) {
        $files = $this->stat->calculateSummary($this->taskGuid, $type, $this->fileCount);
        $statByState = $this->stat->calculateStatsByState($this->taskGuid, $type);
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segmentPerFiles = $segment->calculateSummary($this->taskGuid);
        foreach($files as &$file) {
            settype($segmentPerFiles[$file['fileId']], 'int');
            $file['segmentsPerFile'] = $segmentPerFiles[$file['fileId']];
            $this->initTaskFieldsStat($file, $type);
            $this->addSourceStatistics($file, $type);
            $file['statByState'] = $statByState[$file['fileId']];
        }
        return $files;
    }
    
    protected function initTaskFieldsStat(& $fileStat, $type) {
        $fieldName = $fileStat['fieldName'];
        settype($fileStat['segmentsPerFileFound'], 'integer');
        settype($fileStat['segmentsPerFileNotFound'], 'integer');
        settype($this->taskFieldsStat[$type], 'array');
        settype($this->taskFieldsStat[$type][$fieldName], 'array');
        
        $taskFieldStat = &$this->taskFieldsStat[$type][$fieldName];
        
        $taskSums = array(
            'taskCharFoundCount' => 'charFoundCount',
            'taskCharNotFoundCount' => 'charNotFoundCount',
            'taskWordFoundCount' => 'wordFoundCount',
            'taskWordNotFoundCount' => 'wordNotFoundCount',
            'taskTermFoundCount' => 'termFoundCount',
            'taskTermNotFoundCount' => 'termNotFoundCount',
        );
        
        foreach($taskSums as $k => $v) {
            settype($fileStat[$v], 'integer');
            settype($taskFieldStat[$k], 'integer');
            $taskFieldStat[$k] += $fileStat[$v];
        }
    }
    
    protected function addSourceStatistics(array &$fileStat, $type) {
        $fieldName = $fileStat['fieldName'];
        if($fieldName !== 'source') {
            return;
        }
        $taskFieldStat = &$this->taskFieldsStat[$type][$fieldName];
        
        $taskSums = array(
            'taskTargetCharFoundCount' => 'targetCharFoundCount',
            'taskTargetCharNotFoundCount' => 'targetCharNotFoundCount',
            'taskTargetWordFoundCount' => 'targetWordFoundCount',
            'taskTargetWordNotFoundCount' => 'targetWordNotFoundCount',
            'taskTargetSegmentsPerFileFound' => 'targetSegmentsPerFileFound',
            'taskTargetSegmentsPerFileNotFound' => 'targetSegmentsPerFileNotFound',
        );
        
        foreach($taskSums as $k => $v) {
            settype($fileStat[$v], 'integer');
            settype($taskFieldStat[$k], 'integer');
            $taskFieldStat[$k] += $fileStat[$v];
        }
    }
    
    /**
     * returns the filename for the xml stat file
     * @return string
     */
    protected function getFileName() {
        if($this->type == self::TYPE_IMPORT) {
            return 'segmentstatistics-import';
        }
        if(ZfExtended_Debug::hasLevel('plugin', 'SegmentStatistics')) {
            return 'segmentstatistics-export';
        }
        return 'segmentstatistics-export-'.date('Y-m-d-H-i');
    }
}