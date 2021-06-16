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

class editor_Models_Validator_TaskUserAssoc extends ZfExtended_Models_Validator_Abstract {

    /**
     * Validators for Task User Assoc Entity
     */
    protected function defineValidators() {
        // for task user association validations we have to load the desired workflow for validation, therefore we do nothing here but add the validators on each is valid call
    }
    
    public function isValid(array $data) {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($data['taskGuid']);
        /* @var $workflow editor_Workflow_Default */
        $this->addValidator('id', 'int');
        $this->addValidator('taskGuid', 'guid');
        $this->addValidator('userGuid', 'guid');
        
        //remove status finish from the valid states on job creation
        $id = $this->entity->getId();
        $states = $workflow->getStates();
        if(empty($id)) {
            $states = array_diff($states, [$workflow::STATE_FINISH]);
        }
        $this->addValidator('state', 'inArray', array($states));
        
        $this->addValidator('role', 'inArray', array($workflow->getRoles()));
        $this->addValidator('workflowStepName', 'inArray', array($workflow->getSteps()));
        $this->addValidator('segmentrange', 'stringLength', array('min' => 0, 'max' => 255));
        $this->addValidator('usedState', 'inArray', array($workflow->getStates()));
        $this->addValidator('usedInternalSessionUniqId', 'stringLength', array('min' => 0, 'max' => 32));
        $this->addValidator('isPmOverride', 'boolean');
        $this->addValidator('assignmentDate', 'date', array('Y-m-d H:i:s'),true);
        $this->addValidator('finishedDate', 'date', array('Y-m-d H:i:s'),true);
        $this->addValidator('deadlineDate', 'date', array('Y-m-d H:i:s'),true);
        
        return parent::isValid($data);
    }
}
