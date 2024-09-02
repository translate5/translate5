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

use MittagQI\Translate5\LSP\LspUserService;
use MittagQI\Translate5\User\Action;
use MittagQI\Translate5\User\PermissionAudit\Exception\NotAccessibleForLspUserException;
use MittagQI\Translate5\User\PermissionAudit\PermissionAuditContext;
use MittagQI\ZfExtended\Acl\Roles;
use ZfExtended_Models_User as User;

final class LspUserAccessPermissionAuditor implements PermissionAuditorInterface
{
    public function __construct(
        private readonly LspUserService $lspUserService
    ) {
    }

    public function supports(Action $action): bool
    {
        return in_array($action, [Action::UPDATE, Action::DELETE, Action::READ], true);
    }

    /**
     * Restrict access for job coordinators to LSP users and other job coordinator
     */
    public function assertGranted(User $user, PermissionAuditContext $context): void
    {
        $manager = $context->manager;
        $roles = $manager->getRoles();

        if (array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)) {
            return;
        }

        $coordinator = $this->lspUserService->findCoordinatorBy($manager);

        if (null === $coordinator) {
            return;
        }

        foreach ($this->lspUserService->getAccessibleUsers($coordinator) as $accessibleUser) {
            if ($accessibleUser->getId() === $user->getId()) {
                return;
            }
        }

        throw new NotAccessibleForLspUserException($coordinator);
    }
}
