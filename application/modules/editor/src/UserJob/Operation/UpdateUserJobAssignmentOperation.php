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
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\Task\Validator\BeforeFinishStateTaskValidator;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\UserJob\Contract\UserJobUpdateOperationInterface;
use MittagQI\Translate5\UserJob\Operation\DTO\UpdateUserJobDto;
use Zend_Registry;
use ZfExtended_Logger;

class UpdateUserJobAssignmentOperation implements UserJobUpdateOperationInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly TaskRepository $taskRepository,
        private readonly UserJobActionFeasibilityAssert $feasibilityAssert,
        private readonly ZfExtended_Logger $logger,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly BeforeFinishStateTaskValidator $beforeFinishStateTaskValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            new TaskRepository(),
            UserJobActionFeasibilityAssert::create(),
            Zend_Registry::get('logger')->cloneMe('userJob.update'),
            new editor_Workflow_Manager(),
            BeforeFinishStateTaskValidator::create(),
        );
    }

    /**
     * @throws FeasibilityExceptionInterface
     * @throws TaskHasCriticalQualityErrorsException
     */
    public function update(UserJob $job, UpdateUserJobDto $dto): void
    {
        $this->feasibilityAssert->assertAllowed(Action::UPDATE, $job);

        $oldJob = clone $job;

        if (null !== $dto->state) {
            $job->setState($dto->state);
        }

        if (null !== $dto->workflow) {
            $job->setRole($dto->workflow->role);
            $job->setWorkflow($dto->workflow->workflow);
            $job->setWorkflowStepName($dto->workflow->workflowStepName);
        }

        if (null !== $dto->segmentRange) {
            $job->setSegmentrange($dto->segmentRange);
        }

        if (null !== $dto->deadlineDate) {
            $job->setDeadlineDate($dto->deadlineDate);
        }

        if (null !== $dto->canSeeTrackChangesOfPrevSteps) {
            $job->setTrackchangesShow((int)$dto->canSeeTrackChangesOfPrevSteps);
        }

        if (null !== $dto->canSeeAllTrackChanges) {
            $job->setTrackchangesShowAll((int)$dto->canSeeAllTrackChanges);
        }

        if (null !== $dto->canAcceptOrRejectTrackChanges) {
            $job->setTrackchangesAcceptReject((int) $dto->canAcceptOrRejectTrackChanges);
        }

        $job->validate();

        $task = $this->taskRepository->getByGuid($job->getTaskGuid());
        $workflow = $this->workflowManager->getActiveByTask($task);

        $workflow->hookin()->doWithUserAssoc(
            $oldJob,
            $job,
            function (?string $state) use ($job, $task) {
                if (null !== $state) {
                    $this->beforeFinishStateTaskValidator->validateForTaskFinish($state, $job, $task);
                }

                $this->userJobRepository->save($job);
            }
        );

        if (null !== $dto->state && $oldJob->getState() !== $dto->state) {
            $this->logger->info(
                'E1012',
                'job status changed from {oldState} to {newState}',
                [
                    'tua' => $job->getSanitizedEntityForLog(),
                    'oldState' => $job->getState(),
                    'newState' => $dto->state,
                ]
            );
        }
    }
}
