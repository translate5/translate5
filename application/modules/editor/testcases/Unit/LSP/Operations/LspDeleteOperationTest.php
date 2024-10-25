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

namespace MittagQI\Translate5\Test\Unit\LSP\Operations;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\LSP\Operations\LspDeleteOperation;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_User;

class LspDeleteOperationTest extends TestCase
{
    public function testDeleteLsp(): void
    {
        $lspUserRepository = $this->createMock(LspUserRepository::class);

        $user1 = $this->createMock(ZfExtended_Models_User::class);
        $user2 = $this->createMock(ZfExtended_Models_User::class);

        $lspUserRepository->method('getUsers')->willReturnOnConsecutiveCalls([$user1], [$user2]);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $subLsp = $this->createMock(LanguageServiceProvider::class);

        $userDeleteService = new class() implements UserDeleteOperationInterface {
            public array $deletedUsers = [];

            public function delete(ZfExtended_Models_User $user): void
            {
            }

            public function forceDelete(ZfExtended_Models_User $user): void
            {
                $this->deletedUsers[] = $user;
            }
        };

        $lspRepository = new class($subLsp) implements LspRepositoryInterface {
            public array $deletedLsps = [];

            private bool $firstCall = true;

            public function __construct(
                private LanguageServiceProvider $subLsp
            ) {
            }

            /**
             * @phpstan-ignore-next-line
             */
            public function get(int $id): LanguageServiceProvider
            {
            }

            /**
             * @phpstan-ignore-next-line
             */
            public function getEmptyModel(): LanguageServiceProvider
            {
            }

            public function save(LanguageServiceProvider $lsp): void
            {
            }

            public function delete(LanguageServiceProvider $lsp): void
            {
                $this->deletedLsps[] = $lsp;
            }

            /**
             * @phpstan-ignore-next-line
             */
            public function findCustomerAssignment(
                LanguageServiceProvider $lsp,
                Customer $customer,
            ): ?LanguageServiceProviderCustomer {
            }

            public function saveCustomerAssignment(LanguageServiceProviderCustomer $lspCustomer): void
            {
            }

            public function deleteCustomerAssignment(LanguageServiceProviderCustomer $lspCustomer): void
            {
            }

            /**
             * @phpstan-ignore-next-line
             */
            public function getAll(): iterable
            {
            }

            /**
             * @phpstan-ignore-next-line
             */
            public function getCustomers(LanguageServiceProvider $lsp): iterable
            {
            }

            /**
             * @phpstan-ignore-next-line
             */
            public function getCustomerIds(LanguageServiceProvider $lsp): array
            {
            }

            public function getSubLspList(LanguageServiceProvider $lsp): iterable
            {
                if ($this->firstCall) {
                    $this->firstCall = false;

                    return [$this->subLsp];
                }

                return [];
            }

            public function getEmptyLspCustomerModel(): LanguageServiceProviderCustomer
            {
            }

            public function getSubLspIds(LanguageServiceProvider $lsp): array
            {
                // TODO: Implement getSubLspIds() method.
            }
        };

        $service = new LspDeleteOperation(
            $lspRepository,
            $userDeleteService,
            $lspUserRepository,
        );

        $service->deleteLsp($lsp);

        self::assertSame([$user1, $user2], $userDeleteService->deletedUsers);
        self::assertSame([$subLsp, $lsp], $lspRepository->deletedLsps);
    }
}
