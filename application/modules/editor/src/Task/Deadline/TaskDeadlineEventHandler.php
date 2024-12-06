<?php

namespace MittagQI\Translate5\Task\Deadline;

use editor_ModelInstances;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use Editor_TaskuserassocController;
use MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate\UserJobDeadlineBatchUpdater;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\Import\Defaults\JobAssignmentDefaults;
use Zend_EventManager_Event;
use Zend_EventManager_StaticEventManager;

class TaskDeadlineEventHandler
{
    public function __construct(
        private readonly Zend_EventManager_StaticEventManager $eventManager,
        private readonly UserJobDeadlineBatchUpdater $taskUserAssociationUpdater,
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_EventManager_StaticEventManager::getInstance(),
            UserJobDeadlineBatchUpdater::create(),
            UserJobRepository::create(),
        );
    }

    public function register(): void
    {
        $this->eventManager->attach(
            JobAssignmentDefaults::class,
            'userAssocDefaultsAssigned',
            [$this, 'onUserAssocDefaultsAssigned']
        );

        $this->eventManager->attach(
            Editor_TaskuserassocController::class,
            'afterPostAction',
            [$this, 'onAfterTaskUserAssocPostAction']
        );
    }

    public function onUserAssocDefaultsAssigned(Zend_EventManager_Event $event): void
    {
        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        // if a task has a deadline-date we automatically propagate it to the jobs - adjusted
        // this always happens when user-assoc-defaults are applied
        if ($this->isCalculationTriggered($_REQUEST) && $task->hasValidDeadlineDate()) {
            $jobIds = $this->userJobRepository->findAllJobsInTask($task->getTaskGuid());
            $this->updateDeadlines($jobIds, $task);
        }
    }

    public function onAfterTaskUserAssocPostAction(Zend_EventManager_Event $event): void
    {
        // We cannot simply overwrite data sent by post - only if explicitly wanted via trigger-param
        if ($this->isCalculationTriggered($_REQUEST)) {
            /* @var editor_Models_TaskUserAssoc $tua */
            $job = $event->getParam('entity');
            $task = editor_ModelInstances::taskByGuid($job->getTaskGuid());

            if ($task->hasValidDeadlineDate()) {
                $this->updateDeadlines([$job->getId()], $task);
            }
        }
    }

    private function isCalculationTriggered(array $requestParams): bool
    {
        return array_key_exists('calculateDeadlineDate', $requestParams)
            && ($requestParams['calculateDeadlineDate'] === '1' || $requestParams['calculateDeadlineDate'] === 'true');
    }

    private function updateDeadlines(
        array $associations,
        editor_Models_Task $task,
    ): void {
        $deadlineCalculator = new DeadlineDateCalculator();
        $this->taskUserAssociationUpdater->updateDeadlines(
            $associations,
            $deadlineCalculator->calculateNewDeadlineDate($task)
        );
    }
}
