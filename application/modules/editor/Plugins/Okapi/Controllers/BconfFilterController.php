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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfInvalidException;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FilterEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\Fprm;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FprmValidation;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Helpers\ResourceFileImport;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;

/**
 * REST Endpoint Controller to serve a Bconfs Filter List for the Bconf-Management in the Preferences
 *
 * @property FilterEntity $entity
 */
class editor_Plugins_Okapi_BconfFilterController extends ZfExtended_RestController
{
    protected $entityClass = FilterEntity::class;

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['downloadfprm'];

    /**
     * This also transfers the Extension-Mapping and the default Extensions to the frontend via metaData
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     * @throws OkapiException
     */
    public function indexAction(): void
    {
        $bconf = new BconfEntity();
        $bconf->load($this->getParam('bconfId'));

        // add the grid data
        $this->view->rows = $bconf->getCustomFilterGridData();
        $this->view->total = count($this->view->rows);

        // the extension mapping is sent as meta-data
        if (empty($this->view->metaData)) {
            $this->view->metaData = new stdClass();
        }
        $this->view->metaData->extensionMapping = $bconf->getExtensionMapping()->getIdentifierMap();
        $this->view->metaData->allExtensions = editor_Plugins_Okapi_Init::getSystemDefaultBconf()->getSupportedExtensions();
    }

    /**
     * Adjusted to additionally process the extension mapping
     *
     * @throws BconfInvalidException
     * @throws Throwable
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function postAction(): void
    {
        parent::postAction();
        // extensions have been sent as put-data
        $extensions = explode(',', $this->data->extensions);
        // we need to update the extension-mapping
        $bconf = $this->entity->getRelatedBconf();
        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->addFilter($this->entity->getIdentifier(), $extensions);
        // re-pack the bconf after update
        $bconf->pack();
        // the frontend needs to know about an adjusted identifier
        $this->view->rows->identifier = $this->entity->getIdentifier();
    }

    /**
     * Adjusted to additionally process the extension mapping
     *
     * @throws BconfInvalidException
     * @throws Throwable
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function putAction(): void
    {
        parent::putAction();
        // extensions have been sent as put-data
        $extensions = explode(',', $this->data->extensions);
        // update mapping (which also updates the content/TOC)
        $bconf = $this->entity->getRelatedBconf();
        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->changeFilter($this->entity->getIdentifier(), $extensions);
        // re-pack the bconf after update
        $bconf->pack();
        // the frontend needs to know about an adjusted identifier
        $this->view->rows->identifier = $this->entity->getIdentifier();
    }

    /**
     * Adjusted to additionally process the extension mapping
     *
     * @throws BconfInvalidException
     * @throws Throwable
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function deleteAction(): void
    {
        $this->entityLoad();
        $bconf = $this->entity->getRelatedBconf();
        // remove the filter from the extension mapping (which also updates the content/TOC)
        $extensionMapping = $bconf->getExtensionMapping();
        $extensionMapping->removeFilter($this->entity->getIdentifier());
        // and delete
        $this->processClientReferenceVersion();
        $this->entity->delete();
        // re-pack the bconf after deletion of filter
        $bconf->pack();
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function getfprmAction(): void
    {
        $this->entityLoad();
        $fprm = $this->entity->getFprm();
        // Create the data to send to the Frontend GUIs
        $this->view->type = $fprm->getType();
        $this->view->raw = $fprm->getContent();
        $this->view->transformed = $fprm->crateTransformedData();
        $this->view->translations = $fprm->crateTranslationData();
        $this->view->guidata = $fprm->createGuiData();
    }

    /**
     * @throws BconfInvalidException
     * @throws Throwable
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    public function savefprmAction(): void
    {
        $this->entityLoad();
        // create a fprm from the sent raw content
        $fprm = new Fprm(
            $this->entity->getPath(),
            $this->getRequest()->getRawBody()
        );
        $validationError = null;
        if ($fprm->validate()) {
            // only x-properties based fprms can be properly tested by parsing the content. the others need a validation by using them in longhorn
            if ($fprm->getType() != Fprm::TYPE_XPROPERTIES) {
                $okapiValidation = new FprmValidation($this->entity->getRelatedBconf(), $fprm);
                // this
                if ($okapiValidation->validate()) {
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
            // Only with YAML or XML currently this is a user-error, as both are edited as a whole in a big textfield
            if ($fprm->getType() == Fprm::TYPE_YAML || $fprm->getType() == Fprm::TYPE_XML) {
                $validationError = $fprm->getValidationError();
            } else {
                throw new OkapiException('E1409', [
                    'details' => $fprm->getValidationError(),
                    'filterfile' => $this->entity->getFile(),
                    'bconfId' => $this->entity->getBconfId(),
                ]);
            }
        }
        // the editor will stay open when a user-error occurs, therefore no server-exception then
        if ($validationError != null) {
            $this->view->success = false;
            $this->view->error = $validationError;
        } else {
            $this->view->success = true;
        }
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function downloadfprmAction()
    {
        $this->entityLoad();
        $fprm = new Fprm($this->entity->getPath());
        $downloadFilename = editor_Utils::filenameFromUserText($this->entity->getName(), false) . '-' . $fprm->getFile();
        $fprm->download($downloadFilename);
        exit;
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_UnprocessableEntity
     * @throws OkapiException
     * @throws ZfExtended_Exception
     * @throws Throwable
     */
    public function uploadfprmAction()
    {
        if (empty($_FILES)) {
            throw new OkapiException('E1212', [
                'msg' => "No upload files were found. Please try again. If the error persists, please contact the support.",
            ]);
        }
        $this->entityLoad();
        $fprm = new Fprm($this->entity->getPath(), file_get_contents($_FILES['fprm']['tmp_name']));

        $errMsg = ResourceFileImport::addToBConf($fprm, $this->entity->getRelatedBconf());
        if ($errMsg) {
            throw new OkapiException('E1686', [
                'details' => $errMsg,
            ]);
        }
    }

    /**
     * Overwritten for additional processing on postAction
     *
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws OkapiException
     */
    protected function decodePutData(): void
    {
        // crucial: new entries are sent with a temp identifier 'NEW@FILTER' which needs to be turned to a valid custom identifier and id
        // this represents a clone-operation and the original identifier can be restored from the (cloned) okapiType / okapiId
        parent::decodePutData();
        if ($this->data->identifier === 'NEW@FILTER') {
            // we need to copy the FPRM-file and generate the new identifier (returns Object with properties okapiId | identifier | path | hash
            $newData = FilterEntity::preProcessNewEntry(
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
}
