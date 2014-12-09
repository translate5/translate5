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
trait editor_Plugins_TermTagger_Worker_TermTaggerTrait {
    
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
    public static $SEGMENT_STATE_UNTAGGED = 'untagged';
    public static $SEGMENT_STATE_INPROGRESS = 'inprogress';
    public static $SEGMENT_STATE_TAGGED = 'tagged';
    //public static $SEGMENT_STATE_TARGETNOTFOUND = 'targetnotfound';
    
    /**
     * Stores the init-paramters from the initial call
     * @var array
     */
    protected $data = false;
    
    
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
        $resourceName = self::$praefixResourceName.$resourcePool;
        
        // detect defined slots for the resourcePool
        $config = Zend_Registry::get('config');
        $url = $config->runtimeOptions->termTagger->url;
        switch ($resourcePool) {
            case 'gui':
                $availableSlots = $url->gui->toArray();
                break;
            
            case 'import':
                $availableSlots = $url->import->toArray();
                break;
            
            case 'default':
                $availableSlots = $url->default->toArray();
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
     * decodes the TermTagger JSON and logs an error if data can not be processed
     * @param Zend_Http_Response $result
     * @return stdClass or null on error
     */
    protected function decodeServiceResult(Zend_Http_Response $result = null) {
        if(empty($result)) {
            return null;
        }
        
        $data = json_decode($result->getBody());
        if(!empty($data)) {
            if(!empty($data->error)) {
                $this->log->logError(__CLASS__.' decoded TermTagger Result but with following Error from TermTagger: ', print_r($data,1));
            }
            return $data;
        }
        $msg = "Original TermTagger Result was: \n".$result->getBody()."\n JSON decode error was: ";
        if (function_exists('json_last_error_msg')) {
            $msg .= json_last_error_msg();
        } else {
            static $errors = array(
                JSON_ERROR_NONE             => null,
                JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
                JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
                JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
                JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
                JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
            );
            $error = json_last_error();
            $msg .=  array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
        }
        $this->log->logError(__CLASS__.' cannot json_decode TermTagger Result!', $msg);
        return null;
    }
    
    /**
     * @param editor_Models_Task $task
     * @return SplFileInfo
     */
    protected function getTbxFilename(editor_Models_Task $task) {
        return new SplFileInfo(editor_Models_Import_TermListParser_Tbx::getTbxPath($task));
    }
    
    /**
     * checks if the needed TBX file exists, otherwise recreate if from DB
     * @param editor_Models_Task $task
     * @param SplFileInfo $tbxPath
     */
    protected function assertTbxExists(editor_Models_Task $task, SplFileInfo $tbxPath) {
        if($tbxPath->isReadable()) {
            return file_get_contents($tbxPath);
        }
        //after recreation we need to fetch the IDs!
        $this->data['fetchIds'] = true;
        
        //fallback for recreation of TBX file:
        $term = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        
        $export = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $export editor_Models_Export_Terminology_Tbx */
        return $term->export($task, $export);
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
    protected function checkTermTaggerTbx($url, $tbxHash) {
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
    
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerLoader');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerLoader */
        $worker->init($taskGuid, array('task' => $task));
        //run the worker to send it to the termtagger
        return $worker->run();
    }
    
}