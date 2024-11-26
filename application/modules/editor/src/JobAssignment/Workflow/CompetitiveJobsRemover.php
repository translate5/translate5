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

use editor_Models_Task;
use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\JobAssignment\Notification\DeletedCompetitorsNotification;
use MittagQI\Translate5\LspJob\Contract\DeleteLspJobAssignmentOperationInterface;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\LspJob\Operation\DeleteLspJobAssignmentOperation;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\UserJob\Contract\DeleteUserJobAssignmentOperationInterface;
use MittagQI\Translate5\UserJob\Operation\DeleteUserJobAssignmentOperation;
use MittagQI\Translate5\Workflow\Notification\DTO\DeletedJobDto;

class CompetitiveJobsRemover
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly DeleteUserJobAssignmentOperationInterface $deleteUserJobOperation,
        private readonly DeleteLspJobAssignmentOperationInterface $deleteLspJobOperation,
        private readonly DeletedCompetitorsNotification $notificator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserJobRepository::create(),
            LspJobRepository::create(),
            TaskRepository::create(),
            DeleteUserJobAssignmentOperation::create(),
            DeleteLspJobAssignmentOperation::create(),
            DeletedCompetitorsNotification::create(),
        );
    }

    public function removeCompetitorsOfUserJob(UserJob $job): void
    {
        $task = $this->taskRepository->getByGuid($job->getTaskGuid());
        $responsibleUser = $this->userRepository->getByGuid($job->getUserGuid());
        $anonymizeUsers = $task->anonymizeUsers(false);

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

    public function removeCompetitorsOfLspJob(LspJobAssociation $job): void
    {
        $task = $this->taskRepository->getByGuid($job->getTaskGuid());
        $responsibleUser = $this->userRepository->getByGuid($job->getUserGuid());
        $anonymizeUsers = $task->anonymizeUsers(false);

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

    public function deleteLspJob(
        mixed $toDelete,
        editor_Models_Task $task,
        User $responsibleUser,
        bool $anonymizeUsers
    ): void {
        $dataJob = $this->userJobRepository->getDataJobByLspJob($toDelete->getId());
        $deletedJobData = DeletedJobDto::fromUserJob($dataJob);

        $this->deleteLspJobOperation->forceDelete($toDelete);

        $this->notificator->sendNotification($task, $deletedJobData, $responsibleUser, $anonymizeUsers);
    }

    public function deleteUserJob(
        mixed $toDelete,
        editor_Models_Task $task,
        User $responsibleUser,
        bool $anonymizeUsers
    ): void {
        $deletedGobData = DeletedJobDto::fromUserJob($toDelete);

        $this->deleteUserJobOperation->forceDelete($toDelete);

        $this->notificator->sendNotification($task, $deletedGobData, $responsibleUser, $anonymizeUsers);
    }
}