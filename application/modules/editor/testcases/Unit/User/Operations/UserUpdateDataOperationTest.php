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

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Operations\UserUpdateDataOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_User as User;
use ZfExtended_ValidateException;

class UserUpdateDataOperationTest extends TestCase
{
    private UserRepository|MockObject $userRepository;
    private UserActionFeasibilityAssertInterface|MockObject $userActionFeasibilityChecker;
    private UserUpdateDataOperation $operation;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userActionFeasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);

        $this->operation = new UserUpdateDataOperation(
            $this->userRepository,
            $this->userActionFeasibilityChecker
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
            null,
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setLogin', [$dto->login]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->update($user, $dto);
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

        $this->operation->update($user, $dto);
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

        $this->operation->update($user, $dto);
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

        $this->operation->update($user, $dto);
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

        $this->operation->update($user, $dto);
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
            'en',
        );

        $this->userActionFeasibilityChecker->expects(self::once())
            ->method('assertAllowed')
            ->with(Action::UPDATE, $user);

        $user->expects(self::once())->method('__call')->with('setLocale', [$dto->locale]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->update($user, $dto);
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

        $this->operation->update($user, $dto);
    }

    public function testUpdateThrowsLoginAlreadyInUseException(): void
    {
        $this->expectException(LoginAlreadyInUseException::class);

        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(null, 'duplicate_login', null, null, null, null);

        $this->userActionFeasibilityChecker->expects(self::once())->method('assertAllowed');

        $this->userRepository
            ->method('save')
            ->willThrowException(
                new ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey('E0000', ['field' => 'login'])
            );

        $this->operation->update($user, $dto);
    }

    public function testUpdateThrowsGuidAlreadyInUseException(): void
    {
        $this->expectException(GuidAlreadyInUseException::class);

        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(null, null, null, null, null, null);

        $this->userActionFeasibilityChecker->expects(self::once())->method('assertAllowed');

        $this->userRepository
            ->method('save')
            ->willThrowException(
                new ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey('E0000', ['field' => 'userGuid'])
            );

        $this->operation->update($user, $dto);
    }

    public function testUpdateThrowsFeasibilityException(): void
    {
        $this->expectException(FeasibilityExceptionInterface::class);

        $user = $this->createMock(User::class);
        $dto = new UpdateUserDto(null, null, null, null, null, null);

        $this->userActionFeasibilityChecker->method('assertAllowed')
            ->willThrowException($this->createMock(FeasibilityExceptionInterface::class));

        $this->operation->update($user, $dto);
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
            'en',
        );

        $this->operation->update($user, $dto);
    }
}
