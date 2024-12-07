<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_Task as Task;
use editor_Models_TaskConfig;
use editor_Models_UserAssocDefault as DefaultUserJob;
use editor_Utils;
use editor_Workflow_Default;
use editor_Workflow_Manager;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\JobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\JobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\LspJob\Contract\CreateLspJobOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\CreateLspJobOperation;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DTO\NewLspJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\CreateUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\CreateUserJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use Throwable;
use Zend_Registry;
use ZfExtended_EventManager;
use ZfExtended_Factory;
use ZfExtended_Logger;

class JobAssignmentDefaults implements ITaskDefaults
{
    public function __construct(
        private readonly ZfExtended_EventManager $events,
        private readonly DefaultLspJobRepository $defaultLspJobRepository,
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly CreateLspJobOperationInterface $createLspJobOperation,
        private readonly CreateUserJobOperationInterface $createUserJobOperation,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new ZfExtended_EventManager(self::class),
            DefaultLspJobRepository::create(),
            DefaultUserJobRepository::create(),
            CreateLspJobOperation::create(),
            CreateUserJobOperation::create(),
            new editor_Workflow_Manager(),
            Zend_Registry::get('logger')->cloneMe('userJob.delete'),
        );
    }

    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);

        foreach ($this->defaultLspJobRepository->getDefaultLspJobsForTask($task) as $defaultLspJob) {
            try {
                $this->assignLspJob($defaultLspJob, $taskConfig, $task);
            } catch (Throwable $e) {
                $this->logger->error(
                    'E1677',
                    'Error while assigning default {type} job',
                    [
                        'type' => 'lsp',
                        'exception' => $e::class,
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

    public function assignLspJob(DefaultLspJob $defaultLspJob, ?editor_Models_TaskConfig $taskConfig, Task $task): void
    {
        $dataJob = $this->defaultUserJobRepository->get((int) $defaultLspJob->getDataJobId());

        $workflow = $this->workflowManager->getCached($defaultLspJob->getWorkflow());

        $workflowDto = new WorkflowDto(
            $workflow->getRoleOfStep($defaultLspJob->getWorkflowStepName()),
            $workflow->getName(),
            $defaultLspJob->getWorkflowStepName(),
        );

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

        $trackingDto = new TrackChangesRightsDto(
            (bool) $dataJob->getTrackchangesShow(),
            (bool) $dataJob->getTrackchangesShowAll(),
            (bool) $dataJob->getTrackchangesAcceptReject(),
        );

        $createDto = new NewLspJobDto(
            $task->getTaskGuid(),
            $dataJob->getUserGuid(),
            editor_Workflow_Default::STATE_OPEN,
            $workflowDto,
            null,
            null,
            $this->getDeadlineDate($dataJob, $task),
            $trackingDto,
        );

        $this->createLspJobOperation->assignJob($createDto);
    }

    public function assignUserJob(DefaultUserJob $defaultUserJob, ?editor_Models_TaskConfig $taskConfig, Task $task): void
    {
        $workflow = $this->workflowManager->getCached($defaultUserJob->getWorkflow());

        $workflowDto = new WorkflowDto(
            $workflow->getRoleOfStep($defaultUserJob->getWorkflowStepName()),
            $workflow->getName(),
            $defaultUserJob->getWorkflowStepName(),
        );

        if ((int) $defaultUserJob->getDeadlineDate() > 0) {
            $name = [
                'runtimeOptions',
                'workflow',
                $defaultUserJob->getWorkflow(),
                $defaultUserJob->getWorkflowStepName(),
                'defaultDeadlineDate',
            ];
            $taskConfig->updateInsertConfig(
                $task->getTaskGuid(),
                implode('.', $name), $defaultUserJob->getDeadlineDate()
            );
        }

        $trackingDto = new TrackChangesRightsDto(
            (bool) $defaultUserJob->getTrackchangesShow(),
            (bool) $defaultUserJob->getTrackchangesShowAll(),
            (bool) $defaultUserJob->getTrackchangesAcceptReject(),
        );

        $createDto = new NewUserJobDto(
            $task->getTaskGuid(),
            $defaultUserJob->getUserGuid(),
            editor_Workflow_Default::STATE_OPEN,
            $workflowDto,
            TypeEnum::Editor,
            null,
            null,
            $this->getDeadlineDate($defaultUserJob, $task),
            $trackingDto,
        );

        $this->createUserJobOperation->assignJob($createDto);
    }

    public function getDeadlineDate(DefaultUserJob $defaultUserJob, Task $task): ?string
    {
        // get deadline date config and set it if exist
        $configValue = $task->getConfig(true)
            ->runtimeOptions
            ->workflow
            ->{$defaultUserJob->getWorkflow()}
            ->{$defaultUserJob->getWorkflowStepName()}
            ->defaultDeadlineDate ?? 0;

        if ($configValue > 0) {
            return editor_Utils::addBusinessDays((string)$task->getOrderdate(), $configValue);
        }

        return null;
    }
}
