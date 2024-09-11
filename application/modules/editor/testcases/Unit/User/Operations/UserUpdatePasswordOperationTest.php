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
use MittagQI\Translate5\User\Operations\UserUpdatePasswordOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User as User;
use ZfExtended_ValidateException;

class UserUpdatePasswordOperationTest extends TestCase
{
    private UserRepository|MockObject $userRepository;
    private ZfExtended_AuthenticationInterface|MockObject $authentication;
    private UserUpdatePasswordOperation $operation;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);

        $this->operation = new UserUpdatePasswordOperation(
            $this->userRepository,
            $this->authentication
        );
    }

    public function passwordProvider(): iterable
    {
        yield 'null' => [null, null];
        yield 'empty string' => ['', null];
        yield 'password' => ['password', 'secure_password'];
    }

    /**
     * @dataProvider passwordProvider
     */
    public function testUpdatePassword(?string $password, ?string $expectedSet): void
    {
        $user = $this->createMock(User::class);

        if (! empty($password)) {
            $this->authentication
                ->expects(self::once())
                ->method('createSecurePassword')
                ->with($password)
                ->willReturn($expectedSet)
            ;
        } else {
            $this->authentication->expects(self::never())->method('createSecurePassword');
        }

        $user->expects(self::once())->method('__call')->with('setPasswd', [$expectedSet]);
        $user->expects(self::once())->method('validate');

        $this->userRepository->expects(self::once())->method('save')->with($user);

        $this->operation->updatePassword($user, $password);
    }

    public function testUpdateThrowsValidateException(): void
    {
        $this->expectException(ZfExtended_ValidateException::class);

        $user = $this->createMock(User::class);

        $user->expects(self::once())->method('validate')->willThrowException(new ZfExtended_ValidateException());

        $this->userRepository->expects(self::never())->method('save');

        $this->operation->updatePassword($user, 'password');
    }
}
