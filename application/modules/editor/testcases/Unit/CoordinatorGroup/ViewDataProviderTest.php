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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup;

use BackedEnum;
use editor_Models_Customer_Customer;
use Exception;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupViewDataProvider;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewDataProviderTest extends TestCase
{
    private CoordinatorGroupRepositoryInterface|MockObject $coordinatorGroupRepository;

    private CoordinatorGroupUserRepositoryInterface|MockObject $coordinatorGroupUserRepository;

    private JobCoordinatorRepository|MockObject $jobCoordinatorRepository;

    private ActionPermissionAssertInterface|MockObject $coordinatorGroupPermissionAssert;

    private ActionPermissionAssertInterface|MockObject $customerActionPermissionAssert;

    private ActionPermissionAssertInterface|MockObject $userActionPermissionAssert;

    private CoordinatorGroupViewDataProvider $viewDataProvider;

    public function setUp(): void
    {
        $this->coordinatorGroupRepository = $this->createMock(CoordinatorGroupRepositoryInterface::class);
        $this->coordinatorGroupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $this->jobCoordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->coordinatorGroupPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->customerActionPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->userActionPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);

        $this->viewDataProvider = new CoordinatorGroupViewDataProvider(
            $this->coordinatorGroupRepository,
            $this->coordinatorGroupUserRepository,
            $this->jobCoordinatorRepository,
            $this->coordinatorGroupPermissionAssert,
            $this->customerActionPermissionAssert,
            $this->userActionPermissionAssert,
        );
    }

    public function testGetViewListForReturnEmptyListAsViewerHasNoAccessToCoordinatorGroup(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);

        $this->coordinatorGroupRepository->method('getAll')->willReturn([$group]);

        $this->coordinatorGroupPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException(
                $this->createMock(PermissionExceptionInterface::class)
            )
        ;

        $viewer = $this->createMock(User::class);

        self::assertEmpty($this->viewDataProvider->getViewListFor($viewer));
    }

    public function testGetViewListWithoutRestrictedCoordinatorGroup(): void
    {
        $group1 = $this->createMock(CoordinatorGroup::class);
        $group2 = $this->createMock(CoordinatorGroup::class);
        $group2->method('__call')->willReturnMap([
            ['getId', [], 20],
            ['getName', [], 'CoordinatorGroup name'],
            ['getDescription', [], 'CoordinatorGroup description'],
        ]);

        $this->coordinatorGroupRepository->method('getAll')->willReturn([$group1, $group2]);

        $permissionException = $this->createMock(PermissionExceptionInterface::class);

        $this->coordinatorGroupPermissionAssert
            ->method('assertGranted')
            ->with(
                self::isInstanceOf(CoordinatorGroupAction::class),
                self::callback(
                    fn ($group) => $group === $group1 ? throw $permissionException : true
                ),
            )
        ;

        $this->coordinatorGroupPermissionAssert
            ->method('isGranted')
            ->with(
                self::isInstanceOf(CoordinatorGroupAction::class),
                self::callback(
                    fn ($group) => $group === $group2
                ),
            )
            ->willReturn(true);
        ;

        $viewer = $this->createMock(User::class);

        $users = $this->viewDataProvider->getViewListFor($viewer);

        self::assertCount(1, $users);
        self::assertEquals(
            [
                'id' => (int) $group2->getId(),
                'name' => $group2->getName(),
                'canEdit' => true,
                'canDelete' => true,
                'description' => $group2->getDescription(),
                'coordinators' => [],
                'users' => [],
                'customers' => [],
                'parentId' => null,
            ],
            $users[0]
        );
    }

    public function testBuildViewData(): void
    {
        $permissionException = $this->createMock(PermissionExceptionInterface::class);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('__call')->willReturnMap([
            ['getId', [], 10],
            ['getName', [], 'Coordinator Group name'],
            ['getDescription', [], 'Coordinator GroupJ description'],
        ]);

        $user1 = $this->createMock(User::class);
        $user1->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid-1'],
            ['getUsernameLong', [], 'user-1'],
        ]);
        $coordinator1 = new JobCoordinator($user1->getUserGuid(), $user1, $group);

        $user2 = $this->createMock(User::class);
        $user2->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid-2'],
            ['getUsernameLong', [], 'user-2'],
        ]);

        $user3 = $this->createMock(User::class);

        $this->userActionPermissionAssert
            ->method('assertGranted')
            ->with(
                self::isInstanceOf(UserAction::class),
                self::callback(
                    fn ($user) => $user === $user3 ? throw $permissionException : true
                ),
            )
        ;

        $customer1 = $this->createMock(editor_Models_Customer_Customer::class);
        $customer1->method('__call')->willReturnMap([
            ['getId', [], 11],
            ['getName', [], 'customer-1'],
        ]);
        $customer2 = $this->createMock(editor_Models_Customer_Customer::class);
        $customer2->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getName', [], 'customer-2'],
        ]);

        $customer3 = $this->createMock(editor_Models_Customer_Customer::class);

        $this->customerActionPermissionAssert
            ->method('assertGranted')
            ->with(
                self::isInstanceOf(CustomerAction::class),
                self::callback(
                    fn ($customer) => $customer === $customer3 ? throw $permissionException : true
                ),
            )
        ;

        $this->jobCoordinatorRepository->method('getByCoordinatorGroup')->willReturn([$coordinator1]);

        $this->coordinatorGroupUserRepository->method('getUsers')->willReturn([$user1, $user2, $user3]);

        $this->coordinatorGroupRepository->method('getAll')->willReturn([$group]);
        $this->coordinatorGroupRepository->method('getCustomers')->willReturn([$customer1, $customer2, $customer3]);

        $groupPermissionAssert = new class() implements ActionPermissionAssertInterface {
            public function assertGranted(
                \BackedEnum $action,
                object $object,
                PermissionAssertContext $context,
            ): void {
                if ($action === CoordinatorGroupAction::Read) {
                    return;
                }

                throw new class() extends Exception implements PermissionExceptionInterface {
                };
            }

            public function isGranted(BackedEnum $action, object $object, PermissionAssertContext $context): bool
            {
                return $action === CoordinatorGroupAction::Update;
            }
        };

        $viewer = $this->createMock(User::class);

        $viewDataProvider = new CoordinatorGroupViewDataProvider(
            $this->coordinatorGroupRepository,
            $this->coordinatorGroupUserRepository,
            $this->jobCoordinatorRepository,
            $groupPermissionAssert,
            $this->customerActionPermissionAssert,
            $this->userActionPermissionAssert,
        );

        $expected = [
            'id' => (int) $group->getId(),
            'name' => $group->getName(),
            'canEdit' => true,
            'canDelete' => false,
            'description' => $group->getDescription(),
            'coordinators' => [
                [
                    'guid' => $coordinator1->guid,
                    'name' => $coordinator1->user->getUsernameLong(),
                ],
            ],
            'users' => [
                [
                    'id' => (int) $user1->getId(),
                    'name' => $user1->getUsernameLong(),
                ],
                [
                    'id' => (int) $user2->getId(),
                    'name' => $user2->getUsernameLong(),
                ],
            ],
            'customers' => [
                [
                    'id' => (int) $customer1->getId(),
                    'name' => $customer1->getName(),
                ],
                [
                    'id' => (int) $customer2->getId(),
                    'name' => $customer2->getName(),
                ],
            ],
            'parentId' => null,
        ];

        self::assertEquals($expected, $viewDataProvider->buildViewData($viewer, $group));
    }
}
