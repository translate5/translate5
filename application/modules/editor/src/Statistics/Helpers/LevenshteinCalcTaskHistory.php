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

use editor_Models_SegmentField;
use editor_Models_Workflow_Step;
use editor_Workflow_Default;
use MittagQI\Translate5\Repository\{SegmentHistoryDataRepository, SegmentHistoryRepository};
use MittagQI\Translate5\Segment\LevenshteinUTF8;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class LevenshteinCalcTaskHistory
{
    private LoggedJobsData $jobsLogged;

    private SegmentHistoryRepository $history;

    private SegmentHistoryDataRepository $historyData;

    private editor_Models_Workflow_Step $stepModel;

    private ?Zend_Db_Adapter_Abstract $db;

    public function __construct(
        private readonly string $sqlSince = '',
    ) {
        $this->jobsLogged = new LoggedJobsData($sqlSince, false);
        $this->history = new SegmentHistoryRepository();
        $this->historyData = new SegmentHistoryDataRepository();
        $this->stepModel = new editor_Models_Workflow_Step();
        $this->db = Zend_Db_Table::getDefaultAdapter();
    }

    public function calculate(string $taskGuid, string $workflowName): void
    {
        $results = $this->db->fetchAll(
            'SELECT id,workflowStep,userGuid,segmentId,workflowStepNr,levenshteinOriginal,levenshteinPrevious' .
            ',UNIX_TIMESTAMP(timestamp) AS timestamp FROM LEK_segment_history WHERE taskGuid="' . $taskGuid . '"' .
            ($this->sqlSince ?: '') .
            ' ORDER BY id'
        );

        if (empty($results)) {
            return;
        }

        $segmentLastEditorId = $this->db->fetchPairs(
            'SELECT id,userGuid FROM LEK_segments WHERE taskGuid="' . $taskGuid . '"'
        );
        $this->jobsLogged->initDataFor($taskGuid);

        $resultsDeduplicated = [];

        foreach ($results as $v) {
            // false can be returned here
            $v['segmentCurrentValue'] = (string) $this->db->fetchOne(
                'SELECT edited FROM LEK_segment_history_data WHERE segmentHistoryId=' . $v['id'],
            );

            if ($v['workflowStep'] === null || $v['workflowStep'] === editor_Workflow_Default::STEP_PM_CHECK) {
                $v['workflowStep'] = $this->jobsLogged->lookupWorkflowStep((int) $v['timestamp']);
                // '' for 'no_workflow'
            }

            $key = '';
            foreach (
                [
                    'userGuid',
                    'workflowStep',
                    'segmentId',
                ] as $column
            ) {
                $key .= $v[$column] . '|';
            }
            $resultsDeduplicated[$key] = $v;
        }

        $segmentIdsLastEditConsidered = $segmentIdsLastEditPending = [];
        foreach ($resultsDeduplicated as $v) {
            $v['segmentId'] = (int) $v['segmentId'];
            $isLatestEdit = false;
            if (isset($segmentIdsLastEditConsidered[$v['segmentId']])) {
                continue;
            } elseif ($segmentLastEditorId[$v['segmentId']] === $v['userGuid']) {
                $v1 = $this->db->fetchRow(
                    'SELECT workflowStep,levenshteinOriginal,levenshteinPrevious' .
                    ',UNIX_TIMESTAMP(timestamp) AS timestamp FROM LEK_segments WHERE id=' . $v['segmentId']
                );
                if ($v1['workflowStep'] === editor_Workflow_Default::STEP_PM_CHECK) {
                    $v1['workflowStep'] = $this->jobsLogged->lookupWorkflowStep((int) $v1['timestamp']);
                    if (empty($v1['workflowStep']) && ! empty($v['workflowStep'])) {
                        error_log('Cannot determine workflowStep for pmCheck of segmentId ' . $v['segmentId']);

                        continue;
                    }
                }
                if ($v1['workflowStep'] === $v['workflowStep']) {
                    // we override this entry with the latest values
                    $isLatestEdit = true;
                    $v = array_replace($v, $v1);
                    $v2 = $this->db->fetchRow(
                        'SELECT edited FROM LEK_segment_data WHERE segmentId=' . $v['segmentId'] . ' AND name="' . editor_Models_SegmentField::TYPE_TARGET . '"'
                    );
                    if (! empty($v2)) {
                        $v['segmentCurrentValue'] = $v2['edited'];
                    }
                    $segmentIdsLastEditConsidered[$v['segmentId']] = 1;
                    if (isset($segmentIdsLastEditPending[$v['segmentId']])) {
                        unset($segmentIdsLastEditPending[$v['segmentId']]);
                    }
                } else {
                    $segmentIdsLastEditPending[$v['segmentId']] = 1;
                }
            } else {
                $segmentIdsLastEditPending[$v['segmentId']] = 1;
            }

            if ($v['levenshteinOriginal'] > 0 || $v['levenshteinPrevious'] > 0) {
                continue;
            }

            $this->updateLevenshteinDistances(
                $v['segmentId'],
                $v['segmentCurrentValue'],
                (int) $v['workflowStepNr'],
                $workflowName,
                $taskGuid,
                $isLatestEdit ? 0 : (int) $v['id']
            );
        }

        // segments w/o history entries with userId from LEK_segments
        foreach (array_keys($segmentIdsLastEditPending) as $segmentId) {
            if (isset($segmentIdsLastEditConsidered[$segmentId])) {
                continue;
            }

            $v = $this->db->fetchRow(
                'SELECT workflowStepNr,UNIX_TIMESTAMP(timestamp) AS timestamp FROM LEK_segments WHERE id=' . $segmentId .
                ' AND levenshteinOriginal=0  AND levenshteinPrevious=0'
            );
            if (empty($v)) {
                continue;
            }

            $segmentCurrentValue = $this->db->fetchOne(
                'SELECT edited FROM LEK_segment_data WHERE segmentId=' . $segmentId . ' AND name="' . editor_Models_SegmentField::TYPE_TARGET . '"'
            );

            $this->updateLevenshteinDistances(
                $segmentId,
                $segmentCurrentValue,
                (int) $v['workflowStepNr'],
                $workflowName,
                $taskGuid
            );
        }
    }

    private function updateLevenshteinDistances(
        int $segmentId,
        string $segmentCurrentValue,
        int $workflowStepNr,
        string $workflowName,
        string $taskGuid,
        int $segmentHistoryId = 0,
    ): void {
        $segmentOriginalValue = $this->db->fetchOne(
            'SELECT original FROM LEK_segment_data WHERE segmentId=' . $segmentId . ' AND name="' . editor_Models_SegmentField::TYPE_TARGET . '"'
        );
        if ($segmentOriginalValue === '') {
            // load the last entry of the "translation" step (1st step of the workflow of "translation" type/role)
            $translatorStep = $this->stepModel->loadFirstByFilter($workflowName, [
                'position' => 1,
                'role' => editor_Workflow_Default::ROLE_TRANSLATOR,
            ]);
            if (empty($translatorStep)) {
                // error_log('No translation step for "'.$workflowName.'" workflow');
                return;
            }

            $segmentOriginalValue = $this->getLastHistoryEdited($segmentId, [
                'workflowStep = ?' => $translatorStep['name'],
            ]);
            if ($segmentOriginalValue === null) {
                // error_log('No history entry for segment #' . $segmentId.' within "'.$translatorStep['name'].'" step ['.$v['workflowStep'].']');
                return;
            }
        }

        // get last value from prev. workflow step if available
        $filter = [];
        if ($workflowStepNr === 1) {
            $filter = LoggedJobsData::getBeforeWorkflowStartFilter($taskGuid);
        }
        if (empty($filter)) {
            $filter = [
                'workflowStepNr < ?' => $workflowStepNr,
            ];
        }
        $segmentPrevStepValue = $this->getLastHistoryEdited($segmentId, $filter);

        if ($segmentPrevStepValue === null) {
            $segmentPrevStepValue = $segmentOriginalValue;
        }

        $levenshteinOriginal = LevenshteinUTF8::calcDistance(
            $segmentOriginalValue,
            $segmentCurrentValue
        );
        $levenshteinPrevious = LevenshteinUTF8::calcDistance(
            $segmentPrevStepValue,
            $segmentCurrentValue
        );

        if ($segmentHistoryId) {
            $sql = 'UPDATE LEK_segment_history SET levenshteinOriginal=' . $levenshteinOriginal .
                ',levenshteinPrevious=' . $levenshteinPrevious . ' WHERE id=' . $segmentHistoryId;
        } else {
            // avoid timestamp update
            $sql = 'UPDATE LEK_segments SET timestamp=timestamp,levenshteinOriginal=' . $levenshteinOriginal .
                ',levenshteinPrevious=' . $levenshteinPrevious . ' WHERE id=' . $segmentId;
        }
        $this->db->query($sql);
    }

    private function getLastHistoryEdited(int $segmentId, array $filter): ?string
    {
        $lastInHistory = $this->history->loadLatestForSegment(
            $segmentId,
            $filter
        );

        if (empty($lastInHistory)) {
            return null;
        }
        $historyDataEntry = $this->historyData->loadByHistoryId((int) $lastInHistory['id'], ['edited']);

        return ! empty($historyDataEntry) ? $historyDataEntry['edited'] : null;
    }
}
