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

namespace MittagQI\Translate5\CoordinatorGroup\Operations;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssert;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupUserUnassignCustomersOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedCoordinatorGroupJobsException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerCanNotBeUnAssignedFromCoordinatorGroupUserAsItHasRelatedJobsException;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\User\Contract\UserUnassignCustomersOperationInterface;
use MittagQI\Translate5\User\Operations\UserUnassignCustomersOperation;

final class CoordinatorGroupUserUnassignCustomersOperation implements CoordinatorGroupUserUnassignCustomersOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly ActionFeasibilityAssert $userJobActionFeasibilityAssert,
        private readonly UserUnassignCustomersOperationInterface $userUnassignCustomerOperation,
        private readonly DeleteUserJobOperationInterface $deleteUserJobAssignmentOperation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
            CustomerRepository::create(),
            UserJobActionFeasibilityAssert::create(),
            UserUnassignCustomersOperation::create(),
            DeleteUserJobOperation::create(),
        );
    }

    public function unassignCustomers(CoordinatorGroupUser $coordinatorGroupUser, int ...$customerIds): void
    {
        if (empty($customerIds)) {
            return;
        }

        $this->assertCustomersCanBeUnAssignedFromCoordinator($coordinatorGroupUser, $customerIds);

        foreach ($customerIds as $customerId) {
            $this->assertCustomersCanBeUnAssignedFromCoordinatorGroupUser($coordinatorGroupUser, $customerId);
        }

        $this->deleteJobAssignments($coordinatorGroupUser, ...$customerIds);

        $this->userUnassignCustomerOperation->unassignCustomers($coordinatorGroupUser->user, ...$customerIds);
    }

    public function forceUnassignCustomers(CoordinatorGroupUser $coordinatorGroupUser, int ...$customerIds): void
    {
        if (empty($customerIds)) {
            return;
        }

        $this->assertCustomersCanBeUnAssignedFromCoordinator($coordinatorGroupUser, $customerIds);

        $this->deleteJobAssignments($coordinatorGroupUser, ...$customerIds);

        $this->userUnassignCustomerOperation->unassignCustomers($coordinatorGroupUser->user, ...$customerIds);
    }

    private function deleteJobAssignments(CoordinatorGroupUser $coordinatorGroupUser, int ...$customerIds): void
    {
        foreach ($this->getJobsIterator($coordinatorGroupUser->user->getUserGuid(), ...$customerIds) as $job) {
            $this->deleteUserJobAssignmentOperation->forceDelete($job);
        }
    }

    /**
     * @throws CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedCoordinatorGroupJobsException
     */
    private function assertCustomersCanBeUnAssignedFromCoordinator(
        CoordinatorGroupUser $coordinatorGroupUser,
        array $customerIds
    ): void {
        if (! $this->isCoordinator($coordinatorGroupUser)) {
            return;
        }

        foreach ($customerIds as $customerId) {
            if ($this->coordinatorHasGroupJobsOfCustomer($coordinatorGroupUser->user->getUserGuid(), $customerId)) {
                $customer = $this->customerRepository->get($customerId);

                throw new CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedCoordinatorGroupJobsException(
                    (int) $customer->getId(),
                    $customer->getName(),
                    $coordinatorGroupUser->user->getUserGuid(),
                );
            }
        }
    }

    private function isCoordinator(CoordinatorGroupUser $coordinatorGroupUser): bool
    {
        try {
            JobCoordinator::fromCoordinatorGroupUser($coordinatorGroupUser);

            return true;
        } catch (CantCreateCoordinatorFromUserException) {
            return false;
        }
    }

    /**
     * @return iterable<UserJob>
     */
    private function getJobsIterator(string $userGuid, int ...$customerIds): iterable
    {
        foreach ($customerIds as $customerId) {
            yield from $this->userJobRepository->getUserJobsOfCustomer($userGuid, $customerId);
        }
    }

    private function coordinatorHasGroupJobsOfCustomer(string $userGuid, int $customerId): bool
    {
        return $this->coordinatorGroupJobRepository->coordinatorHasGroupJobsOfCustomer($userGuid, $customerId);
    }

    /**
     * @throws CustomerCanNotBeUnAssignedFromCoordinatorGroupUserAsItHasRelatedJobsException
     */
    private function assertCustomersCanBeUnAssignedFromCoordinatorGroupUser(
        CoordinatorGroupUser $coordinatorGroupUser,
        int $customerId
    ): void {
        try {
            $jobs = $this->userJobRepository->getUserJobsOfCustomer(
                $coordinatorGroupUser->user->getUserGuid(),
                $customerId
            );

            foreach ($jobs as $job) {
                $this->userJobActionFeasibilityAssert->assertAllowed(Action::Delete, $job);
            }
        } catch (FeasibilityExceptionInterface) {
            $customer = $this->customerRepository->get($customerId);

            throw new CustomerCanNotBeUnAssignedFromCoordinatorGroupUserAsItHasRelatedJobsException(
                (int) $customer->getId(),
                $customer->getName(),
                $coordinatorGroupUser->user->getUserGuid(),
            );
        }
    }
}
