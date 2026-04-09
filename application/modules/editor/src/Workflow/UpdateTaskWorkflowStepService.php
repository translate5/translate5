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

namespace MittagQI\Translate5\Workflow;

use editor_Models_Task;
use editor_Models_TaskProgress;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use ReflectionException;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Adapter_Exception;
use Zend_Db_Expr;
use Zend_Db_Statement_Exception;
use Zend_Db_Table;
use Zend_Exception;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_User;

readonly class UpdateTaskWorkflowStepService
{
    public function __construct(
        private TaskWorkflowLogRepository $taskWorkflowLogRepository,
        private SegmentHistoryAggregation $segmentHistoryAggregation,
        private Zend_Db_Adapter_Abstract $db,
    ) {
    }

    /**
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(
            TaskWorkflowLogRepository::create(),
            SegmentHistoryAggregation::create(),
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws Zend_Db_Adapter_Exception
     * @throws ReflectionException
     */
    public function updateWorkflowStep(
        editor_Models_Task $task,
        string $stepName,
        bool $increaseStep = true,
    ): void {
        $previousStepName = $task->getWorkflowStepName();
        $data = [
            'workflowStepName' => $stepName,
        ];
        if ($increaseStep) {
            $data['workflowStep'] = new Zend_Db_Expr('`workflowStep` + 1');
            //step nr is not updated in task entity! For correct value we have
            // to reload the task and load the value form DB.
        }
        $task->__call('setWorkflowStepName', [$stepName]);
        $this->db->update(editor_Models_Task::TABLE_ALIAS, $data, [
            'taskGuid = ?' => $task->getTaskGuid(),
        ]);

        if ($previousStepName !== $stepName) {
            $this->taskWorkflowLogRepository->add(
                $task->getTaskGuid(),
                $task->getWorkflow(),
                $stepName,
                $this->getActingUserGuid(),
            );
            $this->segmentHistoryAggregation->cloneSyntheticEntriesForWorkflowStep(
                $task->getTaskGuid(),
                $task->getWorkflow(),
                $stepName,
            );
        }

        ZfExtended_Factory
            ::get(editor_Models_TaskProgress::class)
                ->updateSegmentFinishCount($task);
    }

    private function getActingUserGuid(): string
    {
        $auth = ZfExtended_Authentication::getInstance();
        $userGuid = $auth->getUserGuid();

        return empty($userGuid) ? ZfExtended_Models_User::SYSTEM_GUID : $userGuid;
    }
}
