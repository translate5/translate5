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

use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\ClientRestrictedPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\ClientRestrictionException;
use MittagQI\Translate5\User\ActionAssert\Permission\PermissionAssertContext;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class ClientRestrictedPermissionAssertTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::DELETE, true];
        yield [Action::UPDATE, true];
        yield [Action::READ, true];
        yield [Action::CREATE, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $lspPermissionAuditor = new ClientRestrictedPermissionAssert();
        $this->assertEquals($expected, $lspPermissionAuditor->supports($action));
    }

    public function testAssertGrantedNotClientRestricted(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects($this->once())
            ->method('isClientRestricted')
            ->willReturn(false);
        $manager->expects($this->never())
            ->method('getRestrictedClientIds');

        $lspPermissionAuditor = new ClientRestrictedPermissionAssert();
        $lspPermissionAuditor->assertGranted($user, $context);
    }

    public function testAssertGranted(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects($this->once())
            ->method('isClientRestricted')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getRestrictedClientIds')
            ->willReturn([2, 3, 4]);

        $user->expects($this->once())
            ->method('getCustomersArray')
            ->willReturn([2, 3, 4]);

        $lspPermissionAuditor = new ClientRestrictedPermissionAssert();
        $lspPermissionAuditor->assertGranted($user, $context);
    }

    public function testAssertGrantedNoAccess(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $manager = $this->createMock(ZfExtended_Models_User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects($this->once())
            ->method('isClientRestricted')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getRestrictedClientIds')
            ->willReturn([2, 3, 4]);

        $user->expects($this->once())
            ->method('getCustomersArray')
            ->willReturn([1, 3, 4]);

        $lspPermissionAuditor = new ClientRestrictedPermissionAssert();
        $this->expectException(ClientRestrictionException::class);
        $lspPermissionAuditor->assertGranted($user, $context);
    }
}
