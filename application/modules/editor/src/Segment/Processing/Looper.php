<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment\Processing;

use editor_Models_Task;
use Exception;
use MittagQI\Translate5\Segment\AbstractProcessor;
use MittagQI\Translate5\Segment\Db\Processing;
use MittagQI\ZfExtended\Worker\Exception\SetDelayedException;

/**
 * A processing Looper processes segments/processing-states in a loop until all segments are in state "processed" or higher
 * This usually is done with multiple loopers in parallel
 */
final class Looper
{
    /**
     * Defines the waiting-time in seconds we will wait, if our workload is temporarily blocked by other
     * processing loopers/workers
     */
    public const BLOCKED_DELAY = 5;

    /**
     * Defines the amount of segments we delay looping workers instead of pausing them
     * This cannot be calculated since all segment-loopers have vastly different batch-sizes
     */
    public const MIN_DELAY_SEGMENTS = 150;

    private State $state;

    /**
     * @var State[]
     */
    private array $toProcess = [];

    private int $batchSize;

    private bool $isReprocessing = false;

    /**
     * Between processing segments and fetching new ones a pause can be configured to make db-deadlocks less probable
     */
    private int $loopingPause;

    /**
     * Tracks the number of processed segments
     */
    private int $numProcessed = 0;

    public function __construct(
        private ProgressInterface $progressReporter,
        private editor_Models_Task $task,
        private AbstractProcessor $processor,
        private int $workerIndex,
    ) {
        $this->loopingPause = $processor->getLoopingPause();
        $this->state = new State($processor->getServiceId());
        $this->batchSize = $processor->getBatchSize();
    }

    /**
     * Loops through the segments tagsStates to process
     * returns true after all segments have been processed
     * @param bool $fromTheTop : if segments should be fetched ascending or descending
     * @throws Exception
     */
    public function run(string $processingMode, bool $fromTheTop = true, bool $doDebug = false): bool
    {
        // looping through segments
        while ($this->fetchNext($fromTheTop)) {
            // create segment tags from State
            $segmentsTags = [];
            foreach ($this->toProcess as $state) {
                $segmentsTags[] = $state->getSegmentTags($this->task, $processingMode);
            }
            // we wrap the processing of a batch in a transaction
            // when the processor leads to an exception, this transaction needs to be closed ...
            // we do that only, if the processing saves back to the tag-state, otherwise we create nested locks in an potentially uncertain order
            if (count($segmentsTags) === 1) {
                $this->processor->process($segmentsTags[0]);
                $this->numProcessed++;
            } else {
                $this->processor->processBatch($segmentsTags);
                $this->numProcessed += count($segmentsTags);
            }
            // report the progress of processed segments
            $this->progressReporter->reportProcessed($this->numProcessed);
            // FALLBACK: if there are states still in "processing", we need to reset their state
            // this may happens when processors decide, there is nothing to do with the segment -
            // the saving of the segments also saves the state to avoid updating a row twice
            foreach ($this->toProcess as $state) {
                if ($state->isProcessing()) {
                    $state->setProcessed();
                }
            }
            // if configured, we wait before fetching the next segments
            if ($this->loopingPause > 0) {
                usleep($this->loopingPause);
            }
        }

        return true;
    }

    /**
     * Retrieves the current progress of the processing as float between 0 and 1
     */
    public function getProgress(): float
    {
        return $this->state->calculateProgress($this->task->getTaskGuid());
    }

    /**
     * Retrieves the TagStates currently being processed
     * @return State[]
     */
    public function getProcessedStates(): array
    {
        return $this->toProcess;
    }

    /**
     * Sets all passed states to the given error-state and finishes pocessing / resets global state
     * @param State[] $problematicStates
     */
    public function setUnprocessedStates(array $problematicStates, int $errorState, bool $doDebug = false): void
    {
        foreach ($problematicStates as $state) {
            if ($state->getState() != State::PROCESSED) {
                $state->setState($errorState);
                if ($doDebug) {
                    error_log('Looper: set State to ' . $errorState . ' and finish processing for segment ' .
                        $state->getSegmentId());
                }
            }
        }
    }

    /**
     * Finishes pocessing / resets global state for all passed states
     * @param State[] $states
     */
    public function setProcessingFinished(array $states, bool $doDebug = false): void
    {
        if (! empty($states)) {
            $segmentIds = array_map(fn ($item) => $item->getSegmentId(), $states);
            $table = new Processing();
            $affected = $table->endProcessingForStates($segmentIds);
            if ($doDebug && $affected > 0) {
                error_log('Looper: finished Processing hard for ' . $affected . ' states');
            }
        }
    }

    public function isReprocessingLoop(): bool
    {
        return $this->isReprocessing;
    }

    /**
     * Retrieves the next segments to process and handles blocked unprocessed states
     * The latter either finishes the workload or throws an set-delayed-exception
     * Returns if segments could be found
     *
     * @throws SetDelayedException
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Models_Db_Exceptions_DeadLockHandler
     * @phpstan-impure
     */
    private function fetchNext(bool $fromTheTop): bool
    {
        $taskGuid = $this->task->getTaskGuid();
        if ($this->fetchNextStates($fromTheTop, $taskGuid)) {
            return true;
        }
        // may we are having only blocked segments, then we need to delay!
        // this may happens, when other processors are blocking our workload ... a rare case though
        if ($this->state->hasBlockedUnprocessed($taskGuid)) {
            // different behaviour for normal tasks, smaller tasks & API-tests
            if ($this->needsToWaitForLockedSegments()) {
                // only the first looper (worker-index 0) for the current processor will continue the loop
                // (but with sleeps) because we want to avoid being dependent on the cronjobs
                // we must respect the max. import time / max delay time to avoid creating endless processes
                // higher indexes will simply finish
                $until = (defined('APPLICATION_APITEST') && APPLICATION_APITEST) ?
                    \MittagQI\Translate5\Test\Api\Helper::RELOAD_TASK_LIMIT // max-sleep for API-tests is shorter ...
                    : \ZfExtended_Worker_Abstract::MAX_SINGLE_DELAY_LIMIT;
                if ($this->workerIndex === 0) {
                    sleep(self::BLOCKED_DELAY);
                    $until -= self::BLOCKED_DELAY;
                    // Why in heaven is phpstan saying "Negated boolean expression is always true" for the fetch ???
                    while (
                        ! $this->fetchNextStates($fromTheTop, $taskGuid) &&
                        $this->state->hasBlockedUnprocessed($taskGuid)
                    ) {
                        sleep(2);
                        $until -= 2;
                        if ($until <= 0) {
                            return false;
                        }
                    }

                    return true;
                }
            } elseif ($this->needsDelayForLockedSegments()) {
                // set our worker to delayed
                // we do this without increasing the delay-counter as we do know (if everything is properly coded)
                // that other processing-workers will either "work through" OR
                // set themselves to delayed if the service is down
                // a blocked workload must not lead to a terminating worker ...
                throw new SetDelayedException(
                    $this->processor->getServiceId(),
                    null,
                    static::BLOCKED_DELAY
                );
            }
        }

        return false;
    }

    /**
     * Fetch the next states to process, either processing or reprocessing mode
     * Returns, if there was something found
     *
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Models_Db_Exceptions_DeadLockHandler
     * @phpstan-impure
     */
    private function fetchNextStates(bool $fromTheTop, string $taskGuid): bool
    {
        $this->isReprocessing = false;
        $this->toProcess = $this->state->fetchNextStates(State::UNPROCESSED, $taskGuid, $fromTheTop, $this->batchSize);
        // may we are in     *  the reprocessing phase
        if (empty($this->toProcess)) {
            $this->toProcess = $this->state->fetchNextStates(State::REPROCESS, $taskGuid, $fromTheTop, 1);
            $this->isReprocessing = (count($this->toProcess) > 0);
        }

        return ! empty($this->toProcess);
    }

    /**
     * Evaluates, if a worker needs to be delayed when there are remaining segments that currently cannot be processed
     * This may is limited by ratio of batches & segments overall for tasks with low number of segments
     */
    private function needsDelayForLockedSegments(): bool
    {
        // hint: if there are blocked segments, these are blocked segments for all running loopers
        // we do not need to delay more batches than the task has segments
        $maxInstances = (int) ceil((int) $this->task->getSegmentCount() / $this->batchSize);

        // normally $maxInstances cannot be 0, but we do not know in which contexts the class may be used
        return $this->workerIndex < $maxInstances || $maxInstances === 0;
    }

    /**
     * Evaluates, if it is better to wait for the blocked segments to become unblocked
     * instead of using a delayed-exception
     * This is crucial for API-tests (where no cronjob is available)
     * And smaller tasks that should not take excessively long to import
     */
    private function needsToWaitForLockedSegments(): bool
    {
        return (
            $this->task->getSegmentCount() <= self::MIN_DELAY_SEGMENTS ||
            (defined('APPLICATION_APITEST') && APPLICATION_APITEST)
        );
    }
}
