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
 * Workflow Step recalculation
 */
class editor_Workflow_Default_StepRecalculation {
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;
    
    protected $nextStepWasSet = [];
    
    public function __construct(editor_Workflow_Default $workflow) {
        $this->workflow = $workflow;
    }
    
    /**
     * Adds the next calculated step for a task (to be used in recalculation here)
     * @param string $taskGuid
     * @param string $newStep
     */
    public function addNextStepSet(string $taskGuid, string $newStep) {
        $this->nextStepWasSet[$taskGuid] = $newStep;
    }
    
    /**
     * recalculates the workflow step by the given task user assoc combinations
     * If the combination of roles and states are pointing to an specific workflow step, this step is used
     * If the states and roles does not match any valid combination, no step is changed.
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function recalculateWorkflowStep(editor_Models_TaskUserAssoc $tua) {
        $taskGuid = $tua->getTaskGuid();
        
        //if the step was recalculated due setNextStep in internal workflow calculations,
        // we may not recalculate it here again!
        if(!empty($this->nextStepWasSet[$taskGuid])) {
            $this->sendFrontEndNotice($this->nextStepWasSet[$taskGuid]);
            return;
        }
        
        $tuas = $tua->loadByTaskGuidList([$taskGuid]);
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $matchingSteps = [];
        $pmOvverideCount=0;
        foreach($tuas as $tua) {
            if($tua['isPmOverride']==1){
                $pmOvverideCount++;
            }
        }
        if(empty($tuas) && count($tuas) == $pmOvverideCount){
            $matchingSteps[]=$this->workflow::STEP_NO_WORKFLOW;
        }else{
            foreach($this->workflow->getValidStates() as $step => $roleStates) {
                if(!$this->areTuasSubset($roleStates, $step, $tuas)) {
                    continue;
                }
                $matchingSteps[] = $step;
            }
        }
        
        //if the current step is one of the possible steps for the tua configuration
        // then everything is OK,
        // or if no valid configuration is found, then we also could not change the step
        if(empty($matchingSteps) || in_array($task->getWorkflowStepName(), $matchingSteps)) {
            return;
        }
        //set the first found valid step to the current workflow step
        $step = reset($matchingSteps);
        $this->workflow->getLogger($task)->info('E1013', 'recalculate workflow to step {step} ', ['step' => $step]);
        $task->updateWorkflowStep($step, false);
        //set $step as new workflow step if different to before!
        $this->sendFrontEndNotice($step);
    }
    
    /**
     * Checks if the given Jobs (tuas) are a subset of the list be compared
     * @param array $toCompare
     * @param string $currentStep
     * @param array $tuas
     * @return bool
     */
    protected function areTuasSubset(array $toCompare, string $currentStep, array $tuas): bool {
        $hasStepToCurrentTaskStep = false;
        foreach($tuas as $tua) {
            if(empty($toCompare[$tua['workflowStepName']])) {
                return false;
            }
            if(!in_array($tua['state'], $toCompare[$tua['workflowStepName']])) {
                return false;
            }
            $hasStepToCurrentTaskStep = $hasStepToCurrentTaskStep || ($currentStep == $tua['workflowStepName']);
        }
        //we can only return true, if the Tuas contain at least one role belonging to the currentStep,
        // in other words we can not reset the task to reviewing, if we do not have a reviewer
        return $hasStepToCurrentTaskStep;
    }
    
    protected function sendFrontEndNotice(string $step) {
        $msg = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        /* @var $msg ZfExtended_Models_Messages */
        $labels = $this->workflow->getLabels();
        $steps = $this->workflow->getSteps();
        $step = $labels[array_search($step, $steps)];
        $msg->addNotice('Der Workflow Schritt der Aufgabe wurde zu "{0}" geändert!', 'core', null, $step);
    }
    
    public function initWorkflowStep(editor_Models_Task $task, $stepName) {
        $this->workflow->getLogger($task)->info('E1013', 'workflow init step to "{step}"', ['step' => $stepName]);
        $task->updateWorkflowStep($stepName, false);
    }

    /**
     * - cleans the not needed automatically added task user associations from the job list
     * - sets the tasks workflow step depending the associated jobs
     * - sets the initial states depending on the workflow step of the task and task usage mode
     * @param editor_Models_Task $task
     */
    public function setupInitialWorkflow(editor_Models_Task $task) {
        /* @var $job editor_Models_TaskUserAssoc */
        $job = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        $jobs = $job->loadByTaskGuidList([$task->getTaskGuid()]);
        
        $usedJobs = [];
        $usedSteps = [];
        //delete jobs created by default which are not belonging to the tasks workflow and collect used steps
        foreach($jobs as $rawJob) {
            if($rawJob['workflow'] === $task->getWorkflow()) {
                $usedJobs[] = $rawJob;
                $usedSteps[] = $rawJob['workflowStepName'];
            }
            else {
                $job->db->delete(['id = ?' => $rawJob['id']]);
            }
        }
        $task->updateTask();
        if(empty($usedJobs)) {
            return;
        }
        
        //sort the found steps regarding the step chain
        $usedSteps = array_unique($usedSteps);
        usort($usedSteps, [$this->workflow, 'compareSteps']);
        
        //we set the tasks workflow step to the first found step of the assigned users, respecting the order of the step chain
        $currentStep = array_shift($usedSteps);
        $task->updateWorkflowStep($currentStep, false);
        
        $isComp = $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE;
        foreach($usedJobs as $rawJob) {
            //currentstep jobs are open
            if($currentStep === $rawJob['workflowStepName']) {
                $state = $isComp ? $this->workflow::STATE_UNCONFIRMED : $this->workflow::STATE_OPEN;
            }
            else {
            //all other steps are coming later in the chain, so they are waiting
                $state = $this->workflow::STATE_WAITING;
            }
            $job->db->update(['state' => $state], ['id = ?' => $rawJob['id']]);
        }
    }
}