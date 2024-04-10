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

    protected array $trgTextA;

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
    protected function getMarkByTbxId(string $srcId, bool $isHomonym = false): string
    {
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
    protected function preload(array $srcIdA)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Reset data arrays
        $this->homonym = $this->trans = $this->trgTextA = [];

        // Get merged list of term tbx ids detected in source and target
        $tbxIdA = array_unique(array_merge($srcIdA, $this->trgIdA));

        // Get merged list of source and target fuzzy languages
        $fuzzy = array_merge($this->sourceFuzzyLanguages, $this->targetFuzzyLanguages);

        // Get `termEntryTbxId` and `term` for each term tbx id detected in source and/or target
        $this->exists = $db->query("
            SELECT `termTbxId`, `termEntryTbxId`, `term` 
            FROM `terms_term` 
            WHERE 1
             AND `termTbxId` IN ('" . join("','", $tbxIdA) . "')
             AND `collectionId` IN (" . join(',', $this->collectionIds) . ")
             AND `processStatus` = 'finalized'
            LIMIT " . count($tbxIdA) . "             
        ")->fetchAll(PDO::FETCH_UNIQUE);

        // Get all terms (from source and target), grouped by their termEntryTbxId
        $this->termsByEntry = $db->query("
            SELECT `termEntryTbxId`, `termEntryTbxId`, `term`, `termTbxId`, `languageId`
            FROM `terms_term`
            WHERE 1
              AND `termEntryTbxId` IN ('" . join("','", array_column($this->exists, 'termEntryTbxId')) . "')
              AND `collectionId`   IN (" . join(',', $this->collectionIds) . ")
              AND `languageId`     IN (" . join(',', $fuzzy) . ")
              AND `processStatus` = 'finalized'
        ")->fetchAll(PDO::FETCH_GROUP);

        // Foreach source term
        foreach ($srcIdA as $srcId) {
            // If NOT exists in db - skip
            if (! $src = $this->exists[$srcId] ?? 0) {
                continue;
            }

            // Pick translations for target fuzzy languages
            foreach ($this->termsByEntry[$src['termEntryTbxId']] as $term) {
                if (in_array($term['languageId'], $this->targetFuzzyLanguages)) {
                    $this->trans[$src['termEntryTbxId']][$term['termTbxId']] = $term['term'];
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
                            $this->trans[$termEntryId][$term['termTbxId']] = $term['term'];
                        }
                    }
                }
            }
        }

        // Collect target terms texts
        foreach ($this->trgIdA as $trgId) {
            if ($text = $this->exists[$trgId]['term'] ?? 0) {
                $this->trgTextA[] = $text;
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

        // Preload data
        $this->preload($srcIdA);

        // Get [termTbxId => [mark1, mark2, ...]] pairs for all terms detected in segment source text
        // As you can see at the line above it can be, for example, 3 occurrences of the same term
        // in segment source, and only 2 translations for tha term in segment target, so that would mean
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
            // Get css class
            $value = $this->getMarkByTbxId($srcId);

            // If translation was found or such source term does not exists in db at all
            if ($value == 'transFound' || ! isset($this->exists[$srcId])) {
                // Append mark for current occurrence of term tag
                $mark[$srcId][] = $value;

                // Keep the mark we have for current source term and goto next source term
                continue;
            }

            // If source term has homonyms among target terms' termEntries
            if ($this->homonym[$srcId] ?? 0) {
                // Foreach homonym
                foreach ($this->homonym[$srcId] as $termEntryId) {
                    // Get mark for homonym
                    $value = $this->getMarkByTbxId($termEntryId, true);

                    // If it's 'transFound' - stop homonym walkthrough
                    if ($value == 'transFound') {
                        break;
                    }
                }

                // Else
            } else {
                // Get source term's termEntryTbxId
                $entryId = $this->exists[$srcId]['termEntryTbxId'];

                // If there are no source term translations
                if (! $transTextA = array_values($this->trans[$entryId] ?? [])) {
                    // Setup 'transNotDefined'-class
                    $value = 'transNotDefined';

                    // Else if at least one of target terms is a translation for the current source term
                } elseif ($transText = array_intersect($transTextA, $this->trgTextA)[0] ?? 0) {
                    // Remove first found term text from $transTextA
                    unset($this->trgTextA[array_search($transText, $this->trgTextA)]);

                    // Setup 'transFound'-class
                    $value = 'transFound';
                }
            }

            // Append mark for current occurrence of term tag
            $mark[$srcId][] = $value;
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
        return preg_replace_callback('/(<div[^>]*class=")([^"]*term[^"]*)("[^>]*>)/', function ($matches) use ($strip) {
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
        $rex = '~<div[^>]*data-tbxid="' . $srcId . '"[^>]*>~';
        $idx = 0;

        // For each occurrence inject the value according occurrence index
        return preg_replace_callback($rex, function ($matches) use ($srcId, &$idx, $values) {
            // Replacement
            $replace = $matches[0];

            // If there is no class-attribute at all
            if (! str_contains($replace, ' class=')) {
                // Append it, empty for now
                $replace = str_replace('<div', '<div class=""', $replace);
            }

            // Append $mark to class list
            return preg_replace('~( class="[^"]*)"~', '$1 ' . $values[$idx++] . '"', $replace);
        }, $source);
    }
}
