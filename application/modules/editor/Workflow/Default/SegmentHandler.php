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
 * Handler functions for the Default Workflow (restricted to segment handling).
 */
class editor_Workflow_Default_SegmentHandler {
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;

    /**
     * User to be used when loading the tua for the edited task | null if not authenticated
     * @var string|null
     */
    private ?string $userGuid = null;

    public function __construct(editor_Workflow_Default $workflow) {
        $this->workflow = $workflow;
        if(ZfExtended_Authentication::getInstance()->isAuthenticated()) {
            $this->userGuid = ZfExtended_Authentication::getInstance()->getUser()->getUserGuid();
        }
    }

    /***
     * Change the current userGuid used for loading the tua for the edited task
     * @param string $newUserGuid
     * @return void
     */
    public function updateUserGuid(string $newUserGuid): void
    {
        $this->userGuid = $newUserGuid;
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

        //we assume that on editing a segment, every user (also not associated pms) have a assoc, so no notFound must be handled
        $tua = editor_Models_Loaders_Taskuserassoc::loadByTask($this->userGuid, $task);
        if($tua->getIsPmOverride() == 1){
            $segmentToSave->setWorkflowStepNr($task->getWorkflowStep()); //set also the number to identify in which phase the changes were done
            $segmentToSave->setWorkflowStep($this->workflow::STEP_PM_CHECK);
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