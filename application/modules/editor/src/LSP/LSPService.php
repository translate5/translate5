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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LSPRepository;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

/**
 * @template LspViewRow as array{id: int, name: string, description: string, coordinators: array{uuid: string, name: string}[], users: array{id: int, name: string}[], customers: array{id: int, name: string}[]}
 */
class LSPService
{
    public function __construct(
        private readonly LSPRepository $lspRepository,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(LSPRepository::create(), new JobCoordinatorRepository());
    }

    /**
     * @return LspViewRow[]
     */
    public function getViewListFor(ZfExtended_Models_User $user): array
    {
        $roles = $user->getRoles();

        if (array_intersect([Roles::ADMIN, Roles::PM, Roles::CLIENTPM], $roles)) {
            return $this->buildViewData($this->lspRepository->getAll());
        }

        if (! in_array(Roles::JOB_COORDINATOR, $roles)) {
            return [];
        }

        try {
            return $this->buildViewData(
                $this->lspRepository->getForJobCoordinator(
                    $this->jobCoordinatorRepository->getByUser($user)
                )
            );
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return [];
        }
    }

    /**
     * @param iterable<LanguageServiceProvider> $lsps
     * @return LspViewRow[]
     */
    private function buildViewData(iterable $lsps): array
    {
        $data = [];

        foreach ($lsps as $lsp) {
            $coordinators = $this->jobCoordinatorRepository->getByLSP($lsp);
            $coordinatorData = [];

            foreach ($coordinators as $coordinator) {
                $coordinatorData[] = [
                    'uuid' => $coordinator->guid,
                    'name' => $coordinator->user->getUsernameLong(),
                ];
            }

            $users = $this->lspRepository->getUsers($lsp);
            $usersData = [];

            foreach ($users as $user) {
                $usersData[] = [
                    'id' => (int) $user->getId(),
                    'name' => $user->getUsernameLong(),
                ];
            }

            $customers = $this->lspRepository->getCustomers($lsp);
            $customersData = [];

            foreach ($customers as $customer) {
                $customersData[] = [
                    'id' => (int) $customer->getId(),
                    'name' => $customer->getName(),
                ];
            }

            $data[] = [
                'id' => (int) $lsp->getId(),
                'name' => $lsp->getName(),
                'description' => $lsp->getDescription(),
                'coordinators' => $coordinatorData,
                'users' => $usersData,
                'customers' => $customersData,
            ];
        }

        return $data;
    }

    public function createLsp(string $name, ?string $description = null): LanguageServiceProvider
    {
        $lsp = \ZfExtended_Factory::get(LanguageServiceProvider::class);
        $lsp->setName($name);
        $lsp->setDescription($description);

        $lsp->save();

        return $lsp;
    }
}