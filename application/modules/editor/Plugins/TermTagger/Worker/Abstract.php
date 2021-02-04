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

abstract class editor_Plugins_TermTagger_Worker_Abstract extends editor_Models_Import_Worker_Abstract {
    
    /**
     * This check prevents the termtagger to process any segments to avoid problems with hanging termtagger
     * see TRANSLATE-2373
     * @return bool
     */
    public static function isSourceAndTargetLanguageEqual(editor_Models_Task $task) : bool {
        return ($task->getSourceLang() === $task->getTargetLang());
    }
    
    const TERMTAGGER_DOWN_CACHE_KEY = 'TermTaggerDownList';
    
    /**
     * overwrites $this->workerModel->maxLifetime
     */
    protected $maxLifetime = '2 HOUR';
    
    // TEST: setting a different blocking-type
    // protected $blockingType = ZfExtended_Worker_Abstract::BLOCK_RESOURCE;
    
    /**
     * Multiple workers are allowed to run simultaneously per task
     * @var string
     */
    protected $onlyOncePerTask = false;
    
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
    protected static $praefixResourceName = 'TermTagger_';
    
    /**
     * Values for termtagging segment-states
     * @var array(strings)
     */
    const SEGMENT_STATE_UNTAGGED = 'untagged';
    const SEGMENT_STATE_INPROGRESS = 'inprogress';
    const SEGMENT_STATE_TAGGED = 'tagged';
    const SEGMENT_STATE_DEFECT = 'defect';
    const SEGMENT_STATE_RETAG = 'retag';
    const SEGMENT_STATE_OVERSIZE = 'oversized';
    const SEGMENT_STATE_IGNORE = 'ignore';
    //const SEGMENT_STATE_TARGETNOTFOUND = 'targetnotfound';
    
    /**
     * Stores the init-paramters from the initial call
     * @var array
     */
    protected $data = false;

    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Plugins_TermTagger_RecalcTransFound
     */
    protected $markTransFound;
    
    /**
     * @var ZfExtended_Logger
     */
    protected $logger;
    
    /**
     * @var Zend_Cache_Core
     */
    protected $memCache;
    
    public function init($taskGuid = NULL, $parameters = array()) {
        $return = parent::init($taskGuid, $parameters);
        
        $taskGuid = $this->workerModel->getTaskGuid();
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $this->task->loadByTaskGuid($taskGuid);
        $this->markTransFound = ZfExtended_Factory::get('editor_Plugins_TermTagger_RecalcTransFound', array($this->task));
        $this->memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true]);
        
        return $return;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    }

    /**
     * @todo forking should be transfered to ZfExtended_Worker_Abstract to make it usable for other workers.
     * it should be based on maxParallelProcesses instead of just having one running worker per slot. maxParallelProcesses is ignored so far.
     * @param int $parentId
     * @param string $state
     *
     * @see ZfExtended_Worker_Abstract::queue()
     */
    public function queue($parentId = 0, $state = NULL) {
        $workerCountToStart = 0;

        $usedSlots = count($this->workerModel->getListSlotsCount(self::$resourceName));
               
        $availableWorkerSlots = count($this->getAvailableSlots($this->resourcePool));
        
        while(($usedSlots+$workerCountToStart)<($availableWorkerSlots+1)){
            $workerCountToStart++;
        }
        if(empty($usedSlots)){
            $workerCountToStart = count($this->getAvailableSlots($this->resourcePool));
        }

        if($workerCountToStart == 0) {
            //E1131No TermTaggers available, please enable term taggers to import this task.
            throw new editor_Plugins_TermTagger_Exception_Down('E1131', [
                'task' => $this->task
            ]);
        }
        // in case of equal languages we only need to start one worker
        if($workerCountToStart > 1 && static::isSourceAndTargetLanguageEqual($this->task)){
            $workerCountToStart = 1;
        }
        
        for($i=0;$i<$workerCountToStart;$i++){
            $this->init($this->workerModel->getTaskGuid(), $this->workerModel->getParameters());
            parent::queue($parentId, $state);
        }
        return $parentId; //since we can't return multiple ids, we just return the given parent again
    }
    /**
     * marks terms in the source with transFound, if translation is present in the target
     * and with transNotFound if not. A translation which is of type
     * editor_Models_Term::STAT_DEPRECATED or editor_Models_Term::STAT_SUPERSEDED
     * is handled as transNotFound
     *
     * @param array $segments array of stdClass. example: array(object(stdClass)#529 (4) {
      ["field"]=>
      string(10) "targetEdit"
      ["id"]=>
      string(7) "4596006"
      ["source"]=>
      string(35) "Die neue VORTEILE Motorenbroschüre"
      ["target"]=>
      string(149) "Il nuovo dépliant PRODUCT INFO <div title="" class="term admittedTerm transNotFound stemmed" data-tbxid="term_00_1_IT_1_08795">motori</div>"),
       another object, ...
     *
     * @return stdClass $segments
    }
     */
    protected function markTransFound(array $segments) {
        return $this->markTransFound->recalcList($segments);
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
        // detect defined slots for the resourcePool
        $availableSlots = $this->getAvailableSlots($resourcePool);
        
        $usedSlots = $this->workerModel->getListSlotsCount(self::$resourceName, $availableSlots);
        
        if(empty($availableSlots)) {
            return ['resource' => self::$resourceName, 'slot' => null];
        }
        
        // all slotes in use
        if (count($usedSlots) == count($availableSlots)) {
            // take first slot in list of usedSlots which is the one with the min. number of counts
            $return = array('resource' => self::$resourceName, 'slot' => $usedSlots[0]['slot']);
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
            
            $return = array('resource' => self::$resourceName, 'slot' => $unusedSlots[array_rand($unusedSlots)]);
            return $return;
        }
        
        // no slot in use
        $return = array('resource' => self::$resourceName, 'slot' => $availableSlots[array_rand($availableSlots)]);
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
        
        
        //remove not available termtaggers from configured list
        $downList = $this->memCache->load(self::TERMTAGGER_DOWN_CACHE_KEY);
        
        if(!empty($downList) && is_array($downList)) {
            $return = array_diff($return, $downList);
        }
        
        // no slots for this resourcePool defined
        if (empty($return) && $resourcePool != 'default') {
            // calculate slot from default resourcePool
            return $this->getAvailableSlots();
        }
        
        return $return;
    }
    
    /**
     * disables the given slot (URL) via memcache.
     * @param string $url
     */
    protected function disableSlot(string $url) : void {
        $list = $this->memCache->load(self::TERMTAGGER_DOWN_CACHE_KEY);
        if(!$list || !is_array($list)) {
            $list = [];
        }
        $list[] = $url;
        $this->memCache->save($list, self::TERMTAGGER_DOWN_CACHE_KEY);
    }
    
    /**
     * @return SplFileInfo
     */
    protected function getTbxFilename() {
        return new SplFileInfo(editor_Models_Import_TermListParser_Tbx::getTbxPath($this->task));
    }
    
    /**
     * Checks if tbx-file with hash $tbxHash is loaded on the TermTagger-server behind $url.
     * If not already loaded, tries to load the tbx-file from the task.
     * Throws Exceptions if TBX could not be loaded!
     * @throws editor_Plugins_TermTagger_Exception_Abstract
     * @param editor_Plugins_TermTagger_Service $termTagger the TermTagger Service to be used
     * @param string $url the TermTagger-server-url
     * @param string $tbxHash unique id of the tbx-file
     */
    protected function checkTermTaggerTbx(editor_Plugins_TermTagger_Service $termTagger, $url, &$tbxHash) {
        try {
            // test if tbx-file is already loaded
            if (!empty($tbxHash) && $termTagger->ping($url, $tbxHash)) {
                return;
            }
            //getDataTbx also creates the TbxHash
            $tbx = $this->getTbxData();
            $tbxHash = $this->task->meta()->getTbxHash();
            $termTagger->open($url, $tbxHash, $tbx);
        }
        catch (editor_Plugins_TermTagger_Exception_Abstract $e) {
            $e->addExtraData([
                'task' => $this->task,
                'termTaggerUrl' => $url,
            ]);
            throw $e;
        }
    }
    
    /**
     * returns the TBX string to be loaded into the termtagger
     * @throws editor_Plugins_TermTagger_Exception_Open
     * @return string
     */
    protected function getTbxData() {
        // try to load tbx-file to the TermTagger-server
        $tbxPath = $this->getTbxFilename($this->task);
        $tbxParser = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $tbxParser editor_Models_Import_TermListParser_Tbx */
        try {
            return $tbxParser->assertTbxExists($this->task, new SplFileInfo($tbxPath));
        }
        catch (editor_Models_Term_TbxCreationException $e) {
            //'E1116' => 'Could not load TBX into TermTagger: TBX hash is empty.',
            throw new editor_Plugins_TermTagger_Exception_Open('E1116', [], $e);
        }
    }
}