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

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspAction;
use MittagQI\Translate5\LSP\ActionAssert\Permission\LspActionPermissionAssert;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\Model\User;

/**
 * @template JC of array{guid: string, name: string}
 * @template U of array{id: int, name: string}
 * @template C of array{id: int, name: string}
 * @template LspRow of array{
 *     id: int,
 *     parentId: int|null,
 *     name: string,
 *     description: string,
 *     canDelete: bool,
 *     canEdit: bool,
 *     coordinators: JC[],
 *     users: U[],
 *     customers: C[]
 * }
 */
class LspViewDataProvider
{
    public function __construct(
        private readonly LspRepositoryInterface $lspRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly ActionPermissionAssertInterface $lspPermissionAssert,
        private readonly ActionPermissionAssertInterface $customerActionPermissionAssert,
        private readonly ActionPermissionAssertInterface $userActionPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            LspRepository::create(),
            LspUserRepository::create(),
            JobCoordinatorRepository::create(),
            LspActionPermissionAssert::create(),
            CustomerActionPermissionAssert::create(),
            UserActionPermissionAssert::create(),
        );
    }

    /**
     * @return LspRow[]
     */
    public function getViewListFor(User $viewer): array
    {
        $data = [];

        foreach ($this->lspRepository->getAll() as $lsp) {
            try {
                $data[] = $this->buildViewData($viewer, $lsp);
            } catch (PermissionExceptionInterface) {
                continue;
            }
        }

        return $data;
    }

    /**
     * @return LspRow
     * @throws PermissionExceptionInterface
     */
    public function buildViewData(User $viewer, LanguageServiceProvider $lsp): array
    {
        $this->lspPermissionAssert->assertGranted(LspAction::Read, $lsp, new PermissionAssertContext($viewer));

        $coordinators = $this->jobCoordinatorRepository->getByLsp($lsp);
        /**
         * @var array<array{guid: string, name: string}> $coordinatorData
         */
        $coordinatorData = [];

        foreach ($coordinators as $coordinator) {
            $coordinatorData[] = [
                'guid' => $coordinator->guid,
                'name' => $coordinator->user->getUsernameLong(),
            ];
        }

        $users = $this->lspUserRepository->getUsers((int) $lsp->getId());
        /**
         * @var array<array{id: int, name: string}> $usersData
         */
        $usersData = [];

        foreach ($users as $user) {
            try {
                $this->userActionPermissionAssert->assertGranted(
                    LspAction::Read,
                    $user,
                    new PermissionAssertContext($viewer)
                );

                $usersData[] = [
                    'id' => (int) $user->getId(),
                    'name' => $user->getUsernameLong(),
                ];
            } catch (PermissionExceptionInterface) {
                continue;
            }
        }

        $customers = $this->lspRepository->getCustomers($lsp);
        /**
         * @var array<array{id: int, name: string}> $customersData
         */
        $customersData = [];

        foreach ($customers as $customer) {
            try {
                $this->customerActionPermissionAssert->assertGranted(
                    LspAction::Read,
                    $customer,
                    new PermissionAssertContext($viewer)
                );

                $customersData[] = [
                    'id' => (int) $customer->getId(),
                    'name' => $customer->getName(),
                ];
            } catch (PermissionExceptionInterface) {
                continue;
            }
        }

        return [
            'id' => (int) $lsp->getId(),
            'parentId' => $lsp->getParentId() ? (int) $lsp->getParentId() : null,
            'name' => $lsp->getName(),
            'canEdit' => $this->userCanEditLsp($viewer, $lsp),
            'canDelete' => $this->userCanDeleteLsp($viewer, $lsp),
            'description' => $lsp->getDescription(),
            'coordinators' => $coordinatorData,
            'users' => $usersData,
            'customers' => $customersData,
        ];
    }

    private function userCanEditLsp(User $viewer, LanguageServiceProvider $lsp): bool
    {
        try {
            $this->lspPermissionAssert->assertGranted(LspAction::Update, $lsp, new PermissionAssertContext($viewer));

            return true;
        } catch (PermissionExceptionInterface) {
            return false;
        }
    }

    private function userCanDeleteLsp(User $viewer, LanguageServiceProvider $lsp): bool
    {
        try {
            $this->lspPermissionAssert->assertGranted(LspAction::Delete, $lsp, new PermissionAssertContext($viewer));

            return true;
        } catch (PermissionExceptionInterface) {
            return false;
        }
    }
}
