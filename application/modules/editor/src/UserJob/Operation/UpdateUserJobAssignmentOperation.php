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

namespace MittagQI\Translate5\UserJob\Operation;

use editor_Models_TaskUserAssoc as UserJob;
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\Validator\BeforeFinishStateTaskValidator;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\UserJob\Contract\UpdateUserJobAssignmentOperationInterface;
use MittagQI\Translate5\UserJob\Exception\AssignedUserCanBeChangedOnlyForLspJobException;
use MittagQI\Translate5\UserJob\Exception\InvalidWorkflowProvidedException;
use MittagQI\Translate5\UserJob\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\UserJob\Exception\TrackChangesRightsAreNotSubsetOfLspJobException;
use MittagQI\Translate5\UserJob\Exception\WorkflowUpdateProhibitedForLspJobsException;
use MittagQI\Translate5\UserJob\Operation\DTO\UpdateUserJobDto;
use MittagQI\Translate5\UserJob\Validation\TrackChangesRightsValidator;
use Zend_Registry;
use ZfExtended_Logger;

class UpdateUserJobAssignmentOperation implements UpdateUserJobAssignmentOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly UserJobActionFeasibilityAssert $feasibilityAssert,
        private readonly ZfExtended_Logger $logger,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly BeforeFinishStateTaskValidator $beforeFinishStateTaskValidator,
        private readonly TrackChangesRightsValidator $trackChangesRightsValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            new TaskRepository(),
            LspJobRepository::create(),
            LspUserRepository::create(),
            UserJobActionFeasibilityAssert::create(),
            Zend_Registry::get('logger')->cloneMe('userJob.update'),
            new editor_Workflow_Manager(),
            BeforeFinishStateTaskValidator::create(),
            TrackChangesRightsValidator::create(),
        );
    }

    public function update(UserJob $job, UpdateUserJobDto $dto): void
    {
        $this->feasibilityAssert->assertAllowed(Action::Update, $job);

        $lspJob = $this->resolveLspJob($job, $dto);

        $oldJob = clone $job;

        if (null !== $dto->state) {
            $job->setState($dto->state);
        }

        $this->updateAssignedUser($job, $dto);

        $this->updateWorkflow($job, $dto);

        if (null !== $lspJob) {
            $job->setLspJobId($lspJob->getId());
        }

        if (null !== $dto->segmentRange) {
            $job->setSegmentrange($dto->segmentRange);
        }

        if (null !== $dto->deadlineDate) {
            $job->setDeadlineDate($dto->deadlineDate);
        }

        $this->updateTrackChangesRights($job, $lspJob, $dto);

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
                ]
            );
        }
    }

    /**
     * @throws WorkflowUpdateProhibitedForLspJobsException
     */
    public function updateWorkflow(UserJob $job, UpdateUserJobDto $dto): void
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

        if ($job->isLspJob()) {
            throw new WorkflowUpdateProhibitedForLspJobsException();
        }

        $job->setRole($dto->workflow->role);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);
    }

    private function resolveLspJob(UserJob $job, UpdateUserJobDto $dto): ?LspJobAssociation
    {
        if (! $job->isLspUserJob()) {
            return null;
        }

        if (null === $dto->workflow) {
            return $this->lspJobRepository->get((int) $job->getLspJobId());
        }

        $lspUser = $this->lspUserRepository->getByUserGuid($job->getUserGuid());

        try {
            return $this->lspJobRepository->getByTaskGuidAndWorkflow(
                (int) $lspUser->lsp->getId(),
                $job->getTaskGuid(),
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );
        } catch (NotFoundLspJobException) {
            throw new InvalidWorkflowStepProvidedException();
        }
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfLspJobException
     */
    private function updateTrackChangesRights(UserJob $job, ?LspJobAssociation $lspJob, UpdateUserJobDto $dto): void
    {
        if (null !== $lspJob && ! $job->isLspJob()) {
            $this->trackChangesRightsValidator->assertTrackChangesRightsAreSubsetOfLspJob(
                $dto->canSeeTrackChangesOfPrevSteps,
                $dto->canSeeAllTrackChanges,
                $dto->canAcceptOrRejectTrackChanges,
                $lspJob,
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
     * @throws OnlyCoordinatorCanBeAssignedToLspJobException
     * @throws AssignedUserCanBeChangedOnlyForLspJobException
     */
    public function updateAssignedUser(UserJob $job, UpdateUserJobDto $dto): void
    {
        if (null === $dto->userGuid) {
            return;
        }

        $lspUser = $this->lspUserRepository->findByUserGuid($dto->userGuid);

        if (! $job->isLspJob()) {
            throw new AssignedUserCanBeChangedOnlyForLspJobException();
        }

        try {
            JobCoordinator::fromLspUser($lspUser);
        } catch (CantCreateCoordinatorFromUserException) {
            throw new OnlyCoordinatorCanBeAssignedToLspJobException();
        }

        $job->setUserGuid($dto->userGuid);
    }
}
