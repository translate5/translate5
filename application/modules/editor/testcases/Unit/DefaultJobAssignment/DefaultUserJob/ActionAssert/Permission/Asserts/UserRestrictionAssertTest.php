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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\Asserts;

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\Asserts\UserRestrictionAssert;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class UserRestrictionAssertTest extends TestCase
{
    private ActionPermissionAssertInterface $userPermissionAssert;

    private UserRepository $userRepository;

    private UserRestrictionAssert $userRestrictionAssert;

    public function setUp(): void
    {
        $this->userPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->userRestrictionAssert = new UserRestrictionAssert(
            $this->userPermissionAssert,
            $this->userRepository,
        );
    }

    public function provideSupports(): iterable
    {
        yield [DefaultJobAction::Update, true];
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Read, true];
    }

    /**
     * @dataProvider provideSupports
     */
    public function testSupports(DefaultJobAction $action, bool $expected): void
    {
        $this->assertEquals($expected, $this->userRestrictionAssert->supports($action));
    }

    public function provideAssertAllowed(): iterable
    {
        yield [DefaultJobAction::Update, false];
        yield [DefaultJobAction::Delete, true];
        yield [DefaultJobAction::Read, false];
    }

    /**
     * @dataProvider provideAssertAllowed
     */
    public function testAssertGranted(DefaultJobAction $action, bool $hasAccessToUser): void
    {
        $user = $this->createMock(User::class);
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $this->userRepository->method('getByGuid')->willReturn($user);

        if (! $hasAccessToUser) {
            $this->userPermissionAssert
                ->method('assertGranted')
                ->willThrowException($this->createMock(PermissionExceptionInterface::class))
            ;
            $this->expectException(NoAccessException::class);
        }

        $defaultJob = $this->createMock(DefaultUserJob::class);
        $defaultJob->method('__call')->willReturn('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}');

        $this->userRestrictionAssert->assertGranted($action, $defaultJob, $context);

        self::assertTrue(true);
    }

    public function testAssertNotGrantedOnUserNotFound(): void
    {
        $manager = $this->createMock(User::class);
        $context = new PermissionAssertContext($manager);

        $this->userRepository->method('getByGuid')->willThrowException(new InexistentUserException('id'));

        $this->expectException(NoAccessException::class);

        $defaultJob = $this->createMock(DefaultUserJob::class);
        $defaultJob->method('__call')->willReturn('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}');

        $this->userRestrictionAssert->assertGranted(DefaultJobAction::Read, $defaultJob, $context);

        self::assertTrue(true);
    }
}
