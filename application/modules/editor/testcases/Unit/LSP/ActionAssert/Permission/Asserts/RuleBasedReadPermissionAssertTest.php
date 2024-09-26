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

namespace MittagQI\Translate5\Test\Unit\LSP\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\ActionAssert\Permission\Asserts\RuleBasedReadPermissionAssert;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\Acl\Roles;
use PHPUnit\Framework\TestCase;

class RuleBasedReadPermissionAssertTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::DELETE, false];
        yield [Action::UPDATE, false];
        yield [Action::READ, true];
        yield [Action::CREATE, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $assert = new RuleBasedReadPermissionAssert(
            $this->createMock(JobCoordinatorRepository::class)
        );
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
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects(self::once())->method('getRoles')->willReturn($roles);

        $assert = new RuleBasedReadPermissionAssert(
            $this->createMock(JobCoordinatorRepository::class)
        );

        $assert->assertGranted($lsp, $context);
    }

    public function isDirectLspProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider isDirectLspProvider
     */
    public function testAssertGrantedPm(bool $isDirectLsp): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->expects(self::once())->method('isDirectLsp')->willReturn($isDirectLsp);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn([Roles::PM]);

        if (! $isDirectLsp) {
            $this->expectException(NoAccessException::class);
        }

        $assert = new RuleBasedReadPermissionAssert(
            $this->createMock(JobCoordinatorRepository::class)
        );
        $assert->assertGranted($lsp, $context);
    }

    public function testAssertNotGrantedIfNotAdminOrPmAndNotCoordinator(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn(['some-role']);

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);
        $jsRepo->expects($this->once())
            ->method('findByUser')
            ->willReturn(null);

        $this->expectException(NoAccessException::class);

        $assert = new RuleBasedReadPermissionAssert($jsRepo);
        $assert->assertGranted($lsp, $context);
    }

    public function testAssertGrantedToLspOfCoordinator(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->expects(self::once())->method('same')->willReturn(true);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn(['some-role']);

        $coordinator = new JobCoordinator(
            'guid',
            $this->createMock(User::class),
            $lsp
        );

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);
        $jsRepo->expects($this->once())
            ->method('findByUser')
            ->willReturn($coordinator);

        $assert = new RuleBasedReadPermissionAssert($jsRepo);
        $assert->assertGranted($lsp, $context);
    }

    public function testAssertGrantedToSubLspOfCoordinator(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->expects(self::once())->method('isSubLspOf')->willReturn(true);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('getRoles')->willReturn(['some-role']);

        $coordinatorLsp = $this->createMock(LanguageServiceProvider::class);
        $coordinatorLsp->expects(self::once())->method('same')->willReturn(false);
        $coordinator = new JobCoordinator(
            'guid',
            $this->createMock(User::class),
            $coordinatorLsp
        );

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);
        $jsRepo->expects($this->once())
            ->method('findByUser')
            ->willReturn($coordinator);

        $assert = new RuleBasedReadPermissionAssert($jsRepo);
        $assert->assertGranted($lsp, $context);
    }
}
