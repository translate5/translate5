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
 * Plugin Bootstrap for Segment Statistics Plugin
 */
class editor_Plugins_SegmentStatistics_Bootstrap {
    
    public function __construct() {
        $event = Zend_EventManager_StaticEventManager::getInstance();
        /* @var $event Zend_EventManager_StaticEventManager */
        $event->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterImport'));
        $event->attach('editor_TaskController', 'afterStatisticsAction', array($this, 'handleAfterStatistics'));
    }
    
    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterImport(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        
        $worker = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Worker');
        /* @var $worker editor_Plugins_SegmentStatistics_Worker */
        $worker->init($task->getTaskGuid());
        $worker->queue();
    }
    
    /**
     * Handler for Editor_IndexController#afterStatisticsAction
     * adds the statistics calculated by this plugin to the response 
     * AND write the results as simple xml into the task directory (which is currently a workaround)
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterStatistics(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task */
        
        $stat = ZfExtended_Factory::get('editor_Plugins_SegmentStatistics_Models_Statistics');
        /* @var $stat editor_Plugins_SegmentStatistics_Models_Statistics */
        
        $view->statistics->files = $stat->getSummary($task->getTaskGuid());
        
        //workaround for clients which cannot access the rest api
        $this->writeToDisk($task, $view->statistics);
    }
    
    /**
     * Workaround Method to write statistics to task data directory
     * @param editor_Models_Task $task
     * @param stdClass $statistics
     */
    protected function writeToDisk(editor_Models_Task $task, stdClass $statistics) {
        $xml = new SimpleXMLElement('<statistics/>');
        $xml->addChild('taskGuid', $statistics->taskGuid);
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
        
        //the wordcount from Task Table is mostly empty because must be set manually on taskCreation
        //$xml->addChild('wordCount', $statistics->wordCount);
        
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