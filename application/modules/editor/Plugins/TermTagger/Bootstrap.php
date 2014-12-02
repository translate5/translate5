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
        
        $defaultUrl = $config->runtimeOptions->termTagger->url->default->toArray();
        if (empty($defaultUrl)) {
            $this->log->logError('Plugin TermTagger config not set',
                                 'The required config-setting runtimeOptions.termTagger.url.default is not set in DB-table Zf_configuration. Value is empty');
            return false;
        }
        
        
        // event-listeners
        $this->staticEvents = Zend_EventManager_StaticEventManager::getInstance();
        $this->staticEvents->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterTaskImport'));
        $this->staticEvents->attach('Editor_IndexController', 'afterIndexAction', array($this, 'handleAfterIndex'));
        $this->staticEvents->attach('editor_Workflow_Default', array('doView', 'doEdit'), array($this, 'handleAfterTaskOpen'));
        $this->staticEvents->attach('editor_Models_Segment', 'beforeSave', array($this, 'handleBeforeSegmentSave'));
        
        // SBE: only for testing
        //$this->staticEvents->attach('Editor_SegmentController', 'beforePutSave', array($this, 'handleTest'));
        $this->staticEvents->attach('IndexController', 'beforeStephanAction', array($this, 'handleTest'));
        // SBE end of testing event-listeners
    }
    
    
    public function handleAfterTaskImport(Zend_EventManager_Event $event) {
        error_log(__CLASS__.'->'.__FUNCTION__);
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        if (!$task->getTerminologie()) {
            return;
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        // Create segments_meta-field 'termtagState' if not exists
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $tempSegement editor_Models_Segment_Meta */
        $meta->addMeta('termtagState', $meta::META_TYPE_STRING, $worker::$SEGMENT_STATE_UNTAGGED, 'Contains the TermTagger-state for this segment while importing', 36);
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), array('resourcePool' => 'import'))) {
            $this->log('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue();
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
        //error_log(__CLASS__.' -> '.__FUNCTION__.' $segment: '.print_r($segment->getDataObject(), true));
        $taskGuid = $segment->getTaskGuid();
        //error_log(__CLASS__.' -> '.__FUNCTION__.' $taskGuid: '.$taskGuid);
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        // stop if task has no terminologie
        if (!$task->getTerminologie()) {
            return;
        }
            
        
        // TODO how to detect change/modification in segment-text?? $segment->isModified(); is not the correct result.
        // TODO continue only if change/modification is detected
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication');
        /*@var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        $serverCommunication->tbxFile = $task->meta()->getTbxHash();;
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $langModel->load($task->getSourceLang());
        $serverCommunication->sourceLang = $langModel->getRfc5646();
        $langModel->load($task->getTargetLang());
        $serverCommunication->targetLang = $langModel->getRfc5646();
        
        $serverCommunication->addSegment($segment->getId(), 'target', $segment->getSource(), $segment->getTargetEdit());
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTagger');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTagger */
        if (!$worker->init($taskGuid, array('serverCommunication' => $serverCommunication, 'resourcePool' => 'gui'))) {
            $this->log('TermTagger-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        
        if (!$worker->run()) {
            $messages = Zend_Registry::get('rest_messages');
            /* @var $messages ZfExtended_Models_Messages */
            $messages->addError('Terme dieses Segments konnten nicht ausgezeichnte werden.');
            return false;
        }
        
        
        $result = $worker->getResult();
        //error_log(__CLASS__.' -> '.__FUNCTION__.' Result: '.print_r($result, true));
        $tempTaggedText = $result[0]->target;
        $segment->setTargetEdit($tempTaggedText);
        //$segment->setTextTagged = true;
        
        return true;
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
        $taskGuid = '{1288e9f4-e3b0-4dc0-8f33-c838dcb386ab}';
        
        $task = ZfExtended_Factory::get('editor_Models_task');
        /* @var $task editor_Models_task */
        $task->loadByTaskGuid($taskGuid);
        
        
        if (empty($segmentIds)) {
            return;
        }
        
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $serverCommunication: '.print_r($serverCommunication, true));
        
        
        
        
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
        //$response = $termtaggerService->openFetchIds($url, 'a300e1140d20e0ac18672d6790e69e0b', '/Users/sb/Desktop/_MittagQI/TRANSLATE-22/TermTagger-Server/{C1D11C25-45D2-11D0-B0E2-444553540203}.tbx');
        $response = $termtaggerService->open($url, 'a300e1140d20e0ac18672d6790e69e0b', file_get_contents('/Users/sb/Desktop/_MittagQI/TRANSLATE-22/TermTagger-Server/Test_2.tbx'));
        error_log(__CLASS__.' -> '.__FUNCTION__.'; $response: '.$response);
    }
}
