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
class editor_Plugins_TermTagger_TermCache {

    /**
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var editor_Models_Terminology_Models_TermModel
     */
    protected $termModel;

    /**
     * @var
     */
    protected $collectionIds;

    /**
     * @var array
     */
    protected $cached = [];

    /**
     * editor_Plugins_TermTagger_TermCache constructor.
     * @param editor_Models_Task $task
     * @param array $collectionIds
     */
    public function __construct(editor_Models_Task $task, array $collectionIds) {
        $this->task = $task;
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $this->collectionIds = $collectionIds;
    }

    /**
     * Load [termTbxId => termEntryTbxId] pairs for terms having termTbxId given by $termTbxIds arg
     *
     * @param array $termTbxIds
     * @return array
     */
    public function loadTermEntryTbxIdsByTermTbxIds(array $termTbxIds) {
        return $this->termModel->loadTermEntryTbxIdsByTermTbxIds($termTbxIds);
    }

    /**
     * Load distinct terms themselves, by their tbx ids
     *
     * @param $targetTermIds
     * @return array
     */
    public function loadDistinctByTbxIds($targetTermIds) {
        return $this->termModel->loadDistinctByTbxIds($targetTermIds);
    }

    /**
     * @return mixed
     */
    public function getTermEntryTbxId() {
        return $this->termModel->getTermEntryTbxId();
    }

    /**
     * @return mixed
     */
    public function getTerm() {
        return $this->termModel->getTerm();
    }

    /**
     * @param $sourceTermId
     * @param $collectionIds
     * @throws Zend_Db_Statement_Exception
     */
    public function loadByMid($sourceTermId, $collectionIds) {

        // If we're inside a import worker thread
        if (ZFEXTENDED_IS_WORKER_THREAD) {

            // Fetch [termTbxId => ['termEntryTbxId' => termEntryTbxId, 'term' => term]] pairs for all terms within given $collectionIds
            $this->cached['loadByMid'] = $this->cached['loadByMid'] ?? Zend_Db_Table::getDefaultAdapter()->query('
                SELECT `termTbxId`, `termEntryTbxId`, `term` 
                FROM `terms_term` 
                WHERE `collectionId` IN (' . join(',', $collectionIds) . ')
            ')->fetchAll(PDO::FETCH_UNIQUE);

            // If data for given $sourceTermId found
            if (isset($this->cached['loadByMid'][$sourceTermId])) {

                // Set the data to be used furter
                $this->termModel->setTermEntryTbxId($this->cached['loadByMid'][$sourceTermId]['termEntryTbxId']);
                $this->termModel->setTerm          ($this->cached['loadByMid'][$sourceTermId]['term']);

            // Else throw not found exception
            } else {
                $this->termModel->notFound('termTbxId', $sourceTermId);
            }

        // Else do as we did
        } else {
            $this->termModel->loadByMid($sourceTermId, $collectionIds);
        }
    }

    /**
     * Returns term-informations for a group id given by $termEntryTbxId arg
     *
     * @param $collectionIds
     * @param $termEntryTbxId
     * @param $targetFuzzyLanguages
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getAllTermsOfGroup($collectionIds, $termEntryTbxId, $targetFuzzyLanguages) {

        // If we're inside a import worker thread
        if (ZFEXTENDED_IS_WORKER_THREAD) {

            // Fetch [
            //  termEntryTbxId1 => [
            //    ['termTbxId' => termTbxId1, 'term' => term1],
            //    ['termTbxId' => termTbxId2, 'term' => term2],
            // ],
            //  termEntryTbxId2 => [
            //    ['termTbxId' => termTbxId3, 'term' => term3],
            //    ['termTbxId' => termTbxId4, 'term' => term4],
            // ] data for all terms within given $collectionIds and $targetFuzzyLanguages
            $this->cached['getAllTermsOfGroup'] = $this->cached['getAllTermsOfGroup'] ?? Zend_Db_Table::getDefaultAdapter()->query('
                SELECT `termEntryTbxId`, `termTbxId`, `term` 
                FROM `terms_term` 
                WHERE TRUE
                  AND `collectionId` IN (' . join(',', $collectionIds) . ') 
                  AND `languageId`   IN (' . join(',', $targetFuzzyLanguages) . ')
            ')->fetchAll(PDO::FETCH_GROUP);

            // Pick the data to be used furter
            return $this->cached['getAllTermsOfGroup'][$termEntryTbxId];

        // Else do as we did
        } else {
            return $this->termModel->getAllTermsOfGroup($collectionIds, $termEntryTbxId, $targetFuzzyLanguages);
        }
    }

    /**
     * Find first homonym for the given $term, stored under any of termEntries
     * identified by $termEntryTbxIds arg, and having language from $languageIds list
     *
     * @param string $term
     * @param array $termEntryTbxIds
     * @param array $languageIds
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function findHomonym(string $term, array $termEntryTbxIds, array $languageIds) {

        // If we're inside a import worker thread
        if (ZFEXTENDED_IS_WORKER_THREAD) {

            // Fetch [term => termTbxId] pairs for all terms within given $termEntryTbxIds and $languageIds
            $this->cached['findHomonym'] = $this->cached['findHomonym'] ?? Zend_Db_Table::getDefaultAdapter()->query('
                SELECT DISTINCT `term`, `termTbxId` 
                FROM `terms_term` 
                WHERE 1
                  AND `termEntryTbxId` IN ("'. join('","', $termEntryTbxIds) . '") 
                  AND `languageId` IN (' . join(',', $languageIds) . ')
            ')->fetchAll(PDO::FETCH_KEY_PAIR);

            // Pick homonym
            return $this->cached['findHomonym'][$term];

        // Else do as we did
        } else {
            return $this->termModel->findHomonym($term, $termEntryTbxIds, $languageIds);
        }
    }
}
