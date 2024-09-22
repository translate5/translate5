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

use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\UserSetPasswordOperation;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_ValidateException;

class UserSetPasswordOperationTest extends TestCase
{
    public function testSetNull(): void
    {
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $user = $this->createMock(User::class);

        $user->expects(self::once())->method('__call')->with('setPasswd', [null]);

        $service = new UserSetPasswordOperation($authentication);
        $service->setPassword($user, null);
    }

    public function testThrowsExceptionOnEmptyString(): void
    {
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $user = $this->createMock(User::class);

        $this->expectException(\InvalidArgumentException::class);

        $user->expects(self::never())->method('__call')->with('setPasswd');

        $service = new UserSetPasswordOperation($authentication);
        $service->setPassword($user, '');
    }

    public function testThrowsValidationException(): void
    {
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $user = $this->createMock(User::class);

        $this->expectException(ZfExtended_ValidateException::class);

        $password = 'qwerty';

        $user->expects(self::once())->method('__call')->with('setPasswd', [$password]);
        $user->expects(self::once())
            ->method('validate')
            ->willThrowException($this->createMock(ZfExtended_ValidateException::class));

        $service = new UserSetPasswordOperation($authentication);
        $service->setPassword($user, $password);
    }

    public function testSetPassword(): void
    {
        $authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $user = new class() extends User {
            private int $count = 0;

            private int $countValidate = 0;

            public function setPasswd(?string $password): void
            {
                $this->count++;

                if ($this->count === 1) {
                    TestCase::assertEquals('qwerty', $password);
                } elseif ($this->count === 2) {
                    TestCase::assertEquals('secure_password', $password);
                } else {
                    TestCase::fail('Unexpected call');
                }
            }

            public function validate(): void
            {
                $this->countValidate++;
            }

            public function count(): int
            {
                return $this->count;
            }

            public function countValidate(): int
            {
                return $this->countValidate;
            }
        };

        $password = 'qwerty';
        $encodedPassword = 'secure_password';

        $authentication
            ->expects(self::once())
            ->method('createSecurePassword')
            ->with($password)
            ->willReturn($encodedPassword);

        $service = new UserSetPasswordOperation($authentication);
        $service->setPassword($user, $password);

        self::assertEquals(2, $user->count());
        self::assertEquals(1, $user->countValidate());
    }
}
