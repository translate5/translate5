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
class editor_TermattributeController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_Term_Attribute';
    
    /**
     * @var editor_Models_Term_Attribute
     */
    protected $entity;
    
    /**
     * @var editor_Models_Term_AttributeProposal
     */
    protected $proposal;
    
    public function indexAction() {
        //term attributes are currently not listable via REST API
        throw new BadMethodCallException();
    }
    
    public function putAction() {
        //term attributes are currently not editable via REST API
        throw new BadMethodCallException();
    }
    
    public function postAction() {
        //term attributes are currently not createable via REST API
        throw new BadMethodCallException();
    }
    
    /**
     * Extend the term with the proposal - if there is any
     * {@inheritDoc}
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        $this->proposal = ZfExtended_Factory::get('editor_Models_Term_AttributeProposal');
        /* @var $proposal editor_Models_Term_Proposal */
        try {
            $this->proposal->loadByAttributeId($this->entity->getId());
            $this->view->rows->proposal = $this->proposal->getDataObject();
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->proposal->init();
            $this->view->rows->proposal = null;
            //do nothing if no proposal found
        }
        $this->view->rows->proposable = $this->entity->isProposable(); 
    }
    
    /**
     * propose a new attribute, this function has the same signature as the putAction, expect that it creates a new propose instead of editing the term directly
     */
    public function proposeOperation() {
        $sessionUser = new Zend_Session_Namespace('user');
        
        $this->decodePutData();
        
        $this->proposal->setAttributeId($this->entity->getId());
        $this->proposal->setCollectionId($this->entity->getCollectionId());
        $this->proposal->setValue(trim($this->data->value));
        $this->proposal->validate();
        
        //set system fields after validation, so we don't have to provide a validator for them
        $this->proposal->setUserGuid($sessionUser->data->userGuid);
        $this->proposal->setUserName($sessionUser->data->userName);
        $this->proposal->setCreated(NOW_ISO);
        
        //we don't save the term, but we save it to a proposal:
        $this->proposal->save();
        
        //in the term attributes the termEntryId is not set
        $entryId=$this->entity->getTermEntryId();
        if($entryId==null){
            $term=ZfExtended_Factory::get('editor_Models_Term');
            /* @var $term editor_Models_Term */
            $term->load($this->entity->getTermId());
            $entryId=$term->getTermEntryId();
        }
        
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        $termEntry->load($entryId);
        
        //update the term entry create/modefy dates
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        $attribute->handleTransacGroup($termEntry);
        
        //update the view
        $this->view->rows->proposal = $this->proposal->getDataObject();
        
        //set the groupid, it is used by the attribute proposal component
        $this->view->rows->groupId=$termEntry->getGroupId();
        $this->view->rows->termEntryId=$termEntry->getId();
        
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        
        //load the term entry attributes
        $this->view->rows->termEntryAttributes=$attribute->getAttributesForTermEntry($termEntry->getId(),[$termEntry->getCollectionId()]);
    }
    
    /**
     * TODO: Tests, later in the development
     * 
     * confirm the proposal and saves the proposed data into the term
     * @throws ZfExtended_UnprocessableEntity
     */
    public function confirmproposalOperation() {
        if(empty($this->view->rows->proposal)) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1108' => 'There is no attribute proposal which can be confirmed.'
            ], 'editor.term.attribute');
            throw new ZfExtended_UnprocessableEntity('E1108');
        }
        $history = $this->entity->getNewHistoryEntity();
        //take over new data from proposal:
        $this->entity->setValue($this->proposal->getValue());
        $this->entity->setProcessStatus($this->entity::PROCESS_STATUS_PROV_PROCESSED);
        $this->entity->setUserName($this->proposal->getUserName());
        $this->entity->setUserGuid($this->proposal->getUserGuid());
        $this->entity->save();
        $this->proposal->delete();
        $history->save();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->proposal = null;
    }
    
    /**
     * removes a proposal
     * @throws ZfExtended_UnprocessableEntity
     */
    public function removeproposalOperation() {
        
        //the removed request is for attribute with process status unprocessed
        if($this->view->rows->processStatus==editor_Models_Term::PROCESS_STATUS_UNPROCESSED){
            $this->entity->delete();
            $this->view->rows = [];
            return;
        }
        
        if(empty($this->view->rows->proposal)) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1110' => 'There is no attribute proposal which can be deleted.'
            ], 'editor.term.attribute');
            throw new ZfExtended_UnprocessableEntity('E1110');
        }
        $this->proposal->delete();
        

        //in the term attributes the termEntryId is not set
        $entryId=$this->entity->getTermEntryId();
        if($entryId==null){
            $term=ZfExtended_Factory::get('editor_Models_Term');
            /* @var $term editor_Models_Term */
            $term->load($this->entity->getTermId());
            $entryId=$term->getTermEntryId();
        }
        
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        $termEntry->load($entryId);
        
        //update the term entry create/modefy dates
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        $attribute->handleTransacGroup($termEntry);
        
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->proposal = null;
    }
}
