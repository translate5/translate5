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

namespace LSP\Operations;

use editor_Models_Customer_Customer;
use MittagQI\Translate5\LSP\Event\CustomerAssignedToLspEvent;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\LSP\Operations\LspAssignCustomerOperation;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class LspAssignCustomerOperationTest extends TestCase
{
    private LspRepositoryInterface|MockObject $lspRepository;

    private LspCustomerAssociationValidator|MockObject $lspCustomerAssociationValidator;

    private EventDispatcherInterface|MockObject $eventDispatcher;

    private LspAssignCustomerOperation $operation;

    public function setUp(): void
    {
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);
        $this->lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->operation = new LspAssignCustomerOperation(
            $this->lspRepository,
            $this->lspCustomerAssociationValidator,
            $this->eventDispatcher,
        );
    }

    public function testThrowsCustomerDoesNotBelongToLspException(): void
    {
        $this->expectException(CustomerDoesNotBelongToLspException::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);

        $parentLsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('get')->with((int) $lsp->getParentId())->willReturn($parentLsp);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->lspCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP')
            ->with($parentLsp, (int) $customer->getId())
            ->willThrowException($this->createMock(CustomerDoesNotBelongToLspException::class));

        $this->lspRepository->expects(self::never())->method('saveCustomerAssignment');
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->operation->assignCustomer($lsp, $customer);
    }

    public function testAssignCustomerToNotDirectLsp(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(false);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '11'],
        ]);

        $parentLsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('get')->with((int) $lsp->getParentId())->willReturn($parentLsp);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->lspCustomerAssociationValidator
            ->expects(self::once())
            ->method('assertCustomersAreSubsetForLSP')
            ->with($parentLsp, (int) $customer->getId());

        $lspCustomer = $this->createMock(LanguageServiceProviderCustomer::class);
        $calledSetter = null;

        $lspCustomer->expects(self::exactly(2))
            ->method('__call')
            ->with(
                self::callback(function ($value) use (&$calledSetter) {
                    $calledSetter = $value;

                    return in_array($value, ['setLspId', 'setCustomerId']);
                }),
                self::callback(function ($value) use (&$calledSetter) {
                    if ('setLspId' === $calledSetter) {
                        self::assertSame([11], $value);

                        return [11] === $value;
                    }

                    return [12] === $value;
                })
            );

        $this->lspRepository
            ->method('getEmptyLspCustomerModel')
            ->willReturn($lspCustomer);

        $this->lspRepository->expects(self::once())->method('saveCustomerAssignment');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerAssignedToLspEvent::class));

        $this->operation->assignCustomer($lsp, $customer);
    }

    public function testAssignCustomerToDirectLsp(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('isDirectLsp')->willReturn(true);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '11'],
        ]);

        $parentLsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('get')->with((int) $lsp->getParentId())->willReturn($parentLsp);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $customer->method('__call')->willReturnMap([
            ['getId', [], '12'],
        ]);

        $this->lspCustomerAssociationValidator
            ->expects(self::never())
            ->method('assertCustomersAreSubsetForLSP');

        $lspCustomer = $this->createMock(LanguageServiceProviderCustomer::class);
        $calledSetter = null;

        $lspCustomer->expects(self::exactly(2))
            ->method('__call')
            ->with(
                self::callback(function ($value) use (&$calledSetter) {
                    $calledSetter = $value;

                    return in_array($value, ['setLspId', 'setCustomerId']);
                }),
                self::callback(function ($value) use (&$calledSetter) {
                    if ('setLspId' === $calledSetter) {
                        self::assertSame([11], $value);

                        return [11] === $value;
                    }

                    return [12] === $value;
                })
            );

        $this->lspRepository
            ->method('getEmptyLspCustomerModel')
            ->willReturn($lspCustomer);

        $this->lspRepository->expects(self::once())->method('saveCustomerAssignment');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerAssignedToLspEvent::class));

        $this->operation->assignCustomer($lsp, $customer);
    }
}
