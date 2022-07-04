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
class editor_Workflow_Default_JobHandler extends editor_Workflow_Default_AbstractHandler {
    const HANDLE_JOB_UNFINISH            = 'doUnfinish';
    
    const HANDLE_JOB_FIRSTCONFIRMOFASTEP = 'handleFirstConfirmOfAStep';
    const HANDLE_JOB_FIRSTCONFIRM        = 'handleFirstConfirm';
    const HANDLE_JOB_ALLCONFIRMOFASTEP   = 'handleAllConfirmOfAStep';
    const HANDLE_JOB_ALLCONFIRM          = 'handleAllConfirm';
    const HANDLE_JOB_CONFIRM             = 'handleConfirm';
    
    const HANDLE_JOB_ADD                 = 'handleUserAssociationAdded';
    const HANDLE_JOB_DELETE              = 'handleUserAssociationDeleted';
    const HANDLE_JOB_EDITED              = 'handleUserAssociationEdited';
    
    /**
     * {@inheritDoc}
     * @see editor_Workflow_Default_AbstractHandler::execute()
     */
    public function execute(editor_Workflow_Actions_Config $actionConfig): ?string {
        $this->config = $actionConfig;
        
        if($actionConfig == self::HANDLE_JOB_DELETE) {
            $tua = $actionConfig->newTua;
            $actionConfig->task = ZfExtended_Factory::get('editor_Models_Task');
            $actionConfig->task->loadByTaskGuid($tua->getTaskGuid());
            
            /* @var $finishHandler editor_Workflow_Default_JobHandler_Finish */
            $finishHandler = ZfExtended_Factory::get('editor_Workflow_Default_JobHandler_Finish');
            $finishHandler->executeOnDeleteJob($actionConfig);
        }
        $this->handleUserAssociationChanged();
        return $actionConfig->trigger;
    }
    
    /**
     * Executes the Job Handler with a callback where the job can be saved outside
     * @param editor_Workflow_Actions_Config $actionConfig
     * @param callable $saveCallback
     * @return string|NULL
     */
    public function executeSave(editor_Workflow_Actions_Config $actionConfig, callable $saveCallback): ?string {
        $this->config = $actionConfig;
        
        $state = $this->getTriggeredState($actionConfig->oldTua, $actionConfig->newTua, 'before');
        $actionConfig->events->trigger($state, $actionConfig->workflow, ['oldTua' => $actionConfig->oldTua, 'newTua' => $actionConfig->newTua]);
        
        //call here stuff which must be done between the before trigger and the other code (normally saving the TUA)
        $saveCallback();
        
        //ensure that segment MV is createad
        $actionConfig->task->createMaterializedView();
        $state = $this->getTriggeredState($actionConfig->oldTua, $actionConfig->newTua);
        $this->doDebug($state && 'No state was given on job handler execute save.');
        if(!empty($state)) {
            if(method_exists($this, $state)) {
                $this->{$state}();
            }
            $actionConfig->events->trigger($state, $actionConfig->workflow, ['oldTua' => $actionConfig->oldTua, 'newTua' => $actionConfig->newTua, 'task' => $actionConfig->task]);
        }
        
        //finally set the trigger to edited and call the job changed handler
        $actionConfig->trigger = self::HANDLE_JOB_EDITED;
        $this->handleUserAssociationChanged();
        $actionConfig->workflow->getStepRecalculation()->recalculateWorkflowStep($actionConfig->newTua);
        return $actionConfig->trigger;
    }
    
    /**
     * will be called when a new task user association is created
     */
    protected function handleUserAssociationChanged() {
        $this->doDebug($this->config->trigger);
        if(empty($this->config->task)) {
            $this->config->task = ZfExtended_Factory::get('editor_Models_Task');
            $this->config->task->loadByTaskGuid($this->config->newTua->getTaskGuid());
        }
        $this->callActions($this->config, $this->config->task->getWorkflowStepName(), $this->config->newTua->getRole(), $this->config->newTua->getState());
    }
    
    /**
     * method returns the triggered state as string ready to use in events, these are mainly:
     * doUnfinish, doView, doEdit, doFinish, doWait, doConfirm
     * beforeUnfinish, beforeView, beforeEdit, beforeFinish, beforeWait, beforeConfirm
     *
     * @param editor_Models_TaskUserAssoc $oldTua
     * @param editor_Models_TaskUserAssoc $newTua
     * @param string $prefix optional, defaults to "do"
     * @return string
     */
    protected function getTriggeredState(editor_Models_TaskUserAssoc $oldTua, editor_Models_TaskUserAssoc $newTua, $prefix = 'do') {
        $oldState = $oldTua->getState();
        $newState = $newTua->getState();
        $workflow = $this->config->workflow;
        if($oldState == $newState) {
            return null;
        }
        
        if($oldState == $workflow::STATE_FINISH && $newState != $workflow::STATE_FINISH) {
            return $prefix.'Unfinish';
        }
        
        if($oldState == $workflow::STATE_UNCONFIRMED && $newState == $workflow::STATE_EDIT) {
            return $prefix.'Confirm';
        }
        
        $result = null;
        switch($newState) {
            case $workflow::STATE_OPEN:
                $result = $prefix.'Open';
                break;
            case $workflow::STATE_VIEW:
                $result = $prefix.'View';
                break;
            case $workflow::STATE_EDIT:
                $result = $prefix.'Edit';
                break;
            case $workflow::STATE_FINISH:
                $result = $prefix.'Finish';
                break;
            case $workflow::STATE_WAITING:
                $result = $prefix.'Wait';
                break;
            default:
                $result = null;
        }
        return $result;
    }
    
    /**
     * is called on finishin a task
     * evaluates the role and states of the User Task Association and calls the matching handlers:
     */
    protected function doFinish() {
        /* @var $finishHandler editor_Workflow_Default_JobHandler_Finish */
        $finishHandler = ZfExtended_Factory::get('editor_Workflow_Default_JobHandler_Finish');
        $finishHandler->execute($this->config);
    }
    
    /**
     * is called on reopening / unfinishing a task
     * unfinish a finished task (taskassoc-specific unfinish in contrast to task-specific reopening)
     * Set all REVIEWED_UNTOUCHED segments to TRANSLATED
     * will be called after a task has been unfinished (after was finished - taskassoc-specific)
     */
    protected function doUnfinish() {
        $this->config->trigger = self::HANDLE_JOB_UNFINISH;
        $this->doDebug($this->config->trigger);
        /* @var $actions editor_Workflow_Actions */
        $this->callActions($this->config, $this->config->task->getWorkflowStepName(), $this->config->newTua->getRole(), $this->config->newTua->getState());
    }
    
    /**
     * is called when a user confirms his job (job was unconfirmed and is set to edit)
     * No handler functions for confirm available, everything is handled via actions
     */
    protected function doConfirm() {
        $stat = $this->calculateConfirm();
        $this->doDebug(__FUNCTION__.print_r($stat,1));
        
        $toTrigger = [];
        if($stat['stepFirstConfirmed']) {
            $toTrigger[] = self::HANDLE_JOB_FIRSTCONFIRMOFASTEP;
        }
        if($stat['firstConfirmed']) {
            $toTrigger[] = self::HANDLE_JOB_FIRSTCONFIRM;
        }
        if($stat['stepAllConfirmed']) {
            $toTrigger[] = self::HANDLE_JOB_ALLCONFIRMOFASTEP;
        }
        if($stat['allConfirmed']) {
            $toTrigger[] = self::HANDLE_JOB_ALLCONFIRM;
        }
        $toTrigger[] = self::HANDLE_JOB_CONFIRM;
        
        $newTua = $this->config->newTua;
        $oldStep = $this->config->task->getWorkflowStepName();
        foreach($toTrigger as $trigger) {
            $this->doDebug($trigger);
            $this->config->trigger = $trigger;
            $this->callActions($this->config, $oldStep, $newTua->getRole(), $newTua->getState());
        }
    }
    
    /**
     * Calculates the workflow step confirmation status
     * Warning: this function may only be called in doConfirm (which is called if there was a state unconfirmed which is now set to edit)
     *  For all other usages the calculation will not be correct, since we don't know if a state was unconfirmed before,
     *  we see only that all states are now not unconfirmed.
     */
    protected function calculateConfirm() {
        $userTaskAssoc = $this->config->newTua;
        $stat = $userTaskAssoc->getUsageStat();
        $sum = 0;
        $stepSum = 0;
        $otherSum = 0;
        $stepUnconfirmedSum = 0;
        foreach($stat as $entry) {
            $sum += (int)$entry['cnt'];
            $isStep = $entry['workflowStepName'] === $userTaskAssoc->getWorkflowStepName();
            $isUnconfirmed = $entry['state'] === $this->config->workflow::STATE_UNCONFIRMED;
            if($isStep) {
                $stepSum += (int)$entry['cnt'];
                if($isUnconfirmed) {
                    $stepUnconfirmedSum += (int)$entry['cnt'];
                }
            }
            if(!$isUnconfirmed) {
                $otherSum += (int)$entry['cnt'];
            }
        }
        return [
            'allConfirmed' => $sum > 0 && $otherSum === $sum,
            'stepAllConfirmed' => $stepUnconfirmedSum === 0,
            'stepFirstConfirmed' => $stepSum - 1 === $stepUnconfirmedSum,
            //firstConfirmed is working only if really all other jobs are unconfirmed, what is seldom, since the other states will be waiting / finished etc.
            'firstConfirmed' => $otherSum === 1,
        ];
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