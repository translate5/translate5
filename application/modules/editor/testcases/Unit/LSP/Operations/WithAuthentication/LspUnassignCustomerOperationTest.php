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

namespace LSP\Operations\WithAuthentication;

use editor_Models_Customer_Customer;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspAction;
use MittagQI\Translate5\LSP\Contract\LspUnassignCustomerOperationInterface;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Operations\WithAuthentication\LspUnassignCustomerOperation;
use MittagQI\Translate5\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

class LspUnassignCustomerOperationTest extends TestCase
{
    private LspUnassignCustomerOperationInterface|MockObject $generalOperation;

    private ActionPermissionAssertInterface|MockObject $lspPermissionAssert;

    private ActionPermissionAssertInterface|MockObject $customerPermissionAssert;

    private ZfExtended_AuthenticationInterface|MockObject $authentication;

    private UserRepository|MockObject $userRepository;

    private LspUnassignCustomerOperation $operation;

    private ZfExtended_Logger $logger;

    public function setUp(): void
    {
        $this->generalOperation = $this->createMock(LspUnassignCustomerOperationInterface::class);
        $this->lspPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->customerPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->authentication = $this->createMock(ZfExtended_AuthenticationInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);

        $this->operation = new LspUnassignCustomerOperation(
            $this->generalOperation,
            $this->lspPermissionAssert,
            $this->customerPermissionAssert,
            $this->authentication,
            $this->userRepository,
            $this->logger,
        );
    }

    public function testThrowsPermissionExceptionForLsp(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->lspPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(LspAction::Update)
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);

        $this->generalOperation->expects(self::never())->method('unassignCustomer');

        $this->operation->unassignCustomer($lsp, $customer);
    }

    public function testThrowsPermissionExceptionForCustomer(): void
    {
        $this->expectException(PermissionExceptionInterface::class);

        $this->customerPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(CustomerAction::Read)
            ->willThrowException($this->createMock(PermissionExceptionInterface::class));

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);

        $this->generalOperation->expects(self::never())->method('unassignCustomer');

        $this->operation->unassignCustomer($lsp, $customer);
    }

    public function testAssignCustomer(): void
    {
        $this->lspPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(LspAction::Update);

        $this->customerPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->with(CustomerAction::Read);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $customer = $this->createMock(editor_Models_Customer_Customer::class);

        $this->generalOperation->expects(self::once())->method('unassignCustomer')->with($lsp, $customer);

        $this->operation->unassignCustomer($lsp, $customer);
    }
}
