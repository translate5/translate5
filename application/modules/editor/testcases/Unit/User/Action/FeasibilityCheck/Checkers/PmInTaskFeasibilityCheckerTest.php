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

namespace User\Action\FeasibilityCheck\Checkers;

use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Checkers\PmInTaskFeasibilityChecker;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Exception\PmInTaskException;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class PmInTaskFeasibilityCheckerTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::DELETE, true];
        yield [Action::UPDATE, false];
        yield [Action::CREATE, false];
        yield [Action::READ, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $auditor = new PmInTaskFeasibilityChecker($this->createMock(TaskRepository::class));
        $this->assertEquals($expected, $auditor->supports($action));
    }

    public function provideAssertAllowed(): iterable
    {
        yield [[], false];
        yield [[[
            'taskGuid' => bin2hex(random_bytes(16)),
        ]], true];
    }

    /**
     * @dataProvider provideAssertAllowed
     */
    public function testAssertAllowed(array $taskList, bool $expectException): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);

        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $taskRepositoryMock = $this->createMock(TaskRepository::class);
        $taskRepositoryMock->expects($this->once())
            ->method('loadListByPmGuid')
            ->with($user->getUserGuid())
            ->willReturn($taskList);

        if ($expectException) {
            $this->expectException(PmInTaskException::class);
        }

        $auditor = new PmInTaskFeasibilityChecker($taskRepositoryMock);
        $auditor->assertAllowed($user);
    }
}
