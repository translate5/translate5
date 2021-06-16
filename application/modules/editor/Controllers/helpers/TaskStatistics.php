<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Helper to generate task statistics for the TaskController
 */
class Editor_Controller_Helper_TaskStatistics extends Zend_Controller_Action_Helper_Abstract {
    /**
     * Get the wokflow progress summary data. The return layout example:
     *   [
     *     {workflowStep: translation, status: finished, progress: 100},
     *     {workflowStep: reviewing, status: running, progress: 33},
     *     {workflowStep: translatorCheck, status: open, progress: 0}
     *   ]
     * @param editor_Models_Task $task
     * @return array
     */
    public function getWorkflowProgressSummary(editor_Models_Task $task): array {
        $workflowProgress = [];
        //when we are in import we may not produce statistics, the statistic creation would start to fill up the MV,
        // which is not possible since the import is running in a different worker and they would lock each other.
        if($task->isImporting()) {
            return $workflowProgress;
        }
        
        $autoStates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $autoStates editor_Models_Segment_AutoStates */
        $segmentProcessingSummary = $autoStates->getStatistics($task->getTaskGuid());
        
        $workflow=$task->getTaskActiveWorkflow();
        $stateMap = $autoStates->getRoleToStateMap();
        
        $steps = $workflow->getStepChain();
        $missingSteps = array_diff(array_values($workflow->getSteps()), $steps);
        //now we have all steps in the order of the stepchain + all remaining steps not in the chain added to the end
        $steps = array_merge($steps, $missingSteps);
        
        $taskStepIndex = array_search($task->getWorkflowStepName(),$steps);
        $totalSegmentCount=$task->getSegmentCount() ?? 0;
        //foreach task workflow chani step check calculate the workflow progress
        $currentStepIndex=0;
        foreach ($steps as $step) {
            $stepResult = new stdClass();
            $stepResult->workflowStep = $step;
            $stepResult->status = $workflow::STATE_OPEN;
            $stepResult->progress = 0;
            
            $stepResult->segmentWorkCount = [
                'edited'    => 0,
                'confirmed' => 0,
                'sum' => 0,
            ];
            $workflowProgress[] = $stepResult;
            $taskStateIsBefore = $taskStepIndex < $currentStepIndex;
            $taskStateIsAfter = $taskStepIndex > $currentStepIndex;

            //increment the step index
            $currentStepIndex++;
            
            $roleOfStep = $workflow->getRoleOfStep($step);

            //PM exception: the step value needed for PM states is just pm, while the step in the workflow is pmCheck
            if($step == $workflow::STEP_PM_CHECK && !$roleOfStep) {
                $roleOfStep = ACL_ROLE_PM;
            }
            
            $states = $stateMap[$roleOfStep] ?? false;
            
            if($states){
                //get all the segment states meaning editing a segment
                $editedStates = array_diff($states, $autoStates->getNotEditedStates());
                $stepResult->segmentWorkCount['edited'] = array_sum(array_intersect_key($segmentProcessingSummary, array_flip($editedStates)));

                //get all the segment states meaning just confirming a segment, without editing
                $confirmedStates = array_intersect($states, $autoStates->getNotEditedStates());
                $stepResult->segmentWorkCount['confirmed'] = array_sum(array_intersect_key($segmentProcessingSummary, array_flip($confirmedStates)));

                //sum up the segment counts of the auto-states to the current workflow step
                $stepResult->segmentWorkCount['sum'] = array_sum(array_intersect_key($segmentProcessingSummary, array_flip($states)));
            }
            
            //first we process the steps other then the current
            if($taskStateIsBefore){
                //we just continue, the OPEN defaults are already set
                continue;
            }
            if($taskStateIsAfter){
                //if the step is before the current step, the status is finish, otherwise open
                $stepResult->status   = $workflow::STATE_FINISH;
                $stepResult->progress = 100;
                continue;
            }
            
            $stepResult->status = $workflow::STATE_EDIT;
            if($stepResult->segmentWorkCount['sum'] > 0 && $totalSegmentCount > 0) {
                $stepResult->progress = round(($stepResult->segmentWorkCount['sum'] / $totalSegmentCount) * 100);
            }
        }
        
        return $workflowProgress;
    }
}
