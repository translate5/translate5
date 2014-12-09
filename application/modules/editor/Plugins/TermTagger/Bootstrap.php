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
    
    /**
     * Fieldname of the source-field of this task
     * @var string
     */
    private $sourceFieldName = '';
    
    /**
     * Fieldname of the source-field of this task if the task is editable
     * @var string
     */
    private $sourceFieldNameOriginal = '';
    
    
    /**
     * Two corresponding array to hold replaced tags.
     * Tags must be replaced in every text-element before send to the TermTagger-Server,
     * because TermTagger can not handle with already TermTagged-text.
     */
    private $replacedTagsNeedles = array();
    private $replacedTagsReplacements = array();
    
    /**
     * Holds a counter for replacedTags to make needles unic
     * @var integer
     */
    private $replaceCounter = 1;
    
    
    public function __construct() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log', array(false));

        if(!$this->assertConfig()) {
            return false;
        }
        
        // event-listeners
        $this->staticEvents = Zend_EventManager_StaticEventManager::getInstance();
        $this->staticEvents->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterTaskImport'),100);
        $this->staticEvents->attach('editor_Models_Import_MetaData', 'importMetaData', array($this, 'handleImportMeta'));
        $this->staticEvents->attach('Editor_IndexController', 'afterIndexAction', array($this, 'handleAfterIndex'));
        $this->staticEvents->attach('editor_Workflow_Default', array('doView', 'doEdit'), array($this, 'handleAfterTaskOpen'));
        //$this->staticEvents->attach('editor_Models_Segment', 'beforeSave', array($this, 'handleBeforeSegmentSave'));
        $this->staticEvents->attach('Editor_SegmentController', 'beforePutSave', array($this, 'handleBeforePutSave'));
    }
    
    /**
     * Invokes to the meta file parsing of task, adds TBX parsing
     * @param Zend_EventManager_Event $event
     */
    public function handleImportMeta(Zend_EventManager_Event $event) {
        $meta = $event->getParam('metaImporter');
        /* @var $meta editor_Models_Import_MetaData */
        $importer = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        $meta->addImporter($importer);
    }
    
    protected function assertConfig() {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->url;

        if (!isset($c->default) || !isset($c->import) || !isset($c->gui)) {
            $this->log->logError('Plugin TermTagger URL config default, import or gui not defined',
                                 'One of the required config-settings default, import or gui under runtimeOptions.termTagger.url is not defined in configuration.');
            return false;
        }
        
        $defaultUrl = $c->default->toArray();
        if (empty($defaultUrl)) {
            $this->log->logError('Plugin TermTagger config not set',
                                 'The required config-setting runtimeOptions.termTagger.url.default is not set in configuration. Value is empty');
            return false;
        }
        return true;
    }
    
    public function handleAfterTaskImport(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        if (!$task->getTerminologie()) {
            return;
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        // Create segments_meta-field 'termtagState' if not exists
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
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
     * 
     * Writes runtimeOptions.termTagger.segmentsPerCall for use in ExtJS
     * into JsVar Editor.data.plugins.termTagger.segmentsPerCall
     * 
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterIndex(Zend_EventManager_Event $event) {
        //error_log('function called: ' . get_class($this) . '->' . __FUNCTION__);
        $params = $event->getParams();
        $view = $params[0];
        
        $config = Zend_Registry::get('config');
        $termTaggerSegmentsPerCall = $config->runtimeOptions->termTagger->segmentsPerCall;
        
        $view->Php2JsVars()->set('plugins.termTagger.segmentsPerCall', $termTaggerSegmentsPerCall);
    }
    
    /**
     * handler for event(s): editor_Workflow_Default#[doView, doEdit]
     * 
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
        //error_log('function called: ' . get_class($this) . '->' . __FUNCTION__);
    }
    
    
    /**
     * Re-TermTag the (modified) segment-text.
     */
    public function handleBeforePutSave(Zend_EventManager_Event $event) {
        $segment = $event->getParam('model');
        /* @var $segment editor_Models_Segment */
        $taskGuid = $segment->getTaskGuid();
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        // stop if task has no terminologie
        if (!$task->getTerminologie()) {
            return;
        }
        
        $serverCommunication = $this->fillServerCommunication($task, $segment);
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTagger');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTagger */
        if (!$worker->init($taskGuid, array('serverCommunication' => $serverCommunication, 'resourcePool' => 'gui'))) {
            $this->log('TermTagger-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        
        if (!$worker->run()) {
            $messages = Zend_Registry::get('rest_messages');
            /* @var $messages ZfExtended_Models_Messages */
            $messages->addError('Terme des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden.');
            return false;
        }
        
        $results = $worker->getResult();
        $sourceTextTagged = false;
        foreach ($results as $result) {
            if ($result->field == 'SourceOriginal') {
                $segment->set($this->sourceFieldNameOriginal, $this->decodeText($result->source));
                continue;
            }
            
            if (!$sourceTextTagged) {
                $segment->set($this->sourceFieldName, $this->decodeText($result->source));
                $sourceTextTagged = true;
            }
            
            $segment->set($result->field, $this->decodeText($result->target));
        }
        
        return true;
    }
    
    /**
     * inclusive all fields of the provided $segment
     * Creates a ServerCommunication-Object initialized with $task
     * 
     * @param editor_Models_Task $task
     * @param editor_Models_Segment $segment
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function fillServerCommunication (editor_Models_Task $task, editor_Models_Segment $segment) {
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication', array($task));
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($task->getTaskGuid());
        
        $this->sourceFieldName = $fieldManager->getFirstSourceName();
        $sourceText = $segment->get($this->sourceFieldName);
        
        if ($task->getEnableSourceEditing()) {
            $this->sourceFieldNameOriginal = $this->sourceFieldName;
            $sourceTextOriginal = $sourceText;
            $this->sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $sourceText = $segment->get($this->sourceFieldName);
        }
        
        $fields = $fieldManager->getFieldList();
        $firstField = true;
        foreach ($fields as $field) {
            if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                continue;
            }
            
            $targetFieldName = $fieldManager->getEditIndex($field->name);
            
            // if source is editable compare original Source with first targetField
            if ($firstField && $task->getEnableSourceEditing()) {
                $serverCommunication->addSegment($segment->getId(), 'SourceOriginal', $this->encodeText($sourceTextOriginal), $this->encodeText($segment->get($targetFieldName)));
                $firstField = false;
            }
            
            $serverCommunication->addSegment($segment->getId(), $targetFieldName, $this->encodeText($sourceText), $this->encodeText($segment->get($targetFieldName)));
        }
        
        return $serverCommunication;
    }
    
    
    
    private function encodeText($text) {
        //return $text;
        
        $matchContentRegExp = '/<div[^>]+class="(open|close|single).*?".*?\/div>/is';
        
        preg_match_all($matchContentRegExp, $text, $tempMatches);
        
        if (empty($tempMatches)) {
            return $text;
        }
        $textOriginal = $text;
        
        //error_log(__CLASS__.'->'.__FUNCTION__.'; $tempMatches: '.print_r($tempMatches, true));
        foreach ($tempMatches[0] as $match) {
            $needle = '<img class="content-tag" src="'.$this->replaceCounter++.'" alt="TaggingError" />';
            $this->replacedTagsNeedles[] = $needle;
            $this->replacedTagsReplacements[] = $match;
            
            $text = str_replace($match, $needle, $text);
        }
        $text = preg_replace('/<div[^>]+>/is', '', $text);
        $text = preg_replace('/<\/div>/', '', $text);
        
        //error_log(__CLASS__.'->'.__FUNCTION__.'; '."\n".$textOriginal.' => '."\n".$text."\n\n");
        error_log(__CLASS__.'->'.__FUNCTION__.'; '.$text."\n\n");
        return $text;
    }
    
    private function decodeText($text) {
        //return $text;
        
        if (empty($this->replacedTagsNeedles)) {
            return $text;
        }
        $textOriginal = $text;
        //error_log(__CLASS__.'->'.__FUNCTION__.'; Replacements: '.print_r(array_merge($this->replacedTagsNeedles, $this->replacedTagsReplacements), true));
        $text = str_replace($this->replacedTagsNeedles, $this->replacedTagsReplacements, $text);
        
        error_log(__CLASS__.'->'.__FUNCTION__.'; '."\n".$textOriginal.' => '."\n".$text."\n\n");
        return $text;
    }
    
}
