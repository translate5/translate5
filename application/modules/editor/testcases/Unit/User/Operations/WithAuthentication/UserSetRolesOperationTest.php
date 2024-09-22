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
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserSetRolesOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_AuthenticationInterface;

class UserSetRolesOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $userPermissionAssert;

    private UserSetRolesOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private ZfExtended_Acl|MockObject $acl;

    private UserRepository|MockObject $userRepository;

    private UserSetRolesOperation $operation;

    protected function setUp(): void
    {
        $this->generalOperation = $this->createMock(UserSetRolesOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->acl = $this->createMock(ZfExtended_Acl::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new UserSetRolesOperation(
            $this->generalOperation,
            $this->authentication,
            $this->acl,
            $this->userRepository,
        );
    }

    public function testThrowsExceptionOnAclUnAllowedRole(): void
    {
        $this->expectException(UserIsNotAuthorisedToAssignRoleException::class);

        $this->authentication->expects(self::once())->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->acl->method('isInAllowedRoles')->willReturn(false);

        $user = $this->createMock(User::class);

        $this->generalOperation->expects(self::never())->method('setRoles');

        $this->operation->setRoles($user, ['role1']);
    }

    public function testThrowsExceptionOnAclException(): void
    {
        $this->expectException(UserIsNotAuthorisedToAssignRoleException::class);

        $this->authentication->expects(self::once())->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->acl->method('isInAllowedRoles')->willThrowException(new \Zend_Acl_Exception());

        $user = $this->createMock(User::class);

        $this->generalOperation->expects(self::never())->method('setRoles');

        $this->operation->setRoles($user, ['role1']);
    }

    public function testThrowsExceptionOnRoleThatNotBelongToAuthUser(): void
    {
        $this->expectException(UserIsNotAuthorisedToAssignRoleException::class);

        $this->authentication->expects(self::once())->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);
        $authUser->method('isAdmin')->willReturn(false);
        $authUser->method('getRoles')->willReturn([]);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $user = $this->createMock(User::class);

        $this->generalOperation->expects(self::never())->method('setRoles');

        $this->operation->setRoles($user, ['role1']);
    }

    public function testAllowSetNotBelongingToAuthUserRoleIfAuthUserIsAdmin(): void
    {
        $this->authentication->expects(self::once())->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);
        $authUser->method('isAdmin')->willReturn(true);
        $authUser->method('getRoles')->willReturn([]);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $user = $this->createMock(User::class);

        $this->generalOperation->expects(self::once())->method('setRoles')->with($user, ['role1']);

        $this->operation->setRoles($user, ['role1']);
    }

    public function testAllowSetBelongingToAuthUserRole(): void
    {
        $this->authentication->expects(self::once())->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);
        $authUser->method('isAdmin')->willReturn(false);
        $authUser->method('getRoles')->willReturn(['role1', 'role2']);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $user = $this->createMock(User::class);

        $this->generalOperation->expects(self::once())->method('setRoles')->with($user, ['role1']);

        $this->operation->setRoles($user, ['role1']);
    }
}
