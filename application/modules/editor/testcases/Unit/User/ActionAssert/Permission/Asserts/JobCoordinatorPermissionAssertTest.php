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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\JobCoordinatorPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NoAccessToUserException;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JobCoordinatorPermissionAssertTest extends TestCase
{
    private CoordinatorGroupUserRepositoryInterface|MockObject $coordinatorGroupUserRepository;

    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private JobCoordinatorPermissionAssert $assert;

    public function setUp(): void
    {
        $this->coordinatorGroupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);

        $this->assert = new JobCoordinatorPermissionAssert(
            $this->coordinatorRepository,
            $this->coordinatorGroupUserRepository,
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

    public function testAssertGrantedForSameUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $context = new PermissionAssertContext($user);

        $this->coordinatorRepository->expects(self::never())->method('findByUser');

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedWhenAuthUserIsNotCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('getRoles')->willReturn(['some-role']);

        $context = new PermissionAssertContext($manager);

        $this->coordinatorGroupUserRepository->expects(self::never())->method('findByUser');

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNotGrantedWhenAuthUserIsCoordinatorAndUserNotCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('getRoles')->willReturn([Roles::PM]);

        $context = new PermissionAssertContext($manager);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn(null);

        $this->coordinatorRepository->method('findByUser')->willReturn($this->createMock(JobCoordinator::class));

        $this->expectException(NoAccessToUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertNotGrantedWhenAuthCoordinatorIsNotSupervisorOfCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('getRoles')->willReturn([Roles::PM]);

        $context = new PermissionAssertContext($manager);

        $groupUser = $this->createMock(CoordinatorGroupUser::class);

        $this->coordinatorGroupUserRepository->method('findByUser')->willReturn($groupUser);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(false);

        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->expectException(NoAccessToUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }
}
