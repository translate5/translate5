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
use editor_Models_Task;
use editor_Workflow_Default;
use MittagQI\Translate5\Segment\UpdateSegmentStatistics;
use Zend_Db_Table;
use Zend_Registry;

class AggregateUnmodifiedSegments
{
    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \Zend_Exception
     */
    public static function aggregate(editor_Models_Task $task, string $workflowStepName, string $userGuid): void
    {
        if (! Zend_Registry::get('config')->resources->db->statistics?->enabled) {
            return;
        }

        $db = Zend_Db_Table::getDefaultAdapter();

        if (in_array(
            $workflowStepName,
            [editor_Workflow_Default::STEP_NO_WORKFLOW, editor_Workflow_Default::STEP_WORKFLOW_ENDED]
        )) {
            $atLeastOneSegmentChanged = $db->fetchOne(
                'SELECT id FROM LEK_segments WHERE taskGuid=? AND ' .
                ($workflowStepName === editor_Workflow_Default::STEP_WORKFLOW_ENDED ? 'workflowStepNr=' . $task->getWorkflowStep() : 'workflowStep IS NOT NULL') .
                ' LIMIT 1',
                $task->getTaskGuid()
            );
            if (empty($atLeastOneSegmentChanged)) {
                return;
            }
        }

        $workflowStepNo = (int) $task->getWorkflowStep();
        // we need prev. workflowStepNo
        $workflowEnded = ($workflowStepName === editor_Workflow_Default::STEP_WORKFLOW_ENDED);
        if (! $workflowEnded) {
            $workflowStepNo--;
        }

        $sqlWhere = '';
        if ($workflowStepName !== editor_Workflow_Default::STEP_NO_WORKFLOW) {
            if ($workflowStepNo === 1) {
                $sqlWhere = self::getStepOneSqlWhere($task->getTaskGuid());
                if (! empty($sqlWhere)) {
                    $sqlWhere = ' OR (1' . $sqlWhere . ')';
                }
            }
            if (empty($sqlWhere)) {
                $sqlWhere .= ' OR (workflowStepNr<' . $workflowStepNo .
                    ($workflowEnded ? '' : ' AND workflowStep<>"' . $workflowStepName . '"') .
                    ')';
            }
        }

        // get unmodified segments within the last workflowStep
        $unmodifiedSegmentIds = $db->fetchCol(
            'SELECT id FROM LEK_segments WHERE taskGuid=? AND (workflowStep IS NULL' . $sqlWhere . ')',
            $task->getTaskGuid()
        );

        if (empty($unmodifiedSegmentIds)) {
            return;
        }

        // for compatibility's sake with older tasks
        $workflowStartTimestamp = LoggedJobsData::getWorkflowStartTimestamp($task->getTaskGuid());

        $workflowName = $task->getWorkflow();
        $segment = new editor_Models_Segment();
        $updateStatistics = UpdateSegmentStatistics::create();

        foreach ($unmodifiedSegmentIds as $segmentId) {
            $segment->load($segmentId);
            // override for aggregation
            $segment->setUserGuid($userGuid);
            $segment->setWorkflowStep($workflowStepName);
            $segment->setLevenshteinPrevious(0);
            // reset to avoid copying from beforeWorkflow edits, <= is needed for api tests to pass
            $editedInStep = $segment->getEditedInStep();
            if (empty($editedInStep)) {
                // make sure older tasks are handled correctly
                $beforeWorkflowStarted = ($workflowStartTimestamp && $segment->getTimestamp() <= $workflowStartTimestamp);
            } else {
                $beforeWorkflowStarted = ($editedInStep === editor_Workflow_Default::STEP_NO_WORKFLOW);
            }
            if ($workflowStepNo === 1 || $beforeWorkflowStarted) {
                $segment->setLevenshteinOriginal(0);
            }
            $updateStatistics->updateFor($segment, $workflowName, $workflowStepNo);
        }
    }

    private static function getStepOneSqlWhere(string $taskGuid): string
    {
        $filter = LoggedJobsData::getBeforeWorkflowStartFilter($taskGuid);
        if (empty($filter)) {
            return '';
        }
        $sqlWhere = '';
        foreach ($filter as $condition => $value) {
            $sqlWhere .= ' AND ' . str_replace(
                '?',
                is_int($value) ? (string) $value : '"' . $value . '"',
                $condition
            );
        }

        return $sqlWhere;
    }
}
