<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2026 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Statement_Exception;
use Zend_Db_Table;
use Zend_Exception;

class StatisticsEditedInStepBackfillCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'statistics:workflowstep:backfill';

    private Zend_Db_Adapter_Abstract $db;

    /**
     * @var array<string, bool>
     */
    private array $createdHelperIndexes = [];

    protected function configure(): void
    {
        $this
            ->setDescription('Statistics migration: backfill editedInStep and task workflow log task-by-task.')
            ->setHelp('Backfill LEK_segments.editedInStep and LEK_segment_history.editedInStep'
                . 'using per-task processing.')
            ->addOption(
                'taskId',
                't',
                InputOption::VALUE_OPTIONAL,
                'Backfill only one task by numeric id'
            )
            ->addOption(
                'taskGuid',
                'g',
                InputOption::VALUE_OPTIONAL,
                'Backfill only one task by taskGuid'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Process only first N tasks (ordered by taskGuid)',
                0
            )
            ->addOption(
                'continue',
                'c',
                InputOption::VALUE_NONE,
                'Continue mode: process only tasks where editedInStep is still empty'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show how many rows per task would be affected without writing data'
            );
    }

    /**
     * @throws Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $this->db = Zend_Db_Table::getDefaultAdapter();

        $limit = (int) ($this->input->getOption('limit') ?? 0);
        $allowFullRun = (bool) $this->input->getOption('continue');
        $dryRun = (bool) $this->input->getOption('dry-run');

        $taskGuid = $this->findTaskGuidFromInput();

        if ($taskGuid === '' && ! $allowFullRun && $this->input->isInteractive()) {
            $confirmed = $this->io->confirm(
                'Security check: start full backfill for all tasks? Re-runs should use --continue.',
                false
            );
            if (! $confirmed) {
                return self::SUCCESS;
            }
        }

        $taskGuids = $this->resolveTaskGuids($taskGuid, $limit, $allowFullRun);
        if ($taskGuids === []) {
            $this->io->success(
                $allowFullRun
                    ? 'No tasks found to process.'
                    : 'No tasks with empty editedInStep found.'
            );

            return self::SUCCESS;
        }

        $this->io->writeln('Processing ' . count($taskGuids) . ' task(s).' . ($dryRun ? ' [dry-run]' : ''));
        if (! $dryRun) {
            $this->ensureHelperIndexes();
        }

        $processStatistics = $this->processTasks($taskGuids, $dryRun);

        if (! $dryRun) {
            $this->dropHelperIndexes();
        }

        $this->io->success([
            'Done' . ($dryRun ? ' [dry-run].' : '.'),
            'Processed: ' . $processStatistics['processed'],
            'Failed: ' . $processStatistics['failed'],
            'Workflow events ' . ($dryRun ? 'to insert' : 'inserted')
                . ': ' . $processStatistics['workflowLogInserted'],
            'Segment rows ' . ($dryRun ? 'to update' : 'updated') . ': ' . $processStatistics['segmentsUpdated'],
            'History rows ' . ($dryRun ? 'to update' : 'updated') . ': ' . $processStatistics['historyUpdated'],
        ]);

        return $processStatistics['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolveTaskGuids(string $taskGuid, int $limit, bool $continueMode): array
    {
        if ($taskGuid !== '') {
            return [$taskGuid];
        }

        if (! $continueMode) {
            $sql = <<<SQL
SELECT t.taskGuid
FROM LEK_task t
ORDER BY t.id
SQL;
        } else {
            $sql = <<<SQL
SELECT x.taskGuid
FROM (
    SELECT DISTINCT s.taskGuid
    FROM LEK_segments s
    WHERE s.editedInStep = ''
    UNION
    SELECT DISTINCT h.taskGuid
    FROM LEK_segment_history h
    WHERE h.editedInStep = ''
) AS x
ORDER BY x.taskGuid
SQL;
        }

        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        return array_map('strval', $this->db->fetchCol($sql));
    }

    private function hasPmCheckData(string $taskGuid): bool
    {
        $sql = <<<SQL
SELECT (
    EXISTS(
        SELECT 1
        FROM LEK_segments s
        WHERE s.taskGuid = :taskGuid
          AND s.editedInStep = ''
          AND (s.workflowStep IS NULL OR s.workflowStep = 'pmCheck')
    )
    OR EXISTS(
        SELECT 1
        FROM LEK_segment_history h
        WHERE h.taskGuid = :taskGuid
          AND h.editedInStep = ''
          AND (h.workflowStep IS NULL OR h.workflowStep = 'pmCheck')
    )
) AS hasData
SQL;

        return (bool) $this->db->fetchOne($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    private function insertWorkflowEvents(string $taskGuid): int
    {
        $sql = <<<SQL
INSERT INTO LEK_task_workflow_log (taskGuid, workflowName, workflowStepName, userGuid, created)
SELECT
    tl.taskGuid,
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.workflow')), 'default') AS workflowName,
    CASE
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.newStep')) = 'workflowEnded'
            THEN 'workflowEnded'
        WHEN JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.step')) = 'no workflow'
            THEN 'no workflow'
        ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.step')), '')
    END AS workflowStepName,
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.userGuid')), '00000000-0000-0000-0000-000000000000') AS userGuid,
    tl.created
FROM LEK_task_log tl
WHERE tl.taskGuid = :taskGuid
  AND tl.domain = 'editor.workflow'
  AND tl.level = 8
  AND tl.eventCode = 'E1013'
  AND (
      tl.message LIKE 'setNextStep: workflow next step "workflowEnded"%'
      OR tl.message LIKE 'recalculate workflow to step %'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM LEK_task_workflow_log twl
      WHERE twl.taskGuid = tl.taskGuid
        AND twl.created = tl.created
        AND twl.workflowStepName = CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.newStep')) = 'workflowEnded'
                THEN 'workflowEnded'
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.step')) = 'no workflow'
                THEN 'no workflow'
            ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.step')), '')
        END
  )
SQL;

        return $this->db->query($sql, [
            'taskGuid' => $taskGuid,
        ])->rowCount();
    }

    private function countWorkflowEventsToInsert(string $taskGuid): int
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM LEK_task_log tl
WHERE tl.taskGuid = :taskGuid
  AND tl.domain = 'editor.workflow'
  AND tl.level = 8
  AND tl.eventCode = 'E1013'
  AND (
      tl.message LIKE 'setNextStep: workflow next step "workflowEnded"%'
      OR tl.message LIKE 'recalculate workflow to step %'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM LEK_task_workflow_log twl
      WHERE twl.taskGuid = tl.taskGuid
        AND twl.created = tl.created
        AND twl.workflowStepName = CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.newStep')) = 'workflowEnded'
                THEN 'workflowEnded'
            WHEN JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.step')) = 'no workflow'
                THEN 'no workflow'
            ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tl.extra, '$.step')), '')
        END
  )
SQL;

        return (int) $this->db->fetchOne($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    private function updateDirectHistory(string $taskGuid): int
    {
        $sql = <<<SQL
UPDATE LEK_segment_history h
SET h.editedInStep = h.workflowStep
WHERE h.taskGuid = :taskGuid
  AND h.editedInStep = ''
  AND h.workflowStep IS NOT NULL
  AND h.workflowStep <> 'pmCheck'
SQL;

        return $this->db->query($sql, [
            'taskGuid' => $taskGuid,
        ])->rowCount();
    }

    private function countDirectHistory(string $taskGuid): int
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM LEK_segment_history h
WHERE h.taskGuid = :taskGuid
  AND h.editedInStep = ''
  AND h.workflowStep IS NOT NULL
  AND h.workflowStep <> 'pmCheck'
SQL;

        return (int) $this->db->fetchOne($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    private function updateDirectSegments(string $taskGuid): int
    {
        $sql = <<<SQL
UPDATE LEK_segments s
SET s.editedInStep = s.workflowStep
WHERE s.taskGuid = :taskGuid
  AND s.editedInStep = ''
  AND s.workflowStep IS NOT NULL
  AND s.workflowStep <> 'pmCheck'
SQL;

        return $this->db->query($sql, [
            'taskGuid' => $taskGuid,
        ])->rowCount();
    }

    private function countDirectSegments(string $taskGuid): int
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM LEK_segments s
WHERE s.taskGuid = :taskGuid
  AND s.editedInStep = ''
  AND s.workflowStep IS NOT NULL
  AND s.workflowStep <> 'pmCheck'
SQL;

        return (int) $this->db->fetchOne($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    private function updatePmCheckHistory(string $taskGuid): int
    {
        $sql = <<<SQL
UPDATE LEK_segment_history h
SET h.editedInStep = COALESCE(
    (
        SELECT e.workflowStepName
        FROM LEK_task_workflow_log e
        WHERE e.taskGuid = h.taskGuid
          AND e.created <= h.created
        ORDER BY e.created DESC, e.id DESC
        LIMIT 1
    ),
    'no workflow'
)
WHERE h.taskGuid = :taskGuid
  AND h.editedInStep = ''
  AND (h.workflowStep IS NULL OR h.workflowStep = 'pmCheck')
SQL;

        return $this->db->query($sql, [
            'taskGuid' => $taskGuid,
        ])->rowCount();
    }

    private function countPmCheckHistory(string $taskGuid): int
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM LEK_segment_history h
WHERE h.taskGuid = :taskGuid
  AND h.editedInStep = ''
  AND (h.workflowStep IS NULL OR h.workflowStep = 'pmCheck')
SQL;

        return (int) $this->db->fetchOne($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    private function updatePmCheckSegments(string $taskGuid): int
    {
        $sql = <<<SQL
UPDATE LEK_segments s
SET s.editedInStep = COALESCE(
    (
        SELECT e.workflowStepName
        FROM LEK_task_workflow_log e
        WHERE e.taskGuid = s.taskGuid
          AND e.created <= s.timestamp
        ORDER BY e.created DESC, e.id DESC
        LIMIT 1
    ),
    'no workflow'
)
WHERE s.taskGuid = :taskGuid
  AND s.editedInStep = ''
  AND (s.workflowStep IS NULL OR s.workflowStep = 'pmCheck')
SQL;

        return $this->db->query($sql, [
            'taskGuid' => $taskGuid,
        ])->rowCount();
    }

    private function countPmCheckSegments(string $taskGuid): int
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM LEK_segments s
WHERE s.taskGuid = :taskGuid
  AND s.editedInStep = ''
  AND (s.workflowStep IS NULL OR s.workflowStep = 'pmCheck')
SQL;

        return (int) $this->db->fetchOne($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    private function ensureHelperIndexes(): void
    {
        $this->createIndexIfMissing(
            'LEK_segments',
            'idx_t5355_segments_edited_wf_task',
            'CREATE INDEX idx_t5355_segments_edited_wf_task ON LEK_segments (editedInStep, workflowStep, taskGuid)'
        );
        $this->createIndexIfMissing(
            'LEK_segment_history',
            'idx_t5355_history_edited_wf_task',
            'CREATE INDEX idx_t5355_history_edited_wf_task
    ON LEK_segment_history (editedInStep, workflowStep, taskGuid)'
        );
        $this->createIndexIfMissing(
            'LEK_task_log',
            'idx_t5355_task_log_extract',
            'CREATE INDEX idx_t5355_task_log_extract ON LEK_task_log (taskGuid, domain, level, eventCode, created, id)'
        );
    }

    private function dropHelperIndexes(): void
    {
        $this->dropIndexIfExists('LEK_segments', 'idx_t5355_segments_edited_wf_task');
        $this->dropIndexIfExists('LEK_segment_history', 'idx_t5355_history_edited_wf_task');
        $this->dropIndexIfExists('LEK_task_log', 'idx_t5355_task_log_extract');
    }

    private function createIndexIfMissing(string $table, string $index, string $createSql): void
    {
        if ($this->indexExists($table, $index)) {
            return;
        }
        $this->db->query($createSql);
        $this->createdHelperIndexes[$table . '.' . $index] = true;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! ($this->createdHelperIndexes[$table . '.' . $index] ?? false)) {
            return;
        }
        if (! $this->indexExists($table, $index)) {
            return;
        }
        $this->db->query(sprintf('DROP INDEX %s ON %s', $index, $table));
    }

    private function indexExists(string $table, string $index): bool
    {
        $sql = <<<SQL
SELECT 1
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = :tableName
  AND INDEX_NAME = :indexName
LIMIT 1
SQL;

        return (bool) $this->db->fetchOne($sql, [
            'tableName' => $table,
            'indexName' => $index,
        ]);
    }

    /**
     * @return array{processed: int, failed: int, workflowLogInserted: int, segmentsUpdated: int, historyUpdated: int}
     */
    private function processTasks(array $taskGuids, bool $dryRun): array
    {
        $progressBar = new ProgressBar($this->output, count($taskGuids));

        $processed = 0;
        $failed = 0;

        $workflowLogInserted = 0;
        $segmentsUpdated = 0;
        $historyUpdated = 0;

        foreach ($taskGuids as $oneTaskGuid) {
            try {
                if ($dryRun) {
                    $segmentsUpdated += $this->countDirectSegments($oneTaskGuid)
                        + $this->countPmCheckSegments($oneTaskGuid);
                    $historyUpdated += $this->countDirectHistory($oneTaskGuid)
                        + $this->countPmCheckHistory($oneTaskGuid);
                    if ($this->hasPmCheckData($oneTaskGuid)) {
                        $workflowLogInserted += $this->countWorkflowEventsToInsert($oneTaskGuid);
                    }
                } else {
                    $this->db->beginTransaction();

                    $segmentsUpdated += $this->updateDirectSegments($oneTaskGuid);
                    $historyUpdated += $this->updateDirectHistory($oneTaskGuid);

                    if ($this->hasPmCheckData($oneTaskGuid)) {
                        $workflowLogInserted += $this->insertWorkflowEvents($oneTaskGuid);
                        $segmentsUpdated += $this->updatePmCheckSegments($oneTaskGuid);
                        $historyUpdated += $this->updatePmCheckHistory($oneTaskGuid);
                    }

                    $this->db->commit();
                }
                $processed++;
            } catch (Throwable $e) {
                if (! $dryRun) {
                    $this->db->rollBack();
                }
                $failed++;
                $this->io->error(sprintf('Task %s failed: %s', $oneTaskGuid, $e->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->writeln('');

        return [
            'processed' => $processed,
            'failed' => $failed,
            'workflowLogInserted' => $workflowLogInserted,
            'segmentsUpdated' => $segmentsUpdated,
            'historyUpdated' => $historyUpdated,
        ];
    }

    private function findTaskGuidFromInput(): string
    {
        $taskId = (int) ($this->input->getOption('taskId') ?? 0);
        $taskGuid = trim((string) ($this->input->getOption('taskGuid') ?? ''));

        if ($taskId > 0 && $taskGuid !== '') {
            throw new RuntimeException('Use either --taskId or --taskGuid, not both.');
        }

        if ($taskId <= 0) {
            return $taskGuid;
        }

        $taskGuid = (string) $this->db->fetchOne(
            'SELECT taskGuid FROM LEK_task WHERE id = :id',
            [
                'id' => $taskId,
            ]
        );

        if ($taskGuid === '') {
            throw new RuntimeException('Task not found for id ' . $taskId);
        }

        return $taskGuid;
    }
}
