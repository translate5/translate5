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

namespace MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\ActionAssert\Feasibility\CoordinatorGroupJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\DeleteCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLockService;
use RuntimeException;

class DeleteCoordinatorGroupJobOperation implements DeleteCoordinatorGroupJobOperationInterface
{
    /**
     * @param ActionFeasibilityAssertInterface<CoordinatorGroupJob> $coordinatorGroupJobActionFeasibilityAssert
     */
    public function __construct(
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ActionFeasibilityAssertInterface $coordinatorGroupJobActionFeasibilityAssert,
        private readonly DeleteUserJobOperation $deleteUserJobAssignmentOperation,
        private readonly TaskLockService $taskLockService,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupJobRepository::create(),
            UserJobRepository::create(),
            TaskRepository::create(),
            CoordinatorGroupJobActionFeasibilityAssert::create(),
            DeleteUserJobOperation::create(),
            TaskLockService::create(),
        );
    }

    public function delete(CoordinatorGroupJob $job): void
    {
        $this->coordinatorGroupJobActionFeasibilityAssert->assertAllowed(Action::Delete, $job);

        $this->forceDelete($job);
    }

    public function forceDelete(CoordinatorGroupJob $job): void
    {
        $lock = $this->taskLockService->getLockForTask($job->getTaskGuid());

        if (! $lock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $job->getTaskGuid());
        }

        try {
            $this->deleteCoordinatorGroupJob($job);
        } finally {
            $lock->release();
        }
    }

    public function deleteCoordinatorGroupJob(CoordinatorGroupJob $job): void
    {
        foreach ($this->coordinatorGroupJobRepository->getSubGroupJobsOf((int) $job->getId()) as $subJob) {
            $this->deleteCoordinatorGroupJob($subJob);
        }

        foreach ($this->userJobRepository->getUserJobsByCoordinatorGroupJob((int) $job->getId()) as $userJob) {
            $this->deleteUserJobAssignmentOperation->deleteUserJob($userJob);
        }

        $dataJob = $this->userJobRepository->getDataJobByCoordinatorGroupJob((int) $job->getId());
        $this->userJobRepository->delete((int) $dataJob->getId());

        $this->taskRepository->updateTaskUserCount($job->getTaskGuid());

        $this->coordinatorGroupJobRepository->delete((int) $job->getId());
    }
}
