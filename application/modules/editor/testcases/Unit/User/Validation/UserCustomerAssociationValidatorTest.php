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

namespace MittagQI\Translate5\Test\Unit\User\Validation;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserCustomerAssociationValidatorTest extends TestCase
{
    private LspUserRepository|MockObject $lspUserRepository;

    private LspCustomerAssociationValidator|MockObject $lspCustomerAssociationValidator;

    private CustomerRepository|MockObject $customerRepository;

    private ActionPermissionAssertInterface|MockObject $customerPermissionAssert;

    private UserCustomerAssociationValidator $validator;

    public function setUp(): void
    {
        $this->lspUserRepository = $this->createMock(LspUserRepository::class);
        $this->lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->customerPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);

        $this->validator = new UserCustomerAssociationValidator(
            $this->lspUserRepository,
            $this->lspCustomerAssociationValidator,
            $this->customerRepository,
            $this->customerPermissionAssert
        );
    }

    public function testAssertCustomersMayBeAssociatedWithUserWithEmptyCustomers(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $this->validator->assertCustomersMayBeAssociatedWithUser($user, ...[]);
        $this->assertTrue(true);
    }

    public function testAssertCustomersMayBeAssociatedWithUserWithNotLspUser(): void
    {
        $this->lspUserRepository->method('findByUser')->willReturn(null);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $this->validator->assertCustomersMayBeAssociatedWithUser($user, 12);
        $this->assertTrue(true);
    }

    public function testAssertCustomersMayBeAssociatedWithUserWithNotLspUserAndEmptyCustomers(): void
    {
        $this->lspUserRepository->method('findByUser')->willReturn(null);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $this->validator->assertCustomersMayBeAssociatedWithUser($user, ...[]);
        $this->assertTrue(true);
    }

    public function testAssertFails(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUser = new LspUser(
            bin2hex(random_bytes(16)),
            $user,
            $this->createMock(LanguageServiceProvider::class),
        );

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->lspCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP')
            ->willThrowException(new CustomerDoesNotBelongToUserException(1, $user->getUserGuid()));

        $this->expectException(CustomerDoesNotBelongToUserException::class);

        $this->validator->assertCustomersMayBeAssociatedWithUser($user, 12);
    }

    public function testAssertSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUser = new LspUser(
            bin2hex(random_bytes(16)),
            $user,
            $this->createMock(LanguageServiceProvider::class),
        );

        $this->lspUserRepository->method('findByUser')->willReturn($lspUser);

        $this->lspCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP');

        $this->validator->assertCustomersMayBeAssociatedWithUser($user, 12);
    }
}
