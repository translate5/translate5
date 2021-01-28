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
 * Extends the import worker to work with resources/slots and limits the running workers by the available resources/slots
 * unfortunalely / historically the differentiation of the code is not very good since some of the needed functionality is in the base-class without being really needed there
 * The naming "slot" is historically and is used synonymous for "resource" in the end since we save a resource/url as the "slot" (= field in the worker table)
 */
abstract class editor_Models_Import_Worker_ResourceAbstract extends editor_Models_Import_Worker_Abstract {

    /**
     * it should be based on maxParallelProcesses instead of just having one running worker per slot. maxParallelProcesses is ignored so far.
     * @param int $parentId
     * @param string $state
     *
     * @see ZfExtended_Worker_Abstract::queue()
     */
    public function queue($parentId = 0, $state = NULL, $startNext = true) {
        
        $workerCountToStart = 0;        
        $usedSlots = count($this->workerModel->getListSlotsCount(static::$resourceName));        
        $availableWorkerSlots = count($this->getAvailableSlots($this->resourcePool));
        
        while(($usedSlots + $workerCountToStart) < ($availableWorkerSlots + 1)){
            $workerCountToStart++;
        }
        if(empty($usedSlots)){
            $workerCountToStart = count($this->getAvailableSlots($this->resourcePool));
        }
        if($workerCountToStart == 0) {
            $this->raiseNoAvailableResourceException();
        }
        for($i=0; $i < $workerCountToStart; $i++){
            $this->init($this->workerModel->getTaskGuid(), $this->workerModel->getParameters());
            parent::queue($parentId, $state);
        }
        return $parentId; //since we can't return multiple ids, we just return the given parent again
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
        if($this->isWorkerThread){
            if($this->process($slot)){
                $this->queueNextWorkers();
                return true;
            }
            return false;
        } else {
            return $this->process($slot);
        }
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
        $usedSlots = $this->workerModel->getListSlotsCount(self::$resourceName, $availableSlots);
        
        if(empty($availableSlots)) {
            return ['resource' => self::$resourceName, 'slot' => null];
        }
        // all slots in use
        if (count($usedSlots) == count($availableSlots)) {
            // take first slot in list of usedSlots which is the one with the min. number of counts
            return ['resource' => self::$resourceName, 'slot' => $usedSlots[0]['slot']];
        }
        // some slots in use
        if (!empty($usedSlots)) {
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
     * Creates the Next Workers to start (the multiplication of workers is done in the queue-method
     * @param string $taskGuid
     * @return boolean
     */
    protected function queueNextWorkers() {
        $taskGuid = $this->workerModel->getTaskGuid();
        $parameters = $this->workerModel->getParameters();
        $worker = ZfExtended_Factory::get(get_class($this));
        /* @var $worker editor_Models_Import_Worker_ResourceAbstract */
        if (!$worker->init($taskGuid, $parameters)) {
            $this->getLogger()->error('E1122', get_class($this).' next Worker can not be initialized!', [
                'taskGuid' => $taskGuid,
                'parameters' => $parameters
            ]);
            return false;
        }
        $worker->queue($this->workerModel->getParentId());
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
