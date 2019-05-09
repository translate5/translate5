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
     * propose a new term, this function has the same signature as the putAction, expect that it creates a new propose instead of editing the term directly
     * {@inheritDoc}
     * @see ZfExtended_RestController::putAction()
     */
    public function proposeOperation() {
        $this->decodePutData();
        
        $proposal = ZfExtended_Factory::get('editor_Models_TermCollection_TermProposal');
        /* @var $proposal editor_Models_TermCollection_TermProposal */
        $proposal->setTermId($this->entity->getId());
        $proposal->setCollectionId($this->entity->getCollectionId());
        $proposal->setTerm($this->data->term);
        $proposal->validate();

        //we don't save the term, but we save it to a proposal: 
        $proposal->save();
        
        //update the view
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->proposal = $proposal->getDataObject();
    }
}
