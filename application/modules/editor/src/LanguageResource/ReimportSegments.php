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
declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_ConfigException;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_Iterator;
use editor_Models_Task as Task;
use editor_Services_Connector;
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\Segment\FilteredIterator;
use ReflectionException;
use stdClass;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Filter_ExtJs6;

class ReimportSegments
{
    public const FILTER_TIMESTAMP = 'timestamp';

    public const FILTER_ONLY_EDITED = 'onlyEdited';

    public const USE_SEGMENT_TIMESTAMP = 'useSegmentTimestamp';

    private const STATE_REIMPORT = 'reimporttm';

    private string $oldState;

    private ZfExtended_Logger $logger;

    public function __construct(
        private readonly LanguageResource $languageResource,
        private readonly Task $task,
    ) {
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws Zend_Exception
     * @throws ReflectionException
     */
    public function reimport(array $params): bool
    {
        $task = $this->task;

        $locked = $task->lock(NOW_ISO, self::STATE_REIMPORT);

        if (! $locked) {
            $this->getLogger()->error(
                'E1169',
                'The task is in use and cannot be reimported into the associated language resources.'
            );

            return false;
        }

        $this->oldState = $task->getState();
        $task->setState(self::STATE_REIMPORT);
        $task->save();
        $task->createMaterializedView();

        $filters = [
            self::FILTER_TIMESTAMP => $params[self::FILTER_TIMESTAMP] ?? null,
            self::FILTER_ONLY_EDITED => $params[self::FILTER_ONLY_EDITED] ?? false,
        ];
        $segments = $this->getSegmentIterator($task, $filters);

        $result = $this->updateSegments(
            $segments,
            $params[self::USE_SEGMENT_TIMESTAMP] ?? false
        );

        $this->reopenTask();

        $message = 'No segments for reimport';
        $params = [
            'taskId' => $this->task->getId(),
            'tmId' => $this->languageResource->getId(),
        ];

        if ($result !== null) {
            $message = 'Task {taskId} re-imported successfully into the desired TM {tmId}';
            $params = array_merge($params, [
                'emptySegments' => $result->emptySegmentsAmount,
                'successfulSegments' => $result->successfulSegmentsAmount,
                'failedSegments' => $result->failedSegmentIds,
            ]);
        }

        $this->getLogger()->info('E0000', $message, $params);

        return true;
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    public function reopenTask(): void
    {
        if ($this->oldState === self::STATE_REIMPORT) {
            $this->oldState = $this->task::STATE_OPEN;
        }
        $this->task->setState($this->oldState);
        $this->task->save();

        if ($this->oldState === $this->task::STATE_END) {
            $this->task->dropMaterializedView();
        }

        $this->task->unlock();
    }

    /**
     * @throws Zend_Exception
     */
    public function getLogger(): ZfExtended_Logger
    {
        if (! isset($this->logger)) {
            $this->logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource', [
                'task' => $this->task ?? null,
                'languageResource' => $this->languageresource ?? null,
            ]);
        }

        return $this->logger;
    }

    /**
     * @throws ReflectionException
     */
    private function getSegmentIterator(Task $task, array $filters): editor_Models_Segment_Iterator
    {
        if (empty($filters)) {
            return ZfExtended_Factory::get(editor_Models_Segment_Iterator::class, [$task->getTaskGuid()]);
        }

        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        $filter = ZfExtended_Factory::get(ZfExtended_Models_Filter_ExtJs6::class, [$segment]);
        $segment->filterAndSort($filter);

        if (isset($filters[self::FILTER_TIMESTAMP])) {
            // all loaded segments are filtered by the given timestamp
            $filterObject = new stdClass();
            $filterObject->field = 'timestamp';
            $filterObject->type = 'string';
            $filterObject->comparison = 'eq';
            $filterObject->value = $filters[self::FILTER_TIMESTAMP];

            $segment->getFilter()->addFilter($filterObject);
        }

        if ($filters[self::FILTER_ONLY_EDITED] ?? false) {
            // all loaded segments are filtered by the states
            $filterObject = new stdClass();
            $filterObject->field = 'autoStateId';
            $filterObject->type = 'notInList';
            $filterObject->comparison = 'in';
            $filterObject->value = [
                AutoStates::NOT_TRANSLATED,
                AutoStates::PRETRANSLATED,
                AutoStates::LOCKED,
                AutoStates::BLOCKED,
            ];

            $segment->getFilter()->addFilter($filterObject);
        }

        return ZfExtended_Factory::get(FilteredIterator::class, [
            $task->getTaskGuid(),
            $segment,
        ]);
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ReflectionException
     * @throws editor_Models_ConfigException
     */
    private function updateSegments(
        editor_Models_Segment_Iterator $segments,
        bool $useSegmentTimestamp
    ): ?ReimportSegmentsResult {
        // in case of filtered segments, the initialization of the segments
        // iterator can result with no segments found for the applied filter
        if ($segments->isEmpty()) {
            return null;
        }

        $assoc = ZfExtended_Factory::get(TaskAssociation::class);

        $assoc->loadByTaskGuidAndTm($this->task->getTaskGuid(), (int) $this->languageResource->getId());

        // check if the current language resources is updatable before updating
        if (empty($assoc->getSegmentsUpdateable())) {
            return null;
        }

        $manager = ZfExtended_Factory::get(editor_Services_Manager::class);

        /** @var UpdatableAdapterInterface|editor_Services_Connector $connector */
        $connector = $manager->getConnector(
            $this->languageResource,
            config: $this->task->getConfig(),
            customerId: (int) $this->task->getCustomerId(),
        );

        $emptySegmentsAmount = 0;
        $successfulSegmentsAmount = 0;
        $failedSegmentsIds = [];
        $firstSegment = null;
        $lastSegment = null;

        $options = [
            UpdatableAdapterInterface::USE_SEGMENT_TIMESTAMP => $useSegmentTimestamp,
            UpdatableAdapterInterface::SAVE_TO_DISK => false,
        ];

        foreach ($segments as $segment) {
            $this->updateSegment(
                $connector,
                $segment,
                $options,
                $emptySegmentsAmount,
                $successfulSegmentsAmount,
                $failedSegmentsIds
            );

            if (null === $firstSegment) {
                $firstSegment = $segment;
                $connector->checkUpdatedSegment($firstSegment);
            }

            $lastSegment = $segment;
        }

        // TODO change to direct call for flushing memory to the disk once it is implemented on t5memory side
        $options[UpdatableAdapterInterface::SAVE_TO_DISK] = true;
        $this->updateSegment(
            $connector,
            $lastSegment,
            $options,
            $emptySegmentsAmount,
            $successfulSegmentsAmount,
            $failedSegmentsIds
        );

        $connector->checkUpdatedSegment($lastSegment);

        return new ReimportSegmentsResult($emptySegmentsAmount, $successfulSegmentsAmount, $failedSegmentsIds);
    }

    private function updateSegment(
        editor_Services_Connector $connector,
        editor_Models_Segment $segment,
        array $options,
        int &$emptySegmentsAmount,
        int &$successfulSegmentsAmount,
        array &$failedSegmentsIds,
    ): void {
        if ($segment->hasEmptySource() || $segment->hasEmptyTarget()) {
            $emptySegmentsAmount++;

            return;
        }

        try {
            try {
                $connector->update($segment, $options);
            } catch (\ZfExtended_Zendoverwrites_Http_Exception|\editor_Services_Connector_Exception) {
                // if the TM is not available (due service restart or whatever)
                // we just wait some time and try it again once.
                sleep(30);
                $connector->update($segment, $options);
            }
        } catch (SegmentUpdateException) {
            $failedSegmentsIds[] = (int) $segment->getId();

            return;
        }

        $successfulSegmentsAmount++;
    }
}
