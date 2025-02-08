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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup;

use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinator;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class JobCoordinatorTest extends TestCase
{
    public function coordinatorGroupDataProvider(): iterable
    {
        yield 'same group' => [
            'testUserFromSameGroup' => true,
            'isCoordinator' => false,
            'testUserFromSubGroup' => false,
            'expected' => true,
        ];

        yield 'different group, test user is not coordinator' => [
            'testUserFromSameGroup' => false,
            'isCoordinator' => false,
            'testUserFromSubGroup' => false,
            'expected' => false,
        ];

        yield 'different group, test user is coordinator, user not from sub group' => [
            'testUserFromSameGroup' => false,
            'isCoordinator' => true,
            'testUserFromSubGroup' => false,
            'expected' => false,
        ];

        yield 'different group, test user is coordinator, user from sub group' => [
            'testUserFromSameGroup' => false,
            'isCoordinator' => true,
            'testUserFromSubGroup' => true,
            'expected' => true,
        ];
    }

    /**
     * @dataProvider coordinatorGroupDataProvider
     */
    public function testIsSupervisorOf(
        bool $testUserFromSameGroup,
        bool $isCoordinator,
        bool $testUserFromSubGroup,
        bool $expected
    ): void {
        $coordinatorUser = $this->createMock(User::class);
        $coordinatorGroup = $this->createMock(CoordinatorGroup::class);
        $coordinatorGroup->method('same')->willReturn($testUserFromSameGroup);

        $coordinator = new JobCoordinator('coordinator-guid', $coordinatorUser, $coordinatorGroup);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);
        $user->method('isCoordinator')->willReturn($isCoordinator);

        $group = $this->createMock(CoordinatorGroup::class);
        $group->method('isSubGroupOf')->willReturn($testUserFromSubGroup);

        $groupUser = new CoordinatorGroupUser('group-guid', $user, $group);

        self::assertSame($expected, $coordinator->isSupervisorOf($groupUser));
    }
}
