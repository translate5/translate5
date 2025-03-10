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

namespace MittagQI\Translate5\Acl\Validation;

use MittagQI\Translate5\Acl\Exception\ClientRestrictedAndNotRolesProvidedTogetherException;
use MittagQI\Translate5\Acl\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\Acl\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\Acl\Exception\RolesCannotBeSetForUserException;
use MittagQI\Translate5\Acl\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\Acl\ExpandRolesService;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\ZfExtended\Acl\AutoSetRoleResource;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use Zend_Acl_Exception;
use ZfExtended_Acl;

class RolesValidator
{
    public function __construct(
        private readonly ZfExtended_Acl $acl,
        private readonly ExpandRolesService $expandRolesService,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Acl::getInstance(),
            ExpandRolesService::create(),
            CoordinatorGroupUserRepository::create(),
        );
    }

    public function assertUserCanSetRoles(User $user, array $roles): void
    {
        foreach ($roles as $role) {
            if (! $this->hasAclPermissionToSetRole($user, $role)) {
                throw new UserIsNotAuthorisedToAssignRoleException($role);
            }

            if ($user->isAdmin() || $user->isPm()) {
                continue;
            }

            if (! in_array($role, $user->getRoles(), true)) {
                throw new UserIsNotAuthorisedToAssignRoleException($role);
            }
        }
    }

    public function assertRolesCanBeSetForUser(array $roles, User $user): void
    {
        $groupUser = $this->coordinatorGroupUserRepository->findByUser($user);
        $providedAdminRoles = array_intersect($roles, Roles::getAdminRoles());

        if ($groupUser !== null && ! empty($providedAdminRoles)) {
            throw new RolesCannotBeSetForUserException($providedAdminRoles);
        }

        if ($groupUser === null && in_array(Roles::JOB_COORDINATOR, $roles, true)) {
            throw new RolesCannotBeSetForUserException([Roles::JOB_COORDINATOR]);
        }
    }

    /**
     * @throws ConflictingRolesExceptionInterface
     * @throws Zend_Acl_Exception
     */
    public function assertRolesDontConflict(array $roles): void
    {
        if (empty($roles)) {
            return;
        }

        $potentialConflictRoles = array_intersect($roles, array_keys(Roles::CONFLICTING_ROLES));

        // straightforward check if any of the roles is in the conflict map
        foreach ($potentialConflictRoles as $potentialConflictRole) {
            $conflictingRoles = array_intersect(Roles::CONFLICTING_ROLES[$potentialConflictRole], $roles);

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
                $conflictingRoles = array_intersect(Roles::CONFLICTING_ROLES[$potentialConflictRole], $populatedRoles);

                if (! empty($conflictingRoles)) {
                    throw new RoleConflictWithRoleThatPopulatedToRolesetException(
                        $role,
                        $potentialConflictRole,
                        $conflictingRoles
                    );
                }
            }
        }

        $roles = $this->expandRolesService->expandListWithAutoRoles($roles, []);

        $hasPrivilegedRoles = ! empty(array_intersect($roles, Roles::getAdminRoles()))
            || ! empty(array_intersect($roles, Roles::getRolesNotRequireClient()));

        if ($hasPrivilegedRoles && ! empty(array_intersect($roles, Roles::getClientRestrictedRoles()))) {
            throw new ClientRestrictedAndNotRolesProvidedTogetherException();
        }
    }

    public function hasAclPermissionToSetRole(User $viewer, string $role): bool
    {
        try {
            return $this->acl->isInAllowedRoles($viewer->getRoles(), SetAclRoleResource::ID, $role);
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}
