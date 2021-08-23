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
     */
    public function postAction() {

        // Get request params
        $params = $this->getRequest()->getParams();

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid'
            ],
        ], $params);

        // Validate params
        $_ = editor_Utils::jcheck([
            'collectionId,languageId' => [
                'req' => true,                                                      // required
                'rex' => 'int11'                                                    // regular expression preset key or raw expression
            ],
            'collectionId' => [
                'key' => 'LEK_languageresources',                                   // points to existing record in a given db table
            ],                                                                      // $_['collectionId'] will contain that record
            'languageId' => [
                'key' => 'LEK_languages',                                           // points to existing record in a given db table
            ],                                                                      // $_['languageId'] will contain that record
            'termEntryId' => [
                'rex' => 'int11',
                'key' => 'terms_term_entry',                                        // points to existing record in a given db table
            ],                                                                      // $_['termEntryId'] will contain that record
            'term' => [
                'req' => true,                                                      // required
                'rex' => '~[^\s]~'                                               // regular expression preset key or raw expression
            ],
            'language' => [
                'req' => true,                                                      // required
                'rex' => 'rfc5646'                              // regular expression preset key or raw expression
            ],
            'note' => [
                'req' => Zend_Registry::get('config')                               // required
                    ->runtimeOptions->termportal->commentAttributeMandatory,
                'rex' => 'varchar255s'                                              // regular expression preset key or raw expression
            ]
        ], $params);

        // Trim whitespaces from term
        $params['term'] = trim($params['term']);

        // If termEntryId is given in params and execution reached this line
        if ($params['termEntryId']) {

            // It means that we have fetch data from `terms_term_entry` table by $params['termEntryId'],
            // so here we just pick props we need from that data, as we need those to create `terms_term` entry
            $termEntryId = $params['termEntryId'];
            $termEntryTbxId = $_['termEntryId']['termEntryTbxId'];
            $termEntryGuid = $_['termEntryId']['entryGuid'];

            // Insert termEntry-data and get termEntryId
        } else {

            // Instantiate termEntry-model instance
            /** @var editor_Models_Terminology_Models_TermEntryModel $termEntryR */
            $termEntryR = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');

            // Apply data
            $termEntryR->init([
                'collectionId' => $params['collectionId'],
                'termEntryTbxId' => $termEntryTbxId = 'id' . ZfExtended_Utils::uuid(),
                'entryGuid' => $termEntryGuid = ZfExtended_Utils::uuid(),
                'isCreatedLocally' => 1, // Just a flag, indicating that termEntry was created manually, e.g. not via tbx import
            ]);

            // Save and get id
            $termEntryId = $termEntryR->insert(['userName' => $this->_session->userName]);
        }

        // Instantiate term-model instance
        /** @var editor_Models_Terminology_Models_TermModel $termR */
        $termR = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');

        // Apply data
        $termR->init([
            'termTbxId' => $termTbxId = 'id' . ZfExtended_Utils::uuid(),
            'collectionId' => $params['collectionId'],
            'termEntryId' => $termEntryId,
            'termEntryTbxId' => $termEntryTbxId,
            'termEntryGuid' => $termEntryGuid,
            //'langSetGuid' => $langSetGuid = '???',
            'guid' => $termGuid = ZfExtended_Utils::uuid(),
            'languageId' => $params['languageId'],
            'language' => $params['language'],
            'term' => trim($params['term']),
            'status' => 'preferredTerm', // which status should be set initially ?
            'processStatus' => $processStatus = 'unprocessed',
            //'definition' => '',
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        // Save and get id
        $termId = $termR->insert([
            'note' => trim($params['note']),
            'userName' => $this->_session->userName,
        ]);

        // Flush params so that GUI to be redirected to that newly created term
        $this->view->assign([

            // Params, that will be used to build search hash-string
            'query' => $params['term'],
            'language' => $params['languageId'],
            'collectionIds' => $params['collectionId'],

            // Param to simulate click on certain found result
            'termId' => $termId,

            //
            'termEntryId' => $termEntryId,
        ]);
    }

    /**
     * Update term (update `terms_term`.`proposal`)
     *
     * @throws ZfExtended_Mismatch
     */
    public function putAction() {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ = editor_Utils::jcheck([
            'termId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_term',
            ],
            'proposal' => [
                //'req' => true,
                'rex' => '~[^\s]~'
            ]
        ], $params);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid'
            ],
        ], $_['termId']);

        // Instantiate term model
        /** @var editor_Models_Terminology_Models_TermModel $t */
        $t = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');

        // Load term
        $t->load($params['termId']);

        // Update it's proposal
        $t->setProposal(trim($params['proposal']));
        $t->setUpdatedBy($this->_session->id);

        // Save, and pass params required to update `terms_transacgrp`-records of type 'modification' for all 3 levels
        $t->update(['userName' => $this->_session->userName]);

        // Flush response data
        $this->view->assign([
            'proposal' => $t->getProposal(),
            'processStatus' => $t->getProposal() ? 'unprocessed' : $t->getProcessStatus()
        ]);
    }

    /**
     * Delete term
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ = editor_Utils::jcheck([
            'termId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_term'
            ]
        ], $params);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid'
            ],
        ], $_['termId']);

        // Get data, that will help to detect whether this term is the last in it's termEntry or language
        $isLast_data = editor_Utils::db()->query('
            SELECT `language`, COUNT(`id`) AS `termQty` 
            FROM `terms_term` 
            WHERE `termEntryId` = ? 
            GROUP BY `language` 
            ORDER BY `language` = ? DESC 
            LIMIT 2        
        ', [$_['termId']['termEntryId'], $_['termId']['language']])->fetchAll(PDO::FETCH_KEY_PAIR);

        // Setup 'isLast' response flag
        $data['isLast'] = $isLast_data[$_['termId']['language']] < 2
            ? (count($isLast_data) < 2 ? 'entry' : 'language')
            : false;

        // If term we're going to delete is the last term within it's termEntry
        if ($data['isLast'] == 'entry') {

            // Delete `terms_images`-records and image-files found by those records
            editor_Models_Terminology_Models_AttributeModel
                ::deleteImages($_['termId']['collectionId'], $_['termId']['termEntryId']);

            /** @var editor_Models_Terminology_Models_TermEntryModel $termEntry */
            $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
            $termEntry->load($_['termId']['termEntryId']);
            $termEntry->delete();

            // Else
        } else {

            // Else this term is the last within it's language
            if ($data['isLast'] == 'language') {

                // Delete `terms_images`-records and image-files found by those records
                editor_Models_Terminology_Models_AttributeModel
                    ::deleteImages($_['termId']['collectionId'], $_['termId']['termEntryId'], $_['termId']['language']);

                // Delete `terms_transacgrp`-records for language-level
                $where = []; foreach (['termEntryId', 'language'] as $prop) $where []= '`' . $prop . '` = "' . $_['termId'][$prop] . '"';
                editor_Utils::db()->query('DELETE FROM `terms_transacgrp` WHERE ' . implode(' AND ', $where));

                // Affect `terms_transacgrp` 'modification'-records for entry- and language-level
                ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                    ->affectLevels($this->_session->userName, $_['termId']['termEntryId'], $_['termId']['language']);
            }

            /** @var editor_Models_Terminology_Models_TermModel $term */
            $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
            $term->load($_['termId']['id']);
            $term->delete();

            // Affect `terms_transacgrp` 'modification'-records for entry- and (maybe) language-level
            ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels(
                    $this->_session->userName,
                    $_['termId']['termEntryId'],
                    $data['isLast'] == 'language' ? null : $_['termId']['language']
                );
        }

        // Setup 'modified' prop, so that modification info, specified in
        // entry- and (maybe) language-level panels can be updated
        $data['modified'] = $this->_session->userName . ', ' . date('d.m.Y H:i:s');

        // Flush response data
        $this->view->assign($data);
    }
}
