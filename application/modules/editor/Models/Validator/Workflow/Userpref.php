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

class editor_Models_Validator_Workflow_Userpref extends ZfExtended_Models_Validator_Abstract {
    /**
     * the taskGuid of the userPref to be validated
     * @var string
     */
    protected $taskGuid;
    
    /**
     * set the taskGuid to load the segment fields
     * @param string $taskGuid
     */
    public function setTaskGuid($taskGuid) {
        $this->taskGuid = $taskGuid;
    }
    
    /**
     * Validators for Segment Entity
     * Validation will be done on calling entity->validate
     */
    protected function defineValidators() {
        $this->addValidator('id', 'int');
        $this->addValidator('taskGuid', 'guid');
        $this->addValidator('notEditContent', 'boolean');
        $this->addValidator('anonymousCols', 'boolean');
        $visibilities = array(
            editor_Models_Workflow_Userpref::VIS_SHOW,
            editor_Models_Workflow_Userpref::VIS_HIDE,
            editor_Models_Workflow_Userpref::VIS_DISABLE,
        );
        $this->addValidator('visibility', 'inArray', array($visibilities));
        $this->addValidator('userGuid', 'guid', array('allowEmpty' => true));
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $this->addValidator('workflow', 'inArray', [$wfm->getWorkflows()]);
        $this->addSegmentFieldValidator();
        $this->addWorkflowStepValidator();
        $this->addValidator('taskUserAssocId', 'int',[],true);
    }

    /**
     * run time validator for the segment fields
     */
    protected function addSegmentFieldValidator() {
        $taskGuid = &$this->taskGuid;
        $fieldValidator = function($value) use (&$taskGuid) {
            $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($taskGuid);
            /* @var $sfm editor_Models_SegmentFieldManager */
            $validTargets = array();
            $givenTargets = explode(',', $value);
            $allTargets = $sfm->getFieldList();
            foreach ($allTargets as $target) {
                $validTargets[] = $target->name;
            }
            $invalid = array_diff($givenTargets, $validTargets);
            return empty($invalid);
        };
        $this->addValidatorCustom('fields', $fieldValidator);
    }
    
    /**
     * runtime validator for the workflow steps
     */
    protected function addWorkflowStepValidator() {
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $taskGuid = &$this->taskGuid;
        $stepValidator = function($value) use (&$wfm, &$taskGuid) {
            if(empty($value)) {
                return true; //an empty workflowstep is valid
            }
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            $workflow = $wfm->getByTask($task);
            return in_array($value, $workflow->getSteps());
        };
        $this->addValidatorCustom('workflowStep', $stepValidator);
    }
}