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

    /**
     * @var array
     */
    protected $termByTbxId = [];

    public function __construct(editor_Models_Task $task) {
        $this->task = $task;
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $this->targetFuzzyLanguages = $lang->getFuzzyLanguages($this->task->getTargetLang(),'id',true);
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
        $assoc = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collectionIds = $assoc->getCollectionsForTask($taskGuid); // This DB-query runs on each segment ?? Not good

        if (empty($collectionIds)) {
            return $source;
        }
        class_exists('editor_Utils');
        i(['was source', $source], 'a');
        i(['was target', $target], 'a');
        //$source = str_replace('id162dcd89-6aef-48a3-9c2a-d7d412bd9e0f', 'id4abddb97-5f14-4e7b-becd-ec51c232c76e', $source);
        $source = $this->removeExistingFlags($source);
        i(['flags removed from source', $source], 'a');
        $target = $this->removeExistingFlags($target);
        i(['flags removed from target', $target], 'a');
        $sourceTermIds = $this->termModel->getTermMidsFromSegment($source);
        $targetTermIds = $this->termModel->getTermMidsFromSegment($target);
        i(['$sourceTermIds', $sourceTermIds], 'a');
        i(['$targetTermIds', $targetTermIds], 'a');
        $toMarkMemory = [];
        $this->groupCounter = [];

        foreach ($sourceTermIds as $sourceTermId) {
            try {

                // Check whether source term having given termTbxId ($sourceTermId) exists within task's termcollections
                $this->termModel->loadByMid($sourceTermId, $collectionIds);

                // Get source term text
                $this->termByTbxId[$sourceTermId] = $this->termModel->getTerm();
            }
            catch (ZfExtended_Models_Entity_NotFoundException $e) {

                // So the source terms are marked as notfound in the repetitions
                $toMarkMemory[$sourceTermId] = null;
                continue;
            }
            //$termEntryTbxId = 'id60093f56-da59-4074-9d1a-9803464a9402';

            // Get termEntryId of the given source term
            $termEntryTbxId = $this->termModel->getTermEntryTbxId();

            // Find translations of the given source term for target fuzzy languages
            $groupedTerms = $this->termModel->getAllTermsOfGroup($collectionIds, $termEntryTbxId, $this->targetFuzzyLanguages);

            // If no translations
            if (empty($groupedTerms)) {

                // Setup a flag indicating that there are no translations for the current source term for the languages we need
                $this->notPresentInTbxTarget[$termEntryTbxId] = true;
            }

            // Counter for those of translations which are found in segment target
            $transFound = $this->groupCounter[$termEntryTbxId] ?? 0;

            // Foreach translation existing in task's termcollection(s) for the given source term
            foreach ($groupedTerms as $groupedTerm) {

                // Check whether translation does exist in segment target
                $targetTermIdKey = array_search($groupedTerm['termTbxId'], $targetTermIds);

                // If exists
                if ($targetTermIdKey !== false) {

                    // Increment translation-which-is-found-in-segment-target counter
                    $transFound++;

                    // Unset it from $targetTermIds-array, so that the only tbx ids of terms to be kept where
                    unset($targetTermIds[$targetTermIdKey]);
                }
            }

            //
            $toMarkMemory[$sourceTermId] = $termEntryTbxId;

            // Apply into class vaiable to be accessed from othe methods
            $this->groupCounter[$termEntryTbxId] = $transFound;
        }
        i(['before now', $source], 'a');
        i(['$toMarkMemory', $toMarkMemory], 'a');

        //
        $hasTermsInTarget = count($targetTermIds);

        foreach ($toMarkMemory as $sourceTermId => $termEntryTbxId) {
            $source = $this->insertTransFoundInSegmentClass($source, $sourceTermId, $termEntryTbxId, $hasTermsInTarget);
        }
        i(['now', $source], 'a');


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
    protected function insertTransFoundInSegmentClass(string $seg, $mid, $groupId, $hasTermsInTarget) {
        class_exists('editor_Utils');
        i([$seg, $mid, $groupId, $this->groupCounter], 'a');

        settype($this->groupCounter[$groupId], 'integer');

        // $mid is source term termTbxId
        // $groupId is termEntryTbxId
        $transFound =& $this->groupCounter[$groupId];
        $presentInTbxTarget = empty($this->notPresentInTbxTarget[$groupId]);
        $rCallback = function($matches) use (&$seg, &$transFound, $presentInTbxTarget){
            foreach ($matches as $match) {
                if($presentInTbxTarget) {
                    $cssClassToInsert = ($transFound>0)?'transFound':'transNotFound';

                    if ($cssClassToInsert == 'transNotFound' && $hasTermsInTarget) {

                        // Get term text, what will be red-underlined unless we do addditional check
                        $red = $this->termByTbxId[$mid];


                    }
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
