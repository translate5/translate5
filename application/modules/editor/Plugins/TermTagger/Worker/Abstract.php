<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * Trait for "TermTagger"
 * used as shared-code-base for the differen TermTaggerWorker.
 */
abstract class editor_Plugins_TermTagger_Worker_Abstract extends ZfExtended_Worker_Abstract {
    
    /**
     * overwrites $this->workerModel->maxLifetime
     */
    protected $maxLifetime = '2 HOUR';
    
    // TEST: setting a different blocking-type
    // protected $blockingType = ZfExtended_Worker_Abstract::BLOCK_RESOURCE;
    
    /**
     * resourcePool for the different TermTagger-Operations;
     * Possible Values: $this->allowdResourcePools = array('default', 'gui', 'import');
     * @var string
     */
    protected $resourcePool = 'default';
    /**
     * Allowd values for setting resourcePool
     * @var array(strings)
     */
    protected static $allowedResourcePools = array('default', 'gui', 'import');
    
    /**
     * Praefix for workers resource-name
     * @var string
    */
    private static $praefixResourceName = 'TermTagger_';
    
    /**
     * Values for termtagging segment-states
     * @var array(strings)
     */
    const SEGMENT_STATE_UNTAGGED = 'untagged';
    const SEGMENT_STATE_INPROGRESS = 'inprogress';
    const SEGMENT_STATE_TAGGED = 'tagged';
    const SEGMENT_STATE_DEFECT = 'defect';
    const SEGMENT_STATE_RETAG = 'retag';
    //const SEGMENT_STATE_TARGETNOTFOUND = 'targetnotfound';
    
    /**
     * Stores the init-paramters from the initial call
     * @var array
     */
    protected $data = false;
    /**
     * @todo forking should be transfered to ZfExtended_Worker_Abstract to make it usable for other workers.
     * it should be based on maxParallelProcesses instead of just having one running worker per slot and maxParallelProcesses=1 as currently - but one slot for each termTagger instance. All termTagger instances should run in the same slot but maxParallelProcesses should be set to the number of termTagger-instances
     * @param string $state
     */
    public function queue($state = NULL) {
        $resourceName = self::$praefixResourceName.$this->resourcePool;
        $usedSlots = $this->workerModel->getListSlotsCount($resourceName);
        
        $workerCountToStart = 0;
        foreach ($usedSlots as $slot) {
            if($slot['count']<=1){
                $workerCountToStart++;
            }
        }
        if(empty($usedSlots)){
            $workerCountToStart = count($this->getAvailableSlots($this->resourcePool));
        }
        
        for($i=0;$i<$workerCountToStart;$i++){
            parent::queue($state);
            $this->init($this->workerModel->getTaskGuid(), array('resourcePool' => $this->resourcePool));
        }
    }
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::calculateDirectSlot()
     */
    protected function calculateDirectSlot() {
        return $this->calculateSlot($this->resourcePool);
    }
    
    
    /**
     * (non-PHPdoc) 
     * @see ZfExtended_Worker_Abstract::calculateQueuedSlot()
     */
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
    private function calculateSlot($resourcePool = 'default') {
        $resourceName = self::$praefixResourceName.$resourcePool;
        
        // detect defined slots for the resourcePool
        $availableSlots = $this->getAvailableSlots($resourcePool);
        
        $usedSlots = $this->workerModel->getListSlotsCount($resourceName);
        
        // all slotes in use
        if (count($usedSlots) == count($availableSlots)) {
            // take first slot in list of usedSlots which is the one with the min. number of counts
            $return = array('resource' => $resourceName, 'slot' => $usedSlots[0]['slot']);
            return $return;
        }
        
        // some slots in use
        if (!empty($usedSlots)) {
            // select a random slot of the unused slots
            $unusedSlots = $availableSlots;
            
            foreach ($usedSlots as $usedSlot) {
                $key = array_search($usedSlot['slot'], $unusedSlots);
                if($key!==false) {
                    unset($unusedSlots[$key]);
                }
            }
            $unusedSlots = array_values($unusedSlots);
            
            $return = array('resource' => $resourceName, 'slot' => $unusedSlots[array_rand($unusedSlots)]);
            return $return;
        }
        
        // no slot in use
        $return = array('resource' => $resourceName, 'slot' => $availableSlots[array_rand($availableSlots)]);
        return $return;
    }
    
    
    /**
     * 
     * @return array
     */
    protected function getAvailableSlots($resourcePool = 'default') {
        $config = Zend_Registry::get('config');
        $url = $config->runtimeOptions->termTagger->url;
        
        switch ($resourcePool) {
            case 'gui':
                $return = $url->gui->toArray();
                break;
            
            case 'import':
                $return = $url->import->toArray();
                break;
            
            case 'default':
            default:
                $return = $url->default->toArray();
                break;
        }
        
        // no slots for this resourcePool defined
        if (empty($return) && $resourcePool != 'default') {
            // calculate slot from default resourcePool
            return $this->getAvailableSlots();
        }
        
        if(empty($return)) {
            trigger_error(__CLASS__.'->'.__FUNCTION__.'; There have to be available slots!');
        }
        
        return $return;
    }
    
    
    /**
     * @param editor_Models_Task $task
     * @return SplFileInfo
     */
    protected function getTbxFilename(editor_Models_Task $task) {
        return new SplFileInfo(editor_Models_Import_TermListParser_Tbx::getTbxPath($task));
    }
    
    /**
     * Checks if tbx-file with hash $tbxHash is loaded on the TermTagger-server behind $url.
     * If not already loaded, tries to load the tbx-file from the task.
     *
     * @param unknown $url the TermTagger-server-url
     * @param unknown $tbxHash unic id of the tbx-file
     *
     * @return boolean true if tbx-file is loaded on the TermTagger-server
     */
    protected function checkTermTaggerTbx($url, &$tbxHash) {
        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        
        // test if tbx-file is already loaded
        if ($termTagger->ping($url, $tbxHash)) {
            return true;
        }
        
        $taskGuid = $this->workerModel->getTaskGuid();
        
        // try to load tbx-file to the TermTagger-server
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $tbxPath = $this->getTbxFilename($task);
        $tbxParser = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $tbxParser editor_Models_Import_TermListParser_Tbx */
        $tbxData = $tbxParser->assertTbxExists($task, new SplFileInfo($tbxPath));
        $tbxHash = $task->meta()->getTbxHash();
        
        $service = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $service editor_Plugins_TermTagger_Service */
        if(!$service->open($url, $tbxHash, $tbxData)) {
            $this->log->logError(__CLASS__.' -> '.__FUNCTION__.'; Terminology disabled because tbx can not be loaded to the TermTagger-server.');
            return false;
        }
        
        return true;
    }
    
}