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

namespace User\Action\FeasibilityCheck;

use MittagQI\Translate5\User\Action\Action;
use MittagQI\Translate5\User\Action\FeasibilityCheck\Checkers\FeasibilityCheckerInterface;
use MittagQI\Translate5\User\Action\FeasibilityCheck\UserActionFeasibilityChecker;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class UserActionFeasibilityCheckerTest extends TestCase
{
    public function testAssertAllowed(): void
    {
        $user = $this->createMock(ZfExtended_Models_User::class);
        $checker1 = $this->createMock(FeasibilityCheckerInterface::class);
        $checker1->expects($this->once())->method('supports')->with(Action::READ)->willReturn(true);
        $checker1->expects($this->once())->method('assertAllowed')->with($user);
        $checker2 = $this->createMock(FeasibilityCheckerInterface::class);
        $checker2->expects($this->once())->method('supports')->with(Action::READ)->willReturn(false);
        $checker2->expects($this->never())->method('assertAllowed');

        $checker = new UserActionFeasibilityChecker([$checker1, $checker2]);
        $checker->assertAllowed(Action::READ, $user);
    }
}
