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

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssert;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupUnassignCustomerOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupUserUnassignCustomersOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupHasUnDeletableJobException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\DefaultJobAssignment\Contract\DeleteDefaultCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\Contract\DeleteDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\DeleteDefaultCoordinatorGroupJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DeleteDefaultUserJobOperation;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\ActionAssert\Feasibility\CoordinatorGroupJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\DeleteCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DeleteCoordinatorGroupJobOperation;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;

final class CoordinatorGroupUnassignCustomerOperation implements CoordinatorGroupUnassignCustomerOperationInterface
{
    public function __construct(
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly ActionFeasibilityAssert $coordinatorGroupJobActionFeasibilityAssert,
        private readonly DeleteCoordinatorGroupJobOperationInterface $deleteCoordinatorGroupJobOperation,
        private readonly CoordinatorGroupUserUnassignCustomersOperationInterface $coordinatorGroupUserUnassignCustomersOperation,
        private readonly DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository,
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly DeleteDefaultCoordinatorGroupJobOperationInterface $deleteDefaultCoordinatorGroupJobOperation,
        private readonly DeleteDefaultUserJobOperationInterface $deleteDefaultUserJobOperation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupRepository::create(),
            CoordinatorGroupJobRepository::create(),
            CoordinatorGroupUserRepository::create(),
            CoordinatorGroupJobActionFeasibilityAssert::create(),
            DeleteCoordinatorGroupJobOperation::create(),
            CoordinatorGroupUserUnassignCustomersOperation::create(),
            DefaultCoordinatorGroupJobRepository::create(),
            DefaultUserJobRepository::create(),
            DeleteDefaultCoordinatorGroupJobOperation::create(),
            DeleteDefaultUserJobOperation::create(),
        );
    }

    public function unassignCustomer(CoordinatorGroup $group, Customer $customer): void
    {
        $groupCustomer = $this->coordinatorGroupRepository->findCustomerConnection(
            (int) $group->getId(),
            (int) $customer->getId()
        );

        if (! $groupCustomer) {
            return;
        }

        $this->assertCoordinatorGroupJobsCanBeDeleted((int) $group->getId(), (int) $customer->getId());
        $this->deleteAssociationWithDependencies($group, $customer);
    }

    public function forceUnassignCustomer(CoordinatorGroup $group, Customer $customer): void
    {
        $groupCustomer = $this->coordinatorGroupRepository->findCustomerConnection(
            (int) $group->getId(),
            (int) $customer->getId()
        );

        if (! $groupCustomer) {
            return;
        }

        $this->deleteAssociationWithDependencies($group, $customer);
    }

    private function deleteAssociationWithDependencies(CoordinatorGroup $group, Customer $customer): void
    {
        foreach ($this->coordinatorGroupRepository->getSubCoordinatorGroupList($group) as $subGroup) {
            $this->forceUnassignCustomer($subGroup, $customer);
        }

        $defaultGroupJobs = $this->defaultCoordinatorGroupJobRepository->getDefaultCoordinatorGroupJobsOfGroupForCustomer(
            (int) $group->getId(),
            (int) $customer->getId(),
        );

        foreach ($defaultGroupJobs as $defaulGroupJob) {
            $this->deleteDefaultCoordinatorGroupJobOperation->delete($defaulGroupJob);
        }

        $defaultUserJobs = $this->defaultUserJobRepository->getDefaultJobsOfCustomerForUsersOfCoordinatorGroup(
            (int) $customer->getId(),
            (int) $group->getId(),
        );

        foreach ($defaultUserJobs as $defaultUserJob) {
            $this->deleteDefaultUserJobOperation->delete($defaultUserJob);
        }

        $coordinatorGroupJobs = $this->getCoordinatorGroupJobsIterator((int) $group->getId(), (int) $customer->getId());

        foreach ($coordinatorGroupJobs as $coordinatorGroupJob) {
            $this->deleteCoordinatorGroupJobOperation->forceDelete($coordinatorGroupJob);
        }

        foreach ($this->coordinatorGroupUserRepository->getCoordinatorGroupUsers($group) as $coordinatorGroupUser) {
            $this->coordinatorGroupUserUnassignCustomersOperation->forceUnassignCustomers(
                $coordinatorGroupUser,
                (int) $customer->getId()
            );
        }

        $this->coordinatorGroupRepository->deleteCustomerAssignment((int) $group->getId(), (int) $customer->getId());
    }

    private function assertCoordinatorGroupJobsCanBeDeleted(int $groupId, int $customerId): void
    {
        try {
            foreach ($this->getCoordinatorGroupJobsIterator($groupId, $customerId) as $job) {
                $this->coordinatorGroupJobActionFeasibilityAssert->assertAllowed(Action::Delete, $job);
            }
        } catch (FeasibilityExceptionInterface $e) {
            throw new CoordinatorGroupHasUnDeletableJobException(previous: $e);
        }
    }

    /**
     * @return iterable<CoordinatorGroupJob>
     */
    private function getCoordinatorGroupJobsIterator(int $groupId, int $customerId): iterable
    {
        return $this->coordinatorGroupJobRepository->getCoordinatorGroupJobsOfCustomer($groupId, $customerId);
    }
}
