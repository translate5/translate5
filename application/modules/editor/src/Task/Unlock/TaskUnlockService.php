<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Task\Unlock;

use editor_Models_Loaders_Taskuserassoc;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use editor_Workflow_Default;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskUnlockPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_EventManager;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_Conflict;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_ValidateException;

/**
 * Used to unlock the task when the browser is closed in the UI. From the UI sendBeacon will be sent with required
 * info, and based on this we will unlock the task without any workflow-related triggers or events.
 */
final class TaskUnlockService
{
    private TaskUnlockPermissionAssert $taskActionPermissionAssert;

    private function __construct(
        TaskUnlockPermissionAssert $taskActionPermissionAssert,
    ) {
        $this->taskActionPermissionAssert = $taskActionPermissionAssert;
    }

    public static function create(): self
    {
        return new self(
            TaskUnlockPermissionAssert::create(),
        );
    }

    public function unlock(
        editor_Models_Task $task,
        object $payload,
        User $user,
        ZfExtended_Logger $log,
        ZfExtended_EventManager $events,
        bool $isEditAllTasks,
    ): void {
        return; //FIXME disabled since buggy

        $this->assertTaskIsNotProject($task);

        $data = $this->sanitizeUnlockPayload($payload);

        $this->taskActionPermissionAssert->assertGranted(
            TaskAction::Read,
            $task,
            new PermissionAssertContext($user)
        );

        $this->assertUnlockStateIsOpen($data);

        //task manipulation is allowed additionally on Excel export (for opening read-only, changing user states etc.)
        $task->checkStateAllowsActions([editor_Models_Task::STATE_EXCELEXPORTED]);

        $this->updateUserState($task, $data, $user, $log, $isEditAllTasks);
        $this->closeAndUnlock($task, $data, $user, $log, $events);
    }

    private function assertTaskIsNotProject(editor_Models_Task $task): void
    {
        if (! $task->isProject()) {
            return;
        }

        //project modification is not allowed. This will be changed in future.
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1284' => 'Projects are not editable.',
        ]);

        throw ZfExtended_Models_Entity_Conflict::createResponse('E1284', [
            'Projekte können nicht bearbeitet werden.',
        ]);
    }

    private function sanitizeUnlockPayload(object $data): object
    {
        $payload = (array) $data;

        return (object) [
            // always sanitize to open. Task is unlocked when the user state is open.
            'userState' => isset($payload['userState']) ? editor_Workflow_Default::STATE_OPEN : null,
            'userStatePrevious' => isset($payload['userStatePrevious']) ? (string) $payload['userStatePrevious'] : null,
        ];
    }

    private function assertUnlockStateIsOpen(object $data): void
    {
        if ($data->userState !== editor_Workflow_Default::STATE_OPEN) {
            throw new ZfExtended_ValidateException('Unlock requests must set userState to "open".');
        }
    }

    /**
     * Unlocks the current task if it's a request that closes the task (set state to open, end, finish)
     * removes the task from session
     */
    private function closeAndUnlock(
        editor_Models_Task $task,
        object $data,
        User $user,
        ZfExtended_Logger $log,
        ZfExtended_EventManager $events,
    ): void {
        $hasState = ! empty($data->userState);
        $isEnding = isset($data->state) && $data->state == $task::STATE_END;
        $resetToOpen = $hasState && $data->userState == editor_Workflow_Default::STATE_EDIT && $isEnding;
        if ($resetToOpen) {
            //This state change will be saved at the end of this method.
            $data->userState = editor_Workflow_Default::STATE_OPEN;
        }
        if (! $isEnding && (! $this->isLeavingTaskRequest($data))) {
            return;
        }

        $task->unlockForUser($user->getUserGuid());

        if ($resetToOpen) {
            $this->updateUserState($task, $data, $user, $log, true);
        }

        $events->trigger(
            "afterTaskClose",
            $this,
            [
                'task' => $task,
                'openState' => $data->userState ?? null,
            ]
        );
    }

    protected function updateUserState(
        editor_Models_Task $task,
        object $data,
        User $user,
        ZfExtended_Logger $log,
        bool $isEditAllTasks,
    ): void {
        if (empty($data->userState)) {
            return;
        }

        $userTaskAssoc = new editor_Models_TaskUserAssoc();

        try {
            if ($isEditAllTasks) {
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole(
                    $user->getUserGuid(),
                    $task
                );
            } else {
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTask($user->getUserGuid(), $task);
            }

            $isPmOverride = (bool) $userTaskAssoc->getIsPmOverride();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            if (! $isEditAllTasks) {
                return;
            }

            $userTaskAssoc->setUserGuid($user->getUserGuid());
            $userTaskAssoc->setTaskGuid($task->getTaskGuid());
            $userTaskAssoc->setWorkflow($task->getWorkflow());
            $userTaskAssoc->setWorkflowStepName('');
            $userTaskAssoc->setRole('');
            $userTaskAssoc->setState('');
            $isPmOverride = true;
            $userTaskAssoc->setIsPmOverride($isPmOverride);
        }

        $oldUserTaskAssoc = clone $userTaskAssoc;

        if ($isPmOverride && $isEditAllTasks) {
            $log->info('E1011', 'PM left task');
            $userTaskAssoc->deletePmOverride();

            return;
        }

        $userTaskAssoc->setUsedInternalSessionUniqId(null);
        $userTaskAssoc->setUsedState(null);

        $userTaskAssoc->setState($data->userState);

        $userTaskAssoc->save();

        if ($oldUserTaskAssoc->getState() != $data->userState) {
            $log->info('E1011', 'job status changed from {oldState} to {newState}', [
                'tua' => $oldUserTaskAssoc,
                'oldState' => $oldUserTaskAssoc->getState(),
                'newState' => $data->userState,
            ]);
        }
    }

    /**
     * returns true if PUT Requests opens a task for open or finish
     */
    private function isLeavingTaskRequest(object $data): bool
    {
        if (empty($data->userState)) {
            return false;
        }

        return $data->userState == editor_Workflow_Default::STATE_OPEN || $data->userState == editor_Workflow_Default::STATE_FINISH;
    }
}
