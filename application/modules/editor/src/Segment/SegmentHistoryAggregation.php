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

namespace MittagQI\Translate5\Segment;

use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Dto\StatisticSegmentDTO;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Logger;

/**
 * DB Client Wrapper for Segments History Aggregated Data
 */
class SegmentHistoryAggregation
{
    private const string INITIAL_WORKFLOW_STEP = '_initial';

    public const string TABLE_NAME_POSTEDITING = 'LEK_statistics_postediting_aggregation';

    public const string TABLE_NAME_STATISTICS = 'LEK_statistics_segment_aggregation';

    private const array CLONE_SYNTHETIC_STEP_COLUMNS = [
        'taskGuid',
        'userGuid',
        'workflowName',
        'workflowStepName',
        'segmentId',
        'levenshteinOriginal',
        'levenshteinPrevious',
        'matchRate',
        'langResType',
        'langResId',
        'editable',
        'latestEntry',
        'qualityScore',
        'segmentlengthPrevious',
    ];

    /**
     * @var StatisticSegmentDTO[]
     */
    private array $buffer = [];

    public function __construct(
        private readonly ?AbstractStatisticsDB $client,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(
            Zend_Registry::get('statistics'),
            Zend_Registry::get('logger')->cloneMe('core.db.statistics')
        );
    }

    public function upsertBuffered(StatisticSegmentDTO $statisticSegmentDTO): void
    {
        // trim brackets
        $this->buffer[] = $statisticSegmentDTO;
    }

    public function flushUpserts(): bool
    {
        if (empty($this->buffer)) {
            return false;
        }

        try {
            $buffer = [];
            foreach ($this->buffer as $row) {
                $buffer[] = $row->toStatisticArray();
            }

            $this->client->upsert(
                self::TABLE_NAME_STATISTICS,
                $buffer,
                array_keys(reset($buffer))
            );
        } catch (Throwable $e) {
            $this->buffer = [];
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');

            return false;
        }

        $this->buffer = [];

        return true;
    }

    public function upsert(StatisticSegmentDTO $statisticSegmentDTO): bool
    {
        $this->upsertBuffered($statisticSegmentDTO);

        return $this->flushUpserts();
    }

    /**
     * Update single segment tqe value. This can happen when single segment TQE evaluation is triggered.
     */
    public function updateQualityScore(
        string $taskGuid,
        int $segmentId,
        string $editedInStep,
        ?int $qualityScore,
    ): void {
        $bind = [
            'taskGuid' => trim($taskGuid, '{}'),
            'segmentId' => $segmentId,
            'editedInStep' => $editedInStep,
            'editedInStepPriority' => $editedInStep,
            'initialStep' => self::INITIAL_WORKFLOW_STEP,
        ];
        $value = $qualityScore === null ? 'NULL' : $qualityScore;

        try {
            $toUpdate = $this->client->oneAssoc(
                'SELECT workflowStepName FROM ' . self::TABLE_NAME_STATISTICS .
                ' WHERE taskGuid = :taskGuid AND segmentId = :segmentId
                AND workflowStepName IN (:editedInStep, :initialStep)
                ORDER BY CASE WHEN workflowStepName = :editedInStepPriority THEN 0 ELSE 1 END LIMIT 1',
                $bind
            );

            if (empty($toUpdate)) {
                return;
            }

            $bind['workflowStepName'] = (string) $toUpdate['workflowStepName'];
            $this->client->query(
                'UPDATE ' . self::TABLE_NAME_STATISTICS . ' SET qualityScore=' . $value .
                ' WHERE taskGuid = :taskGuid AND segmentId = :segmentId AND workflowStepName = :workflowStepName',
                $bind
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    public function removeTaskData(string $taskGuid): void
    {
        $bind = [
            'taskGuid' => trim($taskGuid, '{}'),
        ];

        try {
            $this->client->query(
                'DELETE FROM ' . self::TABLE_NAME_POSTEDITING . ' WHERE taskGuid = :taskGuid',
                $bind
            );
            $this->client->query(
                'DELETE FROM ' . self::TABLE_NAME_STATISTICS . ' WHERE taskGuid = :taskGuid',
                $bind
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    private function logError(string $errMsg): void
    {
        $this->logger->error('E1633', 'Statistics DB error: {msg}', [
            'msg' => $errMsg,
        ]);
    }

    /**
     * @param array<int, array{
     *   taskGuid: string,
     *   segmentId: int,
     *   userGuid: string,
     *   wfStepName: string,
     *   duration: int
     * }> $data
     */
    public function flushPosteditingTimes(array $data): void
    {
        if (empty($data)) {
            return;
        }

        try {
            $this->flushPosteditingData($data);
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    public function increaseOrInsertPosteditingDuration(
        string $taskGuid,
        int $segmentId,
        string $workflowStepName,
        string $userGuid,
        int $duration,
    ): void {
        if ($duration === 0 || $this->client === null) {
            return;
        }

        try {
            $this->client->upsertIncrementDuration(
                self::TABLE_NAME_POSTEDITING,
                trim($taskGuid, '{}'),
                $segmentId,
                $workflowStepName,
                trim($userGuid, '{}'),
                $duration
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    public function updateOrInsertEditable(StatisticSegmentDTO $statisticSegmentDTO): void
    {
        if ($this->client === null) {
            return;
        }

        $bind = [
            'taskGuid' => $statisticSegmentDTO->taskGuid,
            'segmentId' => $statisticSegmentDTO->segmentId,
            'workflowStepName' => $statisticSegmentDTO->wfStepName,
        ];

        try {
            //reset all and make the changed one the latest below
            $this->resetLastEdit($statisticSegmentDTO->taskGuid, $statisticSegmentDTO->segmentId);

            $existing = $this->client->oneAssoc(
                'SELECT segmentId FROM ' . self::TABLE_NAME_STATISTICS .
                ' WHERE taskGuid = :taskGuid AND segmentId = :segmentId
                AND workflowStepName = :workflowStepName LIMIT 1',
                $bind
            );

            if (! empty($existing)) {
                $this->client->query(
                    'UPDATE ' . self::TABLE_NAME_STATISTICS .
                    ' SET editable = :editable, latestEntry = 1' .
                    ' WHERE taskGuid = :taskGuid AND segmentId = :segmentId AND workflowStepName = :workflowStepName',
                    array_merge($bind, [
                        'editable' => $statisticSegmentDTO->isEditable,
                    ])
                );

                return;
            }

            $row = $statisticSegmentDTO->toStatisticArray();
            $row['latestEntry'] = 1;
            $this->client->upsert(
                self::TABLE_NAME_STATISTICS,
                [array_values($row)],
                array_keys($row)
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    public function resetLastEdit(string $taskGuid, ?int $segmentId = null): void
    {
        if ($this->client === null) {
            return;
        }

        try {
            $bind = [
                'taskGuid' => trim($taskGuid, '{}'),
            ];
            $sql = 'UPDATE ' . self::TABLE_NAME_STATISTICS . ' SET latestEntry = 0 WHERE taskGuid = :taskGuid';
            if ($segmentId !== null) {
                $sql .= ' AND segmentId = :segmentId';
                $bind['segmentId'] = $segmentId;
            }
            $this->client->query($sql, $bind);
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    private function getCloneSelectSql(): string
    {
        //see self::CLONE_SYNTHETIC_STEP_COLUMNS for column order!
        return 'SELECT
                    src.taskGuid,
                    src.userGuid,
                    :workflowName,
                    :workflowStepName,
                    src.segmentId,
                    src.levenshteinOriginal,
                    0, -- levenshteinPrevious
                    src.matchRate,
                    src.langResType,
                    src.langResId,
                    src.editable,
                    0, -- latestEntry
                    src.qualityScore,
                    src.segmentlengthPrevious
                FROM ' . self::TABLE_NAME_STATISTICS . ' src
                WHERE src.taskGuid = :taskGuid';
    }

    public function cloneSyntheticEntriesOnAggregation(
        string $taskGuid,
        string $workflowName,
        string $workflowStepName,
        string $previousWorkflowStepName,
    ): void {
        if ($this->client === null) {
            return;
        }

        $bind = [
            'taskGuid' => trim($taskGuid, '{}'),
            'workflowName' => $workflowName,
            'workflowStepName' => $workflowStepName,
            'previousStep' => $previousWorkflowStepName,
        ];

        try {
            // clone previous step rows and ignore duplicate unique-key conflicts for rows created by history entries
            // latest entries are always the ones which exist already out of segment data and history
            $this->client->insertSelectIgnore(
                self::TABLE_NAME_STATISTICS,
                self::CLONE_SYNTHETIC_STEP_COLUMNS,
                $this->getCloneSelectSql() . ' AND src.workflowStepName = :previousStep',
                $bind
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    public function cloneSyntheticEntriesForWorkflowStep(
        string $taskGuid,
        string $workflowName,
        string $workflowStepName,
    ): void {
        if ($this->client === null) {
            return;
        }

        $bind = [
            'taskGuid' => trim($taskGuid, '{}'),
            'workflowName' => $workflowName,
            'workflowStepName' => $workflowStepName,
            'initialStep' => self::INITIAL_WORKFLOW_STEP,
        ];

        try {
            // Two-phase clone strategy for new workflow step reached:
            // 1) clone latestEntry=1 rows first
            // 2) clone _initial fallback rows and ignore duplicate unique-key conflicts
            $this->client->insertSelectIgnore(
                self::TABLE_NAME_STATISTICS,
                self::CLONE_SYNTHETIC_STEP_COLUMNS,
                $this->getCloneSelectSql() . ' AND src.latestEntry = 1',
                $bind
            );

            $this->client->insertSelectIgnore(
                self::TABLE_NAME_STATISTICS,
                self::CLONE_SYNTHETIC_STEP_COLUMNS,
                $this->getCloneSelectSql() . ' AND src.workflowStepName = :initialStep AND src.latestEntry = 0',
                $bind
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }
    }

    /**
     * @param array<int, array{
     *   taskGuid: string,
     *   segmentId: int,
     *   userGuid: string,
     *   wfStepName: string,
     *   duration: int
     * }> $toProcess
     */
    private function flushPosteditingData(array $toProcess): void
    {
        $buffer = [];

        foreach ($toProcess as $row) {
            //for postediting time we can skip entries with no time/duration
            if ((int) $row['duration'] === 0) {
                continue;
            }
            $buffer[] = [
                trim($row['taskGuid'], '{}'),
                (int) $row['segmentId'],
                trim($row['userGuid'], '{}'),
                $row['wfStepName'],
                (int) $row['duration'],
            ];
        }

        if (empty($buffer)) {
            return;
        }

        $this->client->upsert(
            self::TABLE_NAME_POSTEDITING,
            $buffer,
            [
                'taskGuid',
                'segmentId',
                'userGuid',
                'workflowStepName',
                'duration',
            ]
        );
    }
}
