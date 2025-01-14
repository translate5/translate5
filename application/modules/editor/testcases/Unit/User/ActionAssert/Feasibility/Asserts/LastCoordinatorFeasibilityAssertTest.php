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

namespace MittagQI\Translate5\Test\Unit\User\ActionAssert\Feasibility\Asserts;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts\CoordinatorAsserts\LastCoordinatorFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class LastCoordinatorFeasibilityAssertTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::Delete, true];
        yield [Action::Update, false];
        yield [Action::Read, false];
        yield [Action::Create, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $feasibilityAssert = new LastCoordinatorFeasibilityAssert($this->createMock(JobCoordinatorRepository::class));
        $this->assertEquals($expected, $feasibilityAssert->supports($action));
    }

    public function testAssertAllowedNotCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $group = $this->createMock(CoordinatorGroup::class);

        $coordinator = new JobCoordinator('group', $user, $group);

        $coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $coordinatorRepository
            ->method('getCoordinatorsCount')
            ->willReturn(2);

        $groupPermissionAuditor = new LastCoordinatorFeasibilityAssert($coordinatorRepository);
        $groupPermissionAuditor->assertAllowed($coordinator);

        self::assertTrue(true);
    }

    public function testAssertAllowedLastCoordinator(): void
    {
        $user = $this->createMock(User::class);
        $group = $this->createMock(CoordinatorGroup::class);

        $coordinator = new JobCoordinator('group', $user, $group);

        $coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $coordinatorRepository
            ->method('getCoordinatorsCount')
            ->willReturn(1);

        $groupPermissionAuditor = new LastCoordinatorFeasibilityAssert($coordinatorRepository);
        $this->expectException(LastCoordinatorException::class);
        $groupPermissionAuditor->assertAllowed($coordinator);
    }
}
