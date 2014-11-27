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
 * Initial Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Bootstrap {
    
    /**
     * @var Zend_EventManager_StaticEventManager
     */
    protected $staticEvents = false;
    
    /**
     * @var ZfExtended_Log
     */
    protected $log;
    
    
    
    public function __construct() {
        $config = Zend_Registry::get('config');
        
        if (!isset($config->runtimeOptions->termTagger->url->default)) {
            $this->log->logError('Plugin TermTagger config not defined',
                                 'The required config-setting runtimeOptions.termTagger.url.default is not defined in DB-table Zf_configuration.');
            return false;
        }
        
        if (empty($config->runtimeOptions->termTagger->url->default->toArray())) {
            $this->log->logError('Plugin TermTagger config not set',
                                 'The required config-setting runtimeOptions.termTagger.url.default is not set in DB-table Zf_configuration. Value is empty');
            return false;
        }
        
        // event-listeners
        $this->staticEvents = Zend_EventManager_StaticEventManager::getInstance();
        $this->staticEvents->attach('Editor_IndexController', 'afterIndexAction', array($this, 'handleAfterIndex'));
        $this->staticEvents->attach('editor_Workflow_Default', array('doView', 'doEdit'), array($this, 'handleAfterTaskOpen'));
        $this->staticEvents->attach('editor_Models_Segment', 'beforeSave', array($this, 'handleBeforeSegmentSave'));
        $this->staticEvents->attach('Editor_SegmentController', 'beforePutSave', array($this, 'handleTest'));
        
        // SBE: only for testing
        $this->staticEvents->attach('IndexController', 'beforeStephanAction', array($this, 'handleTest'));
        // end of event-listeners
    }
    
    
    /**
     * handler for event: Editor_IndexController#afterIndexAction
     */
    public function handleAfterIndex(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $view = $params[0];
        
        $config = Zend_Registry::get('config');
        $termTaggerSegmentsPerCall = $config->runtimeOptions->termTagger->segmentsPerCall;
        
        $view->Php2JsVars()->set('plugins.termTagger.segmentsPerCall', $termTaggerSegmentsPerCall);
    }
    
    
    /**
     * handler for event(s): editor_Workflow_Default#[doView, doEdit]
     * 
     * Writes runtimeOptions.termTagger.segmentsPerCall for use in ExtJS
     * into JsVar Editor.data.plugins.termTagger.segmentsPerCall
     * 
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
        error_log('function called: ' . get_class($this) . '->' . __FUNCTION__);
        
        // TODO
        //$editor_Worker_TermLoader->queue();
    }
    
    
    /**
     * handler for event: editor_Models_Segment#beforeSave
     * 
     * Re-TermTagg the (modified) segment-text.
     */
    public function handleBeforeSegmentSave(Zend_EventManager_Event $event) {
        $segment = $event->getParam('model');
        /* @var $segment editor_Models_Segment */
        //$taskGuid = $segment->getTaskGuid();
        //error_log(__CLASS__.' -> '.__FUNCTION__.' $taskGuid: '.$taskGuid);
        
        // TODO how to detect change/modification in segment-text?? $segment->isModified(); is not the correct result.
        // TODO only if change/modification is detected
        //      AND if task has tbx
        //      => call Worker_TermTagger->run()
        
        // FIXME Liste mit Segment Daten dynamisieren!?!
        $dataElement = array(   'id' => $segment->getId(),
                                'source' => $segment->getSource(),
                                'targetEdit' => $segment->getTargetEdit());
        $data[] = $dataElement;
        
        //error_log(print_r($data, true));
        $taskGuid = $segment->getTaskGuid();
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTagger');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTagger */
        if (!$worker->init($taskGuid, array('segmentData' => $data, 'resourcePool' => 'gui'))) {
            //error_log(__CLASS__.' -> '.__FUNCTION__.' Worker could not be initialized');
            return false;
        }
        
        // #1 run immediately
        if (!$worker->run()) {return false;};
        
        // #2 run from queue (mutex-save)
        //$worker->queue(); return;
        //if (!$worker->runQueued()) {return false;}
        
        
        $result = $worker->getResult();
        //error_log(__CLASS__.' -> '.__FUNCTION__.' Result: '.print_r($result, true));
        $tempTaggedText = $result[0]['targetEdit'];
        $segment->setTargetEdit($tempTaggedText);
        //$segment->setTextTagged = true;
        
        return;
        
        
        // TEST TEST TEST TEST
        // Demonstration of starting a worker that was rebuild(instanciated) from a worker-model
        $worker->queue(); // just to save the upper worker into the queue (DB-table LEK_worker)
        
        $tempModel = $worker->getModel();
        $worker2 = ZfExtended_Worker_Abstract::instanceByModel($tempModel);
        /* @var $worker2 editor_Plugins_TermTagger_Worker_TermTagger */
        
        if (!$worker2) {
            error_log(__CLASS__.' -> '.__FUNCTION__.' Worker2 could not be initialized');
            return false;
        }
        $worker2->runQueued();
        $result = $worker2->getResult();
        error_log(__CLASS__.' -> '.__FUNCTION__.': '.print_r($result, true));
    }
    
    
    /**
     * handler for test-events: IndexController#beforeStephanAction
     */
    public function handleTest(Zend_EventManager_Event $event) {
        //$segment = $event->getParam('model');
        /* @var $segment editor_Models_Segment */
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $segment: '.print_r($segment->getDataObject(), true));
        
        $request = ZfExtended_Factory::get('Zend_Controller_Request_Http');
        /* @var $request Zend_Controller_Request_Http */
        
        if ($request->getParam('startTest_1') == 1) {
            $this->test_1();
        }
        
        if ($request->getParam('startTest_2')) {
            $this->test_2();
        }
        
        if ($request->getParam('startTest_3')) {
            $this->test_3();
        }
        
        return false;
    }
    
    private function test_1() {
        error_log(__CLASS__.' -> '.__FUNCTION__);
        
        $workerQueue = ZfExtended_Factory::get('ZfExtended_Worker_Queue');
        /* @var $workerQueue ZfExtended_Worker_Queue */
        $workerQueue->process();
    }
    
    private function test_2() {
        error_log(__CLASS__.' -> '.__FUNCTION__);
        
        $trigger = ZfExtended_Factory::get('ZfExtended_Worker_TriggerByHttp');
        /* @var $trigger ZfExtended_Worker_TriggerByHttp */
        $trigger->triggerWorker(23, '5462000c27cc39.07467709');
        
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; Liste-Queued: '.print_r($workerListQueued, true));
    }
    
    
    private function test_3() {
        error_log(__CLASS__.' -> '.__FUNCTION__);
        
        //$workerModel = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $workerModel ZfExtended_Models_Worker */
        //$workerListSlotsCount = $workerModel->getListSlotsCount('TermTagger_default');
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; Liste-Resource: '.print_r($workerListSlotsCount, true));
        
        //$workerModel->wakeupScheduled('{10ea5327-8257-4f4e-abf0-8063e9878b17}');
        
        $termtaggerService = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $termtaggerService editor_Plugins_TermTagger_Service */
        //$termtaggerService->test();
        //$termtaggerService->test_2();
        $termtaggerService->testTagging();
        return;
        
        
        $config = Zend_Registry::get('config');
        $defaultServers = $config->runtimeOptions->termTagger->url->default->toArray();
        $url = $defaultServers[array_rand($defaultServers)];
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; Teste TermTagger-Server $url: '.$url.'; Ergebnis: '.$termtaggerService->testServerUrl($url));
        //$termtaggerService->ping($url, rand(10000000, 99999999));
        //$response = $termtaggerService->openFetchIds($url, 'a300e1140d20e0ac18673d6790e69e0b', '/Users/sb/Desktop/_MittagQI/TRANSLATE-22/TermTagger-Server/{C1D11C25-45D2-11D0-B0E2-444553540203}.tbx');
        $response = $termtaggerService->openFetchIds($url, rand(10000000, 99999999), '/Users/sb/Desktop/_MittagQI/TRANSLATE-22/TermTagger-Server/Test_2.tbx');
        error_log(__CLASS__.' -> '.__FUNCTION__.'; $response: '.$response);
    }
}
