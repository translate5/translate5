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
use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\NoAccessToTaskException;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\Model\User;
use Zend_Acl_Exception;
use ZfExtended_Acl;

/**
 * @implements PermissionAssertInterface<TaskAction, Task>
 */
final class UserHasJobInTaskPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            ZfExtended_Acl::getInstance(),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return TaskAction::Read === $action;
    }

    /**
     * to access a task the user must either have the loadAllTasks right,
     * or must be the tasks PM, or must be associated to the task
     *
     * {@inheritDoc}
     */
    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if ($object->getPmGuid() === $context->actor->getUserGuid()) {
            return;
        }

        if ($this->userJobRepository->userHasJobsInTask($context->actor->getUserGuid(), $object->getTaskGuid())) {
            return;
        }

        if ($context->actor->isCoordinator()) {
            return;
        }

        if (! $this->canLoadAllTasks($context->actor)) {
            throw new NoAccessToTaskException($object);
        }
    }

    private function canLoadAllTasks(User $authUser): bool
    {
        try {
            return $this->acl->isInAllowedRoles($authUser->getRoles(), Rights::ID, Rights::LOAD_ALL_TASKS);
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}
