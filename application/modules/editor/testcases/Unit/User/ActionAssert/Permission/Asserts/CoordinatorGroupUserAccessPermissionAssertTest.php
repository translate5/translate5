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

namespace MittagQI\Translate5\Test\Unit\User\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\CoordinatorGroupUserAccessPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleCoordinatorGroupUserException;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoordinatorGroupUserAccessPermissionAssertTest extends TestCase
{
    private CoordinatorGroupUserRepositoryInterface|MockObject $coordinatorGroupUserRepository;

    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private CoordinatorGroupRepositoryInterface|MockObject $coordinatorGroupRepository;

    private CoordinatorGroupUserAccessPermissionAssert $assert;

    public function setUp(): void
    {
        $this->coordinatorGroupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->coordinatorGroupRepository = $this->createMock(CoordinatorGroupRepositoryInterface::class);

        $this->assert = new CoordinatorGroupUserAccessPermissionAssert(
            $this->coordinatorGroupUserRepository,
            $this->coordinatorRepository,
            $this->coordinatorGroupRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [UserAction::Delete, true];
        yield [UserAction::Update, true];
        yield [UserAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(UserAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->assert->supports($action));
    }

    public function testAssertGrantedAdmin(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isAdmin')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $this->coordinatorRepository->expects($this->never())->method('findByUser');

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccessForNotManagerRole(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);

        $context = new PermissionAssertContext($actor);

        $groupUser = $this->createMock(CoordinatorGroupUser::class);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedWhenNotCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);

        $context = new PermissionAssertContext($actor);

        $this->coordinatorGroupUserRepository->expects(self::once())->method('findByUser')->willReturn(null);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNoAccessGrantedForPmToNotDirectCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(false);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNoAccessGrantedForClientPmToNotDirectCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isPm')->willReturn(false);
        $actor->method('isClientPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(false);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNoAccessGrantedForPmToNotDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isAdmin')->willReturn(false);
        $actor->method('isCoordinator')->willReturn(false);
        $actor->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(false);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNoAccessGrantedForClientPmToNotDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isAdmin')->willReturn(false);
        $actor->method('isCoordinator')->willReturn(false);
        $actor->method('isPm')->willReturn(false);
        $actor->method('isClientPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(false);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNoAccessGrantedForPmToDirectCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
            ['getUserGuid', [], 'user-guid'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(true);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNoAccessGrantedForClientPmToDirectCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
            ['getUserGuid', [], 'user-guid'],
        ]);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isPm')->willReturn(false);
        $actor->method('isClientPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(true);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedForPmToDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isAdmin')->willReturn(false);
        $actor->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(true);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
        self::assertTrue(true);
    }

    public function testAssertGrantedForClientPmToDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isAdmin')->willReturn(false);
        $actor->method('isPm')->willReturn(false);
        $actor->method('isClientPm')->willReturn(true);
        $actor->method('getCustomersArray')->willReturn([1, 2, 3]);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(true);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->coordinatorGroupRepository->method('getCustomerIds')->willReturn([3]);

        $this->assert->assertGranted(UserAction::Read, $user, $context);
        self::assertTrue(true);
    }

    public function testAssertNoAccessGrantedForClientPmToMutateUserIfNoUserRoleGranted(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor = $this->createMock(User::class);
        $actor->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $actor->method('isAdmin')->willReturn(false);
        $actor->method('isPm')->willReturn(false);
        $actor->method('isClientPm')->willReturn(true);
        $actor->method('getCustomersArray')->willReturn([1, 2, 3]);

        $context = new PermissionAssertContext($actor);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isTopRankGroup')->willReturn(true);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $this->coordinatorGroupRepository->method('getCustomerIds')->willReturn([3]);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccessForCoordinatorToNotSameCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $actor = $this->createMock(User::class);
        $context = new PermissionAssertContext($actor);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
            ['getId', [], '12'],
        ]);

        $actor->method('__call')->willReturnMap([
            ['getUserGuid', [], 'manager-guid'],
            ['getId', [], '14'],
        ]);
        $actor->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(false);

        $group = $this->createMock(CoordinatorGroup::class);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedForCoordinatorToSameCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $actor = $this->createMock(User::class);
        $context = new PermissionAssertContext($actor);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);

        $actor->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(true);

        $group = $this->createMock(CoordinatorGroup::class);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->assert->assertGranted(UserAction::Update, $user, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedForCoordinatorToSameCoordinatorGroupCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $actor = $this->createMock(User::class);
        $context = new PermissionAssertContext($actor);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(true);

        $group = $this->createMock(CoordinatorGroup::class);
        $groupUser = new CoordinatorGroupUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->assert->assertGranted(UserAction::Update, $user, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedForCoordinatorToDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $actor = $this->createMock(User::class);
        $context = new PermissionAssertContext($actor);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(true);

        $group = $this->createMock(CoordinatorGroup::class);
        $groupUser = new CoordinatorGroupUser('user-guid', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
        self::assertTrue(true);
    }

    public function testAssertGrantedNoAccessForCoordinatorToNotDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $actor = $this->createMock(User::class);
        $context = new PermissionAssertContext($actor);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
            ['getId', [], '12'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $actor->method('__call')->willReturnMap([
            ['getUserGuid', [], 'manager-guid'],
            ['getId', [], '14'],
        ]);
        $actor->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(false);

        $group = $this->createMock(CoordinatorGroup::class);
        $groupUser = new CoordinatorGroupUser('user-guid', $user, $group);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->expectException(NotAccessibleCoordinatorGroupUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }
}
