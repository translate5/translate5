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
 * Handler methods for task hookins
 */
class editor_Workflow_Default_JobHandler_Finish extends editor_Workflow_Default_AbstractHandler {
    
    const HANDLE_JOB_FINISH             = 'handleFinish';
    const HANDLE_JOB_FIRSTFINISH        = 'handleFirstFinish';
    const HANDLE_JOB_FIRSTFINISHOFAROLE = 'handleFirstFinishOfARole';
    const HANDLE_JOB_ALLFINISH          = 'handleAllFinish';
    const HANDLE_JOB_ALLFINISHOFAROLE   = 'handleAllFinishOfARole';
    
    const HANDLE_TASK_SETNEXTSTEP       = 'handleSetNextStep';
    
    /**
     * @param editor_Workflow_Actions_Config $actionConfig
     */
    public function execute(editor_Workflow_Actions_Config $actionConfig): ?string {
        $this->config = $actionConfig;
        $stat = $this->calculateFinish();
        $this->doDebug(__CLASS__.'::'.print_r($stat,1));
        
        if($stat['roleFirstFinished']) {
            $this->handleFirstFinishOfARole();
        }
        if($stat['firstFinished']) {
            $this->handleFirstFinish();
        }
        if($stat['roleAllFinished']) {
            $this->handleAllFinishOfARole();
        }
        if($stat['allFinished']) {
            $this->handleAllFinish();
        }
        $this->handleFinish();
        return $this->config->trigger;
    }
    
    /**
     * trigger finish calculation after deleting a job (since the task may get finished after deleting the last opened job when all others are already finished)
     * @param editor_Workflow_Actions_Config $actionConfig
     */
    public function executeOnDeleteJob(editor_Workflow_Actions_Config $actionConfig): ?string {
        $this->config = $actionConfig;
        $originalState = $actionConfig->newTua->getState();
        //if the deleted tua was not finished, we have to recheck the allFinished events after deleting it!
        $wasNotFinished = ($originalState !== $this->config->workflow::STATE_FINISH);
        
        $stat = $this->calculateFinish();
        $this->doDebug(__FUNCTION__. ' OriginalState: '.$originalState.'; Finish Stat: '.print_r($stat,1));
        if($wasNotFinished && $stat['roleAllFinished']) {
            //in order to trigger the actions correctly we have to assume that the deleted one was "finished"
            $actionConfig->newTua->setState($this->config->workflow::STATE_FINISH);
            $this->handleAllFinishOfARole();
        }
        if($wasNotFinished && $stat['allFinished']) {
            //in order to trigger the actions correctly we have to assume that the deleted one was "finished"
            $actionConfig->newTua->setState($this->config->workflow::STATE_FINISH);
            $this->handleAllFinish();
        }
        $actionConfig->newTua->setState($originalState);
        return $this->config->trigger;
    }
    
    /**
     * calculates which of the "finish" handlers can be called accordingly to the currently existing tuas of a task
     * @return boolean[]
     */
    protected function calculateFinish() {
        $userTaskAssoc = $this->config->newTua;
        $stat = $userTaskAssoc->getUsageStat();
        //we have to initialize $allFinished with true for proper working but with false if there is no tua at all
        $allFinished = !empty($stat);
        
        //we have to initialize $roleAllFinished with true for proper working but with false if there is no tua with the current tuas role
        $usedRoles = array_column($stat, 'role');
        $roleAllFinished = in_array($userTaskAssoc->getRole(), $usedRoles);
        $roleFirstFinished = false;
        $sum = 0;
        foreach($stat as $entry) {
            $isRole = $entry['role'] === $userTaskAssoc->getRole();
            $isFinish = $entry['state'] === $this->config->workflow::STATE_FINISH;
            if($isRole && $roleAllFinished && ! $isFinish) {
                $roleAllFinished = false;
            }
            if($allFinished && ! $isFinish) {
                $allFinished = false;
            }
            if($isRole && $isFinish && (int)$entry['cnt'] === 1) {
                $roleFirstFinished = true;
            }
            if($isFinish) {
                $sum += (int)$entry['cnt'];
            }
        }
        return [
            'allFinished' => $allFinished,
            'roleAllFinished' => $roleAllFinished,
            'roleFirstFinished' => $roleFirstFinished,
            'firstFinished' => $sum === 1,
        ];
    }
    
    /**
     * will be called after all users of a role has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleAllFinishOfARole() {
        $newTua = $this->config->newTua;
        $this->config->trigger = self::HANDLE_JOB_ALLFINISHOFAROLE;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //this remains as default behaviour
        $nextStep = $this->config->workflow->getNextStep($newTua->getWorkflowStepName());
        $this->doDebug($this->config->trigger." Next Step: ".$nextStep.' to role '.$newTua->getRole().' with step '.$nextStep."; Old Step in Task: ".$oldStep);
        if($nextStep) {
            //Next step triggert ebenfalls eine callAction → aber irgendwie so, dass der neue Wert verwendet wird! Henne Ei!
            $this->setNextStep($task, $nextStep);
            $isComp = $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE;
            $newTua->setStateForStepAndTask($isComp ? $this->config->workflow::STATE_UNCONFIRMED : $this->config->workflow::STATE_OPEN, $nextStep);
        }
        
        //provide here oldStep, since this was the triggering one. The new step is given to handleNextStep trigger
        $this->callActions($this->config, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * will be called after a user has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFinish() {
        $this->doDebug(self::HANDLE_JOB_FINISH);
        $this->config->trigger = self::HANDLE_JOB_FINISH;
        $newTua = $this->config->newTua;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //set the finished date when the user finishes a role
        if($newTua->getState() == $this->config->workflow::STATE_FINISH){
            $newTua->setFinishedDate(NOW_ISO);
            $newTua->save();
        }
        
        $this->callActions($this->config, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * will be called after all associated users of a task has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleAllFinish() {
        $this->doDebug(self::HANDLE_JOB_ALLFINISH);
    }
    
    /**
     * will be called after first user of a role has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFirstFinishOfARole(){
        $task = $this->config->task;
        $taskState = $task->getState();
        if($taskState == $task::STATE_UNCONFIRMED) {
            //we have to confirm the task and retrigger task workflow triggers
            // if task was unconfirmed but a lektor is set to finish, this implies confirming
            $this->config->oldTask = clone $task;
            $task->setState($task::STATE_OPEN);
            
            /* @var $taskHandler editor_Workflow_Default_TaskHandler */
            $taskHandler = ZfExtended_Factory::get('editor_Workflow_Default_TaskHandler');
            $taskHandler->execute($this->config);
            
            $task->save();
            $task->setState($task::STATE_OPEN);
        }
        $this->doDebug(self::HANDLE_JOB_FIRSTFINISHOFAROLE);
    }
    
    /**
     * will be called after a user has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFirstFinish(){
        $this->doDebug(self::HANDLE_JOB_FIRSTFINISH);
    }
    
    /**
     * Sets the new workflow step in the given task and increases by default the workflow step nr
     * @param editor_Models_Task $task
     * @param string $stepName
     */
    protected function setNextStep(editor_Models_Task $task, string $stepName) {
        //store the nextStepWasSet per taskGuid,
        // so this mechanism works also when looping over different tasks with the same workflow instance
        $steps = [
            'oldStep' => $task->getWorkflowStepName(),
            'newStep' => $stepName,
        ];
        $this->config->workflow->getStepRecalculation()->addNextStepSet($task->getTaskGuid(), $stepName);
        $this->doDebug(__FUNCTION__.': workflow next step "{newStep}"; oldstep: "{oldStep}"', $steps, true);
        $task->updateWorkflowStep($stepName, true);
        //call action directly without separate handler method
        $newTua = $this->config->newTua;
        $config = clone $this->config;
        $config->trigger = self::HANDLE_TASK_SETNEXTSTEP;
        $this->callActions($config, $stepName, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * debugging workflow
     * @param string $msg
     * @param array $data optional debuggin data
     * @param bool $levelInfo optional, if true log in level info instead debug
     */
    protected function doDebug($msg, array $data = [], $levelInfo = false) {
        $log = $this->config->workflow->getLogger($this->config->task);
        
        //add the job / tua
        if(!empty($this->config->newTua)) {
            $data['job'] = $this->config->newTua;
        }
        if($levelInfo) {
            $log->info('E1013', $msg, $data);
        }
        else {
            $log->debug('E1013', $msg, $data);
        }
    }
}