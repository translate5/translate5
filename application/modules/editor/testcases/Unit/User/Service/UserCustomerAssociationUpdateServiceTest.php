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

namespace MittagQI\Translate5\Test\Unit\User\Service;

use InvalidArgumentException;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Service\UserCustomerAssociationUpdateService;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User as User;

class UserCustomerAssociationUpdateServiceTest extends TestCase
{
    public function testThrowsFeasibilityExceptionWhenActionNotAllowed(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $user = $this->createMock(User::class);

        $exception = $this->createMock(FeasibilityExceptionInterface::class);
        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user)
            ->willThrowException($exception);

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $this->expectException(FeasibilityExceptionInterface::class);

        $userRepository->expects(self::never())->method('save');

        $updateService->updateAssociatedCustomers($user, []);

        $userRepository->expects(self::never())->method('save');

        $updateService->updateAssociatedCustomersBy($user, [], $this->createMock(User::class));
    }

    public function testThrowsFeasibilityExceptionWhenActionNotAllowedInAuthUserScenario(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $user = $this->createMock(User::class);

        $exception = $this->createMock(FeasibilityExceptionInterface::class);
        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user)
            ->willThrowException($exception);

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $this->expectException(FeasibilityExceptionInterface::class);

        $userRepository->expects(self::never())->method('save');

        $updateService->updateAssociatedCustomersBy($user, [], $this->createMock(User::class));
    }

    public function testThrowsExceptionWhenAuthUserTriesAddCustomersThatHeHasNotAccessTo(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $exception = $this->createMock(InvalidArgumentException::class);
        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForUser')
            ->willThrowException($exception);

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $this->expectException(InvalidArgumentException::class);

        $userRepository->expects(self::never())->method('save');

        $updateService->updateAssociatedCustomersBy($user, [], $authUser);
    }

    public function testThrowsExceptionWhenOneOfProvidedCustomersCannotBeAssociatedWithUser(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $exception = $this->createMock(InvalidArgumentException::class);
        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser')
            ->willThrowException($exception);

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $this->expectException(InvalidArgumentException::class);

        $userRepository->expects(self::never())->method('save');

        $updateService->updateAssociatedCustomers($user, []);
    }

    public function testThrowsExceptionWhenOneOfProvidedCustomersCannotBeAssociatedWithUserInAuthUserScenario(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $user = $this->createMock(User::class);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $exception = $this->createMock(InvalidArgumentException::class);
        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser')
            ->willThrowException($exception);

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $this->expectException(InvalidArgumentException::class);

        $userRepository->expects(self::never())->method('save');

        $updateService->updateAssociatedCustomersBy($user, [], $authUser);
    }

    public function testCustomerAssociatedWithUser(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $customerIds = [1, 2, 3];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('assignCustomers')->with($customerIds);

        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser');

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $updateService->updateAssociatedCustomers($user, $customerIds);
    }

    public function testAssociateSameCustomersWithUserInAuthUserScenario(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $customerIds = [1, 2, 3];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn($customerIds);
        $user->expects(self::once())->method('assignCustomers')->with($customerIds);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser');

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $updateService->updateAssociatedCustomersBy($user, $customerIds, $authUser);
    }

    public function testCustomerUnassociatedFromUserInAuthUserScenarioAuthUserNotClientRestricted(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $customerIds = [1, 2];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn([1, 2, 3]);
        $user->expects(self::once())->method('assignCustomers')->with($customerIds);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(false);

        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser');

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $updateService->updateAssociatedCustomersBy($user, $customerIds, $authUser);
    }

    public function testCustomerUnassociatedFromUserInAuthUserScenarioAuthUserIsClientRestrictedAndHasAccessToDeletedCustomer(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $customerIds = [1, 2];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn([1, 2, 3]);
        $user->expects(self::once())->method('assignCustomers')->with($customerIds);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);
        $authUser->expects(self::once())->method('getCustomersArray')->willReturn([1, 2, 3]);

        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser');

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForUser');

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $updateService->updateAssociatedCustomersBy($user, $customerIds, $authUser);
    }

    public function testCustomerNotUnassociatedFromUserInAuthUserScenarioAuthUserIsClientRestrictedAndDoesNotHaveAccessToDeletedCustomer(): void
    {
        $associationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $customerRepository = $this->createMock(CustomerRepository::class);

        $userCustomers = [1, 2, 3];

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('getCustomersArray')->willReturn($userCustomers);
        $user->expects(self::once())->method('assignCustomers')->with($userCustomers);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);
        $authUser->expects(self::once())->method('getCustomersArray')->willReturn([1]);

        $feasibilityChecker
            ->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersMayBeAssociatedWithUser');

        $associationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForUser');

        $updateService = new UserCustomerAssociationUpdateService(
            $associationValidator,
            $userRepository,
            $feasibilityChecker,
            $customerRepository
        );

        $userRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                $this->callback(fn (User $userToSave) => $userToSave === $user)
            );

        $updateService->updateAssociatedCustomersBy($user, [1, 2], $authUser);
    }
}
