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

use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForJobCoordinatorException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForLspUserException;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\UserSetParentIdsOperation;
use MittagQI\Translate5\User\Validation\ParentUserValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_Entity_NotFoundException;

class UserSetParentIdsOperationTest extends TestCase
{
    private UserRepository|MockObject $userRepository;

    private ParentUserValidator|MockObject $parentUserValidator;

    private JobCoordinatorRepository|MockObject $coordinatorRepository;

    private UserSetParentIdsOperation $operation;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->parentUserValidator = $this->createMock(ParentUserValidator::class);

        $this->operation = new UserSetParentIdsOperation(
            $this->userRepository,
            $this->coordinatorRepository,
            $this->parentUserValidator,
        );
    }

    public function emptyParentIdProvider(): array
    {
        return [
            [''],
            [null],
        ];
    }

    /**
     * @dataProvider emptyParentIdProvider
     */
    public function testSetEmptyParent(?string $parentId): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('__call')->with('setParentIds', [',,']);

        $this->operation->setParentIds($user, $parentId);
    }

    public function testExceptionIsThrownIfParentUserNotFoundByIdentifier(): void
    {
        $this->expectException(ProvidedParentIdCannotBeEvaluatedToUserException::class);

        $user = $this->createMock(User::class);

        $this->userRepository
            ->method('resolveUser')
            ->willThrowException(
                $this->createMock(ZfExtended_Models_Entity_NotFoundException::class)
            );
        $this->userRepository->expects(self::never())->method('save');

        $user->expects(self::never())->method('__call')->with('setParentIds');

        $this->operation->setParentIds($user, 'oops');
    }

    /**
     * @return array<class-string<\Throwable>[]>
     */
    public function invalidParentExceptionProvider(): array
    {
        return [
            [InvalidParentUserProvidedForJobCoordinatorException::class],
            [InvalidParentUserProvidedForLspUserException::class],
        ];
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     * @dataProvider invalidParentExceptionProvider
     */
    public function testExceptionIsThrownOnNotAllowedParentProvided(string $exceptionClass): void
    {
        $this->expectException($exceptionClass);

        $user = $this->createMock(User::class);
        $parentUser = $this->createMock(User::class);

        $this->userRepository->method('resolveUser')->willReturn($parentUser);
        $this->userRepository->expects(self::never())->method('save');

        $user->expects(self::never())->method('__call')->with('setParentIds');

        $this->parentUserValidator
            ->expects(self::once())
            ->method('assertUserCanBeSetAsParentTo')
            ->willThrowException($this->createMock($exceptionClass))
        ;

        $this->operation->setParentIds($user, 'oops');
    }

    public function testListSetOnlyParentIdIfParentIsJobCoordinator(): void
    {
        $parentUser = $this->createMock(User::class);
        $parentUser->method('__call')->willReturnMap([
            ['getId', [], '4'],
            ['getParentIds', [], ',2,3,'],
        ]);

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('validate');
        $user->method('__call')->with('setParentIds', [',' . $parentUser->getId() . ',']);

        $this->userRepository->method('resolveUser')->willReturn($parentUser);
        $this->userRepository->expects(self::never())->method('save');

        $this->parentUserValidator->expects(self::once())->method('assertUserCanBeSetAsParentTo');

        $this->coordinatorRepository
            ->method('findByUser')
            ->willReturn(
                $this->createMock(JobCoordinator::class)
            );

        $this->operation->setParentIds($user, '4');
    }

    public function testParentUserParentIdsAreSetWithItsOwnId(): void
    {
        $parentUser = $this->createMock(User::class);
        $parentUser->method('__call')->willReturnMap([
            ['getId', [], '4'],
            ['getParentIds', [], ',2,3,'],
        ]);

        $user = $this->createMock(User::class);
        $user->expects(self::once())->method('validate');
        $user->method('__call')->with('setParentIds', [',2,3,' . $parentUser->getId() . ',']);

        $this->userRepository->method('resolveUser')->willReturn($parentUser);
        $this->userRepository->expects(self::never())->method('save');

        $this->parentUserValidator->expects(self::once())->method('assertUserCanBeSetAsParentTo');

        $this->coordinatorRepository->method('findByUser')->willReturn(null);

        $this->operation->setParentIds($user, '4');
    }
}
