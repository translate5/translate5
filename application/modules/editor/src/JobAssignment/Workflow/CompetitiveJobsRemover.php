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
use MittagQI\Translate5\JobAssignment\Exception\CompetitiveJobAlreadyTakenException;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJob;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DeleteLspJobOperation;
use MittagQI\Translate5\JobAssignment\Notification\DeletedCompetitorsNotification;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspRepository;
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
        private readonly LspJobRepository $lspJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly LspRepositoryInterface $lspRepository,
        private readonly DeleteUserJobOperation $deleteUserJobOperation,
        private readonly DeleteLspJobOperation $deleteLspJobOperation,
        private readonly DeletedCompetitorsNotification $notificator,
        private readonly TaskLockService $taskLockService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserJobRepository::create(),
            LspJobRepository::create(),
            TaskRepository::create(),
            LspRepository::create(),
            DeleteUserJobOperation::create(),
            DeleteLspJobOperation::create(),
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

            $lspJob = $this->lspJobRepository->findLspJobOfCoordinatorInTask(
                $userGuid,
                $taskGuid,
                $workflowStepName,
            );

            if (null !== $lspJob) {
                $this->removeCompetitorsOfLspJob($lspJob, $task, $responsibleUser, $anonymizeUsers);

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
        if ($job->isLspUserJob()) {
            // for now LSP user jobs behave like cooperative jobs
            return;
        }

        $lspJobs = $this->lspJobRepository->getByTaskGuidAndWorkflow(
            $job->getTaskGuid(),
            $job->getWorkflow(),
            $job->getWorkflowStepName()
        );

        foreach ($lspJobs as $toDelete) {
            $this->deleteLspJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
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
    private function removeCompetitorsOfLspJob(
        LspJob $job,
        Task $task,
        User $responsibleUser,
        bool $anonymizeUsers,
    ): void {
        $lsp = $this->lspRepository->get((int) $job->getLspId());

        if (! $lsp->isDirectLsp()) {
            // for now Sub LSP jobs behave like cooperative jobs
            return;
        }

        $lspJobs = $this->lspJobRepository->getByTaskGuidAndWorkflow(
            $job->getTaskGuid(),
            $job->getWorkflow(),
            $job->getWorkflowStepName()
        );

        foreach ($lspJobs as $toDelete) {
            if ($job->getId() !== $toDelete->getId()) {
                $this->deleteLspJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
            }
        }

        $userJobs = $this->userJobRepository->getJobsByTaskAndStep($job->getTaskGuid(), $job->getWorkflowStepName());

        foreach ($userJobs as $toDelete) {
            $this->deleteUserJob($toDelete, $task, $responsibleUser, $anonymizeUsers);
        }
    }

    private function deleteLspJob(
        LspJob $toDelete,
        Task $task,
        User $responsibleUser,
        bool $anonymizeUsers
    ): void {
        $dataJob = $this->userJobRepository->getDataJobByLspJob($toDelete->getId());
        $deletedJobData = DeletedJobDto::fromUserJob($dataJob);

        $this->deleteLspJobOperation->deleteLspJob($toDelete);

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
