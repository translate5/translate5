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

namespace MittagQI\Translate5\JobAssignment\LspJob\Operation;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\LspJob\ActionAssert\Feasibility\LspJobActionFeasibilityAssert;
use MittagQI\Translate5\JobAssignment\LspJob\Contract\DeleteLspJobOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJob;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\TaskLockService;
use RuntimeException;

class DeleteLspJobOperation implements DeleteLspJobOperationInterface
{
    /**
     * @param ActionFeasibilityAssert<LspJob> $lspJobActionFeasibilityAssert
     */
    public function __construct(
        private readonly LspJobRepository $lspJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ActionFeasibilityAssert $lspJobActionFeasibilityAssert,
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
            LspJobRepository::create(),
            UserJobRepository::create(),
            TaskRepository::create(),
            LspJobActionFeasibilityAssert::create(),
            DeleteUserJobOperation::create(),
            TaskLockService::create(),
        );
    }

    public function delete(LspJob $job): void
    {
        $this->lspJobActionFeasibilityAssert->assertAllowed(Action::Delete, $job);

        $this->forceDelete($job);
    }

    public function forceDelete(LspJob $job): void
    {
        $lock = $this->taskLockService->getLockForTask($job->getTaskGuid());

        if (! $lock->acquire()) {
            throw new RuntimeException('Could not acquire lock for task ' . $job->getTaskGuid());
        }

        try {
            $this->deleteLspJob($job);
        } finally {
            $lock->release();
        }
    }

    public function deleteLspJob(LspJob $job): void
    {
        foreach ($this->lspJobRepository->getSubLspJobsOf((int) $job->getId()) as $subJob) {
            $this->deleteLspJob($subJob);
        }

        foreach ($this->userJobRepository->getUserJobsByLspJob((int) $job->getId()) as $userJob) {
            $this->deleteUserJobAssignmentOperation->deleteUserJob($userJob);
        }

        $dataJob = $this->userJobRepository->getDataJobByLspJob((int) $job->getId());
        $this->userJobRepository->delete((int) $dataJob->getId());

        $this->taskRepository->updateTaskUserCount($job->getTaskGuid());

        $this->lspJobRepository->delete((int) $job->getId());
    }
}
