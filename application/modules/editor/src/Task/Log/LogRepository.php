<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Log;

use editor_Models_Logger_Task as TaskLog;
use editor_Models_Task as Task;
use ZfExtended_Logger;

class LogRepository
{
    public function getLogSummaryForTask(array $taskGuids): array
    {
        $db = (new TaskLog())->db;

        $s = $db->select()
            ->from($db->info($db::NAME), [
                'level',
                'count' => 'COUNT(*)',
                'message' => 'GROUP_CONCAT(message SEPARATOR "|")',
            ])
            ->where('taskGuid IN(?)', $taskGuids)
            ->where('level in (?)', [
                ZfExtended_Logger::LEVEL_FATAL,
                ZfExtended_Logger::LEVEL_ERROR,
                ZfExtended_Logger::LEVEL_WARN,
            ])

            ->group('level')
            ->order('level ASC');

        return $db->fetchAll($s)->toArray();
    }

    public function getLogSummaryForProject(int $projectId): array
    {
        $task = new Task();
        $taskGuids = $task->loadProjectTasks($projectId, true);

        $taskGuids = array_column($taskGuids, 'taskGuid');

        if (empty($taskGuids)) {
            return [];
        }

        return $this->getLogSummaryForTask($taskGuids);
    }
}
