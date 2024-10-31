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

namespace MittagQI\Translate5\LspJob\Operation;

use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\LspJob\Contract\CreateLspJobAssignmentOperationInterface;
use MittagQI\Translate5\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\LspJob\Operation\DTO\NewLspJobDto;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\UserJob\Contract\CreateUserJobAssignmentOperationInterface;
use MittagQI\Translate5\UserJob\Exception\AttemptToAssignSubLspJobBeforeParentJobCreatedException;
use MittagQI\Translate5\UserJob\Exception\NotLspCustomerTaskException;
use MittagQI\Translate5\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\UserJob\Exception\TrackChangesRightsAreNotSubsetOfLspJobException;
use MittagQI\Translate5\UserJob\Operation\CreateUserJobAssignmentOperation;
use MittagQI\Translate5\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\UserJob\TypeEnum;
use MittagQI\Translate5\UserJob\Validation\TrackChangesRightsValidator;

class CreateLspJobAssignmentOperation implements CreateLspJobAssignmentOperationInterface
{
    public function __construct(
        private readonly LspJobRepository $lspJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly LspCustomerAssociationValidator $lspCustomerAssociationValidator,
        private readonly TrackChangesRightsValidator $trackChangesRightsValidator,
        private readonly CreateUserJobAssignmentOperationInterface $createUserJobOperation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspJobRepository::create(),
            new TaskRepository(),
            JobCoordinatorRepository::create(),
            LspCustomerAssociationValidator::create(),
            TrackChangesRightsValidator::create(),
            CreateUserJobAssignmentOperation::create(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function assignJob(NewLspJobDto $dto): LspJobAssociation
    {
        $coordinator = $this->coordinatorRepository->findByUserGuid($dto->userGuid);

        if ($coordinator === null) {
            throw new OnlyCoordinatorCanBeAssignedToLspJobException();
        }

        $lsp = $coordinator->lsp;

        if (! $lsp->isDirectLsp()) {
            try {
                // check if parent LSP Job exists. Sub LSP can have only jobs related to its parent LSP
                $parentJob = $this->lspJobRepository->getByTaskGuidAndWorkflow(
                    (int)$lsp->getParentId(),
                    $dto->taskGuid,
                    $dto->workflow->workflow,
                    $dto->workflow->workflowStepName,
                );
            } catch (NotFoundLspJobException) {
                throw new AttemptToAssignSubLspJobBeforeParentJobCreatedException();
            }

            $this->validateTrackChangesSettings($parentJob, $dto);
        }

        $task = $this->taskRepository->getByGuid($dto->taskGuid);

        try {
            $this->lspCustomerAssociationValidator->assertCustomersAreSubsetForLSP(
                (int) $lsp->getId(),
                (int) $task->getCustomerId()
            );
        } catch (CustomerDoesNotBelongToLspException) {
            throw new NotLspCustomerTaskException();
        }

        $job = $this->lspJobRepository->getEmptyModel();
        $job->setTaskGuid($dto->taskGuid);
        $job->setLspId((int) $coordinator->lsp->getId());
        $job->setWorkflow($dto->workflow->workflow);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);

        $this->lspJobRepository->save($job);

        try {
            $this->createUserJobOperation->assignJob(NewUserJobDto::fromLspJobDto($dto));
        } catch (\Throwable $e) {
            $this->lspJobRepository->delete($job);

            throw $e;
        }

        return $job;
    }

    /**
     * @throws TrackChangesRightsAreNotSubsetOfLspJobException
     */
    private function validateTrackChangesSettings(LspJobAssociation $lspJob, NewLspJobDto $dto): void
    {
        if (TypeEnum::Lsp === $dto->type) {
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
