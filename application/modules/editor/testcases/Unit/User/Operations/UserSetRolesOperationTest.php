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

use MittagQI\Translate5\Acl\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\Acl\ExpandRolesService;
use MittagQI\Translate5\Acl\Validation\RolesValidator;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\Setters\UserRolesSetter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserSetRolesOperationTest extends TestCase
{
    private RolesValidator|MockObject $rolesValidator;

    private ExpandRolesService|MockObject $expandRolesService;

    private UserRolesSetter $operation;

    protected function setUp(): void
    {
        $this->rolesValidator = $this->createMock(RolesValidator::class);
        $this->expandRolesService = $this->createMock(ExpandRolesService::class);

        $this->operation = new UserRolesSetter(
            $this->rolesValidator,
            $this->expandRolesService,
        );
    }

    public function testSetRolesWithEmptyRoles(): void
    {
        $this->rolesValidator->expects(self::once())->method('assertRolesDontConflict');
        $this->expandRolesService->method('expandListWithAutoRoles')->willReturn([]);

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setRoles')->with([]);
        $user->expects(self::once())->method('validate');

        $this->operation->setExpendRolesService($user, []);
    }

    public function testSetDefaultRolesWithNoRolesPassed(): void
    {
        $this->rolesValidator->expects(self::once())->method('assertRolesDontConflict');
        $this->expandRolesService->method('expandListWithAutoRoles')->willReturn(['defaultRole']);

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('setRoles')->with(['defaultRole']);
        $user->expects(self::once())->method('validate');

        $this->operation->setExpendRolesService($user, []);
    }

    public function testInitRoles(): void
    {
        $user = $this->createMock(User::class);
        $roles = ['role1', 'role2'];

        $this->rolesValidator->expects(self::once())
            ->method('assertRolesDontConflict')
            ->with($roles);

        $this->expandRolesService->expects(self::once())
            ->method('expandListWithAutoRoles')
            ->with($roles, [])
            ->willReturn($roles);

        $user->expects(self::once())->method('setRoles')->with($roles);
        $user->expects(self::once())->method('validate');

        $this->operation->setExpendRolesService($user, $roles);
    }

    public function testNothingDoneIfRolesHaveConflict(): void
    {
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $user = $this->createMock(User::class);

        $exception = $this->createMock(ConflictingRolesExceptionInterface::class);

        $this->rolesValidator->method('assertRolesDontConflict')->willThrowException($exception);

        $user->expects($this->never())->method('setRoles');

        $this->operation->setExpendRolesService($user, ['role1']);
    }
}
