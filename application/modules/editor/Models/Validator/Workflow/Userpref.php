<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        $this->addValidator('anonymousCols', 'boolean');
        $this->addValidator('visibility', 'inArray', array(array('show', 'hide', 'disable')));
        $this->addValidator('userGuid', 'guid', array('allowEmpty' => true));
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $this->addValidator('workflow', 'inArray', array(array_keys($wfm->getWorkflows())));
        $this->addSegmentFieldValidator();
        $this->addWorkflowStepValidator();
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