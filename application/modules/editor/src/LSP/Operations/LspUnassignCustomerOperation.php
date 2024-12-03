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
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssert;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\JobAssignment\LspJob\ActionAssert\Feasibility\LspJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\LspJob\Contract\DeleteLspJobOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DeleteLspJobOperation;
use MittagQI\Translate5\LSP\Contract\LspUnassignCustomerOperationInterface;
use MittagQI\Translate5\LSP\Contract\LspUserUnassignCustomersOperationInterface;
use MittagQI\Translate5\LSP\Exception\LspHasUnDeletableJobException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;

final class LspUnassignCustomerOperation implements LspUnassignCustomerOperationInterface
{
    public function __construct(
        private readonly LspRepositoryInterface $lspRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly ActionFeasibilityAssert $lspJobActionFeasibilityAssert,
        private readonly DeleteLspJobOperationInterface $deleteLspJobAssignmentOperation,
        private readonly LspUserUnassignCustomersOperationInterface $lspUserUnassignCustomersOperation,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspRepository::create(),
            LspJobRepository::create(),
            LspUserRepository::create(),
            LspJobActionFeasibilityAssert::create(),
            DeleteLspJobOperation::create(),
            LspUserUnassignCustomersOperation::create(),
        );
    }

    public function unassignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = $this->lspRepository->findCustomerAssignment((int) $lsp->getId(), (int) $customer->getId());

        if (! $lspCustomer) {
            return;
        }

        $this->assertLspJobsCanBeDeleted((int) $lsp->getId(), (int) $customer->getId());
        $this->deleteAssociationWithDependencies($lsp, $customer);
    }

    public function forceUnassignCustomer(LanguageServiceProvider $lsp, Customer $customer): void
    {
        $lspCustomer = $this->lspRepository->findCustomerAssignment((int) $lsp->getId(), (int) $customer->getId());

        if (! $lspCustomer) {
            return;
        }

        $this->deleteAssociationWithDependencies($lsp, $customer);
    }

    private function deleteAssociationWithDependencies(LanguageServiceProvider $lsp, Customer $customer): void
    {
        foreach ($this->lspRepository->getSubLspList($lsp) as $subLsp) {
            $this->forceUnassignCustomer($subLsp, $customer);
        }

        $lspJobs = $this->getLspJobsIterator((int) $lsp->getId(), (int) $customer->getId());

        foreach ($lspJobs as $lspJob) {
            $this->deleteLspJobAssignmentOperation->forceDelete($lspJob);
        }

        foreach ($this->lspUserRepository->getLspUsers($lsp) as $lspUser) {
            $this->lspUserUnassignCustomersOperation->forceUnassignCustomers($lspUser, (int) $customer->getId());
        }

        $this->lspRepository->deleteCustomerAssignment((int) $lsp->getId(), (int) $customer->getId());
    }

    private function assertLspJobsCanBeDeleted(int $lspId, int $customerId): void
    {
        try {
            foreach ($this->getLspJobsIterator($lspId, $customerId) as $lspJob) {
                $this->lspJobActionFeasibilityAssert->assertAllowed(Action::Delete, $lspJob);
            }
        } catch (FeasibilityExceptionInterface $e) {
            throw new LspHasUnDeletableJobException(previous: $e);
        }
    }

    /**
     * @return iterable<LspJobAssociation>
     */
    private function getLspJobsIterator(int $lspId, int $customerId): iterable
    {
        return $this->lspJobRepository->getLspJobsOfCustomer($lspId, $customerId);
    }
}
