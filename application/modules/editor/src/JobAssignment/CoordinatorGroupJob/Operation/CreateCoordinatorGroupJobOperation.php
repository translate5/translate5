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

namespace MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\CreateCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DTO\NewCoordinatorGroupJobDto;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Validation\CompetitiveJobCreationValidator;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\NotCoordinatorGroupCustomerTaskException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\TrackChangesRightsValidator;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLockService;
use RuntimeException;
use Throwable;
use Zend_Registry;
use ZfExtended_Logger;

class CreateCoordinatorGroupJobOperation implements CreateCoordinatorGroupJobOperationInterface
{
    public function __construct(
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly CoordinatorGroupCustomerAssociationValidator $coordinatorGroupCustomerAssociationValidator,
        private readonly TrackChangesRightsValidator $trackChangesRightsValidator,
        private readonly CompetitiveJobCreationValidator $competitiveJobCreationValidator,
        private readonly TaskLockService $taskLock,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupJobRepository::create(),
            UserJobRepository::create(),
            TaskRepository::create(),
            JobCoordinatorRepository::create(),
            CoordinatorGroupCustomerAssociationValidator::create(),
            TrackChangesRightsValidator::create(),
            CompetitiveJobCreationValidator::create(),
            TaskLockService::create(),
            Zend_Registry::get('logger')->cloneMe('coordinatorGroupJob.create'),
        );
    }

    public function assignJob(NewCoordinatorGroupJobDto $dto): CoordinatorGroupJob
    {
        $coordinator = $this->coordinatorRepository->findByUserGuid($dto->userGuid);

        if ($coordinator === null) {
            throw new OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException();
        }

        $taskLock = $this->taskLock->getLockForTask($dto->taskGuid);

        if (! $taskLock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $dto->taskGuid);
        }

        try {
            $group = $coordinator->group;
            $task = $this->taskRepository->getByGuid($dto->taskGuid);

            $this->competitiveJobCreationValidator->assertCanCreate(
                $task,
                $group,
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName
            );

            if (! $group->isTopRankGroup()) {
                try {
                    // check if parent Coordinator Group Job exists.
                    // Sub Group can have only jobs related to its parent Coordinator Group
                    $parentJob = $this->coordinatorGroupJobRepository->getByCoordinatorGroupIdTaskGuidAndWorkflow(
                        (int) $group->getParentId(),
                        $dto->taskGuid,
                        $dto->workflow->workflow,
                        $dto->workflow->workflowStepName,
                    );
                } catch (NotFoundCoordinatorGroupJobException) {
                    throw new AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException();
                }

                $this->validateTrackChangesSettings($parentJob, $dto);
            }

            try {
                $this->coordinatorGroupCustomerAssociationValidator->assertCustomersAreSubsetForCoordinatorGroup(
                    (int) $group->getId(),
                    (int) $task->getCustomerId()
                );
            } catch (CustomerDoesNotBelongToCoordinatorGroupException) {
                throw new NotCoordinatorGroupCustomerTaskException();
            }

            $groupJob = new CoordinatorGroupJob();
            $groupJob->setTaskGuid($dto->taskGuid);
            $groupJob->setGroupId((int) $coordinator->group->getId());
            $groupJob->setWorkflow($dto->workflow->workflow);
            $groupJob->setWorkflowStepName($dto->workflow->workflowStepName);

            $this->coordinatorGroupJobRepository->save($groupJob);

            $dataJob = new UserJob();
            $dataJob->setTaskGuid($task->getTaskGuid());
            $dataJob->setUserGuid($dto->userGuid);
            $dataJob->setState($dto->state);
            $dataJob->setRole($dto->workflow->role);
            $dataJob->setWorkflow($dto->workflow->workflow);
            $dataJob->setWorkflowStepName($dto->workflow->workflowStepName);
            $dataJob->setType(TypeEnum::Coordinator);
            $dataJob->setAssignmentDate($dto->assignmentDate);
            $dataJob->setTrackchangesShow((int) $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps);
            $dataJob->setTrackchangesShowAll((int) $dto->trackChangesRights->canSeeAllTrackChanges);
            $dataJob->setTrackchangesAcceptReject((int) $dto->trackChangesRights->canAcceptOrRejectTrackChanges);
            $dataJob->setCoordinatorGroupJobId($groupJob->getId());

            if (null !== $dto->deadlineDate) {
                $dataJob->setDeadlineDate($dto->deadlineDate);
            }

            if (null !== $dto->segmentRange) {
                $dataJob->setSegmentrange($dto->segmentRange);
            }

            try {
                $dataJob->validate();

                $dataJob->createstaticAuthHash();

                $this->userJobRepository->save($dataJob);
            } catch (Throwable $e) {
                $this->coordinatorGroupJobRepository->delete((int) $groupJob->getId());

                throw $e;
            }

            $this->logger->info('E1012', 'User job created', [
                'task' => $task,
                'job' => $dataJob->getSanitizedEntityForLog(),
            ]);

            return $groupJob;
        } finally {
            $taskLock->release();
        }
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException
     */
    private function validateTrackChangesSettings(
        CoordinatorGroupJob $groupJobJob,
        NewCoordinatorGroupJobDto $dto
    ): void {
        $this->trackChangesRightsValidator->assertTrackChangesRightsAreSubsetOfCoordinatorGroupJob(
            $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps,
            $dto->trackChangesRights->canSeeAllTrackChanges,
            $dto->trackChangesRights->canAcceptOrRejectTrackChanges,
            $groupJobJob,
        );
    }
}
