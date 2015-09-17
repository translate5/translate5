<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
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
    //const SEGMENT_STATE_TARGETNOTFOUND = 'targetnotfound';
    
    /**
     * Stores the init-paramters from the initial call
     * @var array
     */
    protected $data = false;
    /**
     *
     * @var editor_Models_Term 
     */
    protected $termModel;
    /**
     *
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var array
     */
    protected $groupCounter = array();
    
    /**
     * @var array
     */
    protected $notPresentInTbxTarget = array();


    public function init($taskGuid = NULL, $parameters = array()) {
        $return = parent::init($taskGuid, $parameters);
        $this->termModel = ZfExtended_Factory::get('editor_Models_Term');

        $taskGuid = $this->workerModel->getTaskGuid();
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $this->task->loadByTaskGuid($taskGuid);
        return $return;
    }
    /**
     * @todo forking should be transfered to ZfExtended_Worker_Abstract to make it usable for other workers.
     * it should be based on maxParallelProcesses instead of just having one running worker per slot. maxParallelProcesses is ignored so far.
     * @param string $state
     */
    public function queue($state = NULL) {
        $workerCountToStart = 0;

        $usedSlots = count($this->workerModel->getListSlotsCount(self::$resourceName));
               
        $availableWorkerSlots = count($this->getAvailableSlots($this->resourcePool));
        
        while(($usedSlots+$workerCountToStart)<($availableWorkerSlots+1)){
            $workerCountToStart++;
        }
        if(empty($usedSlots)){
            $workerCountToStart = count($this->getAvailableSlots($this->resourcePool));
        }

        for($i=0;$i<$workerCountToStart;$i++){
            $this->init($this->workerModel->getTaskGuid(), $this->workerModel->getParameters());
            parent::queue($state);
        }
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
        //TODO: this config and return can be removed after finishing the initial big transit project
        $config = Zend_Registry::get('config');
        if(!empty($config->runtimeOptions->termTagger->markTransFoundLegacy)) {
            return $segments;
        }
        $taskGuid = $this->task->getTaskGuid();
        foreach ($segments as &$seg) {
            //remove potentially incorrect transFound or transNotFound as inserted by termtagger
            $seg->source = preg_replace('" ?transN?o?t?Found ?"', ' ', $seg->source);
            $seg->target = preg_replace('" ?transN?o?t?Found ?"', ' ', $seg->target);

            $sourceMids = $this->termModel->getTermMidsFromSegment($seg->source);
            $targetMids = $this->termModel->getTermMidsFromSegment($seg->target);
            $toMarkMemory = array();
            $this->groupCounter = array();
            foreach ($sourceMids as $sourceMid) {
                $this->termModel->loadByMid($sourceMid, $taskGuid);
                $groupId = $this->termModel->getGroupId();
                $groupedTerms = $this->termModel->getAllTermsOfGroup($taskGuid, $groupId, array($this->task->getTargetLang()));
                if(empty($groupedTerms)) {
                    $this->notPresentInTbxTarget[$groupId] = true;
                }
                $transFound = (isset($this->groupCounter[$groupId]))?$this->groupCounter[$groupId]:0;
                foreach ($groupedTerms as $groupedTerm) {
                    $targetMidsKey = array_search($groupedTerm['mid'], $targetMids);
                    if($targetMidsKey!==false){
                        $transFound++;
                        unset($targetMids[$targetMidsKey]);
                    }
                }
                $toMarkMemory[$sourceMid] = $groupId;
                $this->groupCounter[$groupId] = $transFound;
            }
            foreach ($toMarkMemory as $sourceMid => $groupId) {
                $seg->source = $this->insertTransFoundInSegmentClass($seg->source, $sourceMid, $groupId);
            }
        }
        return $segments;
    }
    /**
     * insert the css-class transFound or transNotFound into css-class of the term-div tag with the corresponding mid
     * @param string $seg
     * @param string $mid
     * @param $groupId
     * @return string
     */
    protected function insertTransFoundInSegmentClass(string $seg,string $mid, $groupId) {
        settype($this->groupCounter[$groupId], 'integer');
        $transFound =& $this->groupCounter[$groupId];
        $presentInTbxTarget = empty($this->notPresentInTbxTarget[$groupId]);
        $rCallback = function($matches) use (&$seg, &$transFound, $presentInTbxTarget){
            foreach ($matches as $match) {
                if($presentInTbxTarget) {
                    $cssClassToInsert = ($transFound>0)?'transFound':'transNotFound';
                }
                else {
                    $cssClassToInsert = 'transNotDefined';
                }
                
                $transFound--;
                $modifiedMatch = $match;
                if(strpos($modifiedMatch, ' class=')===false){
                    $modifiedMatch = str_replace('<div', '<div class=""', $modifiedMatch);
                }
                $modifiedMatch = preg_replace('/( class="[^"]*)"/', '\\1 '.$cssClassToInsert.'"', $modifiedMatch);
                $seg = preg_replace('/'.$match.'/', $modifiedMatch, $seg, 1);
            }
        };
        
        preg_replace_callback('/<div[^>]*data-tbxid="'.$mid.'"[^>]*>/', $rCallback, $seg);
        return $seg;
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
     * @return SplFileInfo
     */
    protected function getTbxFilename() {
        return new SplFileInfo(editor_Models_Import_TermListParser_Tbx::getTbxPath($this->task));
    }
    
    /**
     * Checks if tbx-file with hash $tbxHash is loaded on the TermTagger-server behind $url.
     * If not already loaded, tries to load the tbx-file from the task.
     *
     * @param string $url the TermTagger-server-url
     * @param string $tbxHash unique id of the tbx-file
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
        
        // try to load tbx-file to the TermTagger-server
        $tbxPath = $this->getTbxFilename($this->task);
        $tbxParser = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $tbxParser editor_Models_Import_TermListParser_Tbx */
        $tbxData = $tbxParser->assertTbxExists($this->task, new SplFileInfo($tbxPath));
        $tbxHash = $this->task->meta()->getTbxHash();
        
        $service = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $service editor_Plugins_TermTagger_Service */
        if(!$service->open($url, $tbxHash, $tbxData)) {
            //$this->log->logError(__CLASS__.' -> '.__FUNCTION__.'; Terminology disabled because tbx can not be loaded to the TermTagger-server.');
            throw new editor_Plugins_TermTagger_Exception_Open(__CLASS__.' -> '.__FUNCTION__.'; Terminology disabled because tbx can not be loaded to the TermTagger-server.');
        }
        
        return true;
    }
    
}