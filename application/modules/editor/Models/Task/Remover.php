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

use MittagQI\Translate5\LanguageResource\Db\TaskAssociation;
use MittagQI\Translate5\LanguageResource\Db\TaskPivotAssociation;
use MittagQI\Translate5\LanguageResource\Operation\DeleteLanguageResourceOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Operation\DeleteTaskTmOperation;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Task\JobsPurger;
use MittagQI\ZfExtended\Localization;

/**
 * Task Remover - on task deletion several things should happen, this is all encapsulated in this class
 */
final class editor_Models_Task_Remover
{
    private editor_Models_Task $task;

    private ZfExtended_EventManager $eventManager;

    private SegmentHistoryAggregation $aggregator;

    private DeleteTaskTmOperation $deleteTaskTmOperation;

    private readonly JobsPurger $jobsPurger;

    /**
     * Sets the task to be removed from system
     */
    public function __construct(editor_Models_Task $task)
    {
        $this->task = $task;
        $this->eventManager = ZfExtended_Factory::get(ZfExtended_EventManager::class, [self::class]);
        $this->aggregator = SegmentHistoryAggregation::create();
        $this->deleteTaskTmOperation = DeleteTaskTmOperation::create();
        $this->jobsPurger = JobsPurger::create();
    }

    /**
     * Removes a task completely from translate5 if task is not locked and therefore removable
     */
    public function remove($forced = false)
    {
        $taskGuid = $this->task->getTaskGuid();
        $projectId = (int) $this->task->getProjectId();
        $isProject = $this->task->isProject();
        if (empty($taskGuid)) {
            return false;
        }
        if ($isProject && $projectId > 0) {
            $this->removeProjectWithTasks($projectId, $forced, true);
        } else {
            $this->removeTask($forced);
        }

        // on import error project may not be created
        // TODO FIXME: why is that called ?
        // It seems this case can not happen as everything is removed already with the code above ...
        if ($projectId > 0) {
            $this->cleanupProject($projectId);
        }
    }

    /***
     * Remove the current loaded task. The task data on the disk will be removed by default ($removeFiles).
     * To disable this set $removeFiles to false.
     * @param false $forced
     * @param true $removeFiles: should the task files be removed
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Conflict
     */
    private function removeTask(bool $forced = false, bool $removeFiles = true)
    {
        if (! $forced) {
            $this->checkRemovable();
        }

        $triggerData = [
            'taskId' => $this->task->getId(),
            'taskGuid' => $this->task->getTaskGuid(),
            'taskName' => $this->task->getTaskName(),
            'isProject' => $this->task->isProject(),
        ];

        $this->removeRelatedDbData();
        $this->jobsPurger->purgeTaskJobs($this->task->getTaskGuid());
        if ($removeFiles) {
            $this->removeDataDirectory();
        }
        $this->task->delete();

        // give plugins a chance to clean up task data
        $this->eventManager->trigger('afterTaskRemoval', $this, $triggerData);
    }

    /***
     * Remove project and all of his tasks and related data
     *
     * @param int $projectId
     * @param bool $forced
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_Conflict
     */
    private function removeProjectWithTasks(int $projectId, bool $forced, bool $removeFiles)
    {
        $model = ZfExtended_Factory::get(editor_Models_Task::class);
        $tasks = $model->loadProjectTasks($projectId);
        $tasks = array_reverse($tasks);
        foreach ($tasks as $projectTask) {
            $this->task->init($projectTask);
            $this->removeTask($forced, $removeFiles);
        }
    }

    /**
     * Remove the project if there are no tasks in the project
     */
    private function cleanupProject(int $projectId)
    {
        $model = ZfExtended_Factory::get(editor_Models_Task::class);
        $tasks = $model->loadProjectTasks($projectId);
        if (count($tasks) > 1 || empty($tasks)) {
            return;
        }
        $this->task->load($projectId);
        $this->remove(true);
    }

    /**
     * removes the tasks data directory from filesystem
     */
    private function removeDataDirectory()
    {
        //also delete files on default delete
        $taskPath = (string) $this->task->getAbsoluteTaskDataPath();
        if (is_dir($taskPath)) {
            ZfExtended_Utils::recursiveDelete($taskPath);
        }
    }

    /**
     * internal function with stuff to be excecuted before deleting a task
     */
    private function checkRemovable()
    {
        $taskGuid = $this->task->getTaskGuid();

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1042' => 'The task can not be removed due it is used by a user.',
            'E1043' => 'The task can not be removed due it is locked by a user.',
            'E1044' => 'The task can not be locked for deletion.',
        ]);

        if ($this->task->isUsed($taskGuid)) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1042', [
                Localization::trans('Die Aufgabe wird von einem Benutzer benutzt, und kann daher nicht gelöscht werden.'),
            ], [
                'task' => $this->task,
            ]);
        }

        if ($this->task->isLocked($taskGuid) && ! $this->task->isErroneous()) {
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1043', [
                Localization::trans('Die Aufgabe ist durch einen Benutzer gesperrt, und kann daher nicht gelöscht werden.'),
            ], [
                'task' => $this->task,
            ]);
        }

        if (! $this->task->lock(NOW_ISO)) {
            //this should not happen, therefore it is not translated
            throw new ZfExtended_Models_Entity_Conflict('E1044', [
                'task' => $this->task,
            ]);
        }

        return true;
    }

    /**
     * drops the tasks Materialized View and deletes several data (segments, terms, file entries)
     * All mentioned data has foreign keys to the task, to reduce locks while deletion this
     * data is deleted directly instead of relying on referential integrity.
     * Also removes the task related term collection and aggregated history data
     */
    private function removeRelatedDbData()
    {
        $this->task->dropMaterializedView();
        $taskGuid = $this->task->getTaskGuid();

        $segmentTable = ZfExtended_Factory::get('editor_Models_Db_Segments');
        $segmentTable->delete([
            'taskGuid = ?' => $taskGuid,
        ]);

        $filesTable = ZfExtended_Factory::get('editor_Models_Db_Files');
        $filesTable->delete([
            'taskGuid = ?' => $taskGuid,
        ]);

        // delete autocreatedOnImport LanguageResources
        // so all languageresource entries in LEK_languageresources_taskassoc and LEK_languageresources_taskpivotassoc
        // where field "autoCreatedOnImport" is set to 1 will be removed
        $this->removeAutocreatedOnImportLanguageResources();
        $this->deleteTaskTmOperation->removeTaskTmsForTask($taskGuid);
        $this->aggregator->removeTaskData($taskGuid);
    }

    /**
     * remove all "autoCreatedOnImport" language resource which are connected to this task.
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function removeAutocreatedOnImportLanguageResources(): void
    {
        // first detect all IDs of the languageresources that need to be deleted

        $db = ZfExtended_Factory::get(TaskAssociation::class);
        $taskAssocTable = ZfExtended_Factory::get(TaskAssociation::class);
        $select = $taskAssocTable->select()->where(
            'autoCreatedOnImport = 1 AND taskGuid = ?',
            $this->task->getTaskGuid()
        );
        $rowsTaskAssoc = $db->fetchAll($select)->toArray();

        $db = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $taskPivotAssocTable = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $select = $taskPivotAssocTable->select()->where(
            'autoCreatedOnImport = 1 AND taskGuid = ?',
            $this->task->getTaskGuid()
        );
        $rowsTaskPivotAssoc = $db->fetchAll($select)->toArray();

        $allEntries = array_merge($rowsTaskAssoc, $rowsTaskPivotAssoc);

        // do nothing if no autocreatedOnImport resources where found.
        if (empty($allEntries)) {
            return;
        }

        $deleteOperation = DeleteLanguageResourceOperation::create();
        foreach ($allEntries as $entry) {
            $languageResourceId = (int) $entry['languageResourceId'];
            $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
            $languageResource->load($languageResourceId);
            $deleteOperation->delete($languageResource, forced: true, deleteInResource: true);
        }
    }
}
