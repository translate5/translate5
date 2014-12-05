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
/**
 * editor_Plugins_SegmentStatistics_Worker Class
 */
class editor_Plugins_SegmentStatistics_Worker extends ZfExtended_Worker_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return empty($parameters);
    } 
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $data = ZfExtended_Factory::get('editor_Models_Segment_Iterator', array($this->taskGuid));
        /* @var $data editor_Models_Segment_Iterator */
        
        $sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $sfm editor_Models_SegmentFieldManager */
        $sfm->initFields($this->taskGuid);
        
        $fields = $sfm->getFieldList();
        $termNotFoundRegEx = '#<div[^>]+class="[^"]*((term[^"]*transNotFound)|(transNotFound[^"]*term))[^"]*"[^>]*>#';
        
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        //walk over segments and fields and get and store statistics data
        foreach($data as $segment) {
            foreach($fields as $field) {
                $fieldName = $field->name;
                $segmentContent = $segment->getDataObject()->$fieldName;
                $stat->init();
                $stat->setTaskGuid($this->taskGuid);
                $stat->setSegmentId($segment->getId());
                $stat->setFieldName($fieldName);
                $stat->setFieldType($field->type);
                $stat->setFileId($segment->getFileId());
                $stat->setCharCount(mb_strlen(strip_tags($segmentContent)));
                $count = preg_match_all($termNotFoundRegEx, $segmentContent, $matches);
                $stat->setTermNotFound($count);
                $stat->save();
            }
        }
        $this->writeToDisk();
    }
    
    /**
     * Method to write statistics to task data directory
     */
    protected function writeToDisk() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $statistics = $task->getStatistics();
                
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        
        $statistics->files = $stat->getSummary($this->taskGuid);
        
        $xml = new SimpleXMLElement('<statistics/>');
        $xml->addChild('taskGuid', $this->taskGuid);
        $xml->addChild('taskName', $statistics->taskName);
        $files = $xml->addChild('files');
        
        $taskFieldsStat = array();
        
        $lastFileId = 0;
        $lastField = null;
        foreach($statistics->files as $fileStat) {
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
            settype($taskFieldsStat[$fieldName]['taskCharCount'], 'integer');
            settype($taskFieldsStat[$fieldName]['taskTermNotFound'], 'integer');
            $taskFieldsStat[$fieldName]['taskCharCount'] += $fileStat['charCount'];
            $taskFieldsStat[$fieldName]['taskTermNotFound'] += $fileStat['termNotFoundCount'];
            
            $field = $fields->addChild('field');
            $field->addChild('fieldName', $fieldName);
            $field->addChild('charCount', $fileStat['charCount']);
            $field->addChild('termNotFoundCount', $fileStat['termNotFoundCount']);
            $field->addChild('segmentsPerFile', $fileStat['segmentsPerFile']);
        }
        
        $xml->addChild('segmentCount', $statistics->segmentCount);
        
        //add the statistics per field for whole task
        foreach($taskFieldsStat as $fieldName => $fieldStat) {
            $fields = $xml->addChild('fields');
            $field = $fields->addChild('field');
            $field->addChild('fieldName', $fieldName);
            foreach($fieldStat as $key => $value) {
                $field->addChild($key, $value);
            }
        }
        
        $filename = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.'segmentstatistics.xml';
        $xml->asXML($filename);
    }
}