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

use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Plugins\VisualReview\Annotation\AnnotationEntity;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Segment\Event\SegmentProcessedEvent;
use MittagQI\Translate5\Segment\Event\SegmentUpdatedEvent;
use MittagQI\Translate5\Segment\Repetition\Event\RepetitionProcessingFailedEvent;
use MittagQI\Translate5\Segment\Repetition\Event\RepetitionReplacementRequestedEvent;
use MittagQI\Translate5\Segment\SearchAndReplace\Event\SearchAndReplaceProcessingFailedEvent;
use MittagQI\Translate5\Segment\SearchAndReplace\Event\SearchAndReplaceProcessingRequestedEvent;
use MittagQI\Translate5\Task\Events\TaskProgressUpdatedEvent;

class editor_Plugins_FrontEndMessageBus_Init extends ZfExtended_Plugin_Abstract
{
    public const CHANNEL_TASK = 'task';

    public const CHANNEL_CHAT = 'chat';

    /**
     * Increase me on each change! (also SERVER_VERSION in server.php when changes reflected in server!)
     *  - Major Version change on backwards incompatible changes.
     *  - Minor Version change on backwards compatible changes.
     */
    public const CLIENT_VERSION = '2.0';

    protected const SEGMENTS_PROCESSED_MESSAGE = 'segmentsProcessed';

    protected static string $description = 'Provides the MessageBus (WebSocket) functionality for multi-user usage and other functions improving the user experience.';

    protected static bool $enabledByDefault = true;

    protected static bool $activateForTests = true;

    /**
     * The services we use
     * @var string[]
     */
    protected static array $services = [
        'frontendmessagebus' => MittagQI\Translate5\Plugins\FrontEndMessageBus\Service::class,
    ];

    /**
     * @var editor_Plugins_FrontEndMessageBus_Bus
     */
    protected $bus;

    protected $localePath = 'locales';

    protected $frontendControllers = [
        'pluginFrontEndMessageBus' => 'Editor.plugins.FrontEndMessageBus.controller.MessageBus',
        'pluginFrontEndMessageBusMultiUser' => 'Editor.plugins.FrontEndMessageBus.controller.MultiUserUsage',
    ];

    public function init()
    {
        $this->bus = ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Bus', [self::CLIENT_VERSION]);
        $this->initEvents();
    }

    protected function initEvents()
    {
        $this->eventManager->attach('editor_TaskController', 'afterTaskOpen', [$this, 'handleAfterTaskOpen']);
        $this->eventManager->attach('editor_TaskController', 'afterTaskClose', [$this, 'handleAfterTaskClose']);
        $this->eventManager->attach('editor_Models_TaskUserTracking', 'afterUserTrackingInsert', [$this, 'handleUpdateUserTracking']);
        $this->eventManager->attach('editor_Models_Task', 'unlock', [$this, 'handleTaskUnlock']);
        //$this->eventManager->attach('editor_TaskController', 'afterIndexAction', array($this, 'handlePing'));
        $this->eventManager->attach('Editor_AlikesegmentController', 'afterGetAction', [$this, 'handleAlikeLoad']);

        $this->eventManager->attach(
            EventDispatcher::class,
            SegmentUpdatedEvent::class,
            [$this, 'handleSegmentSave']
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            RepetitionReplacementRequestedEvent::class,
            [$this, 'sendSegmentRepetitionProcessingStarted']
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            SegmentProcessedEvent::class,
            [$this, 'sendSegmentProcessed']
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            TaskProgressUpdatedEvent::class,
            [$this, 'sendTaskProgressUpdated']
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            RepetitionProcessingFailedEvent::class,
            [$this, 'unlockProcessingRepetitionSegments']
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            SearchAndReplaceProcessingRequestedEvent::class,
            [$this, 'sendSegmentSearchAndReplaceProcessingStarted']
        );
        $this->eventManager->attach(
            EventDispatcher::class,
            SearchAndReplaceProcessingFailedEvent::class,
            [$this, 'unlockProcessingSearchAndReplaceSegments']
        );

        $this->eventManager->attach('Editor_IndexController', 'beforeIndexAction', [$this, 'handleStartSession']);
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', [$this, 'injectFrontendConfig']);
        $this->eventManager->attach('ZfExtended_Resource_GarbageCollector', 'cleanUp', [$this, 'handleGarbageCollection']);
        $this->eventManager->attach('editor_SessionController', 'beforeDeleteAction', [$this, 'handleLogout']);
        $this->eventManager->attach('editor_SessionController', 'resyncOperation', [$this, 'handleSessionResync']);
        $this->eventManager->attach('LoginController', 'beforeLogoutAction', [$this, 'handleLogout']);
        $this->eventManager->attach('Editor_TaskuserassocController', 'beforePutAction', [$this, 'handleJobPut']);
        $this->eventManager->attach('editor_TaskController', 'analysisOperation', [$this, 'handleTaskOperation']);
        $this->eventManager->attach('editor_TaskController', 'pretranslationOperation', [$this, 'handleTaskOperation']);
        $this->eventManager->attach('editor_TaskController', 'autoqaOperation', [$this, 'handleTaskOperation']);
        $this->eventManager->attach('editor_Models_Task_WorkerProgress', 'updateProgress', [$this, 'handleUpdateProgress']);
        $this->eventManager->attach('ZfExtended_Models_Db_Session', 'getStalledSessions', [$this, 'handleGetStalledSessions']);

        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', [$this, 'handleApplicationState']);

        //inject JS strings
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', [$this, 'initJsTranslations']);

        //updating entities in the comment nav
        $this->eventManager->attach('Editor_CommentController', 'afterPostAction', [$this, 'handleNormalComment']);
        $this->eventManager->attach('Editor_CommentController', 'afterPutAction', [$this, 'handleNormalComment']);
        $this->eventManager->attach('Editor_CommentController', 'beforeDeleteAction', [$this, 'handleDelete']); // need beforeDeleteAction for id

        $this->eventManager->attach('editor_Plugins_VisualReview_AnnotationController', 'afterPostAction', [$this, 'handleAnnotation']);
        $this->eventManager->attach('editor_Plugins_VisualReview_AnnotationController', 'afterPutAction', [$this, 'handleAnnotation']);
        $this->eventManager->attach('editor_Plugins_VisualReview_AnnotationController', 'beforeDeleteAction', [$this, 'handleDelete']);  // need beforeDeleteAction for id
    }

    public function initJsTranslations(Zend_EventManager_Event $event)
    {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }

    /**
     * Checks if the configured okapi instance is reachable
     */
    public function handleApplicationState(Zend_EventManager_Event $event)
    {
        $applicationState = $event->getParam('applicationState');
        $applicationState->messageBus = new stdClass();
        $applicationState->messageBus->running = false;

        $dbg = $this->bus->debug();
        $applicationState->messageBus->running = ! empty($dbg);
        if ($applicationState->messageBus->running) {
            //all the internal data should not be provided, the connection count is enough.
            $applicationState->messageBus->connectionCount = $dbg->instanceResult->connectionCount;
        }
    }

    /**
     * @return boolean true if we have an authenticated user, false if not
     */
    public function handleStartSession(Zend_EventManager_Event $event)
    {
        $auth = ZfExtended_Authentication::getInstance();
        if ($auth->isAuthenticated()) {
            $this->bus->startSession(Zend_Session::getId(), $auth->getUserData());

            return true;
        }

        return false;
    }

    public function injectFrontendConfig(Zend_EventManager_Event $event)
    {
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

    public function handleLogout(Zend_EventManager_Event $event)
    {
        $params = $event->getParam('params');
        if (empty($params['id'])) {
            //it was a call to /login/logout
            $sessionId = Zend_Session::getId();
        } else {
            //it was a call to SessionController::deleteAction
            $sessionId = $params['id'];
        }

        $this->bus->stopSession($sessionId, $this->getHeaderConnId());
    }

    public function handleJobPut(Zend_EventManager_Event $event): void
    {
        //1. load the desired job
        $id = $event->getParam('params')['id'] ?? 0;
        $jobRepository = \MittagQI\Translate5\Repository\UserJobRepository::create();

        try {
            $job = $jobRepository->get((int) $id); //empty id evaluates above to 0 which triggers not found then
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return;
        }

        //2. check if there is a locking session, if no, do nothing
        if (empty($job->getUsedInternalSessionUniqId())) {
            return;
        }

        //3. load the session id to the internal session
        $sessIntId = new ZfExtended_Models_Db_Session();
        $row = $sessIntId->fetchRow([
            'internalSessionUniqId = ?' => $job->getUsedInternalSessionUniqId(),
        ]);
        $result = $this->bus->sessionHasConnection($row->session_id);
        $hasConnection = $result->instanceResult ?? true; //in doubt say true so the job is not unlocked!
        if (empty($row) || $hasConnection) { //row is empty, this is cleaned by the next garbage collection call
            return;
        }

        //4. release job for that user if the session has no connections anymore in the socket server
        if ($job->getIsPmOverride()) {
            $job->deletePmOverride();
        } else {
            //direct update, no entity save to override entity version check
            $job->db->update([
                'usedState' => null,
                'usedInternalSessionUniqId' => null,
            ], [
                'id = ?' => $job->getId(),
            ]);
        }

        //5. unlock the task too, if locked by the jobs user
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->unlockForUser($job->getUserGuid(), $job->getTaskGuid());
    }

    /**
     * resync the application state (session and task if opened) into the socket server
     */
    public function handleSessionResync(Zend_EventManager_Event $event)
    {
        //startSession and taskOpen etc into the server
        // we are lazy and reuse our other handlers

        //resync session
        if (! $this->handleStartSession($event)) {
            return; //if we not had a valid authenticated user, we just do nothing
        }

        //resync task open state
        try {
            /** @var editor_SessionController $controller */
            $controller = $event->getParam('controller');
            $task = $controller->getCurrentTask();
            $event->setParam('task', $task);
            $this->handleAfterTaskOpen($event);
        } catch (\MittagQI\Translate5\Task\Current\Exception) {
            //if the task is gone, we can not open it and do nothing
        }

        //marks the connection as in sync and trigger processing of queued messages
        $this->bus->resyncDone($this->getHeaderConnId());
    }

    public function handleAfterTaskOpen(Zend_EventManager_Event $event)
    {
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

    public function handleAlikeLoad(Zend_EventManager_Event $event)
    {
        $masterSegment = $event->getParam('entity');
        /* @var $masterSegment editor_Models_Segment */

        $view = $event->getParam('view');
        $alikeIds = array_column($view->rows, 'id');

        $this->bus->notify(self::CHANNEL_TASK, 'segmentAlikesLoaded', [
            'connectionId' => $this->getHeaderConnId(),
            'masterSegment' => [
                'id' => (int) $masterSegment->getId(),
                'taskGuid' => $masterSegment->getTaskGuid(),
            ],
            'sessionId' => Zend_Session::getId(),
            'alikeIds' => $alikeIds,
        ]);
    }

    public function sendSegmentRepetitionProcessingStarted(Zend_EventManager_Event $zendEvent)
    {
        /** @var RepetitionReplacementRequestedEvent $event */
        $event = $zendEvent->getParam('event');

        $this->bus->notify(self::CHANNEL_TASK, 'segmentProcessingStarted', [
            'taskGuid' => $event->taskGuid,
            'segmentIds' => [$event->masterId, ...$event->repetitionIds],
        ]);
    }

    public function sendSegmentSearchAndReplaceProcessingStarted(Zend_EventManager_Event $zendEvent)
    {
        /** @var SearchAndReplaceProcessingRequestedEvent $event */
        $event = $zendEvent->getParam('event');

        $this->bus->notify(self::CHANNEL_TASK, 'segmentProcessingStarted', [
            'taskGuid' => $event->taskGuid,
            'segmentIds' => $event->segmentIds,
        ]);
    }

    public function sendSegmentProcessed(Zend_EventManager_Event $zendEvent)
    {
        /** @var SegmentProcessedEvent $event */
        $event = $zendEvent->getParam('event');

        $this->bus->notify(self::CHANNEL_TASK, self::SEGMENTS_PROCESSED_MESSAGE, [
            'taskGuid' => $event->taskGuid,
            'segmentIds' => $event->segmentIds,
        ]);
    }

    public function sendTaskProgressUpdated(Zend_EventManager_Event $zendEvent): void
    {
        /** @var TaskProgressUpdatedEvent $event */
        $event = $zendEvent->getParam('event');

        $this->bus->notify(self::CHANNEL_TASK, 'updateTaskProgress', [
            'taskGuid' => $event->taskGuid,
            'progress' => $event->getProgress(),
        ]);
    }

    public function unlockProcessingRepetitionSegments(Zend_EventManager_Event $zendEvent): void
    {
        /** @var RepetitionProcessingFailedEvent $event */
        $event = $zendEvent->getParam('event');

        $segmentRepository = SegmentRepository::create();

        $segment = $segmentRepository->get($event->masterId);

        $this->bus->notify(self::CHANNEL_TASK, self::SEGMENTS_PROCESSED_MESSAGE, [
            'taskGuid' => $segment->getTaskGuid(),
            'segmentIds' => [$event->masterId, ...$event->repetitionIds],
        ]);
    }

    public function unlockProcessingSearchAndReplaceSegments(Zend_EventManager_Event $zendEvent): void
    {
        /** @var SearchAndReplaceProcessingFailedEvent $event */
        $event = $zendEvent->getParam('event');

        $this->bus->notify(self::CHANNEL_TASK, self::SEGMENTS_PROCESSED_MESSAGE, [
            'taskGuid' => $event->taskGuid,
            'segmentIds' => $event->segmentIds,
        ]);
    }

    public function handleAfterTaskClose(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */

        $this->bus->notify(self::CHANNEL_TASK, 'close', [
            'task' => $task->getDataObject(),
            'sessionId' => Zend_Session::getId(),
            'connectionId' => $this->getHeaderConnId(),
        ]);
    }

    public function handleSegmentSave(Zend_EventManager_Event $zendEvent)
    {
        /** @var SegmentUpdatedEvent $event */
        $event = $zendEvent->getParam('event');

        $this->bus->notify(self::CHANNEL_TASK, 'segmentSave', [
            'connectionId' => $this->getHeaderConnId(),
            'segment' => $event->segment->getDataObject(),
            'sessionId' => Zend_Session::getId(),
        ]);
    }

    /**
     * Task unlock event handler
     */
    public function handleTaskUnlock(Zend_EventManager_Event $event)
    {
        $this->reloadGuiTask($event->getParam('task'));
    }

    /**
     * Task operation event handler
     */
    public function handleTaskOperation(Zend_EventManager_Event $event)
    {
        $this->reloadGuiTask($event->getParam('entity'));
    }

    /**
     * Task get event handler
     */
    public function handleTaskGet(Zend_EventManager_Event $event)
    {
        $this->reloadGuiTask($event->getParam('entity'));
    }

    /***
     * Progres update event listener
     * @param Zend_EventManager_Event $event
     */
    public function handleUpdateProgress(Zend_EventManager_Event $event)
    {
        $taskGuid = $event->getParam('taskGuid');
        if (empty($taskGuid)) {
            return;
        }
        $context = $event->getParam('context');
        $taskProgress = ZfExtended_Factory::get(editor_Models_Task_WorkerProgress::class);
        $progress = $taskProgress->calculateProgress($taskGuid, $context);

        $this->bus->notify(self::CHANNEL_TASK, 'updateProgress', [
            'taskGuid' => $taskGuid,
            'progress' => $progress['progress'],
            'operationType' => $progress['operationType'],
        ]);
    }

    /**
     * returns the sessionIDs which were connected to the messagebus but now have no active connection anymore
     */
    public function handleGetStalledSessions(): array
    {
        $res = $this->bus->getStalledSessions();

        return (array) ($res->instanceResult ?? []);
    }

    /***
     * Notify the task chanel with fresh task data
     */
    protected function reloadGuiTask(editor_Models_Task $task)
    {
        //reload the task instance in the GUI
        $this->bus->notify(self::CHANNEL_TASK, 'triggerReload', [
            'taskGuid' => $task->getTaskGuid(),
            'taskId' => $task->getId(),
        ]);
    }

    public function handleUpdateUserTracking(Zend_EventManager_Event $event)
    {
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

    public function handlePing(Zend_EventManager_Event $event)
    {
        $this->bus->ping();
        //$this->handleGarbageCollection();
    }

    public function handleGarbageCollection()
    {
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
     */
    protected function getHeaderConnId(): string
    {
        $f = Zend_Registry::get('frontController');

        /* @var $f Zend_Controller_Front */
        return $f->getRequest()->getHeader('X-Translate5-MessageBus-ConnId');
    }

    public function handleNormalComment(Zend_EventManager_Event $event)
    {
        $entity = $event->getParam('entity');
        /* @var $entity editor_Models_Comment */
        $taskGuid = $entity->getTaskGuid();
        $comments = $entity->loadByTaskPlain($taskGuid, $entity->getId());
        $comment = $comments[0];

        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);
        if ($task->anonymizeUsers(false)) {
            $wfAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            $comment = $wfAnonymize->anonymizeUserdata($taskGuid, $comment['userGuid'], $comment, null, true);
        }
        $comment['type'] = $entity::FRONTEND_ID;
        $this->triggerCommentNavUpdate($comment, $event->getName());
    }

    public function handleAnnotation(Zend_EventManager_Event $event)
    {
        $entity = $event->getParam('entity');
        /* @var $entity AnnotationEntity */
        $taskGuid = $entity->getTaskGuid();
        $annotation = $entity->toArray();

        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);
        if ($task->anonymizeUsers(false)) {
            $wfAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            $annotation = $wfAnonymize->anonymizeUserdata($taskGuid, $annotation['userGuid'], $annotation, null, true);
        }
        $annotation['comment'] = htmlspecialchars($annotation['text']);
        $annotation['type'] = $entity::FRONTEND_ID;
        $this->triggerCommentNavUpdate($annotation, $event->getName());
    }

    public function handleDelete(Zend_EventManager_Event $event)
    {
        /* @var $ent AnnotationEntity|editor_Models_Comment */
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

    public function triggerCommentNavUpdate(array $commentData, string $typeOfChange)
    {
        $this->bus->notify(self::CHANNEL_TASK, 'commentChanged', [
            'connectionId' => $this->getHeaderConnId(),
            'typeOfChange' => $typeOfChange,
            'comment' => $commentData,
            'sessionId' => Zend_Session::getId(),
        ]);
    }
}
