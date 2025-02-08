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

use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\SeeAllUsersPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;

class SeeAllUsersPermissionAssertTest extends TestCase
{
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
        $auditor = new SeeAllUsersPermissionAssert(
            $this->createMock(ZfExtended_Acl::class),
            $this->createMock(CoordinatorGroupUserRepositoryInterface::class)
        );
        $this->assertEquals($expected, $auditor->supports($action));
    }

    public function testAssertGrantedCanSeeAllUsers(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $manager->method('getRoles')->willReturn(['role1', 'role2']);
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);

        $acl->expects(self::once())
            ->method('isInAllowedRoles')
            ->with(
                $manager->getRoles(),
                'system',
                'seeAllUsers'
            )
            ->willReturn(true);

        $groupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $groupUserRepository->method('findByUser')->willReturn(null);

        $auditor = new SeeAllUsersPermissionAssert($acl, $groupUserRepository);
        $auditor->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedSameUser(): void
    {
        $authUser = $this->createMock(User::class);
        $authUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $context = new PermissionAssertContext($authUser);

        $acl = $this->createMock(ZfExtended_Acl::class);

        $acl->expects($this->never())->method('isInAllowedRoles');

        $groupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $groupUserRepository->method('findByUser')->willReturn(null);

        $auditor = new SeeAllUsersPermissionAssert($acl, $groupUserRepository);
        $auditor->assertGranted(UserAction::Read, $authUser, $context);
    }

    public function testAssertGrantedOnCoordinatorGroupUser(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->willReturn(false);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $groupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $groupUserRepository->method('findByUser')->willReturn($this->createMock(CoordinatorGroupUser::class));

        $auditor = new SeeAllUsersPermissionAssert($acl, $groupUserRepository);
        $auditor->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccess(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->willReturn(false);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $groupUserRepository = $this->createMock(CoordinatorGroupUserRepositoryInterface::class);
        $groupUserRepository->method('findByUser')->willReturn(null);

        $auditor = new SeeAllUsersPermissionAssert($acl, $groupUserRepository);
        $this->expectException(NoAccessException::class);
        $auditor->assertGranted(UserAction::Update, $user, $context);
    }
}
