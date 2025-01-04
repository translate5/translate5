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

namespace MittagQI\Translate5\Test\Unit\User\Validation;

use MittagQI\Translate5\Acl\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\Acl\Exception\RolesCannotBeSetForUserException;
use MittagQI\Translate5\Acl\ExpandRolesService;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Acl\Validation\RolesValidator;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;

class RolesValidatorTest extends TestCase
{
    private const EXPANDING_ROLE = 'expanding-role';

    private ZfExtended_Acl|MockObject $acl;

    private RolesValidator $validator;

    private ExpandRolesService|MockObject $expandRolesService;

    private LspUserRepositoryInterface|MockObject $lspUserRepository;

    public function setUp(): void
    {
        $this->acl = $this->createMock(ZfExtended_Acl::class);
        $this->expandRolesService = $this->createMock(ExpandRolesService::class);
        $this->lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);

        $this->validator = new RolesValidator(
            $this->acl,
            $this->expandRolesService,
            $this->lspUserRepository,
        );
    }

    public function testNothingHappensOnEmptyRoleList(): void
    {
        $this->validator->assertRolesDontConflict([]);

        $this->assertTrue(true);
    }

    public function conflictingRolesProvider(): iterable
    {
        yield [[Roles::JOB_COORDINATOR, Roles::ADMIN]];
        yield [[Roles::JOB_COORDINATOR, Roles::SYSTEMADMIN]];
        yield [[Roles::JOB_COORDINATOR, Roles::PM]];
        yield [[Roles::JOB_COORDINATOR, Roles::CLIENTPM]];
        yield [[Roles::TERMPM_ALLCLIENTS, self::EXPANDING_ROLE]];
    }

    /**
     * @dataProvider conflictingRolesProvider
     */
    public function testThrowsExceptionOnConflictingRoles(array $conflictingRoles): void
    {
        $this->expandRolesService->method('expandListWithAutoRoles')->willReturnCallback(
            static fn (array $roles) => in_array(self::EXPANDING_ROLE, $roles)
                ? array_merge($roles, [Roles::CLIENTPM])
                : $roles
        );
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $this->validator->assertRolesDontConflict($conflictingRoles);
    }

    public function testEverythingOkWithPotentiallyConflictingRole(): void
    {
        $this->validator->assertRolesDontConflict([Roles::JOB_COORDINATOR, 'other-role']);

        $this->assertTrue(true);
    }

    public function testThrowsExceptionWhenRolePopulatesToConflictingRole(): void
    {
        $this->expectException(ConflictingRolesExceptionInterface::class);

        $roleToBePopulated = 'role-to-be-populated';

        $this->acl
            ->method('getRightsToRolesAndResource')
            ->with([$roleToBePopulated])
            ->willReturn([Roles::JOB_COORDINATOR, Roles::ADMIN]);

        $this->validator->assertRolesDontConflict([Roles::JOB_COORDINATOR, $roleToBePopulated]);
    }

    public function aclAllowedProvider(): iterable
    {
        yield [true];
        yield [false];
        yield [null];
    }

    /**
     * @dataProvider aclAllowedProvider
     */
    public function testHasAclPermissionToSetRole(?bool $aclAllowed): void
    {
        $viewer = new User();

        if (null !== $aclAllowed) {
            $this->acl->method('isInAllowedRoles')->willReturn($aclAllowed);

            self::assertSame($aclAllowed, $this->validator->hasAclPermissionToSetRole($viewer, 'role'));

            return;
        }

        $this->acl->method('isInAllowedRoles')->willThrowException(new \Zend_Acl_Exception());

        self::assertFalse($this->validator->hasAclPermissionToSetRole($viewer, 'role'));
    }

    public function assertRolesCanBeSetForUserProvider(): iterable
    {
        $lspUser = $this->createMock(LspUser::class);

        yield 'lsp user + admin role' => [
            [Roles::ADMIN],
            RolesCannotBeSetForUserException::class,
            $lspUser,
        ];

        yield 'not lsp user + coordinator role' => [
            [Roles::JOB_COORDINATOR],
            RolesCannotBeSetForUserException::class,
            null,
        ];

        yield 'lsp user + editor role' => [
            [Roles::EDITOR],
            null,
            $lspUser,
        ];

        yield 'not lsp user + admin role' => [
            [Roles::ADMIN],
            null,
            null,
        ];
    }

    /**
     * @dataProvider assertRolesCanBeSetForUserProvider
     */
    public function testAssertRolesCanBeSetForUser(array $roles, ?string $expectedException, ?LspUser $lspUser): void
    {
        if ($lspUser) {
            $this->lspUserRepository->method('findByUser')->willReturn($lspUser);
        }

        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $viewer = new User();

        $this->validator->assertRolesCanBeSetForUser($roles, $viewer);

        self::assertTrue(true);
    }

    public function assertUserCanSetRolesProvider(): iterable
    {
        $user = $this->createMock(User::class);

        yield 'do not have acl permission' => [
            [Roles::ADMIN],
            false,
            UserIsNotAuthorisedToAssignRoleException::class,
            $user,
        ];

        $adminUser = $this->createMock(User::class);
        $adminUser->method('isAdmin')->willReturn(true);

        yield 'user is admin' => [
            [Roles::JOB_COORDINATOR],
            true,
            null,
            $adminUser,
        ];

        $pmUser = $this->createMock(User::class);
        $pmUser->method('isPm')->willReturn(true);

        yield 'user is pm' => [
            [Roles::EDITOR],
            true,
            null,
            $pmUser,
        ];

        $clientPmUser = $this->createMock(User::class);
        $clientPmUser->method('isClientPm')->willReturn(true);

        yield 'user is clientpm' => [
            [Roles::ADMIN],
            false,
            UserIsNotAuthorisedToAssignRoleException::class,
            $clientPmUser,
        ];

        yield 'user do not have role he tries to set' => [
            [Roles::ADMIN],
            false,
            UserIsNotAuthorisedToAssignRoleException::class,
            $user,
        ];
    }

    /**
     * @dataProvider assertUserCanSetRolesProvider
     */
    public function testAssertUserCanSetRoles(array $roles, bool $hasAclPermission, ?string $expectedException, User $user): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $this->acl->method('isInAllowedRoles')->willReturn($hasAclPermission);

        $this->validator->assertUserCanSetRoles($user, $roles);

        self::assertTrue(true);
    }
}
