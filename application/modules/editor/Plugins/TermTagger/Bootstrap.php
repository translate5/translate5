<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    const TASK_STATE = 'termtagging';

    protected static $description = 'Provides term-tagging';
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    /**
     * @var editor_Plugins_TermTagger_RecalcTransFound
     */
    private $markTransFound = null;
    
    public function init() {
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.terminology');

        if(!$this->assertConfig()) {
            return false;
        }
        // Adds our Quality Provider to the global Quality Manager
        editor_Segment_Quality_Manager::registerProvider('editor_Plugins_TermTagger_QualityProvider');
        
        // event-listeners
        $this->eventManager->attach('editor_Models_Import_SegmentProcessor_Review', 'process', array($this, 'handleSegmentImportProcess'));
        $this->eventManager->attach('editor_Models_Import_MetaData', 'importMetaData', array($this, 'handleImportMeta'));
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'termtaggerStateHandler'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'beforeSaveAlike', array($this, 'handleBeforeSaveAlike'));
        
        $this->eventManager->attach('editor_LanguageresourcetaskassocController', 'afterPost#TermCollection', array($this, 'handleAfterTermCollectionAssocChange'));
        $this->eventManager->attach('editor_LanguageresourcetaskassocController', 'afterDelete#TermCollection', array($this, 'handleAfterTermCollectionAssocChange'));
        
        //trigger the check also if the default customer is assigned in the task post action
        $this->eventManager->attach('editor_TaskController', 'afterPostAction', array($this, 'handleAfterTermCollectionAssocChange'));
        
        //checks if the term taggers are available.
        $this->eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', array($this, 'handleTermTaggerCheck'));

        $this->eventManager->attach('editor_ConfigController', 'afterIndexAction', [$this, 'handleAfterConfigIndexAction']);

        $this->eventManager->attach('editor_TaskController', 'tagtermsOperation', [$this, 'handleTagtermsOperation']);
    }

    /**
     * update defaultAdministrativeStatus defaults
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterConfigIndexAction(Zend_EventManager_Event $event) {
        $rows = $event->getParam('view')->rows ?? [];
        if(empty($rows)){
            return;
        }

        //find the defaultAdministrativeStatus config
        $toUpdate = array_search('runtimeOptions.tbx.defaultAdministrativeStatus', array_column($rows, 'name'));

        if(empty($toUpdate)){
            return;
        }
        $config = $rows[$toUpdate];

        /* @var $termNoteStatus editor_Models_Terminology_TermStatus */
        $termNoteStatus = ZfExtended_Factory::get('editor_Models_Terminology_TermStatus');


        $defaults = implode(',',$termNoteStatus->getAdministrativeStatusValues());
        //the config has the same values as defaults
        if($config['defaults'] == $defaults){
            return;
        }

        $model = ZfExtended_Factory::get('editor_Models_Config');
        /* @var $model editor_Models_Config */
        $model->loadByName($config['name']);
        $model->setDefaults($defaults);
        $model->save();

        //update the view rows
        $event->getParam('view')->rows[$toUpdate]['defaults'] = $defaults;
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
            $attributes->customMetaAttributes['termtagState'] = editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_IGNORE;
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
        
        $entity = $event->getParam('entity');
        $entityGuid = $entity->getTaskGuid();

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($entityGuid);
        
        $taskGuids = [$task->getTaskGuid()];
        //check if the current task is project.
        if($task->isProject()){
            //collect all project tasks to check the terminologie
            $taskGuids = $task->loadProjectTasks($task->getProjectId(),true);
            $taskGuids = array_column($taskGuids, 'taskGuid');
        }
        foreach ($taskGuids as $taskGuid) {
            $this->removeTerminologieFile($taskGuid);
            
            $task=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            //update the terminologie flag, based on if there is a termcollection
            //as language resource associated to the task
            $task->updateIsTerminologieFlag($taskGuid);
        }
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
     * is called periodically to check the term tagger instances
     */
    public function handleTermTaggerCheck() {
        $status = $this->termtaggerState();
        $serverList = [];
        $offline = [];
        foreach($status->running as $url => $stat) {
            $serverList[] = "\n".$url . ': '. ($stat ? 'ONLINE': 'OFFLINE!');
            if(!$stat) {
                $offline[] = $url;
            }
        }
        editor_Plugins_TermTagger_Configuration::saveDownListToMemCache($offline);
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
        $allUrls = array_unique(call_user_func_array('array_merge', array_values((array)$termtagger->configured)));
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

    /**
     * Operation action handler. Run termtagging specific for a task
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleTagtermsOperation(Zend_EventManager_Event $event){
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task */

        $initialTaskState = $task->getState();
        $task->checkStateAllowsActions();
        if(!$task->lock(NOW_ISO, self::TASK_STATE)) {
            return;
        }

        $task->setState(self::TASK_STATE);
        $task->save();

        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_SetTaskToOpen');
        /* @var $worker editor_Plugins_TermTagger_Worker_SetTaskToOpen */
        $worker->init($task->getTaskGuid(),['initialTaskState' => $initialTaskState]);
        $parentId = $worker->queue(0, null, false);
        editor_Segment_Quality_Manager::instance()->prepareTagTerms($task, $parentId);
    }
}
