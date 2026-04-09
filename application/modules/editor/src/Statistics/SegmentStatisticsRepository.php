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

namespace MittagQI\Translate5\Statistics;

use editor_Workflow_Default;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\Dto\StatisticFilterDTO;
use MittagQI\Translate5\Statistics\Helpers\AggregateTaskStatistics;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Logger;

/**
 * Repository for Segments History Aggregated Data
 */
class SegmentStatisticsRepository
{
    public const int LEVENSHTEIN_PRECISION = 5;

    public function __construct(
        private readonly AbstractStatisticsDB $client,
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

    /**
     * @return array{
     *     affectedSegments: int,
     *     levenshteinDistanceNoWorkflow: ?float,
     *     posteditingTimeNoWorkflow: ?float,
     *     posteditingTimeInWorkflowStep: float,
     *     levenshteinDistanceInWorkflowStep: float,
     *     posteditingTimeInAllWorkflowSteps: float,
     *     levenshteinDistanceOriginal: ?float,
     *     levenshteinDistanceWorkflowEnded: ?float,
     *     posteditingTimeWorkflowEnded: ?float
     * }|array{}
     */
    public function getStatistics(array $taskGuids, ?StatisticFilterDTO $statisticFilter): array
    {
        if (! $this->client->isAlive()) {
            return [];
        }
        $bind = [];
        $bind['taskGuids'] = $this->trimBrackets($taskGuids);

        $sql = ' WHERE taskGuid IN (:taskGuids) '
            . $this->getCommonWhereSqlForAdvancedFilters($statisticFilter, $bind);

        //ATTENTION for statistic only editable segments are considered
        $sql .= ' AND editable = 1';

        if (! empty($statisticFilter->workflow)) {
            $sql .= ' AND workflowName IN (:workflows)';
            $bind['workflows'] = $statisticFilter->workflow;
        }

        return [
            'affectedSegments' => $this->getSegmentCount($sql, $bind),
            // Ø Levenshtein-Distanz vor Beginn des Workflows (no workflow)
            'levenshteinDistanceNoWorkflow' => $this->getLevenshteinDistanceByNonWorkflowStepAvg(
                $sql,
                $bind,
                $statisticFilter,
                editor_Workflow_Default::STEP_NO_WORKFLOW,
            ),
            // Ø Nachbearbeitungszeit vor Beginn des Workflows (no workflow)
            'posteditingTimeNoWorkflow' => $this->getPosteditingTimeByNonWorkflowStepAvg(
                $sql,
                $bind,
                $statisticFilter,
                editor_Workflow_Default::STEP_NO_WORKFLOW,
            ),
            // Ø Nachbearbeitungszeit innerhalb eines Workflow-Schritts
            'posteditingTimeInWorkflowStep' => $this->getPosteditingTimeInWorkflowStepAvg(
                $sql,
                $bind,
                $statisticFilter,
            ),
            // Ø Levenshtein-Distanz innerhalb eines Workflow-Schritts
            'levenshteinDistanceInWorkflowStep' => $this->getLevenshteinDistanceInWorkflowStepAvg(
                $sql,
                $bind,
                $statisticFilter,
            ),
            // Ø Nachbearbeitungszeit ab Import/Vorübersetzung
            'posteditingTimeInAllWorkflowSteps' => $this->getPosteditingTimeInAllWorkflowStepsAvg(
                $sql,
                $bind,
                $statisticFilter,
            ),
            // Ø Levenshtein-Distanz ab Beginn des Imports
            'levenshteinDistanceOriginal' => $this->getLevenshteinDistanceOriginalAvg(
                $sql,
                $bind,
                $statisticFilter,
            ),
            // Ø Levenshtein-Distanz nach Ende des Workflows (workflow ended)
            'levenshteinDistanceWorkflowEnded' => $this->getLevenshteinDistanceByNonWorkflowStepAvg(
                $sql,
                $bind,
                $statisticFilter,
                editor_Workflow_Default::STEP_WORKFLOW_ENDED,
            ),
            // ø Nachbearbeitungszeit nach Ende des Workflows (workflow ended)
            'posteditingTimeWorkflowEnded' => $this->getPosteditingTimeByNonWorkflowStepAvg(
                $sql,
                $bind,
                $statisticFilter,
                editor_Workflow_Default::STEP_WORKFLOW_ENDED,
            ),
        ];
    }

    public function getTaskGuidsMatchingFilter(
        array $taskGuids,
        StatisticFilterDTO $statisticFilter,
    ): array {
        if (! $this->client->isAlive()) {
            return [];
        }

        $bind = [
            'taskGuids' => $this->trimBrackets($taskGuids),
        ];
        $sqlWhere = $this->getCommonWhereSqlForAdvancedFilters($statisticFilter, $bind);

        //FIXME dokumentiueren → kein user und workflow step filter auf der Task Auswahl - nur später bei den Statistik daten!
        //FIXME when no filter set in $statisticFilter / $sqlWhere we cound just return the given TaskGuids
        // no query needed!

        try {
            $res = $this->client->select(
                'SELECT DISTINCT taskGuid FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS .
                ' WHERE taskGuid IN (:taskGuids)' . $sqlWhere,
                $bind
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');
        }

        if (empty($res)) {
            return [];
        }

        //re-add {} brackets since we store without them in stat table
        return array_map(
            fn ($guid) => '{' . $guid . '}',
            array_column($res, 'taskGuid')
        );
    }

    public function getOrphanedTaskIds(array $taskGuids): array
    {
        if (! $this->client->isAlive()) {
            return [];
        }

        try {
            $res = $this->client->select(
                'SELECT DISTINCT taskGuid FROM (' .
                ' SELECT taskGuid FROM ' . SegmentHistoryAggregation::TABLE_NAME_POSTEDITING .
                ' WHERE taskGuid NOT IN (:taskGuids)' .
                ' UNION ALL' .
                ' SELECT taskGuid FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS .
                ' WHERE taskGuid NOT IN (:taskGuids)' .
                ') AS orphanedTaskGuids',
                [
                    'taskGuids' => $this->trimBrackets($taskGuids),
                ]
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');

            return [];
        }

        return empty($res)
            ? []
            : array_map(
                fn ($guid) => '{' . $guid . '}',
                array_column($res, 'taskGuid')
            );
    }

    public function getPosteditingTimeAggregationBySegmentId(int $segmentId): array
    {
        return $this->getRowsBySegmentId(SegmentHistoryAggregation::TABLE_NAME_POSTEDITING, $segmentId);
    }

    public function getLevenshteinRowsBySegmentId(int $segmentId): array
    {
        return $this->getRowsBySegmentId(SegmentHistoryAggregation::TABLE_NAME_STATISTICS, $segmentId);
    }

    public function getPosteditingTimeAggregationByTaskGuid(string $taskGuid): array
    {
        return $this->getRowsByTaskGuid(SegmentHistoryAggregation::TABLE_NAME_POSTEDITING, $taskGuid);
    }

    public function getLevenshteinRowsByTaskGuid(string $taskGuid): array
    {
        return $this->getRowsByTaskGuid(SegmentHistoryAggregation::TABLE_NAME_STATISTICS, $taskGuid);
    }

    private function getRowsBySegmentId(string $tableName, int $segmentId): array
    {
        if (! $this->client->isAlive()) {
            return [];
        }

        try {
            return $this->client->select(
                'SELECT * FROM ' . $tableName . ' WHERE segmentId = :segmentId',
                [
                    'segmentId' => $segmentId,
                ]
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');

            return [];
        }
    }

    private function getRowsByTaskGuid(string $tableName, string $taskGuid): array
    {
        if (! $this->client->isAlive()) {
            return [];
        }

        try {
            return $this->client->select(
                'SELECT * FROM ' . $tableName . ' WHERE taskGuid = :taskGuid',
                [
                    'taskGuid' => trim($taskGuid, '{}'),
                ]
            );
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');

            return [];
        }
    }

    private function logError(string $errMsg): void
    {
        $this->logger->error('E1633', 'Statistics DB error: {msg}', [
            'msg' => $errMsg,
        ]);
    }

    private function trimBrackets(array $guids): array
    {
        return array_map(fn ($guid) => trim($guid, '{}'), $guids);
    }

    /**
     * Special filters from Advanced Filters window in a base version for task filtering AND segment statistic filtering
     * Currently supported: langResource, langResourceType, matchRateMin, matchRateMax,
     *  qualityScoreMin, qualityScoreMax
     */
    private function getCommonWhereSqlForAdvancedFilters(
        ?StatisticFilterDTO $statisticFilterDTO,
        array &$bind,
    ): string {
        $sqlWhere = '';
        if ($statisticFilterDTO === null) {
            return $sqlWhere;
        }

        if ($statisticFilterDTO->matchRateMin !== null) {
            $sqlWhere .= ' AND matchRate>=' . $statisticFilterDTO->matchRateMin;
        }
        if ($statisticFilterDTO->matchRateMax !== null) {
            $sqlWhere .= ' AND matchRate<=' . $statisticFilterDTO->matchRateMax;
        }
        if ($statisticFilterDTO->qualityScoreMin !== null) {
            $sqlWhere .= ' AND qualityScore>=' . $statisticFilterDTO->qualityScoreMin;
        }
        if ($statisticFilterDTO->qualityScoreMax !== null) {
            $sqlWhere .= ' AND qualityScore<=' . $statisticFilterDTO->qualityScoreMax;
        }
        if (! empty($statisticFilterDTO->langResource)) {
            $value = array_map(fn ($v) => (int) $v, $statisticFilterDTO->langResource);
            $sqlWhere .= ' AND langResId IN(' . implode(',', $value) . ')';
        }
        if (! empty($statisticFilterDTO->langResourceType)) {
            $bind['langResType'] = array_map(fn ($v) => strtolower($v), $statisticFilterDTO->langResourceType);
            $sqlWhere .= ' AND langResType IN(:langResType)';
        }

        return $sqlWhere;
    }

    private function getSegmentCount(string $sql, array $bind): int
    {
        $rows = $this->client->oneAssoc(
            'SELECT count(DISTINCT segmentId) as segmentCount FROM ' .
            SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' ' . $sql,
            $bind
        );

        return $rows['segmentCount'] ?? 0;
    }

    private function getPosteditingTimeByNonWorkflowStepAvg(
        string $sql,
        array $bind,
        ?StatisticFilterDTO $statisticFilter,
        string $nonWorkflowStepName,
    ): ?float {
        //if external step filter is given, "no workflow" sum makes no sense
        if (! empty($statisticFilter->workflowStep)) {
            return null;
        }

        $bind['wfStepName'] = $nonWorkflowStepName;

        $userSql = $this->getUserFilterSql($statisticFilter, $bind, 'p');

        //one AVG is sufficient, by above one workflow step select no segment duplications are present
        //COALESCE is needed, since we want the AVG over all affected segments,
        //  not only the ones with postediting times
        $rows = $this->client->oneAssoc(
            'SELECT AVG(COALESCE(segDuration.sumDuration, 0)) AS durationAvg
             FROM (
                 SELECT DISTINCT
                     s.taskGuid,
                     s.segmentId
                 FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' s ' .
                    $sql . ' AND s.workflowStepName = :wfStepName
             ) AS filteredSegments
             LEFT JOIN (
                 SELECT
                     p.taskGuid,
                     p.segmentId,
                     SUM(p.duration) AS sumDuration
                 FROM ' . SegmentHistoryAggregation::TABLE_NAME_POSTEDITING . ' p
                 WHERE p.workflowStepName = :wfStepName ' . $userSql . '
                 GROUP BY
                     p.taskGuid,
                     p.segmentId
             ) AS segDuration
                 ON segDuration.taskGuid = filteredSegments.taskGuid
                 AND segDuration.segmentId = filteredSegments.segmentId',
            $bind
        );

        return $rows['durationAvg'] ?? 0.0;
    }

    private function getLevenshteinDistanceByNonWorkflowStepAvg(
        string $sql,
        array $bind,
        ?StatisticFilterDTO $statisticFilter,
        string $workflowStepName,
    ): ?float {
        //if external step filter is given, "no workflow" or "workflow ended" sum makes no sense
        if (! empty($statisticFilter->workflowStep)) {
            return null;
        }

        $sql .= $this->getUserFilterSql($statisticFilter, $bind, 's');

        $sql .= ' AND workflowStepName = :wfStepName';
        $bind['wfStepName'] = $workflowStepName;

        //one AVG is sufficient, by above one workflow step select no segment duplications are present
        $rows = $this->client->oneAssoc(
            'SELECT AVG(levenshteinPrevious) as levAvg FROM '
                . SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' s ' . $sql,
            $bind
        );

        return $rows['levAvg'] ?? 0.0;
    }

    private function getPosteditingTimeInWorkflowStepAvg(
        string $sql,
        array $bind,
        ?StatisticFilterDTO $statisticFilter,
    ): float {
        $sqlStepFilterForPostediting = $this->getWorkflowStepFilterSqlForInWorkflowStep($statisticFilter, $bind, 'p');
        $sqlStepFilterForPostediting .= $this->getUserFilterSql($statisticFilter, $bind, 'p');

        //COALESCE is needed, since we want the AVG over all affected segments,
        // not only the ones with postediting times
        $rows = $this->client->oneAssoc(
            'SELECT AVG(segStepAverages.avgStepDuration) AS durationAvg
                 FROM (
                     SELECT
                         AVG(COALESCE(stepSums.stepDuration, 0)) AS avgStepDuration
                     FROM (
                         SELECT DISTINCT
                             s.taskGuid,
                             s.segmentId
                         FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' s ' . $sql . '
                     ) AS filteredSegments
                     LEFT JOIN (
                         SELECT
                             p.taskGuid,
                             p.segmentId,
                             p.workflowStepName,
                             SUM(p.duration) AS stepDuration
                         FROM ' . SegmentHistoryAggregation::TABLE_NAME_POSTEDITING . ' p
                         WHERE TRUE ' . $sqlStepFilterForPostediting . '
                         GROUP BY
                             p.taskGuid,
                             p.segmentId,
                             p.workflowStepName
                     ) AS stepSums
                         ON stepSums.taskGuid = filteredSegments.taskGuid
                         AND stepSums.segmentId = filteredSegments.segmentId
                     GROUP BY
                         filteredSegments.taskGuid,
                         filteredSegments.segmentId
                 ) AS segStepAverages',
            $bind
        );

        return $rows['durationAvg'] ?? 0.0;
    }

    private function getLevenshteinDistanceInWorkflowStepAvg(
        string $sql,
        array $bind,
        ?StatisticFilterDTO $statisticFilter,
    ): float {
        $sqlStepFilter = $this->getWorkflowStepFilterSqlForInWorkflowStep(
            $statisticFilter,
            $bind,
            's',
            true, // for that metric we consider only real steps
        );

        $sql .= $this->getUserFilterSql($statisticFilter, $bind, 's');

        $rows = $this->client->oneAssoc(
            'SELECT AVG(segAverages.avgStepLevenshtein) AS levAvg
             FROM (
                 SELECT AVG(s.levenshteinPrevious) AS avgStepLevenshtein
                 FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' s ' . $sql . $sqlStepFilter . '
                 GROUP BY
                     s.taskGuid,
                     s.segmentId
             ) AS segAverages',
            $bind
        );

        return $rows['levAvg'] ?? 0.0;
    }

    private function getPosteditingTimeInAllWorkflowStepsAvg(
        string $sql,
        array $bind,
        ?StatisticFilterDTO $statisticFilter,
    ): float {
        $sqlStepFilterForPostediting = $this->getWorkflowStepFilterSqlForInWorkflowStep($statisticFilter, $bind, 'p');
        $sqlStepFilterForPostediting .= $this->getUserFilterSql($statisticFilter, $bind, 'p');

        //COALESCE is needed, since we want the AVG over all affected segments,
        // not only the ones with postediting times
        $rows = $this->client->oneAssoc(
            'SELECT AVG(COALESCE(segDuration.sumDuration, 0)) AS durationAvg
                 FROM (
                     SELECT DISTINCT
                         s.taskGuid,
                         s.segmentId
                     FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' s ' . $sql . '
                 ) AS filteredSegments
                 LEFT JOIN (
                     SELECT
                         p.taskGuid,
                         p.segmentId,
                         SUM(p.duration) AS sumDuration
                     FROM ' . SegmentHistoryAggregation::TABLE_NAME_POSTEDITING . ' p
                     WHERE TRUE ' . $sqlStepFilterForPostediting . '
                     GROUP BY
                         p.taskGuid,
                         p.segmentId
                 ) AS segDuration
                     ON segDuration.taskGuid = filteredSegments.taskGuid
                     AND segDuration.segmentId = filteredSegments.segmentId',
            $bind
        );

        return $rows['durationAvg'] ?? 0.0;
    }

    private function getLevenshteinDistanceOriginalAvg(
        string $sql,
        array $bind,
        ?StatisticFilterDTO $statisticFilter,
    ): ?float {
        $hasUserFilter = ! empty($statisticFilter->userName);
        $hasWorkflowStepFilter = ! empty($statisticFilter->workflowStep);
        if ($hasUserFilter || $hasWorkflowStepFilter) {
            return null;
        }

        //one AVG is sufficient, by latestEntry one workflow step per segment is present
        // latestEntry = 1 must map to exactly one row per segment - what is by definition
        // If that latest row is "no workflow" or "workflow ended", the segment
        // shall count with 0 for this method
        $rows = $this->client->oneAssoc(
            'SELECT AVG(s.levenshteinOriginal) AS levAvg
            FROM ' . SegmentHistoryAggregation::TABLE_NAME_STATISTICS . ' s ' . $sql . ' AND s.latestEntry = 1',
            $bind
        );

        return $rows['levAvg'] ?? 0.0;
    }

    private function getUserFilterSql(
        ?StatisticFilterDTO $statisticFilter,
        array &$bind,
        string $tableAlias,
    ): string {
        if (empty($statisticFilter->userName)) {
            return '';
        }
        $bind['userGuids'] = $this->trimBrackets($statisticFilter->userName);

        return ' AND ' . $tableAlias . '.userGuid IN (:userGuids)';
    }

    private function getWorkflowStepFilterSqlForInWorkflowStep(
        ?StatisticFilterDTO $statisticFilter,
        array &$bind,
        string $tableAlias,
        bool $excludeInitial = false,
    ): string {
        if (! empty($statisticFilter?->workflowStep)) {
            $bind['wfStepNames'] = $statisticFilter->workflowStep;

            return ' AND ' . $tableAlias . '.workflowStepName IN (:wfStepNames)';
        }

        $bind['wfStepNoWorkflow'] = editor_Workflow_Default::STEP_NO_WORKFLOW;
        $bind['wfStepWorkflowEnded'] = editor_Workflow_Default::STEP_WORKFLOW_ENDED;

        if ($excludeInitial) {
            $bind['wfStepInitial'] = AggregateTaskStatistics::SYNTHETIC_INITIAL_STEP;

            return ' AND ' . $tableAlias
                . '.workflowStepName NOT IN (:wfStepNoWorkflow, :wfStepWorkflowEnded, :wfStepInitial)';
        }

        return ' AND ' . $tableAlias . '.workflowStepName NOT IN (:wfStepNoWorkflow, :wfStepWorkflowEnded)';
    }
}
