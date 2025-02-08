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
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\DefaultJobAssignment\Contract\CreateDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Exception\NotCoordinatorGroupCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\DefaultUserJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;

class CreateDefaultUserJobOperation implements CreateDefaultUserJobOperationInterface
{
    public function __construct(
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly CoordinatorGroupCustomerAssociationValidator $coordinatorGroupCustomerAssociationValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            DefaultUserJobRepository::create(),
            CoordinatorGroupUserRepository::create(),
            CoordinatorGroupCustomerAssociationValidator::create(),
        );
    }

    /**
     * @throws DefaultUserJobAlreadyExistsException
     * @throws NotCoordinatorGroupCustomerException
     * @throws OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException
     */
    public function assignJob(NewDefaultUserJobDto $dto): DefaultUserJob
    {
        $groupUser = $this->coordinatorGroupUserRepository->findByUserGuid($dto->userGuid);

        if (null !== $groupUser) {
            try {
                $this->coordinatorGroupCustomerAssociationValidator->assertCustomersAreSubsetForCoordinatorGroup(
                    (int) $groupUser->group->getId(),
                    $dto->customerId
                );
            } catch (CustomerDoesNotBelongToCoordinatorGroupException) {
                throw new NotCoordinatorGroupCustomerException();
            }
        }

        $job = new DefaultUserJob();
        $job->setCustomerId($dto->customerId);
        $job->setUserGuid($dto->userGuid);
        $job->setSourceLang($dto->sourceLanguageId);
        $job->setTargetLang($dto->targetLanguageId);
        $job->setWorkflow($dto->workflow->workflow);
        $job->setWorkflowStepName($dto->workflow->workflowStepName);
        $job->setTrackchangesShow((int) $dto->trackChangesRights->canSeeTrackChangesOfPrevSteps);
        $job->setTrackchangesShowAll((int) $dto->trackChangesRights->canSeeAllTrackChanges);
        $job->setTrackchangesAcceptReject((int) $dto->trackChangesRights->canAcceptOrRejectTrackChanges);
        if (null !== $dto->deadline) {
            $job->setDeadlineDate($dto->deadline);
        }

        $job->validate();

        $this->defaultUserJobRepository->save($job);

        return $job;
    }
}
