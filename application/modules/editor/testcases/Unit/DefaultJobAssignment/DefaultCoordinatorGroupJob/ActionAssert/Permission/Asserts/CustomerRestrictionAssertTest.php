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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultCoordinatorGroupJob\ActionAssert\Permission\Asserts;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\ActionAssert\Permission\Asserts\CustomerRestrictionAssert;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\ActionAssert\Permission\Exception\NoAccessToDefaultCoordinatorGroupJobException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerRestrictionAssertTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $customerPermissionAssert;

    private CustomerRepository|MockObject $customerRepository;

    private CustomerRestrictionAssert $assert;

    public function setUp(): void
    {
        $this->customerPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepository::class);

        $this->assert = new CustomerRestrictionAssert(
            $this->customerPermissionAssert,
            $this->customerRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Update, true];
        yield [DefaultJobAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(DefaultJobAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->assert->supports($action));
    }

    public function testAssertNotGrantedIfCustomerNotFound(): void
    {
        $this->customerRepository->method('get')->willThrowException(new InexistentCustomerException(1));
        $defaultGroupJobJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $defaultGroupJobJob->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);
        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->expectException(NoAccessToDefaultCoordinatorGroupJobException::class);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultGroupJobJob, $context);
    }

    public function testAssertNotGrantedIfNoAccessToCustomer(): void
    {
        $customer = $this->createMock(Customer::class);
        $this->customerRepository->method('get')->willReturn($customer);

        $defaultGroupJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $defaultGroupJob->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);

        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->customerPermissionAssert
            ->expects($this->once())
            ->method('assertGranted')
            ->with(CustomerAction::DefaultJob, $customer)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(NoAccessToDefaultCoordinatorGroupJobException::class);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultGroupJob, $context);
    }

    public function testAssertGranted(): void
    {
        $customer = $this->createMock(Customer::class);
        $this->customerRepository->method('get')->willReturn($customer);

        $defaultGroupJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $defaultGroupJob->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);

        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultGroupJob, $context);

        self::assertTrue(true);
    }
}
