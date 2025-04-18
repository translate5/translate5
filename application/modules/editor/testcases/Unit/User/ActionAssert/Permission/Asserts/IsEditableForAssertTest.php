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

namespace MittagQI\Translate5\Test\Unit\User\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\User\ActionAssert\Permission\Asserts\IsEditableForAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class IsEditableForAssertTest extends TestCase
{
    public function provideSupports(): iterable
    {
        yield [UserAction::Update, true];
        yield [UserAction::Delete, true];
        yield [UserAction::Read, false];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(UserAction $action, bool $expected): void
    {
        $auditor = new IsEditableForAssert();
        $this->assertEquals($expected, $auditor->supports($action));
    }

    public function provideAssertAllowed(): iterable
    {
        yield [true, true, false];
        yield [true, false, false];
        yield [false, false, true];
        yield [false, true, true];
    }

    /**
     * @dataProvider provideAssertAllowed
     */
    public function testAssertGrantedEditableUser(bool $isEditable, bool $isCoordinator, bool $expectException): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $user->expects(self::once())->method('isEditableFor')->willReturn($isEditable);
        $user->method('isCoordinator')->willReturn($isCoordinator);

        if ($expectException) {
            $this->expectException(NoAccessException::class);
        }

        $auditor = new IsEditableForAssert();
        $auditor->assertGranted(UserAction::Update, $user, $context);
    }
}
