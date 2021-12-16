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

class editor_Segment_Quality_OperationWorker extends editor_Models_Task_AbstractWorker {
    
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
    private $taskWorkingState;
    
    protected function validateParameters($parameters = array()) {
        // required param steers the way the segments are processed: either directly or via the LEK_segment_tags
        if(array_key_exists('processingMode', $parameters) && array_key_exists('taskWorkingState', $parameters)){
            $this->processingMode = $parameters['processingMode'];
            $this->taskWorkingState = $parameters['taskWorkingState'];
            return true;
        }
        return false;
    }
    
    protected function work(){
        
        $workerId = $this->workerModel->getId();
        
        if ($this->task->lock(NOW_ISO, editor_Segment_Quality_Manager::TASK_STATE_QUALITY_OPERATION)) {
            // lock the task while QA is running
            $this->task->setState($this->taskWorkingState);
            $this->task->save();
        } else {
            return false;
        }
        
        // Crucial: remove any existing segment tag models
        editor_Models_Db_SegmentTags::removeByTaskGuid($this->taskGuid);
        
        // Also, we have to remove all existing qualities
        $table = new editor_Models_Db_SegmentQuality();
        $table->removeByTaskGuid($this->taskGuid);
        
        // add the dependant workers
        editor_Segment_Quality_Manager::instance()->prepareOperation($this->processingMode, $this->task, $workerId);
        
        return true;
    }
}