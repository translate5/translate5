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
 * encapsulates the MarkTransFound Logic:
 * Makes a recalculation of the transFound transNotFound and transNotDefined Information out of and in the given segment content
 */
class editor_Plugins_TermTagger_RecalcTransFound {
    /**
     *
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var editor_Models_Terminology_Models_TermModel
     */
    protected $termModel;

    /**
     * @var editor_Plugins_TermTagger_TermCache
     */
    protected $termCache;

    /**
     * @var array
     */
    protected $sourceFuzzyLanguages;

    /**
     * @var array
     */
    protected $targetFuzzyLanguages;

    /**
     * @var array
     */
    protected $groupCounter = array();

    /**
     * must be reset if task changes. Since task can only be given on construction, no need to reset.
     * @var array
     */
    protected $notPresentInTbxTarget = array();

    public function __construct(editor_Models_Task $task) {
        $this->task = $task;
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');

        /* @var $lang editor_Models_Languages */
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        $this->targetFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getTargetLang(),'id',true);
        $this->sourceFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getSourceLang(),'id',true);

        // Lazy load collectionIds defined for current task
        $this->collectionIds = $this->collectionIds ?? ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->getCollectionsForTask($this->task->getTaskGuid());

        $this->termCache = ZfExtended_Factory::get('editor_Plugins_TermTagger_TermCache', [$task, $this->collectionIds]);
    }

    /**
     * recalculates a list of segment contents
     * consumes a list of stdObjects, each stdObject contain a ->source and a ->target field which are processed
     * @param array $segments
     * @return array
     */
    public function recalcList(array $segments) {
        //TODO: this config and return can be removed after finishing the initial big transit project
        $config = Zend_Registry::get('config');
        if(!empty($config->runtimeOptions->termTagger->markTransFoundLegacy)) {
            return $segments;
        }
        foreach ($segments as &$seg) {
            $seg->source = $this->recalc($seg->source, $seg->target);
        }
        return $segments;
    }

    /**
     * @var null
     */
    public $collectionIds = null;

    public $detected               = [];
    public $trgLangTermsBySrcEntry = [];
    public $targetTermIds          = [];
    public $targetTermTexts        = [];

    /**
     * @param string $sourceTermId
     * @return string
     */
    protected function recalcSingleSourceTerm(string $sourceTermId) {

        // Case 1: If given source term is NOT found in db
        if (!$src = $this->detected[$sourceTermId] ?? 0) {

            // Setup 'transNotDefined'-class
            return 'transNotDefined';

        // Case 2: Else if found, but it has NO translations in db for the target fuzzy languages
        } else if (!$transTermIds = array_column($this->trgLangTermsBySrcEntry[$src['termEntryTbxId']] ?? [], 'termTbxId')) {

            // Setup 'transNotDefined'-class
            return 'transNotDefined';

        // Case 3: Else if at least one of target terms is a translation for the current source term
        } else if ($transTermId = array_intersect($transTermIds, $this->targetTermIds)[0] ?? 0) {

            // Remove first found tbxId from $targetTermIds
            unset($this->targetTermIds[array_search($transTermId, $this->targetTermIds)]);

            // Setup 'transFound'-class
            return 'transFound';

        // Else setup default css class to be used if kept unmodified by below
        } else {

            // Default css class for other cases
            return 'transNotFound';
        }
    }

    /**
     * recalculates one single segment content
     * @param string $source
     * @param string $target is given as reference, if the modified target is needed too
     * @return string the modified source field
     */
    public function recalc(string $source, string &$target): string
    {
        //
        if (!empty(Zend_Registry::get('config')->runtimeOptions->termTagger->markTransFoundLegacy)) {
            return $source;
        }

        // If no term collections - return source as is
        if (empty($this->collectionIds)) {
            return $source;
        }

        // Get source and target
        $source = $this->removeExistingFlags($source);
        $target = $this->removeExistingFlags($target);

        // Get source and target tbx ids
        $sourceTermIds = $this->termModel->getTermMidsFromSegment($source);
        $this->targetTermIds = $this->termModel->getTermMidsFromSegment($target);

        // If no source terms detected - return $source as is
        if (!count($sourceTermIds)) {
            return $source;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Get merged list of term tbx ids detected in source and target
        $mergedTbxIdA = array_unique(array_merge($sourceTermIds, $this->targetTermIds));

        // Get merged list of source and target fuzzy languages
        $mergedLangIdA = array_merge($this->sourceFuzzyLanguages, $this->targetFuzzyLanguages);

        //
        $homonym = $this->trgLangTermsBySrcEntry = $this->targetTermTexts = [];

        // Get `termEntryTbxId` and `term` for each term detected in source and target
        $this->detected = $db->query('
            SELECT `termTbxId`, `termEntryTbxId`, `term` 
            FROM `terms_term` 
            WHERE 1
             AND `termTbxId` IN ("'. join('","', $mergedTbxIdA) . '")
             AND `collectionId` IN (' . join(',', $this->collectionIds) . ')
            LIMIT ' . count($mergedTbxIdA) . '             
        ')->fetchAll(PDO::FETCH_UNIQUE);

        // Collect target terms texts
        foreach ($this->targetTermIds as $targetTermId) {
            if ($trg = $this->detected[$targetTermId] ?? 0) {
                $this->targetTermTexts []= $trg['term'];
            }
        }

        // Get all terms (from source and target), grouped by their termEntryTbxId
        $termsByEntry = $db->query('
            SELECT `termEntryTbxId`, `termEntryTbxId`, `term`, `termTbxId`, `languageId`
            FROM `terms_term`
            WHERE 1
              AND `termEntryTbxId` IN ("' . join('","', array_column($detected, 'termEntryTbxId')) . '")
              AND `collectionId`   IN (' . join(',', $this->collectionIds) . ')
              AND `languageId`     IN (' . join(',', $mergedLangIdA) . ')
        ')->fetchAll(PDO::FETCH_GROUP);

        // Foreach source term
        foreach ($sourceTermIds as $srcTbxId) {

            // If detected in db
            if ($src = $this->detected[$srcTbxId] ?? 0) {

                // Pick translations for target fuzzy languages
                foreach ($termsByEntry[$src['termEntryTbxId']] as $term) {
                    if (in_array($term['languageId'], $this->targetFuzzyLanguages)) {
                        $this->trgLangTermsBySrcEntry[$src['termEntryTbxId']] []= $term;
                    }
                }

                // Pick homonyms under the target terms' termEntries
                foreach ($this->targetTermIds as $trgTbxId) {
                    if ($trg = $this->detected[$trgTbxId] ?? 0) {
                        foreach ($termsByEntry[$trg['termEntryTbxId']] as $term) {
                            if (in_array($term['languageId'], $this->sourceFuzzyLanguages)
                                && $term['term'] == $src['term']
                                && $term['termTbxId'] != $srcTbxId) {
                                $homonym[$srcTbxId] []= $term['termTbxId'];
                            }
                        }
                    }
                }
            }
        }

        // Foreach source term tbx id
        foreach ($sourceTermIds as $sourceTermId) {

            // Get css class
            $css[$sourceTermId] = $this->recalcSingleSourceTerm($sourceTermId);

            // If translation was found or such source term does not exists in db
            if ($css[$sourceTermId] == 'transFound' || !isset($this->detected[$sourceTermId])) {

                // Skip to next source term
                continue;
            }

            // if source term has homonyms among target terms' termEntries
            if ($homonym[$sourceTermId] ?? 0) {

                // Foreach homonym
                foreach ($homonym[$sourceTermId] as $homonym_sourceTermId) {

                    // Get css class
                    $css[$sourceTermId] = $this->recalcSingleSourceTerm($homonym_sourceTermId);

                    // If it's 'transFound' - stop homonym walkthrough
                    if ($css[$sourceTermId] == 'transFound') {
                        break;
                    }
                }

            // Else
            } else {

                //
                $src = $this->detected[$sourceTermId];

                //
                if (!$transTermTexts = array_column($this->trgLangTermsBySrcEntry[$src['termEntryTbxId']] ?? [], 'term')) {

                    // Setup 'transNotDefined'-class
                    $css[$sourceTermId] = 'transNotDefined';

                // Else if at least one of target terms is a translation for the current source term
                } else if ($transTermText = array_intersect($transTermTexts, $this->targetTermTexts)[0] ?? 0) {

                    // Remove first found term text from $targetTermTexts
                    unset($this->targetTermTexts[array_search($transTermText, $this->targetTermTexts)]);

                    // Setup 'transFound'-class
                    $css[$sourceTermId] = 'transFound';
                }
            }
        }

        //class_exists('editor_Utils');
        //i(compact('source', 'target', 'detected', 'termsByEntry', 'homonym', 'trgLangTermsBySrcEntry'), 'a');

        /*$targetTermIds_initial = $targetTermIds; $targetTermIds_unset = [];
        $toMarkMemory = [];
        $this->groupCounter = [];

        foreach ($sourceTermIds as $sourceTermId) {
            $endlessLoopQty = 0;

            // Goto label to be used in case when $sourceTermId initially detected by TermTagger contains termTbxId of a term
            // located under NOT the same termEntry as term(s) detected in segment target text, so that such a term in
            // segment source text will be red-inderlined to inidicate that it's translation(s) was not found in segment
            // target text, despite there actually are correct translations but just in another termEntries. So, this
            // label will be used as a pointer for goto operator executed in case if such situation was detected and
            // alternative termTbxId was found to solve that problem so we'll have to run same iteration from the
            // beginning but with using spoofed value of $sourceTermId variable
            //correct_sourceTermId:

            // Check whether source term having given termTbxId ($sourceTermId) exists
            // within task's termcollections and if not - mark this term as not found
            if (!$src = $detected[$sourceTermId] ?? false) {
                $toMarkMemory[$sourceTermId] = null;
                continue;
            }

            // Get termEntryId of the given source term
            $termEntryTbxId = $src['termEntryTbxId'];

            // Find translations of the given source term for target fuzzy languages
            $groupedTerms = $trgLangTermsBySrcEntry[$termEntryTbxId] ?? [];

            // If no translations found
            if (empty($groupedTerms)) {

                // Setup a flag indicating that there are no translations for the current source term for the languages we need
                $this->notPresentInTbxTarget[$termEntryTbxId] = true;
            }

            // Counter for those of translations which are found in segment target text
            $transFound = $this->groupCounter[$termEntryTbxId] ?? 0;

            // Foreach translation existing for the given source term under the same termEntry
            foreach ($groupedTerms as $groupedTerm) {

                // Check whether translation does exist in segment target text
                $targetTermIdKey = array_search($groupedTerm['termTbxId'], $targetTermIds);

                // If so
                if ($targetTermIdKey !== false) {

                    // Increment translation-which-is-found-in-segment-target counter
                    $transFound ++;

                    // Unset it from $targetTermIds-array, so that the only tbx ids of terms will be kept
                    // there which are not translations to any of terms detected in segment source text
                    unset($targetTermIds[$targetTermIdKey]);
                }
            }

            // If there are terms detected in segment target text but none of them is a translation for source text's current term
            /*if ($targetTermIds && !$transFound) {

                // Unset values from $termEntryTbxIdA if need
                foreach ($targetTermIds_unset as $targetTermId_unset) {
                    unset($termEntryTbxIdA_target[$targetTermId_unset]);
                }

                // If found
                if ($homonym[$sourceTermId]) {

                    // Spoof value of $sourceTermId with found homonym's termTbxId
                    $sourceTermId = $homonym[$sourceTermId][1]['termTbxId'];

                    $endlessLoopQty ++;

                    if ($endlessLoopQty > 10) {
                        i('loopQty: ' . $endlessLoopQty . ' - skip' , 'a');
                        continue;
                    } else {
                        i('loopQty: ' . $endlessLoopQty . ' - try spoofed ', 'a');
                    }

                    // Re-run current iteration
                    goto correct_sourceTermId;

                // Else
                } else {

                    // Fetch target terms texts for all target terms tbx ids
                    $targetTermTexts = $targetTermTexts ?? $this->termCache->loadDistinctByTbxIds($targetTermIds);

                    // Foreach translation existing for the current source term under it's termEntry
                    foreach ($groupedTerms as $groupedTerm) {

                        // Check whether translation does exist in segment target
                        $targetTermTextKey = array_search($groupedTerm['term'], $targetTermTexts);

                        // If exists
                        if ($targetTermTextKey !== false) {

                            // Increment translation-which-is-found-in-segment-target counter
                            $transFound ++;

                            // Unset it from $targetTermIds-array, so that the only tbx ids of terms to be kept where
                            unset($targetTermTexts[$targetTermTextKey]);
                        }
                    }
                }
            }

            //
            $toMarkMemory[$sourceTermId] = $termEntryTbxId;

            // Apply into class vaiable to be accessed from othe methods
            $this->groupCounter[$termEntryTbxId] = $transFound;
        }

        // Reapply css class names where need
        foreach ($toMarkMemory as $sourceTermId => $termEntryTbxId) {
            $source = $this->insertTransFoundInSegmentClass($source, $sourceTermId, $termEntryTbxId);
        }*/

        // Return source text
        return $source;
    }

    /**
     * remove potentially incorrect transFound, transNotFound and transNotDefined inserted by termtagger
     * @param string $content
     * @return string
     */
    protected function removeExistingFlags($content) {
        $classesToRemove = array('transFound', 'transNotFound', 'transNotDefined');

        return preg_replace_callback('/(<div[^>]*class=")([^"]*term[^"]*)("[^>]*>)/', function($matches) use ($classesToRemove){
            $classesFound = explode(' ', $matches[2]);
            //remove the unwanted css classes by array_diff:
            return $matches[1].join(' ', array_diff($classesFound, $classesToRemove)).$matches[3];
        }, $content);
    }

    /**
     * insert the css-class transFound or transNotFound into css-class of the term-div tag with the corresponding mid
     * @param string $seg
     * @param $mid
     * @param $groupId
     * @return string
     */
    protected function insertTransFoundInSegmentClass(string $seg, $mid, $groupId) {
        settype($this->groupCounter[$groupId], 'integer');
        $transFound =& $this->groupCounter[$groupId];
        $presentInTbxTarget = empty($this->notPresentInTbxTarget[$groupId]);
        $rCallback = function($matches) use (&$seg, &$transFound, $presentInTbxTarget){
            foreach ($matches as $match) {
                if($presentInTbxTarget) {
                    $cssClassToInsert = ($transFound>0)?'transFound':'transNotFound';
                }
                else {
                    $cssClassToInsert = 'transNotDefined';
                }

                $transFound--;
                $modifiedMatch = $match;
                if(strpos($modifiedMatch, ' class=')===false){
                    $modifiedMatch = str_replace('<div', '<div class=""', $modifiedMatch);
                }
                $modifiedMatch = preg_replace('/( class="[^"]*)"/', '\\1 '.$cssClassToInsert.'"', $modifiedMatch);
                $seg = preg_replace('/'.$match.'/', $modifiedMatch, $seg, 1);
            }
        };

        preg_replace_callback('/<div[^>]*data-tbxid="'.$mid.'"[^>]*>/', $rCallback, $seg);
        return $seg;
    }
}
