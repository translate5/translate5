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
 * This Worker is triggered after a task import, regardless if the task was successfully imported or the import did not succeed due errors.
 * Therefore this worker may not be a subclass of the editor_Models_Task_AbstractWorker
 */
class editor_Models_Import_Worker_FinalStep extends ZfExtended_Worker_Abstract {
    /**
     * Defines the behaviour class to be used for this worker
     * @var string
     */
    protected $behaviourClass = 'editor_Models_Import_Worker_FinalStepBehaviour';
    
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
        
        $workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $workflowManager editor_Workflow_Manager */
        //we have to initialize the workflow so that it can listen to further events (like importCompleted)
        $workflowManager->getByTask($task);
        
        // importCompleted is also triggered on task errors:
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('importCompleted', $this, [
            'task' => $task,
            'importConfig' => $this->workerModel->getParameters()['config'],
        ]);
        
        //init default user prefs
        return true;
    }
}