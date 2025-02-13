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

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Task;
use editor_Task_Operation;
use editor_Task_Operation_Exception;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use MittagQI\Translate5\PauseWorker\AbstractPauseWorker;
use ReflectionException;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_EventManager;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_Models_Worker;
use ZfExtended_Worker_Behaviour_Default;

/**
 * This will queue pivot pre-translation and batch(if needed) worker
 */
class PivotQueuer
{
    protected ZfExtended_EventManager $events;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->events = ZfExtended_Factory::get(
            ZfExtended_EventManager::class,
            ['MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer']
        );
    }

    /***
     * Queue the match analysis worker
     *
     * @param string $taskGuid
     *
     * @throws ReflectionException
     * @throws ZfExtended_Exception
     * @throws Throwable
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Task_Operation_Exception
     */
    public function queuePivotWorker(string $taskGuid): void
    {
        $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $assoc = $pivotAssoc->loadTaskAssociated($taskGuid);

        if (empty($assoc)) {
            return;
        }

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        if ($task->isImporting()) {
            //on import, we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
            // we do not set a worker-state, this is handled by the import-process
            $this->doQueuePivotWorker($task, $assoc, $parentWorkerId, null);
        } else {
            // this creates the operation start/finish workers
            $operation = editor_Task_Operation::create(editor_Task_Operation::PIVOT_PRE_TRANSLATION, $task);

            try {
                $parentWorkerId = $operation->getWorkerId();
                $workerState = ZfExtended_Models_Worker::STATE_PREPARE;
                // add a different behaviour for the workers when performing an operation
                $workerParameters = [
                    'workerBehaviour' => ZfExtended_Worker_Behaviour_Default::class,
                ];
                // queue the worker
                $this->doQueuePivotWorker($task, $assoc, $parentWorkerId, $workerState, $workerParameters);
                // start operation
                $operation->start();
            } catch (Throwable $e) {
                $operation->onQueueingError();

                throw $e;
            }
        }
    }

    /**
     * Queues the Pivot Worker
     * @throws ReflectionException
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     */
    private function doQueuePivotWorker(
        editor_Models_Task $task,
        array $assoc,
        int $parentWorkerId,
        ?string $workerState,
        array $workerParameters = []
    ): void {
        $user = ZfExtended_Authentication::getInstance()->getUser();

        $workerParameters['userGuid'] = $user?->getUserGuid() ?? ZfExtended_Models_User::SYSTEM_GUID;
        $workerParameters['userName'] = $user?->getUserName() ?? ZfExtended_Models_User::SYSTEM_LOGIN;

        // enable batch query via config
        $workerParameters['batchQuery'] = $this->batchQueryEnabled();
        if ($workerParameters['batchQuery']) {
            // trigger event before the pivot pre-translation worker is queued
            $this->events->trigger(
                'beforePivotPreTranslationQueue',
                'MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer',
                [
                    'task' => $task,
                    'pivotAssociations' => $assoc,
                    'parentWorkerId' => $parentWorkerId,
                ]
            );
        }

        $pivotWorker = ZfExtended_Factory::get(PivotWorker::class);

        if (! $pivotWorker->init($task->getTaskGuid(), $workerParameters)) {
            throw new ZfExtended_Exception(
                'Pivot pre-translation Error on worker init(). Worker could not be initialized'
            );
        }

        $worker = ZfExtended_Factory::get(PausePivotWorker::class);
        $worker->init($task->getTaskGuid(), [
            AbstractPauseWorker::PROCESSOR => PausePivotProcessor::class,
        ]);
        $worker->queue($parentWorkerId, $workerState);

        $pivotWorker->queue($parentWorkerId, $workerState, false);
    }

    /**
     * @throws ReflectionException
     */
    private function fetchImportWorkerId(string $taskGuid): ?int
    {
        $parent = new ZfExtended_Models_Worker();
        $result = $parent->loadByState(
            ZfExtended_Models_Worker::STATE_PREPARE,
            'editor_Models_Import_Worker',
            $taskGuid
        );

        if (count($result) > 0) {
            return (int) $result[0]['id'];
        }

        return 0;
    }

    /***
     * Log analysis warning
     *
     * @param editor_Models_Task $task
     * @param string $message
     * @param array $extra
     *
     * @throws Zend_Exception
     */
    protected function addWarn(editor_Models_Task $task, string $message, array $extra = []): void
    {
        $extra['task'] = $task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100', $message, $extra);
    }

    /**
     * @throws Zend_Exception
     */
    private function batchQueryEnabled(): bool
    {
        return (bool) Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
    }
}
