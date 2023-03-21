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

use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Service;
use MittagQI\Translate5\Plugins\SpellCheck\Segment\Check;
use MittagQI\Translate5\Plugins\SpellCheck\Segment\Configuration;
use MittagQI\Translate5\Plugins\SpellCheck\Segment\Processor;
use MittagQI\Translate5\Plugins\SpellCheck\Worker;

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
     * @var Service|null
     */
    private ?Service $languagetoolService = null;

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
     * We will run with any processing mode if configured
     * @param string $processingMode
     * @param Zend_Config $taskConfig
     * @return bool
     */
    public function hasOperationWorker(string $processingMode, Zend_Config $qualityConfig) : bool {
        return $qualityConfig->enableSegmentSpellCheck == 1;
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
        $resourcePool = 'import';
        // Get spellcheck-version of task's target language, applicable for use with LanguageTool, if supported (Theoretically different spellcheckers may support different language-sets, then the random 'import' url already is a problem!!)
        $spellCheckLang = $this->getSpellcheckLanguage($task, $resourcePool);

        // If task target language is not supported by LanguageTool we can skip adding workers as there is nothing to do
        // HINT: the existing qualities will be removed in the prepeareOperation call anyway
        if (!$spellCheckLang) {
            // Log event
            $this->getLogger($processingMode)->error('E1413', 'SpellCheck can not work when target language is not supported by LanguageTool.', [ 'task' => $task ]);
            return;
        }

        // Crucial: add processing-mode and spellcheck-lang to worker params
        $workerParams = [
            'processingMode' => $processingMode,
            'resourcePool' => $resourcePool,
            'spellCheckLang' => $spellCheckLang
        ] + $workerParams;

        $worker = ZfExtended_Factory::get(Worker::class);
        // If worker init attempt failed - log error
        if (!$worker->init($task->getTaskGuid(), $workerParams)) {
            $this->getLogger($processingMode)->error('E1476', 'SpellCheck Worker can not be initialized!', [ 'parameters' => $workerParams ]);
            return;
        }
        $worker->queue($parentWorkerId);
    }

    /**
     * @param editor_Models_Task $task
     * @param string $processingMode
     */
    public function prepareOperation(editor_Models_Task $task, string $processingMode) {
        // when spellchecking is done with workers all qualities that might exist have to be removed before
        // they can not be re-identified with the  Qualities Object in the SegmentTags
        $qualitiesTable = new editor_Models_Db_SegmentQuality();
        $qualitiesTable->removeByTaskGuidAndType($task->getTaskGuid(), $this->getType());
    }

    /**
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @param array $processingResult
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public function finalizeOperation(editor_Models_Task $task, string $processingMode, array $processingResult){
        // the processing might excluded spellchecking, we only add an event when a result is available
        // also we do not report when no spellchecking was done due to missing spellcheck-language
        if(array_key_exists(Service::SERVICE_ID, $processingResult) && $this->getSpellcheckLanguage($task, 'import')){
            $this->getLogger($processingMode)->info('E1419', 'SpellCheck overall run done - {segmentCounts}', [
                'task' => $task,
                'segmentCounts' => 'tagged '.$processingResult[Service::SERVICE_ID].' of '.$processingResult['segments'],
            ]);
        }
        // we report any defect segments we found during processing
        Processor::reportDefectSegments($task, $processingMode);
    }

    /**
     * @param editor_Models_Task $task
     * @param Zend_Config $qualityConfig
     * @param editor_Segment_Tags $tags
     * @param string $processingMode
     * @return editor_Segment_Tags
     * @throws ZfExtended_Exception
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        // If this check is turned Off in config - return $tags
        if (!$qualityConfig->enableSegmentSpellCheck == 1) {
            return $tags;
        }

        $service = $this->getLanguagetoolService();
        $serviceUrl = $service->getPooledServiceUrl('gui');
        $processor = new Processor($task, $service, $processingMode, $serviceUrl, false);

        // If current task's target lang is not supported by LanguageTool - return
        if (!$processor->getSpellcheckLanguage()) {
            return $tags;
        }
        // If processing mode is 'alike'
        if ($processingMode === editor_Segment_Processing::ALIKE){

            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);

        } else if ($processingMode === editor_Segment_Processing::EDIT) {

            $processor->process($tags, false);
        }

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
     * @return string
     */
    public function translateCategoryTooltip(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : string {
        switch ($category) {
            case Check::CHARACTERS              : return $translate->_('The text contains characters that are garbled or incorrect or that are not used in the language in which the content appears.');
            case Check::DUPLICATION             : return $translate->_('Content has been duplicated improperly.');
            case Check::INCONSISTENCY           : return $translate->_('The text is inconsistent with itself or is translated inconsistently (NB: not for use with terminology inconsistency).');
            case Check::LEGAL                   : return $translate->_('The text is legally problematic (e.g., it is specific to the wrong legal system).');
            case Check::UNCATEGORIZED           : return $translate->_('The issue either has not been categorized or cannot be categorized.');

            case Check::REGISTER                : return $translate->_('The text is written in the wrong linguistic register of uses slang or other language variants inappropriate to the text.');
            case Check::LOCALE_SPECIFIC_CONTENT : return $translate->_('The localization contains content that does not apply to the locale for which it was prepared.');
            case Check::LOCALE_VIOLATION        : return $translate->_('Text violates norms for the intended locale.');
            case Check::GENERAL_STYLE           : return $translate->_('The text contains stylistic errors.');
            case Check::PATTERN_PROBLEM         : return $translate->_('The text fails to match a pattern that defines allowable content (or matches one that defines non-allowable content).');
            case Check::WHITESPACE              : return $translate->_('There is a mismatch in whitespace between source and target content or the text violates specific rules related to the use of whitespace.');
            case Check::TERMINOLOGY             : return $translate->_('An incorrect term or a term from the wrong domain was used or terms are used inconsistently.');
            case Check::INTERNATIONALIZATION    : return $translate->_('There is an issue related to the internationalization of content.');
            case Check::NON_CONFORMANCE         : return $translate->_('Statistically detect wrong use of words that are easily confused');

            case Check::GRAMMAR                 : return $translate->_('The text contains a grammatical error (including errors of syntax and morphology).');
            case Check::MISSPELLING             : return $translate->_('The text contains a misspelling.');
            case Check::TYPOGRAPHICAL           : return $translate->_('The text has typographical errors such as omitted/incorrect punctuation, incorrect capitalization, etc.');
        }
        return '';
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
            case Check::GROUP_GENERAL           : return $translate->_('General');
            case Check::CHARACTERS              : return $translate->_('Characters');
            case Check::DUPLICATION             : return $translate->_('Duplication');
            case Check::INCONSISTENCY           : return $translate->_('Inconsistency');
            case Check::LEGAL                   : return $translate->_('Legal');
            case Check::UNCATEGORIZED           : return $translate->_('Uncategorized');

            case Check::GROUP_STYLE             : return $translate->_('Style');
            case Check::REGISTER                : return $translate->_('Register');
            case Check::LOCALE_SPECIFIC_CONTENT : return $translate->_('Locale-specific content');
            case Check::LOCALE_VIOLATION        : return $translate->_('Locale violation');
            case Check::GENERAL_STYLE           : return $translate->_('General style');
            case Check::PATTERN_PROBLEM         : return $translate->_('Pattern problem');
            case Check::WHITESPACE              : return $translate->_('Whitespace');
            case Check::TERMINOLOGY             : return $translate->_('Terminology');
            case Check::INTERNATIONALIZATION    : return $translate->_('Internationalization');
            case Check::NON_CONFORMANCE         : return $translate->_('Non-conformance');

            case Check::GRAMMAR                 : return $translate->_('Grammar');
            case Check::MISSPELLING             : return $translate->_('Spelling');
            case Check::TYPOGRAPHICAL           : return $translate->_('Typographical');
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
            Check::GROUP_GENERAL => [
                Check::CHARACTERS,
                Check::DUPLICATION,
                Check::INCONSISTENCY,
                Check::LEGAL,
                Check::UNCATEGORIZED,
            ],
            Check::GROUP_STYLE => [
                Check::REGISTER,
                Check::LOCALE_SPECIFIC_CONTENT,
                Check::LOCALE_VIOLATION,
                Check::GENERAL_STYLE,
                Check::PATTERN_PROBLEM,
                Check::WHITESPACE,
                Check::TERMINOLOGY,
                Check::INTERNATIONALIZATION,
                Check::NON_CONFORMANCE,
            ],
            Check::GRAMMAR,
            Check::MISSPELLING,
            Check::TYPOGRAPHICAL,
        ];
    }

    /**
     * Adds Frontend-configurations for the quality types
     * @return array{
     *      field: string,
     *      columnPostfixes: string[]
     * }
     */
    public function getFrontendTypeDefinition() : array {
        return [
            'field' => 'spellCheck',
            'columnPostfixes' => ['EditColumn'],
        ];
    }

    /**
     * @param string $processingMode
     * @return ZfExtended_Logger
     * @throws Zend_Exception
     */
    private function getLogger(string $processingMode): ZfExtended_Logger
    {
        return Zend_Registry::get('logger')->cloneMe(Configuration::getLoggerDomain($processingMode));
    }

    /**
     * @return Service
     * @throws ZfExtended_Exception
     */
    private function getLanguagetoolService(): Service
    {
        if($this->languagetoolService === null){
            $this->languagetoolService = editor_Plugins_SpellCheck_Init::createService('languagetool');
        }
        return $this->languagetoolService;
    }

    /**
     * @param editor_Models_Task $task
     * @param string $resourcePool
     * @return string|false
     * @throws ZfExtended_Exception
     * @throws DownException
     */
    private function getSpellcheckLanguage(editor_Models_Task $task, string $resourcePool): string|false
    {
        return $this->getLanguagetoolService()->getAdapter(null, $resourcePool)->getSpellCheckLangByTaskTargetLangId($task->getTargetLang());
    }
}
