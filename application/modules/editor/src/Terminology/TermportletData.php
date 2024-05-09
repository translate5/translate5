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

use editor_Models_ConfigException;
use editor_Models_Languages;
use editor_Models_Segment as Segment;
use editor_Models_SegmentFieldManager as SegmentFieldManager;
use editor_Models_Task as Task;
use editor_Models_TermCollection_TermCollection as TermCollection;
use editor_Models_Terminology_Models_AttributeDataType as AttributeDataType;
use editor_Models_Terminology_Models_ImagesModel as ImagesModel;
use editor_Models_Terminology_Models_TermModel as TermModel;
use editor_Services_ServiceAbstract;
use Zend_Cache_Exception;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;

/**
 * Loads and prepares the data for the term portlet in the segment meta panel
 */
class TermportletData
{
    private array $result;

    private array $allUsedLanguageIds;

    private array $sourceLangs;

    private array $targetLangs;

    private array $collections;

    private TermModel $termModel;

    private array $allUsedLanguages;

    /**
     * @throws Zend_Cache_Exception
     */
    public function __construct(
        private Task $task,
        private bool $termPortal
    ) {
        $this->initializeLanguages();

        $assoc = ZfExtended_Factory::get(TermCollection::class);
        $this->collections = $assoc->getCollectionsForTask($task->getTaskGuid(), false);

        $this->termModel = ZfExtended_Factory::get(TermModel::class);
    }

    /**
     * @throws Zend_Exception
     */
    public function generate(Segment $segment, string $locale): array
    {
        $this->result = [
            'locales' => [],
            'linkPortal' => $this->termPortal,
            'applicationRundir' => APPLICATION_RUNDIR,
            'termStatMap' => TermModel::getTermStatusMap(),
            'publicModulePath' => APPLICATION_RUNDIR . '/modules/' . Zend_Registry::get('module'),
            'attributeGroups' => [],
            'termGroups' => $this->getByTaskGuidAndSegment($segment),
        ];

        // Arrays to indicate termEntries having at least one term used in source/target
        $used = [
            'source' => [],
            'target' => []
        ];

        // Foreach termEntry
        foreach($this->result['termGroups'] as $termEntryId => $termA) {
            // Foreach term inside termEntry
            foreach ($termA as $term) {
                // If it's a term used in source or target
                if ($term->used) {
                    // Setup a flag indicating this termEntry has at least one such term
                    $used[$term->isSource ? 'source' : 'target'][$termEntryId] = true;
                }
            }
        }

        // Array of unused target terms grouped by termEntryId, but only for termEntries having at least one used source term
        $unusedTarget = [];

        // Foreach termEntry having at least one term used in source
        foreach ($this->result['termGroups'] as $termEntryId => $termA) {
            if (isset($used['source'][$termEntryId])) {
                // Collect unused target terms in a way that will allow us to swap used-flag from one term to another
                foreach ($termA as $idx => $term) {
                    if (! $term->isSource && ! $term->used) {
                        $unusedTarget[$termEntryId][$term->term] = $idx;
                    }
                }
            }
        }

        // Foreach termEntry that has term(s) used in target, but has no term(s) used in source
        foreach ($this->result['termGroups'] as $termEntryId_was => $termA) {
            if (! isset($used['source'][$termEntryId_was])
                || isset($used['target'][$termEntryId_was])) {
                // Foreach term used in target
                foreach ($termA as $idx_was => $term) {
                    if (! $term->isSource && $term->used) {
                        // Check whether we have homonym (in some termEntry having term(s) used in source)
                        // If yes - mark it as used instead of current term
                        foreach ($unusedTarget as $termEntryId_now => $termA) {
                            if (is_int($idx_now = $termA[$term->term] ?? false)) {
                                $this->result['termGroups'][$termEntryId_was][$idx_was]->used = false;
                                $this->result['termGroups'][$termEntryId_now][$idx_now]->used = true;

                                break;
                            }
                        }
                    }
                }
            }
        }

        $this->result['noTerms'] = empty($this->result['termGroups']);

        $termEntryIds = [];
        foreach ($this->result['termGroups'] as $termGroup) {
            foreach ($termGroup as $term) {
                $termEntryIds[] = $term->termEntryId;
            }
        }

        if (! empty($termEntryIds)) {
            $this->result['attributeGroups'] = $this->getAttributesGroups(array_filter($termEntryIds), $locale);
        }

        $this->result['flags'] = $this->allUsedLanguages;

        return $this->result;
    }

    /**
     * Returns term-informations for $segmentId in $taskGuid.
     * Includes assoziated terms corresponding to the tagged terms
     *
     * @throws Zend_Db_Statement_Exception
     * @throws editor_Models_ConfigException
     */
    private function getByTaskGuidAndSegment(Segment $segment): array
    {
        if (empty($this->collections) || ! $this->task->getTerminologie()) {
            return [];
        }

        $termIds = $this->getTermMidsFromTaskSegment($segment);
        $result = $this->getSortedTermGroups($termIds);
        $result = $this->appendHomonyms($result);
        if (empty($result)) {
            return [];
        }

        return $this->termModel->sortTerms($result);
    }

    /**
     * Append homonym data to termportlet data
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function appendHomonyms(array $termportletData): array
    {
        // Get values of processStatus, that homonyms should have in order to be shown
        $processStatusA = $this->task->getConfig()->runtimeOptions->terminology->usedTermProcessStatus->toArray();

        // Array to collect terms (currently used inside the segment), that we need to find homonyms for
        $used = [];

        // Foreach [termEntryTbxId => terms array] pair
        foreach ($termportletData as $termA) {
            // Foreach stdClass-object representing the term inside terms array
            foreach ($termA as $termO) {
                // If it's used in segment
                if ($termO->used) {
                    // Prepare type
                    $type = $termO->isSource ? 'source' : 'target';

                    // Append [termEntryId => term] pair for used term, grouped by source/target
                    $used[$type][$termO->termEntryId] = $termO->term;
                }
            }
        }

        // Get [id => color] pairs for term collections assigned to current task
        $collectionColorA = array_column($this->collections, 'color', 'id');

        // Get comma-separated list of ids of those term collections as well
        $collectionIds = join(',', array_column($this->collections, 'id'));

        // Shortcut to db adapter
        $db = \Zend_Db_Table::getDefaultAdapter();

        // Foreach ['source/target' => [[termEntryId => term], ...]] pairs
        foreach ($used as $type => $termByTermEntryIdA) {
            // Prepare parts of WHERE clause to find termEntries where homonyms are located, if any
            $where = [
                'sameTerm' => $db->quoteInto('`term` IN (?)', array_unique($termByTermEntryIdA)),
                'sameLanguageId' => $db->quoteInto('`languageId` IN (?)', $this->{$type . 'Langs'}),
                'otherTermEntryId' => $db->quoteInto('`termEntryId` NOT IN (?)', array_keys($termByTermEntryIdA)),
            ];

            // As long as we might already have some of homonyms within the current termportlet data we have to
            // make sure that now we fetch termEntryId-array only for homonyms other than we might already have, if any,
            // so those will be the homonyms that are surely unused so far in the current segment
            $homonymTermEntryIdA = $db->query("
                SELECT `termEntryId`
                FROM `terms_term` 
                WHERE {$where['sameTerm']} 
                  AND {$where['sameLanguageId']}
                  AND {$where['otherTermEntryId']}
                  AND `collectionId` IN ($collectionIds)
                  AND `processStatus` = 'finalized'
            ")->fetchAll(\PDO::FETCH_COLUMN);

            // If we have something
            if ($homonymTermEntryIdA) {
                // Prepare parts of WHERE clause to find homonyms themselves
                $where = [
                    'homonymTermEntryId' => $db->quoteInto('`termEntryId` IN (?)', array_unique($homonymTermEntryIdA)),
                    'allUsedLanguageIds' => $db->quoteInto('`languageId` IN (?)', $this->allUsedLanguageIds),
                    'processStatus' => $db->quoteInto('`processStatus` IN (?)', $processStatusA),
                ];

                // Fetch homonyms and along with their translations as stdClass-instances, all grouped by their termEntryId
                $homonymA = $db->query("
                    SELECT
                      `t`.`termEntryTbxId`,
                      `t`.*
                    FROM `terms_term` `t`
                    WHERE {$where['homonymTermEntryId']}
                      AND {$where['allUsedLanguageIds']}
                      AND {$where['processStatus']}
                      AND `collectionId` IN ($collectionIds)
                ")->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_OBJ);

                // Apply auxiliary props required by termportlet renderer
                foreach ($homonymA as &$termA) {
                    foreach ($termA as $termO) {
                        // Setup isSource-flag
                        $termO->isSource = in_array($termO->languageId, $this->sourceLangs);

                        // Setup rtl-flag
                        $termO->rtl = $this->allUsedLanguages[strtolower($termO->language)]['rtl'] ? 1 : '';

                        // Setup other props to be fed to termportlet client-side renderer
                        $termO->used = false;
                        $termO->transFound = false;
                        $termO->collectionColor = $collectionColorA[$termO->collectionId]
                            ?? editor_Services_ServiceAbstract::DEFAULT_COLOR;
                    }
                }

                // Append source/target homonyms to result
                $termportletData += $homonymA;
            }
        }

        // Return
        return $termportletData;
    }

    /**
     * returns all term mids of the given segment in a multidimensional array.
     * First level contains source or target (the fieldname)
     * Second level contains a list of arrays with the found mids and div tags,
     * the div tag is needed for transfound check
     */
    private function getTermMidsFromTaskSegment(Segment $segment): array
    {
        $fieldManager = ZfExtended_Factory::get(SegmentFieldManager::class);
        $fieldManager->initFields($this->task->getTaskGuid());

        //Currently only terminology is shown in the first fields see also TRANSLATE-461
        if ($this->task->getEnableSourceEditing()) {
            $sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
        } else {
            $sourceFieldName = $fieldManager->getFirstSourceName();
        }
        $sourceText = $segment->get($sourceFieldName);

        $targetFieldName = $fieldManager->getEditIndex($fieldManager->getFirstTargetName());
        $targetText = $segment->get($targetFieldName);

        //tbxid should be sufficient as distinct identifier of term tags
        $getTermIdRegEx = '/<div[^>]+data-tbxid="([^"]*)"[^>]*>/';
        preg_match_all($getTermIdRegEx, $sourceText, $sourceMatches, PREG_SET_ORDER);
        preg_match_all($getTermIdRegEx, $targetText, $targetMatches, PREG_SET_ORDER);

        if (empty($sourceMatches) && empty($targetMatches)) {
            return [];
        }

        return [
            'source' => $sourceMatches,
            'target' => $targetMatches,
        ];
    }

    /**
     * Returns a multidimensional array.
     * 1. level: keys: groupId, values: array of terms grouped by groupId
     * 2. level: terms of group groupId
     *
     * @param array $termIds as 2-dimensional array('source' => array(), 'target' => array())
     * @throws Zend_Db_Statement_Exception
     * @throws editor_Models_ConfigException
     */
    private function getSortedTermGroups(array $termIds): array
    {
        if (empty($termIds)) {
            return [];
        }

        $sourceIds = array_column($termIds['source'], 1);
        $targetIds = array_column($termIds['target'], 1);
        $transFoundSearch = array_column($termIds['source'], 0, 1) + array_column($termIds['target'], 0, 1);
        $allIds = array_merge($sourceIds, $targetIds);

        // show only the terms with the config staus values
        $statuses = $this->task->getConfig()->runtimeOptions->terminology->usedTermProcessStatus->toArray();

        if (empty($statuses)) {
            $statuses = [];
        }

        // Prepare comma-separated list, enclosed with single-quotes
        $sourceIds = $this->termModel->db->getAdapter()->quoteInto('?', join(',', array_reverse($sourceIds)));

        // Fetch terms
        $sql = $this->termModel->db->getAdapter()->select()
            ->from([
                't1' => 'terms_term',
            ], ['t2.*'])
            ->distinct()
            ->joinLeft(
                [
                    't2' => 'terms_term',
                ],
                't1.termEntryId = t2.termEntryId AND t1.collectionId = t2.collectionId',
                null
            )
            ->join([
                'l' => 'LEK_languages',
            ], 't2.languageId = l.id', 'rtl')
            ->where('t1.collectionId IN(?)', array_column($this->collections, 'id'))
            ->where('t1.termTbxId IN(?)', $allIds)
            ->where('t1.languageId IN (?)', $this->allUsedLanguageIds)
            ->where('t2.languageId IN (?)', $this->allUsedLanguageIds)
            ->order(["
                GREATEST (
                    FIND_IN_SET(t1.termTbxId, $sourceIds),
                    FIND_IN_SET(t2.termTbxId, $sourceIds)
                ) DESC
            "]);

        if (! empty($statuses)) {
            $sql->where('t1.processStatus in (?)', $statuses);
            $sql->where('t2.processStatus in (?)', $statuses);
        }

        $terms = $this->termModel->db->getAdapter()->fetchAll($sql);

        $termGroups = [];

        $collectionColors = array_column($this->collections, 'color', 'id');

        foreach ($terms as $term) {
            $term = (object) $term;

            if (! isset($termGroups[$term->termEntryTbxId])) {
                $termGroups[$term->termEntryTbxId] = [];
            }

            $term->used = in_array($term->termTbxId, $allIds);
            $term->isSource = in_array($term->languageId, $this->sourceLangs);
            $term->transFound = false;
            if ($term->used) {
                $term->transFound = preg_match('/class="[^"]*transFound[^"]*"/', $transFoundSearch[$term->termTbxId]);
            }

            $term->rtl = (bool) $term->rtl;

            $term->collectionColor = $collectionColors[$term->collectionId]
                ?? editor_Services_ServiceAbstract::DEFAULT_COLOR;

            $termGroups[$term->termEntryTbxId][] = $term;
        }

        return $termGroups;
    }

    /**
     * Get all attributes for given term entries grouped by attribute type (language, entry and term)
     * @throws Zend_Db_Statement_Exception
     */
    private function getAttributesGroups(array $termEntries, string $locale): array
    {
        $sql = $this->termModel->db->getAdapter()->select()
            ->from([
                't1' => 'terms_attributes',
            ], ['t1.*'])
            ->where('t1.termEntryId IN (?)', $termEntries)
            ->where('(t1.language IN (?) OR ISNULL(t1.language))', array_keys($this->allUsedLanguages));

        $attributes = $this->termModel->db->getAdapter()->fetchAll($sql);

        $template = [];
        $template['entry'] = [];
        $template['language'] = [];
        $template['term'] = [];

        $dataTypeLocale = ZfExtended_Factory::get(AttributeDataType::class);
        $locales = $dataTypeLocale->loadAllWithTranslations($locale);

        $images = ZfExtended_Factory::get(ImagesModel::class);

        foreach ($attributes as $attribute) {
            $attribute['nameTranslated'] = $locales[$attribute['dataTypeId']] ?: $attribute['elementName'];
            if ($attribute['type'] == 'figure') {
                $target = $attribute['target'];
                if ($srcA = $images->getImagePathsByTargetIds($attribute['collectionId'], [$target])) {
                    $attribute['value'] =
                        sprintf('<img src="%s" width="150" style="display: block;" alt="Image">', $srcA[$target]);
                }
            } else {
                $attribute['value'] = $attribute['value'];
            }

            if (empty($attribute['language'])) {
                $template['entry'][] = $attribute;
            } elseif (empty($attribute['termId'])) {
                $template['language'][] = $attribute;
            } else {
                $template['term'][] = $attribute;
            }
        }

        return $template;
    }

    /**
     * @throws Zend_Cache_Exception
     */
    private function initializeLanguages(): void
    {
        $languages = ZfExtended_Factory::get(editor_Models_Languages::class);
        $this->sourceLangs = $languages->getFuzzyLanguages((int) $this->task->getSourceLang(), includeMajor: true);
        $this->targetLangs = $languages->getFuzzyLanguages((int) $this->task->getTargetLang(), includeMajor: true);

        //combine all used languages from task
        $this->allUsedLanguageIds = array_unique(array_merge($this->sourceLangs, $this->targetLangs));

        $usedLanguages = $languages->loadByIds($this->allUsedLanguageIds);
        // lower keys, since are also lower in usage in UI
        $keys = array_map('strtolower', array_column($usedLanguages, 'rfc5646'));

        $this->allUsedLanguages = array_combine($keys, $usedLanguages);
    }
}
