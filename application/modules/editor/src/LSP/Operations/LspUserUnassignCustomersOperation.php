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

namespace MittagQI\Translate5\LSP\Operations;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssert;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\LSP\Contract\LspUserUnassignCustomersOperationInterface;
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\Exception\CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedLspJobsException;
use MittagQI\Translate5\LSP\Exception\CustomerCanNotBeUnAssignedFromLspUserAsItHasRelatedJobsException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\User\Contract\UserUnassignCustomersOperationInterface;
use MittagQI\Translate5\User\Operations\UserUnassignCustomersOperation;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\UserJob\Operation\DeleteUserJobOperation;

final class LspUserUnassignCustomersOperation implements LspUserUnassignCustomersOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly LspJobRepository $lspJobRepository,
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
            LspJobRepository::create(),
            CustomerRepository::create(),
            UserJobActionFeasibilityAssert::create(),
            UserUnassignCustomersOperation::create(),
            DeleteUserJobOperation::create(),
        );
    }

    public function unassignCustomers(LspUser $lspUser, int ...$customerIds): void
    {
        if (empty($customerIds)) {
            return;
        }

        $this->assertCustomersCanBeUnAssignedFromCoordinator($lspUser, $customerIds);

        foreach ($customerIds as $customerId) {
            $this->assertCustomersCanBeUnAssignedFromLspUser($lspUser, $customerId);
        }

        $this->deleteJobAssignments($lspUser, ...$customerIds);

        $this->userUnassignCustomerOperation->unassignCustomers($lspUser->user, ...$customerIds);
    }

    public function forceUnassignCustomers(LspUser $lspUser, int ...$customerIds): void
    {
        if (empty($customerIds)) {
            return;
        }

        $this->assertCustomersCanBeUnAssignedFromCoordinator($lspUser, $customerIds);

        $this->deleteJobAssignments($lspUser, ...$customerIds);

        $this->userUnassignCustomerOperation->unassignCustomers($lspUser->user, ...$customerIds);
    }

    private function deleteJobAssignments(LspUser $lspUser, int ...$customerIds): void
    {
        foreach ($this->getJobsIterator($lspUser->user->getUserGuid(), ...$customerIds) as $job) {
            $this->deleteUserJobAssignmentOperation->forceDelete($job);
        }
    }

    /**
     * @throws CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedLspJobsException
     */
    private function assertCustomersCanBeUnAssignedFromCoordinator(LspUser $lspUser, array $customerIds): void
    {
        if (! $this->isCoordinator($lspUser)) {
            return;
        }

        foreach ($customerIds as $customerId) {
            if ($this->coordinatorHasLspJobsOfCustomer($lspUser->user->getUserGuid(), $customerId)) {
                $customer = $this->customerRepository->get($customerId);

                throw new CustomerCanNotBeUnAssignedFromCoordinatorAsItHasRelatedLspJobsException(
                    (int) $customer->getId(),
                    $customer->getName(),
                    $lspUser->user->getUserGuid(),
                );
            }
        }
    }

    private function isCoordinator(LspUser $lspUser): bool
    {
        try {
            JobCoordinator::fromLspUser($lspUser);

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

    private function coordinatorHasLspJobsOfCustomer(string $userGuid, int $customerId): bool
    {
        return $this->lspJobRepository->coordinatorHasLspJobsOfCustomer($userGuid, $customerId);
    }

    /**
     * @throws CustomerCanNotBeUnAssignedFromLspUserAsItHasRelatedJobsException
     */
    private function assertCustomersCanBeUnAssignedFromLspUser(LspUser $lspUser, int $customerId): void
    {
        try {
            $jobs = $this->userJobRepository->getUserJobsOfCustomer($lspUser->user->getUserGuid(), $customerId);

            foreach ($jobs as $job) {
                $this->userJobActionFeasibilityAssert->assertAllowed(Action::Delete, $job);
            }
        } catch (FeasibilityExceptionInterface) {
            $customer = $this->customerRepository->get($customerId);

            throw new CustomerCanNotBeUnAssignedFromLspUserAsItHasRelatedJobsException(
                (int) $customer->getId(),
                $customer->getName(),
                $lspUser->user->getUserGuid(),
            );
        }
    }
}
