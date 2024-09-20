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

namespace Customer\ActionAssert\Permission;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\Permission\AssignedCustomerAssert;
use MittagQI\Translate5\Customer\Exception\NoAccessToCustomerException;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class AssignedCustomerAssertTest extends TestCase
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
        $lspPermissionAuditor = new AssignedCustomerAssert();
        $this->assertEquals($expected, $lspPermissionAuditor->supports($action));
    }

    public function testAssertGrantedNotClientRestricted(): void
    {
        $customer = $this->createMock(Customer::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects($this->once())
            ->method('isClientRestricted')
            ->willReturn(false);
        $manager->expects($this->never())
            ->method('getCustomersArray');

        $lspPermissionAuditor = new AssignedCustomerAssert();
        $lspPermissionAuditor->assertGranted($customer, $context);
    }

    public function testAssertGranted(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], 3],
        ]);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects($this->once())
            ->method('isClientRestricted')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getCustomersArray')
            ->willReturn([2, 3, 4]);

        $lspPermissionAuditor = new AssignedCustomerAssert();
        $lspPermissionAuditor->assertGranted($customer, $context);
    }

    public function testAccessNotGranted(): void
    {
        $this->expectException(NoAccessToCustomerException::class);

        $customer = $this->createMock(Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], 5],
        ]);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->expects($this->once())
            ->method('isClientRestricted')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getCustomersArray')
            ->willReturn([2, 3, 4]);

        $lspPermissionAuditor = new AssignedCustomerAssert();

        $lspPermissionAuditor->assertGranted($customer, $context);
    }
}
