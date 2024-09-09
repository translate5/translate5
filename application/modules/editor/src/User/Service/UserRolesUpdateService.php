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

namespace MittagQI\Translate5\User\Service;

use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\ZfExtended\Acl\SetAclRoleResource;
use ZfExtended_Acl;
use ZfExtended_Models_User as User;

final class UserRolesUpdateService
{
    public function __construct(
        private readonly UserActionFeasibilityAssertInterface $userActionFeasibilityChecker,
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserActionFeasibilityAssert::create(),
            ZfExtended_Acl::getInstance(),
        );
    }

    /**
     * @param string[] $roles
     * @throws FeasibilityExceptionInterface
     */
    public function updateRolesBy(User $user, array $roles, User $actor): void
    {
        $this->userActionFeasibilityChecker->assertAllowed(Action::UPDATE, $user);

        if (empty($roles)) {
            return;
        }

        $oldRoles = $user->getRoles();

        //if there are old roles, remove the roles for which the user isAllowed for setaclrole
        foreach ($oldRoles as $i => $old) {
            if ($this->hasAclPermissionToSetRole($actor, $old)) {
                unset($oldRoles[$i]);
            }
        }

        //check if the user is allowed for the requested roles
        foreach ($roles as $role) {
            if (! $this->hasAclPermissionToSetRole($actor, $role)) {
                throw new \ZfExtended_NoAccessException("Authenticated User is not allowed to modify role " . $role);
            }
        }

        // merge the requested roles and the old roles and apply the autoset roles to them
        $roles = $this->acl->mergeAutoSetRoles($roles, $oldRoles);

        $this->updateRoles($user, $roles);
    }

    public function updateRoles(User $user, array $roles): void
    {
    }

    private function hasAclPermissionToSetRole(User $authUser, string $role)
    {
        return $this->acl->isInAllowedRoles($authUser->getRoles(), SetAclRoleResource::ID, $role);
    }
}
