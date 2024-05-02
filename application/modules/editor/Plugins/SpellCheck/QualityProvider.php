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
use MittagQI\Translate5\Segment\Db\Processing;
use MittagQI\Translate5\Segment\Processing\State;

/**
 * The Quality provider
 * This class just provides the translations for the filter backend
 */
class editor_Plugins_SpellCheck_QualityProvider extends editor_Segment_Quality_Provider
{
    /**
     * Quality type
     *
     * @var string
     */
    protected static $type = 'spellcheck';

    private ?Service $languagetoolService = null;

    /**
     * Method to check whether this quality is turned On
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return $qualityConfig->enableSegmentSpellCheck == 1;
    }

    /**
     * We will run with any processing mode if configured
     */
    public function hasOperationWorker(string $processingMode, Zend_Config $qualityConfig): bool
    {
        return $qualityConfig->enableSegmentSpellCheck == 1;
    }

    /**
     * Add worker
     */
    public function addWorker(editor_Models_Task $task, int $parentWorkerId, string $processingMode, array $workerParams = [])
    {
        $resourcePool = 'import';
        // Get spellcheck-version of task's target language, applicable for use with LanguageTool, if supported (Theoretically different spellcheckers may support different language-sets, then the random 'import' url already is a problem!!)
        $spellCheckLang = $this->getSpellcheckLanguage($task, $resourcePool);

        // If task target language is not supported by LanguageTool we can skip adding workers as there is nothing to do
        // HINT: the existing qualities will be removed in the prepeareOperation call anyway
        if (! $spellCheckLang) {
            // Log event
            $this->getLogger($processingMode)->warn('E1413', 'SpellCheck can not work when target language is not supported by LanguageTool.', [
                'task' => $task,
            ]);

            return;
        }

        // Crucial: add processing-mode and spellcheck-lang to worker params
        $workerParams = [
            'processingMode' => $processingMode,
            'resourcePool' => $resourcePool,
            'spellCheckLang' => $spellCheckLang,
        ] + $workerParams;

        $worker = ZfExtended_Factory::get(Worker::class);
        // If worker init attempt failed - log error
        if (! $worker->init($task->getTaskGuid(), $workerParams)) {
            $this->getLogger($processingMode)->error('E1476', 'SpellCheck Worker can not be initialized!', [
                'parameters' => $workerParams,
            ]);

            return;
        }
        $worker->queue($parentWorkerId);
    }

    public function prepareOperation(editor_Models_Task $task, string $processingMode)
    {
        // when spellchecking is done with workers all qualities that might exist have to be removed before
        // they can not be re-identified with the  Qualities Object in the SegmentTags
        $qualitiesTable = new editor_Models_Db_SegmentQuality();
        $qualitiesTable->removeByTaskGuidAndType($task->getTaskGuid(), $this->getType());
        // find non-editable segments and mark them as unprocessable if not configured to do so
        if (! $task->getConfig()->runtimeOptions->plugins->SpellCheck->checkReadonlySegments) {
            $segmentsTable = ZfExtended_Factory::get(editor_Models_Db_Segments::class);
            $noneditableSegmentIds = $segmentsTable->getAllIdsForTask($task->getTaskGuid(), false, [
                'editable = ?' => 0,
            ]);
            if (! empty($noneditableSegmentIds)) {
                $processingTable = new Processing();
                // set the noneditable segments to ignore
                $processingTable->setSegmentsToState($noneditableSegmentIds, Service::SERVICE_ID, State::IGNORED);
            }
        }
    }

    /**
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public function finalizeOperation(editor_Models_Task $task, string $processingMode, array $processingResult)
    {
        // the processing might excluded spellchecking, we only add an event when a result is available
        // also we do not report when no spellchecking was done due to missing spellcheck-language
        if (array_key_exists(Service::SERVICE_ID, $processingResult) && $this->getSpellcheckLanguage($task, 'import')) {
            $this->getLogger($processingMode)->info('E1419', 'SpellCheck overall run done - {segmentCounts}', [
                'task' => $task,
                'segmentCounts' => 'tagged ' . $processingResult[Service::SERVICE_ID] . ' of ' . $processingResult['segments'],
            ]);
        }
        // we report any defect segments we found during processing
        Processor::reportDefectSegments($task, $processingMode);
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode): editor_Segment_Tags
    {
        // If this check is turned Off in config - return $tags
        if (! $qualityConfig->enableSegmentSpellCheck == 1) {
            return $tags;
        }

        $service = $this->getLanguagetoolService();
        $serviceUrl = $service->getPooledServiceUrl('gui');
        $processor = new Processor($task, $service, $processingMode, $serviceUrl, false);

        // If current task's target lang is not supported by LanguageTool - return
        if (! $processor->getSpellcheckLanguage()) {
            return $tags;
        }
        // If processing mode is 'alike'
        if ($processingMode === editor_Segment_Processing::ALIKE) {
            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);
        } elseif ($processingMode === editor_Segment_Processing::EDIT) {
            // process just editable segments unless configured otherwise
            if ($tags->getSegment()->getEditable() || $task->getConfig()->runtimeOptions->plugins->SpellCheck->checkReadonlySegments) {
                $processor->process($tags, false);
            }
        }

        return $tags;
    }

    /**
     * Translate quality type
     *
     * @throws Zend_Exception
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate): ?string
    {
        return $translate->_('LanguageTool');
    }

    /**
     * Translate category tooltips
     */
    public function translateCategoryTooltip(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task): string
    {
        return match ($category) {
            Check::CHARACTERS => $translate->_('The text contains characters that are garbled or incorrect or that are not used in the language in which the content appears.'),
            Check::DUPLICATION => $translate->_('Content has been duplicated improperly.'),
            Check::INCONSISTENCY => $translate->_('The text is inconsistent with itself or is translated inconsistently (NB: not for use with terminology inconsistency).'),
            Check::LEGAL => $translate->_('The text is legally problematic (e.g., it is specific to the wrong legal system).'),
            Check::UNCATEGORIZED => $translate->_('The issue either has not been categorized or cannot be categorized.'),
            Check::REGISTER => $translate->_('The text is written in the wrong linguistic register of uses slang or other language variants inappropriate to the text.'),
            Check::LOCALE_SPECIFIC_CONTENT => $translate->_('The localization contains content that does not apply to the locale for which it was prepared.'),
            Check::LOCALE_VIOLATION => $translate->_('Text violates norms for the intended locale.'),
            Check::GENERAL_STYLE => $translate->_('The text contains stylistic errors.'),
            Check::PATTERN_PROBLEM => $translate->_('The text fails to match a pattern that defines allowable content (or matches one that defines non-allowable content).'),
            Check::WHITESPACE => $translate->_('There is a mismatch in whitespace between source and target content or the text violates specific rules related to the use of whitespace.'),
            Check::TERMINOLOGY => $translate->_('An incorrect term or a term from the wrong domain was used or terms are used inconsistently.'),
            Check::INTERNATIONALIZATION => $translate->_('There is an issue related to the internationalization of content.'),
            Check::NON_CONFORMANCE => $translate->_('Statistically detect wrong use of words that are easily confused'),
            Check::NUMBERS => '',
            Check::GRAMMAR => $translate->_('The text contains a grammatical error (including errors of syntax and morphology).'),
            Check::MISSPELLING => $translate->_('The text contains a misspelling.'),
            Check::TYPOGRAPHICAL => $translate->_('The text has typographical errors such as omitted/incorrect punctuation, incorrect capitalization, etc.'),
            default => '',
        };
    }

    public function translateCategory(
        ZfExtended_Zendoverwrites_Translate $translate,
        string $category,
        ?editor_Models_Task $task
    ): ?string {
        return match ($category) {
            Check::GROUP_GENERAL => $translate->_('General'),
            Check::CHARACTERS => $translate->_('Characters'),
            Check::DUPLICATION => $translate->_('Duplication'),
            Check::INCONSISTENCY => $translate->_('Inconsistency'),
            Check::LEGAL => $translate->_('Legal'),
            Check::UNCATEGORIZED => $translate->_('Uncategorized'),
            Check::GROUP_STYLE => $translate->_('Style'),
            Check::REGISTER => $translate->_('Register'),
            Check::LOCALE_SPECIFIC_CONTENT => $translate->_('Locale-specific content'),
            Check::LOCALE_VIOLATION => $translate->_('Locale violation'),
            Check::GENERAL_STYLE => $translate->_('General style'),
            Check::PATTERN_PROBLEM => $translate->_('Pattern problem'),
            Check::WHITESPACE => $translate->_('Whitespace'),
            Check::TERMINOLOGY => $translate->_('Terminology'),
            Check::INTERNATIONALIZATION => $translate->_('Internationalization'),
            Check::NON_CONFORMANCE => $translate->_('Non-conformance'),
            Check::NUMBERS => $translate->_('Numbers formatting'),
            Check::GRAMMAR => $translate->_('Grammar'),
            Check::MISSPELLING => $translate->_('Spelling'),
            Check::TYPOGRAPHICAL => $translate->_('Typographical'),
            default => null,
        };
    }

    /**
     * Categories in this quality
     */
    public function getAllCategories(?editor_Models_Task $task): array
    {
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
                Check::NUMBERS,
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
    public function getFrontendTypeDefinition(): array
    {
        return [
            'field' => 'spellCheck',
            'columnPostfixes' => ['EditColumn'],
        ];
    }

    /**
     * @throws Zend_Exception
     */
    private function getLogger(string $processingMode): ZfExtended_Logger
    {
        return Zend_Registry::get('logger')->cloneMe(Configuration::getLoggerDomain($processingMode));
    }

    /**
     * @throws ZfExtended_Exception
     */
    private function getLanguagetoolService(): Service
    {
        if ($this->languagetoolService === null) {
            $this->languagetoolService = editor_Plugins_SpellCheck_Init::createService('languagetool');
        }

        return $this->languagetoolService;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws DownException
     */
    private function getSpellcheckLanguage(editor_Models_Task $task, string $resourcePool): string|false
    {
        return $this->getLanguagetoolService()->getAdapter(null, $resourcePool)->getSpellCheckLangByTaskTargetLangId($task->getTargetLang());
    }
}
