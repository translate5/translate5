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

namespace MittagQI\Translate5\Task\DataProvider;

use editor_Models_Task as Task;
use editor_Workflow_Anonymize;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\JobAssignmentViewDataProvider;
use MittagQI\Translate5\Repository\TaskUserTrackingRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use MittagQI\ZfExtended\Acl\SystemResource;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Acl;
use ZfExtended_Models_Filter;

class TaskViewDataProvider
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly TaskQuerySelectFactory $taskQuerySelectFactory,
        private readonly JobAssignmentViewDataProvider $jobAssignmentViewDataProvider,
        private readonly UserRepository $userRepository,
        private readonly TaskUserTrackingRepository $userTrackingRepository,
        private readonly ActionPermissionAssertInterface $userActionPermissionAssert,
        private readonly editor_Workflow_Anonymize $anonymizer,
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            TaskQuerySelectFactory::create(),
            JobAssignmentViewDataProvider::create(),
            new UserRepository(),
            TaskUserTrackingRepository::create(),
            UserActionPermissionAssert::create(),
            new editor_Workflow_Anonymize(),
            ZfExtended_Acl::getInstance(),
        );
    }

    public function getProjectList(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        int $offset = 0,
        int $limit = 0,
    ): array {
        $totalSelect = $this->taskQuerySelectFactory->createTotalProjectCountSelect($viewer, $filter);
        $select = $this->taskQuerySelectFactory->createProjectSelect($viewer, $filter, $offset, $limit);

        $totalCount = $this->db->fetchOne($totalSelect);
        $tasks = $this->db->fetchAll($select);

        // TODO: extract logic from TaskController to here
        return [
            'totalCount' => $totalCount,
            'rows' => $tasks,
        ];
    }

    public function getTaskList(
        User $viewer,
        ?ZfExtended_Models_Filter $filter,
        int $offset = 0,
        int $limit = 0,
    ): array {
        $totalSelect = $this->taskQuerySelectFactory->createTotalTaskCountSelect($viewer, $filter);
        $select = $this->taskQuerySelectFactory->createTaskSelect($viewer, $filter, $offset, $limit);

        $totalCount = $this->db->fetchOne($totalSelect);
        $tasks = $this->db->fetchAll($select);

        foreach ($tasks as &$task) {
            $task = $this->buildTaskView($task, $viewer);
        }

        // TODO: extract logic from TaskController to here
        return [
            'totalCount' => $totalCount,
            'rows' => $tasks,
        ];
    }

    private function getUserTracking(string $taskGuid, bool $anonymizeUsers, User $viewer): array
    {
        $userTracking = $this->userTrackingRepository->getByTaskGuid($taskGuid);

        if ($anonymizeUsers) {
            return array_map(
                fn ($rowTrack) => $this->anonymizer->anonymizeUserdata(
                    $taskGuid,
                    $rowTrack['userGuid'] ?? '',
                    $rowTrack,
                    $viewer->getUserGuid()
                ),
                $userTracking
            );
        }

        $canSeeAllUsers = $this->acl->isInAllowedRoles(
            $viewer->getRoles(),
            SystemResource::ID,
            SystemResource::SEE_ALL_USERS
        );
        $context = new PermissionAssertContext($viewer);

        $resultList = [];

        foreach ($userTracking as $rowTrack) {
            if (empty($rowTrack['userGuid']) && $canSeeAllUsers) {
                $resultList[] = $rowTrack;

                continue;
            }

            $user = $this->userRepository->findByGuid($rowTrack['userGuid']);

            if (! $user && $canSeeAllUsers) {
                $resultList[] = $rowTrack;

                continue;
            }

            if ($this->userActionPermissionAssert->isGranted(UserAction::Read, $user, $context)) {
                $resultList[] = $rowTrack;

                continue;
            }

            $resultList[] = $this->anonymizer->anonymizeUserdata(
                $taskGuid,
                $rowTrack['userGuid'],
                $rowTrack,
                $viewer->getUserGuid()
            );
        }

        return $resultList;
    }

    public function buildTaskView(array $task, User $viewer): array
    {
        $taskModel = new Task();
        $taskModel->init($task);
        $anonymizeUsers = $taskModel->anonymizeUsers();

        $task['users'] = $this->jobAssignmentViewDataProvider->getListFor($task['taskGuid'], $viewer);
        $task['isUsed'] = false;
        $task['lockingUsername'] = null;

        foreach ($task['users'] as &$job) {
            $viewersJob = $job['userGuid'] === $viewer->getUserGuid();
            $matchingWorkflowStep = $task['workflowStepName'] === $job['workflowStepName'];

            // we need an info about the current user in any case
            // so we set info from first job of the current user,
            // but we override info if a later assoc has the matching workflow step name
            if ($viewersJob && (!isset($task['userRole']) || $matchingWorkflowStep)) {
                $task['userRole'] = $job['role'];
                $task['userState'] = $job['state'];
                $task['userStep'] = $job['workflowStepName'];
                // processing some trackchanges properties that can't be parted out to the trackchanges-plugin
                $task['userTrackchangesShow'] = $job['trackchangesShow'];
                $task['userTrackchangesShowAll'] = $job['trackchangesShowAll'];
                $task['userTrackchangesAcceptReject'] = $job['trackchangesAcceptReject'];
            }

            $task['isUsed'] = $task['isUsed'] || !empty($job['usedState']);

            if ($anonymizeUsers) {
                $job = $this->anonymizer->anonymizeUserdata(
                    $task['taskGuid'],
                    $job['userGuid'],
                    $job,
                    $viewer->getUserGuid()
                );
            }
        }

        if (! empty($task['lockingUser'])) {
            $user = $this->userRepository->findByGuid($task['lockingUser']);

            $task['lockingUsername'] = $user?->getUsernameLong() ?? '- not found -';
        }

        $task['userTracking'] = $this->getUserTracking($task['taskGuid'], $anonymizeUsers, $viewer);

        if (null !== $task['lockingUser'] && $anonymizeUsers) {
            $task = $this->anonymizer->anonymizeUserdata(
                $task['taskGuid'],
                $task['lockingUser'],
                $task,
                $viewer->getUserGuid()
            );
        }

        return $task;
    }
}
