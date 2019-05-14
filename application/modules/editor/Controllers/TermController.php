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
class editor_TermController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_Term';
    
    /**
     * @var editor_Models_Term
     */
    protected $entity;
    
    /**
     * @var editor_Models_Term_Proposal
     */
    protected $proposal;
    
    /**
     * Extend the term with the proposal - if there is any
     * {@inheritDoc}
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        parent::getAction();
        $this->proposal = ZfExtended_Factory::get('editor_Models_Term_Proposal');
        /* @var $proposal editor_Models_Term_Proposal */
        try {
            $this->proposal->loadByTermId($this->entity->getId());
            $this->view->rows->proposal = $this->proposal->getDataObject();
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->proposal->init();
            $this->view->rows->proposal = null;
            //do nothing if no proposal found
        }
    }
    
    /**
     * propose a new term, this function has the same signature as the putAction, expect that it creates a new propose instead of editing the term directly
     * {@inheritDoc}
     * @see ZfExtended_RestController::putAction()
     */
    public function proposeOperation() {
        $this->decodePutData();
        
        $this->proposal->setTermId($this->entity->getId());
        $this->proposal->setCollectionId($this->entity->getCollectionId());
        $this->proposal->setTerm(trim($this->data->term));
        $this->proposal->validate();

        //we don't save the term, but we save it to a proposal: 
        $this->proposal->save();
        
        //update the view
        $this->view->rows->proposal = $this->proposal->getDataObject();
    }
    
    /**
     * confirm the proposal and saves the proposed data into the term
     * @throws ZfExtended_UnprocessableEntity
     */
    public function confirmproposalOperation() {
        if(empty($this->view->rows->proposal)) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1105' => 'There is no proposal which can be confirmed.'
            ], 'editor.term');
            throw new ZfExtended_UnprocessableEntity('E1105');
        }
        //take over data from proposal
        $this->entity->setTerm($this->proposal->getTerm());
        $this->entity->setProcessStatus($this->entity::PROCESS_STATUS_PROV_PROCESSED);
        $this->entity->save();
        $this->proposal->delete();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->proposal = null;
    }
}
