<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * The Quality provider 
 * This class just provides the translations for the filter backend
 */
class editor_Plugins_SpellCheck_QualityProvider extends editor_Segment_Quality_Provider {

    /**
     * Quality type
     *
     * @var string
     */
    protected static $type = 'spellcheck';

    /**
     * Flag indicating whether this quality has categories
     *
     * @var bool
     */
    protected static $hasCategories = true;

    /**
     * SpellCheck segment processor instance
     *
     * @var editor_Plugins_SpellCheck_SegmentProcessor
     */
    protected static $_processor = null;

    /**
     * Method to check whether this quality is turned On
     *
     * @param Zend_Config $qualityConfig
     * @param Zend_Config $taskConfig
     * @return bool
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return $qualityConfig->enableSegmentSpellCheck == 1;
    }

    /**
     * Get SpellCheck segment processor instance
     *
     * @return editor_Plugins_SpellCheck_SegmentProcessor|null
     */
    public function getProcessor() {
        return self::$_processor ?? self::$_processor = ZfExtended_Factory::get('editor_Plugins_SpellCheck_SegmentProcessor');
    }

    /**
     * We will run with any processing mode
     *
     * @param string $processingMode
     * @return bool
     */
    public function hasOperationWorker(string $processingMode) : bool {
        return Zend_Registry::get('config')->runtimeOptions->autoQA->enableSegmentSpellCheck;
    }

    /**
     * Add worker
     *
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     * @param string $processingMode
     * @param array $workerParams
     */
    public function addWorker(editor_Models_Task $task, int $parentWorkerId, string $processingMode, array $workerParams = []) {

        // Get version of task's target language, applicable for use with LanguageTool, if supported
        $spellCheckLang = $this->getProcessor()->getConnector()->getSpellCheckLangByTaskTargetLangId($task->getTargetLang());

        // Crucial: add processing-mode to worker params
        $workerParams = [
            'processingMode' => $processingMode,
            'resourcePool' => 'import',
            'spellCheckLang' => $spellCheckLang
        ] + $workerParams;

        /* @var $worker editor_Plugins_SpellCheck_Worker_Import */
        $worker = ZfExtended_Factory::get('editor_Plugins_SpellCheck_Worker_Import');

        /* @var $meta editor_Models_Segment_Meta */
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');

        // Create segments_meta-field 'spellcheckState' if not exists
        $meta->addMeta('spellcheckState', $meta::META_TYPE_STRING,
            editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_UNCHECKED,
            'Contains the SpellCheck-state for this segment while importing', 36);

        // Reset already checked segments back to unchecked
        $this->prepareSegments($task, $meta);

        // If worker init attempt failed - log error
        if (!$worker->init($task->getTaskGuid(), $workerParams)) {
            $this->log->error('E1128', 'SpellCheckImport Worker can not be initialized!', [ 'parameters' => $workerParams ]);
            return;
        }

        // If task target language is not supported by LanguageTool
        if (!$spellCheckLang) {

            // Log event
            $worker->getLogger()->error('E1413', 'SpellCheck can not work when target language is not supported by LanguageTool.', ['task' => $task]);

            // Prevent worker from being initialized/queued
            return false;
        }

        // Add to queue
        $worker->queue($parentWorkerId);
    }

    /**
     * Check segment against quality
     *
     * {@inheritDoc}
     * @see editor_Segment_Quality_Provider::processSegment()
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        // If this check is turned Off in config - return $tags
        if (!$qualityConfig->enableSegmentSpellCheck == 1) {
            return $tags;
        }

        // If current task's target lang is not supported by LanguageTool - return
        if (!$spellCheckLang = $this->getProcessor()->getConnector()->getSpellCheckLangByTaskTargetLangId($task->getTargetLang())) {
            return $tags;
        }

        // If processing mode is 'alike'
        if ($processingMode == editor_Segment_Processing::ALIKE){

            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);

        // Else
        } else if ($processingMode == editor_Segment_Processing::EDIT) {

            // Do process
            $this->getProcessor()->process([$tags], null, false, $spellCheckLang);
        }

        // Return
        return $tags;
    }

    /**
     * Translate quality type
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return string|null
     * @throws Zend_Exception
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('LanguageTool');
    }

    /**
     * Translate category tooltips
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @param string $category
     * @param editor_Models_Task $task
     * @return string|null
     */
    public function translateCategoryTooltip(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch ($category) {
            case editor_Plugins_SpellCheck_Check::CHARACTERS              : return $translate->_('The text contains characters that are garbled or incorrect or that are not used in the language in which the content appears.');
            case editor_Plugins_SpellCheck_Check::MISTRANSLATION          : return $translate->_('The content of the target mistranslates the content of the source.');
            case editor_Plugins_SpellCheck_Check::OMISSION                : return $translate->_('Necessary text has been omitted from the localization or source.');
            case editor_Plugins_SpellCheck_Check::UNTRANSLATED            : return $translate->_('Content that has been intended for translation is left untranslated.');
            case editor_Plugins_SpellCheck_Check::ADDITION                : return $translate->_('The translated text contains inappropriate additions.');
            case editor_Plugins_SpellCheck_Check::DUPLICATION             : return $translate->_('Content has been duplicated improperly.');
            case editor_Plugins_SpellCheck_Check::INCONSISTENCY           : return $translate->_('The text is inconsistent with itself or is translated inconsistently (NB: not for use with terminology inconsistency).');
            case editor_Plugins_SpellCheck_Check::LEGAL                   : return $translate->_('The text is legally problematic (e.g., it is specific to the wrong legal system).');
            case editor_Plugins_SpellCheck_Check::FORMATTING              : return $translate->_('The text is formatted incorrectly.');
            case editor_Plugins_SpellCheck_Check::INCONSISTENT_ENTITIES   : return $translate->_('The source and target text contain different named entities (dates, times, place names, individual names, etc.)');
            case editor_Plugins_SpellCheck_Check::NUMBERS                 : return $translate->_('Numbers are inconsistent between source and target.');
            case editor_Plugins_SpellCheck_Check::MARKUP                  : return $translate->_('There is an issue related to markup or a mismatch in markup between source and target.');
            case editor_Plugins_SpellCheck_Check::LENGTH                  : return $translate->_('There is a significant difference in source and target length.');
            case editor_Plugins_SpellCheck_Check::NON_CONFORMANCE         : return $translate->_('The content is deemed to show poor statistical conformance to a reference corpus. Higher severity values reflect poorer conformance.');
            case editor_Plugins_SpellCheck_Check::UNCATEGORIZED           : return $translate->_('The issue either has not been categorized or cannot be categorized.');
            case editor_Plugins_SpellCheck_Check::OTHER                   : return $translate->_('Any issue that cannot be assigned to any values listed above.');

            case editor_Plugins_SpellCheck_Check::REGISTER                : return $translate->_('The text is written in the wrong linguistic register of uses slang or other language variants inappropriate to the text.');
            case editor_Plugins_SpellCheck_Check::LOCALE_SPECIFIC_CONTENT : return $translate->_('The localization contains content that does not apply to the locale for which it was prepared.');
            case editor_Plugins_SpellCheck_Check::LOCALE_VIOLATION        : return $translate->_('Text violates norms for the intended locale.');
            case editor_Plugins_SpellCheck_Check::GENERAL_STYLE           : return $translate->_('The text contains stylistic errors.');
            case editor_Plugins_SpellCheck_Check::PATTERN_PROBLEM         : return $translate->_('The text fails to match a pattern that defines allowable content (or matches one that defines non-allowable content).');
            case editor_Plugins_SpellCheck_Check::WHITESPACE              : return $translate->_('There is a mismatch in whitespace between source and target content or the text violates specific rules related to the use of whitespace.');
            case editor_Plugins_SpellCheck_Check::TERMINOLOGY             : return $translate->_('An incorrect term or a term from the wrong domain was used or terms are used inconsistently.');
            case editor_Plugins_SpellCheck_Check::INTERNATIONALIZATION    : return $translate->_('There is an issue related to the internationalization of content.');

            case editor_Plugins_SpellCheck_Check::GRAMMAR                 : return $translate->_('The text contains a grammatical error (including errors of syntax and morphology).');
            case editor_Plugins_SpellCheck_Check::MISPELLING              : return $translate->_('The text contains a misspelling.');
            case editor_Plugins_SpellCheck_Check::TYPOGRAPHICAL           : return $translate->_('The text has typographical errors such as omitted/incorrect punctuation, incorrect capitalization, etc.');
        }
        return NULL;
    }

    /**
     * Translate category
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @param string $category
     * @param editor_Models_Task $task
     * @return string|null
     */
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch ($category) {
            case editor_Plugins_SpellCheck_Check::GROUP_GENERAL           : return $translate->_('General');
            case editor_Plugins_SpellCheck_Check::CHARACTERS              : return $translate->_('Characters');
            case editor_Plugins_SpellCheck_Check::MISTRANSLATION          : return $translate->_('Mistranslation');
            case editor_Plugins_SpellCheck_Check::OMISSION                : return $translate->_('Omission');
            case editor_Plugins_SpellCheck_Check::UNTRANSLATED            : return $translate->_('Untranslated');
            case editor_Plugins_SpellCheck_Check::ADDITION                : return $translate->_('Addition');
            case editor_Plugins_SpellCheck_Check::DUPLICATION             : return $translate->_('Duplication');
            case editor_Plugins_SpellCheck_Check::INCONSISTENCY           : return $translate->_('Inconsistency');
            case editor_Plugins_SpellCheck_Check::LEGAL                   : return $translate->_('Legal');
            case editor_Plugins_SpellCheck_Check::FORMATTING              : return $translate->_('Formatting');
            case editor_Plugins_SpellCheck_Check::INCONSISTENT_ENTITIES   : return $translate->_('Inconsistent entities');
            case editor_Plugins_SpellCheck_Check::NUMBERS                 : return $translate->_('Numbers');
            case editor_Plugins_SpellCheck_Check::MARKUP                  : return $translate->_('Markup');
            case editor_Plugins_SpellCheck_Check::LENGTH                  : return $translate->_('Length');
            case editor_Plugins_SpellCheck_Check::NON_CONFORMANCE         : return $translate->_('Non-conformance');
            case editor_Plugins_SpellCheck_Check::UNCATEGORIZED           : return $translate->_('Uncategorized');
            case editor_Plugins_SpellCheck_Check::OTHER                   : return $translate->_('Other');

            case editor_Plugins_SpellCheck_Check::GROUP_STYLE             : return $translate->_('Style');
            case editor_Plugins_SpellCheck_Check::REGISTER                : return $translate->_('Register');
            case editor_Plugins_SpellCheck_Check::LOCALE_SPECIFIC_CONTENT : return $translate->_('Locale-specific content');
            case editor_Plugins_SpellCheck_Check::LOCALE_VIOLATION        : return $translate->_('Locale violation');
            case editor_Plugins_SpellCheck_Check::GENERAL_STYLE           : return $translate->_('General style');
            case editor_Plugins_SpellCheck_Check::PATTERN_PROBLEM         : return $translate->_('Pattern problem');
            case editor_Plugins_SpellCheck_Check::WHITESPACE              : return $translate->_('Whitespace');
            case editor_Plugins_SpellCheck_Check::TERMINOLOGY             : return $translate->_('Terminology');
            case editor_Plugins_SpellCheck_Check::INTERNATIONALIZATION    : return $translate->_('Internationalization');

            case editor_Plugins_SpellCheck_Check::GRAMMAR                 : return $translate->_('Grammar');
            case editor_Plugins_SpellCheck_Check::MISPELLING              : return $translate->_('Spelling');
            case editor_Plugins_SpellCheck_Check::TYPOGRAPHICAL           : return $translate->_('Typographical');
        }
        return NULL;
    }

    /**
     * Categories in this quality
     *
     * @param editor_Models_Task $task
     * @return array
     */
    public function getAllCategories(editor_Models_Task $task) : array {
        return [
            editor_Plugins_SpellCheck_Check::CHARACTERS,
            editor_Plugins_SpellCheck_Check::MISTRANSLATION,
            editor_Plugins_SpellCheck_Check::OMISSION,
            editor_Plugins_SpellCheck_Check::UNTRANSLATED,
            editor_Plugins_SpellCheck_Check::ADDITION,
            editor_Plugins_SpellCheck_Check::DUPLICATION,
            editor_Plugins_SpellCheck_Check::INCONSISTENCY,
            editor_Plugins_SpellCheck_Check::LEGAL,
            editor_Plugins_SpellCheck_Check::FORMATTING,
            editor_Plugins_SpellCheck_Check::INCONSISTENT_ENTITIES,
            editor_Plugins_SpellCheck_Check::NUMBERS,
            editor_Plugins_SpellCheck_Check::MARKUP,
            editor_Plugins_SpellCheck_Check::LENGTH,
            editor_Plugins_SpellCheck_Check::NON_CONFORMANCE,
            editor_Plugins_SpellCheck_Check::UNCATEGORIZED,
            editor_Plugins_SpellCheck_Check::OTHER,

            editor_Plugins_SpellCheck_Check::REGISTER,
            editor_Plugins_SpellCheck_Check::LOCALE_SPECIFIC_CONTENT,
            editor_Plugins_SpellCheck_Check::LOCALE_VIOLATION,
            editor_Plugins_SpellCheck_Check::GENERAL_STYLE,
            editor_Plugins_SpellCheck_Check::PATTERN_PROBLEM,
            editor_Plugins_SpellCheck_Check::WHITESPACE,
            editor_Plugins_SpellCheck_Check::TERMINOLOGY,
            editor_Plugins_SpellCheck_Check::INTERNATIONALIZATION,

            editor_Plugins_SpellCheck_Check::GRAMMAR,
            editor_Plugins_SpellCheck_Check::MISPELLING,
            editor_Plugins_SpellCheck_Check::TYPOGRAPHICAL,
        ];
    }

    /**
     * Reset already checked segments back to unchecked
     *
     * @param editor_Models_Task $task
     * @param editor_Models_Segment_Meta $meta
     */
    private function prepareSegments(editor_Models_Task $task, editor_Models_Segment_Meta $meta) {

        // Reset status to unchecked for checked segments
        $meta->db->update([
            'spellcheckState' => editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_UNCHECKED
        ],[
            'taskGuid = ?' => $task->getTaskGuid(),
            'spellcheckState = ?' => editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_CHECKED,
        ]);
    }
}
