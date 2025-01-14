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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\Asserts\RoleBasedPermissionAssert;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class RuleBasedPermissionAssertTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [CoordinatorGroupAction::Delete, true];
        yield [CoordinatorGroupAction::Update, true];
        yield [CoordinatorGroupAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(CoordinatorGroupAction $action, bool $expected): void
    {
        $assert = new RoleBasedPermissionAssert(
            $this->createMock(JobCoordinatorRepository::class)
        );
        $this->assertEquals($expected, $assert->supports($action));
    }

    public function testAssertGrantedAdmin(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('isAdmin')->willReturn(true);

        $assert = new RoleBasedPermissionAssert(
            $this->createMock(JobCoordinatorRepository::class)
        );

        $assert->assertGranted(CoordinatorGroupAction::Update, $group, $context);

        self::assertTrue(true);
    }

    public function isDirectCoordinatorGroupProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider isDirectCoordinatorGroupProvider
     */
    public function testAssertGrantedPm(bool $isTopRankGroup): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $group->expects(self::once())->method('isTopRankGroup')->willReturn($isTopRankGroup);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('isPm')->willReturn(true);

        if (! $isTopRankGroup) {
            $this->expectException(NoAccessException::class);
        }

        $assert = new RoleBasedPermissionAssert(
            $this->createMock(JobCoordinatorRepository::class)
        );
        $assert->assertGranted(CoordinatorGroupAction::Update, $group, $context);
    }

    public function testAssertNotGrantedIfNotAdminOrPmAndNotCoordinator(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('isAdmin')->willReturn(false);
        $manager->method('isPm')->willReturn(false);
        $manager->method('isCoordinator')->willReturn(false);

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);

        $this->expectException(NoAccessException::class);

        $assert = new RoleBasedPermissionAssert($jsRepo);
        $assert->assertGranted(CoordinatorGroupAction::Update, $group, $context);
    }

    public function testAssertNotGrantedMutationToCoordinatorGroupOfCoordinator(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $group->expects(self::once())->method('same')->willReturn(true);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('isAdmin')->willReturn(false);
        $manager->method('isPm')->willReturn(false);
        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = new JobCoordinator(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            $this->createMock(User::class),
            $group
        );

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);
        $jsRepo->expects($this->once())
            ->method('findByUser')
            ->willReturn($coordinator);

        $this->expectException(NoAccessException::class);

        $assert = new RoleBasedPermissionAssert($jsRepo);
        $assert->assertGranted(CoordinatorGroupAction::Update, $group, $context);
    }

    public function testAssertGrantedReadToCoordinatorGroupOfCoordinator(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $group->expects(self::once())->method('same')->willReturn(true);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('isAdmin')->willReturn(false);
        $manager->method('isPm')->willReturn(false);
        $manager->method('isCoordinator')->willReturn(true);

        $coordinator = new JobCoordinator(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            $this->createMock(User::class),
            $group
        );

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);
        $jsRepo->expects($this->once())
            ->method('findByUser')
            ->willReturn($coordinator);

        $assert = new RoleBasedPermissionAssert($jsRepo);
        $assert->assertGranted(CoordinatorGroupAction::Read, $group, $context);

        self::assertTrue(true);
    }

    public function testAssertGrantedToSubCoordinatorGroupOfCoordinator(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $group->expects(self::once())->method('isSubGroupOf')->willReturn(true);

        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $manager->method('isAdmin')->willReturn(false);
        $manager->method('isPm')->willReturn(false);
        $manager->method('isCoordinator')->willReturn(true);

        $coordinatorGroup = $this->createMock(CoordinatorGroup::class);
        $coordinatorGroup->expects(self::once())->method('same')->willReturn(false);
        $coordinator = new JobCoordinator(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            $this->createMock(User::class),
            $coordinatorGroup
        );

        $jsRepo = $this->createMock(JobCoordinatorRepository::class);
        $jsRepo->expects($this->once())
            ->method('findByUser')
            ->willReturn($coordinator);

        $assert = new RoleBasedPermissionAssert($jsRepo);
        $assert->assertGranted(CoordinatorGroupAction::Update, $group, $context);
    }
}
