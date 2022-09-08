<?php

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Task;
use editor_Models_Task_AbstractWorker;
use ZfExtended_Factory;
use MittagQI\Translate5\LanguageResource\Pretranslation\Pivot;

/**
 *
 */
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
            $this->task->lock(NOW_ISO, 'pivotpretranslation');

            $params = $this->workerModel->getParameters();

            /** @var Pivot $pivot */
            $pivot = ZfExtended_Factory::get(Pivot::class,[
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