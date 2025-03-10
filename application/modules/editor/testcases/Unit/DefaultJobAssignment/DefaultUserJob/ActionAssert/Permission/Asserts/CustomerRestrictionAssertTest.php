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

namespace DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\Asserts;

use editor_Models_Customer_Customer;
use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\Asserts\CustomerRestrictionAssert;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomerRestrictionAssertTest extends TestCase
{
    private MockObject|ActionPermissionAssertInterface $customerPermissionAssert;

    private MockObject|CustomerRepository $customerRepository;

    private CustomerRestrictionAssert $customerRestrictionAssert;

    public function setUp(): void
    {
        $this->customerPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepository::class);

        $this->customerRestrictionAssert = new CustomerRestrictionAssert(
            $this->customerPermissionAssert,
            $this->customerRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [DefaultJobAction::Update, true];
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(DefaultJobAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->customerRestrictionAssert->supports($action));
    }

    public function provideAssertAllowed(): iterable
    {
        yield [DefaultJobAction::Update, false];
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Read, false];
    }

    /**
     * @dataProvider provideAssertAllowed
     */
    public function testAssertGranted(DefaultJobAction $action, bool $hasAccessToUser): void
    {
        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $this->customerRepository->method('get')->willReturn($customer);

        if (! $hasAccessToUser) {
            $this->customerPermissionAssert
                ->method('assertGranted')
                ->willThrowException($this->createMock(PermissionExceptionInterface::class));
            $this->expectException(NoAccessException::class);
        }

        $defaultJob = $this->createMock(DefaultUserJob::class);
        $defaultJob->method('__call')->willReturn('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}');

        $this->customerRestrictionAssert->assertGranted($action, $defaultJob, $context);

        self::assertTrue(true);
    }

    public function testAssertNotGrantedOnCustomerNotFound(): void
    {
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $this->customerRepository->method('get')->willThrowException(new InexistentCustomerException(1));

        $this->expectException(NoAccessException::class);

        $defaultJob = $this->createMock(DefaultUserJob::class);
        $defaultJob->method('__call')->willReturn('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}');

        $this->customerRestrictionAssert->assertGranted(DefaultJobAction::Read, $defaultJob, $context);

        self::assertTrue(true);
    }
}
