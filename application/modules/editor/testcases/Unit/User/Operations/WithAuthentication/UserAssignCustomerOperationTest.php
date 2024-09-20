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

namespace MittagQI\Translate5\Test\Unit\User\Operations\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserAssignCustomersOperation;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;

class UserAssignCustomerOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $userPermissionAssert;

    private UserAssignCustomersOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private CustomerRepository|MockObject $customerRepository;

    private ActionPermissionAssertInterface|MockObject $customerPermissionAssert;

    private UserAssignCustomersOperation $operation;

    public function setUp(): void
    {
        $this->userPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->generalOperation = $this->createMock(UserAssignCustomersOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->customerPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);

        $this->operation = new UserAssignCustomersOperation(
            $this->userPermissionAssert,
            $this->generalOperation,
            $this->authentication,
            $this->userRepository,
            $this->customerRepository,
            $this->customerPermissionAssert,
        );
    }

    public function testThrowsPermissionException(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->userPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $user = $this->createMock(User::class);

        $this->operation->assignCustomers($user, [1]);
    }

    public function testThrowsExceptionOnNotAllowedCustomer(): void
    {
        $this->expectException(CustomerDoesNotBelongToUserException::class);

        $this->userPermissionAssert->expects(self::once())->method('assertGranted');

        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $customer = $this->createMock(\editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '2'],
        ]);

        $this->customerRepository
            ->method('getList')
            ->willReturn([$customer]);

        $this->customerPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid'],
        ]);

        $this->operation->assignCustomers($user, [1]);
    }

    public function testAssignCustomers(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $this->userPermissionAssert->expects(self::once())->method('assertGranted');

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(false);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $user = $this->createMock(User::class);

        $associatedCustomerIds = [1, 2, 3];

        $this->generalOperation->method('assignCustomers')->with($user, $associatedCustomerIds);

        $this->operation->assignCustomers($user, $associatedCustomerIds);
    }
}
