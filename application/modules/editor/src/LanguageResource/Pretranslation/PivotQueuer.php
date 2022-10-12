<?php

namespace MittagQI\Translate5\LanguageResource\Pretranslation;


use editor_Models_Task;
use editor_Task_Operation;
use editor_User;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_EventManager;
use ZfExtended_Factory;
use ZfExtended_Models_User;
use ZfExtended_Models_Worker;

/**
 * This will queue pivot pre-translation and batch(if needed) worker
 *
 */
class PivotQueuer
{

    /**
     * @var ZfExtended_EventManager
     */
    protected mixed $events = false;

    public function __construct()
    {
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', ['MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer']);
    }

    /***
     * Queue the match analysis worker
     *
     * @param string $taskGuid
     * @throws Zend_Exception
     */
    public function queuePivotWorker(string $taskGuid) : void {

        /** @var TaskPivotAssociation $pivotAssoc */
        $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $assoc = $pivotAssoc->loadTaskAssociated($taskGuid);

        if(empty($assoc)){
            return;
        }

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        if($task->isImporting()) {
            //on import, we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
        } else {
            // crucial: add a different behaviour for the workers when Cperforming an operation
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
            // this creates the operation start/finish workers
            $parentWorkerId = editor_Task_Operation::create(editor_Task_Operation::PIVOT_PRE_TRANSLATION, $task);
        }

        $user = ZfExtended_Authentication::getInstance()->getUser();

        $workerParameters['userGuid'] = $user?->getUserGuid() ?? ZfExtended_Models_User::SYSTEM_GUID;
        $workerParameters['userName'] = $user?->getUserName() ?? ZfExtended_Models_User::SYSTEM_LOGIN;


        //enable batch query via config
        $workerParameters['batchQuery'] = (boolean) Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
        if($workerParameters['batchQuery']){

            // trigger event before the pivot pre-transaltion worker is queued
            $this->events->trigger('beforePivotPreTranslationQueue', 'MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuerPivotQueuer', [
                'task' => $task,
                'pivotAssociations' => $assoc,
                'parentWorkerId' => $parentWorkerId
            ]);
        }

        /** @var PivotWorker $pivotWorker */
        $pivotWorker = ZfExtended_Factory::get(PivotWorker::class);

        if (!$pivotWorker->init($taskGuid, $workerParameters)) {
            $this->addWarn($task,'Pivot pre-translation Error on worker init(). Worker could not be initialized');
            return;
        }
        $pivotWorker->queue($parentWorkerId, null, false);
    }

    /**
     *
     * @param string $taskGuid
     * @return NULL|int
     */
    private function fetchImportWorkerId(string $taskGuid): ?int
    {
        $parent = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result = $parent->loadByState(ZfExtended_Models_Worker::STATE_PREPARE, 'editor_Models_Import_Worker', $taskGuid);
        if(count($result) > 0){
            return $result[0]['id'];
        }
        return 0;
    }

    /***
     * Log analysis warning
     * @param editor_Models_Task $task
     * @param string $message
     * @param array $extra
     * @throws Zend_Exception
     */
    protected function addWarn(editor_Models_Task $task,string $message,array $extra=[]) {
        $extra['task']=$task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100',$message,$extra);
    }
}