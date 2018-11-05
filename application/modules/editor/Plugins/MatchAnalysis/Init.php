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
        if(ZfExtended_Debug::hasLevel('plugin', 'MatchAnalysis')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
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
        $view->headLink()->appendStylesheet(APPLICATION_RUNDIR.'/editor/plugins/resources/matchAnalysis/plugin.css');
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /***
     * Queue the match analysis worker
     * 
     * @param string $taskGuid
     * @param boolean $pretranlsate
     * @param array $eventParams
     * @return void|boolean
     */
    public function queueAnalysis($taskGuid,$pretranlsate=false,$eventParams=array()) {
        if(!$this->checkLanguageResources($taskGuid)){
            error_log("The associated language resource can not be used for analysis.");
            return;
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Worker');
        /* @var $worker editor_Plugins_MatchAnalysis_Worker */
        
        $params=[];
        
        $user = new Zend_Session_Namespace('user');
        $params['userGuid']=$user->data->userGuid;
        $params['userName']=$user->data->userName;
        
        //pretranslate flag
        if($pretranlsate){
            $params['pretranslate']=$pretranlsate;
        }
        
        if(isset($eventParams['internalFuzzy'])){
            $params['internalFuzzy']=$eventParams['internalFuzzy'];
        }
        
        if(isset($eventParams['pretranslateMatchrate'])){
            $params['pretranslateMatchrate']=$eventParams['pretranslateMatchrate'];
        }
        
        // init worker and queue it
        if (!$worker->init($taskGuid, $params)) {
            error_log('MatchAnalysis-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $parent=ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result=$parent->loadByState("editor_Models_Import_Worker", ZfExtended_Models_Worker::STATE_PREPARE,$taskGuid);
        $parentWorkerId=null;
        if(!empty($result)){
            $parentWorkerId=$result[0]['id'];
        }
        $worker->queue($parentWorkerId);
    }
    
    /***
     * Run match analysis and pretranslation
     * 
     * @param editor_Models_Task $task
     * @param boolean $pretranslate
     * @param array $eventParams
     */
    public function runAnalysis(editor_Models_Task $task,$pretranslate=false,$eventParams=array()){
        
        if(!$this->checkLanguageResources($task->getTaskGuid())){
            error_log("The associated language resource can not be used for analysis.");
            return;
        }
        
        if(!$task->lock(NOW_ISO, true)) {
            error_log('Match analysis and pretranslation canot be run. The following task is in use: '.$task->getTaskName().' ('.$task->getTaskGuid().')');
            continue;
        }
        
        //lock the task while match analysis are running
        $oldState = $task->getState();
        $task->setState('matchanalysis');
        $task->save();
        
        //create new analysis
        $analysisAssoc=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc->setTaskGuid($task->getTaskGuid());
        
        //set flag for internal fuzzy usage
        if(isset($eventParams['internalFuzzy'])){
            $analysisAssoc->setInternalFuzzy(filter_var($eventParams['internalFuzzy'], FILTER_VALIDATE_BOOLEAN));
        }
        //set pretranslation matchrate used for the anlysis
        if(isset($eventParams['pretranslateMatchrate'])){
            $analysisAssoc->setPretranslateMatchrate($eventParams['pretranslateMatchrate']);
        }
        
        $analysisId=$analysisAssoc->save();
        
        try {
            $analysis=new editor_Plugins_MatchAnalysis_Analysis($task,$analysisId);
            /* @var $analysis editor_Plugins_MatchAnalysis_Analysis */
            $analysis->setPretranslate($pretranslate);
            $user = new Zend_Session_Namespace('user');
            
            if(isset($eventParams['internalFuzzy'])){
                $analysis->setInternalFuzzy($eventParams['internalFuzzy']);
            }
            
            if(isset($eventParams['pretranslateMatchrate'])){
                $analysis->setPretranslateMatchrate($eventParams['pretranslateMatchrate']);
            }
            
            if(isset($eventParams['pretranslateMt'])){
                $analysis->setPretranslateMt($eventParams['pretranslateMt']);
            }
            
            if(isset($eventParams['pretranslateTmAndTerm'])){
                $analysis->setPretranslateTmAndTerm($eventParams['pretranslateTmAndTerm']);
            }
            
            $analysis->setUserGuid($user->data->userGuid);
            $analysis->setUserName($user->data->userName);
            $analysis->calculateMatchrate();
        } catch (ZfExtended_Exception $e) {
            //error_log("Error happend on match analysis and pretranslation (taskGuid=".$task->getTaskGuid()."). Error was: ".$e->getMessage());
            error_log($e->getMessage());
            //inlock the task
            $task->setState($oldState);
            $task->save();
            $task->unlock();
            throw $e;
        }
        
        //inlock the task
        $task->setState($oldState);
        $task->save();
        $task->unlock();
    }
    
    public function handleOnAnalysisOperation(Zend_EventManager_Event $event){
        //if the task is in import state -> queue the worker, do not pretranslate
        //if the task is allready imported -> run the analysis directly, do not pretranslate
        $this->handleOperation($event);
    }
    
    
    public function handleOnPretranslationOperation(Zend_EventManager_Event $event){
        //if the task is in import state -> queue the worker, set pretranslate to true in the worker and from the worker in the analysis
        //if the task is allready imported -> run the analysis directly, set pretranslate to true
        $this->handleOperation($event,true);
    }
    
    /***
     * Operation action handler. Run analysis and pretranslate if $pretranslate is true.
     * 
     * @param Zend_EventManager_Event $event
     * @param boolean $pretranlsate
     */
    private function handleOperation(Zend_EventManager_Event $event,$pretranlsate=false){
        $task = $event->getParam('entity');
        $params=$event->getParam('params');
        
        /* @var $task editor_Models_Task */
        //if the task is in import state -> queue the worker
        if($task->getState()==editor_Models_Task::STATE_IMPORT){
            $this->queueAnalysis($task->getTaskGuid(),$pretranlsate,$params);
            return;
        }
        $this->runAnalysis($task,$pretranlsate,$params);
    }

    /***
     * Check if for the current task match resources are assigned.
     * Check if the assigned match resources are analysable
     * 
     * @param string $taskGuid
     * 
     * @return boolean
     */
    private function checkLanguageResources($taskGuid){
        $languageResources=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        
        $assocs=$languageResources->loadByAssociatedTaskGuid($taskGuid);
        
        if(empty($assocs)){
            return false;
        }
        
        $hasAnalysable=false;
        foreach ($assocs as $assoc){
            $languageresource=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageresource editor_Models_LanguageResources_LanguageResource  */
            
            $languageresource->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $resource=$manager->getResource($languageresource);
            
            //analysable language resource is found
            if($resource->getAnalysable()){
               $hasAnalysable=true; 
            }
            
        }
        
        return $hasAnalysable;
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
}
