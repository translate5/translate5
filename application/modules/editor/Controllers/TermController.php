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
class editor_TermController extends ZfExtended_RestController
{
    protected $entityClass = 'editor_Models_Terminology_Models_TermModel';

    /**
     * @var editor_Models_Terminology_Models_TermModel
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
    public function getAction()
    {
        parent::getAction();
        $this->proposal = ZfExtended_Factory::get('editor_Models_Term_Proposal');
        /* @var $proposal editor_Models_Term_Proposal */
        try {
            $this->proposal->loadByTermId($this->entity->getId());
            $this->view->rows->proposal = $this->proposal->getDataObject();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->proposal->init();
            $this->view->rows->proposal = null;
            //do nothing if no proposal found
        }
    }

    public function postAction()
    {
        parent::postAction();

        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */

        settype($this->data->isTermProposalFromInstantTranslate, 'boolean');

        //handle additional source term
        $this->handleSourceTerm();

        //todo: Sinisa, handle new TransacGrp
        //handle the term transac group attributes (modification/creation)
        /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */
        $transacGrp->handleTransacGroup($this->entity);

        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */
        $attribute->checkOrCreateProcessStatus($this->entity->getId());

        $this->view->rows = $this->entity->getDataObjectWithAttributes();
        //load the term entry attributes
        $this->view->rows->termEntryAttributes=$attribute->getAttributesForTermEntry($this->entity->getTermEntryId(),[$this->entity->getCollectionId()]);

        if(!empty($this->view->rows->attributes) && !empty($this->view->rows->language)){
            $language = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $language editor_Models_Languages */
            $language->load($this->view->rows->language);
            $this->view->rows->languageRfc5646 = $language->getRfc5646();
        }
    }
    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData()
    {
        parent::decodePutData();

        if (isset($this->data->term)) {
            //remove whitespace from the beggining and the end of the string
            $this->data->term = trim($this->data->term);
        }

        if (isset($this->data->comment)) {
            //remove whitespace from the beggining and the end of the string
            $this->data->comment = trim($this->data->comment);
        }

        $this->convertToLanguageId();
        if ($this->_request->isPut()) {
            //the following fields may not be changed via PUT:
            unset($this->data->language);
            unset($this->data->termEntryId);
            unset($this->data->mid);
            unset($this->data->groupId);
        }

        //ignore all non post request
        if (!$this->_request->isPost()) {
            return;
        }

        if (!empty($this->data->groupId)) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1154', ['groupId' => 'Die GroupId kann nicht direkt gesetzt werden, nur indirekt über eine gegebene TermEntryId.']);
        }

        if (!empty($this->data->termEntryId)) {
            //when the term entry is provided, load the term entry and set the term groupId
            //this is the case when new term is proposed in the allready exisitn termEntry
            $entry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
            /* @var $entry editor_Models_Terminology_Models_TermEntryModel */
            $entry->load($this->data->termEntryId);
            $this->entity->setTermEntryTbxId($entry->getTermEntryTbxId());
            $this->entity->setTermEntryId($entry->getId());
            return;
        }

        if (empty($this->data->termEntryId) && $this->entity->getTermEntryId() === null) {
            $entry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
            /* @var $entry editor_Models_Terminology_Models_TermEntryModel */
            $entry->setCollectionId($this->data->collectionId);
            $entry->setTermEntryTbxId(ZfExtended_Utils::uuid());
            $entry->setIsProposal(true);
            $entry->save();

            //update or create the term entry creation/modification attributes
            // todo: Sinisa, handle TransacGrp
            $transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
            /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */
            $transacGrp->createTransacGroup($entry,'creation');
            $transacGrp->createTransacGroup($entry,'modification');

            $this->entity->setTermEntryId($entry->getId());
            $this->entity->setTermEntryTbxId($entry->getTermEntryTbxId());
            $this->data->termEntryId = $entry->getId();
            $this->data->groupId = $entry->getTermEntryTbxId();
        }
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::additionalValidations()
     */
    protected function additionalValidations()
    {
        //for POST and PUT update always the usage after entity validation
        $this->updateUsageData($this->entity);

        if($this->_request->isPost()) {
            $this->initTermOnPost();
        }
    }

    /**
     * converts a given language value as lcid or Rfc5646 value to the needed ID
     */
    protected function convertToLanguageId()
    {
        //ignoring if already integer like value or empty
        if (empty($this->data->language)) {
            return;
        }

        $languages = explode(',', $this->data->language);

        if (empty($languages)) {
           return;
        }

        $language = $languages[0];
        $this->data->language = $language;

        if ((int)$this->data->language > 0) {
            return;
        }

        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        try {
            $matches = null;
            if (preg_match('/^lcid-([0-9]+)$/i', $this->data->language, $matches)) {
                $language->loadByLcid($matches[1]);
            } else {
                $language->loadByRfc5646($this->data->language);
            }
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->data->language = 0;
            return;
        }
        $this->data->language = $language->getId();
    }

    /**
     * Creates a term entry if a term is tried to be created without any termEntryId and groupId given
     */
    protected function initTermOnPost()
    {
        $this->entity->setCreated(NOW_ISO);
        ZfExtended_UnprocessableEntity::addCodes([
            'E1152' => 'Missing mandatory collectionId for term creation',
            'E1153' => 'Missing mandatory language (ID) for term creation',
            'E1154' => 'GroupId was set explicitly, this is not allowed. Must be set implicit via a given termEntryId',
        ]);
        if (empty($this->data->collectionId)) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1152', ['collectionId' => 'Bitte wählen Sie eine TermCollection aus, welcher dieser Term hinzugefügt werden soll.']);
        }
        if (empty($this->data->language)) {
            throw ZfExtended_UnprocessableEntity::createResponse('E1153', ['language' => 'Bitte wählen Sie die Sprache des Terms aus.']);
        }
        if (empty($this->data->processStatus)) {
            //TODO: this initial value will depend on the ACL with Phase 3 implementation of termportal
            $this->entity->setProcessStatus(editor_Models_Terminology_Models_TermModel::PROCESS_STATUS_UNPROCESSED);
        }
        if (empty($this->data->status)) {
            $this->entity->setStatus(editor_Models_Terminology_Models_TermModel::STAT_ADMITTED);
        }
        if (empty($this->data->mid)) {
            $this->entity->setTermId(ZfExtended_Utils::uuid());
        }
        $entry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $entry editor_Models_Terminology_Models_TermEntryModel */
        if (empty($this->data->termEntryId)) {
            $entry->setCollectionId($this->data->collectionId);
            $entry->setTermEntryTbxId(ZfExtended_Utils::uuid());
            $entry->setIsProposal(true);
            $entry->setId($entry->save());

            $this->entity->setTermEntryId($entry->getId());
            $this->entity->setTermEntryTbxId($entry->getTermEntryTbxId());
        } else {
            //when the term entry is provided, load the term entry and set the term groupId
            //this is the case when new term is proposed in the allready exisitn termEntry
            $entry->load($this->data->termEntryId);
            $this->entity->setTermEntryTbxId($entry->getTermEntryTbxId());
        }

        //update or create the term entry creation/modification attributes
        // todo: Sinisa, handle transacGrp
        $transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */
        $transacGrp->handleTransacGroup($entry);
    }

    /**
     * propose a new term, this function has the same signature as the putAction, expect that it creates a new propose instead of editing the term directly
     */
    public function proposeOperation()
    {
        $sessionUser = new Zend_Session_Namespace('user');
        $this->decodePutData();
        $term = trim($this->data->term);

        //check if the term text exist in the term collection within the language
        $tmpTermValue = $this->entity->findTermInCollection($term);

        if ($tmpTermValue && $tmpTermValue->count() > 0) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1111' => 'The made term proposal does already exist as different term in the same language in the current term collection.'
            ], 'editor.term');
            throw new ZfExtended_Models_Entity_Conflict('E1111');
        }

        $this->proposal->setTermId($this->entity->getId());
        $this->proposal->setCollectionId($this->entity->getCollectionId());
        $this->proposal->setTerm($term);
        $this->proposal->validate();

        //set system fields after validation, so we don't have to provide a validator for them
        $this->proposal->setUserGuid($sessionUser->data->userGuid);
        $this->proposal->setUserName($sessionUser->data->userName);

        //we don't save the term, but we save it to a proposal:
        $this->proposal->save();

        $transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */

        $transacGrp->handleTransacGroup($this->entity);

        $this->view->rows = $this->entity->getDataObjectWithAttributes();
        //update the view
        $this->view->rows->proposal = $this->proposal->getDataObject();
    }

    /**
     * Tries to update or insert a value "comment" into langSet>descripGrp>note of the term
     */
    public function commentOperation()
    {
        $this->decodePutData();
        $commentAttribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $commentAttribute editor_Models_Terminology_Models_AttributeModel */
        try {
            $commentAttribute->loadByTermAndName($this->entity, 'note', $commentAttribute::ATTR_LEVEL_TERM);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            $commentAttribute = $commentAttribute->addTermComment($this->entity->getId(), trim($this->data->comment));
            $this->view->rows = $commentAttribute->getDataObject();
            //set the groupid (termEntryTbxID given from tbx import), it is used by the attribute proposal component
            $this->view->rows->groupId = $this->entity->getTermEntryTbxId();
            return;
        }

        //the comment is proposed
        $sessionUser = new Zend_Session_Namespace('user');

        $this->decodePutData();

        $proposal = ZfExtended_Factory::get('editor_Models_Term_AttributeProposal');
        /* @var $proposal editor_Models_Term_AttributeProposal */

        $proposal->setAttributeId($commentAttribute->getId());
        $proposal->setCollectionId($commentAttribute->getCollectionId());
        $proposal->setValue(trim($this->data->comment));
        $proposal->validate();

        //set system fields after validation, so we don't have to provide a validator for them
        $proposal->setUserGuid($sessionUser->data->userGuid);
        $proposal->setUserName($sessionUser->data->userName);
        $proposal->setCreated(NOW_ISO);

        $proposal->save();

        //in the term attributes the termEntryId is not set
        $entryId = $commentAttribute->getTermEntryId();
        if ($entryId === null) {
            $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
            /* @var $term editor_Models_Terminology_Models_TermModel */
            $term->load($commentAttribute->getTermId());
            $entryId=$term->getTermEntryId();
        }

        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->load($entryId);

        //update the term entry create/modefy dates
        $transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
        /* @var $transacGrp editor_Models_Terminology_Models_TransacgrpModel */
        $transacGrp->handleTransacGroup($termEntry);

        //update the view
        $this->view->rows = $commentAttribute->getDataObject();
        $this->view->rows->proposal = $proposal->getDataObject();
        //set the groupid, it is used by the attribute proposal component
        $this->view->rows->groupId = $termEntry->getTermEntryTbxId();
    }

    /**
     * TODO: Tests, later on the development
     * confirm the proposal and saves the proposed data into the term
     * @throws ZfExtended_UnprocessableEntity
     */
    public function confirmproposalOperation()
    {
        if (empty($this->view->rows->proposal)) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1105' => 'There is no proposal which can be confirmed.'
            ], 'editor.term');
            throw new ZfExtended_UnprocessableEntity('E1105');
        }
        $history = $this->entity->getNewHistoryEntity();
        //take over new data from proposal:
        $this->entity->setTerm($this->proposal->getTerm());
        $this->entity->setProcessStatus($this->entity::PROCESS_STATUS_PROV_PROCESSED);
        $this->updateUsageData($this->entity, $this->proposal->getUserName(), $this->proposal->getUserGuid());
        $this->entity->save();
        $this->proposal->delete();
        $history->save();
        $this->view->rows = $this->entity->getDataObjectWithAttributes();
        $this->view->rows->proposal = null;
    }

    /**
     * removes a proposal
     * @throws ZfExtended_UnprocessableEntity
     */
    public function removeproposalOperation()
    {
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntryId = $this->entity->getTermEntryId();
        //the removed request is for term with process status unprocessed
        if ($this->view->rows->processStatus == $this->entity::PROCESS_STATUS_UNPROCESSED) {
            $this->entity->delete();
            $this->view->rows = [];
            $termEntry->deleteEmptyTermEntry($termEntryId);
            return;
        }

        if (empty($this->view->rows->proposal)) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1109' => 'There is no proposal which can be deleted.'
            ], 'editor.term.attribute');
            throw new ZfExtended_UnprocessableEntity('E1109');
        }
        $this->proposal->delete();
        $termEntry->deleteEmptyTermEntry($termEntryId);

        //remove all term attribute proposals to
        //https://jira.translate5.net/browse/TS-174
        $attributeProposal = ZfExtended_Factory::get('editor_Models_Term_AttributeProposal');
        /* @var $attributeProposal editor_Models_Term_AttributeProposal */
        $attributeProposal->removeAllTermAttributeProposals($this->entity->getId());

        $this->view->rows = $this->entity->getDataObjectWithAttributes();
        $this->view->rows->proposal = null;

        //load all attributes for the term
        $rows = $this->entity->findTermAndAttributes($this->entity->getId());
        $rows = $this->entity->groupTermsAndAttributes($rows);
        if (!empty($rows) && !empty($rows[0]['attributes'])) {
            $this->view->rows->attributes = $rows[0]['attributes'];
        }
    }

    /***
     * Handle the additional term if exist
     */
    protected function handleSourceTerm()
    {
        if ($this->entity->getId() < 1) {
            return;
        }

        if (!isset($this->data->termSource) || !isset($this->data->termSourceLanguage)) {
            return;
        }

        if (empty($this->data->termSource) || empty($this->data->termSourceLanguage)) {
            return;
        }

        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language editor_Models_Languages */
        try {
            $matches = null;
            if (preg_match('/^lcid-([0-9]+)$/i', $this->data->termSourceLanguage, $matches)) {
                $language->loadByLcid($matches[1]);
            } else {
                $language->loadByRfc5646($this->data->termSourceLanguage);
            }
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->data->termSourceLanguage = 0;
            return;
        }
        $this->data->termSourceLanguage = $language->getId();

        //remove whitespace from the beggining and the end of the string
        $this->data->termSource=trim($this->data->termSource);

        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */

        $term->setTerm($this->data->termSource);
        $term->setLanguage($this->data->termSourceLanguage);
        $term->setTermId($this->entity->getTermId());
        $term->setStatus($this->entity->getStatus());
        $term->setProcessStatus($this->entity->getProcessStatus());
        $term->setDefinition($this->entity->getDefinition());
        $term->setTermEntryTbxId($this->entity->getTermEntryTbxId());
        $term->setCollectionId($this->entity->getCollectionId());
        $term->setTermEntryId($this->entity->getTermEntryId());
        $term->setUserGuid($this->entity->getUserGuid());
        $term->setUserName($this->entity->getUserName());
        $term->setCreated(null);
        $term->setUpdated(null);
        $term->setId($term->save());

        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */
        //check or create the term processStatus attribute
        $attribute->checkOrCreateProcessStatus($term->getId());

        //if the term is added from the instant transalte MT engine, set the default comment
        if ($this->data->isTermProposalFromInstantTranslate) {
            $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            $attribute->addTermComment($term->getId(), $translate->_("Aus MT übernommen"));
        }

        $transacGrp = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacGrpModel');
        /* @var $transacGrp editor_Models_Terminology_Models_TransacGrpModel */
        //create or update or create the term transac group attributes
        $transacGrp->handleTransacGroup($term);
    }

    /**
     * Helper function to update the userGuid, userName and updated field of the given entity
     * @param ZfExtended_Models_Entity_Abstract $entity
     * @param string $userName optional, defaults to userName of authenticated user in session
     * @param string $userGuid optional, defaults to userGuid of authenticated user in session
     */
    protected function updateUsageData(ZfExtended_Models_Entity_Abstract $entity, string $userName = null, string $userGuid = null)
    {
        $sessionUser = new Zend_Session_Namespace('user');
        $entity->setUserGuid($userGuid ?? $sessionUser->data->userGuid);
        $entity->setUserName($userName ?? $sessionUser->data->userName);
        $entity->hasField('updated') && $entity->setUpdated(NOW_ISO);
    }
}
