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
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use MittagQI\Translate5\User\Operations\UserSetPasswordOperation;
use MittagQI\Translate5\User\Operations\UserUpdatePasswordOperation;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserUpdatePasswordOperationTest extends TestCase
{
    private UserRepository|MockObject $userRepository;

    private UserSetPasswordOperation|MockObject $setPassword;

    private ResetPasswordEmail|MockObject $resetPasswordEmail;

    private UserUpdatePasswordOperation $operation;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->setPassword = $this->createMock(UserSetPasswordOperation::class);
        $this->resetPasswordEmail = $this->createMock(ResetPasswordEmail::class);

        $this->operation = new UserUpdatePasswordOperation(
            $this->userRepository,
            $this->setPassword,
            $this->resetPasswordEmail
        );
    }

    public function testUpdatePassword(): void
    {
        $user = $this->createMock(User::class);

        $this->setPassword->expects(self::once())->method('setPassword')->with($user, 'password');
        $this->userRepository->expects(self::once())->method('save')->with($user);
        $this->resetPasswordEmail->expects(self::never())->method('sendTo');

        $this->operation->updatePassword($user, 'password');
    }

    public function testResetPassword(): void
    {
        $user = $this->createMock(User::class);

        $this->setPassword->expects(self::once())->method('setPassword')->with($user, null);
        $this->userRepository->expects(self::once())->method('save')->with($user);
        $this->resetPasswordEmail->expects(self::once())->method('sendTo');

        $this->operation->updatePassword($user, null);
    }

    public function testEmailNotSentOnPasswordSetException(): void
    {
        $this->expectException(\Exception::class);
        $user = $this->createMock(User::class);

        $this->setPassword->expects(self::once())->method('setPassword')->willThrowException(new \Exception());
        $this->userRepository->expects(self::never())->method('save')->with($user);
        $this->resetPasswordEmail->expects(self::never())->method('sendTo');

        $this->operation->updatePassword($user, null);
    }
}
