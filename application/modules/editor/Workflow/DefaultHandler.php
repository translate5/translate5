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
 * Handler functions for the Default Workflow.
 * Default roles are:
 * - translator
 * - reviewer
 * - translatorCheck
 * - visitor
 * Default states are waiting, finished, open, edit, view and unconfirmed
 * Default states are waiting, finished, open, edit, view and unconfirmed
 * Basic steps (always available) are
 * - 'no workflow' as initial step
 * - pmCheck for PM usage
 * - workflowEnded as final step
 * All other steps are loaded from the database step configuration list
 */
class editor_Workflow_DefaultHandler {
    public function __construct() {
        $this->events->addIdentifiers(__CLASS__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleImport()
     */
    protected function handleImport(){
        $this->doDebug(__FUNCTION__);
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleBeforeImport()
     */
    protected function handleBeforeImport(){
        $this->doDebug(__FUNCTION__);
        $this->initWorkflowStep($this->newTask, self::STEP_NO_WORKFLOW);
        $this->newTask->load($this->newTask->getId()); //reload task with new workflowStepName and new calculated workflowStepNr
        $this->callActions(__FUNCTION__, self::STEP_NO_WORKFLOW);
    }

    /**
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleImportCompleted()
     */
    protected function handleImportCompleted(){
        $this->doDebug(__FUNCTION__);
        $this->callActions(__FUNCTION__);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleAllFinishOfARole()
     */
    protected function handleAllFinishOfARole(array $finishStat) {
        $newTua = $this->newTaskUserAssoc;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //this remains as default behaviour
        $nextStep = $newTua->getWorkflowStepName();
        $this->doDebug(__FUNCTION__." Next Step: ".$nextStep.' to role '.$newTua->getRole().' with step '.$nextStep."; Old Step in Task: ".$oldStep);
        if($nextStep) {
            //Next step triggert ebenfalls eine callAction → aber irgendwie so, dass der neue Wert verwendet wird! Henne Ei!
            $this->setNextStep($task, $nextStep);
            $nextRole = $this->getRoleOfStep($nextStep);
            $this->doDebug(__FUNCTION__." Next Role: ".$nextRole);
            if($nextRole) {
                $isComp = $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE;
                $newTua->setStateForRoleAndTask($isComp ? self::STATE_UNCONFIRMED : self::STATE_OPEN, $nextRole);
            }
        }
        
        //provide here oldStep, since this was the triggering one. The new step is given to handleNextStep trigger
        $this->callActions(__FUNCTION__, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleFinish()
     */
    protected function handleFinish(array $finishStat) {
        $this->doDebug(__FUNCTION__);
        $newTua = $this->newTaskUserAssoc;
        $taskGuid = $newTua->getTaskGuid();
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $oldStep = $task->getWorkflowStepName();
        
        //set the finished date when the user finishes a role
        if($newTua->getState()==editor_Workflow_Abstract::STATE_FINISH){
            $newTua->setFinishedDate(NOW_ISO);
            $newTua->save();
        }
        
        $this->callActions(__FUNCTION__, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleAllFinish()
     */
    protected function handleAllFinish(array $finishStat) {
        $this->doDebug(__FUNCTION__);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleEnd()
     */
    protected function handleEnd() {
        $this->newTask->dropMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleFirstFinishOfARole()
     */
    protected function handleFirstFinishOfARole(array $finishStat){
        $taskState = $this->newTask->getState();
        if($taskState == editor_Models_Task::STATE_UNCONFIRMED) {
            //we have to confirm the task and retrigger task workflow triggers
            // if task was unconfirmed but a lektor is set to finish, this implies confirming
            $oldTask = clone $this->newTask;
            $this->newTask->setState(editor_Models_Task::STATE_OPEN);
            $this->doWithTask($oldTask, $this->newTask);
            $this->newTask->save();
            $this->newTask->setState(editor_Models_Task::STATE_OPEN);
        }
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleFirstFinish()
     */
    protected function handleFirstFinish(array $finishStat){
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * reopen an ended task (task-specific reopening in contrast to taskassoc-specific unfinish)
     *
     * @see editor_Workflow_Abstract::handleReopen()
     */
    protected function handleReopen(){
        $this->newTask->createMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * unfinish a finished task (taskassoc-specific unfinish in contrast to task-specific reopening)
     * Set all REVIEWED_UNTOUCHED segments to TRANSLATED
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleUnfinish()
     */
    protected function handleUnfinish(){
        $this->doDebug(__FUNCTION__);
        $newTua = $this->newTaskUserAssoc;
        /* @var $actions editor_Workflow_Actions */
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $newTua->getRole(), $newTua->getState());
    }
    
        
    /**
     * checks the delivery dates, if a task is overdue, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     * (non-PHPdoc)
     * @see editor_Workflow_Abstract::handleCronDaily()
     */
    public function doCronDaily() {
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(__FUNCTION__);
    }
    
    /***
     *
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::doCronPeriodical()
     */
    public function doCronPeriodical(){
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleUserAssociationAdded()
     */
    protected function handleUserAssociationAdded() {
        $this->doDebug(__FUNCTION__);
        $tua = $this->newTaskUserAssoc;
        if(empty($this->newTask)) {
            $this->newTask = ZfExtended_Factory::get('editor_Models_Task');
            $this->newTask->loadByTaskGuid($tua->getTaskGuid());
        }
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
    }
    
    /***
     *
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleUserAssociationEdited()
     */
    protected function handleUserAssociationEdited(){
        $this->doDebug(__FUNCTION__);
        $tua = $this->newTaskUserAssoc;
        if(empty($this->newTask)) {
            $this->newTask = ZfExtended_Factory::get('editor_Models_Task');
            $this->newTask->loadByTaskGuid($tua->getTaskGuid());
        }
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
    }
    
    
    /**
     * {@inheritDoc}
     * @see editor_Workflow_Abstract::handleUserAssociationDeleted()
     */
    protected function handleUserAssociationDeleted() {
        $this->doDebug(__FUNCTION__);
        $tua = $this->newTaskUserAssoc;
        if(empty($this->newTask)) {
            $this->newTask = ZfExtended_Factory::get('editor_Models_Task');
            $this->newTask->loadByTaskGuid($tua->getTaskGuid());
        }
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $tua->getRole(), $tua->getState());
    }
}