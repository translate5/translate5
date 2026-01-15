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

namespace MittagQI\Translate5\Task\Import;

use editor_Models_Import_Configuration;
use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Import_Worker;
use editor_Models_Import_Worker_FileTree;
use editor_Models_Import_Worker_FinalStep;
use editor_Models_Import_Worker_ReferenceFileTree;
use editor_Models_Import_Worker_SetTaskToOpen;
use editor_Models_Task;
use editor_Models_TaskConfig;
use editor_Task_Operation;
use editor_Workflow_Manager;
use MittagQI\ZfExtended\Worker\Trigger\Factory as WorkerTriggerFactory;
use ReflectionException;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Worker;

class ProjectWorkersService
{
    private ImportEventTrigger $eventTrigger;

    public function __construct()
    {
        $this->eventTrigger = new ImportEventTrigger();
    }

    /**
     * add and run all the needed import workers
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function queueImportWorkers(
        editor_Models_Task $task,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        editor_Models_Import_Configuration $importConfig,
        bool $importWizardUsed,
    ): void {
        $taskGuid = $task->getTaskGuid();
        $params = [
            'config' => $importConfig,
        ];

        // Queue Import Worker first as it provides the parent ID

        $importWorker = ZfExtended_Factory::get(editor_Models_Import_Worker::class);
        $importWorker->init($taskGuid, array_merge($params, [
            'dataProvider' => $dataProvider,
            'operationType' => editor_Task_Operation::IMPORT, // important for frontend-callbacks / message-bus !
        ]));
        //prevent the importWorker to be started here.
        $parentId = $importWorker->queue(0, ZfExtended_Models_Worker::STATE_PREPARE, false);

        // Queue FileTree and Reference FileTree Worker.
        // NOTE: these will actually run BEFORE the Import worker,
        // but we queue them afterwords to have the import worker as parent

        $fileTreeWorker = ZfExtended_Factory::get(editor_Models_Import_Worker_FileTree::class);
        $fileTreeWorker->init($taskGuid, $params);
        $fileTreeWorker->queue($parentId, ZfExtended_Models_Worker::STATE_PREPARE, false);

        $refTreeWorker = ZfExtended_Factory::get(editor_Models_Import_Worker_ReferenceFileTree::class);
        $refTreeWorker->init($taskGuid, $params);
        $refTreeWorker->queue($parentId, ZfExtended_Models_Worker::STATE_PREPARE, false);

        $worker = ZfExtended_Factory::get(editor_Models_Import_Worker_SetTaskToOpen::class);
        // queuing this worker when task has errors make no sense, init checks this.
        if ($worker->init($taskGuid, [
            'config' => $importConfig,
        ])) {
            $worker->queue($parentId, ZfExtended_Models_Worker::STATE_PREPARE, false);
        }
        // the worker finishing the import
        $worker = ZfExtended_Factory::get(editor_Models_Import_Worker_FinalStep::class);
        if ($worker->init($taskGuid, [
            'config' => $importConfig,
        ])) {
            $worker->queue($parentId, ZfExtended_Models_Worker::STATE_PREPARE);
        }

        // sometimes it is not possbile for additional import workers to be invoked in afterImport,
        // for that reason this event exists:
        $this->eventTrigger->triggerImportWorkerQueued($task, $parentId, $importWizardUsed);
    }

    /**
     * starts the workers of the current or given task
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     */
    public function startImportWorkers(editor_Models_Task $task): void
    {
        $tasks = [$task];
        //if it is a project, start the import workers for each sub-task
        if ($task->isProject()) {
            $tasks = $task->loadProjectTasks((int) $task->getProjectId(), true);

            ZfExtended_Factory::get(editor_Workflow_Manager::class)
                ->getActiveByTask($task)
                ->hookin()
                ->doHandleProjectCreated($task);
        }

        // we fix all task-specific configs of the task for it's remaining lifetime
        // this is crucial to ensure, that important configs are changed throughout the lifetime
        // that are usually not designed to be dynamical (AutoQA, Visual, ...)
        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);
        $taskConfig->fixAfterImport($tasks);

        $model = ZfExtended_Factory::get(editor_Models_Task::class);
        foreach ($tasks as $t) {
            if (is_array($t)) {
                $model->load($t['id']);
            } else {
                $model = $t;
            }

            //import workers can only be started for tasks
            if ($model->isProject()) {
                continue;
            }

            $workerModel = new ZfExtended_Models_Worker();

            try {
                $workerModel->loadFirstOf(
                    editor_Models_Import_Worker::class,
                    $model->getTaskGuid(),
                    [ZfExtended_Models_Worker::STATE_PREPARE]
                );

                //set the prepared worker to scheduled and set them to waiting where possible
                $workerModel->schedulePrepared();
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                //if there is no worker, nothing can be done
            }
        }

        //finally trigger the worker queue
        WorkerTriggerFactory::create()->triggerQueue();
    }
}
