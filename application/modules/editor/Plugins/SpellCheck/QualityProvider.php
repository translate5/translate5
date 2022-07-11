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

        // Lock oversized segments and reset already checked segments back to unchecked
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
     * Translate category
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @param string $category
     * @param editor_Models_Task $task
     * @return string|null
     */
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            case editor_Plugins_SpellCheck_Check::SPELL:   return $translate->_('Spell');
            case editor_Plugins_SpellCheck_Check::GRAMMAR: return $translate->_('Grammar');
            case editor_Plugins_SpellCheck_Check::STYLE:   return $translate->_('Style');
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
            editor_Plugins_SpellCheck_Check::SPELL,
            editor_Plugins_SpellCheck_Check::GRAMMAR,
            editor_Plugins_SpellCheck_Check::STYLE,
        ];
    }

    /**
     * Lock oversized segments and reset already checked segments back to unchecked
     *
     * @param editor_Models_Task $task
     * @param editor_Models_Segment_Meta $meta
     */
    private function prepareSegments(editor_Models_Task $task, editor_Models_Segment_Meta $meta) {

        // Get config
        $config = Zend_Registry::get('config');

        // Get maximum count of characters supported by LanguageTool
        $maxCharacterCount = $config->runtimeOptions->plugins->SpellCheck->languagetool->maxSegmentCharacterCount ?? 20000;

        // Setup oversized status for oversized segments
        $meta->db->update([
            'termtagState' => editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE
        ],[
            'taskGuid = ?' => $task->getTaskGuid(),
            'sourceCharacterCount >= ?' => $maxCharacterCount,
        ]);

        // Reset status to unchecked for checked segments
        $meta->db->update([
            'spellcheckState' => editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_UNCHECKED
        ],[
            'taskGuid = ?' => $task->getTaskGuid(),
            'spellcheckState = ?' => editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_CHECKED,
        ]);
    }
}
