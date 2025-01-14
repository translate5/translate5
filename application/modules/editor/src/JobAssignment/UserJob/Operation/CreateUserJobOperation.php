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
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\CreateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Event\UserJobCreatedEvent;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\CompetitiveJobCreationValidator;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\TrackChangesRightsValidator;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLockService;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Zend_Registry;
use ZfExtended_Logger;

class CreateUserJobOperation implements CreateUserJobOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly TrackChangesRightsValidator $trackChangesRightsValidator,
        private readonly CompetitiveJobCreationValidator $competitiveJobCreationValidator,
        private readonly TaskLockService $taskLockService,
        private readonly ZfExtended_Logger $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            CoordinatorGroupUserRepository::create(),
            CoordinatorGroupJobRepository::create(),
            TaskRepository::create(),
            TrackChangesRightsValidator::create(),
            CompetitiveJobCreationValidator::create(),
            TaskLockService::create(),
            Zend_Registry::get('logger')->cloneMe('userJob.create'),
            EventDispatcher::create(),
        );
    }

    public function assignJob(NewUserJobDto $dto): UserJob
    {
        $taskLock = $this->taskLockService->getLockForTask($dto->taskGuid);

        if (! $taskLock->isAcquired() && ! $taskLock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $dto->taskGuid);
        }

        try {
            $groupUser = $this->coordinatorGroupUserRepository->findByUserGuid($dto->userGuid);
            $groupJob = null;

            if (null !== $groupUser) {
                $groupJob = $this->resolveCoordinatorGroupJob((int) $groupUser->group->getId(), $dto);

                $this->validateTrackChangesSettings($groupJob, $dto);
            }

            $task = $this->taskRepository->getByGuid($dto->taskGuid);

            $this->competitiveJobCreationValidator->assertCanCreate(
                $task,
                $groupJob ? (int) $groupJob->getId() : null,
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );

            $job = new UserJob();
            $job->setTaskGuid($task->getTaskGuid());
            $job->setUserGuid($dto->userGuid);
            $job->setState($dto->state);
            $job->setRole($dto->workflow->role);
            $job->setWorkflow($dto->workflow->workflow);
            $job->setWorkflowStepName($dto->workflow->workflowStepName);
            $job->setType(TypeEnum::Editor);
            $job->setAssignmentDate($dto->assignmentDate);
            $job->setDeadlineDate($dto->deadlineDate);
            $job->setTrackchangesShow((int) $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps);
            $job->setTrackchangesShowAll((int) $dto->trackChangesRights->canSeeAllTrackChanges);
            $job->setTrackchangesAcceptReject((int) $dto->trackChangesRights->canAcceptOrRejectTrackChanges);

            if (null !== $groupJob) {
                $job->setCoordinatorGroupJobId($groupJob->getId());
            }

            if (null !== $dto->segmentRange) {
                $job->setSegmentrange($dto->segmentRange);
            }

            $job->validate();

            $job->createstaticAuthHash();

            $this->userJobRepository->save($job);

            $this->logger->info('E1012', 'User job created', [
                'task' => $task,
                'job' => $job->getSanitizedEntityForLog(),
            ]);

            $this->eventDispatcher->dispatch(new UserJobCreatedEvent($job));

            return $job;
        } finally {
            $taskLock->release();
        }
    }

    /**
     * @throws AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException
     */
    public function resolveCoordinatorGroupJob(int $groupId, NewUserJobDto $dto): CoordinatorGroupJob
    {
        try {
            return $this->coordinatorGroupJobRepository->getByCoordinatorGroupIdTaskGuidAndWorkflow(
                $groupId,
                $dto->taskGuid,
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );
        } catch (NotFoundCoordinatorGroupJobException) {
            throw new AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException();
        }
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException
     */
    private function validateTrackChangesSettings(CoordinatorGroupJob $groupJob, NewUserJobDto $dto): void
    {
        if (TypeEnum::Coordinator === $dto->type) {
            return;
        }

        $this->trackChangesRightsValidator->assertTrackChangesRightsAreSubsetOfCoordinatorGroupJob(
            $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps,
            $dto->trackChangesRights->canSeeAllTrackChanges,
            $dto->trackChangesRights->canAcceptOrRejectTrackChanges,
            $groupJob,
        );
    }
}
