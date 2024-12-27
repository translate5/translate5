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
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\LspUserAccessPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleLspUserException;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LspUserAccessPermissionAssertTest extends TestCase
{
    private LspUserRepositoryInterface|MockObject $lspUserRepository;

    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private LspUserAccessPermissionAssert $assert;

    public function setUp(): void
    {
        $this->lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);

        $this->assert = new LspUserAccessPermissionAssert(
            $this->lspUserRepository,
            $this->coordinatorRepository,
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

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('isAdmin')->willReturn(true);

        $context = new PermissionAssertContext($manager);

        $this->coordinatorRepository->expects($this->never())->method('findByUser');

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccessForNotManagerRole(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);

        $context = new PermissionAssertContext($manager);

        $lspUser = $this->createMock(LspUser::class);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->expectException(NotAccessibleLspUserException::class);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedWhenNotLspUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);

        $context = new PermissionAssertContext($manager);

        $this->lspUserRepository->expects(self::once())->method('findByUser')->willReturn(null);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccessForPmToNotDirectLspUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($manager);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->expectException(NotAccessibleLspUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccessForPmToNotDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('isAdmin')->willReturn(false);
        $manager->method('isCoordinator')->willReturn(false);
        $manager->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($manager);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->expectException(NotAccessibleLspUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedNoAccessForPmToDirectLspUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getId', [], '16'],
            ['getUserGuid', [], 'user-guid'],
        ]);

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($manager);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->expectException(NotAccessibleLspUserException::class);
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

        $manager = $this->createMock(User::class);
        $manager->method('__call')->willReturnMap([
            ['getId', [], '17'],
        ]);
        $manager->method('isAdmin')->willReturn(false);
        $manager->method('isPm')->willReturn(true);

        $context = new PermissionAssertContext($manager);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
        self::assertTrue(true);
    }

    public function testAssertGrantedNoAccessForCoordinatorToNotSameLspUser(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
            ['getId', [], '12'],
        ]);

        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], 'manager-guid'],
            ['getId', [], '14'],
        ]);
        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isCoordinatorOf')->willReturn(false);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->expectException(NotAccessibleLspUserException::class);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }

    public function testAssertGrantedForCoordinatorToSameLspUser(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);

        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(true);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->assert->assertGranted(UserAction::Update, $user, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedForCoordinatorToSameLspCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(true);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->assert->assertGranted(UserAction::Update, $user, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedForCoordinatorToDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(true);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('user-guid', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->assert->assertGranted(UserAction::Update, $user, $context);
        self::assertTrue(true);
    }

    public function testAssertGrantedNoAccessForCoordinatorToNotDirectCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
            ['getId', [], '12'],
        ]);
        $user->method('isCoordinator')->willReturn(true);

        $manager->method('__call')->willReturnMap([
            ['getUserGuid', [], 'manager-guid'],
            ['getId', [], '14'],
        ]);
        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = $this->createMock(JobCoordinator::class);
        $coordinator->method('isSupervisorOf')->willReturn(false);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lspUser = new LspUser('user-guid', $user, $lsp);

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);
        $this->coordinatorRepository->method('findByUser')->willReturn($coordinator);

        $this->expectException(NotAccessibleLspUserException::class);
        $this->assert->assertGranted(UserAction::Update, $user, $context);
    }
}
