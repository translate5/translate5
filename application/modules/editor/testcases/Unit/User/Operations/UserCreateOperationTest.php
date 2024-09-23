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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\LspUserCreateOperation;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\Exception\LspMustBeProvidedInJobCoordinatorCreationProcessException;
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\CreateUserDto;
use MittagQI\Translate5\User\Operations\UserCreateOperation;
use MittagQI\Translate5\User\Operations\UserSetPasswordOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserCreateOperationTest extends TestCase
{
    private UserRepository|MockObject $userRepository;

    private UserSetParentIdsOperationInterface|MockObject $setParentIds;

    private UserSetRolesOperationInterface|MockObject $setRoles;

    private UserSetPasswordOperation|MockObject $setPassword;

    private UserAssignCustomersOperationInterface|MockObject $assignCustomers;

    private LspUserCreateOperation|MockObject $lspUserCreate;

    private ResetPasswordEmail|MockObject $resetPasswordEmail;

    private LspRepositoryInterface|MockObject $lspRepository;

    private LspUserRepositoryInterface|MockObject $lspUserRepository;

    private UserCreateOperation $operation;

    public function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->setParentIds = $this->createMock(UserSetParentIdsOperationInterface::class);
        $this->setRoles = $this->createMock(UserSetRolesOperationInterface::class);
        $this->setPassword = $this->createMock(UserSetPasswordOperation::class);
        $this->assignCustomers = $this->createMock(UserAssignCustomersOperationInterface::class);
        $this->lspUserCreate = $this->createMock(LspUserCreateOperation::class);
        $this->resetPasswordEmail = $this->createMock(ResetPasswordEmail::class);
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);
        $this->lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);

        $this->operation = new UserCreateOperation(
            $this->userRepository,
            $this->setParentIds,
            $this->setRoles,
            $this->setPassword,
            $this->assignCustomers,
            $this->lspUserCreate,
            $this->resetPasswordEmail,
            $this->lspRepository,
            $this->lspUserRepository,
        );
    }

    public function testThrowsExceptionOnCoordinatorCreationIfNoLspProvided(): void
    {
        $this->expectException(LspMustBeProvidedInJobCoordinatorCreationProcessException::class);
        $dto = new CreateUserDto(
            'guid',
            'login',
            'email@translate5.com',
            'firstname',
            'surname',
            'm',
            ['role1', 'role2'],
            [1, 2, 3],
            null,
        );

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setInitialFields');
        $user->method('isCoordinator')->willReturn(true);

        $this->userRepository->method('getEmptyModel')->willReturn($user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->createUser($dto);
    }

    public function testCoordinatorCreated(): void
    {
        $dto = new CreateUserDto(
            'guid',
            'login',
            'email@translate5.com',
            'firstname',
            'surname',
            'm',
            ['role1', Roles::JOB_COORDINATOR],
            [1, 2, 3],
            1,
            'password',
            'parent-guid',
            'en',
        );

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setInitialFields');
        $user->method('isCoordinator')->willReturn(true);
        $user->expects(self::once())->method('validate');

        $this->userRepository->method('getEmptyModel')->willReturn($user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('get')->willReturn($lsp);

        $this->userRepository->expects(self::exactly(2))->method('save')->with($user);

        $this->lspUserCreate
            ->expects(self::once())
            ->method('createLspUser')
            ->with($lsp, $user)
            ->willReturn($this->createMock(LspUser::class));

        $this->setParentIds->expects(self::once())->method('setParentIds')->with($user, $dto->parentId);

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $dto->customers);

        $this->resetPasswordEmail->expects(self::never())->method('sendTo');

        self::assertSame($user, $this->operation->createUser($dto));
    }

    public function testLspUserCreated(): void
    {
        $dto = new CreateUserDto(
            'guid',
            'login',
            'email@translate5.com',
            'firstname',
            'surname',
            'm',
            ['role1', Roles::JOB_COORDINATOR],
            [1, 2, 3],
            1,
            'password',
            'parent-guid',
            'en',
        );

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setInitialFields');
        $user->method('isCoordinator')->willReturn(false);
        $user->expects(self::once())->method('validate');

        $this->userRepository->method('getEmptyModel')->willReturn($user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('get')->willReturn($lsp);

        $this->userRepository->expects(self::exactly(2))->method('save')->with($user);

        $this->lspUserCreate
            ->expects(self::once())
            ->method('createLspUser')
            ->with($lsp, $user)
            ->willReturn($this->createMock(LspUser::class));

        $this->setParentIds->expects(self::once())->method('setParentIds')->with($user, $dto->parentId);

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $dto->customers);

        $this->resetPasswordEmail->expects(self::never())->method('sendTo');

        self::assertSame($user, $this->operation->createUser($dto));
    }

    public function testUserCreated(): void
    {
        $dto = new CreateUserDto(
            'guid',
            'login',
            'email@translate5.com',
            'firstname',
            'surname',
            'm',
            ['role1'],
            [1, 2, 3],
            null,
            'password',
            'parent-guid',
            'en',
        );

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setInitialFields');
        $user->method('isCoordinator')->willReturn(false);
        $user->expects(self::once())->method('validate');

        $this->userRepository->method('getEmptyModel')->willReturn($user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $this->lspRepository->expects(self::never())->method('get');

        $this->userRepository->expects(self::exactly(2))->method('save')->with($user);

        $this->lspUserCreate->expects(self::never())->method('createLspUser');

        $this->setParentIds->expects(self::once())->method('setParentIds')->with($user, $dto->parentId);

        $this->assignCustomers->expects(self::once())->method('assignCustomers')->with($user, $dto->customers);

        $this->resetPasswordEmail->expects(self::never())->method('sendTo');

        self::assertSame($user, $this->operation->createUser($dto));
    }

    public function testSendEmailOnCreation(): void
    {
        $dto = new CreateUserDto(
            'guid',
            'login',
            'email@translate5.com',
            'firstname',
            'surname',
            'm',
            ['role1'],
            [1, 2, 3],
            null,
            null,
            null,
            null,
        );

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setInitialFields');
        $user->method('isCoordinator')->willReturn(false);
        $user->expects(self::once())->method('validate');

        $this->userRepository->method('getEmptyModel')->willReturn($user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $this->lspRepository->expects(self::never())->method('get');

        $this->userRepository->expects(self::exactly(2))->method('save')->with($user);

        $this->lspUserCreate->expects(self::never())->method('createLspUser');

        $this->setParentIds->expects(self::once())->method('setParentIds');

        $this->assignCustomers->expects(self::once())->method('assignCustomers');

        $this->resetPasswordEmail->expects(self::once())->method('sendTo')->with($user);

        self::assertSame($user, $this->operation->createUser($dto));
    }

    public function testProcessRevertedOnException(): void
    {
        $dto = new CreateUserDto(
            'guid',
            'login',
            'email@translate5.com',
            'firstname',
            'surname',
            'm',
            ['role1', Roles::JOB_COORDINATOR],
            [1, 2, 3],
            1,
            'password',
            'parent-guid',
            'en',
        );

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setInitialFields');
        $user->method('isCoordinator')->willReturn(false);
        $user->expects(self::once())->method('validate');

        $this->userRepository->method('getEmptyModel')->willReturn($user);

        $this->setRoles->expects(self::once())->method('setRoles')->with($user, $dto->roles);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('get')->willReturn($lsp);

        $this->userRepository->expects(self::once())->method('save')->with($user);
        $this->userRepository->method('delete')->with($user);

        $lspUser = $this->createMock(LspUser::class);

        $this->lspUserCreate
            ->expects(self::once())
            ->method('createLspUser')
            ->with($lsp, $user)
            ->willReturn($lspUser);

        $this->lspUserRepository->expects(self::once())->method('delete')->with($lspUser);

        $this->setParentIds->expects(self::once())->method('setParentIds')->with($user, $dto->parentId);

        $this->assignCustomers
            ->expects(self::once())
            ->method('assignCustomers')
            ->willThrowException($this->createMock(\Throwable::class));

        $this->resetPasswordEmail->expects(self::never())->method('sendTo');

        $this->expectException(\Throwable::class);

        self::assertSame($user, $this->operation->createUser($dto));
    }
}
