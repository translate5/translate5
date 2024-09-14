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

namespace MittagQI\Translate5\LSP\Operations;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Exception\InexistentCustomerException;
use MittagQI\Translate5\LSP\Event\CustomerAssignedToLspEvent;
use MittagQI\Translate5\LSP\Event\CustomerUnassignedFromLspEvent;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZfExtended_Models_User as User;

class LspCustomerAssociationUpdateOperation
{
    public function __construct(
        private readonly LspRepository $lspRepository,
        private readonly UserCustomerAssociationValidator $userCustomerAssociationValidator,
        private readonly LspCustomerAssociationValidator $lspCustomerAssociationValidator,
        private readonly CustomerRepository $customerRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspRepository::create(),
            UserCustomerAssociationValidator::create(),
            LspCustomerAssociationValidator::create(),
            new CustomerRepository(),
            EventDispatcher::create(),
        );
    }

    /**
     * @param int[] $customerIds
     * @throws InexistentCustomerException
     * @throws CustomerDoesNotBelongToUserException
     * @throws CustomerDoesNotBelongToLspException
     */
    public function updateCustomersBy(LanguageServiceProvider $lsp, array $customerIds, User $authUser): void
    {
        if ($authUser->isClientRestricted()) {
            $this->userCustomerAssociationValidator->assertCustomersAreSubsetForUser($customerIds, $authUser);
        }

        $this->updateCustomers($lsp, ...$customerIds);
    }

    public function updateCustomers(LanguageServiceProvider $lsp, int ...$customerIds): void
    {
        if (! $lsp->isDirectLsp() && ! empty($customerIds)) {
            $parentLsp = $this->lspRepository->get((int) $lsp->getParentId());
            $this->lspCustomerAssociationValidator->assertCustomersAreSubsetForLSP($parentLsp, $customerIds);
        }

        $lspCustomerIds = $this->lspRepository->getCustomerIds($lsp);

        foreach ($lspCustomerIds as $i => $lspCustomerId) {
            if (! in_array($lspCustomerId, $customerIds, true)) {
                $customer = $this->customerRepository->get($lspCustomerId);

                $this->unassignCustomer($lsp, $customer);

                unset($lspCustomerIds[$i]);
            }
        }

        foreach ($customerIds as $customerId) {
            if (! in_array($customerId, $lspCustomerIds, true)) {
                $customer = $this->customerRepository->get($customerId);

                $this->assignCustomer($lsp, $customer);
            }
        }
    }

    private function assignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = $this->lspRepository->getEmptyLspCustomerModel();
        $lspCustomer->setLspId((int) $lsp->getId());
        $lspCustomer->setCustomerId($customer->getId());

        $this->lspRepository->saveCustomerAssignment($lspCustomer);

        $this->eventDispatcher->dispatch(new CustomerAssignedToLspEvent($lsp, $customer));
    }

    private function unassignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = $this->lspRepository->findCustomerAssignment($lsp, $customer);

        if (! $lspCustomer) {
            return;
        }

        $this->lspRepository->deleteCustomerAssignment($lspCustomer);

        $this->eventDispatcher->dispatch(new CustomerUnassignedFromLspEvent($lsp, $customer));
    }
}
