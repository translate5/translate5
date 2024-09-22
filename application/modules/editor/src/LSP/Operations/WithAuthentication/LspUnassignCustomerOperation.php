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

namespace MittagQI\Translate5\LSP\Operations\WithAuthentication;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Customer\Exception\NoAccessToCustomerException;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\Contract\LspUnassignCustomerOperationInterface;
use MittagQI\Translate5\LSP\Exception\NoAccessToLspException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\UserRepository;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

final class LspUnassignCustomerOperation implements LspUnassignCustomerOperationInterface
{
    /**
     * @param ActionPermissionAssertInterface<LanguageServiceProvider> $lspActionPermissionAssert
     * @param ActionPermissionAssertInterface<Customer> $customerActionPermissionAssert
     */
    public function __construct(
        private readonly LspUnassignCustomerOperationInterface $generalOperation,
        private readonly ActionPermissionAssertInterface $lspActionPermissionAssert,
        private readonly ActionPermissionAssertInterface $customerActionPermissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\LSP\Operations\LspUnassignCustomerOperation::create(),
            LspActionPermissionAssert::create(),
            CustomerActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
        );
    }

    /**
     * @throws NoAccessToLspException
     * @throws NoAccessToCustomerException
     * @throws PermissionExceptionInterface
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function unassignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $this->lspActionPermissionAssert->assertGranted(
            Action::UPDATE,
            $lsp,
            new PermissionAssertContext($authUser)
        );

        $this->customerActionPermissionAssert->assertGranted(
            Action::READ,
            $customer,
            new PermissionAssertContext($authUser)
        );

        $this->generalOperation->unassignCustomer($lsp, $customer);
    }
}
