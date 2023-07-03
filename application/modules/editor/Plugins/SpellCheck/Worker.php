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

namespace MittagQI\Translate5\Plugins\SpellCheck;

use editor_Plugins_SpellCheck_Init;
use Exception;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\AbstractException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Service;
use MittagQI\Translate5\Plugins\SpellCheck\Segment\Configuration;
use MittagQI\Translate5\Plugins\SpellCheck\Segment\Processor;
use MittagQI\Translate5\Segment\Processing\State;
use MittagQI\Translate5\Segment\Processing\Worker as ProcessingWorker;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Exception;
use ZfExtended_Logger;

/**
 * Processes the spellchecking of segments in operations like import, analysis, etc.
 * This will be done with the configured serviceUrl in a Loop
 *
 * @property Service $service;
 * @property Processor $processor;
 */
class Worker extends ProcessingWorker
{
    /**
     * @return Service
     * @throws ZfExtended_Exception
     */
    protected function createService(): Service
    {
        return editor_Plugins_SpellCheck_Init::createService('languagetool');
    }

    /**
     * @param string $processingMode
     * @return ZfExtended_Logger
     * @throws Zend_Exception
     */
    protected function createLogger(string $processingMode): ZfExtended_Logger
    {
        $loggerDomain = Configuration::getLoggerDomain($processingMode);
        return Zend_Registry::get('logger')->cloneMe($loggerDomain);
    }

    /**
     * Creates the Processor
     * @return Processor
     */
    protected function createProcessor(): Processor
    {
        return new Processor($this->task, $this->service, $this->processingMode, $this->serviceUrl, true);
    }

    /**
     * @throws DownException
     */
    protected function raiseNoAvailableResourceException()
    {
        // E1466 No reachable LanguageTool instances available, please specify LanguageTool urls to import this task.
        throw new DownException('E1466', [
            'task' => $this->task
        ]);
    }

    /**
     * @param Exception $loopedProcessingException
     * @param State[] $problematicStates
     * @param bool $isReprocessing
     * @return int
     */
    protected function onLooperException(Exception $loopedProcessingException, array $problematicStates, bool $isReprocessing): int
    {
        // If Malfunction exception caught, it means the LanguageTool is up, but HTTP response code was not 2xx, so that
        // - we set the segments status to 'recheck', so each segment will be checked again, segment by segment, not in a bulk manner,
        //   but if while running one-by-one recheck it will result the same problem, then each status will be set as 'defect' one-by-one
        // - we log all the data producing the error.
        if($loopedProcessingException instanceof MalfunctionException || $loopedProcessingException instanceof TimeOutException || $loopedProcessingException instanceof RequestException){
            // set the failed segments either to reprocess or unprocessable depending if we are already reprocessing
            if($isReprocessing){
                $this->setUnprocessedStates($problematicStates, State::UNPROCESSABLE);
                // log if it did not work in the second attempt
                $this->logException($loopedProcessingException);
            } else {
                $this->setUnprocessedStates($problematicStates, State::REPROCESS);
            }
            // in any case we continue processing
            return 1;
        }
        // a Down Exception will be created if all services are down to create an import error. If other URLs are still up, we simply end the worker without further notice
        if($loopedProcessingException instanceof DownException) {
            // we log only, if the last service is down ...
            if($this->service->setServiceUrlDown($this->serviceUrl)){
                $this->logException($loopedProcessingException);
            }
            // this will terminate the processing
            return 0;
        }
        // unknown exceptions will terminate the processing
        if($loopedProcessingException instanceof AbstractException) {
            $this->logException($loopedProcessingException);
            return 0;
        }
        // all other exceptions will be thrown
        return -1;
    }

    /**
     * Spell checking takes approximately 15 % of the import time
     * @return int
     */
    public function getWeight(): int {
        return 15;
    }

    /**
     * Logs an task-error out of the exception
     * @param AbstractException $exception
     */
    private function logException(AbstractException $exception)
    {
        $exception->addExtraData([
            'task' => $this->task
        ]);
        $this->logger->exception($exception, [
            'domain' => Configuration::getLoggerDomain($this->processingMode)
        ]);
    }
}
