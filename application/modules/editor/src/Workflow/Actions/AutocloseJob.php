<?php

namespace MittagQI\Translate5\Workflow\Actions;

use DateTime;
use editor_ModelInstances;
use editor_Models_Db_TaskUserAssoc;
use editor_Models_Task;
use editor_Workflow_Actions_Abstract;
use editor_Workflow_Actions_Config;
use editor_Workflow_Default;
use editor_Workflow_Notification;
use Throwable;
use Zend_Registry;
use ZfExtended_Factory;

/**
 * Each day at runtimeOptions.workflow.autoCloseJobsTriggerTime configured time, this action will try to find all relevant jobs for auto-close.
 * User job will be automatically closed if:
 * - The job deadline date passed
 * - The job is not already closed
 * - The job task is in state open
 * - It is enabled by workflow->autoCloseJobs config
 */
class AutocloseJob extends editor_Workflow_Actions_Abstract
{
    private array $triggerConfigArgs;

    public function closeByDeadline(): void
    {
        if (! $this->itsAboutTime()) {
            return;
        }
        $this->triggerConfigArgs = func_get_args();

        $useTaskDeadlineDateAsReference = $this->triggerConfigArgs[0]?->useTaskDeadlineDateAsReference ?? false;

        $jobs = $this->findJobs($useTaskDeadlineDateAsReference);
        if (empty($jobs)) {
            return;
        }

        $idsToAutoClose = [];
        foreach ($jobs as $tuaData) {
            try {
                $task = editor_ModelInstances::taskByGuid($tuaData['taskGuid']);

                $config = $task->getConfig();
                if ($config->runtimeOptions->workflow->autoCloseJobs) {
                    $idsToAutoClose[] = $tuaData['id'];
                    if (! $config->runtimeOptions->workflow->disableNotifications) {
                        $this->notifyAutoclosed($task, $tuaData['userGuid'], $tuaData['workflowStepName']);
                    }
                }
            } catch (Throwable) {
                // this can happen when actions in the instance overlap with cronjob triggered actions
                // no need to "really" log
                error_log('AutocloseJob: cannot find task ' . $tuaData['taskGuid']);
            }
        }

        // set the found overtime tuas to auto-close
        if (! empty($idsToAutoClose)) {
            $tua = ZfExtended_Factory::get(editor_Models_Db_TaskUserAssoc::class);
            $updated = $tua->update([
                'state' => editor_Workflow_Default::STATE_AUTO_FINISH,
            ], [
                'id IN (?)' => $idsToAutoClose,
            ]);

            $this->log->info(
                '',
                sprintf('Number of updated task user assocs: %d', $updated),
                [
                    'taskUserAssocs' => $idsToAutoClose,
                ]
            );
        }
    }

    private function notifyAutoclosed(editor_Models_Task $task, string $userGuid, string $workflowStepName): void
    {
        $config = new editor_Workflow_Actions_Config();
        $config->task = $task;
        $notifier = editor_Workflow_Notification::create();
        $notifier->init($config);
        $notifier->notifyAutoclosed($this->triggerConfigArgs, $userGuid, $workflowStepName);
    }

    private function findJobs(bool $useTaskDeadlineDateAsReference = false): array
    {
        $tua = ZfExtended_Factory::get(editor_Models_Db_TaskUserAssoc::class);
        $select = $tua->select()
            ->from([
                'tua' => $tua->info($tua::NAME),
            ], 'tua.*')
            ->setIntegrityCheck(false)
            ->join([
                't' => 'LEK_task',
            ], 't.taskGuid = tua.taskGuid', [])
            ->where('t.state IN(?)', [
                editor_Models_Task::STATE_OPEN,
            ])
            ->where('tua.state NOT IN (?)', [
                editor_Workflow_Default::STATE_AUTO_FINISH,
                editor_Workflow_Default::STATE_FINISH,
            ]);

        if ($useTaskDeadlineDateAsReference) {
            $select->where('t.deadlineDate < NOW()');
        } else {
            $select->where('tua.deadlineDate < NOW()');
        }

        return $tua->fetchAll($select)->toArray();
    }

    /**
     * Checks if the current cronjob call is after 21:00. In case it is, we are allowed to check the jobs
     */
    private function itsAboutTime(): bool
    {
        $config = Zend_Registry::get('config');
        $triggerTime = $config->runtimeOptions->workflow->autoCloseJobsTriggerTime ?? '21:00';

        $triggerTime = DateTime::createFromFormat('H:i', $triggerTime);

        if (! $triggerTime) {
            return false;
        }

        return new DateTime() > $triggerTime;
    }
}
