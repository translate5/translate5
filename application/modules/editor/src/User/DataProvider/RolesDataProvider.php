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

 There is a plugin exception availabel for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\User\DataProvider;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\Acl\Validation\RolesValidator;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Zendoverwrites_Translate;

/**
 * @template RoleNode of array{role: string, label: string}
 */
class RolesDataProvider
{
    public function __construct(
        private readonly RolesValidator $rolesValidator,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            RolesValidator::create(),
            ZfExtended_Zendoverwrites_Translate::getInstance(),
        );
    }

    /**
     * @return array{
     *     admins?: RoleNode[],
     *     managers?: RoleNode[],
     *     general: RoleNode[],
     *     clientPmSubRoles: RoleNode[],
     *     requireClient: RoleNode[]
     * }
     */
    public function getGroupedRoles(User $viewer): array
    {
        $groups = [
            'general' => $this->composeRoleSet(Roles::getGeneralRoles(), $viewer),
            'clientPmSubRoles' => $this->composeRoleSet(Roles::getClientPmSubRoles(), $viewer, false),
            'requireClient' => $this->composeRoleSet(Roles::getRolesRequireClient(), $viewer),
        ];

        if ($viewer->isAdmin()) {
            $groups['admins'] = $this->composeRoleSet(Roles::getAdminRoles(), $viewer);
        }

        if (! $viewer->isClientRestricted()) {
            $groups['notRequireClient'] = $this->composeRoleSet(Roles::getRolesNotRequireClient(), $viewer);
        }

        return $groups;
    }

    /**
     * @param string[] $potentialRoles
     * @return RoleNode[]
     */
    private function composeRoleSet(array $potentialRoles, User $viewer, bool $capitalizeRole = true): array
    {
        $roles = [];

        foreach ($potentialRoles as $role) {
            if ($viewer->isCoordinator() && ! in_array($role, $viewer->getRoles(), true)) {
                continue;
            }

            if ($this->rolesValidator->hasAclPermissionToSetRole($viewer, $role)) {
                $roles[] = [
                    'role' => $role,
                    'label' => $capitalizeRole ? mb_ucfirst($this->translate->_($role)) : $this->translate->_($role),
                ];
            }
        }

        return $roles;
    }
}
