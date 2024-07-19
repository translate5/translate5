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

use MittagQI\Translate5\Task\Worker\Behaviour;
use MittagQI\ZfExtended\Worker\Exception\MaxDelaysException;

/**
 * Extends the default worker with task specific additions, basically if the task is on state error, then the worker should set to defunc.
 * All other functionality reacting on the worker run is encapsulated in the behaviour classes
 *
 * The task based worker, is able to load a different behaviour, depending on a non mandatory worker parameter workerBehaviour.
 */
abstract class editor_Models_Task_AbstractWorker extends ZfExtended_Worker_Abstract implements editor_Models_Task_WorkerProgressInterface
{
    /**
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * By default we use the import worker behaviour here
     * → all actions to handle worker in task import context are triggered
     * @var string
     */
    protected $behaviourClass = Behaviour::class;

    /**
     * @var Behaviour
     */
    protected $behaviour;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ReflectionException
     */
    public function init($taskGuid = null, $parameters = [])
    {
        $this->task = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->task->loadByTaskGuid($taskGuid);
        $this->initBehaviour($parameters['workerBehaviour'] ?? null);
        if (! $this->task->isErroneous()) {
            return parent::init($taskGuid, $parameters);
        }

        //we set the worker to defunct when task has errors
        $wm = $this->workerModel;
        if (isset($wm)) {
            $wm->setState($wm::STATE_DEFUNCT);
            $wm->save();
            //wake up remaining - if any
            $this->wakeUpAndStartNextWorkers();
        }

        //if no worker model is set, we don't have to call parent / init a worker model,
        // since we don't even need it in the DB when the task already has errors
        return false;
    }

    protected function initBehaviour(string $behaviourClass = null)
    {
        if (! empty($behaviourClass) && $behaviourClass !== $this->behaviourClass) {
            $newBehaviour = ZfExtended_Factory::get($behaviourClass);
            /* @var $newBehaviour ZfExtended_Worker_Behaviour_Default */
            //adopt configuration (some values may be set before this init was called!)
            $newBehaviour->setConfig($this->behaviour->getConfig());
            $this->behaviour = $newBehaviour;
        }
        //using a different behaviour here without setTask may make no sense, but who knows on which ideas the developers will come ;)
        if (method_exists($this->behaviour, 'setTask')) {
            $this->behaviour->setTask($this->task);
        }
    }

    /**
     * Triggers the update progress event for tasks
     * updateProgress event trigger - can be overriden (disabled) per Worker
     */
    protected function onProgressUpdated(float $progress)
    {
        /** @var editor_Models_Task_WorkerProgress $progress */
        $progress = ZfExtended_Factory::get('editor_Models_Task_WorkerProgress');
        $progress->updateProgress($this->task, $progress, $this->workerModel);
    }

    /**
     * extend the exception handler with task logging
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::handleWorkerException()
     */
    protected function handleWorkerException(Throwable $workException)
    {
        parent::handleWorkerException($workException);
        //just add the task if it is a error code exception
        if ($workException instanceof ZfExtended_ErrorCodeException) {
            $workException->addExtraData([
                'task' => $this->task,
            ]);

            return;
        }

        //if it is an ordinary error, we log that additionaly to the task log.
        $logger = ZfExtended_Factory::get('ZfExtended_Logger', [[
            'writer' => [
                'tasklog' => Zend_Registry::get('config')->resources->ZfExtended_Resource_Logger->writer->tasklog,
            ],
        ]]);
        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        $logger->exception($workException, [
            'extra' => [
                'task' => $this->task,
            ],
        ]);
    }

    /**
     * Worker weight/percent of the total import proccess.
     * @return integer
     */
    public function getWeight(): int
    {
        return 1;
    }

    /**
     * Retrieves the task this worker is bound to
     * Can only be called after ::init()
     */
    public function getTask(): editor_Models_Task
    {
        return $this->task;
    }

    /**
     * For a task-worker, we add capabilities to skip a worker in case it is part of a sub-operation
     */
    protected function onTooManyDelays(string $serviceName, string $workerName = null): void
    {
        $exception = new MaxDelaysException('E1613', [
            'worker' => $workerName ?? get_class($this),
            'service' => $serviceName,
            'task' => $this->task,
        ]);
        // if we are configured to skip the worker on failure we do so
        if ($this->canSkipMaxDelayedOperation() && $this->doSkipMaxDelayedOperation()) {
            // ... but we log the mishap
            $this->log->exception($exception, [
                'level' => $this->log::LEVEL_WARN,
            ]);
            // and may clean-up some stuff (e.g. remove other workers of the operation)
            $this->onSkipMaxDelayedOperation($serviceName);

            if ($this->setDone()) {
                $this->stopExecution('Worker skipped');
            } else {
                $this->stopExecution('Skipping worker failed');
            }
        } else {
            throw $exception;
        }
    }

    /**
     * Future Enhancement:
     * If a service is unavailable for a workload, that can be repeated after import and
     * the configuration to do so is active, that part of the import is skipped.
     * To make that possible, 2 things have to apply:
     * - the config "worker.skipFiledOperationsOnImport" must be set for the task (this is checked in the calling code!)
     * - usually a "mechanic" to skip the whole sub-operation has to be added. This should be implemented in ::onSkipMaxDelayedOperation
     */
    protected function canSkipMaxDelayedOperation(): bool
    {
        return false;
    }

    /**
     * Evaluates if skipping of operations is active for the current task
     * @throws editor_Models_ConfigException
     */
    protected function doSkipMaxDelayedOperation(): bool
    {
        $config = $this->task->getConfig();

        return $config->runtimeOptions->worker->skipFailedOperationsOnImport;
    }

    protected function onSkipMaxDelayedOperation(string $serviceName): void
    {
        // can be implemented in inheriting classes
    }
}
