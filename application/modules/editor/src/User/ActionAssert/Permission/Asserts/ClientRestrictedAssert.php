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

use BackedEnum;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;

/**
 * @implements PermissionAssertInterface<UserAction, User>
 */
final class ClientRestrictedAssert implements PermissionAssertInterface
{
    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self();
    }

    public function supports(BackedEnum $action): bool
    {
        return true;
    }

    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if (! $context->actor->isClientRestricted()) {
            return;
        }

        if (! $this->isAccessGrated($action, $object, $context)) {
            throw new NoAccessException();
        }
    }

    private function isAccessGrated(BackedEnum $action, object $object, PermissionAssertContext $context): bool
    {
        $isMutateAction = in_array($action, [UserAction::Update, UserAction::Delete], true);

        if (
            $context->actor->isClientPm()
            && $isMutateAction
            && ! in_array(Roles::CLIENTPM_USERS, $context->actor->getRoles(), true)
        ) {
            return false;
        }

        if (UserAction::Delete === $action) {
            return empty(array_diff($object->getCustomersArray(), $context->actor->getCustomersArray()));
        }

        return ! empty(array_intersect($object->getCustomersArray(), $context->actor->getCustomersArray()));
    }
}
