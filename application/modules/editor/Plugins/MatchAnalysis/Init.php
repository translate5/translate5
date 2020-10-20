<?php
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_MatchAnalysis_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginMatchAnalysisMatchAnalysis' => 'Editor.plugins.MatchAnalysis.controller.MatchAnalysis'
    );
    
    protected $localePath = 'locales';
    
    /***
     * 
     * @var array
     */
    protected $assocs=[];
    
    /***
     * Langauge resources which are supporting batch query
     * @var array
     */
    protected $batchAssocs = [];
    
    public function getFrontendControllers() {
        $result = array();
        $userSession = new Zend_Session_Namespace('user');
        if(empty($userSession) || empty($userSession->data)) {
            return $result;
        }
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if(!$acl->has('frontend')) {
            return $result;
        }
        foreach($this->frontendControllers as $right => $controller) {
            if($acl->isInAllowedRoles($userSession->data->roles, 'frontend', $right)) {
                $result[] = $controller;
            }
        }
        return $result;
    }
    
    /**
     * Initialize the plugn "Match Analysis"
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        $this->addController('MatchAnalysisController');
        $this->initEvents();
        $this->initRoutes();
    }
    
    /**
     * define all event listener
     */
    protected function initEvents() {
        //$this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleOnAfterImport'));
        //$this->eventManager->attach('Editor_SegmentController', 'afterPutAction', array($this, 'startTestCode'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        
        $this->eventManager->attach('editor_TaskController', 'analysisOperation', array($this, 'handleOnAnalysisOperation'));
        $this->eventManager->attach('editor_TaskController', 'pretranslationOperation', array($this, 'handleOnPretranslationOperation'));
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    public function handleOnAnalysisOperation(Zend_EventManager_Event $event){
        //if the task is in import state -> queue the worker, do not pretranslate
        //if the task is allready imported -> run the analysis directly, do not pretranslate
        $this->handleOperation($event);
    }
    
    public function handleOnPretranslationOperation(Zend_EventManager_Event $event){
        //if the task is in import state -> queue the worker, set pretranslate to true in the worker and from the worker in the analysis
        //if the task is allready imported -> run the analysis directly, set pretranslate to true
        $this->handleOperation($event, true);
    }
    
    /***
     * Queue the match analysis worker
     * 
     * @param string $taskGuid
     * @param bool $pretranlsate
     * @param array $eventParams
     * @return void|boolean
     */
    protected function queueAnalysis($taskGuid, $eventParams = []) {
        if(!$this->hasAssoc($taskGuid)){
            //you can not run analysis without resources to be associated to the task
            return false;
        }
        $valid=$this->checkLanguageResources($taskGuid);
        if(empty($valid)){
            $task=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            $this->addWarn($task,'MatchAnalysis Plug-In: No valid analysable language resources found.',['invalid'=>print_r($this->assocs,1)]);
            $this->lockAndUnlockTask($task);
            return false;
        }
        
        //enable bath query via config
        $eventParams['batchQuery'] = (boolean)$this->config->enableBatchQuery;
        
        $parentWorkerId=null;
        if(!empty($this->batchAssocs) && $eventParams['batchQuery']){
            $parentWorkerId=$this->queueBatchWorker($taskGuid, $eventParams);
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Worker');
        /* @var $worker editor_Plugins_MatchAnalysis_Worker */
        
        $user = new Zend_Session_Namespace('user');
        $eventParams['userGuid']=$user->data->userGuid;
        $eventParams['userName']=$user->data->userName;
        
        // init worker and queue it
        if (!$worker->init($taskGuid, $eventParams)) {
            $task=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            $this->addWarn($task,'MatchAnalysis-Error on worker init(). Worker could not be initialized');
            $this->lockAndUnlockTask($task);
            return false;
        }
        
        if(empty($parentWorkerId)){
            $parent=ZfExtended_Factory::get('ZfExtended_Models_Worker');
            /* @var $parent ZfExtended_Models_Worker */
            $result=$parent->loadByState("editor_Models_Import_Worker", ZfExtended_Models_Worker::STATE_PREPARE,$taskGuid);
            if(!empty($result)){
                $parentWorkerId=$result[0]['id'];
            }
        }
        
        $worker->queue($parentWorkerId);
        return true;
    }
    
    /***
     * Operation action handler. Run analysis and pretranslate if $pretranslate is true.
     * 
     * @param Zend_EventManager_Event $event
     * @param bool $pretranlsate
     */
    protected function handleOperation(Zend_EventManager_Event $event, $pretranslate = false){
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task */
        $params = $event->getParam('params');
        
        settype($params['internalFuzzy'], 'boolean');
        settype($params['pretranslateMatchrate'], 'integer');
        settype($params['pretranslateTmAndTerm'], 'boolean');
        settype($params['pretranslateMt'], 'boolean');
        settype($params['termtaggerSegment'], 'boolean');
        settype($params['isTaskImport'], 'boolean');
        
        $params['pretranslate'] = $pretranslate;
        
        $taskGuids=[$task->getTaskGuid()];
        //if the requested operation is from project, queue analysis for each project task
        if($task->isProject()){
            $projects=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $projects editor_Models_Task */
            $projects=$projects->loadProjectTasks($task->getProjectId(),true);
            $taskGuids=array_column($projects, 'taskGuid');
        }
        
        foreach ($taskGuids as $taskGuid){
            $this->queueAnalysis($taskGuid,$params);
        }
    }
    
    /***
     * For each batch supported connector queue one batch worker
     * @param string $taskGuid
     * @param array $eventParams
     * @return boolean|NULL|number
     */
    protected function queueBatchWorker(string $taskGuid,array $eventParams){
        $parentWorkerId = null;
        foreach ($this->batchAssocs as $batchConnector){
            $batchWorker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_BatchWorker');
            /* @var $batchWorker editor_Plugins_MatchAnalysis_BatchWorker */
            
            if (!$batchWorker->init($taskGuid, ['connector'=>$batchConnector])) {
                $task=ZfExtended_Factory::get('editor_Models_Task');
                /* @var $task editor_Models_Task */
                $task->loadByTaskGuid($taskGuid);
                $this->addWarn($task,'MatchAnalysis-Error on batchWorker init(). Worker could not be initialized');
                $this->lockAndUnlockTask($task);
                return false;
            }
            
            if(empty($parentWorkerId)){
                $parent=ZfExtended_Factory::get('ZfExtended_Models_Worker');
                /* @var $parent ZfExtended_Models_Worker */
                $result=$parent->loadByState("editor_Models_Import_Worker", ZfExtended_Models_Worker::STATE_PREPARE,$taskGuid);
                $parentWorkerId=null;
                if(!empty($result)){
                    $parentWorkerId=$result[0]['id'];
                }
            }
            $parentWorkerId=$batchWorker->queue($parentWorkerId);
        }
        return $parentWorkerId;
    }

    /***
     * Check if the given task has associated language resources
     * @param string $taskGuid
     * @return boolean
     */
    protected function hasAssoc(string $taskGuid){
        $languageResources=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        $this->assocs=$languageResources->loadByAssociatedTaskGuid($taskGuid);
        return !empty($this->assocs);
    }
    
    /***
     * Check if the current associated language resources can be used for analysis
     * @param string $taskGuid
     * @return array
     */
    protected function checkLanguageResources(string $taskGuid){
        
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $valid=[];
        foreach ($this->assocs as $assoc){
            $languageresource=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageresource editor_Models_LanguageResources_LanguageResource  */
            
            $languageresource->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $resource=$manager->getResource($languageresource);
            
            $connector=$manager->getConnector($languageresource,$task->getSourceLang(),$task->getTargetLang());
            /* @var $connector editor_Services_Connector */
            //collect all connectors which are supporting batch query
            if($connector->isBatchQuery()){
                $this->batchAssocs[]=clone $connector;
            }
            
            //analysable language resource is found
            if(!empty($resource) && $resource->getAnalysable()){
                $valid[]=$assoc;
            }
            
        }
        return $valid;
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_matchanalysis_matchanalysis',
                ),
        ));
        $r->addRoute('plugins_matchanalysis_restdefault', $restRoute);
        
        $exportAnalysis = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_matchanalysis_matchanalysis/export',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_matchanalysis_matchanalysis',
                        'action' => 'export'
                ));
        $r->addRoute('plugins_matchanalysis_export', $exportAnalysis);
    }
    
    /***
     * Log analysis warning
     * @param string $taskGuid
     * @param array $extra
     * @param string $message
     */
    protected function addWarn(editor_Models_Task $task,string $message,array $extra=[]) {
        $extra['task']=$task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100',$message,$extra);
    }

    /***
     * Lock the given task with matchanalysis state and unlock the task with the original state.
     * When the task state is changed, the message bus will notify the frontent.
     * @param editor_Models_Task $task
     */
    protected function lockAndUnlockTask(editor_Models_Task $task) {
        $taskOldState = $task->getState();
        //lock the task dedicated for analysis
        if($task->lock(NOW_ISO, editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS)) {
            //lock the task while match analysis are running
            $newState=editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS;
            $task->setState(editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS);
            $task->save();
        }
        //unlock the state
        if(!empty($newState)){
            $task->setState($taskOldState);
            $task->save();
        }
        $task->unlock();
    }
}
