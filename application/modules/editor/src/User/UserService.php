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

namespace MittagQI\Translate5\User;

use MittagQI\Translate5\LSP\LspUserService;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\PermissionAudit\Action;
use MittagQI\Translate5\User\PermissionAudit\Auditors\ClientRestrictedPermissionAuditor;
use MittagQI\Translate5\User\PermissionAudit\Auditors\LastCoordinatorPermissionAuditor;
use MittagQI\Translate5\User\PermissionAudit\Auditors\LspUserAccessPermissionAuditor;
use MittagQI\Translate5\User\PermissionAudit\Auditors\ParentPermissionAuditor;
use MittagQI\Translate5\User\PermissionAudit\Auditors\PmInTaskPermissionAuditor;
use MittagQI\Translate5\User\PermissionAudit\Auditors\UserIsEditableAuditor;
use MittagQI\Translate5\User\PermissionAudit\PermissionAuditContext;
use MittagQI\Translate5\User\PermissionAudit\UserActionPermissionAuditor;
use ZfExtended_Models_User as User;

final class UserService
{
    public function __construct(
        private readonly LspUserService $lspUserService,
        private readonly UserRepository $userRepository
    ) {
    }

    public function delete(User $user, ?User $manager): void
    {
        $auditors = [
            new UserIsEditableAuditor(),
            new PmInTaskPermissionAuditor(),
            ParentPermissionAuditor::create(),
            new ClientRestrictedPermissionAuditor(),
            new LastCoordinatorPermissionAuditor($this->lspUserService),
            new LspUserAccessPermissionAuditor($this->lspUserService),
        ];

        $permissionAuditor = new UserActionPermissionAuditor($auditors);

        $permissionAuditor->assertGranted(Action::DELETE, $user, new PermissionAuditContext($manager));

        $this->userRepository->delete($user);
    }
}
