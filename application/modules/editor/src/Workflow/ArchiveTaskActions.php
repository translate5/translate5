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

namespace MittagQI\Translate5\Workflow;

use editor_Models_Export_Worker;
use editor_Models_Task;
use editor_Models_Task_Remover;
use ReflectionException;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_Conflict;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Worker as Worker;

/**
 * Task related functions for "old" tasks to be archived / deleted,
 * used in the scope of workflow actions and notifications
 */
class ArchiveTaskActions
{
    /**
     * The affected tasks (as data array)
     * @var editor_Models_Task[]
     */
    private array $tasks = [];

    /**
     * @throws ArchiveException
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function __construct(
        private readonly ArchiveConfigDTO $config
    ) {
        //configurable limit per call, defaulting to 5, to reduce DB load per each call
        $limit = $config->limit;
        $workflowSteps = $config->workflowSteps;
        $clientIds = $config->clientIds;

        $sysConfig = Zend_Registry::get('config');
        $taskLifetimeDays = $sysConfig->runtimeOptions->taskLifetimeDays;

        $daysOffset = $config->taskLifetimeDays ?? $taskLifetimeDays ?? 100;
        $lifetimeType = $config->lifetimeType;

        if (! $daysOffset) {
            throw new ArchiveException('E1399');
        }

        /** @var editor_Models_Task $taskEntity */
        $taskEntity = ZfExtended_Factory::get(editor_Models_Task::class);

        $daysOffset = (int) $daysOffset; //ensure that it is plain integer
        $select = $taskEntity->db->select();

        if (! empty($workflowSteps)) {
            $select->where('(`state` = ?', $taskEntity::STATE_END)
                ->orWhere('`workflowStepName` IN (?))', $workflowSteps);
        } else {
            $select->where('`state` = ?', $taskEntity::STATE_END);
        }

        if (! empty($clientIds)) {
            $select->where('`customerId` in (?)', $clientIds);
        }

        if ($lifetimeType == $this->config::LIFETIME_CREATED) {
            $select->where('`created` < (CURRENT_DATE - INTERVAL ? DAY)', $daysOffset);
        } else {
            //default is $this->config::::LIFETIME_MODIFIED
            $select->where('`modified` < (CURRENT_DATE - INTERVAL ? DAY)', $daysOffset);
        }
        // since this action should be normally called periodically, we limit that on a specific amount
        $select->limit($limit);

        $tasks = $taskEntity->db->getAdapter()->fetchAll($select) ?? [];

        foreach ($tasks as $id => $task) {
            $taskEntity->load($task['id']);
            $this->tasks[$id] = $taskEntity;
            //creating a new instance for each loaded task
            $taskEntity = ZfExtended_Factory::get(editor_Models_Task::class);
        }
    }

    /**
     * @return editor_Models_Task[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /***
     * Remove all ended task from the database and from the disk when there is no
     * change since (taskLifetimeDays)config days in lek_task_log
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Conflict
     * @throws ReflectionException
     */
    public function removeOldTasks(bool $keepTasks = false): void
    {
        if (empty($this->tasks)) {
            return;
        }

        $removedTasks = [];
        //foreach task, check the deletable, and delete it
        foreach ($this->tasks as $task) {
            if (! $task->isErroneous()) {
                $task->checkStateAllowsActions();
            }

            //no need for entity version check, since field loaded from db will always have one

            /** @var editor_Models_Task_Remover $remover */
            $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', [$task]);
            $removedTasks[] = $task->getTaskName();
            if (! $keepTasks) {
                $remover->remove();
            }
        }
        /* @var  ZfExtended_Logger $logger */
        $logger = Zend_Registry::get('logger');
        $logger->info('E1011', 'removeOldTasks - removed {taskCount} tasks' . ($keepTasks ? ' - DRYRUN' : ''), [
            'taskCount' => count($removedTasks),
            'taskNames' => $removedTasks,
        ]);
    }

    /**
     * Backup the tasks (as configured in the options) then remove it
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public function backupThenRemove(bool $keepTasks = false): void
    {
        /** @var ZfExtended_Logger $log */
        $log = Zend_Registry::get('logger');

        foreach ($this->tasks as $task) {
            //Kunde / Projektnummer / in folder

            //generate temp folder
            $exportFolderRoot = tempnam($task->getAbsoluteTaskDataPath(), 'export');
            $exportFolderExport = $exportFolderRoot . '/export';

            unlink($exportFolderRoot); //delete the unique file
            //and create a same named directory + the export directory for the plain export
            mkdir($exportFolderExport, recursive: true);

            try {
                $task->checkStateAllowsActions();
            } catch (ZfExtended_Models_Entity_Conflict $e) {
                //log specific error to task
                $log->exception($e);

                continue;
            }

            // prepare the archive worker which runs after the needed export workers
            // - creates the XLF2 (we do not use the XLF2 worker due to much unneeded overhead: cleaning etc)
            // - moves the content then to the desired target and deletes the task afterwards
            /** @var ArchiveWorker $worker */
            $worker = ZfExtended_Factory::get(ArchiveWorker::class);
            $worker->init($task->getTaskGuid(), [
                'exportToFolder' => $exportFolderRoot,
                'keepTasks' => $keepTasks,
                'options' => $this->config->filesystemConfig,
            ]);
            $parentWorkerId = $worker->queue(state: Worker::STATE_PREPARE, startNext: false);

            /** @var editor_Models_Export_Worker $worker */
            $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
            $worker->initFolderExport($task, false, $exportFolderExport);
            $worker->queue($parentWorkerId, Worker::STATE_PREPARE, false);

            $worker->schedulePrepared();
        }
    }
}
