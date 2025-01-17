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
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleCoordinatorGroupUserException;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;

/**
 * @implements PermissionAssertInterface<UserAction, User>
 */
final class CoordinatorGroupUserAccessPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupUserRepository::create(),
            JobCoordinatorRepository::create(),
            CoordinatorGroupRepository::create(),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return in_array($action, [UserAction::Update, UserAction::Delete, UserAction::Read], true);
    }

    public function assertGranted(\BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        $authUser = $context->actor;

        if ($authUser->isAdmin()) {
            return;
        }

        $groupUser = $this->coordinatorGroupUserRepository->findByUser($object);

        if (null === $groupUser) {
            return;
        }

        if ($authUser->isPm()) {
            if ($this->isGrantedForPm($groupUser)) {
                return;
            }

            throw new NotAccessibleCoordinatorGroupUserException($groupUser);
        }

        if ($authUser->isClientPm()) {
            if ($this->isGrantedForClientPm($action, $groupUser, $authUser)) {
                return;
            }

            throw new NotAccessibleCoordinatorGroupUserException($groupUser);
        }

        if ($authUser->getUserGuid() === $object->getUserGuid() && UserAction::Read === $action) {
            return;
        }

        $authCoordinator = $this->coordinatorRepository->findByUser($authUser);

        if (null === $authCoordinator) {
            throw new NotAccessibleCoordinatorGroupUserException($groupUser);
        }

        if (! $authCoordinator->isSupervisorOf($groupUser)) {
            throw new NotAccessibleCoordinatorGroupUserException($groupUser);
        }
    }

    private function isGrantedForPm(CoordinatorGroupUser $groupUser): bool
    {
        if (! $groupUser->group->isTopRankGroup()) {
            return false;
        }

        return $groupUser->isCoordinator();
    }

    private function isGrantedForClientPm(UserAction $action, CoordinatorGroupUser $groupUser, User $actor): bool
    {
        if (! $this->isGrantedForPm($groupUser)) {
            return false;
        }

        if (
            in_array($action, [UserAction::Update, UserAction::Delete], true)
            && ! in_array(Roles::CLIENTPM_USERS, $actor->getRoles(), true)
        ) {
            return false;
        }

        $groupCustomerIds = $this->coordinatorGroupRepository->getCustomerIds((int) $groupUser->group->getId());

        return ! empty(array_intersect($actor->getCustomersArray(), $groupCustomerIds));
    }
}
