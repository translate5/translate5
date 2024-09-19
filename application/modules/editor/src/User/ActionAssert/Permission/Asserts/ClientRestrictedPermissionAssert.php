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

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\ClientRestrictionException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use ZfExtended_Models_User as User;

/**
 * @implements PermissionAssertInterface<User>
 */
final class ClientRestrictedPermissionAssert implements PermissionAssertInterface
{
    public function supports(Action $action): bool
    {
        return in_array($action, [Action::CREATE, Action::UPDATE, Action::DELETE, Action::READ], true);
    }

    /**
     * Restrict access by clients
     *
     * {@inheritDoc}
     */
    public function assertGranted(object $object, PermissionAssertContext $context): void
    {
        if (! $context->manager->isClientRestricted()) {
            return;
        }

        $allowedCustomerIs = $context->manager->getRestrictedClientIds();

        if (! empty(array_diff($object->getCustomersArray(), $allowedCustomerIs))) {
            throw new ClientRestrictionException();
        }
    }
}
