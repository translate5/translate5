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
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\UserJobIsNotEditableException;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\JobAssignmentWasDeletedInTheMeantimeException;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\NoAccessToTaskException;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\Model\User;
use Zend_Acl_Exception;
use Zend_Registry;
use ZfExtended_Acl;
use ZfExtended_Logger;

/**
 * @implements PermissionAssertInterface<Task>
 */
class OpenPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly ZfExtended_Acl $acl,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            LspJobRepository::create(),
            ZfExtended_Acl::getInstance(),
            new editor_Workflow_Manager(),
            Zend_Registry::get('logger')->cloneMe('task.open'),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return TaskAction::View === $action || TaskAction::Edit === $action;
    }

    /**
     * {@inheritDoc}
     */
    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        $granted = $context->actor->getUserGuid() === $object->getPmGuid()
            || $this->canLoadAllTasks($context->actor)
            || (
                $context->actor->isCoordinator()
                && $this->lspOfCoordinatorHasJobForTaskWorkflowStep($context->actor->getUserGuid(), $object->getTaskGuid())
            )
        ;

        $job = $this->userJobRepository->findUserJobInTask(
            $context->actor->getUserGuid(),
            $object->getTaskGuid(),
            $object->getWorkflowStepName(),
        );

        // granted is only true, if the current manager has no job assignment directly.
        $granted = $granted && (null === $job || $job->getIsPmOverride());

        if ($granted) {
            return;
        }

        // if now there is no job, that means it was deleted in the meantime.
        // User may not access the task anymore
        if (null === $job) {
            // If task was already in session, we must delete it.
            // If not the user will always receive an error in JS, and would not be able to do anything.
            throw new JobAssignmentWasDeletedInTheMeantimeException($object);
        }

        // If user has job assignment the workflow must be considered now.
        $workflow = $this->workflowManager->getCached($object->getWorkflow());

        // the job state was changed by a PM, then the task may not be edited anymore by the user
        if (TaskAction::Edit === $action && ! $workflow->isWriteable($job)) {
            $this->logAccessNotGranted($object, $context->actor, $job, TaskAction::Edit);

            throw new UserJobIsNotEditableException($job, $object);
        }

        if (TaskAction::View === $action && ! $workflow->isReadable($job)) {
            $this->logAccessNotGranted($object, $context->actor, $job, TaskAction::View);

            throw new NoAccessToTaskException($object);
        }
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

    private function lspOfCoordinatorHasJobForTaskWorkflowStep(string $coordinatorUserGuid, string $taskGuid): bool
    {
        return $this->lspJobRepository->lspOfCoordinatorHasJobForTaskWorkflowStep($coordinatorUserGuid, $taskGuid);
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