<?php

namespace MittagQI\Translate5\Task\Deadline;

use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use Editor_TaskuserassocController;
use MittagQI\Translate5\Task\Import\Defaults\UserAssocDefaults;
use Zend_EventManager_Event;
use Zend_EventManager_StaticEventManager;
use ZfExtended_Factory;

class TaskDeadlineEventHandler
{
    public function __construct(
        private readonly Zend_EventManager_StaticEventManager $eventManager
    ) {
    }

    public function register(): void
    {
        $this->eventManager->attach(
            UserAssocDefaults::class,
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

        if (empty($task->getDeadlineDate())) {
            return;
        }

        $model = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
        $tuas = $model->loadByTaskGuidList([$task->getTaskGuid()]);

        $this->updateDeadlines($tuas, $task, $model);
    }

    public function onAfterTaskUserAssocPostAction(Zend_EventManager_Event $event): void
    {
        /* @var $task editor_Models_TaskUserAssoc */
        $entity = $event->getParam('entity');
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($entity->getTaskGuid());

        if (empty($task->getDeadlineDate())) {
            return;
        }
        $tuas = [$entity->toArray()];
        $model = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
        $this->updateDeadlines($tuas, $task, $model);
    }

    private function getSubtractDays(editor_Models_Task $task): int
    {
        return $task->getConfig()->runtimeOptions->import->projectDeadline->jobAutocloseSubtractPercent ?? 0;
    }

    private function updateDeadlines(array $associations, editor_Models_Task $task, editor_Models_TaskUserAssoc $tuaModel): void
    {
        $deadlineCalculator = new DeadlineDateCalculator();
        $tuaUpdater = new TaskUserAssociationUpdater($tuaModel);

        $subtractDays = $this->getSubtractDays($task);
        $newDeadline = $deadlineCalculator->calculateNewDeadlineDate($task->getDeadlineDate(), $subtractDays);
        $tuaUpdater->updateDeadlines($associations, $newDeadline);
    }
}
