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

        $termCollection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);

        // If current user has 'termPM_allClients' role, it means all collections are accessible
        // Else we should apply collectionsIds-restriction everywhere, so get accessible collections
        $this->collectionIds =
            $this->isAllowed('editor_term', 'anyCollection')
                ?: $termCollection->getAccessibleCollectionIds(editor_User::instance()->getModel());
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
        $canPropose  = $this->isAllowed('editor_term', 'propose');
        $canReview   = $this->isAllowed('editor_term', 'review');
        $canFinalize = $this->isAllowed('editor_term', 'finalize');

        // Setup a flag indicating whether current user can edit current term
        $editable = $canChangeAny
            || ($canPropose  && $this->entity->getCreatedBy() == $this->_session->id)
            || ($canReview   && $isUnprocessed)
            || ($canFinalize && $isProvisionallyProcessed);

        // If not allowed - flush failure
        if (!$editable) $this->jflush(false, 'This term is not editable');

        // Get request params
        $params = $this->getRequest()->getParams();

        // Update proposal
        $this->entity->setProposal(trim($params['proposal'] ?? ''));
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

    /**
     * Transfer terms for translation as task of type 'termtranslation'
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function transferAction() {

        // Make sure execution won't stop on request aborted
        ignore_user_abort(1);

        // Measure time spent to get here
        mt('we\'re in transferAction');

        // Check params
        $_ = $this->jcheck([
            'clientId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'LEK_customer'
            ],
            'projectName' => [
                'req' => true,
                'rex' => '~[^ ]+~',
            ],
            'sourceLang' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'LEK_languages'
            ],
            'targetLang' => [
                'req' => true,
                'rex' => 'int11list',
                'key' => 'LEK_languages*'
            ],
            'terms' => [
                'req' => true,
                'fis' => 'all,none'
            ],
            'except' => [
                'req' => $this->getParam('terms') == 'none',
                'rex' => 'int11list'
            ],
            'translated,definition' => [
                'req' => true,
                'rex' => 'bool'
            ]
        ]);

        // Make sure sourceLang is not among targetLangs
        if (in_array($_['sourceLang']['id'], array_column($_['targetLang'], 'id'))) {
            $this->jflush(false, 'Source language should NOT be in the list of target languages');
        }

        // If we're in ordinary selection mode,
        // e.g. we have the ids of all terms we need to transfer given by request's except-param
        if ($this->getParam('terms') == 'none') {

            // Load terms data by ids list, given in except-param
            $_ += $this->jcheck(['except' => ['key' => 'terms_term*']]);

            // Get distinct language-values and trim sublanguage-values from them
            foreach(array_unique(array_column($_['except'], 'language')) as $language) {
                $languageA[explode('-', $language)[0]] = true;
            }

            // Make sure all terms belong to 1 distinct language, that is equal to sourceLang-param
            $this->jcheck([
                'distinctQty' => ['eql' => 1],
                'language'    => ['eql' => $_['sourceLang']['rfc5646']]
            ], [
                'distinctQty' => count($languageA),
                'language'    => array_keys($languageA)[0]
            ]);

            // Get distinct collectonId-values
            $collectionIdA = array_unique(array_column($_['except'], 'collectionId'));

            // Make sure all terms belongs to accessible collections
            if (is_array($this->collectionIds))
                if (array_diff($collectionIdA, $this->collectionIds))
                    $this->jflush(false, 'Some of selected terms belongs to inaccessible TermCollections');

            // Get shared customers
            $sharedCustomerIdA = ZfExtended_Factory
                ::get('editor_Models_LanguageResources_CustomerAssoc')
                ->getSharedCustomers($collectionIdA);

            // If no shared customers found
            if (!$sharedCustomerIdA) {
                $this->jflush(false, 'Selected terms should belong to at least 1 shared customer');
            }

            // Make sure value of given clientId-param is in the list of shared customers
            $this->jcheck([
                'clientId' => [
                    'fis' => join(',', $sharedCustomerIdA) ?: 'inaccessible'
                ]
            ]);
        }

        // Measure time spent
        mt('request params validation');

        // If terms-param is 'none'
        if ($this->getParam('terms') == 'none') {

            // Get termIds from except-param
            $termIds = explode(',', $this->getParam('except'));

        // Else if it is 'all'
        } else {

            // 2nd arg required to be passed by reference (see below)
            $total = false;

            // Fetch ids of ALL terms matching last search, excluding ids given by 'except'-param
            $termIds = ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_TermModel')
                ->searchTermByParams(
                    $_SESSION['lastParams'] + ['except' => $this->getParam('except')],
                    $total
                );
        }

        // Instantiate terms transfer util class
        /** @var editor_Plugins_TermPortal_Util_Transfer $transfer */
        $transfer = ZfExtended_Factory::get('editor_Plugins_TermPortal_Util_Transfer');

        // Prepare data for transfer
        $nothingToTranslate = !$transfer->prepare(
            customerId: $this->getParam('clientId'),
            projectName: $this->getParam('projectName'),
            sourceLang: $_['sourceLang']['id'],
            targetLangs: array_column($_['targetLang'], 'id'),
            termIds: $termIds,
            skipTranslated: !$this->getParam('translated'),
            skipDefinition: !$this->getParam('definition'),
        );

        // Measure time spent
        mt('exporting selected terms as raw tbx-files');

        // If all selected terms do already have translations for all selected target languages
        if ($nothingToTranslate) {
            $this->jflush(false, 'All selected terms do already have translations for all selected target languages');
        }

        // Make sequence of API calls to walk through task creation steps
        if (!$steps = $transfer->doSteps(true)) {
            $this->jflush(false, 'Something went wrong');
        }

        // Flush responses
        $this->view->assign($steps);  // i(mt(true), 'a');
    }
}
