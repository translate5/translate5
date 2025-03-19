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

use editor_Models_Segment_AutoStates;
use editor_Models_SegmentField;
use editor_Models_Task;
use editor_Workflow_Default;
use MittagQI\Translate5\Repository\{SegmentHistoryDataRepository, SegmentHistoryRepository};
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class AggregateTaskHistory
{
    private const TRANSLATION_STEP = 'translation';

    private LoggedJobsData $jobsLogged;

    private SegmentHistoryRepository $history;

    private SegmentHistoryDataRepository $historyData;

    private SegmentHistoryAggregation $aggregator;

    private ?Zend_Db_Adapter_Abstract $db;

    public function __construct(
        private readonly string $sqlSince = '',
    ) {
        $this->jobsLogged = new LoggedJobsData($sqlSince);
        $this->history = new SegmentHistoryRepository();
        $this->historyData = new SegmentHistoryDataRepository();
        $this->aggregator = SegmentHistoryAggregation::create();
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function aggregateData(string $taskGuid, string $workflowName): bool
    {
        $results = $this->db->fetchAll(
            'SELECT id,segmentId,editable,matchRate,matchRateType,UNIX_TIMESTAMP(created) AS timestamp' .
            ' FROM LEK_segment_history WHERE taskGuid="' . $taskGuid . '"' . ($this->sqlSince ?: '') .
            ' ORDER BY id'
        );

        if (empty($results)) {
            return true;
        }

        // skip overridden by autostate
        $segmentLastEditorId = $this->db->fetchPairs(
            'SELECT id,IF(autoStateId IN(' . editor_Models_Segment_AutoStates::REVIEWED_UNTOUCHED .
            '),"",userGuid) FROM LEK_segments WHERE taskGuid="' . $taskGuid . '"'
        );

        $this->jobsLogged->initDataFor($taskGuid);

        $resultsDeduplicated = [];
        foreach ($results as $v) {
            // entry is initiated by another user
            $v1 = $this->db->fetchRow(
                'SELECT userGuid,levenshteinOriginal,levenshteinPrevious,workflowStep' .
                ' FROM LEK_segment_history WHERE taskGuid="' . $taskGuid . '" AND segmentId=' . $v['segmentId'] .
                ' AND id>' . $v['id'] . ' ORDER BY id LIMIT 1'
            );
            if (empty($v1)) {
                $v1 = $this->db->fetchRow(
                    'SELECT userGuid,levenshteinOriginal,levenshteinPrevious,workflowStep,autoStateId' .
                    ' FROM LEK_segments WHERE id=' . $v['segmentId']
                );
                if ((int) $v1['autoStateId'] === editor_Models_Segment_AutoStates::REVIEWED_UNTOUCHED) {
                    $v1['userGuid'] = $this->db->fetchOne(
                        'SELECT userGuid FROM LEK_segment_history WHERE id=' . $v['id']
                    );
                }
                unset($v1['autoStateId']);
            }

            // we use initiating User/Workflow/Levenshtein values
            $v = array_replace($v, $v1);

            if ($v['workflowStep'] === null || $v['workflowStep'] === editor_Workflow_Default::STEP_PM_CHECK) {
                $v['workflowStep'] = $this->jobsLogged->lookupWorkflowStep((int) $v['timestamp']);
                if (empty($v['workflowStep'])) {
                    $v['workflowStep'] = editor_Workflow_Default::STEP_NO_WORKFLOW;
                }
            }

            $key = '';
            foreach (['userGuid', 'workflowStep', 'segmentId'] as $column) {
                $key .= $v[$column] . '|';
            }
            $resultsDeduplicated[$key] = $v;
        }

        $aggregation = $segmentIdsInHistory = $segmentIdsLastEditConsidered = $segmentIdsLastEditPending = [];
        $withPretranslation = false;
        $skipWorkflowEnded = $skipNoWorkflow = true;

        $workflowStartTimestamp = LoggedJobsData::getWorkflowStartTimestamp($taskGuid);
        $task = $this->db->fetchRow(
            'SELECT pmGuid,state,workflowStep FROM LEK_task WHERE taskGuid="' . $taskGuid . '"'
        );

        foreach ($resultsDeduplicated as $v) {
            $v['segmentId'] = (int) $v['segmentId'];
            if (! isset($segmentIdsInHistory[$v['workflowStep']])) {
                $segmentIdsInHistory[$v['workflowStep']] = [];
            }
            $segmentIdsInHistory[$v['workflowStep']][$v['segmentId']] = '';

            if (empty($segmentLastEditorId[$v['segmentId']])) {
                // if overridden by autostate
                $segmentLastEditorId[$v['segmentId']] = $this->db->fetchOne(
                    'SELECT userGuid FROM LEK_segment_history WHERE taskGuid="' . $taskGuid . '" AND segmentId=' . $v['segmentId'] .
                    ' ORDER BY id DESC LIMIT 1'
                );
            }

            $isEditable = 1; // until proved otherwise
            $duration = 0;

            if (isset($segmentIdsLastEditConsidered[$v['segmentId']])) {
                continue;
            } elseif ($segmentLastEditorId[$v['segmentId']] === $v['userGuid']) {
                $v1 = $this->db->fetchRow(
                    'SELECT workflowStep,levenshteinOriginal,levenshteinPrevious,matchRate,matchRateType,editable' .
                    ',UNIX_TIMESTAMP(timestamp) AS timestamp FROM LEK_segments WHERE id=' . $v['segmentId']
                );

                if (! empty($v1)) {
                    $isEditable = (int) $v1['editable'];

                    if ($v1['workflowStep'] === editor_Workflow_Default::STEP_PM_CHECK) {
                        $v1['workflowStep'] = $this->jobsLogged->lookupWorkflowStep((int) $v1['timestamp']);
                        if (empty($v1['workflowStep'])) {
                            // cannot determine workflowStep for pmCheck
                            $v1['workflowStep'] = editor_Workflow_Default::STEP_NO_WORKFLOW;
                        }
                    }

                    if ($v1['workflowStep'] === $v['workflowStep']) {
                        // we override this entry with the latest values
                        $v = array_replace($v, $v1);

                        $duration = (int) $this->db->fetchOne(
                            'SELECT duration FROM LEK_segment_data WHERE segmentId=' . $v['segmentId'] .
                            ' AND name="' . editor_Models_SegmentField::TYPE_TARGET . '"'
                        );

                        $segmentIdsLastEditConsidered[$v['segmentId']] = 1;
                        if (isset($segmentIdsLastEditPending[$v['segmentId']])) {
                            unset($segmentIdsLastEditPending[$v['segmentId']]);
                        }
                    } else {
                        $segmentIdsLastEditPending[$v['segmentId']] = 1;
                    }
                }
            } else {
                $segmentIdsLastEditPending[$v['segmentId']] = 1;
            }

            $v['levenshteinOriginal'] = (int) $v['levenshteinOriginal'];
            $v['levenshteinPrevious'] = (int) $v['levenshteinPrevious'];

            switch ($v['workflowStep']) {
                case editor_Workflow_Default::STEP_NO_WORKFLOW:
                    $filter = [
                        'workflowStep = ?' => editor_Workflow_Default::STEP_PM_CHECK,
                        'timestamp <= ?' => $workflowStartTimestamp,
                    ];
                    if ($v['levenshteinOriginal'] > 0) {
                        $skipNoWorkflow = false;
                    }

                    break;
                case editor_Workflow_Default::STEP_WORKFLOW_ENDED:

                    $filter = [
                        'workflowStep = ?' => editor_Workflow_Default::STEP_PM_CHECK,
                        'workflowStepNr = ?' => $task['workflowStep'],
                    ];
                    if ($v['levenshteinPrevious'] > 0) {
                        $skipWorkflowEnded = false;
                    }

                    break;
                default:
                    $filter = [
                        'workflowStep = ?' => $v['workflowStep'],
                    ];
                    if (! $withPretranslation && $v['workflowStep'] === self::TRANSLATION_STEP && $v['levenshteinOriginal'] > 0) {
                        $withPretranslation = true;
                    }
            }

            $duration += $this->historyData->getDurationSumByHistoryIds(
                $this->history->getHistoryIdsForSegment(
                    $v['segmentId'],
                    $v['userGuid'],
                    $filter
                )
            );

            if (! isset($aggregation[$v['workflowStep']])) {
                $aggregation[$v['workflowStep']] = [];
            }
            $aggregation[$v['workflowStep']][] = [
                $taskGuid,
                $v['userGuid'],
                $workflowName,
                $v['workflowStep'],
                $v['segmentId'],
                $duration,
                $v['levenshteinOriginal'],
                $v['levenshteinPrevious'],
                (int) $v['matchRate'],
                $v['matchRateType'],
                $this->getLangResId($v['segmentId']),
                $isEditable,
            ];
        }

        // segments w/o history entries with userId from LEK_segments
        foreach (array_keys($segmentIdsLastEditPending) as $segmentId) {
            if (isset($segmentIdsLastEditConsidered[$segmentId])) {
                continue;
            }

            $v = $this->db->fetchRow(
                'SELECT userGuid,workflowStep,levenshteinOriginal,levenshteinPrevious,matchRate,matchRateType' .
                ',editable,UNIX_TIMESTAMP(timestamp) AS timestamp FROM LEK_segments WHERE id=' . $segmentId
            );
            if (empty($v)) {
                continue;
            }

            if ($v['workflowStep'] === null) {
                $v['workflowStep'] = editor_Workflow_Default::STEP_NO_WORKFLOW;
            } elseif ($v['workflowStep'] === editor_Workflow_Default::STEP_PM_CHECK) {
                $v['workflowStep'] = $this->jobsLogged->lookupWorkflowStep((int) $v['timestamp']);
                if (empty($v['workflowStep'])) {
                    $v['workflowStep'] = editor_Workflow_Default::STEP_NO_WORKFLOW;
                }
            }

            $v['levenshteinOriginal'] = (int) $v['levenshteinOriginal'];
            $v['levenshteinPrevious'] = (int) $v['levenshteinPrevious'];

            switch ($v['workflowStep']) {
                case editor_Workflow_Default::STEP_NO_WORKFLOW:
                    if ($v['levenshteinOriginal'] > 0) {
                        $skipNoWorkflow = false;
                    }

                    break;
                case editor_Workflow_Default::STEP_WORKFLOW_ENDED:
                    if ($v['levenshteinPrevious'] > 0) {
                        $skipWorkflowEnded = false;
                    }

                    break;
                default:
                    if (! $withPretranslation && $v['workflowStep'] === self::TRANSLATION_STEP && $v['levenshteinOriginal'] > 0) {
                        $withPretranslation = true;
                    }
            }

            // no need to look into history ?
            $duration = (int) $this->db->fetchOne(
                'SELECT duration FROM LEK_segment_data WHERE segmentId=' . $segmentId .
                ' AND name="' . editor_Models_SegmentField::TYPE_TARGET . '"'
            );

            if (! isset($aggregation[$v['workflowStep']])) {
                $aggregation[$v['workflowStep']] = [];
            }
            $aggregation[$v['workflowStep']][] = [
                $taskGuid,
                $v['userGuid'],
                $workflowName,
                $v['workflowStep'],
                $segmentId,
                $duration,
                $v['levenshteinOriginal'],
                $v['levenshteinPrevious'],
                (int) $v['matchRate'],
                $v['matchRateType'],
                $this->getLangResId($segmentId),
                (int) $v['editable'],
            ];
        }

        // remove unnecessary entries
        foreach (array_keys($aggregation) as $workflowStep) {
            foreach ($aggregation[$workflowStep] as $idx => $row) {
                $skip = false;
                switch ($row[3]) {
                    case self::TRANSLATION_STEP:
                        $skip = ! $withPretranslation;

                        break;
                    case editor_Workflow_Default::STEP_WORKFLOW_ENDED:
                        $skip = $skipWorkflowEnded;

                        break;
                    case editor_Workflow_Default::STEP_NO_WORKFLOW:
                        $skip = $skipNoWorkflow;
                }
                if ($skip) {
                    unset($aggregation[$workflowStep][$idx]);
                }
            }
        }

        // aggregate unmodified segments within those which have job log entry 'finished'

        $jobsFinished = $this->jobsLogged->getFinishedJobs();
        // if there are any open jobs for a finished step, we treat step as not finished
        foreach (array_keys($jobsFinished) as $workflowStep) {
            $jobsOpen = (int) $this->db->fetchOne(
                'SELECT COUNT(*) FROM LEK_taskUserAssoc WHERE taskGuid="' . $taskGuid .
                '" AND workflowStepName="' . $workflowStep . '" AND state="' . editor_Models_Task::STATE_OPEN . '"'
            );
            if ($jobsOpen > 0) {
                unset($jobsFinished[$workflowStep]);
            }
        }

        if ($workflowStartTimestamp) {
            // any pre-workflow changes ?
            $workflowStep = editor_Workflow_Default::STEP_NO_WORKFLOW;
            if (! empty($segmentIdsInHistory[$workflowStep])) {
                $jobsFinished = [
                    $workflowStep => $task['pmGuid'],
                ] + $jobsFinished;
            }
        }

        $unmodifiedAggregation = [];
        if (! empty($jobsFinished)) {
            if ($task['state'] === editor_Models_Task::STATE_END) {
                // any after-workflow changes ?
                $workflowStep = editor_Workflow_Default::STEP_WORKFLOW_ENDED;
                if (! empty($segmentIdsInHistory[$workflowStep])) {
                    $jobsFinished[$workflowStep] = $task['pmGuid'];
                }
            }

            $workflowStepsOrdered = array_keys($jobsFinished);
            foreach ($workflowStepsOrdered as $workflowStepIdx => $workflowStep) {
                if (! $withPretranslation && $workflowStep === self::TRANSLATION_STEP) {
                    continue;
                }

                $userGuid = self::stepWithinWorkflow(
                    $workflowStep
                ) ? $jobsFinished[$workflowStep] : $task['pmGuid'];
                $segmentIds = $segmentIdsInHistory[$workflowStep] ?? [];

                $results = $this->db->fetchAll(
                    'SELECT id,taskGuid,matchRate,matchRateType,editable,timestamp' .
                    ' FROM LEK_segments WHERE taskGuid="' . $taskGuid . '"' .
                    (empty($segmentIds) ? '' : ' AND id NOT IN (' . implode(
                        ',',
                        array_keys($segmentIds)
                    ) . ')')
                );

                if (! empty($results)) {
                    foreach ($results as $v) {
                        $segmentId = (int) $v['id'];
                        // reset to avoid copying from beforeWorkflow edits, <= is needed for api tests to pass
                        if ($workflowStepIdx == 0 || in_array(
                            $workflowStep,
                            [editor_Workflow_Default::STEP_NO_WORKFLOW, self::TRANSLATION_STEP]
                        ) || ($workflowStartTimestamp && $v['timestamp'] <= $workflowStartTimestamp)) {
                            $v['levenshteinOriginal'] = 0;
                        } else {
                            $v['levenshteinOriginal'] = false;
                            $prevWorkflowStepIdx = $workflowStepIdx - 1;
                            while ($v['levenshteinOriginal'] === false && $prevWorkflowStepIdx >= 0) {
                                // find out previous value
                                $prevWorkflowStep = $workflowStepsOrdered[$prevWorkflowStepIdx];
                                $v['levenshteinOriginal'] = $this->db->fetchOne(
                                    'SELECT levenshteinOriginal FROM LEK_segments WHERE id=' . $segmentId .
                                    ' AND workflowStep="' . $prevWorkflowStep . '"'
                                );

                                if ($v['levenshteinOriginal'] === false) {
                                    $v['levenshteinOriginal'] = $this->db->fetchOne(
                                        'SELECT levenshteinOriginal FROM LEK_segment_history WHERE taskGuid="' . $taskGuid .
                                        '" AND segmentId=' . $segmentId . ' AND workflowStep="' . $prevWorkflowStep .
                                        '" ORDER BY id DESC LIMIT 1'
                                    );
                                }
                                $prevWorkflowStepIdx--;
                            }
                        }

                        if (! isset($unmodifiedAggregation[$workflowStep])) {
                            $unmodifiedAggregation[$workflowStep] = [];
                        }
                        $unmodifiedAggregation[$workflowStep][] = [
                            $v['taskGuid'],
                            $userGuid,
                            $workflowName,
                            $workflowStep,
                            $segmentId,
                            0,
                            (int) $v['levenshteinOriginal'],
                            0,
                            (int) $v['matchRate'],
                            $v['matchRateType'],
                            $this->getLangResId($segmentId),
                            (int) $v['editable'],
                        ];
                    }
                }
            }
        }

        return $this->aggregateHistoryByWorkflowSteps($aggregation, $unmodifiedAggregation);
    }

    public function removeData(string $taskGuid): void
    {
        $this->aggregator->removeTaskData($taskGuid);
    }

    private function aggregateHistoryByWorkflowSteps(array $aggregation, array $unmodifiedAggregation): bool
    {
        if (empty($aggregation)) {
            return true;
        }

        $batchSize = 10_000;

        $num = 0;
        foreach (array_keys($aggregation) as $workflowStep) {
            if (isset($unmodifiedAggregation[$workflowStep])) {
                foreach ($unmodifiedAggregation[$workflowStep] as $row) {
                    $this->aggregator->upsertBuffered(...$row);
                    $num++;
                    if ($num === $batchSize) {
                        $num = 0;
                        if (! $this->aggregator->flushUpserts()) {
                            return false;
                        }
                    }
                }
            }

            foreach ($aggregation[$workflowStep] as $row) {
                $this->aggregator->upsertBuffered(...$row);
                $num++;
                if ($num === $batchSize) {
                    $num = 0;
                    if (! $this->aggregator->flushUpserts()) {
                        return false;
                    }
                }
            }
        }
        if ($num > 0 && ! $this->aggregator->flushUpserts()) {
            return false;
        }

        return true;
    }

    private static function stepWithinWorkflow(string $step): bool
    {
        return ! in_array(
            $step,
            [editor_Workflow_Default::STEP_NO_WORKFLOW, editor_Workflow_Default::STEP_WORKFLOW_ENDED]
        );
    }

    private function getLangResId(int $segmentId): int
    {
        static $langResUuidToId;
        if (! isset($langResUuidToId)) {
            $langResUuidToId = $this->db->fetchPairs('SELECT langResUuid,id FROM LEK_languageresources');
        }
        $langResUuid = $this->db->fetchOne(
            'SELECT preTransLangResUuid FROM LEK_segments_meta WHERE segmentId=' . $segmentId
        );
        if (! empty($langResUuid) && isset($langResUuidToId[$langResUuid])) {
            return (int) $langResUuidToId[$langResUuid];
        }

        return 0;
    }
}
