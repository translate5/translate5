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
 * Initial Class of Plugin "TranslationMemory"
 * 
 * Hint: class must be named NOT Bootstrap, otherwise we will get a strange Zend Error
 */
class editor_Plugins_MatchResource_Init extends ZfExtended_Plugin_Abstract {
    
    // set as match rate type when matchrate was changed
    const MATCH_RATE_TYPE_EDITED = 'matchresourceusage';

    //set by changealike editor
    const MATCH_RATE_TYPE_EDITED_AUTO = 'matchresourceusageauto';
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginMatchResourceTaskassoc' => 'Editor.plugins.MatchResource.controller.TaskAssoc',
        'pluginMatchResourceMatchQuery' => 'Editor.plugins.MatchResource.controller.Editor',
        'pluginMatchResourceSearchQuery' => 'Editor.plugins.MatchResource.controller.Editor',
        'pluginMatchResourceOverview' => 'Editor.plugins.MatchResource.controller.TmOverview',
    );
    
    protected $localePath = 'locales';
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'MatchResource')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        
        $this->initEvents();
        $this->addController('ResourceController');
        $this->addController('TaskassocController');
        $this->addController('TmmtController');
        $this->initRoutes();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('editor_TaskController', 'afterTaskOpen', array($this, 'handleAfterTaskOpen'));
        $this->eventManager->attach('editor_TaskController', 'afterTaskClose', array($this, 'handleAfterTaskClose'));
        $this->eventManager->attach('editor_TaskController', 'afterIndexAction', array($this, 'handleAfterTaskIndexAction'));
        $this->eventManager->attach('editor_TaskController', 'afterGetAction', array($this, 'handleAfterTaskGetAction'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('Editor_SegmentController', 'beforePutSave', array($this, 'handleBeforeSegmentPut'));
        $this->eventManager->attach('Editor_SegmentController', 'afterPutAction', array($this, 'handleAfterSegmentPut'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'beforeSaveAlike', array($this, 'handleBeforeSaveAlike'));
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->Php2JsVars()->set('plugins.MatchResource.preloadedSegments', $this->getConfig()->preloadedTranslationSegments);
        $view->Php2JsVars()->set('plugins.MatchResource.matchrateTypeChangedState', self::MATCH_RATE_TYPE_EDITED);
        $view->headLink()->appendStylesheet(APPLICATION_RUNDIR.'/editor/plugins/resources/matchResource/plugin.css');
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * Handler is called after a task has been opened
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $manager->openForTask($event->getParam('task'));
    }
    
    /**
     * Handler is called after a task has been closed
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskClose(Zend_EventManager_Event $event) {
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $manager->closeForTask($event->getParam('task'));
    }
    
    /***
     * adding tmmt info to task indexAction
     * @param Zend_EventManager_Event $event
     */
     public function handleAfterTaskIndexAction(Zend_EventManager_Event $event){
        /*@var $tmmtmodel editor_Plugins_MatchResource_Models_TmMt */
         $tmmtmodel = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
         
         $taskGuids = array_column($event->getParam('view')->rows, 'taskGuid');
         if(empty($taskGuids)){
             return;
         }
         $taskassocs = array();
         
         $resultlist = $tmmtmodel->loadByAssociatedTaskGuidList($taskGuids);
         if(empty($resultlist)){
             return;
         }
         foreach ($resultlist as $res){
             if(!isset($taskassocs[$res['taskGuid']])){
                 $taskassocs[$res['taskGuid']] = array();
             }
             array_push($taskassocs[$res['taskGuid']], $res);
         }
         foreach($event->getParam('view')->rows as &$tmmt) {
             if(isset($taskassocs[$tmmt['taskGuid']])){
                 $tmmt['taskassocs'] = $taskassocs[$tmmt['taskGuid']];
             }
        }
     }
     
    /**
     * adding tmmt data to task getAction
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskGetAction(Zend_EventManager_Event $event){
        /*@var $tmmtmodel editor_Plugins_MatchResource_Models_TmMt */
         $tmmtmodel = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
         
         $taskguids =$event->getParam('view')->rows->taskGuid;
         
         $resultlist =$tmmtmodel->loadByAssociatedTaskGuidList(array($taskguids));
         $event->getParam('view')->rows->taskassocs = array();
         if(empty($resultlist)){
             return;
         }
         $event->getParam('view')->rows->taskassocs = $resultlist;
    }
     
    /**
     * Before a segment is saved, the matchrate type has to be fixed to valid value
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeSegmentPut(Zend_EventManager_Event $event) {
        $segment = $event->getParam('entity');
        /* @var $segment editor_Models_Segment */
        $givenType = $segment->getMatchRateType();
        
        //if it was a normal segment edit, without overtaking the match we have to do nothing here
        if(!$segment->isModified('matchRateType') || strpos($givenType, self::MATCH_RATE_TYPE_EDITED) !== 0) {
            return;
        }
        
        $matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        /* @var $matchrateType editor_Models_Segment_MatchRateType */
        
        $unknown = function() use ($matchrateType, $givenType, $segment){
            $matchrateType->initEdited($matchrateType::TYPE_UNKNOWN, $givenType);
            $segment->setMatchRateType((string) $matchrateType);
        };
        
        //if it was an invalid type set it to unknown
        if(! preg_match('/'.self::MATCH_RATE_TYPE_EDITED.';tmmtid=([0-9]+)/', $givenType, $matches)) {
            $unknown();
            return;
        }
        
        //load the used TMMT to get more information about it (TM or MT)
        $tmmtid = $matches[1];
        $tmmt = ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
        /* @var $tmmt editor_Plugins_MatchResource_Models_TmMt */
        try {
            $tmmt->load($tmmtid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $unknown();
            return;
        }
        
        //set the type
        $matchrateType->initEdited($tmmt->getResource()->getType());
        
        //REMINDER: this would be possible if we would know if the user edited the segment after using the TM
        //$matchrateType->add($matchrateType::TYPE_INTERACTIVE);
        
        //save the type
        $segment->setMatchRateType((string) $matchrateType);
    }
    
    /**
     * After a segment is changed we inform the services about that. What they do with this information is the service's problem.
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterSegmentPut(Zend_EventManager_Event $event) {
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $manager->updateSegment($event->getParam('entity'));
    }
    
    /**
     * When using change alikes, the transFound information in the source has to be changed.
     * This is done by this handler.
     * 
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeSaveAlike(Zend_EventManager_Event $event) {
        $alikeSegment = $event->getParam('alikeSegment');
        /* @var $alikeSegment editor_Models_Segment */
        
        $type = $alikeSegment->getMatchRateType();
        
        $matchRateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        /* @var $matchRateType editor_Models_Segment_MatchRateType */
        $matchRateType->init($type);
        
        if($matchRateType->isEdited()) {
            $matchRateType->add($matchRateType::TYPE_AUTO_PROPAGATED);
            $alikeSegment->setMatchRateType((string) $matchRateType);
        }
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_matchresource_taskassoc',
                                  'plugins_matchresource_tmmt',
                                  'plugins_matchresource_resource',
                ),
        ));
        $r->addRoute('plugins_matchresource_restdefault', $restRoute);
        
        //WARNING: Order of the route definition is important! 
        // the catchall like download route must be defined before the more specific query/search routes!
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchresource_tmmt/:id/:type',
            array(
                'module' => 'editor',
                'controller' => 'plugins_matchresource_tmmt',
                'action' => 'download'
            ));
        $r->addRoute('plugins_matchresource_download', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchresource_tmmt/:tmmtId/query',
            array(
                'module' => 'editor',
                'controller' => 'plugins_matchresource_tmmt',
                'action' => 'query'
            ));
        $r->addRoute('plugins_matchresource_query', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchresource_tmmt/:tmmtId/search',
            array(
                'module' => 'editor',
                'controller' => 'plugins_matchresource_tmmt',
                'action' => 'search'
            ));
        $r->addRoute('plugins_matchresource_search', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchresource_tmmt/:id/import',
            array(
                'module' => 'editor',
                'controller' => 'plugins_matchresource_tmmt',
                'action' => 'import'
            ));
        $r->addRoute('plugins_matchresource_import', $queryRoute);
        
        $queryRoute = new ZfExtended_Controller_RestLikeRoute(
            'editor/plugins_matchresource_tmmt/:id/tasks',
            array(
                'module' => 'editor',
                'controller' => 'plugins_matchresource_tmmt',
                'action' => 'tasks'
            ));
        $r->addRoute('plugins_matchresource_tasks', $queryRoute);
    }
}
