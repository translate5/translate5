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
abstract class Worker extends PooledServiceWorker
{
    /**
     * @var ZfExtended_Logger
     */
    protected ZfExtended_Logger $logger;

    /**
     * @var Looper
     */
    protected Looper $looper;

    /**
     * @var AbstractProcessor
     */
    protected AbstractProcessor $processor;

    /**
     * @var string
     */
    protected string $processingMode;

    /**
     * To avoid deadlocks it is attempted to fetch segments from the top or back in an alternating manner
     * @var bool
     */
    protected bool $fromTheTop = true;

    /**
     * Must be implemented to create the logger
     * This function is also used in a static context and must not use internal dependencies
     * @param string $processingMode
     * @return ZfExtended_Logger
     */
    abstract protected function createLogger(string $processingMode): ZfExtended_Logger;

    /**
     * Creates the Processor
     * @return AbstractProcessor
     */
    abstract protected function createProcessor(): AbstractProcessor;

    /**
     * Will be called when the looper has an Exception and can be used to intercept the Exception
     * The returned integer steers the behaviour of the looper:
     * - a positive integer causes the looping to continue and processing the remaining segments there might be
     * - "0" halts the looping/processing and causes the worker to finish without exception
     * - a negative integer leads to the passed exception being thrown ending the processing
     * @param Exception $loopedProcessingException
     * @param State[] $problematicStates
     * @param bool $isReprocessing
     * @return int
     */
    protected function onLooperException(Exception $loopedProcessingException, array $problematicStates, bool $isReprocessing): int
    {
        return -1;
    }

    /**
     * @param array $parameters
     * @return bool
     */
    protected function validateParameters($parameters = [])
    {
        // required param defines the mode as defined in editor_Segment_Processing
        if (array_key_exists('processingMode', $parameters)) {
            $this->processingMode = $parameters['processingMode'];
        } else {
            return false;
        }
        return parent::validateParameters($parameters);
    }

    /**
     * @param null $taskGuid
     * @param array $parameters
     * @return bool
     */
    public function init($taskGuid = null, $parameters = [])
    {
        if (parent::init($taskGuid, $parameters)) {
            // this ensures, that worker 0 ... 2 ... are fetching processing-states from the top while 1 ... 3 ... fetch from the back. In theory, this should make deadlocks less likely
            $this->fromTheTop = $this->workerIndex % 2 === 0;
            return true;
        }
        return false;
    }

    protected function work()
    {
        $this->processor = $this->createProcessor();
        if ($this->doDebug) {
            error_log('PooledService/Processing Worker: ' . get_class($this) . '|' . $this->workerModel->getSlot() . ': work for ' . $this->processingMode . ' using slot ' . $this->workerModel->getSlot() . ' with processor ' . get_class($this->processor));
        }
        // special: some processors may decide not to process - usually because conditions not yet have been clear in queueing-phase
        // simply all workers with higher index will terminate then
        if($this->processor->prepareWorkload($this->workerIndex)){
            // loop through the segments to process
            $this->logger = $this->createLogger($this->processingMode);
            $this->looper = new Looper($this->task, $this->processor);
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
     * @param int $errorState
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
        while (!$isFinished) {
            try {
                $isFinished = $this->looper->run($this->processingMode, $this->fromTheTop, $this->doDebug);
            } catch (Exception $processingException) {
                if ($this->doDebug) {
                    error_log('PooledService/Processing Worker: ' . get_class($this) . '|' . $this->workerModel->getSlot() . ': Loop exception: ' . $processingException->getMessage());
                }
                $flag = $this->onLooperException($processingException, $this->looper->getProcessedStates(), $this->looper->isReprocessingLoop());
                if ($flag > 0) {
                    $isFinished = $this->looper->run($this->processingMode, $this->fromTheTop, $this->doDebug);
                } else if ($flag === 0) {
                    $isFinished = true;
                } else {
                    throw $processingException;
                }
            }
        }
    }

    /**
     * @return float
     */
    protected function calculateProgressDone(): float
    {
        // when no looper exists there seems no workload processing needed and we can return 100% ...
        if(isset($this->looper)){
            return $this->looper->getProgress();
        }
        return 1;
    }
}
