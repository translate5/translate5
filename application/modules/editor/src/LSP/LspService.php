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

namespace MittagQI\Translate5\LSP;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LSP\Event\CustomerAssignedToLspEvent;
use MittagQI\Translate5\LSP\Event\CustomerUnassignedFromLspEvent;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZfExtended_Factory;

class LspService
{
    public function __construct(
        private readonly LspRepository $lspRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public static function create(): self
    {
        $lspRepository = LspRepository::create();

        return new self(
            $lspRepository,
            EventDispatcher::create(),
            new UserRepository(),
            UserCustomerAssociationValidator::create(),
        );
    }

    public function getLsp(int $id): LanguageServiceProvider
    {
        return $this->lspRepository->get($id);
    }

    public function createLsp(string $name, ?string $description, ?JobCoordinator $coordinator): LanguageServiceProvider
    {
        $lsp = ZfExtended_Factory::get(LanguageServiceProvider::class);
        $lsp->setName($name);
        $lsp->setDescription($description);

        if (null !== $coordinator) {
            $lsp->setParentId((int) $coordinator->lsp->getId());
        }

        $this->lspRepository->save($lsp);

        return $lsp;
    }

    public function assignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = ZfExtended_Factory::get(LanguageServiceProviderCustomer::class);
        $lspCustomer->setLspId((int) $lsp->getId());
        $lspCustomer->setCustomerId($customer->getId());

        $this->lspRepository->saveCustomerAssignment($lspCustomer);

        $this->eventDispatcher->dispatch(new CustomerAssignedToLspEvent($lsp, $customer));
    }

    public function unassignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = $this->lspRepository->findCustomerAssignment($lsp, $customer);

        if (! $lspCustomer) {
            return;
        }

        $this->lspRepository->deleteCustomerAssignment($lspCustomer);

        $this->eventDispatcher->dispatch(new CustomerUnassignedFromLspEvent($lsp, $customer));
    }

    // TODO: delete all relations of lsp: users, customer associations...
    public function deleteLsp(LanguageServiceProvider $lsp): void
    {
        $lspUsers = $this->lspRepository->getUsers($lsp);

        foreach ($lspUsers as $user) {
            $this->userRepository->delete($user);
        }

        foreach ($this->lspRepository->getSubLspList($lsp) as $subLsp) {
            $this->deleteLsp($subLsp);
        }

        $this->lspRepository->delete($lsp);
    }
}
