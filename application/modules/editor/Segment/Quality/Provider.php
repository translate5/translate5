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
 * Base Implementation for all Quality Providers
 */
abstract class editor_Segment_Quality_Provider implements editor_Segment_TagProviderInterface
{
    /**
     * Retrieves our type
     * @return string
     */
    public static function qualityType()
    {
        return static::$type;
    }

    /**
     * MUST be set in inheriting classes
     * @var string
     */
    protected static $type = null;

    /**
     * @var bool
     */
    protected static $hasCategories = true;

    /**
     * MUST be set in inheriting classes if there is a related segment tag
     * @var string
     */
    protected static $segmentTagClass = 'editor_Segment_AnyTag';

    public function __construct()
    {
        if (static::$type == null) {
            throw new ZfExtended_Exception(get_class($this) . ' must have a ::$type property defined');
        }
    }

    /**
     * Retrieves the provider type that is a system-wide unique string to identify the provider
     */
    public function getType(): string
    {
        return static::$type;
    }

    /**
     * Retrieves if the provider has an own worker for the given processing type
     */
    public function hasOperationWorker(string $processingMode, Zend_Config $qualityConfig): bool
    {
        return false;
    }

    public function addWorker(editor_Models_Task $task, int $parentWorkerId, string $processingMode, array $workerParams = [])
    {
    }

    /**
     * This method will be called for all Providers that have operation workers after
     * the (temporary) processing-model has been created
     */
    public function prepareOperation(editor_Models_Task $task, string $processingMode)
    {
    }

    /**
     * This method will be called for all Providers that have operation workers to finalize the operation
     * The param $processingResult will hold the number of processed items for all segments and each service
     */
    public function finalizeOperation(editor_Models_Task $task, string $processingMode, array $processingResult)
    {
    }

    /**
     * Processes the Segment and it's tags for the editing (which is unthreaded)
     * Note: the return value is used for further processing so it might even be possible to create a new tags-object though this is highly unwanted
     * Note: When the provider is not active, this must be considered in the implementing code
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode): editor_Segment_Tags
    {
        return $tags;
    }

    /**
     * Do preparations for cases when we need full list of task's segments to be analysed for quality detection
     * On Import and other operations, only ::postProcessTask is called since there are no differences to be detected
     */
    public function preProcessTask(editor_Models_Task $task, Zend_Config $qualityConfig, string $processingMode)
    {
    }

    /**
     * Update qualities for cases when we need full list of task's segments to be analysed for quality detection
     */
    public function postProcessTask(editor_Models_Task $task, Zend_Config $qualityConfig, string $processingMode)
    {
    }

    /* *************** Translation API *************** */

    /**
     * Returns a translation for the Provider itself
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate): ?string
    {
        return null;
    }

    /**
     * Returns a translation for the Provider tooltip
     */
    public function translateTypeTooltip(ZfExtended_Zendoverwrites_Translate $translate): string
    {
        return '';
    }

    public function translateTypeTooltipCriticalSuffix(ZfExtended_Zendoverwrites_Translate $translate): string
    {
        return $translate->_('Alle Fehler der folgenden Kategorie sollten behoben ODER auf “falscher Fehler” gesetzt werden');
    }

    /**
     * Returns a translation for a Quality.
     * These Codes are stored in the category column of the LEK_segment_quality model
     * Because MQM translations are task-specific, the task is needed
     */
    public function translateCategory(
        ZfExtended_Zendoverwrites_Translate $translate,
        string $category,
        ?editor_Models_Task $task
    ): ?string {
        return null;
    }

    /**
     * Translate quality category tooltip
     */
    public function translateCategoryTooltip(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task): string
    {
        return '';
    }

    public function translateCategoryTooltipCriticalSuffix(
        ZfExtended_Zendoverwrites_Translate $translate,
        string $category,
        editor_Models_Task $task
    ): string {
        return $translate->_('Alle Fehler der folgenden Kategorie sollten behoben ODER auf “falscher Fehler” gesetzt werden');
    }

    /* *************** REST view API *************** */

    /**
     * Retrieves, if the quality is configuered to be active
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return true;
    }

    public function mustBeZeroErrors(string $type, ?string $category, Zend_Config $taskConfig): bool
    {
        $mustBeZeroErrorsQualities = $taskConfig->runtimeOptions->autoQA->mustBeZeroErrorsQualities ?? null;
        $mustBeZeroErrorsQualities = $mustBeZeroErrorsQualities ? $mustBeZeroErrorsQualities->toArray() : [];

        if (empty($mustBeZeroErrorsQualities)) {
            return false;
        }

        return in_array("$type:$category", $mustBeZeroErrorsQualities, true);
    }

    public function typeHasMustBeZeroErrorsCategories(string $type, Zend_Config $taskConfig): bool
    {
        $mustBeZeroErrorsQualities = $taskConfig->runtimeOptions->autoQA->mustBeZeroErrorsQualities ?? null;
        $mustBeZeroErrorsQualities = $mustBeZeroErrorsQualities ? $mustBeZeroErrorsQualities->toArray() : [];

        if (empty($mustBeZeroErrorsQualities)) {
            return false;
        }

        foreach ($mustBeZeroErrorsQualities as $quality) {
            if (str_contains($quality, "$type:")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves, if the Quality has tags in the segment texts present
     */
    public function hasSegmentTags(): bool
    {
        return true;
    }

    /**
     * Retrieves, if the quality type is filterable (will be shown in the filter panel or task panel)
     */
    public function isFilterableType(): bool
    {
        return true;
    }

    /**
     * Retrieves, if the given category can be a false positive
     */
    public function canBeFalsePositiveCategory(string $category): bool
    {
        return true;
    }

    /**
     * Retrieves, if the Quality type is properly configured/checked (all configurations active)
     */
    public function isFullyChecked(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return true;
    }

    /**
     * Retrieves, if a quality has categories
     */
    public function hasCategories(): bool
    {
        return static::$hasCategories;
    }

    /**
     * Retrieves all Categories a quality can have
     *
     * @return string[]
     */
    public function getAllCategories(?editor_Models_Task $task): array
    {
        return [];
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
        return [];
    }

    /* *************** Tag provider API *************** */

    /**
     * Quality Providers must use the same key to identify the provider & all of it's tags
     * {@inheritDoc}
     * @see editor_Segment_TagProviderInterface::getTagType()
     */
    public function getTagType(): string
    {
        return static::$type;
    }

    public function isSegmentTag(string $type, string $nodeName, array $classNames, array $attributes): bool
    {
        return false;
    }

    public function isExportedTag(): bool
    {
        return false;
    }

    public function createSegmentTag(int $startIndex, int $endIndex, string $nodeName, array $classNames): editor_Segment_Tag
    {
        $className = static::$segmentTagClass;

        return new $className($startIndex, $endIndex);
    }

    public function getTagIndentificationClass(): ?string
    {
        if ($this->hasSegmentTags()) {
            return $this->createSegmentTag(0, 0, 'span', [])->getIdentificationClass();
        }

        return null;
    }

    public function getTagNodeName(): ?string
    {
        // Quirk: we must pass a node-name & classes here just to fulfill the interface which is meant to identify the class, those props are usually overwritten in the class
        if ($this->hasSegmentTags()) {
            return $this->createSegmentTag(0, 0, 'span', [])->getName();
        }

        return null;
    }
}
