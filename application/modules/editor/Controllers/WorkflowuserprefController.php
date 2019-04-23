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
 *
 */
class editor_WorkflowuserprefController extends ZfExtended_RestController {
    protected $entityClass = 'editor_Models_Workflow_Userpref';
    
    /**
     * @var editor_Models_Workflow_Userpref
     */
    protected $entity;
    
    /**
     * overridden to prepare data
     * (non-PHPdoc)
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
        parent::decodePutData();
        if($this->_request->isPost()) {
            unset($this->data->id); //don't set the ID from client side
            //a new default entry cannot be created, also a workflow step must always be set!
            if(empty($this->data->workflowStep) || empty($this->data->workflowStep) && empty($this->data->userGuid)) {
                throw new ZfExtended_ValidateException('Missing workflow step in given data');
            }
        }
        if($this->_request->isPut()) {
            //we cant update an existing userpref entry to workflow step = null,
            //since only the default entry can have an empty worflow step
            if(property_exists($this->data, 'workflowStep') && empty($this->data->workflowStep)) {
                throw new ZfExtended_ValidateException('Missing workflow step in given data');
            }
            if($this->entity->isDefault()) {
                unset($this->data->workflowStep); //don't update the workflowStep of the default entry
                unset($this->data->userGuid); //don't update the userGuid of the default entry
            }
        }
    }
    
    /**
     * deletes the UserPref entry, ensures that the default entry cannot be deleted by API!
     * (non-PHPdoc)
     * @see ZfExtended_RestController::deleteAction()
     */
    public function deleteAction() {
        $this->entity->load($this->_getParam('id'));
        if($this->entity->isDefault()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        $this->processClientReferenceVersion();
        $this->entity->delete();
    }
}