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

use editor_Workflow_Default;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Dto\AggregationFilter;
use Throwable;
use ZfExtended_Logger;

/**
 * Repository for Segments History Aggregated Data
 */
class SegmentHistoryAggregationRepository
{
    public const LEVENSHTEIN_PRECISION = 5;

    public function __construct(
        private readonly AbstractStatisticsDB $client,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('statistics'),
            \Zend_Registry::get('logger')->cloneMe('core.db.statistics')
        );
    }

    /**
     * @param AggregationFilter[] $filters
     */
    public function getStatistics(array $taskGuids, array $filters = []): array
    {
        if (! $this->client->isAlive()) {
            return [];
        }
        $calcLevenshteinOriginal = true;
        $bind = [];
        $bind['taskGuids'] = self::trimBrackets($taskGuids);

        /* FINAL keyword in the query below is needed for Clickhouse's ReplacingMergeTree ENGINE only
        and causes no issues for SQLite/MariaDB */
        $sql = ' FINAL WHERE taskGuid IN (:taskGuids) AND editable=1' . self::getExtraFiltersSQLWhere($filters, $bind);
        $sqlWorkflowStep = ' AND workflowStepName NOT IN("' . editor_Workflow_Default::STEP_NO_WORKFLOW . '","' . editor_Workflow_Default::STEP_WORKFLOW_ENDED . '")';
        $lastEditFlag = false;

        foreach ($filters as $filter) {
            $validFilter = ! empty($filter->isNative)
                && ! empty($filter->value)
                && is_array($filter->value);

            if (! $validFilter) {
                continue;
            }

            switch ($filter->property) {
                case 'workflow':
                    $sql .= ' AND workflowName IN (:workflows)';
                    $bind['workflows'] = self::trimBrackets($filter->value);

                    break;
                case 'userName':
                    // take into account segments only if user was last who edited the segment
                    $lastEditFlag = true;
                    $sql .= ' AND userGuid IN (:userGuids)';
                    $bind['userGuids'] = self::trimBrackets($filter->value);
                    $calcLevenshteinOriginal = false;

                    break;
                    // grid filter and adv. filter
                case 'workflowStepName':
                case 'workflowStep':
                    // override NOT IN ()
                    $sqlWorkflowStep = ' AND workflowStepName IN (:wfStepNames)';
                    $bind['wfStepNames'] = $filter->value;
                    $calcLevenshteinOriginal = false;
            }
        }

        $durationGroupByAsColumn = [
            'workflowStepName' => 'durationAvg',
            'workflowName' => 'durationTotal',
        ];
        $levenshteinGroupByAsColumn = [
            'workflowStepName' => 'levenshteinPrevious',
        ];

        if ($calcLevenshteinOriginal) {
            $levenshteinGroupByAsColumn['workflowName'] = 'levenshteinOriginal';
        }

        try {
            $rows = [];
            foreach ($durationGroupByAsColumn as $groupBy => $durationColumn) {
                // we don't save entries with 0 duration in this table
                $rows[] = $this->client->oneAssoc(
                    'SELECT ROUND(AVG(t.timeSum)/1000,2) AS ' . $durationColumn .
                    ' FROM (SELECT SUM(duration) AS timeSum' .
                    ' FROM ' . SegmentHistoryAggregation::TABLE_NAME . $sql . $sqlWorkflowStep .
                    ' GROUP BY segmentId,' . $groupBy . ') AS t',
                    $bind
                );

                if (isset($levenshteinGroupByAsColumn[$groupBy])) {
                    $levenshteinColumn = $levenshteinGroupByAsColumn[$groupBy];
                    $rows[] = $this->client->oneAssoc(
                        'SELECT ROUND(AVG(t.levAvg),' . self::LEVENSHTEIN_PRECISION . ') AS ' . $levenshteinColumn . 'Avg' .
                        ' FROM (SELECT AVG(' . $levenshteinColumn . ') AS levAvg' .
                        ' FROM ' . SegmentHistoryAggregation::TABLE_NAME_LEV . $sql . $sqlWorkflowStep .
                        ($lastEditFlag || $levenshteinColumn === 'levenshteinOriginal' ? ' AND lastEdit=1' : '') .
                        ' GROUP BY segmentId,' . $groupBy . ') AS t',
                        $bind
                    );
                }
            }

            foreach (
                [
                    'Start' => 'Original',
                    'End' => 'Previous',
                ] as $key => $value
            ) {
                $workflowStep = ($key === 'Start' ? editor_Workflow_Default::STEP_NO_WORKFLOW : editor_Workflow_Default::STEP_WORKFLOW_ENDED);

                $levenshteinColumn = 'levenshtein' . $key . 'Avg';
                $rows[] = $this->client->oneAssoc(
                    'SELECT ROUND(AVG(t.levAvg),' . self::LEVENSHTEIN_PRECISION . ') AS ' . $levenshteinColumn .
                    ' FROM (SELECT AVG(levenshtein' . $value . ') AS levAvg FROM ' . SegmentHistoryAggregation::TABLE_NAME_LEV . $sql .
                    (' AND workflowStepName="' . $workflowStep . '"') . ' GROUP BY segmentId) AS t',
                    $bind
                );

                $durationColumn = 'duration' . $key . 'Avg';
                $rows[] = $this->client->oneAssoc(
                    'SELECT ROUND(AVG(t.timeSum)/1000,2) AS ' . $durationColumn .
                    ' FROM (SELECT SUM(duration) AS timeSum FROM ' . SegmentHistoryAggregation::TABLE_NAME . $sql .
                    (' AND workflowStepName="' . $workflowStep . '"') . ' GROUP BY segmentId) AS t',
                    $bind
                );
            }

            $res = array_merge(...$rows);
            if (! $calcLevenshteinOriginal) {
                $res['levenshteinOriginalAvg'] = 0;
            }
        } catch (Throwable $e) {
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');

            return [];
        }

        return $res;
    }

    /**
     * @param AggregationFilter[] $filters
     */
    public function getFilteredTaskIds(
        array $taskGuids,
        array $filters,
    ): array {
        if (! $this->client->isAlive()) {
            return [];
        }

        $bind = [
            'taskGuids' => self::trimBrackets($taskGuids),
        ];
        $sqlWhere = self::getExtraFiltersSQLWhere($filters, $bind);

        try {
            $res = $this->client->select(
                'SELECT DISTINCT taskGuid FROM ' . SegmentHistoryAggregation::TABLE_NAME .
                ' WHERE taskGuid IN (:taskGuids)' . $sqlWhere,
                $bind
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

    public function getOrphanedTaskIds(array $taskGuids): array
    {
        if (! $this->client->isAlive()) {
            return [];
        }

        try {
            $res = $this->client->select(
                'SELECT DISTINCT taskGuid FROM ' . SegmentHistoryAggregation::TABLE_NAME .
                ' WHERE taskGuid NOT IN (:taskGuids)',
                [
                    'taskGuids' => self::trimBrackets($taskGuids),
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

    private static function trimBrackets(array $guids): array
    {
        return array_map(fn ($guid) => trim($guid, '{}'), $guids);
    }

    /**
     * Special filters from Advanced Filters window
     * Currently supported: langResource, langResourceType, matchRateMin, matchRateMax
     * @param AggregationFilter[] $filters
     */
    private static function getExtraFiltersSQLWhere(
        array $filters,
        array &$bind,
    ): string {
        $sqlWhere = '';
        foreach ($filters as $filter) {
            if (! $filter->isNative) {
                if ($filter->property == 'matchRateMin') {
                    $sqlWhere .= ' AND matchRate>=' . (int) $filter->value;
                } elseif ($filter->property == 'matchRateMax') {
                    $sqlWhere .= ' AND matchRate<=' . (int) $filter->value;
                } elseif ($filter->property == 'langResource') {
                    if (! empty($filter->value) && is_array($filter->value)) {
                        $value = array_map(fn ($v) => (int) $v, $filter->value);
                        $sqlWhere .= ' AND langResId IN(' . implode(',', $value) . ')';
                    }
                } elseif ($filter->property == 'langResourceType') {
                    if (! empty($filter->value) && is_array($filter->value)) {
                        $bind['langResType'] = array_map(fn ($v) => strtolower($v), $filter->value);
                        $sqlWhere .= ' AND langResType IN(:langResType)';
                    }
                }
            }
        }

        return $sqlWhere;
    }

    private function logError(string $errMsg): void
    {
        $this->logger->error('E1633', 'Statistics DB error: {msg}', [
            'msg' => $errMsg,
        ]);
    }
}
