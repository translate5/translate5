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
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\User\Exception\UnableToAssignJobCoordinatorRoleToExistingUserException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Operations\UserUpdateRolesOperation;
use MittagQI\Translate5\User\Validation\RolesValidator;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_Models_User as User;

class UserUpdateRolesOperationTest extends TestCase
{
    private UserActionFeasibilityAssertInterface|MockObject $userActionFeasibilityChecker;
    private RolesValidator|MockObject $rolesValidator;
    private ZfExtended_Acl|MockObject $acl;
    private UserRepository|MockObject $userRepository;

    private LspUserRepositoryInterface|MockObject $lspUserRepository;
    private UserUpdateRolesOperation $operation;

    protected function setUp(): void
    {
        $this->userActionFeasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $this->rolesValidator = $this->createMock(RolesValidator::class);
        $this->acl = $this->createMock(ZfExtended_Acl::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);

        $this->operation = new UserUpdateRolesOperation(
            $this->userActionFeasibilityChecker,
            $this->rolesValidator,
            $this->acl,
            $this->userRepository,
            $this->lspUserRepository,
        );
    }

    public function testUpdateUserRolesByWithEmptyRoles(): void
    {
        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);

        $this->operation->updateRolesBy($user, [], $authUser);

        $user->expects(self::never())->method('setRoles');

        $this->userRepository->expects(self::never())->method('save');
    }

    public function testUpdateUserRolesByThrowsExceptionWhenUserIsNotAuthorized(): void
    {
        $this->expectException(UserIsNotAuthorisedToAssignRoleException::class);

        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);

        $this->acl->method('isInAllowedRoles')->willReturn(false);

        $user->expects(self::never())->method('setRoles');

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateRolesBy($user, ['role1'], $authUser);
    }

    public function testUpdateRoles(): void
    {
        $user = $this->createMock(User::class);
        $roles = ['role1', 'role2'];

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->rolesValidator->expects(self::once())
            ->method('assertRolesDontConflict')
            ->with($roles);

        $this->acl->expects(self::once())
            ->method('mergeAutoSetRoles')
            ->with($roles, [])
            ->willReturn($roles);

        $user->expects(self::once())->method('setRoles')->with($roles);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateRoles($user, $roles);
    }

    public function testCannotSetJobCoordinatorRoleToNotLspUserOnUpdateRoles(): void
    {
        $user = $this->createMock(User::class);
        $roles = [Roles::JOB_COORDINATOR, 'role2'];

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->lspUserRepository->method('findByUser')->willReturn(null);

        $user->expects(self::never())->method('setRoles');

        $this->userRepository->expects(self::never())->method('save');

        $this->expectException(UnableToAssignJobCoordinatorRoleToExistingUserException::class);

        $this->operation->updateRoles($user, $roles);
    }

    public function testCanSetJobCoordinatorRoleToNotLspUserOnUpdateRoles(): void
    {
        $user = $this->createMock(User::class);
        $roles = [Roles::JOB_COORDINATOR, 'role2'];

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->lspUserRepository->method('findByUser')->willReturn($this->createMock(LspUser::class));

        $this->acl->expects(self::once())
            ->method('mergeAutoSetRoles')
            ->with($roles, [])
            ->willReturn($roles);

        $user->expects(self::once())->method('setRoles')->with($roles);

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateRoles($user, $roles);
    }

    public function testOldRolesAreFilteredOnUpdateRolesBy(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn(['old1', 'old2']);

        $authUser = $this->createMock(User::class);
        $authUser->method('getRoles')->willReturn(['some-role']);

        $roles = ['role1', 'role2', 'old1'];

        $this->acl->method('isInAllowedRoles')->willReturnMap([
            [$authUser->getRoles(), SetAclRoleResource::ID, 'old1', false],
            [$authUser->getRoles(), SetAclRoleResource::ID, 'old2', true],
            [$authUser->getRoles(), SetAclRoleResource::ID, 'role1', true],
            [$authUser->getRoles(), SetAclRoleResource::ID, 'role2', true],
        ]);

        $this->rolesValidator->expects(self::once())
            ->method('assertRolesDontConflict')
            ->with($roles);

        $this->acl->expects(self::once())
            ->method('mergeAutoSetRoles')
            ->with($roles, [])
            ->willReturn($roles);

        $user->expects(self::once())->method('setRoles')->with($roles);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updateRolesBy($user, ['role1', 'role2'], $authUser);
    }

    public function testNothingDoneIfRolesHaveConflict(): void
    {
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $user = $this->createMock(User::class);

        $exception = $this->createMock(ConflictingRolesExceptionInterface::class);

        $this->rolesValidator->method('assertRolesDontConflict')->willThrowException($exception);

        $user->expects(self::never())->method('setRoles');

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updateRoles($user, ['role1']);
    }
}
