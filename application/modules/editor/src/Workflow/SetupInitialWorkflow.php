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

namespace MittagQI\Translate5\Workflow;

use MittagQI\Translate5\JobAssignment\Operation\DeleteJobAssignmentOperation;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

class SetupInitialWorkflow
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly DeleteJobAssignmentOperation $deleteJobOperation,
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            DeleteJobAssignmentOperation::create(),
            UserJobRepository::create(),
        );
    }

    /**
     * - cleans the not needed automatically added jobs from the job list
     * - sets task's workflow step depending on associated jobs
     * - sets initial states depending on the workflow step of the task and task usage mode
     * @throws \ReflectionException
     */
    public function setup(\editor_Workflow_Default $workflow, \editor_Models_Task $task): void
    {
        $jobs = $this->userJobRepository->getAllJobsInTask($task->getTaskGuid());

        $usedJobs = [];
        $usedSteps = [];
        //delete jobs created by default which are not belonging to the tasks workflow and collect used steps
        foreach ($jobs as $job) {
            if ($job->getWorkflow() !== $task->getWorkflow()) {
                $this->deleteJobOperation->forceDelete((int) $job->getId());

                continue;
            }

            // if the tua step name is not in the workflow chain, ignore the job collection
            // workflow step names which are not part of the workflow chain should not be used for calculation the
            // initial workflow step
            if (! in_array($job->getWorkflowStepName(), $workflow->getStepChain(), true)) {
                continue;
            }

            $usedJobs[] = $job;
            $usedSteps[] = $job->getWorkflowStepName();
        }

        $this->taskRepository->updateTaskUserCount($task->getTaskGuid());

        if (empty($usedJobs)) {
            return;
        }

        //sort the found steps regarding the step chain
        $usedSteps = array_unique($usedSteps);
        usort($usedSteps, [$workflow, 'compareSteps']);

        // we set the tasks workflow step to the first found step of the assigned users,
        // respecting the order of the step chain
        $currentStep = array_shift($usedSteps);
        $task->updateWorkflowStep($currentStep, false);

        $isComp = $task->getUsageMode() === $task::USAGE_MODE_COMPETITIVE;

        foreach ($usedJobs as $job) {
            //current step jobs are open
            if ($currentStep === $job->getWorkflowStepName()) {
                $state = $isComp ? $workflow::STATE_UNCONFIRMED : $workflow::STATE_OPEN;
            } else {
                //all other steps are coming later in the chain, so they are waiting
                $state = $workflow::STATE_WAITING;
            }

            $job->setState($state);

            $this->userJobRepository->save($job);
        }
    }
}
