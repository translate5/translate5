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
 */
class editor_Workflow_DefaultHandler {
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;
    
    public function __construct(editor_Workflow_Default $workflow) {
        $this->workflow = $workflow;
        $this->events->addIdentifiers(get_class($workflow));
    }
    
    /**
     * will be called after task import, the imported task is available in $this->newTask
     */
    protected function handleImport(){
        $this->doDebug(__FUNCTION__);
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * will be called directly before import is started, task is already created and available
     */
    protected function handleBeforeImport(){
        $this->doDebug(__FUNCTION__);
        $this->initWorkflowStep($this->newTask, self::STEP_NO_WORKFLOW);
        $this->newTask->load($this->newTask->getId()); //reload task with new workflowStepName and new calculated workflowStepNr
        $this->callActions(__FUNCTION__, self::STEP_NO_WORKFLOW);
    }

    /**
     * will be called after import (in set task to open worker) after the task is opened and the import is complete.
     */
    protected function handleImportCompleted(){
        $this->doDebug(__FUNCTION__);
        $this->callActions(__FUNCTION__);
    }

    
    /**
     * will be called after all users of a role has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
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
     * will be called after a user has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
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
        if($newTua->getState()==editor_Workflow_Default::STATE_FINISH){
            $newTua->setFinishedDate(NOW_ISO);
            $newTua->save();
        }
        
        $this->callActions(__FUNCTION__, $oldStep, $newTua->getRole(), $newTua->getState());
    }
    
    /**
     * will be called after all associated users of a task has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleAllFinish(array $finishStat) {
        $this->doDebug(__FUNCTION__);
    }

    /**
     * will be called after a task has been ended
     */
    protected function handleEnd() {
        $this->newTask->dropMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * will be called after first user of a role has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
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
     * will be called after a user has finished a task
     * @param array $finishStat contains the info which of all different finishes are applicable
     */
    protected function handleFirstFinish(array $finishStat){
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * reopen an ended task (task-specific reopening in contrast to taskassoc-specific unfinish)
     * will be called after a task has been reopened (after was ended - task-specific)
     */
    protected function handleReopen(){
        $this->newTask->createMaterializedView();
        $this->doDebug(__FUNCTION__);
    }
    
    /**
     * unfinish a finished task (taskassoc-specific unfinish in contrast to task-specific reopening)
     * Set all REVIEWED_UNTOUCHED segments to TRANSLATED
     * will be called after a task has been unfinished (after was finished - taskassoc-specific)
     */
    protected function handleUnfinish(){
        $this->doDebug(__FUNCTION__);
        $newTua = $this->newTaskUserAssoc;
        /* @var $actions editor_Workflow_Actions */
        $this->callActions(__FUNCTION__, $this->newTask->getWorkflowStepName(), $newTua->getRole(), $newTua->getState());
    }
    
        
    /**
     * checks the delivery dates, if a task is overdue, it'll be finished for all lectors, triggers normal workflow handlers if needed.
     * will be called daily
     */
    public function doCronDaily() {
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * will be called periodically between every 5 to 15 minutes, depending on the traffic on the installation.
     */
    public function doCronPeriodical(){
        $this->isCron = true;
        //no info about tasks, tuas are possible in cron call, so set nothing here
        $this->callActions(__FUNCTION__);
    }
    
    /**
     * will be called when a new task user association is created
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
    
    /**
     * will be called when a new task user association is edited
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
     * will be called when a task user association is deleted
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
    
    /**
     * manipulates the segment as needed by workflow after updated by user
     * @param editor_Models_Segment $segmentToSave
     * @param editor_Models_Task $task
     */
    public function beforeSegmentSave(editor_Models_Segment $segmentToSave, editor_Models_Task $task) {
        $updateAutoStates = function(editor_Models_Segment_AutoStates $autostates, editor_Models_Segment $segment, $tua) {
            //sets the calculated autoStateId
            $oldAutoState = $segment->getAutoStateId();
            $newAutoState = $autostates->calculateSegmentState($segment, $tua);
            $isChanged = $oldAutoState != $newAutoState;
            
            //if a segment with PRETRANS_INITIAL is saved by a translator, it is confirmed by setting it to PRETRANS_TRANSLATED
            // this is needed to restore the auto_state later in things like segmentsSetInitialState
            if($segment->getPretrans() == $segment::PRETRANS_INITIAL && $autostates->isTranslationState($newAutoState) && $isChanged) {
                $segment->setPretrans($segment::PRETRANS_TRANSLATED);
            }
            $segment->setAutoStateId($newAutoState);
        };
        $this->commonBeforeSegmentSave($segmentToSave, $updateAutoStates, $task);
    }
    
    /**
     * manipulates the segment as needed by workflow after user has add or edit a comment of the segment
     * @param editor_Models_Segment $segmentToSave
     * @param editor_Models_Task $task
     */
    public function beforeCommentedSegmentSave(editor_Models_Segment $segmentToSave, editor_Models_Task $task) {
        //FIXME wie soll das überarbeitet werden, wie passt das mit translator überhaupt?
        $updateAutoStates = function(editor_Models_Segment_AutoStates $autostates, editor_Models_Segment $segment, $tua) {
            $autostates->updateAfterCommented($segment, $tua);
        };
        $this->commonBeforeSegmentSave($segmentToSave, $updateAutoStates, $task);
    }
    
    /**
     * internal used method containing all common logic happend on a segment before saving it
     * @param editor_Models_Segment $segmentToSave
     * @param Closure $updateStates
     * @param editor_Models_Task $task
     */
    protected function commonBeforeSegmentSave(editor_Models_Segment $segmentToSave, Closure $updateStates, editor_Models_Task $task) {
        $sessionUser = new Zend_Session_Namespace('user');
        
        //we assume that on editing a segment, every user (also not associated pms) have a assoc, so no notFound must be handled
        $tua =editor_Models_Loaders_Taskuserassoc::loadByTask($sessionUser->data->userGuid, $task);
        if($tua->getIsPmOverride() == 1){
            $segmentToSave->setWorkflowStepNr($task->getWorkflowStep()); //set also the number to identify in which phase the changes were done
            $segmentToSave->setWorkflowStep(self::STEP_PM_CHECK);
        }
        else {
            //sets the actual workflow step
            $segmentToSave->setWorkflowStepNr($task->getWorkflowStep());
            
            //sets the actual workflow step name, does currently depend only on the userTaskRole!
            $segmentToSave->setWorkflowStep($tua->getWorkflowStepName());
        }
        
        $autostates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        
        //set the autostate as defined in the given Closure
        /* @var $autostates editor_Models_Segment_AutoStates */
        $updateStates($autostates, $segmentToSave, $tua);
    }
}