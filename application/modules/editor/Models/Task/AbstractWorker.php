<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
abstract class editor_Models_Task_AbstractWorker extends ZfExtended_Worker_Abstract {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * By default we use the import worker behaviour here (all actions to handle worker in task import context are triggered)
     * @var string
     */
    protected $behaviourClass = 'editor_Models_Import_Worker_Behaviour';
    
    /**
     * @var editor_Models_Import_Worker_Behaviour
     */
    protected $behaviour;
    
    public function init($taskGuid = NULL, $parameters = array()) {
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $ class */
        $this->task->loadByTaskGuid($taskGuid);
        $this->initBehaviour($parameters['workerBehaviour'] ?? null);
        if(!$this->task->isErroneous()) {
            return parent::init($taskGuid, $parameters);
        }
        
        //we set the worker to defunct when task has errors
        $wm = $this->workerModel;
        if(isset($wm)){
            $wm->setState($wm::STATE_DEFUNCT);
            $wm->save();
            //wake up remaining - if any
            $this->wakeUpAndStartNextWorkers();
        }
        //if no worker model is set, we don't have to call parent / init a worker model,
        // since we don't even need it in the DB when the task already has errors
        return false;
    }
    
    protected function initBehaviour(string $behaviourClass = null) {
        if(!empty($behaviourClass) && $behaviourClass !== $this->behaviourClass) {
            $newBehaviour = ZfExtended_Factory::get($behaviourClass);
            /* @var $newBehaviour ZfExtended_Worker_Behaviour_Default */
            //adopt configuration (some values may be set before this init was called!)
            $newBehaviour->setConfig($this->behaviour->getConfig());
            $this->behaviour = $newBehaviour;
        }
        //using a different behaviour here without setTask may make no sense, but who knows on which ideas the developers will come ;)
        if(method_exists($this->behaviour, 'setTask')) {
            $this->behaviour->setTask($this->task);
        }
    }
    
    /**
     * extend the exception handler with task logging
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::handleWorkerException()
     */
    protected function handleWorkerException(Throwable $workException) {
        parent::handleWorkerException($workException);
        //just add the task if it is a error code exception
        if($workException instanceof ZfExtended_ErrorCodeException) {
            $workException->addExtraData(['task' => $this->task]);
            return;
        }
        
        //if it is an ordinary error, we log that additionaly to the task log.
        $logger = ZfExtended_Factory::get('ZfExtended_Logger', [[
            'writer' => [
                'tasklog' => Zend_Registry::get('config')->resources->ZfExtended_Resource_Logger->writer->tasklog
            ]
        ]]);
        /* @var $logger ZfExtended_Logger */
        $logger->exception($workException, [
            'extra' => [
                'task' => $this->task
            ],
        ]);
    }
}
