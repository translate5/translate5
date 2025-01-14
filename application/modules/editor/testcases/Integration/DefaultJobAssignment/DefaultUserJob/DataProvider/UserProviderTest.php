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
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroupCustomer;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider\UserProvider;
use MittagQI\Translate5\Test\Fixtures\CoordinatorGroup\CoordinatorGroupFixtures;
use MittagQI\Translate5\Test\Fixtures\CoordinatorGroup\CoordinatorGroupUserFixtures;
use MittagQI\Translate5\Test\Fixtures\CustomerFixtures;
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
    private array $groupUsers;

    /**
     * @var User[]
     */
    private array $groupCoordinators;

    /**
     * @var User[]
     */
    private array $subGroupUsers;

    /**
     * @var User[]
     */
    private array $subGroupCoordinators;

    /**
     * @var User[]
     */
    private array $subSubGroupUsers;

    /**
     * @var User[]
     */
    private array $subSubGroupCoordinators;

    private CoordinatorGroup $group;

    private CoordinatorGroup $subGroup;

    private CoordinatorGroup $subSubGroup;

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
        $groupFixtures = CoordinatorGroupFixtures::create();
        $groupUserFixtures = CoordinatorGroupUserFixtures::create();

        $this->customers = CustomerFixtures::create()->createCustomers(3);

        $this->simpleUsers = $this->userFixtures->createUsers(3);
        $this->admin = $this->userFixtures->createAdminUser();
        $this->pm = $this->userFixtures->createPmUser();
        $this->clientPm = $this->userFixtures->createClientPmUser([
            (int) $this->customers[0]->getId(),
            (int) $this->customers[1]->getId(),
        ]);

        $this->group = $groupFixtures->createCoordinatorGroups(1)[0];
        $this->groupUsers = $groupUserFixtures->createCoordinatorGroupUsers((int) $this->group->getId(), 3);
        $this->groupCoordinators[] = $groupUserFixtures->createCoordinator((int) $this->group->getId());

        $this->subGroup = $groupFixtures->createSubCoordinatorGroups((int) $this->group->getId(), 1)[0];
        $this->subGroupUsers = CoordinatorGroupUserFixtures::create()->createCoordinatorGroupUsers((int) $this->subGroup->getId(), 3);
        $this->subGroupCoordinators[] = $groupUserFixtures->createCoordinator((int) $this->subGroup->getId());

        $this->subSubGroup = $groupFixtures->createSubCoordinatorGroups((int) $this->subGroup->getId(), 1)[0];
        $this->subSubGroupUsers = CoordinatorGroupUserFixtures::create()->createCoordinatorGroupUsers((int) $this->subSubGroup->getId(), 3);
        $this->subSubGroupCoordinators[] = $groupUserFixtures->createCoordinator((int) $this->subSubGroup->getId());

        foreach ($this->simpleUsers as $key => $user) {
            $user->assignCustomers([
                (int) $this->customers[$key]->getId(),
            ]);
            $user->save();
        }

        foreach ($this->customers as $customer) {
            $groupCustomer = new CoordinatorGroupCustomer();
            $groupCustomer->setGroupId((int) $this->group->getId());
            $groupCustomer->setCustomerId((int) $customer->getId());
            $groupCustomer->save();
        }

        foreach ([0, 1] as $key) {
            $groupCustomer = new CoordinatorGroupCustomer();
            $groupCustomer->setGroupId((int) $this->subGroup->getId());
            $groupCustomer->setCustomerId((int) $this->customers[$key]->getId());
            $groupCustomer->save();
        }

        $groupCustomer = new CoordinatorGroupCustomer();
        $groupCustomer->setGroupId((int) $this->subSubGroup->getId());
        $groupCustomer->setCustomerId((int) $this->customers[0]->getId());
        $groupCustomer->save();

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

        foreach ($this->subSubGroupUsers as $subSubGroupUser) {
            $subSubGroupUser->delete();
        }
        foreach ($this->subSubGroupCoordinators as $subSubGroupCoordinator) {
            $subSubGroupCoordinator->delete();
        }
        $this->subSubGroup->delete();

        foreach ($this->subGroupUsers as $subGroupUser) {
            $subGroupUser->delete();
        }
        foreach ($this->subGroupCoordinators as $subGroupCoordinator) {
            $subGroupCoordinator->delete();
        }
        $this->subGroup->delete();

        foreach ($this->groupUsers as $groupUser) {
            $groupUser->delete();
        }
        foreach ($this->groupCoordinators as $groupCoordinator) {
            $groupCoordinator->delete();
        }
        $this->group->delete();
    }

    private function adminDataProvider(): iterable
    {
        $groupUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->groupUsers);
        $groupCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->groupCoordinators);
        $subGroupUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subGroupUsers);
        $subGroupCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subGroupCoordinators);
        $subSubGroupUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubGroupUsers);
        $subSubGroupCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubGroupCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'groupUsers' => $groupUsers,
                'groupCoordinators' => $groupCoordinators,
                'subGroupUsers' => $subGroupUsers,
                'subGroupCoordinators' => $subGroupCoordinators,
                'subSubGroupUsers' => $subSubGroupUsers,
                'subSubGroupCoordinators' => $subSubGroupCoordinators,
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'groupUsers' => $groupUsers,
                'groupCoordinators' => $groupCoordinators,
                'subGroupUsers' => $subGroupUsers,
                'subGroupCoordinators' => $subGroupCoordinators,
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'groupUsers' => $groupUsers,
                'groupCoordinators' => $groupCoordinators,
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];
    }

    public function testAdminList(): void
    {
        foreach ($this->adminDataProvider() as $case => $data) {
            $groupUsersData = $data[1];
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

            $this->validateGroupUsers($case, $groupUsersData, $userGuids);
        }
    }

    private function pmDataProvider(): iterable
    {
        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];
    }

    public function testPmList(): void
    {
        foreach ($this->pmDataProvider() as $case => $data) {
            $groupUsersData = $data[1];
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

            $this->validateGroupUsers($case, $groupUsersData, $userGuids);
        }
    }

    private function clientPmDataProvider(): iterable
    {
        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];
    }

    public function testClientPmList(): void
    {
        foreach ($this->clientPmDataProvider() as $case => $data) {
            $groupUsersData = $data[1];
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

            $this->validateGroupUsers($case, $groupUsersData, $userGuids);
        }
    }

    private function coordinatorDataProvider(): iterable
    {
        $groupUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->groupUsers);
        $groupCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->groupCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'groupUsers' => $groupUsers,
                'groupCoordinators' => $groupCoordinators,
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'groupUsers' => $groupUsers,
                'groupCoordinators' => $groupCoordinators,
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'groupUsers' => $groupUsers,
                'groupCoordinators' => $groupCoordinators,
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];
    }

    public function testCoordinatorList(): void
    {
        foreach ($this->coordinatorDataProvider() as $case => $data) {
            $groupUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->groupCoordinators[0]);

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

            $this->validateGroupUsers($case, $groupUsersData, $userGuids);
        }
    }

    private function subGroupCoordinatorDataProvider(): iterable
    {
        $subGroupUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subGroupUsers);
        $subGroupCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subGroupCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => $subGroupUsers,
                'subGroupCoordinators' => $subGroupCoordinators,
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => $subGroupUsers,
                'subGroupCoordinators' => $subGroupCoordinators,
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => $subGroupUsers,
                'subGroupCoordinators' => $subGroupCoordinators,
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];
    }

    public function testSubGroupCoordinatorList(): void
    {
        foreach ($this->subGroupCoordinatorDataProvider() as $case => $data) {
            $groupUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->subGroupCoordinators[0]);

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

            $this->validateGroupUsers($case, $groupUsersData, $userGuids);
        }
    }

    private function subSubGroupCoordinatorDataProvider(): iterable
    {
        $subSubGroupUsers = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubGroupUsers);
        $subSubGroupCoordinators = array_map(fn (User $user) => $user->getUserGuid(), $this->subSubGroupCoordinators);

        yield self::FIRST_CUSTOMER => [
            (int) $this->customers[0]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => $subSubGroupUsers,
                'subSubGroupCoordinators' => $subSubGroupCoordinators,
            ],
        ];

        yield self::SECOND_CUSTOMER => [
            (int) $this->customers[1]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];

        yield self::THIRD_CUSTOMER => [
            (int) $this->customers[2]->getId(),
            [
                'groupUsers' => [],
                'groupCoordinators' => [],
                'subGroupUsers' => [],
                'subGroupCoordinators' => [],
                'subSubGroupUsers' => [],
                'subSubGroupCoordinators' => [],
            ],
        ];
    }

    public function testSubSubGroupCoordinatorList(): void
    {
        foreach ($this->subSubGroupCoordinatorDataProvider() as $case => $data) {
            $groupUsersData = $data[1];
            $list = $this->provider->getPossibleUsers($data[0], $this->subSubGroupCoordinators[0]);

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

            $this->validateGroupUsers($case, $groupUsersData, $userGuids);
        }
    }

    private function validateGroupUsersGroup(string $case, string $groupName, array $guidsToCheck, array $userGuids): void
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

    private function validateGroupUsers(string $case, mixed $groupUsersData, array $userGuids): void
    {
        $this->validateGroupUsersGroup($case, 'Coordinator Group users', $groupUsersData['groupUsers'], $userGuids);
        $this->validateGroupUsersGroup($case, 'Coordinator Group Coordinators', $groupUsersData['groupCoordinators'], $userGuids);
        $this->validateGroupUsersGroup($case, 'Sub Coordinator Group users', $groupUsersData['subGroupUsers'], $userGuids);
        $this->validateGroupUsersGroup($case, 'Sub Coordinator Group Coordinators', $groupUsersData['subGroupCoordinators'], $userGuids);
        $this->validateGroupUsersGroup($case, 'Sub Sub Coordinator Group users', $groupUsersData['subSubGroupUsers'], $userGuids);
        $this->validateGroupUsersGroup(
            $case,
            'Sub Sub Coordinator Group Coordinators',
            $groupUsersData['subSubGroupCoordinators'],
            $userGuids
        );
    }
}
