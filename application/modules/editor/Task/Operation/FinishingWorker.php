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
 * An Operation is wrapped by two workers, this is the finishing one
 * Mainly this resets the state of the task & triggers an operation event
 *
 */
class editor_Task_Operation_FinishingWorker extends editor_Models_Task_AbstractWorker {
    
    /**
     * This defines the ongoing operation
     * @var string
     */
    private $operationType;
    /**
     * Defines the initial state of the task before the operation was started
     * @var string
     */
    private $taskInitialState;
    
    protected function validateParameters($parameters = array()) {
        // required param steers the way the segments are processed: either directly or via the LEK_segment_tags
        if(array_key_exists('operationType', $parameters) && array_key_exists('taskInitialState', $parameters)){
            $this->operationType = $parameters['operationType'];
            $this->taskInitialState = $parameters['taskInitialState'];
            return true;
        }
        return false;
    }
    
    protected function work(){        

        $events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        /* @var $events ZfExtended_EventManager */
        $events->trigger("operationFinished", $this, array('operationType' => $this->operationType, 'task' => $this->task));
        
        $this->task->setState($this->taskInitialState);
        $this->task->save();
        
        return true;
    }
}
