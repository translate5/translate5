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

namespace MittagQI\Translate5\JobAssignment\UserJob\Operation;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Event\UserJobDeletedEvent;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLock;
use MittagQI\Translate5\Task\TaskLockService;
use Psr\EventDispatcher\EventDispatcherInterface;
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
        private readonly EventDispatcherInterface $eventDispatcher,
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
            EventDispatcher::create(),
        );
    }

    public function delete(UserJob $job): void
    {
        if ($job->isCoordinatorGroupJob()) {
            throw new RuntimeException('Use DeleteLspJobAssignmentOperationInterface::delete for Coordinator group jobs');
        }

        $taskLock = $this->acquireLock($job->getTaskGuid());

        try {
            $this->feasibilityAssert->assertAllowed(Action::Delete, $job);

            $this->deleteUserJob($job);
        } finally {
            $taskLock->release();
        }
    }

    public function forceDelete(UserJob $job): void
    {
        if ($job->isCoordinatorGroupJob()) {
            throw new RuntimeException('Use DeleteLspJobOperationInterface::forceDelete for Coordinator group jobs');
        }

        $taskLock = $this->acquireLock($job->getTaskGuid());

        try {
            $this->deleteUserJob($job);
        } finally {
            $taskLock->release();
        }
    }

    public function deleteUserJob(UserJob $job): void
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

        $this->eventDispatcher->dispatch(new UserJobDeletedEvent($job));
    }

    private function acquireLock(string $taskGuid): TaskLock
    {
        $taskLock = $this->taskLockService->getLockForTask($taskGuid);

        if (! $taskLock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $taskGuid);
        }

        return $taskLock;
    }
}
