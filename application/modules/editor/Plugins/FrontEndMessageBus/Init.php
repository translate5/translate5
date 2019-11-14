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
 */
class editor_Plugins_FrontEndMessageBus_Init extends ZfExtended_Plugin_Abstract {
    const CHANNEL_TASK = 'task';
    const CHANNEL_CHAT = 'chat';
    
    /**
     * @var editor_Plugins_FrontEndMessageBus_Bus
     */
    protected $bus;
    
    protected $frontendControllers = array(
        'pluginFrontEndMessageBus' => 'Editor.plugins.FrontEndMessageBus.controller.MessageBus',
    );
    
    public function init() {
        $this->bus = ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Bus');
        $this->initEvents();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('editor_TaskController', 'afterTaskOpen', array($this, 'handleAfterTaskOpen'));
        $this->eventManager->attach('editor_TaskController', 'afterTaskClose', array($this, 'handleAfterTaskClose'));
        
        $this->eventManager->attach('editor_Models_TaskUserTracking', 'afterUserTrackingInsert', array($this, 'handleUpdateUserTracking'));
        
        $this->eventManager->attach('editor_TaskController', 'afterIndexAction', array($this, 'handlePing'));
        $this->eventManager->attach('Editor_SegmentController', 'afterPutAction', array($this, 'handleSegmentSave'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'afterGetAction', array($this, 'handleAlikeLoad'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'afterPutAction', array($this, 'handleAlikeSave'));
        

        // FIXME send the session id to the message bus, so that the user is known and allowed to communicate
        // → idea here: Instead listening to a login event, we just attach to the IndexController. 
        //   If the application is loaded there, we trigger a "connect" to the MessageBus
        //   Then we have the user, the sessionId and so on. 
        //   Then we have to attach to garbage cleaning and logout to remove the session
        //   additionally we should create a sync function, triggered by the periodical cron and triggerable when the MessageBus restarts.
        //   sync should just deliver all sessions with logged in users, and opened tasks (if any) to the MessageBus.
        //   Sensefull?
        $this->eventManager->attach('Editor_IndexController', 'beforeIndexAction', array($this, 'handleStartSession'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        
        //TODO test logout calls:
        $this->eventManager->attach('editor_SessionController', 'beforeDeleteAction', array($this, 'handleLogout'));
        $this->eventManager->attach('editor_SessionController', 'resyncOperation', array($this, 'handleSessionResync'));
        
        $this->eventManager->attach('LoginController', 'logoutAction', array($this, 'handleLogout'));
        
        
        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'handleApplicationState'));
    }
    
    /**
     * Checks if the configured okapi instance is reachable
     * @param Zend_EventManager_Event $event
     */
    public function handleApplicationState(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->messageBus = new stdClass();
        
        //TODO return information if message bus is alive or not, return debug statement from message server on success.
        $applicationState->messageBus->running = true;
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleStartSession(Zend_EventManager_Event $event) {
        $user = new Zend_Session_Namespace('user');
        if(!empty($user->data->userGuid)) {
            $this->bus->startSession(Zend_Session::getId(), $user->data);
        }
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        //the configured socket server 
        $view->Php2JsVars()->set('plugins.FrontEndMessageBus.socketServer', $this->getConfig()->socketServer);
        
        //a random connectionId. calculating a random value on server side is more reliable as in frontend: 
        // see https://stackoverflow.com/questions/1349404/generate-random-string-characters-in-javascript
        $view->Php2JsVars()->set('plugins.FrontEndMessageBus.connectionId', bin2hex(random_bytes(16)));
        
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleLogout(Zend_EventManager_Event $event) {
        $params = $event->getParam('params');
        if(empty($params['id'])) {
            //it was a call to /login/logout
            $sessionId = Zend_Session::getId();
        }
        else {
            //it was a call to SessionController::deleteAction
            $sessionId = $params['id'];
        }
        
        $this->bus->stopSession($sessionId);
    }
    
    /**
     * resync the application state (session and task if opened) into the socket server
     * @param Zend_EventManager_Event $event
     */
    public function handleSessionResync(Zend_EventManager_Event $event) {
        //startSession and taskOpen etc into the server
        // we are lazy and reuse our other handlers
        
        error_log("RESYNC SESSION");
        
        //resync session
        $this->handleStartSession($event);
        
        //resync task open state
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $session = new Zend_Session_Namespace();
        if(empty($session->taskGuid)) {
            return;
        }
        try {
            $task->loadByTaskGuid($session->taskGuid);
            $event->setParam('task', $task);
            $this->handleAfterTaskOpen($event);
        }
        catch (ZfExtended_NotFoundException $e) {
            //if the task is gone, we can not open it and do nothing
        }
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        
        $this->bus->notify(self::CHANNEL_TASK, 'open', [
            'task' => $task->getDataObject(),
            'sessionId' => Zend_Session::getId(),
        ]);
    }
    
    public function handleAlikeLoad(Zend_EventManager_Event $event) {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $connectionId = $f->getRequest()->getHeader('X-Translate5-MessageBus-ConnId');
        
        $masterSegment = $event->getParam('entity');
        /* @var $masterSegment editor_Models_Segment */
        
        $view = $event->getParam('view');
        $alikeIds = array_column($view->rows, 'id');
        $this->bus->notify(self::CHANNEL_TASK, 'segmentAlikesLoaded', [
            'connectionId' => $connectionId,
            'masterSegment' => $masterSegment->getDataObject(),
            'sessionId' => Zend_Session::getId(),
            'alikeIds' => $alikeIds,
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleAlikeSave(Zend_EventManager_Event $event) {
        $segment = $event->getParam('entity');
        /* @var $segment editor_Models_Segment */
        
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $connectionId = $f->getRequest()->getHeader('X-Translate5-MessageBus-ConnId');
        
        $this->bus->notify(self::CHANNEL_TASK, 'segmentAlikeSave', [
            'connectionId' => $connectionId,
            'segment' => $segment->getDataObject(),
            'sessionId' => Zend_Session::getId(),
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskClose(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        
        $this->bus->notify(self::CHANNEL_TASK, 'close', [
            'task' => $task->getDataObject(),
            'sessionId' => Zend_Session::getId(),
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleSegmentSave(Zend_EventManager_Event $event) {
        $segment = $event->getParam('entity');
        /* @var $segment editor_Models_Segment */
        
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $connectionId = $f->getRequest()->getHeader('X-Translate5-MessageBus-ConnId');
        
        $this->bus->notify(self::CHANNEL_TASK, 'segmentSave', [
            'connectionId' => $connectionId,
            'segment' => $segment->getDataObject(),
            'sessionId' => Zend_Session::getId(),
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleUpdateUserTracking(Zend_EventManager_Event $event) {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $connectionId = $f->getRequest()->getHeader('X-Translate5-MessageBus-ConnId'); //FIXME die Aufrufe mit diesem getHeader zusammenfassen!
        $this->bus->notify(self::CHANNEL_TASK, 'triggerReload', [
            'taskGuid' => $event->getParam('taskGuid'),
            'excludeConnection' => $connectionId,
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handlePing(Zend_EventManager_Event $event) {
        $this->bus->ping();
    }
}
