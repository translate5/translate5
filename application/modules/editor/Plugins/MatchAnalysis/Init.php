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
        'pluginMatchAnalysisMatchAnalysis' => 'Editor.plugins.MatchAnalysis.controller.MatchAnalysis',
        'pluginMatchAnalysisGridPrinter' => 'Editor.plugins.MatchAnalysis.controller.GridPrinter',
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
    }
    
    
    /***
     * After task import event handler
     * @param Zend_EventManager_Event $event
     */
    public function handleOnAfterImport(Zend_EventManager_Event $event) {
        $parentWorkerId = $event->getParam('parentWorkerId');
        $task = $event->getParam('task');
        $taskGuid=$task->getTaskGuid();
        
        $worker = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Worker');
        /* @var $worker editor_Plugins_MatchAnalysis_Worker */
        
        $params=[];
        
        // init worker and queue it
        if (!$worker->init($taskGuid, $params)) {
            $this->log->logError('MatchAnalysis-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($parentWorkerId);
    }
    
    public function startTestCode(Zend_EventManager_Event $event){
        $taskGuid=$event->getParam('entity')->getTaskGuid();
        
        $service=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Analysis');
        /* @var $service editor_Plugins_MatchAnalysis_Analysis */
        
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        
        $task->loadByTaskGuid($taskGuid);
        $service->setTask($task);
        
        $service->calculateMatchrate();
    }
    
    public function checkTaskAssoc($taskGuid){
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
    }
}
