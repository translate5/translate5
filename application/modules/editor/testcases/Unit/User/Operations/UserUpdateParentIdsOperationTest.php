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
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\Translate5\User\Operations\UserUpdateParentIdsOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Acl;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User as User;
use ZfExtended_ValidateException;

class UserUpdateParentIdsOperationTest extends TestCase
{
    private UserActionFeasibilityAssertInterface|MockObject $userActionFeasibilityChecker;
    private ZfExtended_Acl|MockObject $acl;
    private UserRepository|MockObject $userRepository;
    private UserUpdateParentIdsOperation $operation;

    protected function setUp(): void
    {
        $this->userActionFeasibilityChecker = $this->createMock(UserActionFeasibilityAssertInterface::class);
        $this->acl = $this->createMock(ZfExtended_Acl::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->operation = new UserUpdateParentIdsOperation(
            $this->userActionFeasibilityChecker,
            $this->acl,
            $this->userRepository
        );
    }

    public function testNothingDoneIfAuthUserCanNotSeeAllUsers(): void
    {
        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);
        $authUser->method('getRoles')->willReturn([]);

        $this->acl->method('isInAllowedRoles')->willReturn(false);
        $this->userRepository->expects(self::never())->method('save');

        $user->expects(self::never())->method('__call')->with('setParentIds');

        $this->operation->updateParentIdsBy($user, '1', $authUser);
    }

    public function testNothingDoneIfEmptyParentIdsPassed(): void
    {
        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);
        $authUser->method('getRoles')->willReturn([]);

        $this->acl->method('isInAllowedRoles')->willReturn(true);
        $this->userRepository->expects(self::never())->method('save');

        $user->expects(self::never())->method('__call')->with('setParentIds');

        $this->operation->updateParentIdsBy($user, '', $authUser);
    }

    public function testUpdateParentIdsThrowsFeasibilityException(): void
    {
        $this->expectException(FeasibilityExceptionInterface::class);

        $user = $this->createMock(User::class);

        $this->userActionFeasibilityChecker->method('assertAllowed')
            ->willThrowException($this->createMock(FeasibilityExceptionInterface::class));

        $this->operation->updateParentIds($user, [1, 2]);
    }

    public function testEmptyListSetOnEmptyArrayPassed(): void
    {
        $user = $this->createMock(User::class);

        $user->expects(self::once())->method('__call')->with('setParentIds', [',,']);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save');

        $this->operation->updateParentIds($user, []);
    }

    public function testListSetOnArrayOfParentIdsPassed(): void
    {
        $user = $this->createMock(User::class);

        $user->expects(self::once())->method('__call')->with('setParentIds', [',1,2,']);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save');

        $this->operation->updateParentIds($user, [1, 2]);
    }

    public function testUpdateParentIdsThrowsValidationException(): void
    {
        $this->expectException(ZfExtended_ValidateException::class);

        $user = $this->createMock(User::class);

        $user->expects(self::once())->method('validate')->willThrowException($this->createMock(ZfExtended_ValidateException::class));

        $this->operation->updateParentIds($user, [1, 2]);
    }

    public function testOnlyParentUserIdSetCauseItHasNoParentIdsItself(): void
    {
        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);
        $authUser->method('getRoles')->willReturn([]);

        $parentUser = $this->createMock(User::class);
        $parentUser->method('__call')->willReturnMap([
            ['getId', [], '1'],
            ['getParentIds', [], ''],
        ]);

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->userRepository->method('resolveUser')->willReturn($parentUser);
        $this->userRepository->expects(self::once())->method('save');

        $user->expects(self::once())
            ->method('__call')
            ->with('setParentIds', [',' . $parentUser->getId() . ',']);

        $this->operation->updateParentIdsBy($user, $parentUser->getId(), $authUser);
    }

    public function testParentUserParentIdsAreSetWithItsOwnId(): void
    {
        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);
        $authUser->method('getRoles')->willReturn([]);

        $parentUser = $this->createMock(User::class);
        $parentUser->method('__call')->willReturnMap([
            ['getId', [], '3'],
            ['getParentIds', [], ',1,2,'],
        ]);

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->userRepository->method('resolveUser')->willReturn($parentUser);
        $this->userRepository->expects(self::once())->method('save');

        $user->expects(self::once())
            ->method('__call')
            ->with('setParentIds', [',1,2,' . $parentUser->getId() . ',']);

        $this->operation->updateParentIdsBy($user, $parentUser->getId(), $authUser);
    }

    public function testExceptionIsThrownIfParentUserNotFoundByIdentifier(): void
    {
        $this->expectException(ProvidedParentIdCannotBeEvaluatedToUserException::class);

        $user = $this->createMock(User::class);
        $authUser = $this->createMock(User::class);
        $authUser->method('getRoles')->willReturn([]);

        $this->acl->method('isInAllowedRoles')->willReturn(true);

        $this->userRepository
            ->method('resolveUser')
            ->willThrowException(
                $this->createMock(ZfExtended_Models_Entity_NotFoundException::class)
            );
        $this->userRepository->expects(self::never())->method('save');

        $user->expects(self::never())->method('__call')->with('setParentIds');

        $this->operation->updateParentIdsBy($user, 'oops', $authUser);
    }
}
