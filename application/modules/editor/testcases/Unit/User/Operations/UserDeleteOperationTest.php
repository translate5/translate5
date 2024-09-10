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

namespace MittagQI\Translate5\Test\Unit\User\Operations;

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Operations\UserDeleteOperation;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User as User;

class UserDeleteOperationTest extends TestCase
{
    public function deleteDataProvider(): array
    {
        return [
            'action allowed' => [true],
            'action not allowed' => [false],
        ];
    }

    /**
     * @dataProvider deleteDataProvider
     */
    public function testDelete(bool $actionAllowed): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $feasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $user = $this->createMock(User::class);

        if ($actionAllowed) {
            $feasibilityChecker
                ->expects($this->once())
                ->method('assertAllowed')
                ->with(Action::DELETE, $user);
            $userRepository->expects(self::once())->method('delete')->with($user);
        } else {
            $exception = $this->createMock(FeasibilityExceptionInterface::class);
            $feasibilityChecker
                ->expects(self::once())
                ->method('assertAllowed')
                ->with(Action::DELETE, $user)
                ->willThrowException($exception);
            $this->expectException(FeasibilityExceptionInterface::class);
        }

        $service = new UserDeleteOperation($userRepository, $feasibilityChecker);
        $service->delete($user);
    }
}
