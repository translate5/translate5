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
use ZfExtended_Factory;

/**
 * Each night at 21:00 this action will try to find all relevant jobs for auto-close.
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
        $jobs = $this->findJobs();
        if (empty($jobs)) {
            return;
        }

        $this->triggerConfigArgs = func_get_args();

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
        // set the found overtimed tuas to auto-close
        if (count($idsToAutoClose) > 0) {
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

    private function findJobs(): array
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
            ])
            ->where('tua.deadlineDate < NOW()');

        return $tua->fetchAll($select)->toArray();
    }

    /**
     * Checks if the current cronjob call is after 21:00. In case it is, we are allowed to check the jobs
     */
    private function itsAboutTime(): bool
    {
        $now = new DateTime();
        $now->setTime(21, 0, 0);

        return new DateTime() > $now;
    }
}
