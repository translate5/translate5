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
 * 
 * Finishes Operations regarding the Quality processing
 *
 */
class editor_Segment_Quality_OperationFinishingWorker extends editor_Models_Task_AbstractWorker {
    
    /**
     * This defines the processing mode for the segments we process
     * This worker is used in various situations
     * @var string
     */
    private $processingMode;
    /**
     * Defines the initial state of the task before the operation was started
     * @var string
     */
    private $taskInitialState;
    
    protected function validateParameters($parameters = array()) {
        // required param steers the way the segments are processed: either directly or via the LEK_segment_tags
        if(array_key_exists('processingMode', $parameters) && array_key_exists('taskInitialState', $parameters)){
            $this->processingMode = $parameters['processingMode'];
            $this->taskInitialState = $parameters['taskInitialState'];
            return true;
        }
        return false;
    }
    
    protected function work(){        
        // write the segments back to the segments model
        editor_Segment_Quality_Manager::instance()->finishOperation($this->processingMode, $this->task);
        // unlock the task if locked
        if($this->task->isLocked($this->task->getTaskGuid())){
            $this->task->unlock();
        }
        // reset the quality operation state if not set
        if($this->task->getState() != $this->taskInitialState){
            $this->task->setState($this->taskInitialState);
            $this->task->save();
        }
        return true;
    }
}
