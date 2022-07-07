<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * REST Endpoint Controller to serve a Bconfs Filter List for the Bconf-Management in the Preferences
 *
 * @property editor_Plugins_Okapi_Bconf_Filter_Entity $entity
 */
class editor_Plugins_Okapi_BconfFilterController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Plugins_Okapi_Bconf_Filter_Entity';

    /**
     * This also transfers the Extension-Mapping and the default Extensions to the frontend via metaData
     * @return void
     * @throws editor_Plugins_Okapi_Exception
     */
    public function indexAction() {
        $bconf = new editor_Plugins_Okapi_Bconf_Entity();
        $bconf->load($this->getParam('bconfId'));

        // add the grid data
        $this->view->rows = $bconf->getCustomFilterGridData();
        $this->view->total = count($this->view->rows);

        // the extension mapping is sent as meta-data
        if(!$this->view->metaData){
            $this->view->metaData = new stdClass();
        }
        $this->view->metaData->extensionMapping = $bconf->getExtensionMapping()->getIdentifierMap();
        $this->view->metaData->allExtensions = editor_Plugins_Okapi_Init::getAllExtensions();
    }

    /**
     * Adjusted to additionally process the extension mapping
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function postAction(){
        parent::postAction();
        $extensionMapping = $this->entity->getRelatedBconf()->getExtensionMapping();
        $extensionMapping->changeFilter($this->entity->getIdentifier(), $this->entity->getFileExtensions());
    }

    /**
     * Adjusted to additionally process the extension mapping
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function putAction(){
        parent::putAction();
        $extensionMapping = $this->entity->getRelatedBconf()->getExtensionMapping();
        $extensionMapping->addFilter($this->entity->getIdentifier(), $this->entity->getFileExtensions());
    }

    /**
     * Adjusted to additionally process the extension mapping
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function deleteAction(){
        $this->entityLoad();
        $extensionMapping = $this->entity->getRelatedBconf()->getExtensionMapping();
        $extensionMapping->removeFilter($this->entity->getIdentifier());
        $this->processClientReferenceVersion();
        $this->entity->delete();
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function getfprmAction(){
        $this->entityLoad();
        $fprm = $this->entity->getFprm();
        $fprm->output();
        exit;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws editor_Plugins_Okapi_Exception
     */
    public function savefprmAction(){
        $this->entityLoad();
        // create a fprm from the sent raw content
        $fprm = new editor_Plugins_Okapi_Bconf_Filter_Fprm(
            $this->entity->getPath(),
            $this->getRequest()->getRawBody()
        );
        if($fprm->validate()){
            // save FPRM
            $fprm->flush();
            // update/pack the related bconf
            $this->entity->getRelatedBconf()->getFile()->pack();
        } else {
            // this can only happen if the implementation of a filter-frontend generates faulty data
            throw new editor_Plugins_Okapi_Exception('E1409', ['details' => $fprm->getValidationError(), 'filterfile' => $this->entity->getFile(), 'bconfId' => $this->entity->getBconfId()]);
        }
    }
}