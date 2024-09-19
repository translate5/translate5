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

namespace MittagQI\Translate5\Test\Unit\LSP;

use editor_Models_Customer_Customer;
use Exception;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\ViewDataProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class ViewDataProviderTest extends TestCase
{
    public function testGetViewListForReturnEmptyListAsViewerHasNoAccessToLsp(): void
    {
        $lspRepository = $this->createMock(LspRepository::class);
        $lspUserRepository = $this->createMock(LspUserRepository::class);
        $jobCoordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $permissionAssert = $this->createMock(ActionPermissionAssertInterface::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);

        $lspRepository->method('getAll')->willReturn([$lsp]);

        $permissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException(
                $this->createMock(PermissionExceptionInterface::class)
            )
        ;

        $viewer = $this->createMock(User::class);

        $viewDataProvider = new ViewDataProvider(
            $lspRepository,
            $lspUserRepository,
            $jobCoordinatorRepository,
            $permissionAssert,
        );

        self::assertEmpty($viewDataProvider->getViewListFor($viewer));
    }

    public function testBuildViewData(): void
    {
        $lspRepository = $this->createMock(LspRepositoryInterface::class);
        $lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $jobCoordinatorRepository = $this->createMock(JobCoordinatorRepository::class);

        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], 10],
            ['getName', [], 'lsp name'],
            ['getDescription', [], 'lsp description'],
        ]);

        $user1 = $this->createMock(User::class);
        $user1->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid-1'],
            ['getUsernameLong', [], 'user-1'],
        ]);
        $coordinator1 = new JobCoordinator($user1->getUserGuid(), $user1, $lsp);

        $user2 = $this->createMock(User::class);
        $user2->method('__call')->willReturnMap([
            ['getUserGuid', [], 'user-guid-2'],
            ['getUsernameLong', [], 'user-2'],
        ]);

        $customer1 = $this->createMock(editor_Models_Customer_Customer::class);
        $customer1->method('__call')->willReturnMap([
            ['getId', [], 11],
            ['getName', [], 'customer-1'],
        ]);
        $customer2 = $this->createMock(editor_Models_Customer_Customer::class);
        $customer2->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getName', [], 'customer-2'],
        ]);

        $jobCoordinatorRepository->method('getByLSP')->willReturn([$coordinator1]);

        $lspUserRepository->method('getUsers')->willReturn([$user1, $user2]);

        $lspRepository->method('getAll')->willReturn([$lsp]);
        $lspRepository->method('getCustomers')->willReturn([$customer1, $customer2]);

        $permissionAssert = new class() implements ActionPermissionAssertInterface {
            public function assertGranted(
                Action $action,
                object $object,
                PermissionAssertContext $context
            ): void {
                if (Action::UPDATE === $action) {
                    return;
                }

                throw new class() extends Exception implements PermissionExceptionInterface {
                };
            }
        };

        $viewer = $this->createMock(User::class);

        $viewDataProvider = new ViewDataProvider(
            $lspRepository,
            $lspUserRepository,
            $jobCoordinatorRepository,
            $permissionAssert,
        );

        $expected = [
            'id' => (int) $lsp->getId(),
            'name' => $lsp->getName(),
            'canEdit' => true,
            'canDelete' => false,
            'description' => $lsp->getDescription(),
            'coordinators' => [
                [
                    'guid' => $coordinator1->guid,
                    'name' => $coordinator1->user->getUsernameLong(),
                ],
            ],
            'users' => [
                [
                    'id' => (int) $user1->getId(),
                    'name' => $user1->getUsernameLong(),
                ],
                [
                    'id' => (int) $user2->getId(),
                    'name' => $user2->getUsernameLong(),
                ],
            ],
            'customers' => [
                [
                    'id' => (int) $customer1->getId(),
                    'name' => $customer1->getName(),
                ],
                [
                    'id' => (int) $customer2->getId(),
                    'name' => $customer2->getName(),
                ],
            ],
            'parentId' => null,
        ];

        self::assertEquals($expected, $viewDataProvider->buildViewData($viewer, $lsp));
    }
}
