<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_Task as Task;
use editor_Models_TaskConfig;
use editor_Models_TaskUserAssoc as UserJob;
use editor_Models_UserAssocDefault as DefaultUserJob;
use editor_Utils;
use editor_Workflow_Default;
use editor_Workflow_Manager;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\DataProvider\HierarchicalDefaultCoordinatorGroupJobsProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\CreateCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\CreateCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DTO\NewCoordinatorGroupJobDto;
use MittagQI\Translate5\JobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\JobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\CreateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\CreateUserJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\FileTranslation\FileTranslationTypeChecker;
use Throwable;
use Zend_Registry;
use ZfExtended_EventManager;
use ZfExtended_Factory;
use ZfExtended_Logger;

class JobAssignmentDefaults implements ITaskDefaults
{
    public function __construct(
        private readonly ZfExtended_EventManager $events,
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly HierarchicalDefaultCoordinatorGroupJobsProvider $hierarchicalDefaultCoordinatorGroupJobsProvider,
        private readonly CreateCoordinatorGroupJobOperationInterface $createCoordinatorGroupJobOperation,
        private readonly CreateUserJobOperationInterface $createUserJobOperation,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new ZfExtended_EventManager(self::class),
            CoordinatorGroupRepository::create(),
            DefaultUserJobRepository::create(),
            CoordinatorGroupUserRepository::create(),
            CoordinatorGroupJobRepository::create(),
            UserJobRepository::create(),
            HierarchicalDefaultCoordinatorGroupJobsProvider::create(),
            CreateCoordinatorGroupJobOperation::create(),
            CreateUserJobOperation::create(),
            new editor_Workflow_Manager(),
            Zend_Registry::get('logger')->cloneMe('userJob.default.assign'),
        );
    }

    public function canApplyDefaults(Task $task): bool
    {
        return ! FileTranslationTypeChecker::isTranslationTypeTask($task->getTaskType());
    }

    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);

        foreach ($this->hierarchicalDefaultCoordinatorGroupJobsProvider->getHierarchicallyFor($task) as $defaultGroupJob) {
            try {
                $this->assignCoordinatorGroupJob($defaultGroupJob, $taskConfig, $task);
            } catch (Throwable $e) {
                $this->logger->error(
                    'E1677',
                    'Error while assigning default {type} job',
                    [
                        'type' => 'Coordinator group',
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                        'defaultCoordinatorGroupJob' => $defaultGroupJob->getId(),
                        'task' => $task->getTaskGuid(),
                        'trace' => $e->getTraceAsString(),
                    ],
                );
            }
        }

        foreach ($this->defaultUserJobRepository->getDefaultUserJobsForTask($task) as $defaultUserJob) {
            try {
                $this->assignUserJob($defaultUserJob, $taskConfig, $task);
            } catch (Throwable $e) {
                $this->logger->error(
                    'E1677',
                    'Error while assigning default {type} job',
                    [
                        'type' => 'user',
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                        'defaultUserJob' => $defaultUserJob->getId(),
                        'task' => $task->getTaskGuid(),
                        'trace' => $e->getTraceAsString(),
                    ],
                );
            }
        }

        $this->events->trigger('userAssocDefaultsAssigned', $this, [
            'task' => $task,
        ]);
    }

    private function assignCoordinatorGroupJob(
        DefaultCoordinatorGroupJob $defaultCoordinatorGroupJob,
        ?editor_Models_TaskConfig $taskConfig,
        Task $task
    ): void {
        $dataJob = $this->defaultUserJobRepository->get((int) $defaultCoordinatorGroupJob->getDataJobId());

        $workflow = $this->workflowManager->getCached($defaultCoordinatorGroupJob->getWorkflow());

        $workflowDto = new WorkflowDto(
            $workflow->getRoleOfStep($defaultCoordinatorGroupJob->getWorkflowStepName()),
            $workflow->getName(),
            $defaultCoordinatorGroupJob->getWorkflowStepName(),
        );

        $trackingDto = new TrackChangesRightsDto(
            (bool) $dataJob->getTrackchangesShow(),
            (bool) $dataJob->getTrackchangesShowAll(),
            (bool) $dataJob->getTrackchangesAcceptReject(),
        );

        $group = $this->coordinatorGroupRepository->get((int) $defaultCoordinatorGroupJob->getGroupId());

        if (! $group->isTopRankGroup()) {
            try {
                $groupDataJob = $this->getCoordinatorGroupDataJob(
                    (int) $group->getParentId(),
                    $task->getTaskGuid(),
                    $defaultCoordinatorGroupJob->getWorkflow(),
                    $defaultCoordinatorGroupJob->getWorkflowStepName(),
                );
            } catch (NotFoundCoordinatorGroupJobException) {
                // PM or Coordinator haven't assigned default Coordinator group job for parent of this Coordinator group
                return;
            }

            // For Sub Coordinator group jobs Track changes permissions should be subset of parent Coordinator group job
            $trackingDto = $this->computeTrackChangesDto($groupDataJob, $dataJob);
        }

        if ((int) $dataJob->getDeadlineDate() > 0) {
            $name = [
                'runtimeOptions',
                'workflow',
                $defaultCoordinatorGroupJob->getWorkflow(),
                $defaultCoordinatorGroupJob->getWorkflowStepName(),
                'defaultDeadlineDate',
            ];
            $taskConfig->updateInsertConfig($task->getTaskGuid(), implode('.', $name), $dataJob->getDeadlineDate());
        }

        $createDto = new NewCoordinatorGroupJobDto(
            $task->getTaskGuid(),
            $dataJob->getUserGuid(),
            $this->getJobState($task),
            $workflowDto,
            null,
            null,
            $this->getDeadlineDate($dataJob, $task),
            $trackingDto,
        );

        $this->createCoordinatorGroupJobOperation->assignJob($createDto);
    }

    private function assignUserJob(DefaultUserJob $defaultUserJob, ?editor_Models_TaskConfig $taskConfig, Task $task): void
    {
        $groupUser = $this->coordinatorGroupUserRepository->findByUserGuid($defaultUserJob->getUserGuid());
        $groupDataJob = null;

        if (null !== $groupUser) {
            try {
                $groupDataJob = $this->getCoordinatorGroupDataJob(
                    (int) $groupUser->group->getId(),
                    $task->getTaskGuid(),
                    $defaultUserJob->getWorkflow(),
                    $defaultUserJob->getWorkflowStepName(),
                );
            } catch (NotFoundCoordinatorGroupJobException) {
                // PM haven't assigned default Coordinator group job for this workflow and step
                return;
            }
        }

        $workflow = $this->workflowManager->getCached($defaultUserJob->getWorkflow());

        $workflowDto = new WorkflowDto(
            $workflow->getRoleOfStep($defaultUserJob->getWorkflowStepName()),
            $workflow->getName(),
            $defaultUserJob->getWorkflowStepName(),
        );

        if ((float) $defaultUserJob->getDeadlineDate() > 0) {
            $name = [
                'runtimeOptions',
                'workflow',
                $defaultUserJob->getWorkflow(),
                $defaultUserJob->getWorkflowStepName(),
                'defaultDeadlineDate',
            ];
            $taskConfig->updateInsertConfig(
                $task->getTaskGuid(),
                implode('.', $name),
                $defaultUserJob->getDeadlineDate()
            );
        }

        // For Coordinator group user jobs Track changes permissions should be subset of Coordinator group job
        $trackingDto = $this->computeTrackChangesDto($groupDataJob, $defaultUserJob);

        $createDto = new NewUserJobDto(
            $task->getTaskGuid(),
            $defaultUserJob->getUserGuid(),
            $this->getJobState($task),
            $workflowDto,
            TypeEnum::Editor,
            null,
            null,
            $this->getDeadlineDate($defaultUserJob, $task),
            $trackingDto,
        );

        $this->createUserJobOperation->assignJob($createDto);
    }

    private function computeTrackChangesDto(?UserJob $groupDataJob, DefaultUserJob $jobToAssign): TrackChangesRightsDto
    {
        $show = $groupDataJob
            ? $groupDataJob->getTrackchangesShow() && $jobToAssign->getTrackchangesShow()
            : (bool) $jobToAssign->getTrackchangesShow();

        $showAll = $groupDataJob
            ? $groupDataJob->getTrackchangesShowAll() && $jobToAssign->getTrackchangesShowAll()
            : (bool) $jobToAssign->getTrackchangesShowAll();

        $acceptReject = $groupDataJob
            ? $groupDataJob->getTrackchangesAcceptReject() && $jobToAssign->getTrackchangesAcceptReject()
            : (bool) $jobToAssign->getTrackchangesAcceptReject();

        return new TrackChangesRightsDto($show, $showAll, $acceptReject);
    }

    private function getDeadlineDate(DefaultUserJob $defaultUserJob, Task $task): ?string
    {
        // get deadline date config and set it if exist
        $configValue = $task->getConfig(true)
            ->runtimeOptions
            ->workflow
            ->{$defaultUserJob->getWorkflow()}
            ->{$defaultUserJob->getWorkflowStepName()}
            ->defaultDeadlineDate ?? 0;

        if ($configValue > 0) {
            return editor_Utils::addBusinessDays((string) $task->getOrderdate(), $configValue);
        }

        return null;
    }

    private function getJobState(Task $task): string
    {
        return $task->isCompetitive()
            ? editor_Workflow_Default::STATE_UNCONFIRMED
            : editor_Workflow_Default::STATE_OPEN;
    }

    /**
     * @throws NotFoundCoordinatorGroupJobException
     */
    private function getCoordinatorGroupDataJob(
        int $groupId,
        string $taskGuid,
        string $workflow,
        string $workflowStepName
    ): UserJob {
        $groupJob = $this->coordinatorGroupJobRepository->getByCoordinatorGroupIdTaskGuidAndWorkflow(
            $groupId,
            $taskGuid,
            $workflow,
            $workflowStepName,
        );

        return $this->userJobRepository->getDataJobByCoordinatorGroupJob((int) $groupJob->getId());
    }
}
