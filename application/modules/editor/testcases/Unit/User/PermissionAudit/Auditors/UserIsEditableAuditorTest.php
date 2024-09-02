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

namespace User\PermissionAudit\Auditors;

use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Checkers\UserIsEditableFeasibilityChecker;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Exception\UserIsNotEditableException;
use MittagQI\Translate5\User\Action\PermissionAudit\PermissionAuditContext;
use PHPUnit\Framework\TestCase;

class UserIsEditableAuditorTest extends TestCase
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
        $auditor = new UserIsEditableFeasibilityChecker();
        $this->assertEquals($expected, $auditor->supports($action));
    }

    public function testAssertGrantedEditableUser(): void
    {
        $user = $this->createMock(\ZfExtended_Models_User::class);
        $manager = $this->createMock(\ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

        $user->expects($this->once())
            ->method('__call')->willReturnMap([
                ['getEditable', [], true],
            ]);

        $auditor = new UserIsEditableFeasibilityChecker();
        $auditor->assertAllowed($user, $context);
    }

    public function testAssertGrantedNotEditableUser(): void
    {
        $user = $this->createMock(\ZfExtended_Models_User::class);
        $manager = $this->createMock(\ZfExtended_Models_User::class);
        $context = new PermissionAuditContext($manager);

        $user->expects($this->once())
            ->method('__call')->willReturnMap([
                ['getEditable', [], false],
            ]);

        $this->expectException(UserIsNotEditableException::class);

        $auditor = new UserIsEditableFeasibilityChecker();
        $auditor->assertAllowed($user, $context);
    }
}
