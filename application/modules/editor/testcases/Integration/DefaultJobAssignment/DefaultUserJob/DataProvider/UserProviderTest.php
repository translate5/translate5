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

namespace MittagQI\Translate5\Test\Integration\DefaultJobAssignment\DefaultUserJob\DataProvider;

use editor_Models_Customer_Customer;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider\UserProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Model\LanguageServiceProviderCustomer;
use MittagQI\Translate5\Test\Fixtures\CustomerFixtures;
use MittagQI\Translate5\Test\Fixtures\LSP\LspFixtures;
use MittagQI\Translate5\Test\Fixtures\LSP\LspUserFixtures;
use MittagQI\Translate5\Test\Fixtures\UserFixtures;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\TestCase;

class UserProviderTest extends TestCase
{
    private const FIRST_CUSTOMER = 'First customer';

    private const SECOND_CUSTOMER = 'Second customer';

    private const THIRD_CUSTOMER = 'Third customer';

    /**
     * @var User[]
     */
    private array $simpleUsers;

    /**
     * @var User[]
     */
    private array $lspUsers;

    /**
     * @var User[]
     */
    private array $lspCoordinators;

    /**
     * @var User[]
     */
    private array $subLspUsers;

    /**
     * @var User[]
     */
    private array $subLspCoordinators;

    /**
     * @var User[]
     */
    private array $subSubLspUsers;

    /**
     * @var User[]
     */
    private array $subSubLspCoordinators;

    private LanguageServiceProvider $lsp;

    private LanguageServiceProvider $subLsp;

    private LanguageServiceProvider $subSubLsp;

    private User $admin;

    private User $pm;

    private User $clientPm;

    /**
     * @var editor_Models_Customer_Customer[]
     */
    private array $customers;

    private UserFixtures $userFixtures;

    private UserProvider $provider;

    public function setUp(): void
    {
        $this->userFixtures = UserFixtures::create();
        $lspFixtures = LspFixtures::create();
        $lspUserFixtures = LspUserFixtures::create();

        $this->customers = CustomerFixtures::create()->createCustomers(3);

        $this->simpleUsers = $this->userFixtures->createUsers(3);
        $this->admin = $this->userFixtures->createAdminUser();
        $this->pm = $this->userFixtures->createPmUser();
        $this->clientPm = $this->userFixtures->createClientPmUser([
            (int) $this->customers[0]->getId(),
            (int) $this->customers[1]->getId(),
        ]);

        $this->lsp = $lspFixtures->createLsps(1)[0];
        $this->lspUsers = $lspUserFixtures->createLspUsers((int) $this->lsp->getId(), 3);
        $this->lspCoordinators[] = $lspUserFixtures->createCoordinator((int) $this->lsp->getId());

        $this->subLsp = $lspFixtures->createSubLsps((int) $this->lsp->getId(), 1)[0];
        $this->subLspUsers = LspUserFixtures::create()->createLspUsers((int) $this->subLsp->getId(), 3);
        $this->subLspCoordinators[] = $lspUserFixtures->createCoordinator((int) $this->subLsp->getId());

        $this->subSubLsp = $lspFixtures->createSubLsps((int) $this->subLsp->getId(), 1)[0];
        $this->subSubLspUsers = LspUserFixtures::create()->createLspUsers((int) $this->subSubLsp->getId(), 3);
        $this->subSubLspCoordinators[] = $lspUserFixtures->createCoordinator((int) $this->subSubLsp->getId());

        foreach ($this->simpleUsers as $key => $user) {
            $user->assignCustomers([
                (int) $this->customers[$key]->getId(),
            ]);
            $user->save();
        }

        foreach ($this->customers as $customer) {
            $lspCustomer = new LanguageServiceProviderCustomer();
            $lspCustomer->setLspId((int) $this->lsp->getId());
            $lspCustomer->setCustomerId((int) $customer->getId());
            $lspCustomer->save();
        }

        foreach ([0, 1] as $key) {
            $lspCustomer = new LanguageServiceProviderCustomer();
            $lspCustomer->setLspId((int) $this->subLsp->getId());
            $lspCustomer->setCustomerId((int) $this->customers[$key]->getId());
            $lspCustomer->save();
        }

        $lspCustomer = new LanguageServiceProviderCustomer();
        $lspCustomer->setLspId((int) $this->subSubLsp->getId());
        $lspCustomer->setCustomerId((int) $this->customers[0]->getId());
        $lspCustomer->save();

        $this->provider = UserProvider::create();
    }

    public function tearDown(): void
    {
        foreach ($this->simpleUsers as $user) {
            $user->delete();
        }
        $this->admin->delete();
        $this->pm->delete();
        $this->clientPm->delete();

        foreach ($this->customers as $customer) {
            $customer->delete();
        }

        foreach ($this->subSubLspUsers as $subSubLspUser) {
            $subSubLspUser->delete();
        }
        foreach ($this->subSubLspCoordinators as $subSubLspCoordinator) {
            $subSubLspCoordinator->delete();
        }
        $this->subSubLsp->delete();

        foreach ($this->subLspUsers as $subLspUser) {
            $subLspUser->delete();
        }
        foreach ($this->subLspCoordinators as $subLspCoordinator) {
            $subLspCoordinator->delete();
        }
        $this->subLsp->delete();

        foreach ($this->lspUsers as $lspUser) {
            $lspUser->delete();
        }
        foreach ($this->lspCoordinators as $lspCoordinator) {
            $lspCoordinator->delete();
        }
        $this->lsp->delete();
    }

    private function adminDataProvider(): iterable
    {
        $lspUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->lspUsers);
        $lspCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->lspCoordinators);
        $subLspUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subLspUsers);
        $subLspCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subLspCoordinators);
        $subSubLspUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubLspUsers);
        $subSubLspCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubLspCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'lspUsers' => $lspUsers,
                'lspCoordinators' => $lspCoordinators,
                'subLspUsers' => $subLspUsers,
                'subLspCoordinators' => $subLspCoordinators,
                'subSubLspUsers' => $subSubLspUsers,
                'subSubLspCoordinators' => $subSubLspCoordinators,
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'lspUsers' => $lspUsers,
                'lspCoordinators' => $lspCoordinators,
                'subLspUsers' => $subLspUsers,
                'subLspCoordinators' => $subLspCoordinators,
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'lspUsers' => $lspUsers,
                'lspCoordinators' => $lspCoordinators,
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];
    }

    public function testAdminList(): void
    {
        foreach ($this->adminDataProvider() as $case => $data) {
            $lspUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->admin);

            self::assertNotEmpty($list, "$case: List is empty");

            $userGuids = array_map(fn (array $user) => $user['userGuid'], $list);

            $notInList = [];

            foreach ($this->simpleUsers as $user) {
                if (! in_array($user->getUserGuid(), $userGuids, true)) {
                    $notInList[] = $user->getUserGuid();
                }
            }

            if (! empty($notInList)) {
                self::fail($case . ': Users not in list: ' . implode(', ', $notInList));
            }

            if (! in_array($this->admin->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Admin not in list');
            }

            if (! in_array($this->pm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': PM not in list');
            }

            if (! in_array($this->clientPm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Client PM not in list');
            }

            $this->validateLspUsers($case, $lspUsersData, $userGuids);
        }
    }

    private function pmDataProvider(): iterable
    {
        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];
    }

    public function testPmList(): void
    {
        foreach ($this->pmDataProvider() as $case => $data) {
            $lspUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->pm);

            self::assertNotEmpty($list, "$case: List is empty");

            $userGuids = array_map(fn (array $user) => $user['userGuid'], $list);

            $notInList = [];

            foreach ($this->simpleUsers as $user) {
                if (! in_array($user->getUserGuid(), $userGuids, true)) {
                    $notInList[] = $user->getUserGuid();
                }
            }

            if (! empty($notInList)) {
                self::fail($case . ': Users not in list: ' . implode(', ', $notInList));
            }

            if (! in_array($this->admin->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Admin not in list');
            }

            if (! in_array($this->pm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': PM not in list');
            }

            if (! in_array($this->clientPm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Client PM not in list');
            }

            $this->validateLspUsers($case, $lspUsersData, $userGuids);
        }
    }

    private function clientPmDataProvider(): iterable
    {
        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];
    }

    public function testClientPmList(): void
    {
        foreach ($this->clientPmDataProvider() as $case => $data) {
            $lspUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->pm);

            self::assertNotEmpty($list, "$case: List is empty");

            $userGuids = array_map(fn (array $user) => $user['userGuid'], $list);

            $notInList = [];

            foreach ($this->simpleUsers as $user) {
                if (! in_array($user->getUserGuid(), $userGuids, true)) {
                    $notInList[] = $user->getUserGuid();
                }
            }

            if (! empty($notInList)) {
                self::fail($case . ': Users not in list: ' . implode(', ', $notInList));
            }

            if (! in_array($this->admin->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Admin not in list');
            }

            if (! in_array($this->pm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': PM not in list');
            }

            if (! in_array($this->clientPm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Client PM not in list');
            }

            $this->validateLspUsers($case, $lspUsersData, $userGuids);
        }
    }

    private function coordinatorDataProvider(): iterable
    {
        $lspUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->lspUsers);
        $lspCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->lspCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'lspUsers' => $lspUsers,
                'lspCoordinators' => $lspCoordinators,
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'lspUsers' => $lspUsers,
                'lspCoordinators' => $lspCoordinators,
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'lspUsers' => $lspUsers,
                'lspCoordinators' => $lspCoordinators,
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];
    }

    public function testCoordinatorList(): void
    {
        foreach ($this->coordinatorDataProvider() as $case => $data) {
            $lspUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->lspCoordinators[0]);

            self::assertNotEmpty($list, "$case: List is empty");

            $userGuids = array_map(fn (array $user) => $user['userGuid'], $list);

            $inList = [];

            foreach ($this->simpleUsers as $user) {
                if (in_array($user->getUserGuid(), $userGuids, true)) {
                    $inList[] = $user->getUserGuid();
                }
            }

            if (! empty($inList)) {
                self::fail($case . ': Users in list: ' . implode(', ', $inList));
            }

            if (in_array($this->admin->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Admin in list');
            }

            if (in_array($this->pm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': PM in list');
            }

            if (in_array($this->clientPm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Client PM in list');
            }

            $this->validateLspUsers($case, $lspUsersData, $userGuids);
        }
    }

    private function subLspCoordinatorDataProvider(): iterable
    {
        $subLspUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subLspUsers);
        $subLspCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subLspCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => $subLspUsers,
                'subLspCoordinators' => $subLspCoordinators,
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => $subLspUsers,
                'subLspCoordinators' => $subLspCoordinators,
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => $subLspUsers,
                'subLspCoordinators' => $subLspCoordinators,
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];
    }

    public function testSubLspCoordinatorList(): void
    {
        foreach ($this->subLspCoordinatorDataProvider() as $case => $data) {
            $lspUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->subLspCoordinators[0]);

            if (self::THIRD_CUSTOMER === $case) {
                self::assertEmpty($list, "$case: List is not empty");

                continue;
            }

            self::assertNotEmpty($list, "$case: List is empty");

            $userGuids = array_map(fn (array $user) => $user['userGuid'], $list);

            $inList = [];

            foreach ($this->simpleUsers as $user) {
                if (in_array($user->getUserGuid(), $userGuids, true)) {
                    $inList[] = $user->getUserGuid();
                }
            }

            if (! empty($inList)) {
                self::fail($case . ': Users in list: ' . implode(', ', $inList));
            }

            if (in_array($this->admin->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Admin in list');
            }

            if (in_array($this->pm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': PM in list');
            }

            if (in_array($this->clientPm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Client PM in list');
            }

            $this->validateLspUsers($case, $lspUsersData, $userGuids);
        }
    }

    private function subSubLspCoordinatorDataProvider(): iterable
    {
        $subSubLspUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubLspUsers);
        $subSubLspCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubLspCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => $subSubLspUsers,
                'subSubLspCoordinators' => $subSubLspCoordinators,
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'lspUsers' => [],
                'lspCoordinators' => [],
                'subLspUsers' => [],
                'subLspCoordinators' => [],
                'subSubLspUsers' => [],
                'subSubLspCoordinators' => [],
            ],
        ];
    }

    public function testSubSubLspCoordinatorList(): void
    {
        foreach ($this->subSubLspCoordinatorDataProvider() as $case => $data) {
            $lspUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->subSubLspCoordinators[0]);

            if (in_array($case, [self::SECOND_CUSTOMER, self::THIRD_CUSTOMER])) {
                self::assertEmpty($list, "$case: List is not empty");

                continue;
            }

            self::assertNotEmpty($list, "$case: List is empty");

            $userGuids = array_map(fn (array $user) => $user['userGuid'], $list);

            $inList = [];

            foreach ($this->simpleUsers as $user) {
                if (in_array($user->getUserGuid(), $userGuids, true)) {
                    $inList[] = $user->getUserGuid();
                }
            }

            if (! empty($inList)) {
                self::fail($case . ': Users in list: ' . implode(', ', $inList));
            }

            if (in_array($this->admin->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Admin in list');
            }

            if (in_array($this->pm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': PM in list');
            }

            if (in_array($this->clientPm->getUserGuid(), $userGuids, true)) {
                self::fail($case . ': Client PM in list');
            }

            $this->validateLspUsers($case, $lspUsersData, $userGuids);
        }
    }

    private function validateLspUsersGroup(string $case, string $groupName, array $guidsToCheck, array $userGuids): void
    {
        $notInList = array_diff($guidsToCheck, $userGuids);

        if (! empty($guidsToCheck) && ! empty($notInList)) {
            self::fail("$case: $groupName not in list: " . implode(', ', $notInList));
        }

        $inList = array_intersect($guidsToCheck, $userGuids);

        if (empty($guidsToCheck) && ! empty($inList)) {
            self::fail("$case: $groupName in list: " . implode(', ', $notInList));
        }
    }

    private function validateLspUsers(string $case, mixed $lspUsersData, array $userGuids): void
    {
        $this->validateLspUsersGroup($case, 'LSP users', $lspUsersData['lspUsers'], $userGuids);
        $this->validateLspUsersGroup($case, 'LSP Coordinators', $lspUsersData['lspCoordinators'], $userGuids);
        $this->validateLspUsersGroup($case, 'Sub LSP users', $lspUsersData['subLspUsers'], $userGuids);
        $this->validateLspUsersGroup($case, 'Sub LSP Coordinators', $lspUsersData['subLspCoordinators'], $userGuids);
        $this->validateLspUsersGroup($case, 'Sub Sub LSP users', $lspUsersData['subSubLspUsers'], $userGuids);
        $this->validateLspUsersGroup(
            $case,
            'Sub Sub LSP Coordinators',
            $lspUsersData['subSubLspCoordinators'],
            $userGuids
        );
    }
}
