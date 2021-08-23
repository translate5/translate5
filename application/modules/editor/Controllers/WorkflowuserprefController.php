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
 *
 */
class editor_WorkflowuserprefController extends ZfExtended_RestController {
    protected $entityClass = 'editor_Models_Workflow_Userpref';
    
    /**
     * @var editor_Models_Workflow_Userpref
     */
    protected $entity;
    
    public function init() {
        ZfExtended_UnprocessableEntity::addCodes([
            'E1172' => 'The referenced user is not associated to the task or does event not exist anymore.',
            'E1205' => 'Missing workflow step in given data.',
            'E1206' => 'Missing workflow step in given data.',
        ], 'editor.workflow.userprefs');
        parent::init();
    }
    
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
                //should happen on API usage only, so no translation of the error here
                throw new ZfExtended_UnprocessableEntity('E1205');
            }
        }
        if($this->_request->isPut()) {
            //we cant update an existing userpref entry to workflow step = null,
            //since only the default entry can have an empty worflow step
            if(property_exists($this->data, 'workflowStep') && empty($this->data->workflowStep)) {
                //should happen on API usage only, so no translation of the error here
                throw new ZfExtended_UnprocessableEntity('E1206');
            }
            if($this->entity->isDefault()) {
                unset($this->data->workflowStep); //don't update the workflowStep of the default entry
                unset($this->data->userGuid); //don't update the userGuid of the default entry
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        try {
            parent::postAction();
        }
        catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            $this->handleIntegrityConstraint($e);
        }
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        try {
            parent::putAction();
        }
        catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
            $this->handleIntegrityConstraint($e);
        }
    }
    
    /**
     * converts the integrity constraint exception to an user friendly exception for the frontend
     * @param ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    protected function handleIntegrityConstraint(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
        //check if the error comes from the customer assoc or not
        if(! $e->isInMessage('REFERENCES `LEK_taskUserAssoc`')) {
            throw $e;
        }
        throw ZfExtended_UnprocessableEntity::createResponse('E1172', [
            'userGuid' => 'Der referenzierte Benutzer ist der Aufgabe nicht mehr zugewiesen oder existiert nicht mehr.'
        ], [], $e);
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