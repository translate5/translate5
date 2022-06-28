<?php

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Task;
use editor_Models_Task_AbstractWorker;
use ZfExtended_Factory;

class PivotWorker extends editor_Models_Task_AbstractWorker
{

    /***
     * @param $parameters
     * @return bool
     */
    protected function validateParameters($parameters = array()): bool
    {
        $neededEntries = ['userGuid', 'userName'];
        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);
        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    protected function work()
    {
        try {
            // lock the task dedicated for pivot pre-translation
            if ($this->task->lock(NOW_ISO, 'pivotpretranslation')) {
                // else check if we are in import, then no separate lock is needed. Therefore if we are not in import this is an error
            } else if ($this->task->getState() != editor_Models_Task::STATE_IMPORT) {
                $this->log->error('E1397', 'Pivot pre-translation: task can not be locked for pivot pre-translation.', [
                    'task' => $this->task
                ]);
                return false;
            }

            $params = $this->workerModel->getParameters();

            /** @var Pivot $pivot */
            $pivot = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\Pretranslation\Pivot',[
                $this->task
            ]);
            $pivot->setUserGuid($params['userGuid']);
            $pivot->setUserName($params['userName']);
            $pivot->pretranslate();
            $this->task->unlock();
            return true;
        }catch (\Throwable $e){

            // when error happens unlock the task
            $this->task->unlock();
            $this->log->error('E1100', 'Pivot pre-translation cannot be run. See additional errors for more Information.', [
                'task' => $this->task,
            ]);
            $this->log->exception($e, [
                'extra' => [
                    'task' => $this->task
                ],
            ]);
            return false;

        }
    }
}