<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_Task as Task;
use editor_Models_TaskConfig;
use editor_Models_TaskUserAssoc as UserJob;
use editor_Models_UserAssocDefault as DefaultUserJob;
use editor_Utils;
use editor_Workflow_Default;
use editor_Workflow_Manager;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\DataProvider\HierarchicalDefaultLspJobsProvider;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\JobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\JobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\LspJob\Contract\CreateLspJobOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\CreateLspJobOperation;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DTO\NewLspJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\CreateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\CreateUserJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use Throwable;
use Zend_Registry;
use ZfExtended_EventManager;
use ZfExtended_Factory;
use ZfExtended_Logger;

class JobAssignmentDefaults implements ITaskDefaults
{
    public function __construct(
        private readonly ZfExtended_EventManager $events,
        private readonly LspRepositoryInterface $lspRepository,
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly HierarchicalDefaultLspJobsProvider $hierarchicalDefaultLspJobsProvider,
        private readonly CreateLspJobOperationInterface $createLspJobOperation,
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
            LspRepository::create(),
            DefaultUserJobRepository::create(),
            LspUserRepository::create(),
            LspJobRepository::create(),
            UserJobRepository::create(),
            HierarchicalDefaultLspJobsProvider::create(),
            CreateLspJobOperation::create(),
            CreateUserJobOperation::create(),
            new editor_Workflow_Manager(),
            Zend_Registry::get('logger')->cloneMe('userJob.default.assign'),
        );
    }

    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);

        foreach ($this->hierarchicalDefaultLspJobsProvider->getHierarchicallyFor($task) as $defaultLspJob) {
            try {
                $this->assignLspJob($defaultLspJob, $taskConfig, $task);
            } catch (Throwable $e) {
                $this->logger->error(
                    'E1677',
                    'Error while assigning default {type} job',
                    [
                        'type' => 'lsp',
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                        'defaultLspJob' => $defaultLspJob->getId(),
                        'task' => $task->getTaskGuid(),
                        'trace' => $e->getTraceAsString(),
                    ],
                );
            }
        }

        foreach ($this->defaultUserJobRepository->getDefaultLspJobsForTask($task) as $defaultUserJob) {
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

    private function assignLspJob(DefaultLspJob $defaultLspJob, ?editor_Models_TaskConfig $taskConfig, Task $task): void
    {
        $dataJob = $this->defaultUserJobRepository->get((int) $defaultLspJob->getDataJobId());

        $workflow = $this->workflowManager->getCached($defaultLspJob->getWorkflow());

        $workflowDto = new WorkflowDto(
            $workflow->getRoleOfStep($defaultLspJob->getWorkflowStepName()),
            $workflow->getName(),
            $defaultLspJob->getWorkflowStepName(),
        );

        $trackingDto = new TrackChangesRightsDto(
            (bool) $dataJob->getTrackchangesShow(),
            (bool) $dataJob->getTrackchangesShowAll(),
            (bool) $dataJob->getTrackchangesAcceptReject(),
        );

        $lsp = $this->lspRepository->get((int) $defaultLspJob->getLspId());

        if (! $lsp->isDirectLsp()) {
            try {
                $lspDataJob = $this->getLspDataJob(
                    (int) $lsp->getParentId(),
                    $task->getTaskGuid(),
                    $defaultLspJob->getWorkflow(),
                    $defaultLspJob->getWorkflowStepName(),
                );
            } catch (NotFoundLspJobException) {
                // PM or Coordinator haven't assigned default LSP job for parent of this LSP
                return;
            }

            // For Sub LSP jobs Track changes permissions should be subset of parent LSP job
            $trackingDto = $this->computeTrackChangesDto($lspDataJob, $dataJob);
        }

        if ((int) $dataJob->getDeadlineDate() > 0) {
            $name = [
                'runtimeOptions',
                'workflow',
                $defaultLspJob->getWorkflow(),
                $defaultLspJob->getWorkflowStepName(),
                'defaultDeadlineDate',
            ];
            $taskConfig->updateInsertConfig($task->getTaskGuid(), implode('.', $name), $dataJob->getDeadlineDate());
        }

        $createDto = new NewLspJobDto(
            $task->getTaskGuid(),
            $dataJob->getUserGuid(),
            $this->getJobState($task),
            $workflowDto,
            null,
            null,
            $this->getDeadlineDate($dataJob, $task),
            $trackingDto,
        );

        $this->createLspJobOperation->assignJob($createDto);
    }

    private function assignUserJob(DefaultUserJob $defaultUserJob, ?editor_Models_TaskConfig $taskConfig, Task $task): void
    {
        $lspUser = $this->lspUserRepository->findByUserGuid($defaultUserJob->getUserGuid());
        $lspDataJob = null;

        if (null !== $lspUser) {
            try {
                $lspDataJob = $this->getLspDataJob(
                    (int) $lspUser->lsp->getId(),
                    $task->getTaskGuid(),
                    $defaultUserJob->getWorkflow(),
                    $defaultUserJob->getWorkflowStepName(),
                );
            } catch (NotFoundLspJobException) {
                // PM haven't assigned default LSP job for this workflow and step
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

        // For LSP user jobs Track changes permissions should be subset of LSP job
        $trackingDto = $this->computeTrackChangesDto($lspDataJob, $defaultUserJob);

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

    private function computeTrackChangesDto(?UserJob $lspDataJob, DefaultUserJob $jobToAssign): TrackChangesRightsDto
    {
        $show = $lspDataJob
            ? $lspDataJob->getTrackchangesShow() && $jobToAssign->getTrackchangesShow()
            : (bool) $jobToAssign->getTrackchangesShow();

        $showAll = $lspDataJob
            ? $lspDataJob->getTrackchangesShowAll() && $jobToAssign->getTrackchangesShowAll()
            : (bool) $jobToAssign->getTrackchangesShowAll();

        $acceptReject = $lspDataJob
            ? $lspDataJob->getTrackchangesAcceptReject() && $jobToAssign->getTrackchangesAcceptReject()
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
     * @throws NotFoundLspJobException
     */
    private function getLspDataJob(int $lspId, string $taskGuid, string $workflow, string $workflowStepName): UserJob
    {
        $lspJob = $this->lspJobRepository->getByLspIdTaskGuidAndWorkflow(
            $lspId,
            $taskGuid,
            $workflow,
            $workflowStepName,
        );

        return $this->userJobRepository->getDataJobByLspJob((int) $lspJob->getId());
    }
}
