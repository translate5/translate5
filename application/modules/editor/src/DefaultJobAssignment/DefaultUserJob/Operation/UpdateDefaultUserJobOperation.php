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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation;

use editor_Models_UserAssocDefault as DefaultUserJob;
use editor_Workflow_Manager;
use MittagQI\Translate5\DefaultJobAssignment\Contract\UpdateDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Exception\NotLspCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DTO\UpdateDefaultJobDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\Repository\LspUserRepository;

class UpdateDefaultUserJobOperation implements UpdateDefaultUserJobOperationInterface
{
    public function __construct(
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly LspCustomerAssociationValidator $lspCustomerAssociationValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            DefaultUserJobRepository::create(),
            LspUserRepository::create(),
            new editor_Workflow_Manager(),
            LspCustomerAssociationValidator::create(),
        );
    }

    public function updateJob(DefaultUserJob $job, UpdateDefaultJobDto $dto): void
    {
        $lspUser = $this->lspUserRepository->findByUserGuid($dto->userGuid);

        if (null !== $lspUser) {
            try {
                $this->lspCustomerAssociationValidator->assertCustomersAreSubsetForLSP(
                    (int) $lspUser->lsp->getId(),
                    (int) $job->getCustomerId()
                );
            } catch (CustomerDoesNotBelongToLspException) {
                throw new NotLspCustomerException();
            }
        }

        if (null !== $dto->workflowStepName) {
            $workflow = $this->workflowManager->getCached($job->getWorkflow());

            if (! in_array($dto->workflowStepName, $workflow->getUsableSteps())) {
                throw new InvalidWorkflowStepProvidedException();
            }

            $job->setWorkflowStepName($dto->workflowStepName);
        }

        if (null !== $dto->sourceLanguageId) {
            $job->setSourceLang($dto->sourceLanguageId);
        }

        if (null !== $dto->targetLanguageId) {
            $job->setTargetLang($dto->targetLanguageId);
        }

        if (null !== $dto->deadline) {
            $job->setDeadlineDate($dto->deadline);
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

        $this->defaultUserJobRepository->save($job);
    }
}
