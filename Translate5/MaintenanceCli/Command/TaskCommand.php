<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Task;
use MittagQI\Translate5\Task\FileTranslation\FileTranslationType;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend_Registry;

/*
 * task ls → lists available tasks, id taskguid status taskNr taskName
 * → filtering?
 * task rm [ID]|[GUID]
 * task info [ID]|[GUID] prints information about a task
 * task import [ID]|[GUID] → starts import for that task (needed probably with decoupled task import start)
 * task unlock [ID]|[status]|[GUID] unlock a task identified by its ID or status or GUID
 */

class TaskCommand extends Translate5AbstractCommand
{
    public const WITH_DATE_PATTERN = '/^(.+)\s+(\([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\))$/';

    public const IDENTIFIER_DESCRIPTION = 'A task-id, task-guid (with/without braces), task-name or a task-name followed by the creation-date (<name> Y-m-d H:i:s)';

    /**
     * All task-types that may have task-tada (projects will not have any user-generated data)
     * Note: can NOT be implemented as array because the classes are not found then (???)
     */
    public static function taskTypesWithData(): array
    {
        return [
            \editor_Task_Type_Default::ID,
            \editor_Task_Type_ProjectTask::ID,
            \editor_Task_Type_TermTranslationTask::ID,
            FileTranslationType::ID,
        ];
    }

    /**
     * Finds a task by identifier
     * The identifier can be:
     *  - a task-id
     *  - a task-guid
     *  - a task-name
     *  - a task-name followed by the creation-date "<task-name> Y-m-d H:i:s"
     * if multiple tasks are found there will be a chice to select the desired task
     * You can further filter the task to find by task-states and task-types
     * In silent-mode, the identifier must bring ONE task and no IO will be generated
     *
     * @throws \Zend_Exception
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public static function findTaskFromArgument(
        SymfonyStyle $io,
        string $taskIdentifier,
        bool $silent = false,
        array $taskTypes = [],
        array $taskStates = []
    ): ?editor_Models_Task {
        $task = new editor_Models_Task();
        $config = Zend_Registry::get('config');
        $matches = [];
        $select = $task->db->select();

        if (! empty($taskTypes)) {
            $select->where('taskType IN (?)', $taskTypes);
        }
        if (! empty($taskStates)) {
            $select->where('state IN (?)', $taskStates);
        }
        if (is_numeric($taskIdentifier)) {
            $select->where('id = ?', $taskIdentifier);
        } elseif (preg_match($config->runtimeOptions->defines->GUID_REGEX, $taskIdentifier) === 1) {
            $select->where('taskGuid = ?', '{' . trim($taskIdentifier, '{}') . '}');
        } elseif (preg_match(self::WITH_DATE_PATTERN, $taskIdentifier, $matches) === 1) {
            $select
                ->where('taskName LIKE ?', '%' . $matches[1] . '%')
                ->where('created LIKE ?', '%' . $matches[2] . '%');
        } else {
            $select->where('taskName LIKE ?', '%' . $taskIdentifier . '%');
        }

        $tasks = $task->db->fetchAll($select)->toArray();

        if (count($tasks) === 0) {
            if (! $silent) {
                $io->error('Task "' . $taskIdentifier . '" not found.');
            }

            return null;
        } elseif (count($tasks) === 1) {
            $taskId = (int) $tasks[0]['id'];
        } elseif ($silent) {
            // in silent mode the search must find exactly one task
            return null;
        } else {
            $taskNames = [];
            foreach ($tasks as $data) {
                $taskNames[] = $data['taskName'] . ' (id: ' . $data['id'] . ')';
            }
            $question = new ChoiceQuestion('Please choose a Task', $taskNames, null);
            $taskName = $io->askQuestion($question);
            $parts = explode(' (id: ', $taskName);
            $taskId = (int) rtrim(array_pop($parts), ')');
        }
        $task->load($taskId);

        return $task;
    }

    /**
     * Searches multiple tasks by identifier
     * The identifier might be
     * - equals a task-id
     * - like a task-guid
     * - like a task-name
     * - like an order-no / taskNr
     */
    public static function searchTasksFromArgument(string $searchQuery, bool $onlyById = false): array
    {
        $task = new editor_Models_Task();
        $s = $task->db->select()
            //languages here too?
            ->from($task->db, [
                'ID' => 'id',
                'TaskGUID' => 'taskGuid',
                'Order No.' => 'taskNr',
                'Task name' => 'taskName',
                'External ID' => 'foreignId',
            ])
            ->where('id = ?', $searchQuery);
        if (! $onlyById) {
            $s->orWhere('foreignId = ?', $searchQuery)
                ->orWhere('taskGuid like ?', '%' . $searchQuery . '%')
                ->orWhere('taskName like ?', '%' . $searchQuery . '%')
                ->orWhere('taskNr like ?', '%' . $searchQuery . '%');
        }

        return $task->db->fetchAll($s)->toArray();
    }
}
