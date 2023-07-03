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

use MittagQI\Translate5\Cronjob\CronEventTrigger;
use MittagQI\Translate5\Plugins\TermTagger\Processor\RecalcTransFound;
use MittagQI\Translate5\Plugins\TermTagger\Service;
use MittagQI\Translate5\Terminology\CleanupCollection;

/**
 * Initial Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Bootstrap extends ZfExtended_Plugin_Abstract {

    protected static string $description = 'Provides term-tagging';
    protected static bool $enabledByDefault = true;
    protected static bool $activateForTests = true;

    /**
     * The services we use
     * @var string[]
     */
    protected static array $services = [
        'termtagger' => Service::class
    ];

    /**
     * @var ZfExtended_Logger
     */
    protected $log;

    /**
     * @var RecalcTransFound
     */
    private $markTransFound = null;

    /**
     * @var array
     */
    protected $frontendControllers = array(
        'pluginTermTaggerMain' => 'Editor.plugins.TermTagger.controller.Main'
    );
    
    public function init() {
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.terminology');

        if(!$this->assertConfig()) {
            return false;
        }

        // Adds our Quality Provider to the global Quality Manager
        editor_Segment_Quality_Manager::registerProvider(editor_Plugins_TermTagger_QualityProvider::class);

        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));

        // event-listeners
        $this->eventManager->attach('editor_Models_Import_MetaData', 'importMetaData', array($this, 'handleImportMeta'));
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'termtaggerStateHandler'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'beforeSaveAlike', array($this, 'handleBeforeSaveAlike'));
        
        $this->eventManager->attach('editor_LanguageresourcetaskassocController', 'afterPost#TermCollection', array($this, 'handleAfterTermCollectionAssocChange'));
        $this->eventManager->attach('editor_LanguageresourcetaskassocController', 'afterDelete#TermCollection', array($this, 'handleAfterTermCollectionAssocChange'));
        
        //checks if the term taggers are available.
        $this->eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', array($this, 'handleTermTaggerCheck'));

        $this->eventManager->attach('editor_ConfigController', 'afterIndexAction', [$this, 'handleAfterConfigIndexAction']);
        $this->eventManager->attach('Editor_SegmentController', 'afterIndexAction', [$this, 'handleAfterSegmentIndex']);

        $this->eventManager->attach(
            CronEventTrigger::class,
            CronEventTrigger::DAILY,
            [$this, 'handleAfterDailyAction']
        );
    }

    /***
     * Cron controller daily action
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterDailyAction(Zend_EventManager_Event $event): void
    {
        $collectionModel = ZfExtended_Factory::get(editor_Models_TermCollection_TermCollection::class);
        $collections = $collectionModel->loadAllEntities();

        foreach ($collections as $collection){
            $cleanup = ZfExtended_Factory::get(CleanupCollection::class,[
                $collection
            ]);
            $cleanup->checkAndClean();
        }
    }

    /***
     * @param Zend_EventManager_Event $event
     * @return void
     */
    public function initJsTranslations(Zend_EventManager_Event $event): void
    {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }

    /***
     * @param Zend_EventManager_Event $event
     * @return void
     */
    public function injectFrontendConfig(Zend_EventManager_Event $event): void
    {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
    }

    /**
     * Append spellcheck data for each segment within segments store data
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterSegmentIndex(Zend_EventManager_Event $event) {

        // Get array of segment ids
        $view = $event->getParam('view');
        $segmentIds = array_column($view->rows, 'id');

        // Get [segmentId => termTaggerData] pairs
        $segmentTermTaggerDataById = ZfExtended_Factory
            ::get('editor_Models_SegmentQuality')
            ->getTermTaggerData($segmentIds);

        // Apply to response
        foreach ($view->rows as &$row) {
            $row['termTagger'] = $segmentTermTaggerDataById[$row['id']];
        }
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
        $state = $this->getService('termtagger')->getServiceState();
        if(!$state->runningAll) {
            $serverList = [];
            foreach($state->running as $url => $stat) {
                $serverList[] = "\n".$url . ': '. ($stat ? 'ONLINE': 'OFFLINE!');
            }
            $this->log->error('E1125', 'TermTagger DOWN: one or more configured TermTagger instances are not available: {serverList}', [
                'serverList' => join('; ', $serverList),
                'serverStatus' => $state,
            ]);
        }
    }

    /**
     * Adds the termtagger state to the general state handler
     * @param Zend_EventManager_Event $event
     * @throws ZfExtended_Exception
     */
    public function termtaggerStateHandler(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->termtagger = $this->getService('termtagger')->getServiceState(false);
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
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */

        // disable when source/target language similar, see TRANSLATE-2373
        if($task->isSourceAndTargetLanguageSimilar()){
            return;
        }
        
        // take over source original only for non editing source, see therefore TRANSLATE-549
        // Attention for alikes and if source is editable:
        //   - the whole content (including term trans[Not]Found info) must be changed in the editable field,
        //     this is done in the AlikeController
        //   - in the original only the transFound infor has to be updated, this is done here
        
        // lazy instanciation of markTransFound
        if(empty($this->markTransFound)) {
            $task = editor_ModelInstances::taskByGuid($masterSegment->getTaskGuid());
            $this->markTransFound = new RecalcTransFound($task);
        }
        $sourceOrig = $alikeSegment->getSource();
        $targetEdit = $alikeSegment->getTargetEdit();
        $alikeSegment->setSource($this->markTransFound->recalc($sourceOrig, $targetEdit));
    }
}
