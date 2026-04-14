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

namespace MittagQI\Translate5\Statistics\Helpers;

use editor_Models_Segment;
use editor_Models_Segment_AutoStates;
use editor_Models_SegmentField;
use editor_Models_Task;
use editor_Workflow_Default;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\Dto\StatisticSegmentAggregationDTO;
use MittagQI\Translate5\Statistics\Dto\StatisticSegmentDTO;
use MittagQI\Translate5\Statistics\SegmentLevenshteinRepository;
use MittagQI\Translate5\Workflow\TaskWorkflowLogRepository;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Exception;
use ZfExtended_Models_Entity_NotFoundException;

class AggregateTaskStatistics
{
    /**
     * @var array<string, int>
     */
    private array $languageResourceIdMap;

    public function __construct(
        private readonly SegmentHistoryAggregation $aggregator,
        private readonly SegmentLevenshteinRepository $segmentLevenshteinRepository,
        private readonly TaskWorkflowLogRepository $taskWorkflowLogRepository,
        private readonly ?Zend_Db_Adapter_Abstract $db
    ) {
        $this->languageResourceIdMap = $this->db->fetchPairs('SELECT langResUuid,id FROM LEK_languageresources');
    }

    /**
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(
            SegmentHistoryAggregation::create(),
            SegmentLevenshteinRepository::create(),
            TaskWorkflowLogRepository::create(),
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function aggregateHistoricalData(string $taskGuid): void
    {
        $this->aggregator->resetLastEdit($taskGuid);
        $this->aggregator->flushPosteditingTimes(
            $this->fetchGroupedDurationSums($taskGuid)
        );

        $task = new editor_Models_Task();
        $task->loadByTaskGuid($taskGuid);

        $historyBySegmentId = $this->fetchSegmentHistoryGroupedBySegmentId($taskGuid);
        $segments = $this->fetchSegmentsForTask($taskGuid);
        foreach ($segments as $segment) {
            $historyEntries = $historyBySegmentId[(int) $segment['id']] ?? [];
            $this->processSegments($task, $segment, $historyEntries);
        }
        $this->aggregator->flushUpserts();
        $this->cloneUnmodifiedSegmentsPerWorkflowStep($task);
    }

    public function aggregateOnImport(editor_Models_Task $task): void
    {
        $this->aggregator->resetLastEdit($task->getTaskGuid()); //mostly empty, just for correctness
        $segments = $this->fetchSegmentsForTask($task->getTaskGuid());
        foreach ($segments as $segment) {
            $this->processSegments($task, $segment, []);
        }
        $this->aggregator->flushUpserts();
        $this->cloneUnmodifiedSegmentsPerWorkflowStep($task);
    }

    /**
     * On pre-translation no manually segment edits may exist, therefore we just update all
     * stat entries (what are only synthetic cloned ones) for that segment
     */
    public function syncPretranslationStatisticsForSegment(editor_Models_Segment $segment): void
    {
        $this->aggregator->updatePretranslationStatistics(
            $segment->getTaskGuid(),
            (int) $segment->getId(),
            (int) $segment->getMatchRate(),
            $segment->getMatchRateType(),
            $this->resolveLangResId([
                'langResId' => $segment->meta()->getPreTransLangResUuid(),
            ]),
            (int) $segment->getEditable(),
            ($segment->getQualityScore() !== null && $segment->getQualityScore() !== '')
                ? (int) $segment->getQualityScore()
                : null,
        );
    }

    public function removeData(string $taskGuid): void
    {
        $this->segmentLevenshteinRepository->removeByTaskGuid($taskGuid);
        $this->aggregator->removeTaskData($taskGuid);
    }

    /**
     * Fetch aggregated postediting durations from LEK_segments and LEK_segment_history summed and grouped by
     * (segmentId, userGuid, editedInStep) for one task.
     *
     * @return array<int, array{
     *   taskGuid: string,
     *   segmentId: int,
     *   userGuid: string,
     *   wfStepName: string,
     *   duration: int
     * }>
     */
    private function fetchGroupedDurationSums(string $taskGuid): array
    {
        // Sum up durations from current and history rows per segment/user/workflowStep for one task.
        $sql = <<<SQL
            SELECT
                t.segmentId,
                t.userGuid,
                t.editedInStep,
                SUM(t.duration) AS duration
            FROM (
                SELECT
                    s.id AS segmentId,
                    s.userGuid,
                    s.editedInStep,
                    SUM(sd.duration) AS duration
                FROM LEK_segments s
                INNER JOIN LEK_segment_data sd
                    ON sd.segmentId = s.id
                    AND sd.name = :targetFieldSegment
                WHERE s.taskGuid = :taskGuidSegment
                    AND sd.duration > 0
                GROUP BY
                    s.id,
                    s.userGuid,
                    s.editedInStep

                UNION ALL

                SELECT
                    h.segmentId,
                    h.userGuid,
                    h.editedInStep,
                    SUM(hd.duration) AS duration
                FROM LEK_segment_history h
                INNER JOIN LEK_segment_history_data hd
                    ON hd.segmentHistoryId = h.id
                    AND hd.name = :targetFieldHistory
                WHERE h.taskGuid = :taskGuidHistory
                    AND hd.duration > 0
                GROUP BY
                    h.segmentId,
                    h.userGuid,
                    h.editedInStep
            ) t
            GROUP BY
                t.segmentId,
                t.userGuid,
                t.editedInStep
            SQL;

        $rows = $this->db->fetchAll($sql, [
            'targetFieldSegment' => editor_Models_SegmentField::TYPE_TARGET,
            'taskGuidSegment' => $taskGuid,
            'targetFieldHistory' => editor_Models_SegmentField::TYPE_TARGET,
            'taskGuidHistory' => $taskGuid,
        ]);
        if (empty($rows)) {
            return [];
        }

        $durationRows = [];
        foreach ($rows as $row) {
            $durationRows[] = [
                'taskGuid' => trim($taskGuid, '{}'),
                'segmentId' => (int) $row['segmentId'],
                'userGuid' => trim((string) $row['userGuid'], '{}'),
                'wfStepName' => (string) $row['editedInStep'],
                'duration' => (int) $row['duration'],
            ];
        }

        return $durationRows;
    }

    /**
     * Read task history ordered by segment and REVERSED timeline and group by segmentId.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function fetchSegmentHistoryGroupedBySegmentId(string $taskGuid): array
    {
        $sql = <<<SQL
            SELECT
                h.segmentId as id,
                h.userGuid,
                h.editedInStep,
                h.workflowStep,
                COALESCE(hs.levenshteinOriginal, 0) AS levenshteinOriginal,
                COALESCE(hs.levenshteinPrevious, 0) AS levenshteinPrevious,
                COALESCE(hs.segmentlengthPrevious, 0) AS segmentlengthPrevious,
                h.matchRate,
                h.matchRateType,
                h.editable,
                h.autoStateId,
                h.qualityScore,
                h.id as historyId
            FROM LEK_segment_history h
            LEFT JOIN LEK_segment_statistics hs
                ON hs.segmentId = h.segmentId
                AND hs.historyId = h.id
            WHERE h.taskGuid = :taskGuid AND h.autoStateId != :blockedState
            ORDER BY
                h.segmentId ASC,
                h.id DESC
            SQL; // we return from newest to oldest history entry!

        $rows = $this->db->fetchAll($sql, [
            'taskGuid' => $taskGuid,
            //blocked segments are always blocked and never editable so we have to ignore
            // normally there should be no history entry, but we never know...
            'blockedState' => editor_Models_Segment_AutoStates::BLOCKED,
        ]);
        if (empty($rows)) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $segmentId = (int) $row['id'];
            if (! isset($grouped[$segmentId])) {
                $grouped[$segmentId] = [];
            }
            $grouped[$segmentId][] = $row;
        }

        return $grouped;
    }

    /**
     * Read all segments of a task as base input for segment-centric aggregation.
     *
     * @return array<int, array{
     *      id: int,
     *      userGuid: string,
     *      editedInStep: string,
     *      workflowStep: string|null,
     *      levenshteinOriginal: int,
     *      levenshteinPrevious: int,
     *      segmentlengthPrevious: int,
     *      matchRate: int,
     *      matchRateType: string|null,
     *      editable: int,
     *      autoStateId: int,
     *      qualityScore: string|null,
     *      langResId: int|null,
     *      timestampTs: int
     *  }>
     */
    private function fetchSegmentsForTask(string $taskGuid): array
    {
        $sql = <<<SQL
            SELECT
                s.id,
                s.userGuid,
                s.editedInStep,
                s.workflowStep,
                COALESCE(ss.levenshteinOriginal, 0) AS levenshteinOriginal,
                COALESCE(ss.levenshteinPrevious, 0) AS levenshteinPrevious,
                COALESCE(ss.segmentlengthPrevious, 0) AS segmentlengthPrevious,
                s.matchRate,
                s.matchRateType,
                s.editable,
                s.autoStateId,
                s.qualityScore,
                m.preTransLangResUuid as langResId
            FROM LEK_segments s
            JOIN LEK_segments_meta m ON (s.id = m.segmentId)
            LEFT JOIN LEK_segment_statistics ss
                ON ss.segmentId = s.id
                AND ss.historyId = 0
            WHERE s.taskGuid = :taskGuid AND s.autoStateId != :blockedState
            ORDER BY s.id ASC
            SQL;

        return $this->db->fetchAll($sql, [
            'taskGuid' => $taskGuid,
            //blocked segments are always blocked and never editable so we have to ignore
            'blockedState' => editor_Models_Segment_AutoStates::BLOCKED,
        ]);
    }

    /**
     * Segment-centric processing entry point used by the new refactor block.
     */
    private function processSegments(editor_Models_Task $task, array $segment, array $historyEntries): void
    {
        $langResId = $this->resolveLangResId($segment);

        $currentSegment = $this->getAggregationDto($segment);

        $toBeProcessed = $this->reduceUnneeded($currentSegment, $historyEntries);
        $toBeProcessed = $this->reduceEditedToOnePerStep($toBeProcessed);

        //at least one segment is needed!
        if (empty($toBeProcessed)) {
            $toBeProcessed[] = $currentSegment;
        }
        $toBeProcessed[0]->latestEntry = 1;
        $this->saveSegmentEntries($task, $langResId, $toBeProcessed);
    }

    /**
     * @param array{langResId:int|string|null} $segment
     */
    private function resolveLangResId(array $segment): int
    {
        if (array_key_exists($segment['langResId'], $this->languageResourceIdMap)) {
            return (int) $this->languageResourceIdMap[$segment['langResId']];
        }

        return 0;
    }

    private function getAggregationDto(
        array $segment
    ): StatisticSegmentAggregationDTO {
        $editedInStep = empty($segment['editedInStep'])
                ? editor_Workflow_Default::STEP_NO_WORKFLOW
                : (string) $segment['editedInStep'];

        return StatisticSegmentAggregationDTO::fromAssocArray([
            'userGuid' => (string) $segment['userGuid'],
            'editedInStep' => $editedInStep,
            'workflowStep' => (string) $segment['workflowStep'],
            'id' => (int) $segment['id'],
            'autoStateId' => (int) $segment['autoStateId'],
            'matchRate' => (int) $segment['matchRate'],
            'matchRateType' => (string) $segment['matchRateType'],
            'qualityScore' => (int) $segment['qualityScore'],
            'isEditable' => (int) $segment['editable'],
            'levenshteinOriginal' => (int) $segment['levenshteinOriginal'],
            'levenshteinPrevious' => (int) $segment['levenshteinPrevious'],
            'segmentlengthPrevious' => (int) ($segment['segmentlengthPrevious'] ?? 0),
        ]);
    }

    /**
     * @return StatisticSegmentAggregationDTO[]
     */
    private function reduceEditedToOnePerStep(array $toBeProcessed): array
    {
        /**
         * @var StatisticSegmentAggregationDTO $initialEntry
         */
        $initialEntry = end($toBeProcessed);
        if ($initialEntry->levenshteinPrevious === 0 && $initialEntry->levenshteinOriginal === 0) {
            $initialEntry->editedInStep = $this->aggregator::INITIAL_WORKFLOW_STEP;
        }

        $result = [];
        $stepProcessedAlready = [];
        foreach ($toBeProcessed as $segment) {
            // ignore: if found already an entry to that step - or if was untouched
            if ($stepProcessedAlready[$segment->editedInStep] ?? false) {
                continue;
            }
            $result[] = $segment;
            $stepProcessedAlready[$segment->editedInStep] = true;
        }

        return $result;
    }

    /**
     * @return StatisticSegmentAggregationDTO[]
     */
    private function reduceUnneeded(
        StatisticSegmentAggregationDTO $currentSegment,
        array $historyEntries,
    ): array {
        $toBeProcessed = [];
        if ($currentSegment->autoStateId != editor_Models_Segment_AutoStates::REVIEWED_UNTOUCHED) {
            $toBeProcessed[] = $currentSegment;
        }
        foreach ($historyEntries as $historyEntry) {
            $dto = $this->getAggregationDto($historyEntry);
            $toBeProcessed[] = $dto;

            if ($dto->autoStateId === editor_Models_Segment_AutoStates::REVIEWED_UNTOUCHED) {
                continue;
            }

            //if we reached PRETRANSLATED no further histories are needed,
            // since content is empty and matchRate and matchType is not given
            if ($dto->autoStateId === editor_Models_Segment_AutoStates::PRETRANSLATED) {
                return $toBeProcessed;
            }
        }

        return $toBeProcessed;
    }

    /**
     * @param StatisticSegmentAggregationDTO[] $toBeProcessed
     */
    private function saveSegmentEntries(
        editor_Models_Task $task,
        int $langResId,
        array $toBeProcessed,
    ): void {
        foreach (array_reverse($toBeProcessed) as $segment) {
            $this->aggregator->upsertBuffered(new StatisticSegmentDTO(
                $task->getTaskGuid(),
                $segment->userGuid,
                $task->getWorkflow(),
                $segment->editedInStep,
                $segment->id,
                $segment->levenshteinOriginal,
                $segment->levenshteinPrevious,
                $segment->matchRate,
                $segment->matchRateType,
                $langResId,
                $segment->isEditable,
                $segment->qualityScore,
                $segment->latestEntry,
                $segment->segmentlengthPrevious,
            ));
        }
    }

    private function cloneUnmodifiedSegmentsPerWorkflowStep(editor_Models_Task $task): void
    {
        $steps = $this->taskWorkflowLogRepository->getDistinctStepsInOrder($task->getTaskGuid());
        $previousStep = $this->aggregator::INITIAL_WORKFLOW_STEP;
        foreach ($steps as $step) {
            $this->aggregator->cloneSyntheticEntriesOnAggregation(
                $task->getTaskGuid(),
                $task->getWorkflow(),
                $step,
                $previousStep,
            );
            $previousStep = $step;
        }
    }
}
