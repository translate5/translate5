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

namespace MittagQI\Translate5\JobAssignment\LspJob\Operation;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\JobAssignment\LspJob\Contract\CreateLspJobOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJob;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DTO\NewLspJobDto;
use MittagQI\Translate5\JobAssignment\LspJob\Validation\CompetitiveJobCreationValidator;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignSubLspJobBeforeParentJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\NotLspCustomerTaskException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfLspJobException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\TrackChangesRightsValidator;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLockService;
use RuntimeException;
use Throwable;

class CreateLspJobOperation implements CreateLspJobOperationInterface
{
    public function __construct(
        private readonly LspJobRepository $lspJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly LspCustomerAssociationValidator $lspCustomerAssociationValidator,
        private readonly TrackChangesRightsValidator $trackChangesRightsValidator,
        private readonly CompetitiveJobCreationValidator $competitiveJobCreationValidator,
        private readonly TaskLockService $taskLock,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspJobRepository::create(),
            UserJobRepository::create(),
            TaskRepository::create(),
            JobCoordinatorRepository::create(),
            LspCustomerAssociationValidator::create(),
            TrackChangesRightsValidator::create(),
            CompetitiveJobCreationValidator::create(),
            TaskLockService::create(),
        );
    }

    public function assignJob(NewLspJobDto $dto): LspJob
    {
        $coordinator = $this->coordinatorRepository->findByUserGuid($dto->userGuid);

        if ($coordinator === null) {
            throw new OnlyCoordinatorCanBeAssignedToLspJobException();
        }

        $taskLock = $this->taskLock->getLockForTask($dto->taskGuid);

        if (! $taskLock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $dto->taskGuid);
        }

        try {
            $lsp = $coordinator->lsp;
            $task = $this->taskRepository->getByGuid($dto->taskGuid);

            $this->competitiveJobCreationValidator->assertCanCreate(
                $task,
                $lsp,
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName
            );

            if (! $lsp->isDirectLsp()) {
                try {
                    // check if parent LSP Job exists. Sub LSP can have only jobs related to its parent LSP
                    $parentJob = $this->lspJobRepository->getByLspIdTaskGuidAndWorkflow(
                        (int) $lsp->getParentId(),
                        $dto->taskGuid,
                        $dto->workflow->workflow,
                        $dto->workflow->workflowStepName,
                    );
                } catch (NotFoundLspJobException) {
                    throw new AttemptToAssignSubLspJobBeforeParentJobCreatedException();
                }

                $this->validateTrackChangesSettings($parentJob, $dto);
            }

            try {
                $this->lspCustomerAssociationValidator->assertCustomersAreSubsetForLSP(
                    (int) $lsp->getId(),
                    (int) $task->getCustomerId()
                );
            } catch (CustomerDoesNotBelongToLspException) {
                throw new NotLspCustomerTaskException();
            }

            $lspJob = new LspJob();
            $lspJob->setTaskGuid($dto->taskGuid);
            $lspJob->setLspId((int) $coordinator->lsp->getId());
            $lspJob->setWorkflow($dto->workflow->workflow);
            $lspJob->setWorkflowStepName($dto->workflow->workflowStepName);

            $this->lspJobRepository->save($lspJob);

            $userJob = new UserJob();
            $userJob->setTaskGuid($task->getTaskGuid());
            $userJob->setUserGuid($dto->userGuid);
            $userJob->setState($dto->state);
            $userJob->setRole($dto->workflow->role);
            $userJob->setWorkflow($dto->workflow->workflow);
            $userJob->setWorkflowStepName($dto->workflow->workflowStepName);
            $userJob->setType(TypeEnum::Lsp);
            $userJob->setAssignmentDate($dto->assignmentDate);
            $userJob->setTrackchangesShow((int) $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps);
            $userJob->setTrackchangesShowAll((int) $dto->trackChangesRights->canSeeAllTrackChanges);
            $userJob->setTrackchangesAcceptReject((int) $dto->trackChangesRights->canAcceptOrRejectTrackChanges);
            $userJob->setLspJobId($lspJob->getId());

            if (null !== $dto->deadlineDate) {
                $userJob->setDeadlineDate($dto->deadlineDate);
            }

            if (null !== $dto->segmentRange) {
                $userJob->setSegmentrange($dto->segmentRange);
            }

            $userJob->createstaticAuthHash();

            try {
                $userJob->validate();

                $this->userJobRepository->save($userJob);
            } catch (Throwable $e) {
                $this->lspJobRepository->delete((int) $lspJob->getId());

                throw $e;
            }

            return $lspJob;
        } finally {
            $taskLock->release();
        }
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfLspJobException
     */
    private function validateTrackChangesSettings(LspJob $lspJob, NewLspJobDto $dto): void
    {
        $this->trackChangesRightsValidator->assertTrackChangesRightsAreSubsetOfLspJob(
            $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps,
            $dto->trackChangesRights->canSeeAllTrackChanges,
            $dto->trackChangesRights->canAcceptOrRejectTrackChanges,
            $lspJob,
        );
    }
}
