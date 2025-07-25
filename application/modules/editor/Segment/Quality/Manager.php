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

use MittagQI\Translate5\Segment\Db\Processing;
use MittagQI\ZfExtended\Worker\Queue;

/**
 * The central Quality Manager
 * Orchestrates all Quaities via the editor_Segment_Quality_Provider registry and API
 * All plugins that provide Quality relevant APIs have to register a Quality Provider in the Plugin Init/Bootstrap
 * The first use of the Quality manager instance will lock the registry thus registration of providers in a later phase will lead to exceptions
 */
final class editor_Segment_Quality_Manager
{
    public const CONFIG_MUST_BE_ZERO_QUALITY_ERRORS = 'runtimeOptions.autoQA.mustBeZeroErrorsQualities';

    /**
     * This can be used to disable the AutoQA processing completely. This does not disable the frontend
     * DO ONLY USE FOR DEVELOPMENT PURPOSES
     */
    public const ACTIVE = true;

    /**
     * AutoQA-Operation: Performs a re-evaluation of all qualities for the task, retags all segments
     * This Operation usually tag's terms and spellchecks and may needs a while
     * @throws editor_Task_Operation_Exception
     */
    public static function autoqaOperation(editor_Models_Task $task): int
    {
        // check if the current tsak state allows operations
        $task->checkStateAllowsActions();
        // creates the operation wrapper, that sets the needed task-states
        $operation = editor_Task_Operation::create(editor_Task_Operation::AUTOQA, $task);

        try {
            // this triggers a refresh of the task's TBX cache
            $task->meta()->resetTbxHash([$task->getTaskGuid()]);
            // queues the operation workers
            self::instance()->queueOperation(
                editor_Segment_Processing::RETAG,
                editor_Task_Operation::AUTOQA,
                $task,
                $operation->getWorkerId(),
                ZfExtended_Models_Worker::STATE_PREPARE
            );
            // start the operation
            $operation->start();
            // triggers the worker-queue
            $workerQueue = ZfExtended_Factory::get(Queue::class);
            $workerQueue->trigger();
        } catch (Throwable $e) {
            $operation->onQueueingError();

            throw $e;
        }

        return $operation->getWorkerId();
    }

    /**
     * TagTerma-Operation: renews the term-tagging and related qualities
     * This Operation tag's terms and may needs a while
     * @throws editor_Task_Operation_Exception
     */
    public static function tagtermsOperation(editor_Models_Task $task)
    {
        // no tagterms operation when source/target language similar, see TRANSLATE-2373
        if ($task->isSourceAndTargetLanguageSimilar()) {
            return;
        }
        // check if the current tsak state allows operations
        $task->checkStateAllowsActions();
        // queue a task operation and the depending quality operation
        $operation = editor_Task_Operation::create(editor_Task_Operation::TAGTERMS, $task);

        try {
            // queues the operation workers
            self::instance()->queueOperation(
                editor_Segment_Processing::TAGTERMS,
                editor_Task_Operation::TAGTERMS,
                $task,
                $operation->getWorkerId(),
                ZfExtended_Models_Worker::STATE_PREPARE
            );
            // start the operation
            $operation->start();
            // trigger the workers to work
            $workerQueue = ZfExtended_Factory::get(Queue::class);
            $workerQueue->trigger();
        } catch (Throwable $e) {
            $operation->onQueueingError();

            throw $e;
        }
    }

    private static ?editor_Segment_Quality_Manager $_instance = null;

    /**
     * Holds all quality provider classes before instantiation (which locks adding to this array)
     * Here base quality checks that are always present / not being added by plugins are defined initially
     * @var string[]
     */
    private static array $_provider = [
        editor_Segment_Internal_Provider::class,
        editor_Segment_MatchRate_Provider::class,
        editor_Segment_Mqm_Provider::class,
        editor_Segment_Qm_Provider::class,
        editor_Segment_Length_QualityProvider::class,
        editor_Segment_Empty_QualityProvider::class,
        editor_Segment_Consistent_QualityProvider::class,
        editor_Segment_Whitespace_QualityProvider::class,
        editor_Segment_Numbers_QualityProvider::class,
    ];

    /**
     * @var boolean
     */
    private static bool $_locked = false;

    /**
     * Adds a Provider to the Quality manager
     * @throws ZfExtended_Exception
     */
    public static function registerProvider(string $className)
    {
        if (self::$_locked) {
            throw new ZfExtended_Exception('Adding a Quality Provider after app bootstrapping is not allowed.');
        }
        if (! in_array($className, self::$_provider)) {
            self::$_provider[] = $className;
        }
    }

    public static function instance(): editor_Segment_Quality_Manager
    {
        if (self::$_instance == null) {
            self::$_instance = new editor_Segment_Quality_Manager();
            self::$_locked = true;
        }

        return self::$_instance;
    }

    /**
     * @var editor_Segment_Quality_Provider[]
     */
    private array $registry;

    private ?ZfExtended_Zendoverwrites_Translate $translate = null;

    /**
     * Just a cache for the export types which are defined statically via the TagProviderInterface
     */
    private ?array $exportTypes = null;

    /**
     * The constructor instantiates all providers and locks the registry
     * @throws ZfExtended_Exception
     */
    private function __construct()
    {
        $this->registry = [];
        foreach (self::$_provider as $providerClass) {
            try {
                $provider = new $providerClass();
                /* @var $provider editor_Segment_Quality_Provider */
                $this->registry[$provider->getType()] = $provider;
            } catch (Throwable) {
                throw new ZfExtended_Exception('Quality Provider ' . $providerClass . ' does not exist');
            }
        }
    }

    /**
     * @return boolean
     */
    public function hasProvider(string $type): bool
    {
        return array_key_exists($type, $this->registry);
    }

    public function getProvider(string $type): ?editor_Segment_Quality_Provider
    {
        if (array_key_exists($type, $this->registry)) {
            return $this->registry[$type];
        }

        return null;
    }

    /**
     * Adds the neccessary import workers
     * This is called after the "afterDirectoryParsing" of the FileFileTree Worker
     */
    public function queueImport(editor_Models_Task $task, int $workerParentId = 0, bool $skipCheck = false): void
    {
        if (
            ! $skipCheck
            && $this->isProcessingSkipped(editor_Segment_Processing::IMPORT, $task, true)
        ) {
            return;
        }

        // add starting worker - with the mandatory params
        $workerParams = [
            'processingMode' => editor_Segment_Processing::IMPORT,
            'operationType' => editor_Task_Operation::IMPORT,
            'skipCheck' => $skipCheck,
        ];
        $worker = ZfExtended_Factory::get(editor_Segment_Quality_OperationWorker::class);

        if ($worker->init($task->getTaskGuid(), $workerParams)) {
            $workerId = $worker->queue($workerParentId, null, false);

            if ($workerParentId === 0) {
                // in case there was no parent worker the Quality-Operation will be the parent ...
                $workerParentId = $workerId;
            }

            // add the workers of our providers
            $this->queueProviderWorkers(editor_Segment_Processing::IMPORT, $task, $workerParentId, []);
            // add finishing worker
            $worker = ZfExtended_Factory::get(editor_Segment_Quality_OperationFinishingWorker::class);

            if ($worker->init($task->getTaskGuid(), $workerParams)) {
                $worker->queue($workerParentId, null, false);
            }
        }
    }

    /**
     * Adds the neccessary workers for an operation
     * @throws ReflectionException
     */
    public function queueOperation(
        string $processingMode,
        string $operationType,
        editor_Models_Task $task,
        int $workerParentId,
        string $workerState = null,
    ): void {
        if ($this->isProcessingSkipped($processingMode, $task, true)) {
            return;
        }
        // add starting worker
        $workerParams = [
            'processingMode' => $processingMode,
            'operationType' => $operationType,
        ];
        // mandatory for any quality processing
        $worker = ZfExtended_Factory::get(editor_Segment_Quality_OperationWorker::class);
        if ($worker->init($task->getTaskGuid(), $workerParams)) {
            $workerId = $worker->queue($workerParentId, $workerState, false);
            if ($workerParentId === 0) {
                // in case there was no parent worker the Quality-Operation will be the parent ...
                $workerParentId = $workerId;
            }
            // add the workers of our providers
            $this->queueProviderWorkers($processingMode, $task, $workerParentId, []);
            // add finishing worker
            $worker = ZfExtended_Factory::get(editor_Segment_Quality_OperationFinishingWorker::class);
            if ($worker->init($task->getTaskGuid(), $workerParams)) {
                $worker->queue($workerParentId, null, false);
            }
        }
    }

    /**
     * Prepares the quality workers depending on the context/processing type
     * @throws editor_Models_ConfigException
     * @throws Zend_Exception
     */
    public function prepareOperation(string $processingMode, editor_Models_Task $task, bool $skipCheck): void
    {
        if (! $skipCheck && $this->isProcessingSkipped($processingMode, $task)) {
            return;
        }

        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;

        //  we have to remove all existing qualities when performing an operation ... NOT when running a solitary operation as then the qualities of the other providers remain untouched
        $typeToRemove = editor_Segment_Processing::isSolitaryOperation($processingMode) ? editor_Segment_Processing::getProviderTypeForSolitaryOperation($processingMode) : null;
        editor_Models_Db_SegmentQuality::deleteForTask($task->getTaskGuid(), $typeToRemove);

        // if we have workers we must prepare the State processing table
        if ($this->hasProviderWorkers($processingMode, $qualityConfig)) {
            // create entries for all segmentIds
            $table = new Processing();
            $table->prepareOperation($task->getTaskGuid());

            // maybe some workers need to prepare additional entries by setting custom States
            foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
                $provider = $this->getProvider($type);
                if ($provider->hasOperationWorker($processingMode, $qualityConfig)) {
                    $provider->prepareOperation($task, $processingMode);
                }
            }
        }
    }

    /**
     * Finishes an operation: processes all non-worker providers & saves the processed tags-model back to the segments
     * @throws Zend_Db_Table_Exception
     * @throws editor_Models_ConfigException
     */
    public function finishOperation(string $processingMode, editor_Models_Task $task, bool $skipCheck = false): void
    {
        if (! $skipCheck && $this->isProcessingSkipped($processingMode, $task)) {
            return;
        }

        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        $taskGuid = $task->getTaskGuid();
        $segmentTable = ZfExtended_Factory::get(editor_Models_Db_Segments::class);
        $segmentIds = $segmentTable->getAllIdsForTask($task->getTaskGuid(), true);
        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        // when we had workers we have to use the Processing-table to fetch the segments. Currently, this always will be the case but who knows ...
        $processingTable = ($this->hasProviderWorkers($processingMode, $qualityConfig)) ? new Processing() : null;
        // represent the quality-types that are involved in the processing ... for Solitary Operations this may just be a single type
        $processedTypes = $this->getProviderTypesForProcessing($processingMode);
        // solitary operations use the processing cache just for the state but the segment is saved directly when processing
        // In this case we do not need to process the segments further and can skip that step
        $isSolitaryWorkerProcess = (editor_Segment_Processing::isSolitaryOperation($processingMode)
            && $this->getProvider($processedTypes[0])->hasOperationWorker($processingMode, $qualityConfig));

        if (! $isSolitaryWorkerProcess) {
            $qualities = [];

            foreach ($segmentIds as $segmentId) {
                $tags = null;
                // fetch the segment-tags-model. When there have wbeen worker-operations it will originate from the processing-model
                // when there were no workers involved, the tagsJson of the State is not set and we fetch the contents from the segment-table
                if ($processingTable !== null) {
                    $row = $processingTable->fetchRow($processingTable->select()->where('segmentId = ?', $segmentId));
                    if (! empty($row->tagsJson)) {
                        $tags = editor_Segment_Tags::fromJson($task, $processingMode, $row->tagsJson);
                    }
                }
                if ($tags === null) {
                    $segment->load($segmentId);
                    $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment);
                }
                // process all quality providers that do not have an worker for the current operation
                foreach ($processedTypes as $type) {
                    $provider = $this->getProvider($type);
                    if (! $provider->hasOperationWorker($processingMode, $qualityConfig)) {
                        $tags = $provider->processSegment($task, $qualityConfig, $tags, $processingMode);
                    }
                }
                // we save all qualities at once to reduce db-strain
                $qualities = array_merge($qualities, $tags->extractNewQualities());
                // save the segment tags content back to the segment
                // in an worker-based operation this will save the segments back
                $tags->saveToSegment();
            }

            // save qualities
            editor_Models_Db_SegmentQuality::saveRows($qualities);
        }

        // clean up processing & retrieve the number of processed segments for each state ... when we have a processing
        $processingResult = ($processingTable === null) ? [
            'segments' => count($segmentIds),
        ] : $processingTable->getOperationResult($taskGuid);

        // post actions: post-processing (needed for quality-workers that need to contextualize all segments) or finalization for qualities with operation workers
        foreach ($processedTypes as $type) {
            $provider = $this->getProvider($type);
            if ($provider->hasOperationWorker($processingMode, $qualityConfig)) {
                $provider->finalizeOperation($task, $processingMode, $processingResult);
            } else {
                // Append qualities, that can be detected only after all segments have been processed
                $provider->postProcessTask($task, $qualityConfig, $processingMode);
            }
        }
        // removes all entries from the processing table if the processing-model was used
        if ($processingTable) {
            $processingTable->finishOperation($taskGuid);
        }
    }

    /**
     * @throws editor_Models_ConfigException
     */
    public function processSegment(editor_Models_Segment $segment, editor_Models_Task $task, string $processingMode)
    {
        if ($this->isProcessingSkipped($processingMode, $task)) {
            return;
        }
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment);
        foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
            $tags = $this->getProvider($type)->processSegment($task, $qualityConfig, $tags, $processingMode);
        }
        // saves back to the segment
        $tags->save();
    }

    /**
     * Special API for qualities which can only be evaluated by processing all segments of a task
     * This method is called BEFORE saving the segments and it's repetitions
     * Operations like Import or Analyze will only have ::postProcessTask being called since there are no differences to be detected
     *
     * @throws editor_Models_ConfigException
     */
    public function preProcessTask(editor_Models_Task $task, string $processingMode)
    {
        if ($this->isProcessingSkipped($processingMode, $task)) {
            return;
        }
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
            $this->getProvider($type)->preProcessTask($task, $qualityConfig, $processingMode);
        }
    }

    /**
     * Special API for qualities which can only be evaluated by processing all segments of a task
     * This method is called AFTER saving the segments and it's repetitions
     *
     * @throws editor_Models_ConfigException
     */
    public function postProcessTask(editor_Models_Task $task, string $processingMode)
    {
        if ($this->isProcessingSkipped($processingMode, $task)) {
            return;
        }
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
            $this->getProvider($type)->postProcessTask($task, $qualityConfig, $processingMode);
        }
    }

    /**
     * Alike Segments have a special processing as they clone some qualities from their original segment
     * @throws editor_Models_ConfigException
     */
    public function processAlikeSegment(editor_Models_Segment $segment, editor_Models_Task $task, editor_Segment_Alike_Qualities $alikeQualities)
    {
        if ($this->isProcessingSkipped(editor_Segment_Processing::ALIKE, $task)) {
            return;
        }
        $processingMode = editor_Segment_Processing::ALIKE;
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment);
        $tags->initAlikeQualities($alikeQualities);
        foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
            $tags = $this->getProvider($type)->processSegment($task, $qualityConfig, $tags, $processingMode);
        }
        // saves back to the segment
        $tags->save();
    }

    /**
     * The central API to identify the needed Tag class by classnames and attributes
     * @param string[] $classNames
     * @param string[] $attributes
     */
    public function evaluateInternalTag(string $tagType, string $nodeName, array $classNames, array $attributes, int $startIndex, int $endIndex): ?editor_Segment_Tag
    {
        if (! empty($tagType) && array_key_exists($tagType, $this->registry)) {
            if ($this->registry[$tagType]->isSegmentTag($tagType, $nodeName, $classNames, $attributes)) {
                return $this->registry[$tagType]->createSegmentTag($startIndex, $endIndex, $nodeName, $classNames);
            }

            return null;
        }
        foreach ($this->registry as $type => $provider) {
            if ($provider->isSegmentTag($tagType, $nodeName, $classNames, $attributes)) {
                return $provider->createSegmentTag($startIndex, $endIndex, $nodeName, $classNames);
            }
        }

        return null;
    }

    /**
     * Translates a Segment Quality Type
     * @throws ZfExtended_Exception
     */
    public function translateQualityType(string $type): string
    {
        if ($this->hasProvider($type)) {
            $translation = $this->getProvider($type)->translateType($this->getTranslate());
            if ($translation === null) {
                throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityType: provider of type "' . $type . '" has no translation for the type".');
            }

            return $translation;
        }

        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityType: provider of type "' . $type . '" not present.');
    }

    /**
     * Translates a Segment Quality Type tooltip
     * @throws ZfExtended_Exception
     */
    public function translateQualityTypeTooltip(string $type): string
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->translateTypeTooltip($this->getTranslate());
        }

        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityTypeTooltip: provider of type "' . $type . '" not present.');
    }

    public function translateQualityTypeTooltipCriticalSuffix(string $type): string
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->translateTypeTooltipCriticalSuffix($this->getTranslate());
        }

        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityTypeTooltip: provider of type "' . $type . '" not present.');
    }

    /**
     * Translates a Segment Quality Category tooltip
     * @throws ZfExtended_Exception
     */
    public function translateQualityCategoryTooltip(string $type, string $category, editor_Models_Task $task = null): string
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->translateCategoryTooltip($this->getTranslate(), $category, $task);
        }

        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityCategoryTooltip: provider of type "' . $type . '" not present.');
    }

    /**
     * Translates a Segment Quality Code that is referenced in LEK_segment_quality category in conjunction with type
     * @throws ZfExtended_Exception
     */
    public function translateQualityCategory(string $type, string $category, editor_Models_Task $task): string
    {
        if ($this->hasProvider($type)) {
            $translation = $this->getProvider($type)->translateCategory($this->getTranslate(), $category, $task);
            if ($translation === null) {
                throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityCategory: provider of type "' . $type . '" has no translation of category "' . $category . '".');
            }

            return $translation;
        }

        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityCategory: provider of type "' . $type . '" not present.');
    }

    /**
     * Evaluates, if the quality of the given type has categories
     */
    public function hasCategories(string $type): bool
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->hasCategories();
        }

        return false;
    }

    /**
     * Evaluates, if a quality of the given type renders tags in the tags texts
     */
    public function hasSegmentTags(string $type): bool
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->hasSegmentTags();
        }

        return false;
    }

    /**
     * Evaluates, if a quality of the given type is a type that generally should show up in the filter panel and in the task properties
     */
    public function isFilterableType(string $type): bool
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->isFilterableType();
        }

        return false;
    }

    /**
     * Evaluates, if a quality of the given type and category can be false positive
     */
    public function canBeFalsePositiveCategory(string $type, string $category): bool
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->canBeFalsePositiveCategory($category);
        }

        return false;
    }

    /**
     * Evaluates, if the quality of the given type is fully configured/checked
     */
    public function isFullyCheckedType(string $type, Zend_Config $taskConfig): bool
    {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->isFullyChecked($taskConfig->runtimeOptions->autoQA, $taskConfig);
        }

        return false;
    }

    /**
     * Injects JS stuff into the frontend neeeded initially in the JS
     */
    public function addAppJsData(ZfExtended_View_Helper_Php2JsVars $php2JsVars)
    {
        $typeDefinitions = [];
        foreach ($this->registry as $type => $provider) {
            $definition = $provider->getFrontendTypeDefinition();
            if (! empty($definition)) {
                $typeDefinitions[] = $definition;
            }
        }
        $php2JsVars->set('quality.types', $typeDefinitions);
    }

    /**
     * Retrieves all types of qualities we have. By default only those, that should show up in the filter-panel
     * @return string[]
     * @throws editor_Models_ConfigException
     */
    public function getAllFilterableTypes(editor_Models_Task $task): array
    {
        $types = [];
        $taskConfig = $task->getConfig();
        $qualityConfig = $taskConfig->runtimeOptions->autoQA;
        foreach ($this->registry as $type => $provider) {
            if ($provider->isActive($qualityConfig, $taskConfig) && $provider->isFilterableType()) {
                $types[] = $provider->getType();
            }
        }

        return $types;
    }

    public function getActiveTypeToCategoryMap(?editor_Models_Task $task, Zend_Config $config): iterable
    {
        $qualityConfig = $config->runtimeOptions->autoQA;

        $translate = $this->getTranslate();

        foreach ($this->registry as $provider) {
            if (! $provider->isActive($qualityConfig, $config) && $provider->isFilterableType()) {
                continue;
            }

            $typeLabel = $provider->translateType($translate);

            if (! $provider->hasCategories()) {
                yield "{$provider->getType()}:{$provider->getType()}" => $typeLabel;

                continue;
            }

            foreach ($provider->getAllCategories($task) as $category) {
                if (! is_array($category)) {
                    $key = "{$provider->getType()}:{$category}";

                    yield $key => "$typeLabel: {$provider->translateCategory($translate, $category, $task)}";

                    continue;
                }

                foreach ($category as $subCategory) {
                    $key = "{$provider->getType()}:{$subCategory}";

                    yield $key => "$typeLabel: {$provider->translateCategory($translate, $subCategory, $task)}";
                }
            }
        }
    }

    public function mustBeZeroErrors(string $type, string $category, editor_Models_Task $task): bool
    {
        return (bool) $this->getProvider($type)?->mustBeZeroErrors($type, $category, $task->getConfig());
    }

    public function typeHasMustBeZeroErrorsCategories(string $type, editor_Models_Task $task): bool
    {
        return $this->getProvider($type)?->typeHasMustBeZeroErrorsCategories($type, $task->getConfig());
    }

    public function isFaultyInternalTagType(string $type, string $category): bool
    {
        return editor_Segment_Tag::TYPE_INTERNAL === $type
            && editor_Segment_Internal_TagComparision::isFault($type, $category);
    }

    /**
     * Retrieves the types of qualities that should not show up in the quality panel & quality task views
     * @return string[]
     */
    public function getFilterTypeBlacklist(): array
    {
        $blacklist = [];
        foreach ($this->registry as $type => $provider) {
            if (! $provider->isFilterableType()) {
                $blacklist[] = $provider->getType();
            }
        }

        return $blacklist;
    }

    /**
     * Retrieves all types that will be exported (with further processing)
     */
    public function getAllExportedTypes(): array
    {
        if ($this->exportTypes === null) {
            $this->exportTypes = [];
            foreach ($this->registry as $type => $provider) {
                if ($provider->isExportedTag()) {
                    $this->exportTypes[] = $provider->getType();
                }
            }
        }

        return $this->exportTypes;
    }

    public function getTranslate(): ZfExtended_Zendoverwrites_Translate
    {
        if ($this->translate == null) {
            $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        }

        return $this->translate;
    }

    /**
     * Queues the workers of our providers for an operation
     */
    private function queueProviderWorkers(string $processingMode, editor_Models_Task $task, int $parentWorkerId, array $workerParams)
    {
        if (self::ACTIVE) {
            $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
            foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
                $provider = $this->getProvider($type);
                if ($provider->hasOperationWorker($processingMode, $qualityConfig)) {
                    $provider->addWorker($task, $parentWorkerId, $processingMode, $workerParams);
                }
            }
        }
    }

    /**
     * Evaluates, if there are workers involved in the quality processing for a processing mode
     */
    private function hasProviderWorkers(string $processingMode, Zend_Config $qualityConfig): bool
    {
        foreach ($this->getProviderTypesForProcessing($processingMode) as $type) {
            if ($this->getProvider($type)->hasOperationWorker($processingMode, $qualityConfig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves the provider-types used for a processing-step
     * @return string[]
     * @throws ZfExtended_Exception
     */
    private function getProviderTypesForProcessing(string $processingMode): array
    {
        if (editor_Segment_Processing::isSolitaryOperation($processingMode)) {
            $type = editor_Segment_Processing::getProviderTypeForSolitaryOperation($processingMode);
            if ($this->hasProvider($type)) {
                return [$type];
            }

            return [];
        }

        return array_keys($this->registry);
    }

    /**
     * Returns true if the processing should be completely skipped for a processing-mode
     * @return bool
     * @throws editor_Models_ConfigException
     */
    private function isProcessingSkipped(string $processingMode, editor_Models_Task $task, bool $logSkipping = false)
    {
        if (! self::ACTIVE) {
            return true;
        }
        if ($processingMode === editor_Segment_Processing::IMPORT && (! $task->getTaskType()->isAutoStartAutoQA() || ! $task->getConfig()->runtimeOptions->autoQA->autoStartOnImport)) {
            if ($logSkipping) {
                $task->logger('editor.task')->warn('E1432', 'AutoQA-step of the import process - is deactivated');
            }

            return true;
        }

        return false;
    }
}
