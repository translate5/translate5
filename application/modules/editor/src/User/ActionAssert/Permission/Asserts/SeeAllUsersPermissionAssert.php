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
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use MittagQI\ZfExtended\Acl\SystemResource;
use ZfExtended_Acl;

/**
 * @implements PermissionAssertInterface<UserAction, User>
 */
final class SeeAllUsersPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly ZfExtended_Acl $acl,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Acl::getInstance(),
            CoordinatorGroupUserRepository::create(),
        );
    }

    public function supports(\BackedEnum $action): bool
    {
        return in_array($action, [UserAction::Update, UserAction::Delete, UserAction::Read], true);
    }

    public function assertGranted(\BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        $authUser = $context->actor;

        if ($authUser->getUserGuid() === $object->getUserGuid() && $action === UserAction::Read) {
            return;
        }

        if ($this->acl->isInAllowedRoles($authUser->getRoles(), SystemResource::ID, SystemResource::SEE_ALL_USERS)) {
            return;
        }

        if (
            $authUser->isClientPm()
            && ! empty(array_intersect($object->getCustomersArray(), $authUser->getCustomersArray()))
        ) {
            return;
        }

        $groupUser = $this->coordinatorGroupUserRepository->findByUser($object);

        if (null !== $groupUser) {
            return;
        }

        throw new NoAccessException();
    }
}
