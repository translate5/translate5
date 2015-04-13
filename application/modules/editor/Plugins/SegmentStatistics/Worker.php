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
        $termFoundRegEx = '#<div[^>]+class="[^"]*((term[^"]*transFound)|(transFound[^"]*term))[^"]*"[^>]*>#';
        $termNotFoundRegEx = '#<div[^>]+class="[^"]*((term[^"]*transNotFound)|(transNotFound[^"]*term))[^"]*"[^>]*>#';
        
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
                $stat->setCharCount(mb_strlen($segment->stripTags($segmentContent)));
                $count = preg_match_all($termNotFoundRegEx, $segmentContent, $matches);
                $stat->setTermNotFound($count);
                $count = preg_match_all($termFoundRegEx, $segmentContent, $matches);
                $stat->setTermFound($count);
                $stat->save();
            }
        }
        
        //regenerate missing import Stats if needed:
        //copy exports nach import, wo es kein import passend zum export gibt!
        $stat->regenerateImportStats($this->taskGuid);
        return true;
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
                
        $xml = new SimpleXMLElement('<statistics/>');
        $xml->addChild('taskGuid', $this->taskGuid);
        $xml->addChild('taskName', $statistics->taskName);
        $xml->addChild('segmentCount', $statistics->segmentCount);
        $xml->addChild('segmentCountEditable', $statistics->segmentCountEditable);
        
        if($this->type == self::TYPE_IMPORT) {
            $this->addTypeSpecificXml($xml->addChild('import'), $statistics, self::TYPE_IMPORT);
        }
        else {
            $this->addTypeSpecificXml($xml->addChild('import'), $statistics, self::TYPE_IMPORT);
            $this->addTypeSpecificXml($xml->addChild('export'), $statistics, self::TYPE_EXPORT);
        }
        
        $filename = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.$this->getFileName();
        $xml->asXML($filename);
    }
    
    protected function addTypeSpecificXml($xml, $statistics, $type) {
        $statistics->files = $this->getFileStatistics($type);
        $files = $xml->addChild('files');
        
        $taskFieldsStat = array();
        
        $lastFileId = 0;
        $lastField = null;
        
        foreach($statistics->files as $fileStat) {
            settype($fileStat['charFoundCount'],'integer');
            settype($fileStat['charNotFoundCount'],'integer');
            settype($fileStat['termFoundCount'],'integer');
            settype($fileStat['termNotFoundCount'],'integer');
            settype($fileStat['segmentsPerFileFound'],'integer');
            settype($fileStat['segmentsPerFileNotFound'],'integer');
            
            //implement next file:
            if($lastFileId != $fileStat['fileId']) {
                $file = $files->addChild('file');
                $file->addChild('fileName', $fileStat['fileName']);
                $file->addChild('fileId', $fileStat['fileId']);
                $fields = $file->addChild('fields');
                $lastFileId = $fileStat['fileId'];
            }

            //calculate statistics per field for whole task
            $fieldName = $fileStat['fieldName'];
            settype($taskFieldsStat[$fieldName], 'array');
            settype($taskFieldsStat[$fieldName]['taskCharFoundCount'], 'integer');
            settype($taskFieldsStat[$fieldName]['taskCharNotFoundCount'], 'integer');
            settype($taskFieldsStat[$fieldName]['taskTermFoundCount'], 'integer');
            settype($taskFieldsStat[$fieldName]['taskTermNotFoundCount'], 'integer');
            $taskFieldsStat[$fieldName]['taskCharFoundCount'] += $fileStat['charFoundCount'];
            $taskFieldsStat[$fieldName]['taskCharNotFoundCount'] += $fileStat['charNotFoundCount'];
            $taskFieldsStat[$fieldName]['taskTermFoundCount'] += $fileStat['termFoundCount'];
            $taskFieldsStat[$fieldName]['taskTermNotFoundCount'] += $fileStat['termNotFoundCount'];
            
            
            $field = $fields->addChild('field');
            $field->addChild('fieldName', $fieldName);
            $field->addChild('charFoundCount', $fileStat['charFoundCount']);
            $field->addChild('charNotFoundCount', $fileStat['charNotFoundCount']);
            $field->addChild('termFoundCount', $fileStat['termFoundCount']);
            $field->addChild('termNotFoundCount', $fileStat['termNotFoundCount']);
            $field->addChild('segmentsPerFile', $fileStat['segmentsPerFile']);
            $field->addChild('segmentsPerFileFound', $fileStat['segmentsPerFileFound']);
            $field->addChild('segmentsPerFileNotFound', $fileStat['segmentsPerFileNotFound']);
            if($fieldName == 'source') {
                settype($fileStat['targetCharFoundCount'],'integer');
                settype($fileStat['targetCharNotFoundCount'],'integer');
                settype($fileStat['targetSegmentsPerFileFound'],'integer');
                settype($fileStat['targetSegmentsPerFileNotFound'],'integer');
                settype($taskFieldsStat[$fieldName]['taskTargetCharFoundCount'], 'integer');
                settype($taskFieldsStat[$fieldName]['taskTargetCharNotFoundCount'], 'integer');
                settype($taskFieldsStat[$fieldName]['taskTargetSegmentsPerFileFound'], 'integer');
                settype($taskFieldsStat[$fieldName]['taskTargetSegmentsPerFileNotFound'], 'integer');
                $taskFieldsStat[$fieldName]['taskTargetCharFoundCount'] += $fileStat['targetCharFoundCount'];
                $taskFieldsStat[$fieldName]['taskTargetCharNotFoundCount'] += $fileStat['targetCharNotFoundCount'];
                $taskFieldsStat[$fieldName]['taskTargetSegmentsPerFileFound'] += $fileStat['targetSegmentsPerFileFound'];
                $taskFieldsStat[$fieldName]['taskTargetSegmentsPerFileNotFound'] += $fileStat['targetSegmentsPerFileNotFound'];
                //<!-- only targets to sources with transNotFounds are counted: --> 
                $field->addChild('targetCharFoundCount', $fileStat['targetCharFoundCount']);
                $field->addChild('targetCharNotFoundCount', $fileStat['targetCharNotFoundCount']);
                $field->addChild('targetSegmentsPerFileFound', $fileStat['targetSegmentsPerFileFound']);
                $field->addChild('targetSegmentsPerFileNotFound', $fileStat['targetSegmentsPerFileNotFound']);
            }
        }
        
        //add the statistics per field for whole task
        $fields = $xml->addChild('fields');
        foreach($taskFieldsStat as $fieldName => $fieldStat) {
            $field = $fields->addChild('field');
            $field->addChild('fieldName', $fieldName);
            foreach($fieldStat as $key => $value) {
                $field->addChild($key, $value);
            }
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
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segmentPerFiles = $segment->calculateSummary($this->taskGuid);
        foreach($files as &$file) {
            settype($segmentPerFiles[$file['fileId']], 'int');
            $file['segmentsPerFile'] = $segmentPerFiles[$file['fileId']];
        }
        return $files;
    }
    
    /**
     * returns the filename for the xml stat file
     * @return string
     */
    protected function getFileName() {
        if($this->type == self::TYPE_IMPORT) {
            return 'segmentstatistics-import.xml';
        }
        return 'segmentstatistics-export-'.date('Y-m-d-H-i').'.xml';
    }
}