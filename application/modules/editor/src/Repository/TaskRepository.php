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

use editor_Models_Db_Task as TaskTable;
use editor_Models_Db_TaskUserAssoc;
use editor_Models_Task as Task;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Models_Entity_NotFoundException;

class TaskRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    /**
     * @throws InexistentTaskException
     */
    public function get(int $id): Task
    {
        try {
            $task = new Task();
            $task->load($id);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentTaskException((string) $id);
        }

        return $task;
    }

    /**
     * @throws InexistentTaskException
     */
    public function getByGuid(string $guid): Task
    {
        try {
            $task = new Task();
            $task->loadByTaskGuid($guid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new InexistentTaskException($guid);
        }

        return $task;
    }

    /**
     * @throws InexistentTaskException
     */
    public function getWithLockByGuid(string $guid): Task
    {
        $task = new Task();

        $this->db->beginTransaction();

        $select = $this->db->select()
            ->forUpdate()
            ->from(TaskTable::TABLE_NAME)
            ->where('taskGuid = ?', $guid)
        ;
        $row = $this->db->fetchRow($select);

        if (empty($row)) {
            $this->db->rollBack();

            throw new InexistentTaskException($guid);
        }

        $task->init(
            new \Zend_Db_Table_Row(
                [
                    'table' => $task->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $task;
    }

    public function release()
    {

    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getProjectBy(Task $task): Task
    {
        return $this->get((int) $task->getProjectId());
    }

    /**
     * @return iterable<Task>
     */
    public function getProjectTaskList(int $projectId): iterable
    {
        $s = $this->db->select()
            ->from(TaskTable::TABLE_NAME)
            ->where('projectId = ?', $projectId)
            ->where('id != ?', $projectId)
        ;
        $tasksData = $this->db->fetchAll($s);

        $task = new Task();

        foreach ($tasksData as $taskData) {
            $task->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $task->db,
                        'data' => $taskData,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

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
        $s = $this->db->select()->from(TaskTable::TABLE_NAME)->where('pmGuid = ?', $pmGuid);

        return $this->db->fetchAll($s);
    }

    public function updateTaskUserCount(string $taskGuid): void
    {
        $sql = <<<SQL
update %s task,
    (
        select count(*) cnt from %s where taskGuid = ? and isPmOverride = 0 and type != %d
    ) job
set task.userCount = job.cnt where task.taskGuid = ?
SQL;
        $sql = sprintf(
            $sql,
            TaskTable::TABLE_NAME,
            editor_Models_Db_TaskUserAssoc::TABLE_NAME,
            TypeEnum::Lsp->value,
        );
        $sql = $this->db->quoteInto($sql, $taskGuid, 'string', 2);

        $this->db->query($sql);
    }
}
