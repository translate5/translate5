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

/**
 * A processing Looper processes segments/processing-states in a loop until all segments are in state "processed" or higher
 * This usually is done with multiple loopers in parallel
 */
final class Looper
{
    private State $state;

    /**
     * @var State[]
     */
    private array $toProcess;

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
        private AbstractProcessor $processor
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
        $this->fetchNext($fromTheTop);
        // looping through segments
        while (! empty($this->toProcess)) {
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
            // if configured, we wait before fetching the next segments
            if ($this->loopingPause > 0) {
                usleep($this->loopingPause);
            }
            $this->fetchNext($fromTheTop);
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
     * Retrieves the TagStates currently being procesed
     * @return State[]
     */
    public function getProcessedStates(): array
    {
        return $this->toProcess;
    }

    /**
     * @param State[] $problematicStates
     */
    public function setUnprocessedStates(array $problematicStates, int $errorState, bool $doDebug = false)
    {
        foreach ($problematicStates as $state) {
            if ($state->getState() != State::PROCESSED) {
                $state->setState($errorState);
                if ($doDebug) {
                    error_log('Looper: set State to ' . $errorState . ' for segment ' . $state->getSegmentId());
                }
            }
        }
    }

    public function isReprocessingLoop(): bool
    {
        return $this->isReprocessing;
    }

    /**
     * Retrieves the next segments to process
     */
    private function fetchNext(bool $fromTheTop)
    {
        $this->isReprocessing = false;
        $this->toProcess = $this->state->fetchNextStates(State::UNPROCESSED, $this->task->getTaskGuid(), $fromTheTop, $this->batchSize);
        if (empty($this->toProcess)) {
            $this->toProcess = $this->state->fetchNextStates(State::REPROCESS, $this->task->getTaskGuid(), $fromTheTop, 1);
            $this->isReprocessing = (count($this->toProcess) > 0);
        }
    }
}
