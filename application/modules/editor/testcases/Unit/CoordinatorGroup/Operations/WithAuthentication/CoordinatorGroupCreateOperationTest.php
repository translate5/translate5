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
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupCreateOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication\CoordinatorGroupCreateOperation;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;

class CoordinatorGroupCreateOperationTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $coordinatorRepository;

    private CoordinatorGroupCreateOperationInterface|MockObject $generalOperation;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private CoordinatorGroupCreateOperation $operation;

    public function setUp(): void
    {
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->generalOperation = $this->createMock(CoordinatorGroupCreateOperationInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new CoordinatorGroupCreateOperation(
            $this->generalOperation,
            $this->coordinatorRepository,
            $this->authentication,
            $this->userRepository,
        );
    }

    public function testCreateCoordinatorGroupAuthUserIsNotCoordinator(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $group = $this->createMock(CoordinatorGroup::class);

        $this->coordinatorRepository->method('findByUser')->with($authUser)->willReturn(null);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->generalOperation
            ->expects(self::once())
            ->method('createCoordinatorGroup')
            ->with('name', 'description', null)
            ->willReturn($group);

        $this->operation->createCoordinatorGroup('name', 'description');
    }

    public function testCreateCoordinatorGroupAuthUserIsCoordinator(): void
    {
        $this->authentication->method('getUserId')->willReturn(1);

        $authUser = $this->createMock(User::class);

        $user = $this->createMock(User::class);
        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $coordinator = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $group);

        $this->coordinatorRepository->method('findByUser')->with($authUser)->willReturn($coordinator);

        $this->userRepository->expects(self::once())->method('get')->with(1)->willReturn($authUser);

        $this->generalOperation
            ->expects(self::once())
            ->method('createCoordinatorGroup')
            ->with('name', 'description', (int) $group->getId())
            ->willReturn($group);

        $this->operation->createCoordinatorGroup('name', 'description');
    }
}
