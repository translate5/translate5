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
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\WithAuthentication\UserDeleteOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

class UserDeleteOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $userPermissionAssert;

    private UserDeleteOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private UserDeleteOperation $operation;

    private ZfExtended_Logger $logger;

    public function setUp(): void
    {
        $this->userPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->generalOperation = $this->createMock(UserDeleteOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);

        $this->operation = new UserDeleteOperation(
            $this->userPermissionAssert,
            $this->generalOperation,
            $this->authentication,
            $this->userRepository,
            $this->logger,
        );
    }

    public function testThrowsPermissionExceptionOnDelete(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->userPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $user = $this->createMock(User::class);

        $this->operation->delete($user);
    }

    public function testThrowsPermissionExceptionOnForceDelete(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->userPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $user = $this->createMock(User::class);

        $this->operation->delete($user);
    }

    public function testDelete(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->userPermissionAssert->expects(self::once())->method('assertGranted');

        $user = $this->createMock(User::class);

        $this->generalOperation->method('delete')->with($user);

        $this->operation->delete($user);
    }

    public function testForceDelete(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->userPermissionAssert->expects(self::once())->method('assertGranted');

        $user = $this->createMock(User::class);

        $this->generalOperation->method('forceDelete')->with($user);

        $this->operation->forceDelete($user);
    }
}
