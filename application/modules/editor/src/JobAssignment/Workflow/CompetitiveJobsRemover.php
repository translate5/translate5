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

namespace MittagQI\Translate5\JobAssignment\Workflow;

use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DeleteCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\Exception\CompetitiveJobAlreadyTakenException;
use MittagQI\Translate5\JobAssignment\Notification\DeletedCompetitorsNotification;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\TaskLockService;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\Workflow\Notification\DTO\DeletedJobDto;
use RuntimeException;

class CompetitiveJobsRemover
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly DeleteUserJobOperation $deleteUserJobOperation,
        private readonly DeleteCoordinatorGroupJobOperation $deleteCoordinatorGroupJobOperation,
        private readonly DeletedCompetitorsNotification $notificator,
        private readonly TaskLockService $taskLockService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
            TaskRepository::create(),
            CoordinatorGroupRepository::create(),
            DeleteUserJobOperation::create(),
            DeleteCoordinatorGroupJobOperation::create(),
            DeletedCompetitorsNotification::create(),
            TaskLockService::create(),
        );
    }

    /**
     * @throws CompetitiveJobAlreadyTakenException
     */
    public function removeCompetitorsOfJobFor(string $userGuid, string $taskGuid, string $workflowStepName): void
    {
        $lock = $this->taskLockService->getLockForTask($taskGuid);

        if (! $lock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $taskGuid);
        }

        try {
            $task = $this->taskRepository->getByGuid($taskGuid);
            $responsibleUser = $this->userRepository->getByGuid($userGuid);
            $anonymizeUsers = $task->anonymizeUsers(false);

            $userJob = $this->userJobRepository->findUserJobInTask(
                $userGuid,
                $taskGuid,
                $workflowStepName,
            );

            if (null !== $userJob) {
                $this->removeCompetitorsOfUserJob($userJob, $task, $responsibleUser, $anonymizeUsers);

                return;
            }

            $groupJob = $this->coordinatorGroupJobRepository->findCurrentCoordinatorGroupJobOfCoordinatorInTask(
                $userGuid,
                $taskGuid,
                $workflowStepName,
            );

            if (null !== $groupJob) {
                $this->removeCompetitorsOfCoordinatorGroupJob($groupJob, $task, $responsibleUser, $anonymizeUsers);

                return;
            }

            throw new CompetitiveJobAlreadyTakenException();
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws CompetitiveJobAlreadyTakenException
     */
    private function removeCompetitorsOfUserJob(
        UserJob $job,
        Task $task,
        User $responsibleUser,
        bool $anonymizeUsers,
    ): void {
        if ($job->isCoordinatorGroupUserJob()) {
            // for now CoordinatorGroup user jobs behave like cooperative jobs
            return;
        }

        $groupJobs = $this->coordinatorGroupJobRepository->getByTaskGuidAndWorkflow(
            $job->getTaskGuid(),
            $job->getWorkflow(),
            $job->getWorkflowStepName()
        );

        foreach ($groupJobs as $toDelete) {
            $this->deleteCoordinatorGroupJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
        }

        $userJobs = $this->userJobRepository->getJobsByTaskAndStep($job->getTaskGuid(), $job->getWorkflowStepName());

        foreach ($userJobs as $toDelete) {
            if ($job->getId() !== $toDelete->getId()) {
                $this->deleteUserJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
            }
        }
    }

    /**
     * @throws CompetitiveJobAlreadyTakenException
     */
    private function removeCompetitorsOfCoordinatorGroupJob(
        CoordinatorGroupJob $job,
        Task $task,
        User $responsibleUser,
        bool $anonymizeUsers,
    ): void {
        $group = $this->coordinatorGroupRepository->get((int) $job->getGroupId());

        if (! $group->isTopRankGroup()) {
            // for now Sub CoordinatorGroup jobs behave like cooperative jobs
            return;
        }

        $groupJobs = $this->coordinatorGroupJobRepository->getByTaskGuidAndWorkflow(
            $job->getTaskGuid(),
            $job->getWorkflow(),
            $job->getWorkflowStepName()
        );

        foreach ($groupJobs as $toDelete) {
            if ($job->getId() !== $toDelete->getId()) {
                $this->deleteCoordinatorGroupJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
            }
        }

        $userJobs = $this->userJobRepository->getJobsByTaskAndStep($job->getTaskGuid(), $job->getWorkflowStepName());

        foreach ($userJobs as $toDelete) {
            $this->deleteUserJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
        }
    }

    private function deleteCoordinatorGroupJob(
        CoordinatorGroupJob $toDelete,
        Task $task,
        User $responsibleUser,
        bool $anonymizeUsers
    ): void {
        $dataJob = $this->userJobRepository->getDataJobByCoordinatorGroupJob((int) $toDelete->getId());
        $deletedJobData = DeletedJobDto::fromUserJob($dataJob);

        $this->deleteCoordinatorGroupJobOperation->deleteCoordinatorGroupJob($toDelete);

        $this->notificator->sendNotification($task, $deletedJobData, $responsibleUser, $anonymizeUsers);
    }

    private function deleteUserJob(
        UserJob $toDelete,
        Task $task,
        User $responsibleUser,
        bool $anonymizeUsers
    ): void {
        $deletedJobData = DeletedJobDto::fromUserJob($toDelete);

        $this->deleteUserJobOperation->deleteUserJob($toDelete);

        $this->notificator->sendNotification($task, $deletedJobData, $responsibleUser, $anonymizeUsers);
    }
}
