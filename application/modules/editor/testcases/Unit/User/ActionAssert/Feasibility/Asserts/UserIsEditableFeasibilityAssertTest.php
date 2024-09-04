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

use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts\UserIsEditableFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\UserIsNotEditableException;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class UserIsEditableFeasibilityCheckerTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [Action::CREATE, false];
        yield [Action::UPDATE, true];
        yield [Action::DELETE, true];
        yield [Action::READ, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(Action $action, bool $expected): void
    {
        $auditor = new UserIsEditableFeasibilityAssert();
        $this->assertEquals($expected, $auditor->supports($action));
    }

    public function provideAssertAllowed(): iterable
    {
        yield [true, false];
        yield [false, true];
    }

    /**
     * @dataProvider provideAssertAllowed
     */
    public function testAssertAllowedEditableUser(bool $isEditable, bool $expectException): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);

        $user->expects($this->once())
            ->method('__call')->willReturnMap([
                ['getEditable', [], $isEditable],
            ]);

        if ($expectException) {
            $this->expectException(UserIsNotEditableException::class);
        }

        $auditor = new UserIsEditableFeasibilityAssert();
        $auditor->assertAllowed($user);
    }
}
