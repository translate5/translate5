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

namespace MittagQI\Translate5\Plugins\TermTagger;

use editor_Plugins_TermTagger_Bootstrap;
use editor_Plugins_TermTagger_Exception_Abstract;
use editor_Plugins_TermTagger_Exception_Down;
use editor_Plugins_TermTagger_Exception_Malfunction;
use editor_Plugins_TermTagger_Exception_Open;
use editor_Plugins_TermTagger_Exception_Request;
use editor_Plugins_TermTagger_Exception_TimeOut;
use Exception;
use MittagQI\Translate5\Plugins\TermTagger\Processor\Remover;
use MittagQI\Translate5\Plugins\TermTagger\Processor\Tagger;
use MittagQI\Translate5\Segment\Processing\AbstractProcessingWorker;
use MittagQI\Translate5\Segment\Processing\State;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Exception;
use ZfExtended_Logger;

/**
 * Tags terms (or removes the tags in case no terminology is set) for operations like import, analysis, etc.
 * This will be done with the configured serviceUrl in a Loop
 *
 * @property Service $service;
 * @property Tagger|Remover $processor;
 */
class Worker extends AbstractProcessingWorker
{
    /**
     * @throws ZfExtended_Exception
     */
    protected function createService(): Service
    {
        return editor_Plugins_TermTagger_Bootstrap::createService('termtagger');
    }

    /**
     * Creates the Processor
     */
    protected function createProcessor(): Tagger|Remover
    {
        if ($this->task->getTerminologie()) {
            // normally we add terms by tagging
            return new Tagger($this->task, $this->service, $this->processingMode, $this->serviceUrl, true);
        } else {
            // in case there is no terminology (never set or removed) we remove all term-tags
            return new Remover($this->task, $this->service, $this->processingMode, $this->serviceUrl, true);
        }
    }

    /**
     * @throws Zend_Exception
     */
    protected function createLogger(string $processingMode): ZfExtended_Logger
    {
        $loggerDomain = Configuration::getLoggerDomain($processingMode);

        return Zend_Registry::get('logger')->cloneMe($loggerDomain);
    }

    /**
     * @throws editor_Plugins_TermTagger_Exception_Down
     */
    protected function raiseNoAvailableResourceException()
    {
        // E1131 No TermTaggers available, please enable term taggers to import this task.
        throw new editor_Plugins_TermTagger_Exception_Down('E1131', [
            'task' => $this->task,
        ]);
    }

    /**
     * @param State[] $problematicStates
     */
    protected function onLooperException(Exception $loopedProcessingException, array $problematicStates, bool $isReprocessing): int
    {
        // Malfunction means the termtagger is up, but the send data produces an error in the tagger.
        // 1. we set the segment satus to reprocess, so each segment is tagged again, segment by segment, not in a bulk manner if they are not yer reprocessed
        // 2. we set the segment to unprocessable when it already was being reprocessed
        // the logging will be done in the finalizeOperation of the quality-provider
        if ($loopedProcessingException instanceof editor_Plugins_TermTagger_Exception_Malfunction || $loopedProcessingException instanceof editor_Plugins_TermTagger_Exception_TimeOut || $loopedProcessingException instanceof editor_Plugins_TermTagger_Exception_Request) {
            // set the failed segments either to reprocess or unprocessable depending if we are already reprocessing
            if ($isReprocessing) {
                $this->setUnprocessedStates($problematicStates, State::UNPROCESSABLE);
                // log if it did not work in the second attempt
                $this->logTaskException($loopedProcessingException);
            } else {
                $this->setUnprocessedStates($problematicStates, State::REPROCESS);
            }

            // in any case we continue processing
            return 1;
        }
        // a Down Exception will be created if all services are down to create an import error.
        // If other URLs are still up, we simply end the worker without further notice
        if ($loopedProcessingException instanceof editor_Plugins_TermTagger_Exception_Down) {
            // when a TermTagger is down, the behaviour depends on if we are a load-balanced service or not
            $this->onServiceDown($loopedProcessingException);

            // this will terminate the processing
            return 0;
        }
        // editor_Plugins_TermTagger_Exception_Open Exceptions mean mostly that there is problem with the TBX data
        // so we do not create a new worker entry, that imports the task without terminology markup then
        if ($loopedProcessingException instanceof editor_Plugins_TermTagger_Exception_Open) {
            $this->task->setTerminologie(false);
            $this->task->save();
            $this->logTaskException($loopedProcessingException);

            // this will terminate the processing
            return 0;
        }
        // all other Termtagger exceptions will be logged according their severity
        if ($loopedProcessingException instanceof editor_Plugins_TermTagger_Exception_Abstract) {
            $this->logTaskException($loopedProcessingException);

            // unknown exceptions will terminate the processing
            return 0;
        }

        // all other exceptions will be thrown
        return -1;
    }

    /**
     * Term tagging takes approximately 15 % of the import time
     */
    public function getWeight(): int
    {
        return 15;
    }

    /**
     * Logs an task-error out of the exception
     */
    protected function logTaskException(Exception $exception): void
    {
        $this->logger->exception($exception, [
            'domain' => Configuration::getLoggerDomain($this->processingMode),
            'extra' => [
                'task' => $this->task,
            ],
        ]);
    }
}
