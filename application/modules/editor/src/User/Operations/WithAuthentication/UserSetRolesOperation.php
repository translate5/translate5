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

namespace MittagQI\Translate5\User\Operations\WithAuthentication;

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\Exception\RoleConflictWithRoleThatPopulatedToRolesetException;
use MittagQI\Translate5\User\Exception\RolesetHasConflictingRolesException;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User as User;

/**
 * Ment to be used to initialize roles for a user.
 * So only on User creation or in special cases where the roles need to be reinitialized.
 */
final class UserSetRolesOperation implements UserSetRolesOperationInterface
{
    public function __construct(
        private readonly UserSetRolesOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly ZfExtended_Acl $acl,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\User\Operations\UserSetRolesOperation::create(),
            ZfExtended_Authentication::getInstance(),
            ZfExtended_Acl::getInstance(),
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
    public function setRoles(User $user, array $roles): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        foreach ($roles as $role) {
            if (! $this->hasAclPermissionToSetRole($authUser, $role)) {
                throw new UserIsNotAuthorisedToAssignRoleException($role);
            }

            if ($authUser->isAdmin()) {
                continue;
            }

            if (! in_array($role, $authUser->getRoles(), true)) {
                throw new UserIsNotAuthorisedToAssignRoleException($role);
            }
        }

        $this->operation->setRoles($user, $roles);
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
