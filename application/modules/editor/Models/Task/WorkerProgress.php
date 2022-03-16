<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Extends the default worker with task specific additions, basically if the task is on state error, then the worker should set to defunc.
 * All other functionality reacting on the worker run is encapsulated in the behaviour classes
 *
 * The task based worker, is able to load a different behaviour, depending on a non mandatory worker parameter workerBehaviour.
 */
class editor_Models_Task_WorkerProgress {
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_Task_WorkerProgress $progress
     * @param ZfExtended_Models_Worker $workerModel
     */
    public function updateProgress(editor_Models_Task $task, editor_Models_Task_WorkerProgress $progress, ZfExtended_Models_Worker $workerModel)
    {
        //parentId: The context(worker parentId or workerId) represents set of workers connected with same parentId.
        $parentId = $workerModel->getParentId() ?: $workerModel->getId();

        //fire event if progress was called (must be called on new event instance with abstract class as ID)
        /** @var ZfExtended_EventManager $events */
        $events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $events->trigger("updateProgress", __CLASS__, [
            'taskGuid'      =>  $task->getTaskGuid(),
            'progress'      => $progress,
            'context'       => $parentId
        ]);
    }

    /***
     * Calculates progres for given taskGuid for all queued task workers.
     * The total percentage will be distributed based on the worker weight.
     *
     * @param string $taskGuid
     * @param int|null $context : parentId or id of a worker with $taskGuid as taskGuid.
     * Use id only when there is no parentId for the current worker (the current worker is parent of all other queues. ex: editor_Models_Import_Worker)
     * @return array
     */
    public function calculateProgress(string $taskGuid, int $context = null): array {
        /** @var ZfExtended_Models_Worker $worker */
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');

        //if the context is not provided, try to calculate one base on the workers state
        if($context == null){
            //get the context from the current running worker for the task
            //the context is the current running worker parentId or id(when the running worker is master worker like editor_Models_Import_Worker)
            $context = $worker->findWorkerContext($taskGuid);
            if(empty($context)){
                return [];
            }
            $context = $context['parentId'] ?: $context['id'];
        }

        $foundWorkers = $worker->loadByTaskAndContext($taskGuid, $context);
        if(empty($foundWorkers)){
            return [];
        }
        //set the worker weight as internal variable and filter out non task progress affecting workers
        $result = [];
        foreach($foundWorkers as $foundWorker) {
            // ignore if worker does not implement WorkerProgressInterface
            if(!is_subclass_of((string) $foundWorker['worker'], 'editor_Models_Task_WorkerProgressInterface')) {
                continue;
            }
            /** @var editor_Models_Task_WorkerProgressInterface $w */
            $w = ZfExtended_Factory::get($foundWorker['worker']);
            $foundWorker['weight'] = $w->getWeight();
            $result[] = $foundWorker;
        }

        $resultArray = [];
        $resultArray['progress'] = 1;
        $resultArray['workersDone'] = 0;
        $resultArray['workersTotal'] = count($result);
        $resultArray['taskGuid'] = $taskGuid;
        $resultArray['workerRunning'] = '';

        $totalWeight = array_sum(array_column($result, 'weight'));

        foreach ($result as &$single) {
            //adjust the worker weight, base od the current queue list
            $single['weight'] = ($single['weight'] / $totalWeight ) * 100;
            if($single['state'] == $worker::STATE_DONE){
                //collect the finished progress
                $resultArray['progress']+=$single['weight'];
                $resultArray['workersDone']++;
            }
            if($single['state'] == $worker::STATE_RUNNING){
                //calculate the running progress
                //ex: worker weight is 60% of the total import time
                //    the current worker job progress is 50%
                //    add 30% to the total progress
                $resultArray['progress']+=$single['weight'] / (100 / max(1,($single['progress']*100)));
                $resultArray['workerRunning']=$single['worker'];
            }
        }
        //check if all jobs are done
        if($resultArray['workersDone'] == $resultArray['workersTotal']){
            $resultArray['progress']=100;
        }
        $resultArray['progress'] = min(100,round($resultArray['progress'],2));
        return $resultArray;
    }
}
