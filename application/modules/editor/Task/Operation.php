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
 * A simple Overview class that holds the identifiers for all operations and creates the start/finishing worker
 * to wrap a task-operation
 */
class editor_Task_Operation
{
    // Every operation (also from Plugins) should define their type here to have an overview about the possible operations
    // these keys presumably also be used to find code related to the operation, so please keep them pretty unique !

    /**
     * @var string
     */
    public const IMPORT = 'import';

    /**
     * @var string
     */
    public const MATCHANALYSIS = 'matchanalysis';

    /**
     * @var string
     */
    public const AUTOQA = 'autoqa';

    /**
     * @var string
     */
    public const TAGTERMS = 'tagterms';

    /**
     * @var string
     */
    public const PIVOT_PRE_TRANSLATION = 'pivotpretranslation';

    /**
     * @var string
     */
    public const VISUAL_EXCHANGE = 'visualexchange';

    /**
     * For contexts where the operation-type might be unknown
     * @var string
     */
    public const UNKNOWN = 'unknown';

    /**
     * Creates a Task-Operation which is the wrapper for any operation performed for tasks albeit the Import
     */
    public static function create(
        string $operationType,
        editor_Models_Task $task,
        string $startingWorkerClass = editor_Task_Operation_StartingWorker::class,
        string $finishingWorkerClass = editor_Task_Operation_FinishingWorker::class,
        array $workerParams = []
    ): editor_Task_Operation {
        $taskState = $task->getState();
        // Only one operation is allowed to run at a time !
        if (in_array($taskState, self::getAllOperations())) {
            throw new editor_Task_Operation_Exception('E1396', [
                'taskstate' => $taskState,
            ]);
        }
        // we do not want excelExports to be manipulated
        if ($taskState === editor_Models_Task::STATE_EXCELEXPORTED) {
            throw new editor_Task_Operation_Exception('E1395', [
                'taskstate' => $taskState,
                'operation' => $operationType,
            ]);
        }

        return new self($operationType, $task, $startingWorkerClass, $finishingWorkerClass, $workerParams);
    }

    /**
     * retrieves all Operations
     * @return string[]
     */
    public static function getAllOperations(): array
    {
        return [self::AUTOQA, self::MATCHANALYSIS, self::PIVOT_PRE_TRANSLATION, self::TAGTERMS, self::VISUAL_EXCHANGE];
    }

    private string $taskGuid;

    private editor_Task_Operation_StartingWorker $startingWorker;

    private int $startingWorkerId;

    /**
     * Queues an operation (the starting & finishing workr). The operation must be started via ::start()
     * @param editor_Models_Task $task The task the operation is bound to.
     *                                 The task's state will be set to the $operationType
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function __construct(
        string $operationType,
        editor_Models_Task $task,
        string $startingWorkerClass,
        string $finishingWorkerClass,
        array $workerParams
    ) {
        $this->taskGuid = $task->getTaskGuid();
        $this->startingWorker = ZfExtended_Factory::get($startingWorkerClass);
        $workerParams['operationType'] = $operationType; // important for frontend-callbacks / message-bus !
        if ($this->startingWorker->init($this->taskGuid, $workerParams)) {
            $this->startingWorkerId = $this->startingWorker->queue(0, ZfExtended_Models_Worker::STATE_PREPARE, false);
            // add finishing worker
            $worker = ZfExtended_Factory::get($finishingWorkerClass);
            $workerParams['taskInitialState'] = $task->getState();
            if ($worker->init($this->taskGuid, $workerParams)) {
                $worker->queue($this->startingWorkerId, ZfExtended_Models_Worker::STATE_PREPARE, false);

                return;
            }
        }

        throw new ZfExtended_Exception(
            'Operation "' . $operationType . '" could not be started, the operation workers could not be initialized.'
        );
    }

    /**
     * Retrieves the parent-id to use for all inner workers
     */
    public function getWorkerId(): int
    {
        return $this->startingWorkerId;
    }

    /**
     * Starts / schedules the prepared workers after inner workers are queued
     */
    public function start(): void
    {
        $this->startingWorker->schedulePrepared();
    }

    /**
     * This API must be called when the creation of a task-operation failed to clean the created workers up
     */
    public function onQueueingError(): void
    {
        $this->startingWorker->getModel()->cleanForTask($this->taskGuid);
    }
}
