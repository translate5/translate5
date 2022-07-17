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
        // extensions have been sent as put-data
        $extensions = explode(',', $this->data->extensions);
        // we need to update the extension-mapping but not the content/TOC
        $extensionMapping = $this->entity->getRelatedBconf()->getExtensionMapping();
        $extensionMapping->changeFilter($this->entity->getIdentifier(), $extensions);
        // the frontend needs to know about an adjusted identifier
        $this->view->rows->identifier = $this->entity->getIdentifier();
    }

    /**
     * Adjusted to additionally process the extension mapping
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function putAction(){
        parent::putAction();
        // extensions have been sent as put-data
        $extensions = explode(',', $this->data->extensions);
        // update mapping
        $bconf = $this->entity->getRelatedBconf();
        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->addFilter($this->entity->getIdentifier(), $extensions);
        // update content/TOC
        $content = $bconf->getContent();
        $content->addFilter($this->entity->getIdentifier());
        $content->flush();
        // the frontend needs to know about an adjusted identifier
        $this->view->rows->identifier = $this->entity->getIdentifier();
    }

    /**
     * Overwritten for additional processing on postAction
     */
    protected function decodePutData(){
        // crucial: new entries are sent with a temp identifier 'NEW@FILTER' which needs to be turned to a valid custom identifier and id
        // this represents a clone-operation and the original identifier can be restored from the (cloned) okapiType / okapiId
        parent::decodePutData();
        if($this->data->identifier === 'NEW@FILTER'){
            // we need to copy the FPRM-file and generate the new identifier (returns Object with properties okapiId | identifier | path | hash
            $newData = editor_Plugins_Okapi_Bconf_Filter_Entity::preProcessNewEntry(
                intval($this->data->bconfId),
                $this->data->okapiType,
                $this->data->okapiId,
                $this->data->name
            );
            $this->data->okapiId = $newData->okapiId;
            $this->data->identifier = $newData->identifier;
            $this->data->hash = $newData->hash;
        }
    }

    /**
     * Adjusted to additionally process the extension mapping
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Plugins_Okapi_Exception
     */
    public function deleteAction(){
        $this->entityLoad();
        $bconf = $this->entity->getRelatedBconf();
        // remove the filter from the extension mapping
        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->removeFilter($this->entity->getIdentifier());
        // remove the filter from the content/TOC
        $content = $bconf->getContent();
        $content->removeFilter($this->entity->getIdentifier());
        $content->flush();
        // and delete
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
        $validationError = NULL;
        if($fprm->validate()){
            // only x-properties based fprms can be properly tested by parsing the content. the others need a validation by using them in longhorn
            if($fprm->getType() != editor_Plugins_Okapi_Bconf_Filter_Fprm::TYPE_XPROPERTIES){
                $okapiValidation = new editor_Plugins_Okapi_Bconf_Filter_FprmValidation($this->entity->getRelatedBconf(), $fprm);
                // this
                if($okapiValidation->validate()){
                    // update/pack the hash, flushing of bconf & fprm is already done by the validator
                    $this->entity->setHash($fprm->getHash());
                    $this->entity->save();
                } else {
                    $validationError = $okapiValidation->getValidationError();
                }
            } else {
                // save x-properties fprm, set hash, pack bconf
                $fprm->flush();
                $this->entity->setHash($fprm->getHash());
                $this->entity->getRelatedBconf()->pack();
                $this->entity->save();
            }
        } else {
            // this can normally only happen if the implementation of a filter-frontend generated faulty data.
            // Only with YAML currently this is a user-error, as YAML is edited as a whole in a big text-field
            if($fprm->getType() == editor_Plugins_Okapi_Bconf_Filter_Fprm::TYPE_YAML){
                $validationError = $fprm->getValidationError();
            } else {
                throw new editor_Plugins_Okapi_Exception('E1409', ['details' => $fprm->getValidationError(), 'filterfile' => $this->entity->getFile(), 'bconfId' => $this->entity->getBconfId()]);
            }
        }
        // the editor will stay open when a user-error occurs, therefore no server-exception then
        if($validationError != NULL){
            $this->view->success = false;
            $this->view->error = $validationError;
        } else {
            $this->view->success = true;
        }
    }
}