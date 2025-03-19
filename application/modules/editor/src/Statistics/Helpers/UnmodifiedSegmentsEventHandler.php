<?php

namespace MittagQI\Translate5\Statistics\Helpers;

use editor_Models_Task;
use editor_Workflow_Default;
use editor_Workflow_Default_StepRecalculation;
use Zend_EventManager_Event;
use Zend_EventManager_StaticEventManager;

class UnmodifiedSegmentsEventHandler
{
    public function __construct(
        private readonly Zend_EventManager_StaticEventManager $eventManager,
    ) {
    }

    public function register(): void
    {
        // triggered when task is set to "end" (not when workflow is finshed)
        $this->eventManager->attach(
            editor_Workflow_Default::class,
            'doEnd',
            [$this, 'onTaskEnd']
        );

        $this->eventManager->attach(
            editor_Workflow_Default_StepRecalculation::class,
            'onRecalculate',
            [$this, 'onWorkflowStepRecalculate']
        );
    }

    public function onWorkflowStepRecalculate(Zend_EventManager_Event $event): void
    {
        /* @var $task editor_Models_Task */
        $task = $event->getParam('task');
        if ($task->getWorkflowStepName() === editor_Workflow_Default::STEP_NO_WORKFLOW) {
            AggregateUnmodifiedSegments::aggregate(
                $task,
                editor_Workflow_Default::STEP_NO_WORKFLOW,
                $task->getPmGuid()
            );
        }
    }

    public function onTaskEnd(Zend_EventManager_Event $event): void
    {
        /* @var $task editor_Models_Task */
        $task = $event->getParam('newTask');
        AggregateUnmodifiedSegments::aggregate(
            $task,
            editor_Workflow_Default::STEP_WORKFLOW_ENDED,
            $task->getPmGuid()
        );
    }
}
