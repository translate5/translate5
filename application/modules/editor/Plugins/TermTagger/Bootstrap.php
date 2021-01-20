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
 * Initial Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Bootstrap extends ZfExtended_Plugin_Abstract {
    /**
     * @var ZfExtended_Logger
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
     * @var editor_Plugins_TermTagger_RecalcTransFound
     */
    private $markTransFound = null;
    
    public function init() {
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.terminology');

        if(!$this->assertConfig()) {
            return false;
        }
        
        // event-listeners
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterTaskImport'),100);
        $this->eventManager->attach('editor_Models_Import_SegmentProcessor_Review', 'process', array($this, 'handleSegmentImportProcess'));
        $this->eventManager->attach('editor_Models_Import_MetaData', 'importMetaData', array($this, 'handleImportMeta'));
        $this->eventManager->attach('editor_Models_Segment_Updater', 'beforeSegmentUpdate', array($this, 'handleBeforeSegmentUpdate'));
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'termtaggerStateHandler'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'beforeSaveAlike', array($this, 'handleBeforeSaveAlike'));
        
        $this->eventManager->attach('editor_LanguageresourcetaskassocController', 'afterPost#TermCollection', array($this, 'handleAfterTermCollectionAssocChange'));
        $this->eventManager->attach('editor_LanguageresourcetaskassocController', 'afterDelete#TermCollection', array($this, 'handleAfterTermCollectionAssocChange'));
        
        //trigger the check also if the default customer is assigned in the task post action
        $this->eventManager->attach('editor_TaskController', 'afterPostAction', array($this, 'handleAfterTermCollectionAssocChange'));
        
        //checks if the term taggers are available.
        $this->eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', array($this, 'handleTermTaggerCheck'));
    }
    
    /**
     * By default read only segments are not tagged, can be disabled via config
     * @param Zend_EventManager_Event $event
     */
    public function handleSegmentImportProcess(Zend_EventManager_Event $event) {
        $attributes = $event->getParam('segmentAttributes');
        $config = $event->getParam('config');
        
        /* @var $attributes editor_Models_Import_FileParser_SegmentAttributes */
        if(!$attributes->editable && !$config->runtimeOptions->termTagger->tagReadonlySegments) {
            $attributes->customMetaAttributes['termtagState'] = editor_Plugins_TermTagger_Worker_Abstract::SEGMENT_STATE_IGNORE;
        }
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
    
    /***
     * After post action handler in language resources task assoc
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTermCollectionAssocChange(Zend_EventManager_Event $event){
        $entity=$event->getParam('entity');
        /* @var $entity editor_Models_LanguageResources_Taskassoc */
        $this->removeTerminologieFile($entity->getTaskGuid());
        
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        //update the terminologie flag, based on if there is a termcollection
        //as language resource associated to the task
        $task->updateIsTerminologieFlag($entity->getTaskGuid());
    }
    
    /***
     * Remove the terminologie file from the disk.
     * @param string $taskGuid
     */
    private function removeTerminologieFile($taskGuid){
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        //get/check if the tbx file exist
        $tbxPath = new SplFileInfo(editor_Models_Import_TermListParser_Tbx::getTbxPath($task));
        if ($tbxPath->getPathname()!=null && file_exists($tbxPath->getPathname())){
            //Remove the file if exist. The file will be recreated on the initial try to tag a segment.
            unlink($tbxPath);
        }
        $meta = $task->meta();
        $meta->setTbxHash("");
        $meta->save();
    }
    
    protected function assertConfig() {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->url;
        if (!isset($c->default) || !isset($c->import) || !isset($c->gui)) {
            $this->log->error('E1126', 'Plugin TermTagger URL config default, import or gui not defined (check config runtimeOptions.termTagger.url)');
            return false;
        }
        
        $defaultUrl = $c->default->toArray();
        if (empty($defaultUrl)) {
            $this->log->error('E1127', 'Plugin TermTagger default server not configured: configuration is empty.');
            return false;
        }
        return true;
    }
    
    /**
     * Queues the termtagger worker after import
     *
     * @param Zend_EventManager_Event $event
     * @return void|boolean
     */
    public function handleAfterTaskImport(Zend_EventManager_Event $event) {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->switchOn->import;
        if((boolean)$c === false) {
            return;
        }
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
        $meta->addMeta('termtagState', $meta::META_TYPE_STRING, $worker::SEGMENT_STATE_UNTAGGED, 'Contains the TermTagger-state for this segment while importing', 36);
        
        $this->lockOversizedSegments($task, $meta, $config);
        
        // init worker and queue it
        $params = ['resourcePool' => 'import'];
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->error('E1128', 'TermTaggerImport Worker can not be initialized!', [
                'parameters' => $params,
            ]);
            return false;
        }
        $worker->queue($event->getParam('parentWorkerId'));
    }
    
    /**
     * Find oversized segments and mark them as oversized
     *
     * @param editor_Models_Task $task
     * @param editor_Models_Segment_Meta $meta
     * @param Zend_Config $config
     */
    protected function lockOversizedSegments(editor_Models_Task $task, editor_Models_Segment_Meta $meta, Zend_Config $config) {
        $maxWordCount = $config->runtimeOptions->termTagger->maxSegmentWordCount ?? 150;
        $meta->db->update([
            'termtagState' => editor_Plugins_TermTagger_Worker_TermTaggerImport::SEGMENT_STATE_OVERSIZE
        ], [
            'taskGuid = ?' => $task->getTaskGuid(),
            'sourceWordCount >= ?' => $maxWordCount,
        ]);
    }
    
    /**
     * Re-TermTag the (modified) segment-text.
     */
    public function handleBeforeSegmentUpdate(Zend_EventManager_Event $event) {
        $config = Zend_Registry::get('config');
        $c = $config->runtimeOptions->termTagger->switchOn->GUI;
        if((boolean)$c === false) {
            return;
        }
        
        $segment = $event->getParam('entity');
        
        /* @var $segment editor_Models_Segment */
        $taskGuid = $segment->getTaskGuid();
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        // stop if task has no terminologie
        if (!$task->getTerminologie()||!$segment->isDataModified()) {
            return;
        }

        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTagger');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTagger */
        
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        
        if($segment->meta()->getTermtagState() == $worker::SEGMENT_STATE_OVERSIZE) {
            $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden: Das Segment ist zu lang.');
            return false;
        }
        
        $serverCommunication = $this->fillServerCommunication($task, $segment);
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $params = ['serverCommunication' => $serverCommunication, 'resourcePool' => 'gui'];
        if (!$worker->init($taskGuid, $params)) {
            $this->log->error('E1128', 'TermTaggerImport Worker can not be initialized!', [
                'parameters' => $params,
            ]);
            return false;
        }
        
        if (!$worker->run()) {
            $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden.');
            return false;
        }
        
        $results = $worker->getResult();
        $sourceTextTagged = false;
        foreach ($results as $result) {
            if ($result->field == 'SourceOriginal') {
                $segment->set($this->sourceFieldNameOriginal, $result->source);
                continue;
            }
            
            if (!$sourceTextTagged) {
                $segment->set($this->sourceFieldName, $result->source);
                $sourceTextTagged = true;
            }
            
            $segment->set($result->field, $result->target);
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
                $serverCommunication->addSegment($segment->getId(), 'SourceOriginal', $sourceTextOriginal, $segment->get($targetFieldName));
                $firstField = false;
            }
            
            $serverCommunication->addSegment($segment->getId(), $targetFieldName, $sourceText, $segment->get($targetFieldName));
        }
        
        return $serverCommunication;
    }
    
    /**
     * is called periodically to check the term tagger instances
     */
    public function handleTermTaggerCheck() {
        $memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), ['automatic_serialization' => true]);
        
        $status = $this->termtaggerState();
        $serverList = [];
        $offline = [];
        foreach($status->running as $url => $stat) {
            $serverList[] = "\n".$url . ': '. ($stat ? 'ONLINE': 'OFFLINE!');
            if(!$stat) {
                $offline[] = $url;
            }
        }
        //update the block list of not available term taggers
        $memCache->save($offline, editor_Plugins_TermTagger_Worker_Abstract::TERMTAGGER_DOWN_CACHE_KEY);
        if(!$status->runningAll) {
            $this->log->error('E1125', 'TermTagger DOWN: one or more configured TermTagger instances are not available: {serverList}', [
                'serverList' => join('; ', $serverList),
                'serverStatus' => $status,
            ]);
        }
    }
    
    public function termtaggerStateHandler(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->termtagger = $this->termtaggerState();
    }
    
    /**
     * Checks if the configured termtaggers are available and returns the result as stdClass
     * @return stdClass
     */
    public function termtaggerState() {
        $testTimeout = 10;
        $termtagger = new stdClass();
        $ttService = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service', ['editor.terminology', $testTimeout, $testTimeout]);
        /* @var $ttService editor_Plugins_TermTagger_Service */
        $termtagger->configured = $ttService->getConfiguredUrls();
        $allUrls = array_unique(call_user_func_array('array_merge', (array)$termtagger->configured));
        $running = array();
        $version = array();
        $termtagger->runningAll = true;
        foreach($allUrls as $url) {
            $running[$url] = $ttService->testServerUrl($url, $version[$url]);
            $termtagger->runningAll = $running[$url] && $termtagger->runningAll;
        }
        $termtagger->running = $running;
        $termtagger->version = $version;
        return $termtagger;
    }
    
    /**
     * When using change alikes, the transFound information in the source has to be changed.
     * This is done by this handler.
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeSaveAlike(Zend_EventManager_Event $event) {
        $isSourceEditable = (boolean) $event->getParam('isSourceEditable');
        $masterSegment = $event->getParam('masterSegment');
        /* @var $masterSegment editor_Models_Segment */
        $alikeSegment = $event->getParam('alikeSegment');
        /* @var $alikeSegment editor_Models_Segment */
        
        // take over source original only for non editing source, see therefore TRANSLATE-549
        // Attention for alikes and if source is editable:
        //   - the whole content (including term trans[Not]Found info) must be changed in the editable field,
        //     this is done in the AlikeController
        //   - in the original only the transFound infor has to be updated, this is done here
        
        //lazy instanciation of markTransFound
        if(empty($this->markTransFound)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($masterSegment->getTaskGuid());
            $this->markTransFound = ZfExtended_Factory::get('editor_Plugins_TermTagger_RecalcTransFound', array($task));
        }
        $sourceOrig = $alikeSegment->getSource();
        $targetEdit = $alikeSegment->getTargetEdit();
        $alikeSegment->setSource($this->markTransFound->recalc($sourceOrig, $targetEdit));
    }
}
