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
use editor_Workflow_Actions_Config;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_Conflict;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * Task related functions for "old" tasks to be archived / deleted, used in the scope of workflow actions and notifications
 */
class ArchiveTaskActions {

    private editor_Workflow_Actions_Config $triggerConfig;

    /**
     * The affected tasks (as data array)
     * @var editor_Models_Task[]
     */
    private array $tasks;

    /**
     * @throws Zend_Exception
     */
    public function __construct(editor_Workflow_Actions_Config $triggerConfig)
    {
        $this->triggerConfig = $triggerConfig;
        //configurable limit per call, defaulting to 5, to reduce DB load per each call
        $limit = $triggerConfig->parameters->limit ?? 5;

        $config = Zend_Registry::get('config');
        $taskLifetimeDays= $config->runtimeOptions->taskLifetimeDays;

        $daysOffset = $taskLifetimeDays ?? 100;

        if(!$daysOffset){
            throw new ArchiveException('E1399');
        }

        /** @var editor_Models_Task $taskEntity */
        $taskEntity = ZfExtended_Factory::get('editor_Models_Task');

        $daysOffset = (int)$daysOffset; //ensure that it is plain integer
        $s = $taskEntity->db->select()
            ->where('`state` = ?', $taskEntity::STATE_END)
            ->where('`modified` < (CURRENT_DATE - INTERVAL ? DAY)', $daysOffset)
            ->limit($limit); // since this action should be normally called periodically, we limit that on a specific amount
        $this->tasks = $taskEntity->db->getAdapter()->fetchAll($s) ?? [];
        foreach($this->tasks as $id => $task) {
            $taskEntity->load($task['id']);
            $this->tasks[$id] = $taskEntity;
            //creating a new instance for each loaded task
            $taskEntity = ZfExtended_Factory::get('editor_Models_Task');
        }
    }

    /***
     * Remove all ended task from the database and from the disk when there is no
     * change since (taskLifetimeDays)config days in lek_task_log
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Conflict
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function removeOldTasks() {

        if(empty($this->tasks)) {
            return;
        }

        $removedTasks = [];
        //foreach task task, check the deletable, and delete it
        foreach ($this->tasks as $task){
            if(!$task->isErroneous()){
                $task->checkStateAllowsActions();
            }

            //no need for entity version check, since field loaded from db will always have one

            /** @var editor_Models_Task_Remover $remover */
            $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array($task));
            $removedTasks[] = $task->getTaskName();
            $remover->remove();
        }
        /* @var  ZfExtended_Logger $logger */
        $logger = Zend_Registry::get('logger');
        $logger->info('E1011', 'removeOldTasks - removed {taskCount} tasks', [
            'taskCount' => count($removedTasks),
            'taskNames' => $removedTasks
        ]);
    }

    /**
     * Backup the tasks (as configured in the options) then remove it
     * @throws Zend_Exception
     */
    public function backupThenRemove()
    {
        /** @var ZfExtended_Logger $log */
        $log = Zend_Registry::get('logger');

        /** @var editor_Models_Task $task */
        foreach ($this->tasks as $task) {
            //Kunde / Projektnummer / in folder

            //generate temp folder
            $exportFolderRoot = tempnam($task->getAbsoluteTaskDataPath(), 'export');
            $exportFolderExport = $exportFolderRoot.'/export';

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
            $worker = ZfExtended_Factory::get('\MittagQI\Translate5\Workflow\ArchiveWorker');
            $worker->init($task->getTaskGuid(), [
                'exportToFolder' => $exportFolderRoot,
                'options' => $this->triggerConfig->parameters,
            ]);
            $parentWorkerId = $worker->queue(startNext: false);

            /** @var editor_Models_Export_Worker $worker */
            $worker = ZfExtended_Factory::get('editor_Models_Export_Worker');
            $worker->initFolderExport($task, false, $exportFolderExport);
            $worker->queue($parentWorkerId);
       }
    }
}
