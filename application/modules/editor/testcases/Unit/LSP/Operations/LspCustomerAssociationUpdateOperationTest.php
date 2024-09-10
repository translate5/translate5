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

namespace MittagQI\Translate5\Test\Unit\LSP\Service;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\LSP\Event\CustomerAssignedToLspEvent;
use MittagQI\Translate5\LSP\Event\CustomerUnassignedFromLspEvent;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\LSP\Operations\LspCustomerAssociationUpdateOperation;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZfExtended_Models_User as User;

class LspCustomerAssociationUpdateOperationTest extends TestCase
{
    public function testUpdateCustomersThrowsExceptionIfCustomerNotInParentLsp(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('__call')->willReturnMap([
            ['getParentId', [], 1],
        ]);

        $customer = $this->createMock(Customer::class);

        $lspCustomerAssociationValidator->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP')
            ->willThrowException(new CustomerDoesNotBelongToLspException(1, 1));

        $service = new LspCustomerAssociationUpdateOperation(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $this->expectException(CustomerDoesNotBelongToLspException::class);

        $service->updateCustomers($lsp, $customer);
    }

    public function testSameCustomersDontProduceChanges(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('__call')->willReturnMap([
            ['getParentId', [], 1],
        ]);

        $customer1 = $this->createMock(Customer::class);
        $customer1->method('__call')->willReturnMap([
            ['getId', [], 1],
        ]);

        $customer2 = $this->createMock(Customer::class);
        $customer2->method('__call')->willReturnMap([
            ['getId', [], 2],
        ]);

        $lspRepository->method('getCustomers')->willReturn([$customer1, $customer2]);
        $lspRepository->expects(self::never())->method('saveCustomerAssignment');
        $lspRepository->expects(self::never())->method('deleteCustomerAssignment');

        $lspCustomerAssociationValidator->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP');

        $eventDispatcher->expects(self::never())->method('dispatch');

        $service = new LspCustomerAssociationUpdateOperation(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $service->updateCustomers($lsp, $customer1, $customer2);
    }

    public function testCustomerUnassigned(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('__call')->willReturnMap([
            ['getParentId', [], 1],
        ]);

        $customer1 = $this->createMock(Customer::class);
        $customer1->method('__call')->willReturnMap([
            ['getId', [], 1],
        ]);

        $customer2 = $this->createMock(Customer::class);
        $customer2->method('__call')->willReturnMap([
            ['getId', [], 2],
        ]);

        $lspCustomer = $this->createMock(LanguageServiceProviderCustomer::class);

        $lspRepository->method('getCustomers')->willReturn([$customer1, $customer2]);
        $lspRepository->expects(self::never())->method('saveCustomerAssignment');
        $lspRepository
            ->method('findCustomerAssignment')
            ->willReturn($lspCustomer)
        ;
        $lspRepository
            ->expects(self::once())
            ->method('deleteCustomerAssignment')
            ->with($lspCustomer)
        ;

        $lspCustomerAssociationValidator->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP');

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerUnassignedFromLspEvent::class))
        ;

        $service = new LspCustomerAssociationUpdateOperation(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $service->updateCustomers($lsp, $customer1);
    }

    public function testNothingDoneOnCustomerUnassignmentIfAssociationNoLongerExists(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('__call')->willReturnMap([
            ['getParentId', [], 1],
        ]);

        $customer1 = $this->createMock(Customer::class);
        $customer1->method('__call')->willReturnMap([
            ['getId', [], 1],
        ]);

        $customer2 = $this->createMock(Customer::class);
        $customer2->method('__call')->willReturnMap([
            ['getId', [], 2],
        ]);

        $lspRepository->method('getCustomers')->willReturn([$customer1, $customer2]);
        $lspRepository->expects(self::never())->method('saveCustomerAssignment');
        $lspRepository->method('findCustomerAssignment')->willReturn(null);
        $lspRepository->expects(self::never())->method('deleteCustomerAssignment');

        $lspCustomerAssociationValidator->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP');

        $eventDispatcher->expects(self::never())->method('dispatch');

        $service = new LspCustomerAssociationUpdateOperation(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $service->updateCustomers($lsp, $customer1);
    }

    public function testCustomerAssigned(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], 10],
        ]);

        $customer1 = $this->createMock(Customer::class);
        $customer1->method('__call')->willReturnMap([
            ['getId', [], 1],
        ]);

        $customer2 = $this->createMock(Customer::class);
        $customer2->method('__call')->willReturnMap([
            ['getId', [], 2],
        ]);

        $lspCustomer = new class() extends LanguageServiceProviderCustomer {
            public function __construct()
            {
            }

            public function setCustomerId(int $customerId): void
            {
                TestCase::assertSame(2, $customerId);
            }

            public function setLspId(int $lspId): void
            {
                TestCase::assertSame(10, $lspId);
            }
        };

        $lspRepository->method('getCustomers')->willReturn([$customer1]);
        $lspRepository
            ->expects(self::once())
            ->method('saveCustomerAssignment')
            ->with($lspCustomer);
        $lspRepository->method('getEmptyLspCustomerModel')->willReturn($lspCustomer);
        $lspRepository->expects(self::never())->method('deleteCustomerAssignment');

        $lspCustomerAssociationValidator->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP');

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerAssignedToLspEvent::class))
        ;

        $service = new LspCustomerAssociationUpdateOperation(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $service->updateCustomers($lsp, $customer1, $customer2);
    }

    public function testUpdateByUserMakesAssertionForCustomerSubsetForClientRestrictedUser(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $userCustomerAssociationValidator = $this->createMock(UserCustomerAssociationValidator::class);
        $lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $lspRepository->expects(self::never())->method('saveCustomerAssignment');
        $lspRepository->expects(self::never())->method('deleteCustomerAssignment');

        $userCustomerAssociationValidator->expects(self::once())
            ->method('assertCustomersAreSubsetForUser')
            ->willThrowException(new CustomerDoesNotBelongToUserException(1, 'guid'))
        ;

        $authUser = $this->createMock(User::class);
        $authUser->method('isClientRestricted')->willReturn(true);

        $customer = $this->createMock(Customer::class);

        $customerRepository->method('getList')->willReturn([$customer]);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $service = new LspCustomerAssociationUpdateOperation(
            $lspRepository,
            $userCustomerAssociationValidator,
            $lspCustomerAssociationValidator,
            $customerRepository,
            $eventDispatcher,
        );

        $this->expectException(CustomerDoesNotBelongToUserException::class);

        $service->updateCustomersBy($lsp, [1], $authUser);
    }
}
