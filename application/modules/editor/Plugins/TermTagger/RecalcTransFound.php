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

    public $exists               = [];
    public $trans = [];
    public $homonym                = [];
    public $srcIdA          = [];
    public $trgIdA          = [];
    public $trgTextA        = [];

    /**
     * @param string $srcId
     * @return string
     */
    protected function getMarkByTbxId(string $srcId) {

        // If given source term is NOT found in db
        if (!$src = $this->exists[$srcId] ?? 0) {

            // Setup 'transNotDefined'-class
            return 'transNotDefined';

        // Else if found, but it has NO translations in db for the target fuzzy languages
        } else if (!$transIdA = array_column($this->trans[$src['termEntryTbxId']] ?? [], 'termTbxId')) {

            // Setup 'transNotDefined'-class
            return 'transNotDefined';

        // Else if at least one of target terms is a translation for the current source term
        } else if ($transTermId = array_intersect($transIdA, $this->trgIdA)[0] ?? 0) {

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
     * Preload data, sufficient for being further used to detect correct source terms marks
     *
     * @throws Zend_Db_Statement_Exception
     */
    protected function preload() {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Reset data arrays
        $this->homonym = $this->trans = $this->trgTextA = [];

        // Get merged list of term tbx ids detected in source and target
        $tbxIdA = array_unique(array_merge($this->srcIdA, $this->trgIdA));

        // Get merged list of source and target fuzzy languages
        $fuzzy = array_merge($this->sourceFuzzyLanguages, $this->targetFuzzyLanguages);

        // Get `termEntryTbxId` and `term` for each term tbx id detected in source and/or target
        $this->exists = $db->query('
            SELECT `termTbxId`, `termEntryTbxId`, `term` 
            FROM `terms_term` 
            WHERE 1
             AND `termTbxId` IN ("'. join('","', $tbxIdA) . '")
             AND `collectionId` IN (' . join(',', $this->collectionIds) . ')
            LIMIT ' . count($tbxIdA) . '             
        ')->fetchAll(PDO::FETCH_UNIQUE);

        // Get all terms (from source and target), grouped by their termEntryTbxId
        $termsByEntry = $db->query('
            SELECT `termEntryTbxId`, `termEntryTbxId`, `term`, `termTbxId`, `languageId`
            FROM `terms_term`
            WHERE 1
              AND `termEntryTbxId` IN ("' . join('","', array_column($this->exists, 'termEntryTbxId')) . '")
              AND `collectionId`   IN (' . join(',', $this->collectionIds) . ')
              AND `languageId`     IN (' . join(',', $fuzzy) . ')
        ')->fetchAll(PDO::FETCH_GROUP);

        // Foreach source term
        foreach ($this->srcIdA as $srcId) {

            // If NOT exists in db - skip
            if (!$src = $this->exists[$srcId] ?? 0) {
                continue;
            }

            // Pick translations for target fuzzy languages
            foreach ($termsByEntry[$src['termEntryTbxId']] as $term) {
                if (in_array($term['languageId'], $this->targetFuzzyLanguages)) {
                    $this->trans[$src['termEntryTbxId']] []= $term['termTbxId'];
                }
            }

            // Pick homonyms under the target terms' termEntries
            foreach ($this->trgIdA as $trgTbxId) {
                if ($trg = $this->exists[$trgTbxId] ?? 0) {
                    foreach ($termsByEntry[$trg['termEntryTbxId']] as $term) {
                        if (in_array($term['languageId'], $this->sourceFuzzyLanguages)
                            && $term['term'] == $src['term']
                            && $term['termTbxId'] != $srcId) {
                            $this->homonym[$srcId] []= $term['termTbxId'];
                        }
                    }
                }
            }
        }

        // Collect target terms texts
        foreach ($this->trgIdA as $trgId) {
            if ($text = $this->exists[$trgId]['term'] ?? 0) {
                $this->trgTextA []= $text;
            }
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
        // If termTagger's markTransFoundLegacy-config is set to 1 - return source as is
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
        $this->srcIdA = $this->termModel->getTermMidsFromSegment($source);
        $this->trgIdA = $this->termModel->getTermMidsFromSegment($target);

        // If no source terms detected - return source as is
        if (!count($this->srcIdA)) {
            return $source;
        }

        // Preload data
        $this->preload();

        //
        $markA = $this->getMarkBySrcIdA();

        class_exists('editor_Utils');
        i([
            'source' => $source,
            'target' => $target,
            'srcIdA' => $this->srcIdA,
            'trgIdA' => $this->trgIdA,
            'exists' => $this->exists,
            'trans' => $this->trans,
        ], 'a');

        // Recalc transNotFound/transNotDefined/transFound marks
        foreach ($markA as $tbxId => $mark) {
            $source = $this->insertMark($source, $tbxId, $mark);
        }

        // Return source text
        return $source;
    }

    /**
     *
     */
    public function getMarkBySrcIdA() {

        // Marks array
        $mark = [];

        // Foreach source term tbx id
        foreach ($this->srcIdA as $srcId) {

            // Get css class
            $mark[$srcId] = $this->getMarkByTbxId($srcId);

            // If translation was found or such source term does not exists in db
            if ($mark[$srcId] == 'transFound' || !isset($this->detected[$srcId])) {

                // Skip to next source term
                continue;
            }

            continue; // temporary

            // If source term has homonyms among target terms' termEntries
            if ($this->homonym[$srcId] ?? 0) {

                // Foreach homonym
                foreach ($this->homonym[$srcId] as $homonym_srcId) {

                    // Get css class
                    $mark[$srcId] = $this->getMarkByTbxId($homonym_srcId);

                    // If it's 'transFound' - stop homonym walkthrough
                    if ($mark[$srcId] == 'transFound') {
                        break;
                    }
                }

            // Else
            } else {

                // Get source term's termEntryTbxId
                $entryId = $this->detected[$srcId]['termEntryTbxId'];

                // If there are no source term translations
                if (!$transTextA = array_column($this->trans[$entryId] ?? [], 'term')) {

                    // Setup 'transNotDefined'-class
                    $mark[$srcId] = 'transNotDefined';

                // Else if at least one of target terms is a translation for the current source term
                } else if ($transText = array_intersect($transTextA, $this->trgTextA)[0] ?? 0) {

                    // Remove first found term text from $transTextA
                    unset($this->trgTextA[array_search($transText, $this->trgTextA)]);

                    // Setup 'transFound'-class
                    $mark[$srcId] = 'transFound';
                }
            }
        }

        // Return marks for all terms within current segment source text
        return $mark;
    }

    /**
     * Remove potentially incorrect transFound, transNotFound and transNotDefined inserted by termtagger
     *
     * @param string $content
     * @return string
     */
    protected function removeExistingFlags($content) {

        // List
        $del = ['transFound', 'transNotFound', 'transNotDefined'];

        //
        return preg_replace_callback('/(<div[^>]*class=")([^"]*term[^"]*)("[^>]*>)/', function($matches) use ($del){

            //
            $classesFound = explode(' ', $matches[2]);

            // Remove the unwanted css classes by array_diff:
            return $matches[1] . join(' ', array_diff($classesFound, $del)) . $matches[3];

        }, $content);
    }

    /**
     * Insert the css-class transFound/transNotFound/transNotDefined into css-class of the term-div tag with the corresponding $tbxId
     *
     * @param string $source
     * @param $tbxId
     * @param $mark
     * @return string
     */
    protected function insertMark(string $source, $tbxId, $mark) {

        // Tag regular expression
        $rex = '~<div[^>]*data-tbxid="' . $tbxId .'"[^>]*>~';

        // Inject $mark into css classes list
        preg_replace_callback($rex, function($matches) use (&$source, $mark) {

            // Foreach matched tag (e.g. if more than one match it means that terms having same tbxId were detected)
            foreach ($matches as $match) {

                // Replacement
                $replace = $match;

                // If there is no class-attrbite at all
                if (strpos($match, ' class=') === false) {

                    // Append it, empty for now
                    $replace = str_replace('<div', '<div class=""', $replace);
                }

                // Append $mark to class list
                $replace = preg_replace('~( class="[^"]*)"~', '$1 ' . $mark . '"', $replace);

                // Replace original version opening tag with modified one
                $source = preg_replace('~' . $match . '~', $replace, $source, 1);
            }
        }, $source);

        // Return
        return $source;
    }
}
