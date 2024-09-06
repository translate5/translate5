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
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\LspUserService;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\LspUserAccessPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleLspUserException;
use MittagQI\Translate5\User\ActionAssert\Permission\PermissionAssertContext;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class LspUserAccessPermissionAssertTest extends TestCase
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
        $assert = new LspUserAccessPermissionAssert($this->createMock(LspUserService::class));
        $this->assertEquals($expected, $assert->supports($action));
    }

    public function provideAssertGrantedAdmin(): iterable
    {
        yield [[Roles::ADMIN]];
        yield [[Roles::SYSTEMADMIN]];
    }

    /**
     * @dataProvider provideAssertGrantedAdmin
     */
    public function testAssertGrantedAdmin(array $roles): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn($roles);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->expects($this->never())->method('findCoordinatorBy');

        $assert = new LspUserAccessPermissionAssert($lspUserService);
        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccessForNotManagerRole(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn(['some-role']);

        $lspUser = $this->createMock(LspUser::class);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $this->expectException(NotAccessibleLspUserException::class);
        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedWhenNotLspUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn(['some-role']);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->expects(self::once())->method('findLspUserBy')->willReturn(null);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccessForPmToNotDirectLspUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn([Roles::PM]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $this->expectException(NotAccessibleLspUserException::class);
        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccessForPmToNotDirectCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $manager->method('getRoles')->willReturn([Roles::PM]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $this->expectException(NotAccessibleLspUserException::class);
        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccessForPmToDirectLspUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([]);

        $manager->method('getRoles')->willReturn([Roles::PM]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $this->expectException(NotAccessibleLspUserException::class);
        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedForPmToDirectCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $manager->method('getRoles')->willReturn([Roles::PM]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $assert->assertGranted($user, $context);
        self::assertTrue(true);
    }

    public function testAssertGrantedNoAccessForCoordinatorToNotSameLspUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([]);

        $manager->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(false);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);
        $lspUserService->method('findCoordinatorBy')->willReturn($coordinator);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $this->expectException(NotAccessibleLspUserException::class);
        $assert->assertGranted($user, $context);
    }

    public function testAssertGrantedForCoordinatorToSameLspUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([]);

        $manager->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(true);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);
        $lspUserService->method('findCoordinatorBy')->willReturn($coordinator);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $assert->assertGranted($user, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedForCoordinatorToSameLspCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $manager->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(true);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);
        $lspUserService->method('findCoordinatorBy')->willReturn($coordinator);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $assert->assertGranted($user, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedForCoordinatorToDirectCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $manager->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $coordinatorLsp = $this->createMock(LanguageServiceProvider::class);
        $coordinator = new JobCoordinator('coordinator-guid', $user, $coordinatorLsp);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isSubLspOf')->willReturn(true);
        $lspUser = new LspUser('user-guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);
        $lspUserService->method('findCoordinatorBy')->willReturn($coordinator);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $assert->assertGranted($user, $context);
        self::assertTrue(true);
    }

    public function testAssertGrantedNoAccessForCoordinatorToNotDirectCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $manager->method('getRoles')->willReturn([Roles::JOB_COORDINATOR]);

        $coordinatorLsp = $this->createMock(LanguageServiceProvider::class);
        $coordinator = new JobCoordinator('coordinator-guid', $user, $coordinatorLsp);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isSubLspOf')->willReturn(false);
        $lspUser = new LspUser('user-guid', $user, $lsp);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findLspUserBy')->willReturn($lspUser);
        $lspUserService->method('findCoordinatorBy')->willReturn($coordinator);

        $assert = new LspUserAccessPermissionAssert($lspUserService);

        $this->expectException(NotAccessibleLspUserException::class);
        $assert->assertGranted($user, $context);
    }
}
