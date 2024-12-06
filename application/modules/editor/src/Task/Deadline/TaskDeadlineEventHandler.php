<?php

namespace MittagQI\Translate5\Task\Deadline;

use editor_ModelInstances;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use Editor_TaskuserassocController;
use MittagQI\Translate5\Task\Import\Defaults\JobAssignmentDefaults;
use Zend_EventManager_Event;
use Zend_EventManager_StaticEventManager;
use ZfExtended_Factory;

class TaskDeadlineEventHandler
{
    public function __construct(
        private readonly Zend_EventManager_StaticEventManager $eventManager,
    ) {
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
            $model = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
            $tuas = $model->loadByTaskGuidList([$task->getTaskGuid()]);
            $this->updateDeadlines($tuas, $task, $model);
        }
    }

    public function onAfterTaskUserAssocPostAction(Zend_EventManager_Event $event): void
    {
        // We cannot simply overwrite data sent by post - only if explicitly wanted via trigger-param
        if ($this->isCalculationTriggered($_REQUEST)) {
            /* @var editor_Models_TaskUserAssoc $tua */
            $tua = $event->getParam('entity');
            $task = editor_ModelInstances::taskByGuid($tua->getTaskGuid());
            if ($task->hasValidDeadlineDate()) {
                $tuas = [$tua->toArray()];
                $model = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
                $this->updateDeadlines($tuas, $task, $model);
            }
        }
    }

    private function isCalculationTriggered(array $requestParams): bool
    {
        return array_key_exists('calculateDeadlineDate', $requestParams) &&
            ($requestParams['calculateDeadlineDate'] === '1' || $requestParams['calculateDeadlineDate'] === 'true');
    }

    private function updateDeadlines(
        array $associations,
        editor_Models_Task $task,
        editor_Models_TaskUserAssoc $tuaModel,
    ): void {
        $deadlineCalculator = new DeadlineDateCalculator();
        $tuaUpdater = new TaskUserAssociationUpdater($tuaModel);
        $tuaUpdater->updateDeadlines($associations, $deadlineCalculator->calculateNewDeadlineDate($task));
    }
}
