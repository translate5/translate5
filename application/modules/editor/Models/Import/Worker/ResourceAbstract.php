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
 * Extends the import worker to work with resources/slots and limits the running workers by the available resources/slots
 * unfortunalely / historically the differentiation of the code is not very good since some of the needed functionality is in the base-class without being really needed there
 * The naming "slot" is historically and is used synonymous for "resource" in the end since we save a resource/url as the "slot" (= field in the worker table)
 */
abstract class editor_Models_Import_Worker_ResourceAbstract extends editor_Models_Task_AbstractWorker {

    /**
     * it should be based on maxParallelProcesses instead of just having one running worker per slot. maxParallelProcesses is ignored so far.
     * UGLY: The param $startNext has a different meaning here:more like "startAsManyAsThereAreFreeResources" ...
     *
     * @see ZfExtended_Worker_Abstract::queue()
     */
    public function queue($parentId=0, $state=NULL, $startNext=true) : int {

        // we start as many workers as there a free resources on startNext
        if($startNext){
            
            $availableSlots = count($this->getAvailableSlots($this->resourcePool));
            // if there are no available slots (e.g. all Resources down) we need to raise an exception to inform the user
            if($availableSlots == 0){
                $this->raiseNoAvailableResourceException();
            }
            
            // we trigger the parent init without starting them of course
            $idToReturn = parent::queue($parentId, $state, false);
            
            // the still free slots after the worker is queued
            $usedSlots = count($this->workerModel->getListSlotsCount(static::$resourceName));
            
            // we can use the free slots to start additional workers
            if($availableSlots > $usedSlots){
                for($i=0; $i < ($availableSlots - $usedSlots); $i++){
                    $worker = ZfExtended_Factory::get(get_class($this));
                    /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
                    $worker->init($this->workerModel->getTaskGuid(), $this->workerModel->getParameters());
                    $worker->queue($parentId, $state, false);
                }
            }
            // we now can start the queue
            $this->wakeUpAndStartNextWorkers();
            $this->emulateBlocking();
            
            return $idToReturn;
            
        } else {
            return parent::queue($parentId, $state, false);
        }
    }
    /**
     * A Resource worker has the "process" function to replace the "work" API enriched with the slot as param
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work(){
        $slot = $this->workerModel->getSlot();
        if(empty($slot)){
            return false;
        }
        return $this->process($slot);
    }

    // overwrites ZfExtended_Worker_Abstract functionality
    protected function calculateDirectSlot() {
        return $this->calculateSlot($this->resourcePool);
    }

    // overwrites ZfExtended_Worker_Abstract functionality
    protected function calculateQueuedSlot() {
        return $this->calculateSlot($this->resourcePool);
    }

    /**
     * Calculates the resource and slot for the given $resourcePool
     * Some kind of "load-balancing" is used in calculations so every resource/slot-combination is used in the same weight
     *
     * @param string $resourcePool
     * @return array('resource' => resourceName, 'slot' => slotName)
     */
    protected function calculateSlot($resourcePool = 'default') {
        // detect defined slots for the resourcePool
        $availableSlots = $this->getAvailableSlots($resourcePool);
        $numAvailableSlots = count($availableSlots);
        $usedSlots = $this->workerModel->getListSlotsCount(self::$resourceName, $availableSlots);
        $numUsedSlots = count($usedSlots);

        if($numAvailableSlots < 1) {
            return ['resource' => self::$resourceName, 'slot' => null];
        }
        // all slots in use
        if ($numUsedSlots > 0 && $numUsedSlots === $numAvailableSlots) {
            // take first slot in list of usedSlots which is the one with the min. number of counts
            return ['resource' => self::$resourceName, 'slot' => $usedSlots[0]['slot']];
        }
        // some slots in use
        if ($numUsedSlots > 0) {
            // sort out the used slots
            $unusedSlots = $availableSlots;
            foreach ($usedSlots as $usedSlot) {
                $key = array_search($usedSlot['slot'], $unusedSlots);
                if($key !== false) {
                    unset($unusedSlots[$key]);
                }
            }
            $unusedSlots = array_values($unusedSlots);
            // select a random unused slot
            return ['resource' => self::$resourceName, 'slot' => $unusedSlots[array_rand($unusedSlots)]];
        }
        // no slot in use, select a random available slot
        return ['resource' => self::$resourceName, 'slot' => $availableSlots[array_rand($availableSlots)]];
    }
    /**
     * Chaining of Resource workers: Creates the Next Workers to start (the multiplication of workers is done in the queue-method) if we successfully worked
     * @return boolean
     */
    protected function onRunQueuedFinished($success) {
        // only queue workers if the work was successful
        // the last worker must return false if no segments to process could be found, otherwise we will work endlessly...
        if($success){        
            $taskGuid = $this->workerModel->getTaskGuid();
            $parameters = $this->workerModel->getParameters();
            $worker = ZfExtended_Factory::get(get_class($this));
            /* @var $worker editor_Models_Import_Worker_ResourceAbstract */
            if (!$worker->init($taskGuid, $parameters)) {
                $this->getLogger()->error('E1122', get_class($this).' next Worker can not be initialized!', [
                    'taskGuid' => $taskGuid,
                    'parameters' => $parameters
                ]);
            }
            $worker->queue($this->workerModel->getParentId(), ZfExtended_Models_Worker::STATE_WAITING);
        }
    }
    /**
     * Needs to be defined in the real worker. Performs the threaded work
     * @param string $slot
     * @return bool
     */
    abstract protected function process(string $slot) : bool;
    /**
     * Needs to be defined in the real worker. Retrieves the available resource slots.
     * @return array
     */
    abstract protected function getAvailableSlots($resourcePool = 'default') : array;
    /**
     * @throws Exception
     */
    abstract protected function raiseNoAvailableResourceException();
}
