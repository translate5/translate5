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

namespace MittagQI\Translate5\CoordinatorGroup;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupActionPermissionAssert;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;

/**
 * @template JC of array{guid: string, name: string}
 * @template U of array{id: int, name: string}
 * @template C of array{id: int, name: string}
 * @template CoordinatorGroupRow of array{
 *     id: int,
 *     parentId: int|null,
 *     name: string,
 *     description: string,
 *     canDelete: bool,
 *     canEdit: bool,
 *     coordinators: JC[],
 *     users: U[],
 *     customers: C[]
 * }
 */
class CoordinatorGroupViewDataProvider
{
    public function __construct(
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly ActionPermissionAssertInterface $coordinatorGroupPermissionAssert,
        private readonly ActionPermissionAssertInterface $customerActionPermissionAssert,
        private readonly ActionPermissionAssertInterface $userActionPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupRepository::create(),
            CoordinatorGroupUserRepository::create(),
            JobCoordinatorRepository::create(),
            CoordinatorGroupActionPermissionAssert::create(),
            CustomerActionPermissionAssert::create(),
            UserActionPermissionAssert::create(),
        );
    }

    /**
     * @return CoordinatorGroupRow[]
     */
    public function getViewListFor(User $viewer): array
    {
        $data = [];

        foreach ($this->coordinatorGroupRepository->getAll() as $coordinatorGroup) {
            try {
                $data[] = $this->buildViewData($viewer, $coordinatorGroup);
            } catch (PermissionExceptionInterface) {
                continue;
            }
        }

        return $data;
    }

    /**
     * @return CoordinatorGroupRow
     * @throws PermissionExceptionInterface
     */
    public function buildViewData(User $viewer, CoordinatorGroup $coordinatorGroup): array
    {
        $context = new PermissionAssertContext($viewer);
        $this->coordinatorGroupPermissionAssert->assertGranted(
            CoordinatorGroupAction::Read,
            $coordinatorGroup,
            $context
        );

        $coordinators = $this->jobCoordinatorRepository->getByCoordinatorGroup($coordinatorGroup);
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

        $users = $this->coordinatorGroupUserRepository->getUsers((int) $coordinatorGroup->getId());
        /**
         * @var array<array{id: int, name: string}> $usersData
         */
        $usersData = [];

        foreach ($users as $user) {
            try {
                $this->userActionPermissionAssert->assertGranted(
                    UserAction::Read,
                    $user,
                    $context
                );

                $usersData[] = [
                    'id' => (int) $user->getId(),
                    'name' => $user->getUsernameLong(),
                ];
            } catch (PermissionExceptionInterface) {
                continue;
            }
        }

        $customers = $this->coordinatorGroupRepository->getCustomers($coordinatorGroup);
        /**
         * @var array<array{id: int, name: string}> $customersData
         */
        $customersData = [];

        foreach ($customers as $customer) {
            try {
                $this->customerActionPermissionAssert->assertGranted(
                    CustomerAction::Read,
                    $customer,
                    new PermissionAssertContext($viewer)
                );

                $customersData[] = [
                    'id' => (int) $customer->getId(),
                    'name' => $customer->getName(),
                ];
            } catch (PermissionExceptionInterface) {
                continue;
            }
        }

        return [
            'id' => (int) $coordinatorGroup->getId(),
            'parentId' => $coordinatorGroup->getParentId() ? (int) $coordinatorGroup->getParentId() : null,
            'name' => $coordinatorGroup->getName(),
            'canEdit' => $this->userCanEditCoordinatorGroup($viewer, $coordinatorGroup),
            'canDelete' => $this->userCanDeleteCoordinatorGroup($viewer, $coordinatorGroup),
            'description' => $coordinatorGroup->getDescription(),
            'coordinators' => $coordinatorData,
            'users' => $usersData,
            'customers' => $customersData,
        ];
    }

    private function userCanEditCoordinatorGroup(User $viewer, CoordinatorGroup $coordinatorGroup): bool
    {
        return $this->coordinatorGroupPermissionAssert->isGranted(
            CoordinatorGroupAction::Update,
            $coordinatorGroup,
            new PermissionAssertContext($viewer)
        );
    }

    private function userCanDeleteCoordinatorGroup(User $viewer, CoordinatorGroup $coordinatorGroup): bool
    {
        return $this->coordinatorGroupPermissionAssert->isGranted(
            CoordinatorGroupAction::Delete,
            $coordinatorGroup,
            new PermissionAssertContext($viewer)
        );
    }
}
