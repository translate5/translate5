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

use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\PermissionAudit\Auditors\ParentPermissionAuditor;
use MittagQI\Translate5\User\Action\PermissionAudit\Exception\NoAccessException;
use MittagQI\Translate5\User\Action\PermissionAudit\PermissionAuditContext;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User;

class ParentPermissionAuditorTest extends TestCase
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
        $auditor = new ParentPermissionAuditor(
            $this->createMock(ZfExtended_Acl::class),
            $this->createMock(ZfExtended_AuthenticationInterface::class)
        );
        $this->assertEquals($expected, $auditor->supports($action));
    }

    public function testAssertGrantedCanSeeAllUsers(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

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

        $auditor = new ParentPermissionAuditor($acl, $authentication);
        $auditor->assertGranted($user, $context);
    }

    public function testAssertGrantedSameUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($user);

        $acl = $this->createMock(ZfExtended_Acl::class);
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $acl->expects($this->once())
            ->method('isInAllowedRoles')
            ->willReturn(false);

        $user->expects($this->never())->method('hasParent');
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $auditor = new ParentPermissionAuditor($acl, $authentication);
        $auditor->assertGranted($user, $context);
    }

    public function testAssertGrantedParentUser(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

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

        $auditor = new ParentPermissionAuditor($acl, $authentication);
        $auditor->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccess(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

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

        $auditor = new ParentPermissionAuditor($acl, $authentication);
        $this->expectException(NoAccessException::class);
        $auditor->assertGranted($user, $context);
    }
}
