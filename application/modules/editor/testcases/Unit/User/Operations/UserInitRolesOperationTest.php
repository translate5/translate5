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
use MittagQI\Translate5\User\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Operations\UserInitRolesOperation;
use MittagQI\Translate5\User\Validation\RolesValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_Models_User as User;

class UserInitRolesOperationTest extends TestCase
{
    private RolesValidator|MockObject $rolesValidator;
    private ZfExtended_Acl|MockObject $acl;
    private UserRepository|MockObject $userRepository;
    private UserInitRolesOperation $operation;

    protected function setUp(): void
    {
        $this->rolesValidator = $this->createMock(RolesValidator::class);
        $this->acl = $this->createMock(ZfExtended_Acl::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new UserInitRolesOperation(
            $this->rolesValidator,
            $this->acl,
            $this->userRepository
        );
    }

    public function testInitUserRolesByWithEmptyRoles(): void
    {
        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);

        $this->operation->initUserRolesBy($user, [], $authUser);

        $user->expects($this->never())->method('setRoles');

        $this->userRepository->expects($this->never())->method('save');
    }

    public function testInitUserRolesByThrowsExceptionWhenUserIsNotAuthorized(): void
    {
        $this->expectException(UserIsNotAuthorisedToAssignRoleException::class);

        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);

        $this->acl->method('isInAllowedRoles')->willReturn(false);

        $user->expects($this->never())->method('setRoles');

        $this->userRepository->expects($this->never())->method('save');

        $this->operation->initUserRolesBy($user, ['role1'], $authUser);
    }

    public function testInitRoles(): void
    {
        $user = $this->createMock(User::class);
        $roles = ['role1', 'role2'];

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->rolesValidator->expects($this->once())
            ->method('assertRolesDontConflict')
            ->with($roles);

        $this->acl->expects($this->once())
            ->method('mergeAutoSetRoles')
            ->with($roles, [])
            ->willReturn($roles);

        $user->expects($this->once())->method('setRoles')->with($roles);
        $user->expects($this->once())->method('validate');

        $this->userRepository->expects($this->once())->method('save')->with($user);

        $this->operation->initRoles($user, $roles);
    }

    public function testNothingDoneIfRolesHaveConflict(): void
    {
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $user = $this->createMock(User::class);

        $exception = $this->createMock(ConflictingRolesExceptionInterface::class);

        $this->rolesValidator->method('assertRolesDontConflict')->willThrowException($exception);

        $user->expects($this->never())->method('setRoles');

        $this->userRepository->expects($this->never())->method('save');

        $this->operation->initRoles($user, ['role1']);
    }
}
