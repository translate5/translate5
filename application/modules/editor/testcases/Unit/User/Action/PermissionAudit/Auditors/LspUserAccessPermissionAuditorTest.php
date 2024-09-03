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

namespace User\Action\PermissionAudit\Auditors;

use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\LspUserService;
use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\PermissionAudit\Auditors\LspUserAccessPermissionAuditor;
use MittagQI\Translate5\User\Action\PermissionAudit\PermissionAuditContext;
use MittagQI\ZfExtended\Acl\Roles;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class LspUserAccessPermissionAuditorTest extends TestCase
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
        $lspPermissionAuditor = new LspUserAccessPermissionAuditor($this->createMock(LspUserService::class));
        $this->assertEquals($expected, $lspPermissionAuditor->supports($action));
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
        $context = new PermissionAuditContext($manager);

        $manager->method('getRoles')->willReturn($roles);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->expects($this->never())->method('findCoordinatorBy');

        $lspPermissionAuditor = new LspUserAccessPermissionAuditor($lspUserService);
        $lspPermissionAuditor->assertGranted($user, $context);
    }

    public function testAssertGrantedNoCoordinator(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

        $manager->method('getRoles')->willReturn([]);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->expects($this->once())
            ->method('findCoordinatorBy')
            ->willReturn(null);

        $lspPermissionAuditor = new LspUserAccessPermissionAuditor($lspUserService);
        $lspPermissionAuditor->assertGranted($user, $context);
    }

    public function testAssertGrantedAccessibleUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

        $manager->method('getRoles')->willReturn([]);

        $coordinator = $this->createMock(JobCoordinator::class);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findCoordinatorBy')
            ->with($this->callback(fn (object $provided) => $provided === $manager))
            ->willReturn($coordinator);
        $lspUserService->expects($this->once())
            ->method('getAccessibleUsers')
            ->with($this->callback(fn (object $provided) => $provided === $coordinator))
            ->willReturn([$user]);

        $lspPermissionAuditor = new LspUserAccessPermissionAuditor($lspUserService);
        $lspPermissionAuditor->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccess(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

        $manager->method('getRoles')->willReturn([]);

        $coordinator = $this->createMock(JobCoordinator::class);

        $lspUserService = $this->createMock(LspUserService::class);
        $lspUserService->method('findCoordinatorBy')
            ->with($this->callback(fn (object $provided) => $provided === $manager))
            ->willReturn($coordinator);
        $lspUserService->expects($this->once())
            ->method('getAccessibleUsers')
            ->with($this->callback(fn (object $provided) => $provided === $coordinator))
            ->willReturn([$manager]);

        $lspPermissionAuditor = new LspUserAccessPermissionAuditor($lspUserService);
        $lspPermissionAuditor->assertGranted($user, $context);
    }
}
