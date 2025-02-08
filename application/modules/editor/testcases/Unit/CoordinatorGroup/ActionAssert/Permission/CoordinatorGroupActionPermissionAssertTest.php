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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup\ActionAssert\Permission;

use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupActionPermissionAssert;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class CoordinatorGroupActionPermissionAssertTest extends TestCase
{
    public function testAssertGranted(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $permissionAuditorMock1 = $this->createMock(PermissionAssertInterface::class);
        $permissionAuditorMock1->method('supports')->willReturn(true);
        $permissionAuditorMock1
            ->expects(self::once())
            ->method('assertGranted')
            ->with(CoordinatorGroupAction::Delete, $group, $context);

        $permissionAuditorMock2 = $this->createMock(PermissionAssertInterface::class);
        $permissionAuditorMock2->method('supports')->willReturn(false);
        $permissionAuditorMock2->expects(self::never())->method('assertGranted');

        $auditor = new CoordinatorGroupActionPermissionAssert([$permissionAuditorMock1, $permissionAuditorMock2]);
        $auditor->assertGranted(CoordinatorGroupAction::Delete, $group, $context);
    }

    public function testAssertGrantedException(): void
    {
        $group = $this->createMock(CoordinatorGroup::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $permissionAuditorMock = $this->createMock(PermissionAssertInterface::class);
        $permissionAuditorMock->expects($this->once())
            ->method('assertGranted')
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));
        $permissionAuditorMock->expects($this->once())->method('supports')->willReturn(true);

        $auditor = new CoordinatorGroupActionPermissionAssert([$permissionAuditorMock]);

        $this->expectException(PermissionExceptionInterface::class);
        $auditor->assertGranted(CoordinatorGroupAction::Delete, $group, $context);
    }
}
