<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Additional Log Writer which just logs all events with an field "task" with a task Entity in its extra data to a separate task log table
 */
class editor_Logger_TaskWriter extends ZfExtended_Logger_Writer_Abstract {
    public function write(ZfExtended_Logger_Event $event) {
        //currently we just do not write duplicates and duplicate info to the task log → the duplicate data is kept in the main log
        if($this->getDuplicateCount($event) > 0) {
            return;
        }
        
        // we clone the event so that we can delete the task afterwards without modifying the real event perhaps used later in another writer
        $event = clone $event;
        $task = $event->extra['task'];
        /* @var $task editor_Models_Task */
        $taskLog = ZfExtended_Factory::get('editor_Models_Logger_Task');
        /* @var $taskLog editor_Models_Logger_Task */
        if($task->isModified()) {
            $modified = $task->getModifiedValues();
            foreach($modified as $field => $value) {
                //we get also modified values if value is the same, but the type was changed (integer vs string)
                // therefore we check == for 0, so we get all falsy value changes
                // otherwise we compare typeless to get only changed values
                if($value == 0 || $value != $task->__call('get'.ucfirst($field),array())) {
                    $event->extra['Task field '.$field] = $value;
                }
            }
        }
        $taskLog->setFromEventAndTask($event, $task);
        // we don't log the task data again, thats implicit via the taskGuid
        // first we have to ensure that extraFlat is filled
        $event->getExtraFlattenendAndSanitized();
        //then we just unset the task in both
        unset($event->extra['task']);
        unset($event->extraFlat['task']);
        $taskLog->setExtra($event->getExtraAsJson());
        $taskLog->save();
    }
    
    public function isAccepted(ZfExtended_Logger_Event $event) {
        if(empty($event->extra) || empty($event->extra['task']) || !is_a($event->extra['task'], 'editor_Models_Task')) {
            return false;
        }
        return parent::isAccepted($event);
    }
}