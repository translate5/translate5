<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Additional Log Writer which just logs all events with an field "task" with a task Entity in its extra data to a separate task log table
 */
class editor_Logger_TaskWriter extends ZfExtended_Logger_Writer_Abstract {
    public function write(ZfExtended_Logger_Event $event) {
        //currently we just do not write duplicates and duplicate info to the task log → the duplicate data is kept in the main log
        //TODO we can not just ignore duplicates, since the error may be task independent, but we should get the error on each affected task!
        //example: LanguageResource is not available will result in many duplicates, but we should have at least one entry
        // for each task. Solution: we have to search for the duplication hash in the log of the specific task,
        // if it does not exist, we add the entry, otherwise we ignore it. So the error is at least once in the log.
//         if($this->getDuplicateCount($event) > 0) {
//             return;
//         }
        
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
        try {
            $taskLog->save();
        }
        catch(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            //do nothing here! The error itself was logged in the system log,
            // the task seems to be deleted in the meantime, so no need and way to log it here
            // this can happen for example if error happens in a worker (async from GUI),
            // and the mail logger needs some time to send the mail, and the task writer is the last writer,
            // so the task may be deleted while the worker is not finished yet doing the logging
        }
    }
    
    public function isAccepted(ZfExtended_Logger_Event $event) {
        if(empty($event->extra) || empty($event->extra['task']) || !is_a($event->extra['task'], 'editor_Models_Task')) {
            return false;
        }
        return parent::isAccepted($event);
    }
}