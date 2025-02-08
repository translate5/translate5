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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup\Operations\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupUpdateOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Operations\DTO\UpdateCoordinatorGroupDto;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupUpdateOperation;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

class LspUpdateOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $groupPermissionAssert;

    private CoordinatorGroupUpdateOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private CoordinatorGroupUpdateOperation $operation;

    private ZfExtended_Logger $logger;

    public function setUp(): void
    {
        $this->groupPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->generalOperation = $this->createMock(CoordinatorGroupUpdateOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);

        $this->operation = new CoordinatorGroupUpdateOperation(
            $this->generalOperation,
            $this->groupPermissionAssert,
            $this->authentication,
            $this->userRepository,
            $this->logger,
        );
    }

    public function testThrowsPermissionExceptionOnDelete(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->groupPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(CoordinatorGroupAction::Update)
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $group = $this->createMock(CoordinatorGroup::class);

        $dto = new UpdateCoordinatorGroupDto('name', 'description');

        $this->operation->updateCoordinatorGroup($group, $dto);
    }

    public function testUpdateLsp(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->groupPermissionAssert->expects(self::once())->method('assertGranted')->with(CoordinatorGroupAction::Update);

        $group = $this->createMock(CoordinatorGroup::class);

        $this->generalOperation->expects(self::once())->method('updateCoordinatorGroup')->with($group);

        $dto = new UpdateCoordinatorGroupDto('name', 'description');

        $this->operation->updateCoordinatorGroup($group, $dto);
    }
}
