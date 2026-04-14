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

use editor_Workflow_Default;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

class WorkflowStepCalculator
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
        );
    }

    public function getNextStep(editor_Workflow_Default $workflow, string $taskGuid, string $step): ?string
    {
        $associatedSteps = $this->userJobRepository->getWorkflowStepNamesOfJobsInTask($taskGuid);

        if (empty($associatedSteps)) {
            return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
        }

        $stepChain = array_values($workflow->getStepChain());

        $stepCount = count($stepChain);
        $position = array_search($step, $stepChain, true);

        // if the current step is not found in the chain or
        // if there are no jobs the workflow should be ended then
        // (normally we never reach here since to change the workflow at least one job is needed)
        if ($position === false) {
            return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
        }

        //we want the position of the next step, not the current one:
        $position++;

        //loop over all steps after the current one
        for (; $position < $stepCount; $position++) {
            if (in_array($stepChain[$position], $associatedSteps, true)) {
                //the first one with associated users is returned
                return $stepChain[$position];
            }
        }

        return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
    }

    /**
     * Returns next step in stepChain, or STEP_WORKFLOW_ENDED if for nextStep no users are associated
     */
    public function getValidTaskWorkflowStep(editor_Workflow_Default $workflow, string $taskGuid): string
    {
        $matchingSteps = $this->getMatchingSteps($workflow, $taskGuid);

        $task = $this->taskRepository->getByGuid($taskGuid);

        if (empty($matchingSteps)) {
            if ($this->userJobRepository->taskHasNotFinishedJob($taskGuid, $workflow->getName())) {
                // if there is no workflow, then we can not change the step, but also do not want to end the workflow
                return $task->getWorkflowStepName();
            }

            return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
        }

        // if the current step is one of the possible steps for the tua configuration
        // then everything is OK
        if (in_array($task->getWorkflowStepName(), $matchingSteps, true)) {
            return $task->getWorkflowStepName();
        }

        return reset($matchingSteps);
    }

    private function getMatchingSteps(editor_Workflow_Default $workflow, string $taskGuid): array
    {
        $jobsCount = 0;
        $pmOverrideCount = 0;
        $matchingSteps = [];
        $jobsData = [];

        foreach ($this->coordinatorGroupJobRepository->getTaskCoordinatorGroupDataJobs($taskGuid) as $dataJob) {
            $jobsCount++;

            $jobsData[] = [
                'state' => $dataJob->getState(),
                'workflowStepName' => $dataJob->getWorkflowStepName(),
            ];
        }

        foreach ($this->userJobRepository->getTaskJobs($taskGuid) as $userJob) {
            $jobsCount++;

            if ($userJob->getIsPmOverride()) {
                $pmOverrideCount++;
            }

            $jobsData[] = [
                'state' => $userJob->getState(),
                'workflowStepName' => $userJob->getWorkflowStepName(),
            ];
        }

        if (0 === $jobsCount || $jobsCount === $pmOverrideCount) {
            return [
                editor_Workflow_Default::STEP_NO_WORKFLOW,
            ];
        }

        foreach ($workflow->getValidStates() as $step => $roleStates) {
            if (! $this->areJobsSubset($roleStates, $step, $jobsData)) {
                continue;
            }

            $matchingSteps[] = $step;
        }

        return $matchingSteps;
    }

    /**
     * Checks if the given Jobs are a subset of the list be compared
     */
    protected function areJobsSubset(array $toCompare, string $currentStep, array $jobs): bool
    {
        $hasStepToCurrentTaskStep = false;
        foreach ($jobs as $job) {
            if (empty($toCompare[$job['workflowStepName']])) {
                // if a job's step does not exist in the compare list, we just ignore that job
                continue;
            }

            if (! in_array($job['state'], $toCompare[$job['workflowStepName']], true)) {
                // if the jobs step exist, but its state is not configured, then the configuration is invalid for that step
                return false;
            }

            $hasStepToCurrentTaskStep = $hasStepToCurrentTaskStep || ($currentStep === $job['workflowStepName']);
        }

        //we can only return true, if the Tuas contain at least one role belonging to the currentStep,
        // in other words we can not reset the task to reviewing, if we do not have a reviewer
        return $hasStepToCurrentTaskStep;
    }
}
