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
 */
class editor_Plugins_FrontEndMessageBus_Init extends ZfExtended_Plugin_Abstract {
    const CHANNEL_TASK = 'task';
    const CHANNEL_CHAT = 'chat';
    
    /**
     * Increase me on each change! (also SERVER_VERSION in server.php!)
     */
    const CLIENT_VERSION = '1.1';
    
    protected static $description = 'Provides the MessageBus (WebSocket) functionality for multi-user usage and other functions improving the user experience.';
    
    /**
     * @var editor_Plugins_FrontEndMessageBus_Bus
     */
    protected $bus;
    
    protected $localePath = 'locales';
    
    protected $frontendControllers = array(
        'pluginFrontEndMessageBus' => 'Editor.plugins.FrontEndMessageBus.controller.MessageBus',
        'pluginFrontEndMessageBusMultiUser' => 'Editor.plugins.FrontEndMessageBus.controller.MultiUserUsage',
    );
    
    public function init() {
        $this->bus = ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Bus', [self::CLIENT_VERSION]);
        $this->initEvents();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('editor_TaskController', 'afterTaskOpen', array($this, 'handleAfterTaskOpen'));
        $this->eventManager->attach('editor_TaskController', 'afterTaskClose', array($this, 'handleAfterTaskClose'));
        $this->eventManager->attach('editor_Models_TaskUserTracking', 'afterUserTrackingInsert', array($this, 'handleUpdateUserTracking'));
        $this->eventManager->attach('editor_Models_Task', 'unlock', array($this, 'handleTaskUnlock'));
        //$this->eventManager->attach('editor_TaskController', 'afterIndexAction', array($this, 'handlePing'));
        $this->eventManager->attach('Editor_SegmentController', 'afterPutAction', array($this, 'handleSegmentSave'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'afterGetAction', array($this, 'handleAlikeLoad'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'afterPutAction', array($this, 'handleAlikeSave'));
        $this->eventManager->attach('Editor_IndexController', 'beforeIndexAction', array($this, 'handleStartSession'));
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', array($this, 'handleGarbageCollection'));
        $this->eventManager->attach('editor_SessionController', 'beforeDeleteAction', array($this, 'handleLogout'));
        $this->eventManager->attach('editor_SessionController', 'resyncOperation', array($this, 'handleSessionResync'));
        $this->eventManager->attach('LoginController', 'beforeLogoutAction', array($this, 'handleLogout'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'beforePutAction', array($this, 'handleJobPut'));
        $this->eventManager->attach('editor_TaskController', 'analysisOperation', array($this, 'handleTaskOperation'));
        $this->eventManager->attach('editor_TaskController', 'pretranslationOperation', array($this, 'handleTaskOperation'));
        $this->eventManager->attach('editor_TaskController', 'autoqaOperation', array($this, 'handleTaskOperation'));
        $this->eventManager->attach('editor_Models_Task_WorkerProgress', 'updateProgress',array($this, 'handleUpdateProgress'));
        $this->eventManager->attach('ZfExtended_Models_Db_Session', 'getValidSessionsSql',array($this, 'handleGetValidSessionsSql'));

        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'handleApplicationState'));

        //inject JS strings
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));

        //updating entities in the comment nav
        $this->eventManager->attach('Editor_CommentController', 'afterPostAction', array($this, 'handleNormalComment'));
        $this->eventManager->attach('Editor_CommentController', 'afterPutAction', array($this, 'handleNormalComment'));
        $this->eventManager->attach('Editor_CommentController', 'beforeDeleteAction', array($this, 'handleDelete')); // need beforeDeleteAction for id

        $this->eventManager->attach('editor_Plugins_VisualReview_AnnotationController', 'afterPostAction', array($this, 'handleAnnotation'));
        $this->eventManager->attach('editor_Plugins_VisualReview_AnnotationController', 'afterPutAction', array($this, 'handleAnnotation'));
        $this->eventManager->attach('editor_Plugins_VisualReview_AnnotationController', 'beforeDeleteAction', array($this, 'handleDelete'));  // need beforeDeleteAction for id
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * Checks if the configured okapi instance is reachable
     * @param Zend_EventManager_Event $event
     */
    public function handleApplicationState(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->messageBus = new stdClass();
        $applicationState->messageBus->running = false;

        $dbg = $this->bus->debug();
        $applicationState->messageBus->running = !empty($dbg);
        if($applicationState->messageBus->running) {
            //all the internal data should not be provided, the connection count is enough.
            $applicationState->messageBus->connectionCount = $dbg->instanceResult->connectionCount;
        }
    }
    
    /**
     * @param Zend_EventManager_Event $event
     * @return boolean true if we have an authenticated user, false if not
     */
    public function handleStartSession(Zend_EventManager_Event $event) {
        $user = new Zend_Session_Namespace('user');
        if(!empty($user->data->userGuid)) {
            $this->bus->startSession(Zend_Session::getId(), $user->data);
            return true;
        }
        return false;
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        //the configured socket server
        $view->Php2JsVars()->set('plugins.FrontEndMessageBus.socketServer', $this->getConfig()->socketServer);
        
        //a random connectionId. calculating a random value on server side is more reliable as in frontend:
        // see https://stackoverflow.com/questions/1349404/generate-random-string-characters-in-javascript
        $view->Php2JsVars()->set('plugins.FrontEndMessageBus.connectionId', bin2hex(random_bytes(16)));

        //the client version
        $view->Php2JsVars()->set('plugins.FrontEndMessageBus.clientVersion', self::CLIENT_VERSION);
        
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
        
        $this->bus->stopSession($sessionId, $this->getHeaderConnId());
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleJobPut(Zend_EventManager_Event $event) {
        //1. load the desired job
        $id = $event->getParam('params')['id'] ?? 0;
        $job = clone $event->getParam('entity');
        /* @var $job editor_Models_TaskUserAssoc */
        try {
            $job->load($id); //empty id evaluates above to 0 which triggers not found then
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            return;
        }

        //2. check if there is a locking session, if no, do nothing
        if(empty($job->getUsedInternalSessionUniqId())) {
            return;
        }

        //3. load the session id to the internal session
        $sessIntId = new ZfExtended_Models_Db_SessionMapInternalUniqId();
        $row = $sessIntId->fetchRow(['internalSessionUniqId = ?' => $job->getUsedInternalSessionUniqId()]);
        $result = $this->bus->sessionHasConnection($row->session_id);
        $hasConnection = $result->instanceResult ?? true; //in doubt say true so the job is not unlocked!
        if(empty($row) || $hasConnection) { //row is empty, this is cleaned by the next garbage collection call
            return;
        }

        //4. release job for that user if the session has no connections anymore in the socket server
        if($job->getIsPmOverride()) {
            $job->deletePmOverride();
        }
        else {
            //direct update, no entity save to override entity version check
            $job->db->update([
                'usedState' => null,
                'usedInternalSessionUniqId' => null,
            ],['id' => $job->getId()]);
        }

        //5. unlock the task too, if locked by the jobs user
        $task = ZfExtended_Factory::get('editor_Models_task');
        /* @var $task editor_Models_task */
        $task->unlockForUser($job->getUserGuid(), $job->getTaskGuid());
    }
    
    /**
     * resync the application state (session and task if opened) into the socket server
     * @param Zend_EventManager_Event $event
     */
    public function handleSessionResync(Zend_EventManager_Event $event) {
        //startSession and taskOpen etc into the server
        // we are lazy and reuse our other handlers
        
        //resync session
        if(! $this->handleStartSession($event)) {
            return; //if we not had a valid authenticated user, we just do nothing
        }
        
        //resync task open state
        try {
            /** @var editor_SessionController $controller */
            $controller = $event->getParam('controller');
            $task = $controller->getCurrentTask();
            $event->setParam('task', $task);
            $this->handleAfterTaskOpen($event);
        } catch (\MittagQI\Translate5\Models\Task\Current\Exception) {
            //if the task is gone, we can not open it and do nothing
        }

        //marks the connection as in sync and trigger processing of queued messages
        $this->bus->resyncDone($this->getHeaderConnId());
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        
        $ut = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $ut editor_Models_TaskUserTracking */
        
        $this->bus->notify(self::CHANNEL_TASK, 'open', [
            'task' => $task->getDataObject(),
            'sessionId' => Zend_Session::getId(),
            'userTracking' => $ut->getByTaskGuid($task->getTaskGuid()),
        ]);
    }
    
    public function handleAlikeLoad(Zend_EventManager_Event $event) {
        $masterSegment = $event->getParam('entity');
        /* @var $masterSegment editor_Models_Segment */
        
        $view = $event->getParam('view');
        $alikeIds = array_column($view->rows, 'id');
        $this->bus->notify(self::CHANNEL_TASK, 'segmentAlikesLoaded', [
            'connectionId' => $this->getHeaderConnId(),
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
        
        $this->bus->notify(self::CHANNEL_TASK, 'segmentAlikeSave', [
            'connectionId' => $this->getHeaderConnId(),
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
            'connectionId' => $this->getHeaderConnId(),
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleSegmentSave(Zend_EventManager_Event $event) {
        $segment = $event->getParam('entity');
        /* @var $segment editor_Models_Segment */
        
        $this->bus->notify(self::CHANNEL_TASK, 'segmentSave', [
            'connectionId' => $this->getHeaderConnId(),
            'segment' => $segment->getDataObject(),
            'sessionId' => Zend_Session::getId(),
        ]);
    }
    
    /**
     * Task unlock event handler
     * @param Zend_EventManager_Event $event
     */
    public function handleTaskUnlock(Zend_EventManager_Event $event) {
        $this->reloadGuiTask($event->getParam('task'));
    }

    /**
     * Task operation event handler
     * @param Zend_EventManager_Event $event
     */
    public function handleTaskOperation(Zend_EventManager_Event $event) {
        $this->reloadGuiTask($event->getParam('entity'));
    }

    /**
     * Task get event handler
     * @param Zend_EventManager_Event $event
     */
    public function handleTaskGet(Zend_EventManager_Event $event) {
        $this->reloadGuiTask($event->getParam('entity'));
    }
    
    /***
     * Progres update event listener
     * @param Zend_EventManager_Event $event
     */
    public function handleUpdateProgress(Zend_EventManager_Event $event) {
        $taskGuid = $event->getParam('taskGuid');
        if(empty($taskGuid)){
            return;
        }
        $context = $event->getParam('context');
        
        $taskProcess = ZfExtended_Factory::get('editor_Models_Task_WorkerProgress');
        /** @var editor_Models_Task_WorkerProgress $taskProcess */
        $progress = $taskProcess->calculateProgress($taskGuid, $context);

        $this->bus->notify(self::CHANNEL_TASK, 'updateProgress', [
            'taskGuid' => $taskGuid,
            'progress' => $progress['progress']
        ]);
    }

    /**
     * @return array
     */
    public function handleGetValidSessionsSql(): array {
        $res = $this->bus->getConnectionSessions();
        return (array) ($res->instanceResult ?? []);
    }

    /***
     * Notify the task chanel with fresh task data
     */
    protected function reloadGuiTask(editor_Models_Task $task){
        //reload the task instance in the GUI
        $this->bus->notify(self::CHANNEL_TASK, 'triggerReload', [
            'taskGuid' =>$task->getTaskGuid(),
            'taskId' => $task->getId(),
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleUpdateUserTracking(Zend_EventManager_Event $event) {
        //reload the task instance in the GUI
        $this->bus->notify(self::CHANNEL_TASK, 'triggerReload', [
            'taskGuid' => $event->getParam('taskGuid'),
            'taskId' => 0,
            'excludeConnection' => $this->getHeaderConnId(),
        ]);

        //update the usertrackings
        $ut = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $ut editor_Models_TaskUserTracking */
        $this->bus->notify(self::CHANNEL_TASK, 'updateUserTracking', [
            'taskGuid' => $event->getParam('taskGuid'),
            'userTracking' => $ut->getByTaskGuid($event->getParam('taskGuid')),
        ]);
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handlePing(Zend_EventManager_Event $event) {
        $this->bus->ping();
        //$this->handleGarbageCollection();
    }
    
    public function handleGarbageCollection() {
        $sessions = ZfExtended_Factory::get('ZfExtended_Models_Db_Session');
        /* @var $sessions ZfExtended_Models_Db_Session */
        $existingSessionIds = $sessions->fetchAll($sessions->select()->reset()->from($sessions, ['session_id']));
        $existingSessionIds = array_column($existingSessionIds->toArray(), 'session_id');
        
        //instance garbage collection
        $this->bus->garbageCollection($existingSessionIds);
        
        $metrics = ZfExtended_Factory::get('editor_Models_Metrics');
        /* @var $sessions editor_Models_Metrics */
        $metrics->collect();
        $this->bus->updateMetrics($metrics->get());
    }
    
    /**
     * returns the connection ID delivered via HTTP header to the translate5 server
     * @return string
     */
    protected function getHeaderConnId(): string {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        return $f->getRequest()->getHeader('X-Translate5-MessageBus-ConnId');
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleNormalComment(Zend_EventManager_Event $event) {
        $entity = $event->getParam('entity');
        /* @var $entity editor_Models_Comment */
        $taskGuid = $entity->getTaskGuid();
        $comments  = $entity->loadByTaskPlain($taskGuid, $entity->getId());
        $comment = $comments[0];
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);
        if($task->anonymizeUsers(false)){
            $wfAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            $comment = $wfAnonymize->anonymizeUserdata($taskGuid, $comment['userGuid'], $comment, null, true);
        }
        $comment['type'] = $entity::FRONTEND_ID;
        $this->triggerCommentNavUpdate($comment, $event->getName());
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleAnnotation(Zend_EventManager_Event $event) {
        $entity = $event->getParam('entity');
        /* @var $entity editor_Plugins_VisualReview_Annotation_Entity */
        $taskGuid = $entity->getTaskGuid();
        $annotation  = $entity->toArray();
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);
        if($task->anonymizeUsers(false)){
            $wfAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            $annotation = $wfAnonymize->anonymizeUserdata($taskGuid, $annotation['userGuid'], $annotation, null, true);
        }
        $annotation['comment'] = htmlspecialchars($annotation['text']);
        $annotation['type'] = $entity::FRONTEND_ID;
        $this->triggerCommentNavUpdate($annotation, $event->getName());
    }

    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleDelete(Zend_EventManager_Event $event) {
        /* @var $ent editor_Plugins_VisualReview_Annotation_Entity|editor_Models_Comment */
        $ent = $event->getParam('entity');

        $controller = $event->getParam('controller');
        $task = $controller->getCurrentTask();

        $a_ent = [
            'taskGuid' => $task->getTaskGuid(),
            'type' => $ent::FRONTEND_ID,
            'id' => $event->getParams()['params']['id'],
        ];
        $this->triggerCommentNavUpdate($a_ent, $event->getName());
    }

    public function triggerCommentNavUpdate(array $commentData, string $typeOfChange) {
        $this->bus->notify(self::CHANNEL_TASK, 'commentChanged', [
            'connectionId' => $this->getHeaderConnId(),
            'typeOfChange' => $typeOfChange,
            'comment'      => $commentData,
            'sessionId'    => Zend_Session::getId(),
        ]);
    }
}
