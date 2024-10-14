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

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\PasswordDto;
use MittagQI\Translate5\User\Operations\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Operations\UserSetPasswordOperation;
use MittagQI\Translate5\User\Operations\UserUpdateOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_ValidateException;

class UserUpdateOperationTest extends TestCase
{
    private UserRepository|MockObject $userRepository;

    private ActionFeasibilityAssertInterface|MockObject $userActionFeasibilityChecker;

    private UserSetRolesOperationInterface|MockObject $setRoles;

    private UserSetPasswordOperation|MockObject $setPassword;

    private UserAssignCustomersOperationInterface|MockObject $assignCustomers;

    private ResetPasswordEmail|MockObject $resetPasswordEmail;

    private UserUpdateOperation $operation;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userActionFeasibilityChecker = $this->createMock(ActionFeasibilityAssertInterface::class);
        $this->setRoles = $this->createMock(UserSetRolesOperationInterface::class);
        $this->setPassword = $this->createMock(UserSetPasswordOperation::class);
        $this->assignCustomers = $this->createMock(UserAssignCustomersOperationInterface::class);
        $this->resetPasswordEmail = $this->createMock(ResetPasswordEmail::class);

        $this->operation = new UserUpdateOperation(
            $this->userRepository,
            $this->userActionFeasibilityChecker,
            $this->setRoles,
            $this->setPassword,
            $this->assignCustomers,
            $this->resetPasswordEmail,
        );
    }

    public function testUpdateLogin(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            'new_login',
            null,
            null,
            null,
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setLogin', [$dto->login]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateEmail(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            'new_email@example.com',
            null,
            null,
            null,
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setEmail', [$dto->email]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateFirstName(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            'John',
            null,
            null,
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setFirstName', [$dto->firstName]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateSurName(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            'Doe',
            null,
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setSurName', [$dto->surName]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateGender(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            'm',
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setGender', [$dto->gender]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateLocale(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
            locale: 'en',
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setLocale', [$dto->locale]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdatePassword(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
            password: new PasswordDto('password'),
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->setPassword->expects(self::once())->method('setPassword')->with($user, $dto->password->password);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testSendsResetMail(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
            password: null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->setPassword->expects(self::never())->method('setPassword');
        $user->expects(self::once())->method('validate');

        $this->resetPasswordEmail->expects(self::once())->method('sendTo')->with($user);

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateRoles(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
            roles: ['role1', 'role2'],
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateCustomers(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
            customers: [11, 12],
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $dto->customers);

        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateEmpty(): void
    {
        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::never())->method('__call')->with($this->callback(static function (string $method): bool {
            return ! str_contains($method, 'set');
        }));

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateThrowsFeasibilityException(): void
    {
        $this->expectException(FeasibilityExceptionInterface::class);

        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(null, null, null, null, null, null);

        $this->userActionFeasibilityChecker->method('assertAllowed')
            ->willThrowException($this->createMock(FeasibilityExceptionInterface::class));

        $this->operation->updateUser($user, $dto);
    }

    public function testUpdateThrowsValidateException(): void
    {
        $this->expectException(ZfExtended_ValidateException::class);

        $user = $this->createMock(User::class);

        $user->expects(self::once())->method('validate')->willThrowException(new ZfExtended_ValidateException());

        $this->userRepository->expects(self::never())->method('save');

        $dto = new UpdateUserDto(
            null,
            null,
            null,
            null,
            null,
        );

        $this->operation->updateUser($user, $dto);
    }
}
