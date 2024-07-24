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

use Exception;
use MittagQI\Translate5\PooledService\Worker as PooledServiceWorker;
use MittagQI\Translate5\Segment\AbstractProcessor;
use ZfExtended_Logger;

/**
 * A processing worker processes segments in a loop until no unprocessed segments are available for the task
 */
abstract class Worker extends PooledServiceWorker implements ProgressInterface
{
    /**
     * Defines the number of segments after which the progress is reported
     */
    public const PROGRESS_INTERVAL = 50;

    protected ZfExtended_Logger $logger;

    protected Looper $looper;

    protected AbstractProcessor $processor;

    protected string $processingMode;

    /**
     * To avoid deadlocks it is attempted to fetch segments from the top or back in an alternating manner
     */
    protected bool $fromTheTop = true;

    /**
     * Tracks how often the progress was reported
     */
    protected int $numReports = 0;

    /**
     * Must be implemented to create the logger
     * This function is also used in a static context and must not use internal dependencies
     */
    abstract protected function createLogger(string $processingMode): ZfExtended_Logger;

    /**
     * Creates the Processor
     */
    abstract protected function createProcessor(): AbstractProcessor;

    /**
     * Will be called when the looper has an Exception and can be used to intercept the Exception
     * The returned integer steers the behaviour of the looper:
     * - a positive integer causes the looping to continue and processing the remaining segments there might be
     * - "0" halts the looping/processing and causes the worker to finish without exception
     * - a negative integer leads to the passed exception being thrown ending the processing
     * @param State[] $problematicStates
     */
    protected function onLooperException(Exception $loopedProcessingException, array $problematicStates, bool $isReprocessing): int
    {
        return -1;
    }

    protected function validateParameters(array $parameters): bool
    {
        // required param defines the mode as defined in editor_Segment_Processing
        if (array_key_exists('processingMode', $parameters)) {
            $this->processingMode = $parameters['processingMode'];
        } else {
            return false;
        }

        return parent::validateParameters($parameters);
    }

    public function onInit(array $parameters): bool
    {
        if (parent::onInit($parameters)) {
            // this ensures, that worker 0 ... 2 ... are fetching processing-states from the top
            // while 1 ... 3 ... fetch from the back.
            //In theory, this should make deadlocks less likely
            $this->fromTheTop = $this->workerIndex % 2 === 0;

            return true;
        }

        return false;
    }

    public function reportProcessed(int $numProcessed): void
    {
        // when the num of processed segments exceeds our next progress interval
        // we report the achieved progress to our worker-model
        if ($numProcessed > ($this->numReports + 1) * self::PROGRESS_INTERVAL) {
            $this->updateProgress($this->looper->getProgress());
            $this->numReports++;
        }
    }

    protected function work(): bool
    {
        $this->processor = $this->createProcessor();
        if ($this->doDebug) {
            error_log('PooledService/Processing Worker: ' . get_class($this) . '|' . $this->workerModel->getSlot() . ': work for ' . $this->processingMode . ' using slot ' . $this->workerModel->getSlot() . ' with processor ' . get_class($this->processor));
        }
        // special: some processors may decide not to process - usually because conditions not yet have been clear in queueing-phase
        // simply all workers with higher index will terminate then
        if ($this->processor->prepareWorkload($this->workerIndex)) {
            // loop through the segments to process
            $this->logger = $this->createLogger($this->processingMode);
            $this->looper = new Looper($this, $this->task, $this->processor);
            $this->doLoop();
        } else {
            if ($this->doDebug) {
                error_log('PooledService/Processing Worker: ' . get_class($this) . ' with index ' . $this->workerIndex . ' terminates because the processor ' . get_class($this->processor) . ' decided processing is not neccessary');
            }
        }

        return true;
    }

    /**
     * Sets all Segments in the batch, that are not of state processed, to the given state
     * @param State[] $problematicStates
     */
    protected function setUnprocessedStates(array $problematicStates, int $errorState)
    {
        $this->looper->setUnprocessedStates($problematicStates, $errorState, $this->doDebug);
    }

    /**
     * Loops through the segments until the looper returns
     * stops/continues on Exception according the decision in ::onLooperException
     * @throws Exception
     */
    private function doLoop()
    {
        $isFinished = false;
        while (! $isFinished) {
            try {
                $isFinished = $this->looper->run($this->processingMode, $this->fromTheTop, $this->doDebug);
            } catch (Exception $processingException) {
                if ($this->doDebug) {
                    error_log('PooledService/Processing Worker: ' . get_class($this) . '|' . $this->workerModel->getSlot() . ': Loop exception: ' . $processingException->getMessage());
                }
                $flag = $this->onLooperException($processingException, $this->looper->getProcessedStates(), $this->looper->isReprocessingLoop());
                if ($flag > 0) {
                    // let the loop continue processing the next segments and retrying the failed segment later on (if the exception-handling is correctly implemented)
                    $isFinished = false;
                } elseif ($flag === 0) {
                    // this finishes the loop and the whole processing without an exception
                    $isFinished = true;
                } else {
                    // exception should be bubbled up (presumably terminating the operation/import
                    throw $processingException;
                }
            }
        }
    }
}
