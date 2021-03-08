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
 * This Worker reopens the task after the import was successful
 */
class editor_Models_Import_Worker_SetTaskToOpen extends editor_Models_Task_AbstractWorker {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(empty($parameters['config']) || !$parameters['config'] instanceof editor_Models_Import_Configuration) {
            throw new ZfExtended_Exception('missing or wrong parameter config, must be if instance editor_Models_Import_Configuration');
        }
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        if ($task->getState() != $task::STATE_IMPORT) {
            return false;
        }
        
        $task->setState($this->getInitialTaskState($task));
        $task->save();
        $task->unlock();
        
        return true;
    }
    
    /**
     * returns the initial status for a task directly after import
     * see config runtimeOptions.import.initialTaskState;
     * @param editor_Models_Task $task
     * @return string
     */
    protected function getInitialTaskState(editor_Models_Task $task) {
        $config = $task->getConfig();
        $status = $config->runtimeOptions->import->initialTaskState;
        $reflection = new ReflectionObject($task);
        $constants = $reflection->getConstants();
        if(!in_array($status, $constants)) {
            throw new ZfExtended_Exception('The configured initialTaskState is not valid! state: '.$status.'; valid states:'.print_r($constants,1));
        }
        return $status;
    }
}