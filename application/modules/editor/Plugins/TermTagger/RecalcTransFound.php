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
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $this->targetFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getTargetLang(),'id',true);
        $this->sourceFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getSourceLang(),'id',true);
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

    /**
     * recalculates one single segment content
     * @param string $source
     * @param string $target is given as reference, if the modified target is needed too
     * @return string the modified source field
     */
    public function recalc(string $source, string &$target): string
    {
        //TODO: this config and return can be removed after finishing the initial big transit project
        $config = Zend_Registry::get('config');
        if (!empty($config->runtimeOptions->termTagger->markTransFoundLegacy)) {
            return $source;
        }
        $taskGuid = $this->task->getTaskGuid();

        // Lazy load collectionIds defined for current task
        if ($this->collectionIds === null) {
            /* @var $assoc editor_Models_TermCollection_TermCollection */
            $assoc = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            $this->collectionIds = $assoc->getCollectionsForTask($taskGuid); // This DB-query runs on each segment ?? Not good
        }

        if (empty($this->collectionIds)) {
            return $source;
        }
        $source = $this->removeExistingFlags($source);
        $target = $this->removeExistingFlags($target);
        $sourceTermIds = $this->termModel->getTermMidsFromSegment($source);
        $targetTermIds = $this->termModel->getTermMidsFromSegment($target);
        $targetTermIds_initial = $targetTermIds; $targetTermIds_unset = [];
        $toMarkMemory = [];
        $this->groupCounter = [];

        foreach ($sourceTermIds as $sourceTermId) {

            // Goto label to be used in case when $sourceTermId initially detected by TermTagger contains termTbxId of a term
            // located under NOT the same termEntry as term(s) detected in segment target text, so that such a term in
            // segment source text will be red-inderlined to inidicate that it's translation(s) was not found in segment
            // target text, despite there actually are correct translations but just in another termEntries. So, this
            // label will be used as a pointer for goto operator executed in case if such situation was detected and
            // alternative termTbxId was found to solve that problem so we'll have to run same iteration from the
            // beginning but with using spoofed value of $sourceTermId variable
            correct_sourceTermId:

            // Check whether source term having given termTbxId ($sourceTermId) exists within task's termcollections
            try {
                $this->termModel->loadByMid($sourceTermId, $this->collectionIds);
            }
            catch (ZfExtended_Models_Entity_NotFoundException $e) {

                // So the source terms are marked as notfound in the repetitions
                $toMarkMemory[$sourceTermId] = null;
                continue;
            }

            // Get termEntryId of the given source term
            $termEntryTbxId = $this->termModel->getTermEntryTbxId();

            // Find translations of the given source term for target fuzzy languages
            $groupedTerms = $this->termModel->getAllTermsOfGroup($this->collectionIds, $termEntryTbxId, $this->targetFuzzyLanguages);

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

                    // Collect target terms tbx ids, that were unset from $targetTermIds
                    $targetTermIds_unset []= $groupedTerm['termTbxId'];

                    // Unset it from $targetTermIds-array, so that the only tbx ids of terms will be kept
                    // there which are not translations to any of terms detected in segment source text
                    unset($targetTermIds[$targetTermIdKey]);
                }
            }

            // If there are terms detected in segment target text but none of them is a translation for source text's current term
            if ($targetTermIds && !$transFound) {

                // Shortcut to db adapter instance
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();

                // Lazy load distinct termEntryTbx ids of target terms
                $termEntryTbxIdA = $termEntryTbxIdA ?? $db->query('
                    SELECT `termTbxId`, `termEntryTbxId` 
                    FROM `terms_term` 
                    WHERE `termTbxId` IN ("'. join('","', $targetTermIds_initial) . '")
                ')->fetchAll(PDO::FETCH_KEY_PAIR);

                // Unset values from $termEntryTbxIdA if need
                foreach ($targetTermIds_unset as $targetTermId_unset) {
                    unset($termEntryTbxIdA[$targetTermId_unset]);
                }

                // Try to find current source term's homonym under the target terms' termEntries
                $sourceTermId_homonym = $db->query('
                    SELECT `termTbxId` 
                    FROM `terms_term` 
                    WHERE 1
                      AND `termEntryTbxId` IN ("'. join('","', $termEntryTbxIdA) . '") 
                      AND `term` = ?
                      AND `languageId` IN (' . join(',', $this->sourceFuzzyLanguages) . ')
                ', $this->termModel->getTerm())->fetchColumn();

                // If found
                if ($sourceTermId_homonym) {

                    // Spoof value of $sourceTermId with found homonym's termTbxId
                    $sourceTermId = $sourceTermId_homonym;

                    // Re-run current iteration
                    goto correct_sourceTermId;

                // Else
                } else {

                    // Fetch target terms texts for all target terms tbx ids
                    $targetTermTexts = $targetTermTexts ?? $db->query('
                        SELECT DISTINCT term 
                        FROM `terms_term` 
                        WHERE `termTbxId` IN ("'. join('","', $targetTermIds) . '")
                    ')->fetchAll(PDO::FETCH_COLUMN);

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
        }

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
