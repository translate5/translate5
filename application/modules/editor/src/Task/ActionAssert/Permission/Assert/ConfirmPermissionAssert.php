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
use editor_Workflow_Manager;
use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\JobAssignmentWasDeletedInTheMeantimeException;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\NoAccessToTaskException;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\UserJobIsNotEditableException;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\Model\User;
use Zend_Acl_Exception;
use Zend_Registry;
use ZfExtended_Acl;
use ZfExtended_Logger;
use function PHPUnit\Framework\isWritable;

/**
 * @implements PermissionAssertInterface<TaskAction, Task>
 */
class ConfirmPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly ZfExtended_Acl $acl,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
            ZfExtended_Acl::getInstance(),
            new editor_Workflow_Manager(),
            Zend_Registry::get('logger')->cloneMe('task.open'),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return TaskAction::Confirm === $action;
    }

    /**
     * @param \editor_Models_Task $object
     * @throws JobAssignmentWasDeletedInTheMeantimeException
     * @throws NoAccessToTaskException
     * @throws UserJobIsNotEditableException
     */
    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        $actorGuid = $context->actor->getUserGuid();
        $granted =
            $actorGuid === $object->getPmGuid()
            || $actorGuid === $object->getCreatedByUserGuid()
            || $this->canLoadAllTasks($context->actor);

        $granted = $granted || (
            $context->actor->isCoordinator()
            && $this->groupOfCoordinatorHasJobForTaskWorkflowStep(
                $context->actor->getUserGuid(),
                $object->getTaskGuid()
            )
        );

        $job = $this->userJobRepository->findUserJobInTask(
            $context->actor->getUserGuid(),
            $object->getTaskGuid(),
            $object->getWorkflowStepName(),
        );

        $noValidJob = null === $job || $job->getIsPmOverride();

        // granted is only true, if the current manager has no job assignment directly.
        $granted = $granted && $noValidJob;

        if ($granted) {
            return;
        }

        // if now there is no job, that means it was deleted in the meantime.
        // User may not access the task anymore
        if ($noValidJob) {
            /**
             * For Client PM and alike roles we have specific assert
             * @see ClientRestrictedPermissionAssert
             */
            if ($context->actor->isClientRestricted()) {
                return;
            }

            // If task was already in session, we must delete it.
            // If not the user will always receive an error in JS, and would not be able to do anything.
            throw new JobAssignmentWasDeletedInTheMeantimeException($object);
        }

        // If user has job assignment the workflow must be considered now.
        $workflow = $this->workflowManager->getCached($object->getWorkflow());

        if (! $workflow->isReadable($job)) {
            $this->logAccessNotGranted($object, $context->actor, $job, TaskAction::Confirm);

            throw new NoAccessToTaskException($object);
        }

        //a check with $workflow->isWriteable($job) makes no sense, since a job in an unconfirmed task
        // is initially by default in view state, and therefore never isWritable!
        // so we check for ! isStateChangeable which ensures that originating state was not finish for example
        $readonlyStates = array_diff($workflow->getReadableStates(), $workflow->getWriteableStates());
        if (TaskAction::Confirm !== $action
            //allow explicitly since unconfirmed tasks are always opened in VIEW:
            || $job->getState() === $workflow::STATE_VIEW
            // prevent waiting / finished to approve:
            || ! in_array($job->getState(), $readonlyStates, true)) {
            return;
        }

        $this->logAccessNotGranted($object, $context->actor, $job, TaskAction::Confirm);

        throw new UserJobIsNotEditableException($job, $object);
    }

    private function logAccessNotGranted(
        Task $task,
        User $authUser,
        ?\editor_Models_TaskUserAssoc $job,
        TaskAction $action
    ): void {
        $this->logger->info('E9999', 'Debug data to E9999 - Keine Zugriffsberechtigung!', [
            'job' => $job ? $job->getDataObject() : 'no tua',
            'isPmOver' => (bool) $job?->getIsPmOverride(),
            'loadAllTasks' => $this->canLoadAllTasks($authUser),
            'isAuthUserTaskPm' => $authUser->getUserGuid() === $task->getPmGuid(),
            'notGrantedAction' => $action->name,
        ]);
    }

    private function groupOfCoordinatorHasJobForTaskWorkflowStep(string $coordinatorUserGuid, string $taskGuid): bool
    {
        return $this->coordinatorGroupJobRepository->coordinatorGroupOfCoordinatorHasJobForTaskWorkflowStep(
            $coordinatorUserGuid,
            $taskGuid
        );
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
