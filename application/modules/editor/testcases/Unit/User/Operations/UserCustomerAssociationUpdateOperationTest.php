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

use InvalidArgumentException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Operations\UserCustomerAssociationUpdateOperation;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserCustomerAssociationUpdateOperationTest extends TestCase
{
    private UserCustomerAssociationValidator|MockObject $associationValidator;

    private UserRepository|MockObject $userRepository;

    private UserActionFeasibilityAssertInterface|MockObject $feasibilityChecker;

    private UserAssignCustomersOperationInterface|MockObject $assignCustomers;

    private UserCustomerAssociationUpdateOperation $operation;

    public function setUp(): void
    {
        $this->associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $this->assignCustomers = $this->createMock(UserAssignCustomersOperationInterface::class);

        $this->operation = new UserCustomerAssociationUpdateOperation(
            $this->associationValidator,
            $this->userRepository,
            $this->feasibilityChecker,
            $this->assignCustomers,
        );
    }

    public function testThrowsFeasibilityExceptionWhenActionNotAllowed(): void
    {
        $user = $this->createMock(User::class);

        $exception = $this->createMock(FeasibilityExceptionInterface::class);
        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user)
            ->willThrowException($exception);

        $this->expectException(FeasibilityExceptionInterface::class);

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateAssociatedCustomers($user, []);

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateAssociatedCustomersBy($user, [], $this->createMock(User::class));
    }

    public function testThrowsFeasibilityExceptionWhenActionNotAllowedInAuthUserScenario(): void
    {
        $user = $this->createMock(User::class);

        $exception = $this->createMock(FeasibilityExceptionInterface::class);
        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user)
            ->willThrowException($exception);

        $this->expectException(FeasibilityExceptionInterface::class);

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateAssociatedCustomersBy($user, [], $this->createMock(User::class));
    }

    public function testThrowsExceptionWhenAuthUserTriesAddCustomersThatHeHasNotAccessTo(): void
    {
        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $exception = $this->createMock(InvalidArgumentException::class);
        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->associationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForUser')
            ->willThrowException($exception);

        $this->expectException(InvalidArgumentException::class);

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateAssociatedCustomersBy($user, [], $authUser);
    }

    public function testThrowsExceptionWhenCustomerDoesNotBelongToLsp(): void
    {
        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $exception = $this->createMock(CustomerDoesNotBelongToLspException::class);
        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->assignCustomers
            ->expects(self::once())
            ->method('assignCustomers')
            ->willThrowException($exception);

        $this->expectException(CustomerDoesNotBelongToLspException::class);

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateAssociatedCustomers($user, []);
    }

    public function testCustomerAssociatedWithUser(): void
    {
        $customerIds = [1, 2, 3];

        $user = $this->createMock(User::class);

        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->assignCustomers
            ->expects(self::once())
            ->method('assignCustomers');

        $this->userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $this->operation->updateAssociatedCustomers($user, $customerIds);
    }

    public function testAssociateSameCustomersWithUserInAuthUserScenario(): void
    {
        $customerIds = [1, 2, 3];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn($customerIds);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $this->associationValidator->expects(self::once())->method('assertCustomersAreSubsetForUser');

        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $customerIds);

        $this->userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $this->operation->updateAssociatedCustomersBy($user, $customerIds, $authUser);
    }

    public function testCustomerUnassociatedFromUserInAuthUserScenarioAuthUserNotClientRestricted(): void
    {
        $customerIds = [1, 2];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn([1, 2, 3]);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(false);

        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $customerIds);

        $this->userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $this->operation->updateAssociatedCustomersBy($user, $customerIds, $authUser);
    }

    public function testCustomerUnassociatedFromUserInAuthUserScenarioAuthUserIsClientRestrictedAndHasAccessToDeletedCustomer(): void
    {
        $customerIds = [1, 2];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn([1, 2, 3]);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);
        $authUser->expects(self::once())->method('getCustomersArray')->willReturn([1, 2, 3]);

        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->associationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForUser');

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $customerIds);

        $this->userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $this->operation->updateAssociatedCustomersBy($user, $customerIds, $authUser);
    }

    public function testCustomerNotUnassociatedFromUserInAuthUserScenarioAuthUserIsClientRestrictedAndDoesNotHaveAccessToDeletedCustomer(): void
    {
        $userCustomers = [1, 2, 3];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn($userCustomers);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);
        $authUser->expects(self::once())->method('getCustomersArray')->willReturn([1]);

        $this->feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->associationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForUser');

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $userCustomers);

        $this->userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $this->operation->updateAssociatedCustomersBy($user, [1, 2], $authUser);
    }
}
