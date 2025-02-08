<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\JobAssignment\UserJob\Operation;

use editor_Models_TaskUserAssoc as UserJob;
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\CoordinatorGroup\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorDontBelongToLCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\UpdateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidWorkflowProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\WorkflowUpdateProhibitedForCoordinatorGroupJobsException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\UpdateUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\TrackChangesRightsValidator;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLockService;
use MittagQI\Translate5\Task\Validator\BeforeFinishStateTaskValidator;
use RuntimeException;
use Zend_Registry;
use ZfExtended_Logger;

class UpdateUserJobOperation implements UpdateUserJobOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly UserJobActionFeasibilityAssert $feasibilityAssert,
        private readonly ZfExtended_Logger $logger,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly BeforeFinishStateTaskValidator $beforeFinishStateTaskValidator,
        private readonly TrackChangesRightsValidator $trackChangesRightsValidator,
        private readonly TaskLockService $taskLockService,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            TaskRepository::create(),
            CoordinatorGroupJobRepository::create(),
            CoordinatorGroupUserRepository::create(),
            UserJobActionFeasibilityAssert::create(),
            Zend_Registry::get('logger')->cloneMe('userJob.update'),
            new editor_Workflow_Manager(),
            BeforeFinishStateTaskValidator::create(),
            TrackChangesRightsValidator::create(),
            TaskLockService::create(),
        );
    }

    public function update(UserJob $job, UpdateUserJobDto $dto): void
    {
        $lock = $this->taskLockService->getLockForTask($job->getTaskGuid());

        if (! $lock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $job->getTaskGuid());
        }

        try {
            $this->feasibilityAssert->assertAllowed(Action::Update, $job);

            $groupJob = $this->resolveCoordinatorGroupJob($job, $dto);

            $oldJob = clone $job;

            if (null !== $dto->state) {
                $job->setState($dto->state);
            }

            $this->updateAssignedUser($job, $dto);

            $this->updateWorkflow($job, $dto);

            if (null !== $groupJob) {
                $job->setCoordinatorGroupJobId($groupJob->getId());
            }

            if (null !== $dto->segmentRange && ! $job->isCoordinatorGroupJob()) {
                $job->setSegmentrange($dto->segmentRange);
            }

            if (null !== $dto->deadlineDate) {
                $job->setDeadlineDate($dto->deadlineDate);
            }

            $this->updateTrackChangesRights($job, $groupJob, $dto);

            $job->validate();

            $task = $this->taskRepository->getByGuid($job->getTaskGuid());
            $workflow = $this->workflowManager->getActiveByTask($task);

            $workflow->hookin()->doWithUserAssoc(
                $oldJob,
                $job,
                function (?string $state) use ($job, $task) {
                    if (null !== $state) {
                        $this->beforeFinishStateTaskValidator->validateForTaskFinish($state, $job, $task);
                    }

                    $this->userJobRepository->save($job);
                }
            );

            if (null !== $dto->state && $oldJob->getState() !== $dto->state) {
                $this->logger->info(
                    'E1012',
                    'job status changed from {oldState} to {newState}',
                    [
                        'tua' => $job->getSanitizedEntityForLog(),
                        'oldState' => $job->getState(),
                        'newState' => $dto->state,
                        'task' => $task,
                    ]
                );
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws WorkflowUpdateProhibitedForCoordinatorGroupJobsException
     */
    private function updateWorkflow(UserJob $job, UpdateUserJobDto $dto): void
    {
        if (null === $dto->workflow) {
            return;
        }

        if ($job->getWorkflow() !== $dto->workflow->workflow) {
            throw new InvalidWorkflowProvidedException();
        }

        if ($job->getWorkflowStepName() === $dto->workflow->workflowStepName) {
            return;
        }

        if ($job->isCoordinatorGroupJob()) {
            throw new WorkflowUpdateProhibitedForCoordinatorGroupJobsException();
        }

        $job->setRole($dto->workflow->role);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);
    }

    private function resolveCoordinatorGroupJob(UserJob $job, UpdateUserJobDto $dto): ?CoordinatorGroupJob
    {
        if (! $job->isCoordinatorGroupUserJob()) {
            return null;
        }

        if (null === $dto->workflow) {
            return $this->coordinatorGroupJobRepository->get((int) $job->getCoordinatorGroupJobId());
        }

        $groupUser = $this->coordinatorGroupUserRepository->getByUserGuid($job->getUserGuid());

        try {
            return $this->coordinatorGroupJobRepository->getByCoordinatorGroupIdTaskGuidAndWorkflow(
                (int) $groupUser->group->getId(),
                $job->getTaskGuid(),
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );
        } catch (NotFoundCoordinatorGroupJobException) {
            throw new InvalidWorkflowStepProvidedException();
        }
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException
     */
    private function updateTrackChangesRights(UserJob $job, ?CoordinatorGroupJob $groupJob, UpdateUserJobDto $dto): void
    {
        if (null !== $groupJob && ! $job->isCoordinatorGroupJob()) {
            $this->trackChangesRightsValidator->assertTrackChangesRightsAreSubsetOfCoordinatorGroupJob(
                $dto->canSeeTrackChangesOfPrevSteps,
                $dto->canSeeAllTrackChanges,
                $dto->canAcceptOrRejectTrackChanges,
                $groupJob,
            );
        }

        if (null !== $dto->canSeeTrackChangesOfPrevSteps) {
            $job->setTrackchangesShow((int) $dto->canSeeTrackChangesOfPrevSteps);
        }

        if (null !== $dto->canSeeAllTrackChanges) {
            $job->setTrackchangesShowAll((int) $dto->canSeeAllTrackChanges);
        }

        if (null !== $dto->canAcceptOrRejectTrackChanges) {
            $job->setTrackchangesAcceptReject((int) $dto->canAcceptOrRejectTrackChanges);
        }
    }

    /**
     * @throws OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException
     * @throws AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException
     */
    private function updateAssignedUser(UserJob $job, UpdateUserJobDto $dto): void
    {
        if (null === $dto->userGuid) {
            return;
        }

        $groupUser = $this->coordinatorGroupUserRepository->findByUserGuid($dto->userGuid);

        if (! $job->isCoordinatorGroupJob()) {
            throw new AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException();
        }

        try {
            JobCoordinator::fromCoordinatorGroupUser($groupUser);
        } catch (CantCreateCoordinatorFromUserException) {
            throw new OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException();
        }

        $currentCoordinator = $this->coordinatorGroupUserRepository->getByUserGuid($job->getUserGuid());

        if (! $currentCoordinator->group->same($groupUser->group)) {
            throw new CoordinatorDontBelongToLCoordinatorGroupException();
        }

        $job->setUserGuid($dto->userGuid);
    }
}
