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
 * editor_Plugins_TermTagger_Worker_TermTagger Class
 */
class editor_Plugins_TermTagger_Worker_TermTagger extends editor_Plugins_TermTagger_Worker_Abstract {

    /**
     * Defines the timeout in seconds how long a single segment needs to be tagged
     * @var integer
     */
    const TIMEOUT_REQUEST = 180;
    
    /**
     * Defines the timeout in seconds how long the upload and parse request of a TBX may need
     * @var integer
     */
    const TIMEOUT_TBXIMPORT = 600;
    
    /**
     * @var editor_Plugins_TermTagger_Service_ServerCommunication
     */
    protected $serverCommunication = null;
    
    public function __construct() {
        parent::__construct();
        $this->logger = Zend_Registry::get('logger')->cloneMe('editor.terminology.segmentediting');
        $this->behaviour->setConfig(['isMaintenanceScheduled' => false]);
    }
    
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
     *
     * @see ZfExtended_Worker_Abstract::init()
     */
    public function init($taskGuid = NULL, $parameters = array()) {
        //since validateParams is checkin it too late, we have to check it here
        if (empty($parameters['serverCommunication'])) {
            $this->log->error('E1124','Parameter validation failed, missing serverCommunication object.',[
                'taskGuid' => $taskGuid,
                'parameters' => $parameters,
            ]);
            return false;
        }
        $this->serverCommunication = $parameters['serverCommunication'];
        unset($parameters['serverCommunication']); //we don't want and need this in the DB

        return parent::init($taskGuid, $parameters);
    }

    /**
     * (non-PHPdoc)
     *
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        return parent::run();
    }

    /**
     * (non-PHPdoc)
     *
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        if (empty($this->serverCommunication)) {
            return false;
        }
        
        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service', [$this->logger->getDomain(), self::TIMEOUT_REQUEST, self::TIMEOUT_TBXIMPORT]);
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        
        $result = '';
        $slot = $this->workerModel->getSlot();
        if(empty($slot)) {
            return false;
        }
        try {
            $this->checkTermTaggerTbx($termTagger, $slot, $this->serverCommunication->tbxFile);
            $result = $termTagger->tagterms($slot, $this->serverCommunication);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            if($exception instanceof editor_Plugins_TermTagger_Exception_Down) {
                $this->disableSlot($slot);
            }
            $this->serverCommunication->task = '- see directly in event -';
            $exception->addExtraData([
                'task' => $this->task,
                'termTagData' => $this->serverCommunication,
            ]);
            $this->logger->exception($exception, [
                'domain' => 'editor.terminology.segmentediting'
            ]);
            if($exception instanceof editor_Plugins_TermTagger_Exception_Open) {
                //editor_Plugins_TermTagger_Exception_Open Exceptions mean mostly that there is problem with the TBX data
                //so we have to disable termtagging for this task, otherwise on each segment save we will get such a warning
                $this->task->setTerminologie(0);
                $this->task->save();
                return false;
            }
        }
        
        // on error return false and store original untagged data
        if (empty($result) && $result !== '0') {
            return false;
        }
        $this->result = $result->segments;
        $this->result = $this->markTransFound($this->result);
        return true;
    }
}