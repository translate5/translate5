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
class editor_TermController extends ZfExtended_RestController
{
    /**
     * Use termportal trait
     */
    use editor_Controllers_Traits_TermportalTrait;

    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_Terminology_Models_TermModel';

    /**
     * @var editor_Models_Terminology_Models_TermModel
     */
    protected $entity;

    /**
     * Collections, allowed for current user
     *
     * @var
     */
    protected $collectionIds = false;

    /**
     * @throws Zend_Session_Exception
     */
    public function init() {

        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();

        // Pick session
        $this->_session = (new Zend_Session_Namespace('user'))->data;

        // If current user has 'termPM_allClients' role, it means all collections are accessible
        // Else we should apply collectionsIds-restriction everywhere, so get accessible collections
        $this->collectionIds =
            in_array('termPM_allClients', $this->_session->roles)
                ?: ZfExtended_Factory::get('ZfExtended_Models_User')->getAccessibleCollectionIds();
    }

    /**
     *
     */
    public function indexAction()
    {
        // Term attributes are currently not listable via REST API
        throw new BadMethodCallException();
    }

    /**
     *
     */
    public function getAction() {
        throw new BadMethodCallException();
    }


    /**
     * Create term (with own termEntry, if need)
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     */
    public function postAction() {

        // Check params
        $_ = $this->_postCheckParams();

        // Get request params
        $params = $this->getRequest()->getParams();

        // Collection statistics diff
        $diff = ['termEntry' => 0, 'term' => 0, 'attribute' => 0];

        // Trim whitespaces from term
        $params['term'] = trim($params['term']);

        // Load termEntry model instance if termEntryId param is given or create and return a new one
        $te = $this->_postLoadOrCreateTermEntry();

        // Increase termEntry stats diff
        if (!isset($params['termEntryId'])) {
            $diff['termEntry'] ++;
        }

        // If 'sourceLang' and 'sourceTerm' params are given, it means we here because of
        // InstantTranslate usage in a way that assume that we found no existing termEntry by sourceTerm-param
        // so we save both terms (source and target) under same newly created termEntry
        if ($_['sourceLang'] ?? 0) {

            // Init current entity with data and insert into database
            $this->_postTermInit([
                'languageId' => $_['sourceLang']['id'],
                'language' => $_['sourceLang']['rfc5646'],
                'term' => trim($params['sourceTerm']),
                'status' => 'preferredTerm', // which status should be set initially ?
            ], $te)->insert([
                'userName' => $this->_session->userName,
                'userGuid' => $this->_session->userGuid
            ]);

            // Increment term and attribute stats diff
            $diff['term'] ++;
            $diff['attribute'] ++; // processStatus-attr was added for source term
        }

        /* @var $termNoteStatus editor_Models_Terminology_TermStatus */
        $termNoteStatus = ZfExtended_Factory::get('editor_Models_Terminology_TermStatus');

        // Apply data
        $this->_postTermInit([
            'languageId' => $_['language']['id'],
            'language' => $params['language'],
            'term' => $params['term'],
            'status' => $termNoteStatus->getDefaultTermStatus(),
        ], $te)->insert([
            'note' => trim($params['note'] ?? ''),
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Increment term and attribute stats diff
        $diff['term'] ++;
        $diff['attribute'] += trim($params['note'] ?? '') ? 2 : 1; // processStatus and maybe note attr were added for term

        // Update
        ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->updateStats($this->entity->getCollectionId(), $diff);

        // Flush params so that GUI to be redirected to that newly created term
        $this->view->assign([

            // Params, that will be used to build search hash-string
            'query' => $this->entity->getTerm(),
            'language' => $this->entity->getLanguageId(),
            'collectionIds' => $this->entity->getCollectionId(),

            // Params to simulate click on certain found result
            'termId' => $this->entity->getId(),
            'termEntryId' => $this->entity->getTermEntryId(),

            // Tbx ids
            'termTbxId' => $this->entity->getTermTbxId(),
            'termEntryTbxId' => $this->entity->getTermEntryTbxId(),
        ]);
    }

    /**
     * Request params validation prior term creation
     *
     * @return array
     * @throws Zend_Exception
     * @throws ZfExtended_Mismatch
     */
    protected function _postCheckParams() {

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ]);

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        return $this->jcheck([
            'collectionId' => [
                'req' => true,                                                      // required
                'rex' => 'int11',                                                   // regular expression preset key or raw expression
                'key' => 'LEK_languageresources',                                   // points to existing record in a given db table
            ],
            'language' => [
                'req' => true,                                                      // required
                'rex' => 'rfc5646',                                                 // regular expression preset key or raw expression
                'key' => 'LEK_languages.rfc5646',                                   // points to existing record in a given db table
            ],
            'term' => [
                'req' => true,                                                      // required
                'rex' => '~[^\s]~',                                                 // regular expression preset key or raw expression
            ],
            'note' => [
                'req' => Zend_Registry::get('config')                               // required
                   ->runtimeOptions->termportal->commentAttributeMandatory,
                'rex' => 'varchar255s'                                              // regular expression preset key or raw expression
            ],
            'termEntryId' => [
                'rex' => 'int11',
                'key' => 'terms_term_entry',                                        // points to existing record in a given db table
            ],                                                                      // $_['termEntryId'] will contain that record
            'sourceTerm' => [
                'req' => isset($params['sourceLang']),
                'rex' => '~[^\s]~'
            ],
            'sourceLang' => [
                'req' => isset($params['sourceTerm']),
                'rex' => 'rfc5646',
                'key' => 'LEK_languages.rfc5646'
            ]
        ]);
    }

    /**
     * Check whether termEntryId request param is given,
     * and if yes - load model instance, else created it otherwise
     *
     * @return editor_Models_Terminology_Models_TermEntryModel
     */
    protected function _postLoadOrCreateTermEntry() {

        // Get params
        $params = $this->getRequest()->getParams();

        // Get termEntry-model
        /** @var editor_Models_Terminology_Models_TermEntryModel $termEntry */
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');

        // If termEntryId is given in params
        if ($params['termEntryId'] ?? 0) {

            // Load instance
            $termEntry->load($params['termEntryId']);

        // Else init new one with data and insert into database
        } else {

            // Apply data
            $termEntry->init([
                'collectionId' => $params['collectionId'],
                'termEntryTbxId' => 'id' . ZfExtended_Utils::uuid(),
                'entryGuid' => ZfExtended_Utils::uuid(),
                'isCreatedLocally' => 1, // Just a flag, indicating that termEntry was created manually, e.g. not via tbx import
            ]);

            // Insert
            $termEntry->insert([
                'userName' => $this->_session->userName,
                'userGuid' => $this->_session->userGuid
            ]);
        }

        // Return termEntry model instance
        return $termEntry;
    }

    /**
     * Concat custom data given by $data arg with shared/common data,
     * and use that data to init termModel-model instance
     *
     * @param array $data
     * @param editor_Models_Terminology_Models_TermEntryModel $te
     * @return editor_Models_Terminology_Models_TermModel
     */
    protected function _postTermInit(array $data, editor_Models_Terminology_Models_TermEntryModel $te) {

        // Do init
        $this->entity->init($data + [
            'termTbxId' => 'id' . ZfExtended_Utils::uuid(),
            'collectionId' => $te->getCollectionId(),
            'termEntryId' => $te->getId(),
            'termEntryTbxId' => $te->getTermEntryTbxId(),
            'termEntryGuid' => $te->getEntryGuid(),
            //'langSetGuid' => $langSetGuid = '???',
            'guid' => ZfExtended_Utils::uuid(),
            'processStatus' => 'unprocessed',
            'definition' => '',
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        // Return $this->entity itself
        return $this->entity;
    }

    /**
     * Update term (update `terms_term`.`proposal`)
     *
     * @throws ZfExtended_Mismatch
     */
    public function putAction() {

        // Validate params and load entity
        $this->jcheck([
            'termId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => $this->entity,
            ],
            'proposal' => [
                //'req' => true,
                'rex' => '~[^\s]~'
            ]
        ]);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $this->entity);

        // Setup a flag indicating whther current user can change any term
        $canChangeAny = $this->isAllowed('editor_term', 'putAny');

        // Status shortcuts
        $isUnprocessed = $this->entity->getProposal() || $this->entity->getProcessStatus() == 'unprocessed';
        $isProvisionallyProcessed = !$this->entity->getProposal() && $this->entity->getProcessStatus() == 'provisionallyProcessed';

        // Roles shortcuts
        $termProposer  = in_array('termProposer' , $this->_session->roles);
        $termReviewer  = in_array('termReviewer' , $this->_session->roles);
        $termFinalizer = in_array('termFinalizer', $this->_session->roles);

        // Setup a flag indicating whether current user can edit current term
        $editable = $canChangeAny
            || ($termProposer  && $this->entity->getCreatedBy() == $this->_session->id)
            || ($termReviewer  && $isUnprocessed)
            || ($termFinalizer && $isProvisionallyProcessed);

        // If not allowed - flush failure
        if (!$editable) $this->jflush(false, 'This term is not editable');

        // Get request params
        $params = $this->getRequest()->getParams();

        // Update proposal
        $this->entity->setProposal(trim($params['proposal']));
        $this->entity->setUpdatedBy($this->_session->id);

        // Save, and pass params required to update `terms_transacgrp`-records of type 'modification' for all 3 levels
        $updated = $this->entity->update([
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid
        ]);

        // Flush response data
        $this->view->assign([
            'updated' => $updated,
            'proposal' => $this->entity->getProposal(),
            'processStatus' => $this->entity->getProposal() ? 'unprocessed' : $this->entity->getProcessStatus(),
        ]);
    }

    /**
     * Delete term
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {

        // Validate params and load entity
        $this->jcheck([
            'termId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => $this->entity
            ]
        ]);

        // Setup a flag indicating whether current user can delete any term
        $canDeleteAny = $this->isAllowed('editor_term', 'deleteAny');

        // Setup a flag indicating whether current user can delete current term
        // Actually, the logic is that if user can't delete any, then the deletion is only
        // possible if user has termProposer-role, and he is the creator of current term
        // And currently, all is ok because other roles (termReviewer and termFinalizer)
        // do not have delete-right on editor_term-resource, so the execution won't even reach
        // for them, but if at some point of time they would be granted then the below line
        // should be amended to rely on termProposer-role explicitly
        $deletable = $canDeleteAny || $this->entity->getCreatedBy() == $this->_session->id;

        // If current term is not deletable - flush failure
        if (!$deletable) $this->jflush(false, 'This term is not deletable');

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $this->entity);

        // Setup 'isLast' response flag
        $data['isLast'] = $this->entity->isLast();

        // Backup props
        $collectionId = $this->entity->getCollectionId();
        $termEntryId  = $this->entity->getTermEntryId();
        $language     = $this->entity->getLanguage();

        // Collection statistics diff
        $diff = ['termEntry' => 0, 'term' => 0];

        // If term we're going to delete is the last term within it's termEntry
        if ($data['isLast'] == 'entry') {

            // Delete:
            // 1. all images of termEntry (including language-level images)
            // 2. terms_term_entry-record itself
            $this->entity->preDeleteIfLast4Entry();

            // Decrement stats for termEntry
            $diff['termEntry'] --;

        // Else
        } else {

            // Else this term is the last within it's language
            if ($data['isLast'] == 'language') $this->entity->preDeleteIfLast4Language();

            // Affect `terms_transacgrp` 'modification'-records for entry- and (maybe) language-level
            ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels(
                    $this->_session->userName,
                    $this->_session->userGuid,
                    $termEntryId,
                    $data['isLast'] == 'language' ? null : $language
                );
        }

        // Delete the term
        $this->entity->delete();

        // Decrement stats for term
        $diff['term'] --;

        // Update collection stats
        ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection')->updateStats($collectionId, $diff);

        // Setup 'modified' prop, so that modification info, specified in
        // entry- and (maybe) language-level panels can be updated
        $data['modified'] = $this->_session->userName . ', ' . date('d.m.Y H:i:s');

        // Flush response data
        $this->view->assign($data);
    }
}
