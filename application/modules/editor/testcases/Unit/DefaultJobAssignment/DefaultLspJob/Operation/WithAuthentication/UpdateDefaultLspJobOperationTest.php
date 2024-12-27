<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\DefaultJobAssignment\Contract\UpdateDefaultLspJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\WithAuthentication\UpdateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DTO\UpdateDefaultJobDto;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;
use ZfExtended_NotAuthenticatedException;

class UpdateDefaultLspJobOperationTest extends TestCase
{
    private MockObject|ZfExtended_AuthenticationInterface $authentication;

    private UserRepository $userRepository;

    private MockObject|UpdateDefaultLspJobOperationInterface $updateOperation;

    private MockObject|ActionPermissionAssertInterface $defaultLspJobPermissionAssert;

    private MockObject|ActionPermissionAssertInterface $userActionPermissionAssert;

    private MockObject|ZfExtended_Logger $logger;

    private UpdateDefaultLspJobOperation $operation;

    public function setUp(): void
    {
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->updateOperation = $this->createMock(UpdateDefaultLspJobOperationInterface::class);
        $this->defaultLspJobPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->userActionPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);

        $this->operation = new UpdateDefaultLspJobOperation(
            $this->authentication,
            $this->userRepository,
            $this->updateOperation,
            $this->defaultLspJobPermissionAssert,
            $this->userActionPermissionAssert,
            $this->logger,
        );
    }

    public function testThrowsNotAuthenticatedException(): void
    {
        $defaultLspJob = $this->createMock(DefaultLspJob::class);

        $this->userRepository->method('get')->willThrowException(new InexistentUserException('1'));

        $this->expectException(ZfExtended_NotAuthenticatedException::class);

        $this->updateOperation->expects(self::never())->method('updateJob');

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            null,
            1.0,
            null,
            null,
            null,
        );

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testNotAllowsToUpdateIfUserDontHavePermissionTo(): void
    {
        $defaultLspJob = $this->createMock(DefaultLspJob::class);

        $actor = $this->createMock(User::class);

        $this->userRepository->method('get')->willReturn($actor);

        $this->defaultLspJobPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(DefaultJobAction::Update, $defaultLspJob)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(PermissionExceptionInterface::class);

        $this->logger->expects(self::once())->method('__call');

        $this->updateOperation->expects(self::never())->method('updateJob');

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            null,
            1.0,
            null,
            null,
            null,
        );

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testNotAllowsToUpdateIfActorNotAllowedToReadUser(): void
    {
        $defaultLspJob = $this->createMock(DefaultLspJob::class);

        $actor = $this->createMock(User::class);

        $this->userRepository->method('get')->willReturn($actor);

        $userToSet = $this->createMock(User::class);

        $this->userRepository->method('getByGuid')->willReturn($userToSet);

        $this->userActionPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(UserAction::Read, $userToSet)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(PermissionExceptionInterface::class);

        $this->logger->expects(self::once())->method('__call');

        $this->updateOperation->expects(self::never())->method('updateJob');

        $dto = new UpdateDefaultJobDto(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testAllowsToUpdate(): void
    {
        $defaultLspJob = $this->createMock(DefaultLspJob::class);

        $actor = $this->createMock(User::class);

        $this->userRepository->method('get')->willReturn($actor);

        $this->logger->expects(self::once())->method('__call');

        $this->updateOperation->expects(self::once())->method('updateJob');

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            null,
            1.0,
            null,
            null,
            null,
        );

        $this->operation->updateJob($defaultLspJob, $dto);
    }
}
