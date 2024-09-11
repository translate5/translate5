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

namespace MittagQI\Translate5\User\Operations;

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\ConflictingRolesExceptionInterface;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Validation\RolesValidator;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Models_User as User;
use ZfExtended_ValidateException;

/**
 * Ment to be used to initialize roles for a user.
 * So only on User creation or in special cases where the roles need to be reinitialized.
 */
final class UserInitRolesOperation
{
    public function __construct(
        private readonly RolesValidator $rolesValidator,
        private readonly ZfExtended_Acl $acl,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        $acl = ZfExtended_Acl::getInstance();

        return new self(
            new RolesValidator($acl),
            $acl,
            new UserRepository(),
        );
    }

    /**
     * @param string[] $roles
     * @throws RolesetHasConflictingRolesException
     * @throws RoleConflictWithRoleThatPopulatedToRolesetException
     * @throws UserIsNotAuthorisedToAssignRoleException
     * @throws Zend_Acl_Exception
     */
    public function initUserRolesBy(User $user, array $roles, User $authUser): void
    {
        if (empty($roles)) {
            return;
        }

        foreach ($roles as $role) {
            if (!$this->hasAclPermissionToSetRole($authUser, $role)) {
                throw new UserIsNotAuthorisedToAssignRoleException($role);
            }
        }

        $this->initRoles($user, $roles);
    }

    /**
     * @param User $user
     * @param string[] $roles
     * @throws ConflictingRolesExceptionInterface
     * @throws Zend_Acl_Exception
     * @throws ZfExtended_ValidateException
     */
    public function initRoles(User $user, array $roles): void
    {
        $this->rolesValidator->assertRolesDontConflict($roles);

        $roles = $this->acl->mergeAutoSetRoles($roles, []);

        $user->setRoles($roles);

        $user->validate();

        $this->userRepository->save($user);
    }

    private function hasAclPermissionToSetRole(User $authUser, string $role): bool
    {
        try {
            return $this->acl->isInAllowedRoles($authUser->getRoles(), SetAclRoleResource::ID, $role);
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}
