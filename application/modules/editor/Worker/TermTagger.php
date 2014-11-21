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
 * editor_Worker_TermTagger Class
 */
class editor_Worker_Termtagger extends ZfExtended_Worker_Abstract {
    
    /**
     * overwrites $this->workerModel->maxLifetime
     */
    protected $maxLifetime = '2 HOUR';
    
    // TEST: setting a different blocking-type
    // protected $blockingType = ZfExtended_Worker_Abstract::BLOCK_RESOURCE;
    
    /**
     * resourcePool for the different TermTagger-Operations;
     * Possible Values: self::allowdResourcePools = array('default', 'gui', 'import');
     * @var string
     */
    protected $resourcePool = 'default';
    /**
     * Allowd values for setting resourcePool
     * @var array(strings)
     */
    private static $allowedResourcePools = array('default', 'gui', 'import');
    
    /**
     * Praefix for workers resource-name
     * @var string
     */
    const praefixResourceName = 'TermTagger_';
    
    
    /**
     * Stores the init-paramters from the initial call
     * @var array
     */
    protected $data = false;
    
    
    
    /**
     * Special Paramters:
     * 
     * $parameters['resourcePool']
     * sets the resourcePool for slot-calculation depending on the context.
     * Possible values are all values out of self::allowedResourcePool
     * 
     * 
     * On very first init:
     * seperate data from parameters which are needed while processing queued-worker.
     * All informations which are only relevant in 'normal processing (not queued)'
     * are not needed to be saved in DB worker-table (aka not send to parent::init as $parameters)
     * 
     * ATTENTION:
     * for queued-operating $parameters saved in parent::init MUST have all necessary paramters
     * to call this init function again on instanceByModel
     * 
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::init()
     */
    public function init($taskGuid = NULL, $parameters = array()) {
        $this->data = $parameters;
        
        $parametersToSave = array();
        
        if (isset($parameters['resourcePool'])) {
            if (in_array($parameters['resourcePool'], self::$allowedResourcePools)) {
                $this->resourcePool = $parameters['resourcePool'];
                $parametersToSave['resourcePool'] = $this->resourcePool;
            }
        }
        
        // TODO (siehe auch ->work())
        // Unterscheidung zwischen einer Liste an Segmenten die ausgezeichnet werden sollen
        // und einem einzelnen Text der Ausgezeichnet werden soll.
        
        if (isset($parameters['segmentIds'])) {
            $parametersToSave['segmentIds'] = $parameters['segmentIds'];
        }
        
        if (isset($parameters['segmentData'])) {
            foreach ($parameters['segmentData'] as $item) {
                $parametersToSave['segmentIds'][] = $item['id'];
            }
        }
        
        return parent::init($taskGuid, $parametersToSave);
    }
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if (!isset($parameters['segmentData'][0]['targetEdit']) && empty($parameters['segmentIds'])) {
            error_log(__CLASS__.' -> '.__FUNCTION__.' can not validate $parameters: '.print_r($parameters, true));
            return false;
        }
        return true;
    } 
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::calculateDirectSlot()
     */
    protected function calculateDirectSlot() {
        //return array('resource' => 'TermTagger_default', 'slot' => 'Termtagger.local/index.php');
        return $this->calculateSlot('default');
    }
    
    /**
     * (non-PHPdoc) 
     * @see ZfExtended_Worker_Abstract::calculateQueuedSlot()
     */
    protected function calculateQueuedSlot() {
        //return array('resource' => 'TermTagger_default', 'slot' => 'TermTaggerSlot_'.rand(1, 3).'.local/index.php');
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
        $resourceName = self::praefixResourceName.$resourcePool;
        //error_log(__CLASS__.' -> '.__FUNCTION__.' $resourcePool: '.$resourcePool.' $resourceName: '.$resourceName);
        
        // detect defined slots for the resourcePool
        $config = Zend_Registry::get('config');
        switch ($resourcePool) {
            case 'gui':
                $availableSlots = $config->runtimeOptions->termTagger->url->gui->toArray();
                break;
            
            case 'import':
                $availableSlots = $config->runtimeOptions->termTagger->url->import->toArray();
                break;
            
            case 'default':
                $availableSlots = $config->runtimeOptions->termTagger->url->default->toArray();
                break;
        }
        // no slots for this resourcePool defined
        if (empty($availableSlots) && $resourcePool != 'default') {
            // calculate slot from default resourcePool
            return $this->calculateSlot('default');
        }
        
        $usedSlots = $this->workerModel->getListSlotsCount($resourceName);
        
        // all slotes in use
        if (count($usedSlots) == count($availableSlots)) {
            // take first slot in list of usedSlots which is the one with the min. number of counts
            $return = array('resource' => $resourceName, 'slot' => $usedSlots[0]['slot']);
            //error_log(__CLASS__.' -> '.__FUNCTION__.'; $return '.print_r($return, true));
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
            //error_log(__CLASS__.' -> '.__FUNCTION__.'; $return '.print_r($return, true));
            return $return;
        }
        
        // no slot in use
        $return = array('resource' => $resourceName, 'slot' => $availableSlots[array_rand($availableSlots)]);
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $return '.print_r($return, true));
        return $return;
        
    }
    
    
    /*
    public function queue() {
        throw new BadMethodCallException('Du kommst hier nicht rein '.__CLASS__.'->'.__FUNCTION__);
    }
    */
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        $result = parent::run();
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        
        error_log(__CLASS__.' -> '.__FUNCTION__);
        sleep(3);
        
        if (empty($this->data)) {
            return false;
        }
        
        // TODO hier klinkt sich später der tatsächliche TermTagger-Process ein.
        // Unterscheidung zwischen einer Liste an Segmenten die ausgezeichnet werden sollen
        // und einem einzelnen Text der Ausgezeichnet werden soll.
        
        if (isset($this->data['segmentIds'])) {
            $this->result = $this->data['segmentIds'];
        }
        
        if (isset($this->data['segmentData'])) {
            foreach ($this->data['segmentData'] as &$segment) {
                $tempText = $segment['targetEdit'];
                $tempText = 'PSEUDO-TERMTAGGED: '.$tempText;
                $segment['targetEdit'] = $tempText;
                
            }
            $this->result = $this->data['segmentData'];
        }
        
        
        return true;
    }
    
}