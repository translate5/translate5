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

namespace MittagQI\Translate5\Task\BatchSet;

use DateTime;
use editor_Models_Filter_TaskSpecific;
use editor_Models_Task as Task;
use Exception;
use MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate\UserJobDeadlineBatchUpdater;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\DataProvider\TaskQuerySelectFactory;
use REST_Controller_Request_Http as Request;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

class TaskBatchSetDeadlineDate implements TaskBatchSetterInterface
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly TaskQuerySelectFactory $taskQuerySelectFactory,
        private readonly UserRepository $userRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly UserJobDeadlineBatchUpdater $userJobDeadlineBatchUpdater,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('task.batchSet.deadlineDate'),
            TaskQuerySelectFactory::create(),
            new UserRepository(),
            UserJobRepository::create(),
            ZfExtended_Authentication::getInstance(),
            Zend_Db_Table::getDefaultAdapter(),
            UserJobDeadlineBatchUpdater::create(),
        );
    }

    public function supports(string $updateType): bool
    {
        return 'deadlineDate' === $updateType;
    }

    public function process(Request $request): void
    {
        $deadlineDate = $request->getParam('deadlineDate');
        $workflow = $request->getParam('batchWorkflow');
        $workflowStep = $request->getParam('batchWorkflowStep');

        if (empty($deadlineDate)) {
            $this->logger->error(
                'E1678',
                'Missing {param} parameter for batch update',
                [
                    'param' => 'deadlineDate'
                ]
            );

            return;
        }

        if (empty($workflow) || empty($workflowStep)) {
            $this->logger->error(
                'E1678',
                'Missing {param} parameter for batch update',
                [
                    'param' => empty($workflow) ? 'batchWorkflow' : 'batchWorkflowStep'
                ]
            );

            return;
        }

        try {
            $deadlineDate = (new DateTime($deadlineDate))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->logger->exception($e);

            return;
        }

        foreach ($this->prepareAllowedTaskGuids($request) as $taskGuid) {
            $jobIds = $this->userJobRepository->getAllJobIdsInTask($taskGuid);

            $this->userJobDeadlineBatchUpdater->updateDeadlines($jobIds, $deadlineDate);

            $this->logger->info(
                'E1012',
                'Deadline date for user jobs in task {task} updated to {deadlineDate}',
                [
                    'jobs' => $jobIds,
                    'task' => $taskGuid,
                    'deadlineDate' => $deadlineDate,
                ]
            );
        }
    }

    private function prepareAllowedTaskGuids(Request $request): array
    {
        if (! empty($request->getParam('projectsAndTasks'))) {
            return $this->getTaskGuidsFromProjectsAndTasks(explode(',', $request->getParam('projectsAndTasks')));
        }

        return $this->getTaskGuidsFromFilteredProjects($request->getParam('filter'));
    }

    private function getAllowedTaskGuids(string $jsonFilter): array
    {
        $task = new Task();

        $filter = new editor_Models_Filter_TaskSpecific($task, $jsonFilter);

        $viewer = $this->userRepository->get($this->authentication->getUserId());

        $taskSelect = $this->taskQuerySelectFactory->createTaskSelect($viewer, $filter);

        $rows = $this->db->fetchAll($taskSelect);

        return array_column($rows, 'taskGuid');
    }

    private function getTaskGuidsFromFilteredProjects(string $jsonFilter): array
    {
        $task = new Task();

        $filter = new editor_Models_Filter_TaskSpecific($task, $jsonFilter);

        $viewer = $this->userRepository->get($this->authentication->getUserId());

        $taskSelect = $this->taskQuerySelectFactory->createProjectSelect($viewer, $filter);

        $rows = $this->getAllowedTaskGuids(
            json_encode([
                [
                    'operator' => 'in',
                    'value' => array_column($this->db->fetchAll($taskSelect), 'id'),
                    'property' => 'projectId',
                ],
            ])
        );

        return array_column($rows, 'taskGuid');
    }

    private function getTaskGuidsFromProjectsAndTasks(array $taskAndProjectIds): array
    {
        $taskGuids = $this->getAllowedTaskGuids(
            json_encode([
                [
                    'operator' => 'in',
                    'value' => $taskAndProjectIds,
                    'property' => 'id',
                ],
            ])
        );

        return array_merge(
            $taskGuids,
            $this->getTaskGuidsFromFilteredProjects(
                json_encode([
                    [
                        'operator' => 'in',
                        'value' => $taskAndProjectIds,
                        'property' => 'id',
                    ],
                ])
            )
        );
    }
}
