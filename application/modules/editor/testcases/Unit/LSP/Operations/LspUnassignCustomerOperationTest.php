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
use MittagQI\Translate5\LSP\Event\CustomerUnassignedFromLspEvent;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\LSP\Operations\LspUnassignCustomerOperation;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class LspUnassignCustomerOperationTest extends TestCase
{
    private LspRepositoryInterface|MockObject $lspRepository;
    private EventDispatcherInterface|MockObject $eventDispatcher;

    private LspUnassignCustomerOperation $operation;

    public function setUp(): void
    {
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->operation = new LspUnassignCustomerOperation(
            $this->lspRepository,
            $this->eventDispatcher,
        );
    }

    public function testNothingDoneIfNoConnection(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('findCustomerAssignment')->willReturn(null);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);

        $this->lspRepository->expects(self::never())->method('deleteCustomerAssignment');

        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->operation->unassignCustomer($lsp, $customer);
    }

    public function testUnassignCustomer(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $customer = $this->createMock(editor_Models_Customer_Customer::class);
        $lspCustomer = $this->createMock(LanguageServiceProviderCustomer::class);

        $this->lspRepository->method('findCustomerAssignment')->willReturn($lspCustomer);

        $this->lspRepository->expects(self::once())->method('deleteCustomerAssignment')->with($lspCustomer);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CustomerUnassignedFromLspEvent::class));

        $this->operation->unassignCustomer($lsp, $customer);
    }
}
