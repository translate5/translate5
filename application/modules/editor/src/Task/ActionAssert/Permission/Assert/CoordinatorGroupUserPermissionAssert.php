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

namespace MittagQI\Translate5\Task\ActionAssert\Permission\Assert;

use BackedEnum;
use editor_Models_Task as Task;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\CoordinatorGroupUserHasNoAccessToTaskException;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;

/**
 * @implements PermissionAssertInterface<TaskAction, Task>
 */
final class CoordinatorGroupUserPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            CoordinatorGroupUserRepository::create(),
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return true;
    }

    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        $groupUser = $this->coordinatorGroupUserRepository->findByUser($context->actor);

        if (null === $groupUser) {
            return;
        }

        if (TaskAction::Update === $action || TaskAction::Delete === $action) {
            throw new CoordinatorGroupUserHasNoAccessToTaskException($object);
        }

        if (TaskAction::AssignJob === $action) {
            if (! $groupUser->isCoordinator()) {
                throw new CoordinatorGroupUserHasNoAccessToTaskException($object);
            }

            $this->assertCoordinatorGroupHasJobInTask((int) $groupUser->group->getId(), $object);
        }

        $authUser = $context->actor;

        if ($this->userJobRepository->userHasJobsInTask($authUser->getUserGuid(), $object->getTaskGuid())) {
            return;
        }

        if (! $groupUser->isCoordinator()) {
            throw new CoordinatorGroupUserHasNoAccessToTaskException($object);
        }

        $this->assertCoordinatorGroupHasJobInTask((int) $groupUser->group->getId(), $object);
    }

    private function assertCoordinatorGroupHasJobInTask(int $groupId, Task $object): void
    {
        if (! $this->coordinatorGroupJobRepository->coordinatorGroupHasJobInTask($groupId, $object->getTaskGuid())) {
            throw new CoordinatorGroupUserHasNoAccessToTaskException($object);
        }
    }
}
