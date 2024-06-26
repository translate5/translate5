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
 * regarding the memory usage on load of all terms:
    [08-Sep-2021 20:54:23 Europe/Vienna] MEM 6 12 MB
 */
class editor_Models_Terminology_BulkOperation_Term extends editor_Models_Terminology_BulkOperation_Abstract
{
    /**
     * Since the langset guids are only persisted implicitly in the terms table, we just them here too
     */
    protected array $langsetGuids = [];

    /**
     * inserted tbx guids, so that their ids can be loaded afterwards
     */
    protected array $insertedTbxIds = [];

    /**
     * Mapping of each term by content and language to its termId and termEntryId
     */
    protected array $allTerms = [];

    /**
     * @var editor_Models_Terminology_Models_TermModel
     */
    protected $model;

    /**
     * @var editor_Models_Terminology_TbxObjects_Term
     */
    protected $importObject;

    public function __construct()
    {
        $this->model = new editor_Models_Terminology_Models_TermModel();
        $this->importObject = new editor_Models_Terminology_TbxObjects_Term();
    }

    public function freeMemory()
    {
        parent::freeMemory();
        $this->allTerms = [];
        $this->insertedTbxIds = [];
        $this->langsetGuids = [];
    }

    /**
     * process the found terms, additionally cache langsetGuids to normal processing
     */
    protected function processOneExistingRow(int $id, editor_Models_Terminology_TbxObjects_Abstract $element)
    {
        /* @var $element editor_Models_Terminology_TbxObjects_Term */
        $langsetKey = $element->termEntryTbxId . '-' . $element->language;
        if (empty($this->langsetGuids[$langsetKey])) {
            $this->langsetGuids[$langsetKey] = $element->langSetGuid;
        }
        $key = $this->getAllTermsKey($element);
        $value = $element->termTbxId . '#' . $element->termEntryTbxId . '#' . $element->guid . '#' . $element->id;
        //we need all terms, but separated for each termentry where it is contained
        if (array_key_exists($key, $this->allTerms)) {
            $this->allTerms[$key][] = $value;
        } else {
            $this->allTerms[$key] = [$value];
        }
        // we need to store the guid to for setting it later for new term attributes in existing terms
        $this->existing[$element->getCollectionKey()] = $id . '#' . $element->getDataHash() . '#' . $element->guid;

        // increment the total term count
        $this->processedCount['totalCount']++;
    }

    protected function getAllTermsKey(editor_Models_Terminology_TbxObjects_Term $term): string
    {
        return strtolower($term->language . '-' . $term->term);
    }

    /**
     * searches the given unsaved term in the allTerm list from DB (returns the first found), optionally searching only in one termEntry
     *
     * @param string|null $termEntryTbxId filtering for a specific term entry
     */
    protected function findInAllTerms(editor_Models_Terminology_TbxObjects_Term $term, string $termEntryTbxId = null): ?array
    {
        $found = $this->allTerms[$this->getAllTermsKey($term)] ?? null;
        if (empty($found)) {
            return null;
        }
        //if we do not filter for a specific termEntry, we just return the first found termEntry containing the term
        if (empty($termEntryTbxId)) {
            return explode('#', reset($found));
        }
        foreach ($found as $foundInEntry) {
            $foundInEntry = explode('#', $foundInEntry);
            if ($foundInEntry[1] === $termEntryTbxId) {
                return $foundInEntry;
            }
        }

        return null;
    }

    /**
     * returns the fields to be loaded
     */
    protected function getFieldsToLoad(): array
    {
        $fields = parent::getFieldsToLoad();
        $fields[] = 'termEntryId';
        $fields[] = 'termEntryTbxId';
        $fields[] = 'termTbxId';
        $fields[] = 'language';
        $fields[] = 'langSetGuid';

        return $fields;
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Term $elementObject
     */
    protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject)
    {
        $elementObject->langSetGuid = $elementObject->parentLangset->langSetGuid;
        $elementObject->termEntryId = $elementObject->parentEntry->id;
        $elementObject->termEntryGuid = $elementObject->parentEntry->entryGuid;
        $elementObject->termEntryTbxId = $elementObject->parentEntry->termEntryTbxId;
    }

    /**
     * returns a langSetGuid to a term entry id and language. Since only stored in terms DB, we load it from Term Namespace
     */
    public function getExistingLangsetGuid(string $termEntryTbxGuid, string $language): ?string
    {
        return $this->langsetGuids[$termEntryTbxGuid . '-' . $language] ?? null;
    }

    /**
     * saves the term as described, but additionally fetches the inserted ids for new terms
     * @throws editor_Models_Terminology_Import_Exception|Zend_Db_Table_Exception
     */
    public function createOrUpdateElement()
    {
        if (empty($this->toBeProcessed)) {
            return;
        }

        $this->insertedTbxIds = []; // insertedTbxIds is filled inside parent createOrUpdateElement

        //find the collection ID from the to be processed terms
        $collectionId = reset($this->toBeProcessed)->collectionId ?? 0;

        parent::createOrUpdateElement();

        if (empty($collectionId)) {
            //this may not happen!
            throw new editor_Models_Terminology_Import_Exception('E1356', [
                'msg' => 'No collection ID found in inserted term',
            ]);
        }

        // we have to bulk update the timestamp of the existing terms, otherwise they are deleted with deleteTermsOlderThanCurrentImport = true
        $this->updateEditTimestamp($collectionId);

        if (empty($this->insertedTbxIds)) {
            // no inserts performed
            return;
        }

        // must be done like this since the where/quote functions will not cast int only keys as strings
        // ex:  for termTbxId 1 and 4 the query will be : WHERE (termTbxId in (1, 4))
        //      instead of  WHERE (termTbxId in ("1", "4"))
        $keys = implode('","', array_keys($this->insertedTbxIds));

        //TODO: what is the point of this code ? Validation only or ? since the attar is reset right after it is filled up
        //fetch the newly inserted IDs
        $s = $this->model->db->select()
            ->from($this->model->db, ['id', 'termTbxId', 'guid'])
            ->where('termTbxId in ("' . $keys . '")')
            ->where('collectionId = ?', $collectionId);
        $ids = $this->model->db->fetchAll($s)->toArray();

        foreach ($ids as $id) {
            if (empty($this->insertedTbxIds[$id['termTbxId']])) {
                //this may not happen!
                throw new editor_Models_Terminology_Import_Exception('E1356', [
                    'msg' => 'No ID to an inserted term found!',
                    'collectionId' => $collectionId,
                    'termTbxId' => $id['termTbxId'],
                ]);
            }
            //set the ID in the referenced term tbx instance for further reuse via getParent
            $this->insertedTbxIds[$id['termTbxId']] -> id = $id['id'];
        }
        $this->insertedTbxIds = [];
    }

    /**
     * bulk update the edit timestamp of not changed terms (deletion prevention)
     */
    protected function updateEditTimestamp(int $collectionId)
    {
        if (empty($this->unchangedIds)) {
            return;
        }
        $this->model->db->update([
            'updatedAt' => NOW_ISO,
        ], [
            'collectionId = ?' => $collectionId,
            'id in (?)' => $this->unchangedIds,
        ]);
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Term $element
     */
    protected function prepareSqlInsert(editor_Models_Terminology_TbxObjects_Abstract $element, int $count, array &$sqlInsert): string
    {
        $this->insertedTbxIds[$element->termTbxId] = $element;

        return parent::prepareSqlInsert($element, $count, $sqlInsert);
    }

    /**
     * Performs the default merging (by TBX IDs if matched, and advanced merging if enabled)
     */
    public function mergeTerms(editor_Models_Terminology_BulkOperation_TermEntry $bulkEntry, bool $mergeTerms)
    {
        $payload = [];

        //1. check if termEntry to be added is already in DB
        $existingId = $bulkEntry->findExisting($bulkEntry->getCurrentEntry(), $payload);
        $entryExistsByTbxId = ! is_null($existingId);
        $foundParentEntryTbxId = null;

        /* @var editor_Models_Terminology_TbxObjects_Term $term */
        foreach ($this->toBeProcessed as $term) {
            $termId = $this->findExisting($term);
            $termExistsByTbxIds = ! is_null($termId);

            //termEntry with the parsed termEntryTbxID exists in DB
            // and term with the parsed termTbxID exists in DB
            if ($entryExistsByTbxId && $termExistsByTbxIds) {
                // those values are used by the term attributes and must be set here
                $term->id = $termId;
                // we have direct match from above, base on that, get the term from the existing pull, and set the guid from there
                // setting the guid is required when new attributes are inserted for terms which are merged
                $existing = $this->existing[$term->getCollectionKey()];
                $existing = explode('#', $existing);
                $term->guid = $existing[2];

                // term data is merged automatically via createOrUpdateElement later on
                continue;
            }

            // 2. if we have found a termEntry, the term is tried to be merged inside that termEntry
            // into existing terms of same language and term content, regardless of $mergeTerms setting
            if (($entryExistsByTbxId && ! $termExistsByTbxIds) || $foundParentEntryTbxId !== null) {
                $foundLocalTerm = $this->findInAllTerms($term, $bulkEntry->getCurrentEntry()->termEntryTbxId);
            } elseif ($mergeTerms) {
                $foundLocalTerm = $this->findInAllTerms($term);
            }

            // 3. if there is a matching term in the same termEntry / or in all terms, we reuse its TBX IDs so that it is merged into that term then
            //  a foundLocalTerm can only be used, if it is the first match of a term in termEntry, otherwise the already found termEntry must be reused
            if (! empty($foundLocalTerm) && ($foundParentEntryTbxId === null || $foundParentEntryTbxId === $foundLocalTerm[1])) {
                $term->id = $foundLocalTerm[3];
                $term->guid = $foundLocalTerm[2];
                $foundParentEntryTbxId = $foundLocalTerm[1];
                $term->termTbxId = $foundLocalTerm[0];
                // term data is merged automatically via createOrUpdateElement then
            }

            // reuse the termEntryTbxID found in the above term or in a previous term of the same entry
            if (! empty($foundParentEntryTbxId)) {
                $term->termEntryTbxId = $foundParentEntryTbxId;
                $term->parentEntry->termEntryTbxId = $foundParentEntryTbxId;
                $term->parentLangset->langSetGuid = $this->getExistingLangsetGuid($term->termEntryTbxId, $term->language);
            }

            // if no existing term was found, it is just added as new one (either with reused entry TBX ID or new TBX ID)
        }
    }
}
