<?php

namespace MittagQI\Translate5\Workflow\Actions;

use DateTime;
use editor_ModelInstances;
use editor_Models_Db_TaskUserAssoc;
use editor_Models_Task;
use editor_Workflow_Actions_Abstract;
use editor_Workflow_Default;
use Throwable;
use ZfExtended_Factory;

class AutocloseJob extends editor_Workflow_Actions_Abstract
{
    public function closeByDeadline(): void
    {
        if (! $this->itsAboutTime()) {
            return;
        }
        $jobs = $this->findJobs();
        if (empty($jobs)) {
            return;
        }

        $idsToAutoClose = [];
        foreach ($jobs as $tua) {
            try {
                $task = editor_ModelInstances::taskByGuid($tua->taskGuid);
                if ($task->hasDeadlineDate() && $task->getConfig()->runtimeOptions->workflow->autoCloseJobs) {
                    $idsToAutoClose[] = $tua->id;
                }
            } catch (Throwable $e) {
                // this can happen when actions in the instance overlap with cronjob triggered actions
                // no need to "really" log
                error_log('AutocloseJob: cannot find task ' . $tua->taskGuid);
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
            ->where('t.deadlineDate IS NOT NULL')
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
