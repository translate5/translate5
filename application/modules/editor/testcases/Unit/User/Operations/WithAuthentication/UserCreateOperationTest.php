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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssertInterface;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserCreateOperationInterface;
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\Exception\AttemptToSetLspForNonJobCoordinatorException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserCreateOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;

class UserCreateOperationTest extends TestCase
{
    private UserCreateOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private LspRepositoryInterface|MockObject $lspRepository;

    private LspActionPermissionAssertInterface|MockObject $lspPermissionAssert;

    private UserRepository|MockObject $userRepository;

    private UserCreateOperation $operation;

    public function setUp(): void
    {
        $this->generalOperation = $this->createMock(UserCreateOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);
        $this->lspPermissionAssert = $this->createMock(LspActionPermissionAssertInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new UserCreateOperation(
            $this->generalOperation,
            $this->authentication,
            $this->coordinatorRepository,
            $this->lspRepository,
            $this->lspPermissionAssert,
            $this->userRepository,
        );
    }

    public function testThrowsExceptionOnAttemptToSetNotAllowedLsp(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->authentication->method('getUserId')->willReturn(1);

        $this->lspPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->lspRepository->method('get')->willReturn($this->createMock(LanguageServiceProvider::class));

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
        );

        $this->operation->createUser($dto);
    }

    public function testAssignCustomers(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(false);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $user = $this->createMock(User::class);

        $associatedCustomerIds = [1, 2, 3];

        $this->generalOperation->method('assignCustomers')->with($user, $associatedCustomerIds);

        $this->operation->assignCustomers($user, $associatedCustomerIds);
    }
}
