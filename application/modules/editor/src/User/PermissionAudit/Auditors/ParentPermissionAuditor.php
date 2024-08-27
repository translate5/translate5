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

namespace MittagQI\Translate5\User\PermissionAudit\Auditors;

use MittagQI\Translate5\User\PermissionAudit\ActionInterface;
use MittagQI\Translate5\User\PermissionAudit\Exception\NoAccessException;
use MittagQI\Translate5\User\PermissionAudit\PermissionAuditContext;
use MittagQI\ZfExtended\Acl\SystemResource;
use ZfExtended_Models_User as User;

final class ParentPermissionAuditor implements PermissionAuditorInterface
{
    public function __construct(
        private readonly \ZfExtended_Acl $acl,
        private readonly \ZfExtended_Authentication $auth
    ) {
    }

    public static function create(): self
    {
        return new self(
            \ZfExtended_Acl::getInstance(),
            \ZfExtended_Authentication::getInstance()
        );
    }

    /**
     * Restrict access if user is not same as the manager or a child of the manager
     */
    public function assertGranted(ActionInterface $action, User $user, PermissionAuditContext $context): void
    {
        //Am I allowed to see all users:
        if ($this->acl->isInAllowedRoles(
            $this->auth->getUserRoles(),
            SystemResource::ID,
            SystemResource::SEE_ALL_USERS
        )) {
            return;
        }

        $manager = $context->manager;

        //if the edited user is the current user, also everything is OK
        if ($manager->getUserGuid() === $user->getUserGuid()) {
            return;
        }

        if ($user->hasParent($manager->getId())) {
            return;
        }

        throw new NoAccessException();
    }
}
