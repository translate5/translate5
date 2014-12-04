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
 * editor_Plugins_TermTagger_Worker_TermTaggerLoader Class
 */
class editor_Plugins_TermTagger_Worker_TermTaggerLoader extends ZfExtended_Worker_Abstract {
    
    use editor_Plugins_TermTagger_Worker_TermTaggerTrait;
    
    
    /**
     * Special Paramters:
     * 
     * $parameters['resourcePool']
     * sets the resourcePool for slot-calculation depending on the context.
     * Possible values are all values out of $this->allowedResourcePool
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
        settype($parameters['fetchIds'], 'boolean');
        $this->data = $parameters;
        
        $parametersToSave = array();
        
        if (isset($parameters['resourcePool']) && in_array($parameters['resourcePool'], self::$allowedResourcePools)) {
            $this->resourcePool = $parameters['resourcePool'];
            $parametersToSave['resourcePool'] = $this->resourcePool;
        }
        return parent::init($taskGuid, $parametersToSave);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    } 
    
    /**
     * enable a direct call of the worker by setting to public
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        return parent::run();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        if (empty($this->data)) {
            return false;
        }
        
        //load task or use given task in direct runs
        if(empty($this->data->task) || !is_subclass_of($this->data->task, 'editor_Models_Task')) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($this->taskGuid);
        }
        else {
            $task = $this->data->task;
        }
        
        $meta = $task->meta();
        //ensure existence of the tbxHash field
        $meta->addMeta('tbxHash', $meta::META_TYPE_STRING, null, 'Contains the MD5 hash of the original imported TBX file before adding IDs', 36);
        
        $service = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $service editor_Plugins_TermTagger_Service */
        $url = $this->workerModel->getSlot();
        
        $tbxPath = $this->getTbxFilename($task);
        $tbxData = $this->assertTbxExists($task, $tbxPath);
        
        if(empty($tbxData)) {
            $this->log->logError('Terminology disabled since empty TBX data given!');
            return false;
        }
        
        //get TBX hash from DB, if set
        $hash = $task->meta()->getTbxHash();
        if(!empty($hash) && !$this->data['fetchIds']) {
            $service->open($url, $hash, $tbxData);
            return true;
        }
        
        //ensure that we have the hash in the DB before we add IDs:
        $hash = md5($tbxData);
        $task->meta()->setTbxHash($hash);
        $task->meta()->save();
        
        $result = $service->openFetchIds($url, $hash, $tbxData);
        
        $data = json_decode($result);
        unset($result);
        
        $tmpFile = tempnam(dirname($tbxPath), 'tbx');
        file_put_contents($tmpFile, $data->tbxdata);
        rename($tmpFile, $tbxPath);
        return true;
    }
    
}