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

namespace MittagQI\Translate5\User\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\ZfExtended\Acl\SystemResource;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Acl;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

/**
 * @implements PermissionAssertInterface<User>
 */
final class ParentPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly ZfExtended_Acl $acl,
        private readonly ZfExtended_AuthenticationInterface $auth
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Acl::getInstance(),
            ZfExtended_Authentication::getInstance()
        );
    }

    public function supports(Action $action): bool
    {
        return in_array($action, [Action::UPDATE, Action::DELETE, Action::READ], true);
    }

    /**
     * Restrict access if user is not same as the manager or a child of the manager
     *
     * {@inheritDoc}
     */
    public function assertGranted(object $object, PermissionAssertContext $context): void
    {
        // Am I allowed to see all users:
        if ($this->acl->isInAllowedRoles(
            $this->auth->getUserRoles(),
            SystemResource::ID,
            SystemResource::SEE_ALL_USERS
        )) {
            return;
        }

        $manager = $context->manager;

        // if user is current user, also everything is OK
        if ($manager->getUserGuid() === $object->getUserGuid()) {
            return;
        }

        if ($object->hasParent($manager->getId())) {
            return;
        }

        throw new NoAccessException();
    }
}
