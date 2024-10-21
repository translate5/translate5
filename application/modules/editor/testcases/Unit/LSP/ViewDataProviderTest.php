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
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\ViewDataProvider;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewDataProviderTest extends TestCase
{
    private LspRepositoryInterface|MockObject $lspRepository;

    private LspUserRepositoryInterface|MockObject $lspUserRepository;

    private JobCoordinatorRepository|MockObject $jobCoordinatorRepository;

    private ActionPermissionAssertInterface|MockObject $lspPermissionAssert;

    private ActionPermissionAssertInterface|MockObject $customerActionPermissionAssert;

    private ActionPermissionAssertInterface|MockObject $userActionPermissionAssert;

    private ViewDataProvider $viewDataProvider;

    public function setUp(): void
    {
        $this->lspRepository = $this->createMock(LspRepositoryInterface::class);
        $this->lspUserRepository = $this->createMock(LspUserRepositoryInterface::class);
        $this->jobCoordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->lspPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->customerActionPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);
        $this->userActionPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);

        $this->viewDataProvider = new ViewDataProvider(
            $this->lspRepository,
            $this->lspUserRepository,
            $this->jobCoordinatorRepository,
            $this->lspPermissionAssert,
            $this->customerActionPermissionAssert,
            $this->userActionPermissionAssert,
        );
    }

    public function testGetViewListForReturnEmptyListAsViewerHasNoAccessToLsp(): void
    {
        $lsp = $this->createMock(LanguageServiceProvider::class);

        $this->lspRepository->method('getAll')->willReturn([$lsp]);

        $this->lspPermissionAssert
            ->expects(self::once())
            ->method('assertGranted')
            ->willThrowException(
                $this->createMock(PermissionExceptionInterface::class)
            )
        ;

        $viewer = $this->createMock(User::class);

        self::assertEmpty($this->viewDataProvider->getViewListFor($viewer));
    }

    public function testGetViewListWithoutRestrictedLsp(): void
    {
        $lsp1 = $this->createMock(LanguageServiceProvider::class);
        $lsp2 = $this->createMock(LanguageServiceProvider::class);
        $lsp2->method('__call')->willReturnMap([
            ['getId', [], 20],
            ['getName', [], 'lsp name'],
            ['getDescription', [], 'lsp description'],
        ]);

        $this->lspRepository->method('getAll')->willReturn([$lsp1, $lsp2]);

        $permissionException = $this->createMock(PermissionExceptionInterface::class);

        $this->lspPermissionAssert
            ->method('assertGranted')
            ->with(
                self::isInstanceOf(Action::class),
                self::callback(
                    fn ($lsp) => $lsp === $lsp1 ? throw $permissionException : true
                ),
            )
        ;

        $viewer = $this->createMock(User::class);

        self::assertCount(1, $this->viewDataProvider->getViewListFor($viewer));
        self::assertEquals(
            [
                'id' => (int) $lsp2->getId(),
                'name' => $lsp2->getName(),
                'canEdit' => true,
                'canDelete' => true,
                'description' => $lsp2->getDescription(),
                'coordinators' => [],
                'users' => [],
                'customers' => [],
                'parentId' => null,
            ],
            $this->viewDataProvider->getViewListFor($viewer)[0]
        );
    }

    public function testBuildViewData(): void
    {
        $permissionException = $this->createMock(PermissionExceptionInterface::class);

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

        $user3 = $this->createMock(User::class);

        $this->userActionPermissionAssert
            ->method('assertGranted')
            ->with(
                self::isInstanceOf(Action::class),
                self::callback(
                    fn ($user) => $user === $user3 ? throw $permissionException : true
                ),
            )
        ;

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

        $customer3 = $this->createMock(editor_Models_Customer_Customer::class);

        $this->customerActionPermissionAssert
            ->method('assertGranted')
            ->with(
                self::isInstanceOf(Action::class),
                self::callback(
                    fn ($customer) => $customer === $customer3 ? throw $permissionException : true
                ),
            )
        ;

        $this->jobCoordinatorRepository->method('getByLSP')->willReturn([$coordinator1]);

        $this->lspUserRepository->method('getUsers')->willReturn([$user1, $user2, $user3]);

        $this->lspRepository->method('getAll')->willReturn([$lsp]);
        $this->lspRepository->method('getCustomers')->willReturn([$customer1, $customer2, $customer3]);

        $lspPermissionAssert = new class() implements ActionPermissionAssertInterface {
            public function assertGranted(
                Action $action,
                object $object,
                PermissionAssertContext $context,
            ): void {
                if ($action === Action::Read) {
                    return;
                }

                throw new class() extends Exception implements PermissionExceptionInterface {
                };
            }
        };

        $viewer = $this->createMock(User::class);

        $viewDataProvider = new ViewDataProvider(
            $this->lspRepository,
            $this->lspUserRepository,
            $this->jobCoordinatorRepository,
            $lspPermissionAssert,
            $this->customerActionPermissionAssert,
            $this->userActionPermissionAssert,
        );

        $expected = [
            'id' => (int) $lsp->getId(),
            'name' => $lsp->getName(),
            'canEdit' => false,
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
