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

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User as User;

class UserCustomerAssociationValidatorTest extends TestCase
{
    public function customerIdsProvider(): array
    {
        return [
            [1, true],
            [2, true],
            [3, false],
        ];
    }

    /**
     * @dataProvider customerIdsProvider
     */
    public function testAssertCustomersAreSubsetForUser(int $customerId, bool $isSubset): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);

        $user = $this->createMock(User::class);
        $user->method('getCustomersArray')->willReturn([1, 2]);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $customer = $this->createMock(Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], $customerId],
        ]);

        if (! $isSubset) {
            $this->expectException(CustomerDoesNotBelongToUserException::class);
        }

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersAreSubsetForUser([$customer], $user);
        $this->assertTrue(true);
    }

    public function testAssertCustomersAreSubsetForUserWithEmptyCustomers(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);

        $user = $this->createMock(User::class);
        $user->method('getCustomersArray')->willReturn([1, 2]);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersAreSubsetForUser([], $user);
        $this->assertTrue(true);
    }

    public function testAssertCustomersMayBeAssociatedWithUserWithEmptyCustomers(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersMayBeAssociatedWithUser([], $user);
        $this->assertTrue(true);
    }

    public function testAssertCustomersMayBeAssociatedWithUserWithNotLspUser(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);
        $lspUserRepository->method('findByUser')->willReturn(null);

        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $customer = $this->createMock(Customer::class);

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersMayBeAssociatedWithUser([$customer], $user);
        $this->assertTrue(true);
    }

    public function testAssertCustomersMayBeAssociatedWithUserWithNotLspUserAndEmptyCustomers(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);
        $lspUserRepository->method('findByUser')->willReturn(null);

        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersMayBeAssociatedWithUser([], $user);
        $this->assertTrue(true);
    }

    public function testAssertFails(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUser = new LspUser(
            bin2hex(random_bytes(16)),
            $user,
            $this->createMock(LanguageServiceProvider::class),
        );

        $lspUserRepository->method('findByUser')->willReturn($lspUser);

        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP')
            ->willThrowException(new CustomerDoesNotBelongToUserException(1, $user->getUserGuid()));

        $customer = $this->createMock(Customer::class);

        $this->expectException(CustomerDoesNotBelongToUserException::class);

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersMayBeAssociatedWithUser([$customer], $user);
    }

    public function testAssertSuccess(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);

        $user = $this->createMock(User::class);
        $user->method('__call')->willReturnMap([
            ['getUserGuid', [], bin2hex(random_bytes(16))],
        ]);

        $lspUser = new LspUser(
            bin2hex(random_bytes(16)),
            $user,
            $this->createMock(LanguageServiceProvider::class),
        );

        $lspUserRepository->method('findByUser')->willReturn($lspUser);

        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP');

        $customer = $this->createMock(Customer::class);

        $validator = new UserCustomerAssociationValidator(
            $lspUserRepository,
            $lspCustomerAssociationValidator,
        );
        $validator->assertCustomersMayBeAssociatedWithUser([$customer], $user);
    }
}
