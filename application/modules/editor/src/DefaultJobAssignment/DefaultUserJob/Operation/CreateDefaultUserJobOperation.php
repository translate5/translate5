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
use MittagQI\Translate5\DefaultJobAssignment\Contract\CreateDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\DefaultUserJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\Repository\LspUserRepository;

class CreateDefaultUserJobOperation implements CreateDefaultUserJobOperationInterface
{
    public function __construct(
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
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
        );
    }

    /**
     * @throws DefaultUserJobAlreadyExistsException
     * @throws OnlyCoordinatorCanBeAssignedToLspJobException
     */
    public function assignJob(NewDefaultUserJobDto $dto): DefaultUserJob
    {
        $lspUser = $this->lspUserRepository->findByUserGuid($dto->userGuid);

        $this->assertLspUserCanBeAssignedToJobType($lspUser, $dto->type);

        $job = new DefaultUserJob();
        $job->setCustomerId($dto->customerId);
        $job->setUserGuid($dto->userGuid);
        $job->setSourceLang($dto->sourceLanguageId);
        $job->setTargetLang($dto->targetLanguageId);
        $job->setWorkflow($dto->workflow->workflow);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);
        $job->setDeadlineDate($dto->deadline);
        $job->setTrackchangesShow((int) $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps);
        $job->setTrackchangesShowAll((int) $dto->trackChangesRights->canSeeAllTrackChanges);
        $job->setTrackchangesAcceptReject((int) $dto->trackChangesRights->canAcceptOrRejectTrackChanges);

        $job->validate();

        $this->defaultUserJobRepository->save($job);

        return $job;
    }

    /**
     * @throws OnlyCoordinatorCanBeAssignedToLspJobException
     */
    private function assertLspUserCanBeAssignedToJobType(?LspUser $lspUser, TypeEnum $type): void
    {
        if (TypeEnum::Lsp !== $type) {
            return;
        }

        if (null === $lspUser || ! $lspUser->isCoordinator()) {
            throw new OnlyCoordinatorCanBeAssignedToLspJobException();
        }
    }
}
