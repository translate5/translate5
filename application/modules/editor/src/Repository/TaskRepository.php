<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Repository;

use editor_Models_Task;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class TaskRepository
{
    /**
     * @throws InexistentTaskException
     */
    public function get(int $id): editor_Models_Task
    {
        try {
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->load($id);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentTaskException((string) $id);
        }

        return $task;
    }

    /**
     * @throws InexistentTaskException
     */
    public function getByGuid(string $guid): editor_Models_Task
    {
        try {
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->loadByTaskGuid($guid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentTaskException($guid);
        }

        return $task;
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getProjectBy(editor_Models_Task $task): editor_Models_Task
    {
        return $this->get((int) $task->getProjectId());
    }

    /**
     * @return iterable<editor_Models_Task>
     */
    public function getProjectTaskList(int $projectId): iterable
    {
        $db = ZfExtended_Factory::get(editor_Models_Task::class)->db;
        $s = $db->select()->where('projectId = ?', $projectId)->where('id != ?', $projectId);
        $tasksData = $db->fetchAll($s);

        $task = ZfExtended_Factory::get(editor_Models_Task::class);

        foreach ($tasksData as $taskData) {
            $task->init($taskData);

            yield clone $task;
        }
    }

    /**
     * Return all tasks associated to a specific user as PM
     *
     * @return array[]
     */
    public function loadListByPmGuid(string $pmGuid): array
    {
        $db = ZfExtended_Factory::get(editor_Models_Task::class)->db;
        $s = $db->select()->where('pmGuid = ?', $pmGuid);

        return $db->fetchAll($s)->toArray();
    }
}
