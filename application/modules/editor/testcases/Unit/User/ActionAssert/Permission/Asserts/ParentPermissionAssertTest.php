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

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\ParentPermissionAssert;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_AuthenticationInterface;

class ParentPermissionAssertTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::DELETE, true];
        yield [Action::UPDATE, true];
        yield [Action::READ, true];
        yield [Action::CREATE, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $auditor = new ParentPermissionAssert(
            $this->createMock(ZfExtended_Acl::class),
            $this->createMock(ZfExtended_AuthenticationInterface::class),
            $this->createMock(LspUserRepositoryInterface::class)
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
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->with(
                ['role1', 'role2'],
                'system',
                'seeAllUsers'
            )
            ->willReturn(true);

        $authentication->expects($this->once())
            ->method('getUserRoles')
            ->willReturn(['role1', 'role2']);

        $user->expects($this->never())->method('hasParent');

        $lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $lspUserRepository->method('findByUser')->willReturn(null);

        $auditor = new ParentPermissionAssert($acl, $authentication, $lspUserRepository);
        $auditor->assertGranted($user, $context);
    }

    public function testAssertGrantedSameUser(): void
    {
        $authUser = $this->createMock(User::class);
        $authUser->expects($this->never())->method('hasParent');
        $authUser->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $context = new PermissionAssertContext($authUser);

        $acl = $this->createMock(ZfExtended_Acl::class);
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $acl->expects($this->never())->method('isInAllowedRoles');

        $lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $lspUserRepository->method('findByUser')->willReturn(null);

        $auditor = new ParentPermissionAssert($acl, $authentication, $lspUserRepository);
        $auditor->assertGranted($authUser, $context);
    }

    public function testAssertGrantedOnLspUser(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->willReturn(false);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $lspUserRepository->method('findByUser')->willReturn($this->createMock(LspUser::class));

        $auditor = new ParentPermissionAssert($acl, $authentication, $lspUserRepository);
        $auditor->assertGranted($user, $context);
    }

    public function testAssertGrantedParentUser(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->willReturn(false);

        $user->expects($this->once())
            ->method('hasParent')
            ->with($manager->getId())
            ->willReturn(true);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $lspUserRepository->method('findByUser')->willReturn(null);

        $auditor = new ParentPermissionAssert($acl, $authentication, $lspUserRepository);
        $auditor->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccess(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $acl = $this->createMock(ZfExtended_Acl::class);
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->willReturn(false);

        $user->expects($this->once())
            ->method('hasParent')
            ->with($manager->getId())
            ->willReturn(false);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $lspUserRepository->method('findByUser')->willReturn(null);

        $auditor = new ParentPermissionAssert($acl, $authentication, $lspUserRepository);
        $this->expectException(NoAccessException::class);
        $auditor->assertGranted($user, $context);
    }
}
