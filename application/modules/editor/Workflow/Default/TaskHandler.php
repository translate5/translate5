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
 * Handler methods for task hookins
 */
class editor_Workflow_Default_TaskHandler extends editor_Workflow_Default_AbstractHandler {
    const HANDLE_TASK_CHANGE = 'handleTaskChange';
    const HANDLE_TASK_REOPEN = 'doReopen';
    const HANDLE_TASK_CONFIRM = 'doTaskConfirm';
    const HANDLE_TASK_END = 'doEnd';
    
    public function execute(editor_Workflow_Actions_Config $actionConfig): ?string {
        $this->config = $actionConfig;
        $actionConfig->trigger = $this->calculateHandler();
        
        if(empty($actionConfig->trigger)) {
            return null;
        }
        
        $tasks = ['oldTask' => $actionConfig->oldTask, 'newTask' => $actionConfig->task];
        $this->doDebug($actionConfig->trigger);
        
        /**
         * reopen an ended task (task-specific reopening in contrast to taskassoc-specific unfinish)
         * will be called after a task has been reopened (after was ended - task-specific)
         */
        if($actionConfig->trigger == self::HANDLE_TASK_REOPEN) {
            $actionConfig->task->createMaterializedView();
        }
        
        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTask($actionConfig->authenticatedUser->getUserGuid(), $actionConfig->task);
            $this->callActions($actionConfig, $actionConfig->task->getWorkflowStepName(), $tua->getRole(), $tua->getState());
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->callActions($actionConfig, $actionConfig->task->getWorkflowStepName());
        }
        //FIXME did we have before triggers for tasks too before???
        $actionConfig->events->trigger($actionConfig->trigger, $actionConfig->workflow, $tasks);
        
        if($actionConfig->trigger == self::HANDLE_TASK_END) {
            $actionConfig->task->dropMaterializedView();
        }
        
        return $actionConfig->trigger;
    }
    
    /**
     * calculates the handler to be called
     * @return string|NULL
     */
    protected function calculateHandler(): ?string {
        $newState = $this->config->task->getState();
        $oldState = $this->config->oldTask->getState();
        
        if($newState == $oldState) {
            return self::HANDLE_TASK_CHANGE;
        }
        $handler = null;
        switch($newState) {
            case $this->config->task::STATE_OPEN:
                if($oldState == $this->config->task::STATE_END) {
                    $handler = self::HANDLE_TASK_REOPEN;
                    break;
                }
                if($oldState == $this->config->task::STATE_UNCONFIRMED) {
                    $handler = self::HANDLE_TASK_CONFIRM;
                }
                break;
                
            case $this->config->task::STATE_END:
                // is called on ending
                // will be called after a task has been ended
                $handler = self::HANDLE_TASK_END;
                break;
                
            case $this->config->task::STATE_UNCONFIRMED:
            default:
                //doing currently nothing
                break;
        }
        return $handler;
    }
    
    /**
     * debugging workflow
     * @param string $msg
     * @param array $data optional debuggin data
     * @param bool $levelInfo optional, if true log in level info instead debug
     */
    protected function doDebug($msg, array $data = [], $levelInfo = false) {
        $log = $this->config->workflow->getLogger($this->config->task);
        
        if($levelInfo) {
            $log->info('E1013', $msg, $data);
        }
        else {
            $log->debug('E1013', $msg, $data);
        }
    }
}