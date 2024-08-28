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

namespace MittagQI\Translate5\LSP;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LSP\DTO\UpdateData;
use MittagQI\Translate5\LSP\Event\CustomerAssignedToLspEvent;
use MittagQI\Translate5\LSP\Event\CustomerUnassignedFromLspEvent;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToJobCoordinatorException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\UserRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

/**
 * @template JC of array{guid: string, name: string}
 * @template U of array{id: int, name: string}
 * @template C of array{id: int, name: string}
 * @template LspRow of array{id: int, name: string, description: string, coordinators: JC[], users: U[], customers: C[]}
 */
class LspService
{
    public function __construct(
        private readonly LspRepository $lspRepository,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public static function create(): self
    {
        $lspRepository = LspRepository::create();

        return new self(
            $lspRepository,
            new JobCoordinatorRepository($lspRepository, new LspUserRepository()),
            EventDispatcher::create(),
            new UserRepository(),
        );
    }

    public function doesUserHaveAccessToLsp(ZfExtended_Models_User $user, LanguageServiceProvider $lsp): bool
    {
        $roles = $user->getRoles();

        $coordinator = $this->jobCoordinatorRepository->findByUser($user);

        return array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)
            || (in_array(Roles::PM, $roles) && $lsp->isDirectLsp())
            || (null !== $coordinator && $lsp->isSubLspOf($coordinator->lsp));
    }

    public function getLsp(int $id): LanguageServiceProvider
    {
        return $this->lspRepository->get($id);
    }

    /**
     * @param iterable<Customer> $customers
     * @throws CustomerDoesNotBelongToJobCoordinatorException
     */
    public function validateCustomersAreSubsetForCoordinator(JobCoordinator $coordinator, iterable $customers): void
    {
        $coordinatorCustomers = $coordinator->user->getCustomersArray();

        foreach ($customers as $customer) {
            if (! in_array($customer->getId(), $coordinatorCustomers)) {
                throw new CustomerDoesNotBelongToJobCoordinatorException((int) $customer->getId(), $coordinator->guid);
            }
        }
    }

    /**
     * @param iterable<Customer> $customers
     * @throws CustomerDoesNotBelongToLspException
     */
    public function validateCustomersAreSubsetForLSP(LanguageServiceProvider $lsp, iterable $customers): void
    {
        $lspCustomers = $this->lspRepository->getCustomers($lsp);
        $lspCustomersIds = [];

        foreach ($lspCustomers as $customer) {
            $lspCustomersIds[] = (int) $customer->getId();
        }

        foreach ($customers as $customer) {
            if (! in_array($customer->getId(), $lspCustomersIds)) {
                throw new CustomerDoesNotBelongToLspException((int) $customer->getId(), (int) $lsp->getId());
            }
        }
    }

    /**
     * @return LspRow[]
     */
    public function getViewListFor(ZfExtended_Models_User $user): array
    {
        $roles = $user->getRoles();

        if (array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)) {
            return $this->buildViewListData($user, $this->lspRepository->getAll());
        }

        if (in_array(Roles::PM, $roles)) {
            return $this->buildViewListData($user, $this->lspRepository->getForPmRole());
        }

        if (! in_array(Roles::JOB_COORDINATOR, $roles)) {
            return [];
        }

        try {
            return $this->buildViewListData(
                $user,
                $this->lspRepository->getForJobCoordinator(
                    $this->jobCoordinatorRepository->getByUser($user)
                )
            );
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return [];
        }
    }

    /**
     * @return LspRow
     */
    public function buildViewData(ZfExtended_Models_User $viewer, LanguageServiceProvider $lsp): array
    {
        $coordinators = $this->jobCoordinatorRepository->getByLSP($lsp);
        /**
         * @var array<array{guid: string, name: string}> $coordinatorData
         */
        $coordinatorData = [];

        foreach ($coordinators as $coordinator) {
            $coordinatorData[] = [
                'guid' => $coordinator->guid,
                'name' => $coordinator->user->getUsernameLong(),
            ];
        }

        $users = $this->lspRepository->getUsers($lsp);
        /**
         * @var array<array{id: int, name: string}> $usersData
         */
        $usersData = [];

        foreach ($users as $user) {
            $usersData[] = [
                'id' => (int) $user->getId(),
                'name' => $user->getUsernameLong(),
            ];
        }

        $customers = $this->lspRepository->getCustomers($lsp);
        /**
         * @var array<array{id: int, name: string}> $customersData
         */
        $customersData = [];

        foreach ($customers as $customer) {
            $customersData[] = [
                'id' => (int) $customer->getId(),
                'name' => $customer->getName(),
            ];
        }

        return [
            'id' => (int) $lsp->getId(),
            'name' => $lsp->getName(),
            'canEdit' => $this->doesUserHaveAccessToLsp($viewer, $lsp),
            'canDelete' => $this->doesUserHaveAccessToLsp($viewer, $lsp),
            'description' => $lsp->getDescription(),
            'coordinators' => $coordinatorData,
            'users' => $usersData,
            'customers' => $customersData,
        ];
    }

    public function createLsp(string $name, ?string $description, ?JobCoordinator $coordinator): LanguageServiceProvider
    {
        $lsp = ZfExtended_Factory::get(LanguageServiceProvider::class);
        $lsp->setName($name);
        $lsp->setDescription($description);

        if (null !== $coordinator) {
            $lsp->setParentId((int) $coordinator->lsp->getId());
        }

        $this->lspRepository->save($lsp);

        return $lsp;
    }

    public function assignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = ZfExtended_Factory::get(LanguageServiceProviderCustomer::class);
        $lspCustomer->setLspId((int) $lsp->getId());
        $lspCustomer->setCustomerId($customer->getId());

        $this->lspRepository->saveCustomerAssignment($lspCustomer);

        $this->eventDispatcher->dispatch(new CustomerAssignedToLspEvent($lsp, $customer));
    }

    public function unassignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = $this->lspRepository->findCustomerAssignment($lsp, $customer);

        if (! $lspCustomer) {
            return;
        }

        $this->lspRepository->deleteCustomerAssignment($lspCustomer);

        $this->eventDispatcher->dispatch(new CustomerUnassignedFromLspEvent($lsp, $customer));
    }

    /**
     * @throws CustomerDoesNotBelongToLspException
     */
    public function updateLsp(LanguageServiceProvider $lsp, UpdateData $data): void
    {
        if (! $lsp->isDirectLsp() && ! empty($data->customers)) {
            $parentLsp = $this->getLsp((int) $lsp->getParentId());
            $this->validateCustomersAreSubsetForLSP($parentLsp, $data->customers);
        }

        $lsp->setName($data->name);
        $lsp->setDescription($data->description);

        $this->lspRepository->save($lsp);

        $newCustomerIdsSet = array_map(fn (Customer $customer) => $customer->getId(), $data->customers);

        $lspCustomers = $this->lspRepository->getCustomers($lsp);
        $lspCustomersIds = [];

        foreach ($lspCustomers as $customer) {
            if (! in_array($customer->getId(), $newCustomerIdsSet)) {
                $this->unassignCustomer($lsp, $customer);

                continue;
            }

            $lspCustomersIds[] = $customer->getId();
        }

        foreach ($data->customers as $customer) {
            if (! in_array($customer->getId(), $lspCustomersIds)) {
                $this->assignCustomer($lsp, $customer);
            }
        }
    }

    public function deleteLsp(LanguageServiceProvider $lsp): void
    {
        $lspUsers = $this->lspRepository->getUsers($lsp);

        foreach ($lspUsers as $user) {
            $this->userRepository->delete($user);
        }

        foreach ($this->lspRepository->getSubLspList($lsp) as $subLsp) {
            $this->deleteLsp($subLsp);
        }

        $this->lspRepository->delete($lsp);
    }

    /**
     * @param iterable<LanguageServiceProvider> $lsps
     * @return LspRow[]
     */
    private function buildViewListData(ZfExtended_Models_User $viewer, iterable $lsps): array
    {
        $data = [];

        foreach ($lsps as $lsp) {
            $data[] = $this->buildViewData($viewer, $lsp);
        }

        return $data;
    }
}
