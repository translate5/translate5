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

namespace MittagQI\Translate5\Terminology;

use editor_Models_Languages;
use editor_Models_TermCollection_TermCollection;
use Zend_Cache_Exception;
use ZfExtended_Factory;

/**
 * Search the current term collection with given query string.
 * All fuzzy languages will be included in the search.('en' as search language will result with search using 'en','en-US','en-GB' etc)
 * Result will be listed only if there is matching term in the opposite language:
 * Example if there is a match for term in source(de), and in the same term entry, there is term in the opposite language(en), than this */
class SearchCollection
{
    /***
     * Search in source constant string
     */
    public const SEARCH_SOURCE = 'source';

    /***
     * Search in target constant string
     */
    public const SEARCH_TARGET = 'target';

    /***
     * Search for source or target term. Default to source
     * @var string
     */
    private string $searchField = self::SEARCH_SOURCE;

    /***
     * All fuzzy source langauges
     * @var array
     */
    private array $sourceLangauges;

    /***
     * All fuzzy target langauges
     * @var array
     */
    private array $targetLangauges;

    /***
     * Use wildcards when searching for the term.
     * @var bool
     */
    private bool $useWildcard;

    /**
     * Search query
     */
    private string $query;

    /**
     * @param int $collectionId collectionId where should be searched
     * @param int $sourceLang query string should match all terms with this sourceLang
     * @param int $targetLang the resul terms will be in this targetLanguage
     * @throws Zend_Cache_Exception
     */
    public function __construct(
        private int $collectionId,
        private int $sourceLang,
        private int $targetLang
    ) {
        $languageModel = ZfExtended_Factory::get(editor_Models_Languages::class);

        // get source and target language fuzzy
        $this->sourceLangauges = $languageModel->getFuzzyLanguages($sourceLang, 'id', true);
        $this->targetLangauges = $languageModel->getFuzzyLanguages($targetLang, 'id', true);
    }

    /***
     * Search terms in the term collection
     *
     * @param string $query
     * @param bool $useWildcard
     * @return array
     */
    public function search(string $query, bool $useWildcard = false): array
    {
        $this->query = $query;
        $this->useWildcard = $useWildcard;

        $entries = $this->findEntries();
        if (empty($entries)) {
            return [];
        }

        $termEntryTbxIds = [];
        $termEntryTbxIdSearch = [];
        foreach ($entries as $res) {
            $termEntryTbxIds[] = $res['termEntryTbxId'];
            //collect the searched terms, so thy are merged with the results
            if (! isset($termEntryTbxIdSearch[$res['termEntryTbxId']])) {
                $termEntryTbxIdSearch[$res['termEntryTbxId']] = [];
            }
            $termEntryTbxIdSearch[$res['termEntryTbxId']][] = [
                'term' => $res['term'],
                'languageId' => $res['languageId'],
            ];
        }

        $targetResults = $this->findTargetTerms($termEntryTbxIds);

        //merge the searched terms with the result
        foreach ($targetResults as &$single) {
            $single['default' . $this->searchField] = '';
            if (! empty($termEntryTbxIdSearch[$single['termEntryTbxId']])) {
                $single['default' . $this->searchField] = $termEntryTbxIdSearch[$single['termEntryTbxId']][0]['term'];
                $single['default' . $this->searchField . 'LanguageId'] = $termEntryTbxIdSearch[$single['termEntryTbxId']][0]['languageId'];
            }
        }

        return $targetResults;
    }

    /**
     * Search for the matching term entries for the searched term.
     */
    private function findEntries(): array
    {
        $collection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
        $db = $collection->db;

        // Get the langauges base on term search source
        $langauges = $this->searchField === self::SEARCH_SOURCE ? $this->sourceLangauges : $this->targetLangauges;

        $compareWith = '=';

        // in case we use wildcards, we should search the terms table with LIKE and in addition, we should escape all
        // mysql wildcards in case the search string contains such
        if ($this->useWildcard) {
            $compareWith = 'LIKE';

            // escape the wildcards when searching with wildcards
            $this->query = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $this->query);
            $this->query = '%' . $this->query . '%';
        }

        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from('terms_term')
            /* @phpstan-ignore-next-line since removed anyway in a short */
            ->where(FEATURE_TRANSLATE_4673_ENABLE
                ? ('term ' . $compareWith . '?')
                : ('lower(term) ' . $compareWith . ' lower(?) COLLATE utf8mb4_bin'), $this->query)
            ->where('collectionId = ?', $this->collectionId)
            ->where('languageId IN(?)', $langauges)
            ->group('termEntryTbxId');

        return $db->fetchAll($s)->toArray();
    }

    /**
     * Search for terms in the target language for given term entry tbx id
     */
    private function findTargetTerms(array $termEntryTbxIds): array
    {
        $collection = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
        $db = $collection->db;

        $langauges = $this->searchField === self::SEARCH_SOURCE ? $this->targetLangauges : $this->sourceLangauges;

        // fill all terms in the opposite field of the matched term results
        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from([
                't' => 'terms_term',
            ])
            ->joinLeft([
                'ta' => 'terms_attributes',
            ], 'ta.termId = t.id AND ta.type = "processStatus"', ['ta.type AS processStatusAttribute', 'ta.value AS processStatusAttributeValue'])
            ->where('t.termEntryTbxId IN(?)', $termEntryTbxIds)
            ->where('t.languageId IN(?)', $langauges)
            ->where('t.collectionId = ?', $this->collectionId);

        return $db->fetchAll($s)->toArray();
    }

    public function getSearchField(): string
    {
        return $this->searchField;
    }

    public function setSearchField(string $searchField): void
    {
        $this->searchField = $searchField;
    }
}
