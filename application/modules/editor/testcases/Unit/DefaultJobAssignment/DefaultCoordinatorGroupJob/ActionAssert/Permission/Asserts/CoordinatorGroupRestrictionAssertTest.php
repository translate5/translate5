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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultCoordinatorGroupJob\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupNotFoundException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\ActionAssert\Permission\Asserts\CoordinatorGroupRestrictionAssert;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\ActionAssert\Permission\Exception\NoAccessToDefaultCoordinatorGroupJobException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoordinatorGroupRestrictionAssertTest extends TestCase
{
    private ActionPermissionAssertInterface|MockObject $coordinatorGroupPermissionAssert;

    private CoordinatorGroupRepositoryInterface|MockObject $coordinatorGroupRepository;

    private CoordinatorGroupRestrictionAssert $assert;

    public function setUp(): void
    {
        $this->coordinatorGroupPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->coordinatorGroupRepository = $this->createMock(CoordinatorGroupRepositoryInterface::class);

        $this->assert = new CoordinatorGroupRestrictionAssert(
            $this->coordinatorGroupPermissionAssert,
            $this->coordinatorGroupRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Update, true];
        yield [DefaultJobAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(DefaultJobAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->assert->supports($action));
    }

    public function testAssertNotGrantedIfCoordinatorGroupNotFound(): void
    {
        $this->coordinatorGroupRepository->method('get')->willThrowException(new CoordinatorGroupNotFoundException(1));
        $defaultGroupJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $defaultGroupJob->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);
        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->expectException(NoAccessToDefaultCoordinatorGroupJobException::class);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultGroupJob, $context);
    }

    public function testAssertNotGrantedIfNoAccessToCoordinatorGroup(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $this->coordinatorGroupRepository->method('get')->willReturn($group);

        $defaultGroupJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $defaultGroupJob->method('__call')->willReturnMap([
            ['getGroupId', [], 1],
        ]);

        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->coordinatorGroupPermissionAssert
            ->expects($this->once())
            ->method('assertGranted')
            ->with(CoordinatorGroupAction::Update, $group)
            ->willThrowException(new class() extends \Exception implements PermissionExceptionInterface {
            });

        $this->expectException(NoAccessToDefaultCoordinatorGroupJobException::class);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultGroupJob, $context);
    }

    public function testAssertGranted(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $this->coordinatorGroupRepository->method('get')->willReturn($group);

        $defaultGroupJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $defaultGroupJob->method('__call')->willReturnMap([
            ['getGroupId', [], 1],
        ]);

        $viewer = $this->createMock(User::class);
        $context = new PermissionAssertContext($viewer);

        $this->assert->assertGranted(DefaultJobAction::Update, $defaultGroupJob, $context);

        self::assertTrue(true);
    }
}
