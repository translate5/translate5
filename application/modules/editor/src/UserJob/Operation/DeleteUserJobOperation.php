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

namespace MittagQI\Translate5\UserJob\Operation;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLock;
use MittagQI\Translate5\Task\TaskLockService;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\UserJob\Operation\DTO\UserJobToDelete;
use RuntimeException;
use Zend_Registry;
use ZfExtended_Logger;

class DeleteUserJobOperation implements DeleteUserJobOperationInterface
{
    /**
     * @param ActionFeasibilityAssertInterface<UserJob> $feasibilityAssert
     */
    public function __construct(
        private readonly ActionFeasibilityAssertInterface $feasibilityAssert,
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ZfExtended_Logger $logger,
        private readonly TaskLockService $taskLockService,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobActionFeasibilityAssert::create(),
            UserJobRepository::create(),
            TaskRepository::create(),
            Zend_Registry::get('logger')->cloneMe('userJob.delete'),
            TaskLockService::create(),
        );
    }

    public function delete(UserJobToDelete $toDelete): void
    {
        if ($toDelete->job->isLspJob()) {
            throw new RuntimeException('Use DeleteLspJobAssignmentOperationInterface::delete for LSP jobs');
        }

        $taskLock = $this->acquireLock($toDelete);

        try {
            $this->feasibilityAssert->assertAllowed(Action::Delete, $toDelete->job);

            $this->deleteUserJob($toDelete->job);
        } finally {
            $taskLock->release();
        }
    }

    public function forceDelete(UserJobToDelete $toDelete): void
    {
        if ($toDelete->job->isLspJob()) {
            throw new RuntimeException('Use DeleteLspJobAssignmentOperationInterface::forceDelete for LSP jobs');
        }

        $taskLock = $this->acquireLock($toDelete);

        try {
            $this->deleteUserJob($toDelete->job);
        } finally {
            $taskLock->release();
        }
    }

    private function deleteUserJob(UserJob $job): void
    {
        $task = $this->taskRepository->getByGuid($job->getTaskGuid());

        if ($task->isLocked($task->getTaskGuid(), $job->getUserGuid())) {
            $this->taskLockService->unlockTask($task);
        }

        $jobData = $job->getSanitizedEntityForLog();

        $this->userJobRepository->delete((int) $job->getId());

        $this->taskRepository->updateTaskUserCount($task->getTaskGuid());

        $this->logger->info('E1012', 'job deleted', [
            'task' => $task,
            'job' => $jobData,
        ]);
    }

    private function acquireLock(UserJobToDelete $toDelete): TaskLock
    {
        $taskLock = $toDelete->taskLock ?: $this->taskLockService->getLockForTask($toDelete->job->getTaskGuid());

        if (! $taskLock->isAcquired() && ! $taskLock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $toDelete->job->getTaskGuid());
        }

        return $taskLock;
    }
}
