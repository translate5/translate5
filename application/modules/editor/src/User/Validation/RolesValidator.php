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

namespace MittagQI\Translate5\User\Validation;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\User\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\ZfExtended\Acl\AutoSetRoleResource;
use MittagQI\ZfExtended\Acl\Roles as BaseRoles;
use ZfExtended_Acl;

class RolesValidator
{
    /**
     * @var array<string, string[]>
     */
    private array $conflictMap = [
        Roles::JOB_COORDINATOR => [
            BaseRoles::ADMIN,
            BaseRoles::SYSTEMADMIN,
            BaseRoles::PM,
            BaseRoles::CLIENTPM,
        ],
    ];

    public function __construct(
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    /**
     * @throws ConflictingRolesExceptionInterface
     * @throws \Zend_Acl_Exception
     */
    public function assertRolesDontConflict(array $roles): void
    {
        if (empty($roles)) {
            return;
        }

        $potentialConflictRoles = array_intersect($roles, array_keys($this->conflictMap));

        // straightforward check if any of the roles is in the conflict map
        foreach ($potentialConflictRoles as $potentialConflictRole) {
            $conflictingRoles = array_intersect($this->conflictMap[$potentialConflictRole], $roles);

            if (! empty($conflictingRoles)) {
                throw new RolesetHasConflictingRolesException($potentialConflictRole, $conflictingRoles);
            }
        }

        // check for populated roles. Some roles are populated with setaclrole
        foreach ($roles as $role) {
            if (in_array($role, $potentialConflictRoles, true)) {
                continue;
            }

            $populatedRoles = $this->acl->getRightsToRolesAndResource([$role], AutoSetRoleResource::ID);

            foreach ($potentialConflictRoles as $potentialConflictRole) {
                $conflictingRoles = array_intersect($this->conflictMap[$potentialConflictRole], $populatedRoles);

                if (! empty($conflictingRoles)) {
                    throw new RoleConflictWithRoleThatPopulatedToRolesetException(
                        $role,
                        $potentialConflictRole,
                        $conflictingRoles
                    );
                }
            }
        }
    }
}
