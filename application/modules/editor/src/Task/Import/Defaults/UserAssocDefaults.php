<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_Task as Task;
use editor_Models_TaskConfig;
use editor_Models_TaskUserAssoc;
use editor_Models_UserAssocDefault;
use editor_Utils;
use editor_Workflow_Manager;
use ZfExtended_EventManager;
use ZfExtended_Factory;

class UserAssocDefaults implements ITaskDefaults
{
    private ZfExtended_EventManager $events;

    public function __construct()
    {
        $this->events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [self::class]);
    }

    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $defaults = ZfExtended_Factory::get(editor_Models_UserAssocDefault::class);
        $defaults = $defaults->loadDefaultsForTask($task);
        if (empty($defaults)) {
            return;
        }

        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);

        foreach ($defaults as $assoc) {
            $manager = ZfExtended_Factory::get(editor_Workflow_Manager::class);

            $workflow = $manager->getCached($assoc['workflow']);

            $role = $workflow->getRoleOfStep($assoc['workflowStepName']);

            $model = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
            $model->setWorkflow($assoc['workflow']);

            $model->setWorkflowStepName($assoc['workflowStepName']);
            $model->setRole($role);

            $model->setTaskGuid($task->getTaskGuid());
            $model->setUserGuid($assoc['userGuid']);

            // if there is default deadline date default config, insert it as task specific config.
            if ($assoc['deadlineDate'] !== null && $assoc['deadlineDate'] > 0) {
                $name = [
                    'runtimeOptions',
                    'workflow',
                    $model->getWorkflow(),
                    $model->getWorkflowStepName(),
                    'defaultDeadlineDate',
                ];
                $taskConfig->updateInsertConfig($task->getTaskGuid(), implode('.', $name), $assoc['deadlineDate']);
            }

            // get deadline date config and set it if exist
            $configValue = $task->getConfig(true)
                ->runtimeOptions
                ->workflow
                ->{$model->getWorkflow()}
                ->{$model->getWorkflowStepName()}
                ->defaultDeadlineDate ?? 0;
            if ($configValue > 0) {
                $model->setDeadlineDate(editor_Utils::addBusinessDays((string) $task->getOrderdate(), $configValue));
            }
            // processing some trackchanges properties that can't be parted out to the trackchanges-plugin
            $model->setTrackchangesShow($assoc['trackchangesShow']);
            $model->setTrackchangesShowAll($assoc['trackchangesShowAll']);
            $model->setTrackchangesAcceptReject($assoc['trackchangesAcceptReject']);

            $model->save();
        }

        $this->events->trigger('userAssocDefaultsAssigned', $this, [
            'defaults' => $defaults,
            'task' => $task,
        ]);
    }
}
