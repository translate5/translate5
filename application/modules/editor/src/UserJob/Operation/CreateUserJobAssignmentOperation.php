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
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LspJob\Contract\CreateLspJobAssignmentOperationInterface;
use MittagQI\Translate5\LspJob\DTO\NewLspJobDto;
use MittagQI\Translate5\LspJob\Exception\LspJobAlreadyExistsException;
use MittagQI\Translate5\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\LspJob\Operation\CreateLspJobAssignmentOperation;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\UserJob\Contract\CreateUserJobAssignmentOperationInterface;
use MittagQI\Translate5\UserJob\Exception\AttemptToAssignLspUserToAJobBeforeLspJobCreatedException;
use MittagQI\Translate5\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\UserJob\Exception\OnlyOneUniqueLspJobCanBeAssignedPerTaskException;
use MittagQI\Translate5\UserJob\Exception\TrackChangesRightsAreNotSubsetOfLspJobException;
use MittagQI\Translate5\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\UserJob\TypeEnum;
use MittagQI\Translate5\UserJob\Validation\TrackChangesRightsValidator;

class CreateUserJobAssignmentOperation implements CreateUserJobAssignmentOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CreateLspJobAssignmentOperationInterface $createLspJobAssignmentOperation,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly LspJobRepository $lspJobRepository,
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
            CreateLspJobAssignmentOperation::create(),
            LspUserRepository::create(),
            LspJobRepository::create(),
            TrackChangesRightsValidator::create(),
        );
    }

    /**
     * @throws AttemptToAssignLspUserToAJobBeforeLspJobCreatedException
     * @throws OnlyCoordinatorCanBeAssignedToLspJobException
     * @throws OnlyOneUniqueLspJobCanBeAssignedPerTaskException
     * @throws TrackChangesRightsAreNotSubsetOfLspJobException
     */
    public function assignJob(NewUserJobDto $dto): UserJob
    {
        $lspUser = $this->lspUserRepository->findByUserGuid($dto->userGuid);
        $lspJob = null;

        if (null !== $lspUser) {
            $lspJob = $this->resolveLspJob($lspUser, $dto);
        }

        $this->validateLspUserJob($lspJob, $dto);

        $job = $this->userJobRepository->getEmptyModel();
        $job->setTaskGuid($dto->taskGuid);
        $job->setUserGuid($dto->userGuid);
        $job->setState($dto->state);
        $job->setRole($dto->workflow->role);
        $job->setWorkflow($dto->workflow->workflow);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);
        $job->setType($dto->type);
        $job->setSegmentrange($dto->segmentRange);
        $job->setAssignmentDate($dto->assignmentDate);
        $job->setDeadlineDate($dto->deadlineDate);
        $job->setTrackchangesShow((int) $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps);
        $job->setTrackchangesShowAll((int) $dto->trackChangesRights->canSeeAllTrackChanges);
        $job->setTrackchangesAcceptReject((int) $dto->trackChangesRights->canAcceptOrRejectTrackChanges);
        $job->setLspJobId($lspJob?->getId());

        $job->validate();

        $job->createstaticAuthHash();

        $this->userJobRepository->save($job);

        return $job;
    }

    /**
     * @throws AttemptToAssignLspUserToAJobBeforeLspJobCreatedException
     * @throws OnlyCoordinatorCanBeAssignedToLspJobException
     * @throws OnlyOneUniqueLspJobCanBeAssignedPerTaskException
     */
    public function resolveLspJob(LspUser $lspUser, NewUserJobDto $dto): LspJobAssociation
    {
        if (TypeEnum::LSP === $dto->type) {
            try {
                JobCoordinator::fromLspUser($lspUser);
            } catch (CantCreateCoordinatorFromUserException) {
                throw new OnlyCoordinatorCanBeAssignedToLspJobException();
            }

            // UserJob with type LSP plays role of data store for LSP job
            $newLspJobDto = new NewLspJobDto($dto->taskGuid, (int) $lspUser->lsp->getId(), $dto->workflow);

            try {
                return $this->createLspJobAssignmentOperation->assignJob($newLspJobDto);
            } catch (LspJobAlreadyExistsException) {
                // unique(taskId, lspId, workflow, workflowStepName)
                throw new OnlyOneUniqueLspJobCanBeAssignedPerTaskException();
            }
        }

        try {
            return $this->lspJobRepository->getByTaskGuidAndWorkflow(
                (int) $lspUser->lsp->getId(),
                $dto->taskGuid,
                $dto->workflow->workflow,
                $dto->workflow->workflowStepName,
            );
        } catch (NotFoundLspJobException) {
            throw new AttemptToAssignLspUserToAJobBeforeLspJobCreatedException();
        }
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfLspJobException
     */
    private function validateLspUserJob(?LspJobAssociation $lspJob, NewUserJobDto $dto): void
    {
        if (null === $lspJob) {
            return;
        }

        if (TypeEnum::LSP === $dto->type) {
            return;
        }

        $this->trackChangesRightsValidator->assertTrackChangesRightsAreSubsetOfLspJob(
            $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps,
            $dto->trackChangesRights->canSeeAllTrackChanges,
            $dto->trackChangesRights->canAcceptOrRejectTrackChanges,
            $lspJob,
        );
    }
}
