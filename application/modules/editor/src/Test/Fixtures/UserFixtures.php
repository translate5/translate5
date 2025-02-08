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

namespace MittagQI\Translate5\Test\Fixtures;

use Faker\Factory;
use Faker\Generator;
use MittagQI\Translate5\Acl\ExpandRolesService;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Utils;

/**
 * @codeCoverageIgnore
 */
class UserFixtures
{
    public function __construct(
        private readonly ExpandRolesService $expandRolesService,
        private readonly Generator $faker,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ExpandRolesService::create(),
            Factory::create(),
        );
    }

    /**
     * @return User[]
     */
    public function createUsers(int $count): array
    {
        $users = [];

        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setUserGuid(ZfExtended_Utils::guid(true));
            $user->setFirstName($this->faker->firstName());
            $user->setSurName($this->faker->lastName());
            $user->setLogin($this->faker->userName());
            $user->setEmail($this->faker->email());
            $user->setEditable(true);
            $user->setRoles([Roles::EDITOR]);

            $user->save();

            $users[] = $user;
        }

        return $users;
    }

    public function createAdminUser(): User
    {
        $random = bin2hex(random_bytes(4));
        $user = new User();
        $user->setUserGuid(ZfExtended_Utils::guid(true));
        $user->setFirstName('Admin');
        $user->setSurName('User');
        $user->setLogin('admin.' . $random);
        $user->setEmail($random . '.admin@translate5.net');
        $user->setEditable(true);

        $roles = $this->expandRolesService->expandListWithAutoRoles([Roles::ADMIN], []);
        $user->setRoles($roles);

        $user->save();

        return $user;
    }

    public function createPmUser(): User
    {
        $random = bin2hex(random_bytes(4));
        $user = new User();
        $user->setUserGuid(ZfExtended_Utils::guid(true));
        $user->setFirstName('Project');
        $user->setSurName('Manager');
        $user->setLogin('pm-user.' . $random);
        $user->setEmail($random . '.pm-user@translate5.net');
        $user->setEditable(true);

        $roles = $this->expandRolesService->expandListWithAutoRoles([Roles::PM], []);
        $user->setRoles($roles);

        $user->save();

        return $user;
    }

    /**
     * @param int[] $clientIds
     * @param string[] $subRoles
     */
    public function createClientPmUser(array $clientIds, array $subRoles = []): User
    {
        $random = bin2hex(random_bytes(4));
        $user = new User();
        $user->setUserGuid(ZfExtended_Utils::guid(true));
        $user->setFirstName('Project');
        $user->setSurName('Manager (Client)');
        $user->setLogin('client-pm-user.' . $random);
        $user->setEmail($random . '.client-pm-user@translate5.net');
        $user->setEditable(true);
        $user->assignCustomers($clientIds);

        $roles = $this->expandRolesService->expandListWithAutoRoles(
            array_merge([Roles::CLIENTPM], $subRoles),
            []
        );
        $user->setRoles($roles);

        $user->save();

        return $user;
    }

    public function createCoordinatorUser(): User
    {
        $random = bin2hex(random_bytes(4));
        $user = new User();
        $user->setUserGuid(ZfExtended_Utils::guid(true));
        $user->setFirstName('Coordinator');
        $user->setSurName('User');
        $user->setLogin('coordinator-user.' . $random);
        $user->setEmail($random . '.coordinator@translate5.net');

        $roles = $this->expandRolesService->expandListWithAutoRoles([Roles::JOB_COORDINATOR], []);
        $user->setRoles($roles);

        $user->save();

        return $user;
    }
}
