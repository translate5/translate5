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

use editor_Models_Segment_MatchRateType;
use editor_Workflow_Default;
use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use Throwable;
use Zend_Registry;
use ZfExtended_Logger;

/**
 * DB Client Wrapper for Segments History Aggregated Data
 */
class SegmentHistoryAggregation
{
    public const TABLE_NAME = 'LEK_segment_history_aggregation';

    public const TABLE_NAME_LEV = 'LEK_segment_history_aggregation_lev';

    private array $buffer = [];

    public function __construct(
        private readonly ?AbstractStatisticsDB $client,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('statistics'),
            Zend_Registry::get('logger')->cloneMe('core.db.statistics')
        );
    }

    public function upsertBuffered(
        string $taskGuid,
        string $userGuid,
        string $wfName,
        string $wfStepName,
        int $segmentId,
        int $duration,
        int $levenshteinOriginal,
        int $levenshteinPrevious,
        int $matchRate,
        string $matchRateType,
        int $langResId,
        int $isEditable,
    ): void {
        // trim brackets
        $taskGuid = trim($taskGuid, '{}');
        $userGuid = trim($userGuid, '{}');

        $this->buffer[] = [
            $taskGuid,
            $userGuid,
            $wfName,
            $wfStepName,
            $segmentId,
            $duration,
            $levenshteinOriginal,
            $levenshteinPrevious,
            $matchRate,
            editor_Models_Segment_MatchRateType::getLangResourceType($matchRateType),
            $langResId,
            $isEditable,
        ];
    }

    public function flushUpserts(): bool
    {
        if (empty($this->buffer)) {
            return false;
        }

        try {
            $buffer = [];
            foreach ($this->buffer as $row) {
                if ($row[5] === 0) { // duration is 0
                    continue;
                }
                array_splice($row, 6, 2);
                $buffer[] = $row;
            }

            $this->client->upsert(
                self::TABLE_NAME,
                $buffer,
                [
                    'taskGuid',
                    'userGuid',
                    'workflowName',
                    'workflowStepName',
                    'segmentId',
                    'duration',
                    //'levenshteinOriginal',
                    //'levenshteinPrevious',
                    'matchRate',
                    'langResType',
                    'langResId',
                    'editable',
                ]
            );

            $notInWorkflow = [
                editor_Workflow_Default::STEP_NO_WORKFLOW => 1,
                editor_Workflow_Default::STEP_WORKFLOW_ENDED => 1,
            ];
            $buffer = $segmentIds = $lastEdit = [];
            $lastEditIdx = count($this->buffer[0]) - 1;
            foreach ($this->buffer as $idx => $row) {
                $segmentId = (int) $row[4];
                $stepWithinWorkflow = ! isset($notInWorkflow[$row[3]]);
                if ($stepWithinWorkflow) {
                    if (isset($lastEdit[$segmentId])) {
                        // set lastEdit=0
                        $prevIdx = $lastEdit[$segmentId];
                        $buffer[$prevIdx][$lastEditIdx] = 0;
                    }
                    $lastEdit[$segmentId] = $idx;
                    $segmentIds[] = $segmentId;
                }
                array_splice($row, 5, 1);
                $row[] = $stepWithinWorkflow ? 1 : 0; // add lastEdit=1 if within workflow
                $buffer[] = $row;
            }

            if (! empty($segmentIds)) {
                // reset prev. lastEdit flags
                // we update segments per one task in batch import, so this is perfect for now
                $this->client->query(
                    'UPDATE ' . self::TABLE_NAME_LEV . ' SET lastEdit=0 WHERE taskGuid=:taskGuid' .
                    ' AND segmentId IN (:segmentIds)',
                    [
                        'taskGuid' => $this->buffer[0][0],
                        'segmentIds' => $segmentIds,
                    ]
                );
            }

            $this->client->upsert(
                self::TABLE_NAME_LEV,
                $buffer,
                [
                    'taskGuid',
                    'userGuid',
                    'workflowName',
                    'workflowStepName',
                    'segmentId',
                    //'duration',
                    'levenshteinOriginal',
                    'levenshteinPrevious',
                    'matchRate',
                    'langResType',
                    'langResId',
                    'editable',
                    'lastEdit',
                ]
            );
        } catch (Throwable $e) {
            $this->buffer = [];
            $this->logError($e->getMessage() . ' [' . __FUNCTION__ . ']');

            return false;
        }

        $this->buffer = [];

        return true;
    }

    public function upsert(
        string $taskGuid,
        string $userGuid,
        string $wfName,
        string $wfStepName,
        int $segmentId,
        int $duration,
        int $levenshteinOriginal,
        int $levenshteinPrevious,
        int $matchRate,
        string $matchRateType,
        int $langResId,
        int $isEditable,
    ): bool {
        $this->upsertBuffered(
            $taskGuid,
            $userGuid,
            $wfName,
            $wfStepName,
            $segmentId,
            $duration,
            $levenshteinOriginal,
            $levenshteinPrevious,
            $matchRate,
            $matchRateType,
            $langResId,
            $isEditable
        );

        return $this->flushUpserts();
    }

    public function updateEditable(string $taskGuid, int $segmentId, int $editable): void
    {
        $bind = [
            'taskGuid' => trim($taskGuid, '{}'),
        ];

        try {
            $this->client->query(
                'UPDATE ' . self::TABLE_NAME . ' SET editable=' . $editable .
                ' WHERE taskGuid = :taskGuid AND segmentId=' . $segmentId,
                $bind
            );
            $this->client->query(
                'UPDATE ' . self::TABLE_NAME_LEV . ' SET editable=' . $editable .
                ' WHERE taskGuid = :taskGuid AND segmentId=' . $segmentId,
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
                'DELETE FROM ' . self::TABLE_NAME . ' WHERE taskGuid = :taskGuid',
                $bind
            );
            $this->client->query(
                'DELETE FROM ' . self::TABLE_NAME_LEV . ' WHERE taskGuid = :taskGuid',
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
}
