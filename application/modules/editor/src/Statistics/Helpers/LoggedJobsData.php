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
use editor_Workflow_Default;
use Zend_Db_Table;

class LoggedJobsData
{
    private array $jobsHistory = [];

    private array $jobsFinished = [];

    private const bulkChunkSize = 10_000;

    public function __construct(
        private readonly string $sqlWhere,
        private readonly bool $includeFinishedJobs = true,
    ) {
    }

    public function getFinishedJobs(): array
    {
        return $this->jobsFinished;
    }

    /* Real workflow step lookup for "pmCheck" entries; if not found, return '' */
    public function lookupWorkflowStep(int $time): string
    {
        $stepName = '';
        if (! empty($this->jobsHistory)) {
            foreach ($this->jobsHistory as $created => $workflowStep) {
                // as some history entries are added immediately on "workflowEnded" and should belong to after workflow
                if ($created <= $time) {
                    $stepName = $workflowStep;
                } else {
                    break;
                }
            }
        }

        return $stepName;
    }

    public function initDataFor(string $taskGuid): void
    {
        $this->jobsHistory = $this->jobsFinished = [];
        $db = Zend_Db_Table::getDefaultAdapter();

        $lastID = 0;
        do {
            // process log in chunks
            $result = $db->fetchAll(
                'SELECT id,UNIX_TIMESTAMP(created) AS created,extra FROM LEK_task_log WHERE domain="editor.workflow" AND level=8' .
                ' AND eventCode IN("E1013"' . ($this->includeFinishedJobs ? ',"E1011","E1012"' : '') . ')' . // consider adding as 3rd column to the existing index on (domain, level)
                ' AND (' .
                'message LIKE \'setNextStep: workflow next step "' . editor_Workflow_Default::STEP_WORKFLOW_ENDED . '"%\' OR ' .
                'message LIKE "recalculate workflow to step %"' .
                ($this->includeFinishedJobs ? ' OR message LIKE "% to ' . editor_Workflow_Default::STATE_FINISH . '"' : '') .
                ')' .
                ($taskGuid ? ' AND taskGuid = "' . $taskGuid . '"' : '') .
                $this->sqlWhere . ' AND id > ' . $lastID . ' ORDER BY id LIMIT ' . self::bulkChunkSize,
            );

            foreach ($result as $v) {
                $lastID = $v['id'];
                $created = (int) $v['created'];
                $v = json_decode($v['extra'], true);
                if ($this->includeFinishedJobs && ! empty($v['newState']) && is_array(
                    $v['tua']
                )) { // $v['newState'] === 'finished'
                    // if we have 'null' workflowStepName
                    $workflowStepName = empty($v['tua']['workflowStepName']) ? '' : $v['tua']['workflowStepName'];
                    $this->jobsFinished[$workflowStepName] = $v['tua']['userGuid'];
                } elseif (! empty($v['step']) || ! empty($v['newStep'])) { // recalculate workflow OR workflowEnded
                    if (isset($v['newStep']) && $v['newStep'] === editor_Workflow_Default::STEP_WORKFLOW_ENDED) {
                        $workflowStepName = editor_Workflow_Default::STEP_WORKFLOW_ENDED;
                    } else {
                        $workflowStepName = $v['step'] !== editor_Workflow_Default::STEP_NO_WORKFLOW ? $v['step'] : '';
                    }
                    $this->jobsHistory[$created] = $workflowStepName;
                }
            }
        } while (count($result) === self::bulkChunkSize);
        // filter out "workflowEnded" if it is not the last one (duplicates appear in api tests)
        $lastJobIdx = count($this->jobsHistory) - 1;
        $jobIdx = 0;
        foreach ($this->jobsHistory as $created => $workflowStepName) {
            if ($workflowStepName === editor_Workflow_Default::STEP_WORKFLOW_ENDED && $jobIdx < $lastJobIdx) {
                unset($this->jobsHistory[$created]);
            }
            $jobIdx++;
        }
    }

    public static function getWorkflowStartTimestamp(string $taskGuid): string
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $s = $db->select()
            ->from('LEK_task_log', ['created'])
            ->where('taskGuid=?', $taskGuid)
            ->where('eventCode = "E1013"')
            ->where('message like ?', 'recalculate workflow to step %')
            ->order('id')
            ->limit(1);

        return (string) $db->fetchOne($s);
    }

    public static function getBeforeWorkflowStartFilter(string $taskGuid, ?editor_Models_Segment $segment = null): array
    {
        $hasEmptyEditedInStep = false;
        if ($segment !== null && empty($segment->getEditedInStep())) {
            $hasEmptyEditedInStep = true;
        }
        if (! $hasEmptyEditedInStep) {
            $hasEmptyEditedInStep = self::historyHasEmptyEditedInStep($taskGuid, (int) $segment?->getId());
        }

        // If no empty editedInStep entries then no need to look in task_log
        if (! $hasEmptyEditedInStep) {
            return [
                'editedInStep IN ("", ?)' => editor_Workflow_Default::STEP_NO_WORKFLOW,
            ];
        }

        $workflowStartTimestamp = self::getWorkflowStartTimestamp($taskGuid);
        if (empty($workflowStartTimestamp)) {
            return [];
        }

        return [
            'workflowStepNr = ?' => 1,
            'timestamp <= ?' => $workflowStartTimestamp, // <= is needed for api tests to pass
        ];
    }

    private static function historyHasEmptyEditedInStep(string $taskGuid, int $segmentId = 0): bool
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $s = $db->select()
            ->from('LEK_segment_history', ['id'])
            ->where('taskGuid=?', $taskGuid)
            ->where('editedInStep=""')
            ->where('workflowStepNr>0');

        if ($segmentId > 0) {
            $s = $s->where('segmentId=?', $segmentId);
        }

        return (! empty($db->fetchOne($s->limit(1))));
    }
}
