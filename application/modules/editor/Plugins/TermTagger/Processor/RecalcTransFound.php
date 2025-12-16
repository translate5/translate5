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

namespace MittagQI\Translate5\Plugins\TermTagger\Processor;

use editor_Models_Languages;
use editor_Models_Task;
use editor_Models_TermCollection_TermCollection;
use editor_Models_Terminology_Models_TermModel;
use PDO;
use Zend_Cache_Exception;
use Zend_Db_Statement_Exception;
use Zend_Db_Table_Abstract;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;

/**
 * encapsulates the MarkTransFound Logic:
 * Makes a recalculation of the transFound transNotFound and transNotDefined Information out of and in the given segment content
 */
class RecalcTransFound
{
    private const string TERMS_BYCLASS_REGEX = '/(<div[^>]*class=")([^"]*term[^"]*)("[^>]*>)/';

    private const string TERMS_BYTBXID_REGEX = '~<div[^>]*data-tbxid="@srcId@"[^>]*>~';

    protected editor_Models_Task $task;

    protected editor_Models_Terminology_Models_TermModel $termModel;

    protected array $sourceFuzzyLanguages;

    protected array $targetFuzzyLanguages;

    protected ?array $collectionIds = null;

    protected array $exists;

    protected array $trans;

    protected array $termsByEntry;

    protected array $homonym;

    protected array $trgIdA;

    /**
     * @throws Zend_Cache_Exception
     */
    public function __construct(editor_Models_Task $task)
    {
        $this->task = $task;
        $this->termModel = ZfExtended_Factory::get(editor_Models_Terminology_Models_TermModel::class);

        $lang = ZfExtended_Factory::get(editor_Models_Languages::class);
        $this->targetFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getTargetLang(), 'id', true);
        $this->sourceFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getSourceLang(), 'id', true);

        // Lazy load collectionIds defined for current task
        $this->collectionIds = $this->collectionIds ?? ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class)
            ->getCollectionsForTask($this->task->getTaskGuid());
    }

    /**
     * Recalculates a list of segment contents
     * consumes a list of stdObjects, each stdObject contain a ->source and a ->target field which are processed
     *
     * @throws Zend_Exception
     */
    public function recalcList(array $segments): array
    {
        //TODO: this config and return can be removed after finishing the initial big transit project. Remove?
        $config = Zend_Registry::get('config');
        if (! empty($config->runtimeOptions->termTagger->markTransFoundLegacy)) {
            return $segments;
        }
        foreach ($segments as &$segment) {
            $segment->source = $this->recalc($segment->source, $segment->target);
        }

        return $segments;
    }

    /**
     * Get translation status mark for source term having tbxId given by $srcId arg,
     * or for source term's homonym, identified by termEntryTbxId still given by $srcId but with `true` as value of 2nd arg
     */
    protected function getMarkByTbxId(string $srcId, bool $isHomonym = false, &$thisTransStatus = null): string
    {
        // Clear
        $thisTransStatus = null;

        // If $isHomonym arg is true, it means that $srcId arg contains termEntryTbxId of a homonym term for some source term
        // so that we set up $src variable for it to be an array containing termEntryTbxId-key for it to be possible to use for
        // finding translations. We do that to avoid excessive SQL-query as the only thing we need for homonym term is it's
        // termEntryTbxId, which we did preload but not in $this->exists array
        $src = $isHomonym
            ? [
                'termEntryTbxId' => $srcId,
            ]
            : ($this->exists[$srcId] ?? 0);

        // If given source term is NOT found in db
        if (! $src) {
            // Setup 'transNotDefined'-class
            return 'transNotDefined';

            // Else if found, but it has NO translations in db for the target fuzzy languages
        } elseif (! $transIdA = array_keys($this->trans[$src['termEntryTbxId']] ?? [])) {
            // Setup 'transNotDefined'-class
            return 'transNotDefined';

            // Else if at least one of target terms is a translation for the current source term
        } elseif ($transTermId = array_values(array_intersect($transIdA, $this->trgIdA))[0] ?? 0) {
            // Remove first found tbxId from $trgIdA
            unset($this->trgIdA[array_search($transTermId, $this->trgIdA)]);

            // Setup status of a found translation
            $thisTransStatus = $this->trans[$src['termEntryTbxId']][$transTermId]['status'];

            // Setup 'transFound'-class
            return 'transFound';

            // Else setup default css class to be used if kept unmodified by below
        } else {
            // Default css class for other cases
            return 'transNotFound';
        }
    }

    /**
     * Preload data, sufficient for being further used to detect correct source terms translation status marks
     *
     * @throws Zend_Db_Statement_Exception
     */
    protected function preload(array $srcIdA, string &$target)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Reset data arrays
        $this->homonym = $this->trans = [];

        // ! Get merged list of term tbx ids detected in source and target
        $tbxIdA = array_unique(array_merge($srcIdA, $this->trgIdA));

        // Get merged list of source and target fuzzy languages
        $fuzzy = array_merge($this->sourceFuzzyLanguages, $this->targetFuzzyLanguages);

        // Prepare template for sql query to fetch terms by their tbxIds
        $existsSql = "
            SELECT DISTINCT `termTbxId`, `termEntryTbxId`, `term` 
            FROM `terms_term` 
            WHERE `termTbxId` IN ('%s')
              AND `collectionId` IN (" . join(',', $this->collectionIds) . ")
              AND `processStatus` = 'finalized'          
        ";

        // ! Get `termEntryTbxId` and `term` for each term tbx id detected in source and/or target
        $this->exists = $db->query(sprintf($existsSql, join("','", $tbxIdA)))->fetchAll(PDO::FETCH_UNIQUE);

        // Get all terms (from source and target), grouped by their termEntryTbxId
        $this->termsByEntry = $db->query("
            SELECT `termEntryTbxId`, `termEntryTbxId`, `term`, `termTbxId`, `languageId`, `status`
            FROM `terms_term`
            WHERE `termEntryTbxId` IN ('" . join("','", array_column($this->exists, 'termEntryTbxId')) . "')
              AND `collectionId`   IN (" . join(',', $this->collectionIds) . ")
              AND `languageId`     IN (" . join(',', $fuzzy) . ")
              AND `processStatus` = 'finalized'
            ORDER BY FIND_IN_SET(`status`, 'preferredTerm,standardizedTerm') DESC,
                 NOT FIND_IN_SET(`status`, 'deprecatedTerm,supersededTerm') DESC, 
              `status` = 'admittedTerm' ASC  
        ")->fetchAll(PDO::FETCH_GROUP);

        // Spoof current termTbxId with another termTbxId within segment target for cases
        // when current termTbxId for a target term IS NOT from the termEntry that source term is from
        // but we have same term with another termTbxId that IS from the same termEntry that source term is from
        $this->spoofTargetTermsTbxIdsIfNeed($tbxIdA, $srcIdA, $target, $existsSql);

        // Foreach source term
        foreach ($srcIdA as $srcId) {
            // If NOT exists in db - skip
            if (! $src = $this->exists[$srcId] ?? 0) {
                continue;
            }

            // Pick translations for target fuzzy languages
            foreach ($this->termsByEntry[$src['termEntryTbxId']] as $term) {
                if (in_array($term['languageId'], $this->targetFuzzyLanguages)) {
                    $this->trans[$src['termEntryTbxId']][$term['termTbxId']] = [
                        'term' => $term['term'],
                        'status' => $term['status'],
                    ];
                }
            }

            // Pick target terms' termEntries having homonyms for current source term
            foreach ($this->trgIdA as $trgTbxId) {
                if ($trg = $this->exists[$trgTbxId] ?? 0) {
                    foreach ($this->termsByEntry[$trg['termEntryTbxId']] as $term) {
                        if (in_array($term['languageId'], $this->sourceFuzzyLanguages)
                            && $term['term'] == $src['term']
                            && $term['termTbxId'] != $srcId) {
                            $this->homonym[$srcId][] = $term['termEntryTbxId'];
                        }
                    }
                }
            }
        }

        // Add translations for source-terms-homonyms to be able to find those among target terms
        // in case if we won't find translations for source-terms-themselves among target terms
        foreach ($this->homonym as $srcId => $termEntryIdA) {
            foreach ($termEntryIdA as $termEntryId) {
                if (! isset($this->trans[$termEntryId])) {
                    foreach ($this->termsByEntry[$termEntryId] as $term) {
                        if (in_array($term['languageId'], $this->targetFuzzyLanguages)) {
                            $this->trans[$termEntryId][$term['termTbxId']] = [
                                'term' => $term['term'],
                                'status' => $term['status'],
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Recalculates translation status marks for all terms found by TermTagger within single segment source text
     *
     * @param string $target is given as reference, if the modified target is needed too
     * @return string the modified source field
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    public function recalc(string $source, string &$target): string
    {
        // If termTagger's markTransFoundLegacy-config is set to 1 - return source as is
        if (! empty(Zend_Registry::get('config')->runtimeOptions->termTagger->markTransFoundLegacy)) {
            return $source;
        }

        // If no term collections - return source as is
        if (empty($this->collectionIds)) {
            return $source;
        }

        // Get source and target
        $source = $this->removeExistingFlags($source);
        $target = $this->removeExistingFlags($target);

        // If no source terms detected - return source as is
        if (! count($srcIdA = $this->termModel->getTermMidsFromSegment($source))) {
            return $source;
        }

        // Get target tbx ids
        $this->trgIdA = $this->termModel->getTermMidsFromSegment($target);

        // Preload data and spoof current termTbxId with another termTbxId within segment target for cases
        // when current termTbxId for a target term IS NOT from the termEntry that source term is from
        // but we have same term with another termTbxId that IS from the same termEntry that source term is from
        $this->preload($srcIdA, $target);

        // Get [termTbxId => [mark1, mark2, ...]] pairs for all terms detected in segment source text
        // As you can see at the line above it can be, for example, 3 occurrences of the same term
        // in segment source, and only 2 translations for that term in segment target, so that would mean
        // translations for first two - are found, but for the 3rd one - not found.
        // So 'mark1, mark2, ...' above are to indicate status for each occurrence of a term in segment source
        $markA = $this->getMarkBySrcIdA($srcIdA);

        // Recalc transNotFound/transNotDefined/transFound marks
        foreach ($markA as $srcId => $values) {
            $source = $this->insertMark($source, $srcId, $values);
        }

        // Return source text
        return $source;
    }

    /**
     * Get marks to be later injected as css class for term tags in segment source text
     */
    protected function getMarkBySrcIdA(array $srcIdA): array
    {
        // Marks array
        $mark = [];

        // Foreach source term tbx id
        foreach ($srcIdA as $srcId) {
            // Translation presence status initial value
            $presenceStatus = 'transNotDefined';

            // Logic:
            // 1.source term does not exist in db                                                        => transNotDefined
            // -.source term does exist in db
            //   2. but no translation(s) exist in db in termEntry where that source term is from        => transNotDefined
            //   3. and translation(s) do exist in db in that termEntry, but not used in segment target  => transNotFound
            //   4. and translation(s) do exist in db in that termEntry, and are used in segment target  => transFound

            // Better target term status will be here, if applicable
            $bestTransStatus = '';

            // If source term does exists
            if (isset($this->exists[$srcId])) {
                // Detect translation presence status
                $presenceStatus = $this->getMarkByTbxId($srcId, false, $thisTransStatus);

                // If translation not present, this means we're here due to scenario 2 or 3,
                // and this means it may make sense to check the homonyms, if any
                if ($presenceStatus !== 'transFound' && isset($this->homonym[$srcId])) {
                    // Foreach homonym
                    foreach ($this->homonym[$srcId] as $termEntryId) {
                        // Get status for homonym
                        $presenceStatus = $this->getMarkByTbxId($termEntryId, true, $thisTransStatus);

                        // If it's 'transFound' - stop homonym walkthrough
                        if ($presenceStatus === 'transFound') {
                            break;
                        }
                    }
                }

                // If translation was found
                if ($presenceStatus !== 'transNotDefined') {
                    // Get the best target term we have in current termEntry, if we have at least one
                    if (isset($this->trans[$this->exists[$srcId]['termEntryTbxId']])) {
                        $firstA = [array_values($this->trans[$this->exists[$srcId]['termEntryTbxId']])[0]];
                    } else {
                        $firstA = [];
                    }

                    // Append the best target term we have in each homonym termEntry,
                    // if such a termEntry have at least one target term
                    foreach ($this->homonym[$srcId] ?? [] as $termEntryId) {
                        if (isset($this->trans[$termEntryId])) {
                            $firstA[] = array_values($this->trans[$termEntryId])[0];
                        }
                    }

                    // Wrap $firstA into an array to mame it compatible with further TermModel->sortTermGroups()
                    $firstA = [$firstA];
                    $firstA = $this->termModel->sortTermGroups($firstA);
                    $firstA = $firstA[0];

                    // Get status of first translation, which is the best translation we have in terminology db
                    $firstAmongFirst = $firstA[0]['status'];

                    // If used translation is not the best one
                    if ($thisTransStatus !== $firstAmongFirst) {
                        // Spoof the $presenceStatus to indicate that the best translation is not found
                        $presenceStatus = 'transNotFound';

                        // Indicate the status of the best translation
                        $bestTransStatus = $firstAmongFirst;
                    }
                }
            }

            // Append mark for current occurrence of term tag
            $mark[$srcId][] = [
                'presenceStatus' => $presenceStatus,
                'bestTransStatus' => $bestTransStatus,
            ];
        }

        // Return marks for all terms within current segment source text
        return $mark;
    }

    /**
     * Remove potentially incorrect transFound, transNotFound and transNotDefined inserted by TermTagger
     */
    protected function removeExistingFlags(string $content): string
    {
        // List of TermTagger-assigned statuses to be stripped prior recalculation
        $strip = ['transFound', 'transNotFound', 'transNotDefined'];

        // Strip statuses
        return preg_replace_callback(self::TERMS_BYCLASS_REGEX, function ($matches) use ($strip) {
            // Get array of found classes
            $classesFound = explode(' ', $matches[2]);

            // Remove the unwanted css classes by array_diff:
            return $matches[1] . join(' ', array_diff($classesFound, $strip)) . $matches[3];
        }, $content);
    }

    /**
     * Insert the css-class transFound/transNotFound/transNotDefined into css-classes list of the term-div tag with the corresponding $tbxId
     */
    protected function insertMark(string $source, string $srcId, array $values): string
    {
        // Tag regular expression and replacements counter
        $regEx = str_replace('@srcId@', $srcId, self::TERMS_BYTBXID_REGEX);
        $idx = 0;

        // For each occurrence inject the value according occurrence index
        return preg_replace_callback($regEx, function ($matches) use ($srcId, &$idx, $values) {
            // Replacement
            $replace = $matches[0];

            // If there is no class-attribute at all
            if (! str_contains($replace, ' class=')) {
                // Append it, empty for now
                $replace = str_replace('<div', '<div class=""', $replace);
            }

            // Prepare css classes whitespace-separated list to be inserted
            // 1.Class name under index 'presenceStatus' can be:
            // - 'transNotFound'
            // - 'transNotDefined'
            // - 'transFound'
            // 2.Class name under index 'bestTransStatus' exists in cases when we have better translation
            //   than current or missing translation, and can be:
            // - 'standardizedTerm'
            // - 'preferredTerm'
            // - 'admittedTerm'
            // - '' (empty string), if we don't have translation-terms with above statuses in terminology db
            // Here we setup a prefix to distinguish between source term statuses and statuses of best possible translations
            $insert = $values[$idx]['presenceStatus'] . \editor_Utils::rif(
                $values[$idx]['bestTransStatus'],
                ' ' . \editor_Plugins_TermTagger_Tag::BEST_TRANS_STATUS_PREFIX . '$1'
            );

            // Increment same term occurrences counter
            $idx++;

            // Append $insert to class list
            return preg_replace('~( class="[^"]*)"~', '$1 ' . $insert . '"', $replace);
        }, $source);
    }

    /**
     * Spoof current termTbxId with another termTbxId within segment target for each case
     * when current termTbxId for a target term IS NOT from the termEntry that source term is from
     * but we have same term with another termTbxId that IS from the same termEntry that source term is from
     */
    private function spoofTargetTermsTbxIdsIfNeed(array &$tbxIdA, array $srcIdA, string &$target, string $existsSql): void
    {
        // Get db adapter
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Array of [oldTbxId => newTbxId] pairs
        $oldToNewTbx = [];

        // Arrays to indicate termEntries having at least one term used in source/target
        $used = [
            'source' => [],
            'target' => [],
        ];

        // Foreach term entry
        foreach ($this->termsByEntry as $termEntryId => $termA) {
            foreach ($termA as $idx => $term) {
                // Setup 'used' and 'isSource' flags for each term
                $this->termsByEntry[$termEntryId][$idx]['used'] = $_used = in_array($term['termTbxId'], $tbxIdA);
                $this->termsByEntry[$termEntryId][$idx]['isSource'] = $_isSource = in_array($term['termTbxId'], $srcIdA);

                // If it's a term used in source or target
                if ($_used) {
                    // Setup a flag indicating this termEntry has at least one such term
                    $used[$_isSource ? 'source' : 'target'][$termEntryId] = true;
                }
            }
        }

        // Array of unused target terms grouped by termEntryId, but only for termEntries having at least one used source term
        $unusedTarget = [];

        // Foreach termEntry having at least one term used in source
        foreach ($this->termsByEntry as $termEntryId => $termA) {
            if (isset($used['source'][$termEntryId])) {
                // Collect unused target terms in a way that will allow us to swap used-flag from one term to another
                foreach ($termA as $idx => $term) {
                    if (! $term['isSource'] && ! $term['used']) {
                        $unusedTarget[$termEntryId][$term['term']] = $idx;
                    }
                }
            }
        }

        // Foreach termEntry that has term(s) used in target, but has no term(s) used in source
        foreach ($this->termsByEntry as $termEntryId_was => $termA) {
            if (! isset($used['source'][$termEntryId_was])
                && isset($used['target'][$termEntryId_was])) {
                // Foreach term used in target
                foreach ($termA as $idx_was => $term) {
                    if (! $term['isSource'] && $term['used']) {
                        // Check whether we have homonym (in some termEntry having term(s) used in source)
                        // If yes - mark it as used instead of current term
                        foreach ($unusedTarget as $termEntryId_now => $termA) {
                            if (is_int($idx_now = $termA[$term['term']] ?? false)) {
                                $oldTbxId = (string) $this->termsByEntry[$termEntryId_was][$idx_was]['termTbxId'];
                                $newTbxId = (string) $this->termsByEntry[$termEntryId_now][$idx_now]['termTbxId'];
                                $oldToNewTbx[$oldTbxId] = $newTbxId;

                                break;
                            }
                        }
                    }
                }
            }
        }

        // If nothing to be spoofed - return
        if (! count($oldToNewTbx)) {
            return;
        }

        // Replace old/new tbx-ids in segment target
        foreach ($oldToNewTbx as $oldTbxId => $newTbxId) {
            $target = $this->replaceTbxIdInMarkup($oldTbxId, $newTbxId, $target);
        }

        // Shortcuts
        $oldTbxIds = array_keys($oldToNewTbx);
        $newTbxIds = array_values($oldToNewTbx);

        // Replace in $tbxIdA
        array_walk($tbxIdA, fn (&$tbxId) => $tbxId = $oldToNewTbx[$tbxId] ?? $tbxId);

        // Replace in $this->trgIdA
        array_walk($this->trgIdA, fn (&$tbxId) => $tbxId = $oldToNewTbx[$tbxId] ?? $tbxId);

        // Unset term data for old tbx ids
        foreach ($oldTbxIds as $oldTbxId) {
            unset($this->exists[$oldTbxId]);
        }

        // Append term data for new tbx ids
        $this->exists += $db->query(sprintf($existsSql, join("','", $newTbxIds)))->fetchAll(PDO::FETCH_UNIQUE);
    }

    /**
     * Exchanges a tbx-id in a markup-string
     */
    private function replaceTbxIdInMarkup(string $oldTbxId, string $newTbxId, string $markup): string
    {
        // FIXME unescaped $oldTbxId values containing regex delimiters/meta characters will break the pattern and trigger a preg_replace_callback TypeError; escape or avoid regex usage here.
        $regEx = str_replace('@srcId@', $oldTbxId, self::TERMS_BYTBXID_REGEX);

        return preg_replace_callback($regEx, function ($matches) use ($oldTbxId, $newTbxId) {
            return str_replace('data-tbxid="' . $oldTbxId . '"', 'data-tbxid="' . $newTbxId . '"', (string) $matches[0]);
        }, $markup);
    }
}
