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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation;

use MittagQI\Translate5\DefaultJobAssignment\Contract\CreateDefaultLspJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\Contract\CreateDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Exception\NotLspCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DTO\NewDefaultLspJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\CreateDefaultUserJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\DefaultLspJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use Throwable;

class CreateDefaultLspJobOperation implements CreateDefaultLspJobOperationInterface
{
    public function __construct(
        private readonly DefaultLspJobRepository $defaultLspJobRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly LspCustomerAssociationValidator $lspCustomerAssociationValidator,
        private readonly CreateDefaultUserJobOperationInterface $createDefaultUserJobOperation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            DefaultLspJobRepository::create(),
            JobCoordinatorRepository::create(),
            LspCustomerAssociationValidator::create(),
            CreateDefaultUserJobOperation::create(),
        );
    }

    /**
     * @throws DefaultLspJobAlreadyExistsException
     * @throws NotLspCustomerException
     * @throws OnlyCoordinatorCanBeAssignedToLspJobException
     */
    public function assignJob(NewDefaultLspJobDto $dto): DefaultLspJob
    {
        $coordinator = $this->coordinatorRepository->findByUserGuid($dto->userGuid);

        if ($coordinator === null) {
            throw new OnlyCoordinatorCanBeAssignedToLspJobException();
        }

        $lsp = $coordinator->lsp;

        try {
            $this->lspCustomerAssociationValidator->assertCustomersAreSubsetForLSP(
                (int) $lsp->getId(),
                $dto->customerId
            );
        } catch (CustomerDoesNotBelongToLspException) {
            throw new NotLspCustomerException();
        }

        $userJob = $this->createDefaultUserJobOperation->assignJob(NewDefaultUserJobDto::fromDefaultLspJobDto($dto));

        $job = new DefaultLspJob();
        $job->setCustomerId($dto->customerId);
        $job->setLspId((int) $coordinator->lsp->getId());
        $job->setSourceLang($dto->sourceLanguageId);
        $job->setTargetLang($dto->targetLanguageId);
        $job->setWorkflow($dto->workflow->workflow);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);
        $job->setDataJobId((int) $userJob->getId());

        $this->defaultLspJobRepository->save($job);

        return $job;
    }
}
