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

namespace MittagQI\Translate5\Test\Unit\User\Operations;

use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\UserAssignCustomersOperation;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserAssignCustomerOperationTest extends TestCase
{
    private UserCustomerAssociationValidator|MockObject $userCustomerAssociationValidator;

    private UserAssignCustomersOperation $operation;

    private UserRepository $userRepository;

    public function setUp(): void
    {
        $this->userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new UserAssignCustomersOperation(
            $this->userCustomerAssociationValidator,
            $this->userRepository,
        );
    }

    public function testThrowsExceptionOnNotAllowedCustomer(): void
    {
        $this->expectException(CustomerDoesNotBelongToCoordinatorGroupException::class);

        $this->userCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser')
            ->willThrowException($this->createMock(CustomerDoesNotBelongToCoordinatorGroupException::class));

        $user = $this->createMock(User::class);

        $this->operation->assignCustomers($user, 1);
    }

    public function testAssignCustomers(): void
    {
        $this->userCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser');

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('assignCustomers')->with([1, 2, 3]);

        $this->operation->assignCustomers($user, ...[1, 2, 3]);
    }
}
