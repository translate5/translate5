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

use MittagQI\Translate5\JobAssignment\Operation\DeleteJobAssignmentOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

/**
 * Workflow Step recalculation
 */
class editor_Workflow_Default_StepRecalculation
{
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;

    private readonly TaskRepository $taskRepository;

    private readonly DeleteJobAssignmentOperation $deleteJobOperation;

    private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository;

    private readonly UserJobRepository $userJobRepository;

    protected $nextStepWasSet = [];

    public function __construct(editor_Workflow_Default $workflow)
    {
        $this->workflow = $workflow;
        $this->taskRepository = TaskRepository::create();
        $this->deleteJobOperation = DeleteJobAssignmentOperation::create();
        $this->coordinatorGroupJobRepository = CoordinatorGroupJobRepository::create();
        $this->userJobRepository = UserJobRepository::create();
    }

    /**
     * Adds the next calculated step for a task (to be used in recalculation here)
     */
    public function addNextStepSet(string $taskGuid, string $newStep)
    {
        $this->nextStepWasSet[$taskGuid] = $newStep;
    }

    /**
     * recalculates the workflow step by the given task user assoc combinations
     * If the combination of roles and states are pointing to an specific workflow step, this step is used
     * If the states and roles does not match any valid combination, no step is changed.
     */
    public function recalculateWorkflowStep(string $taskGuid)
    {
        //if the step was recalculated due setNextStep in internal workflow calculations,
        // we may not recalculate it here again!
        if (! empty($this->nextStepWasSet[$taskGuid])) {
            $this->sendFrontEndNotice($this->nextStepWasSet[$taskGuid]);

            return;
        }

        $task = $this->taskRepository->getByGuid($taskGuid);

        $matchingSteps = $this->getMatchingSteps($taskGuid);

        // if the current step is one of the possible steps for the tua configuration
        // then everything is OK,
        // or if no valid configuration is found, then we also could not change the step
        if (empty($matchingSteps) || in_array($task->getWorkflowStepName(), $matchingSteps)) {
            return;
        }

        //set the first found valid step to the current workflow step
        $step = reset($matchingSteps);
        $this->workflow->getLogger($task)->info('E1013', 'recalculate workflow to step {step} ', [
            'step' => $step,
        ]);

        $events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [get_class($this)]);
        $events->trigger('onRecalculate', $this, [
            'task' => $task,
            'step' => $step,
        ]);

        $task->updateWorkflowStep($step, false);
        //set $step as new workflow step if different to before!
        $this->sendFrontEndNotice($step);
    }

    private function getMatchingSteps(string $taskGuid): array
    {
        $jobsCount = 0;
        $pmOverrideCount = 0;
        $matchingSteps = [];
        $jobsData = [];

        foreach ($this->coordinatorGroupJobRepository->getTaskCoordinatorGroupJobs($taskGuid) as $groupJob) {
            $jobsCount++;

            $dataJob = $this->userJobRepository->getDataJobByCoordinatorGroupJob((int) $groupJob->getId());
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

        foreach ($this->workflow->getValidStates() as $step => $roleStates) {
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
            if (! in_array($job['state'], $toCompare[$job['workflowStepName']])) {
                // if the jobs step exist, but its state is not configured, then the configuration is invalid for that step
                return false;
            }
            $hasStepToCurrentTaskStep = $hasStepToCurrentTaskStep || ($currentStep == $job['workflowStepName']);
        }

        //we can only return true, if the Tuas contain at least one role belonging to the currentStep,
        // in other words we can not reset the task to reviewing, if we do not have a reviewer
        return $hasStepToCurrentTaskStep;
    }

    protected function sendFrontEndNotice(string $step)
    {
        $msg = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        /* @var $msg ZfExtended_Models_Messages */
        $labels = $this->workflow->getLabels();
        $steps = $this->workflow->getSteps();
        $step = $labels[array_search($step, $steps)];
        $msg->addNotice('Der Workflow Schritt der Aufgabe wurde zu "{0}" geändert!', 'core', null, $step);
    }

    public function initWorkflowStep(editor_Models_Task $task, $stepName)
    {
        $this->workflow->getLogger($task)->info('E1013', 'workflow init step to "{step}"', [
            'step' => $stepName,
        ]);
        $task->updateWorkflowStep($stepName, false);
    }

    /**
     * - cleans the not needed automatically added jobs from the job list
     * - sets task's workflow step depending on associated jobs
     * - sets initial states depending on the workflow step of the task and task usage mode
     * @throws ReflectionException
     */
    public function setupInitialWorkflow(editor_Models_Task $task): void
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
            if (! in_array($job->getWorkflowStepName(), $this->workflow->getStepChain())) {
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
        usort($usedSteps, [$this->workflow, 'compareSteps']);

        // we set the tasks workflow step to the first found step of the assigned users,
        // respecting the order of the step chain
        $currentStep = array_shift($usedSteps);
        $task->updateWorkflowStep($currentStep, false);

        $isComp = $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE;

        foreach ($usedJobs as $job) {
            //current step jobs are open
            if ($currentStep === $job->getWorkflowStepName()) {
                $state = $isComp ? $this->workflow::STATE_UNCONFIRMED : $this->workflow::STATE_OPEN;
            } else {
                //all other steps are coming later in the chain, so they are waiting
                $state = $this->workflow::STATE_WAITING;
            }

            $job->setState($state);

            $this->userJobRepository->save($job);
        }
    }
}
