<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * editor_Plugins_SegmentStatistics_Worker Class
 */
class editor_Plugins_SegmentStatistics_Worker extends ZfExtended_Worker_Abstract {
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
        $data = ZfExtended_Factory::get('editor_Models_Segment_Iterator', array($this->taskGuid));
        /* @var $data editor_Models_Segment_Iterator */
        if ($data->isEmpty()) {
            return false;
        }
        
        $this->setType();
        $this->prepareIfExport();
        
        $sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $sfm editor_Models_SegmentFieldManager */
        $sfm->initFields($this->taskGuid);
        
        $fields = $sfm->getFieldList();
        
        $this->term = ZfExtended_Factory::get('editor_Models_Term');
        
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        //walk over segments and fields and get and store statistics data
        foreach($data as $segment) {
            /* @var $segment editor_Models_Segment */
            foreach($fields as $field) {
                $segmentContent = $this->getSegmentContent($sfm, $segment, $field);
                $stat->init();
                $stat->setTaskGuid($this->taskGuid);
                $stat->setSegmentId($segment->getId());
                $stat->setFieldName($field->name);//always the name without "Edit"!
                $stat->setFieldType($field->type);
                $stat->setType($this->type);
                $stat->setFileId($segment->getFileId());
                $stat->setCharCount($segment->charCount($segmentContent));
                $stat->setWordCount($segment->wordCount($segmentContent));
                
                $termCount = $this->termCounter($segmentContent, $field->name);
  
                $stat->setTermNotFound($termCount['notFound']);
                $stat->setTermFound($termCount['found']);
                
                $stat->save();
            }
        }
        $this->storeTermStats();
        
        //regenerate missing import Stats if needed:
        //copy exports nach import, wo es kein import passend zum export gibt!
        $stat->regenerateImportStats($this->taskGuid);
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
            'found' => 0,
            'notFound' => 0,
        );
        
        $termInfo = $this->term->getTermInfosFromSegment($segmentContent);
        
        foreach($termInfo as $term) {
            //for Term stat, we count source terms only:
            if($fieldName == 'source') {
                $this->findTermContent($term['mid']);
            }
            
            settype($this->termFoundCounter[$term['mid']], 'integer');
            settype($this->termNotFoundCounter[$term['mid']], 'integer');
            if(in_array('transNotFound', $term['classes'])) {
                $termCount['notFound']++;
                $this->termNotFoundCounter[$term['mid']]++;
            } else {
                $termCount['found']++;
                $this->termFoundCounter[$term['mid']]++;
            }
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
            $t = $this->term->loadByMid($mid, $this->taskGuid);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e){
            $this->termContent[$mid] = "Term not found in DB! Mid: ".$mid;
            return;
        }
        $this->termContent[$mid] = $t->term;
    }
    
    /**
     * Stores the term usage in the DB
     */
    protected function storeTermStats() {
        $termStat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_TermStatistics');
        /* @var $termStat editor_Plugins_SegmentStatistics_Models_TermStatistics */
        //since the term stat are generated on export only, they have to be deleted and regenerated on each export:
        $termStat->deleteByTask($this->taskGuid);
        foreach($this->termContent as $mid => $term) {
            $termStat->init(array(
                'taskGuid' => $this->taskGuid,
                'mid' => $mid,
                'term' => $term,
                'notFoundCount' => $this->termNotFoundCounter[$mid],
                'foundCount' => $this->termFoundCounter[$mid],
            ));
            $termStat->save();
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
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        $stat->deleteType($this->taskGuid, self::TYPE_EXPORT);
    }
        
    /**
     * Method to write statistics to task data directory
     */
    protected function writeToDisk() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $statistics = $task->getStatistics();
        
        if($this->type == self::TYPE_IMPORT) {
            $statistics->filesImport = $this->getFileStatistics(self::TYPE_IMPORT);
        }
        else {
            $statistics->filesImport = $this->getFileStatistics(self::TYPE_IMPORT);
            $statistics->filesExport = $this->getFileStatistics(self::TYPE_EXPORT);
        }
        
        $statistics->taskFields = $this->taskFieldsStat;
        
        $filename = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.$this->getFileName();
        
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
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        $files = $stat->calculateSummary($this->taskGuid, $type);
        
        $statByState = $stat->calculateStatsByState($this->taskGuid, $type);
        
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
        return 'segmentstatistics-export-'.date('Y-m-d-H-i');
    }
}