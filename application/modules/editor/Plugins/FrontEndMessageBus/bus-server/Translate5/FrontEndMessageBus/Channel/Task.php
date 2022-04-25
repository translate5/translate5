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

namespace Translate5\FrontEndMessageBus\Channel;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\SegmentMsg;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Channel;
use Translate5\FrontEndMessageBus\FrontendMsgValidator;
/**
 * Encapsulates logic specific to an opened task in an instance
 */
class Task extends Channel {
    use FrontendMsgValidator;
    const CHANNEL_NAME = 'task';
    
    /**
     * Maps a taskGuid to a list of sessions where the task is opened
     * @var array
     */
    protected $taskToSessionMap = [];
    
    /**
     * contains the usertrackingId to each task
     * @var array
     */
    protected $taskUserTracking = [];
    
    /**
     * If a segment is listed here, it is locked. Key = segment id, value = connection of the locking user.
     * @var array
     */
    protected $editedSegments = [];
    
    /**
     * contains a map of a requested master Segment ID to its alike IDs
     * @var array
     */
    protected $alikeSegments = [];
    
    /*************************
     * Frontend Methods
     *************************/
    public function segmentEditRequest(FrontendMsg $request) {
        $answer = $this->createSegmentAnswerFromFrontend($request);
        
        if(empty($answer)) {
            return;
        }
        
        //release other segment(s) of this session/connection
        $this->releaseLocalSegment($request->conn->openedSegmentId ?? 0);
        
        //if the segment is here already in use, we send a NAK
        if(!empty($this->editedSegments[$answer->segmentId]) && $this->editedSegments[$answer->segmentId] !== $request->conn) {
            $answer->segmentOpenNak();
            return;
        }
        
        //registered opened segment locally (only master segmentIds)
        $request->conn->openedSegmentId = $answer->segmentId;
        $this->editedSegments[$answer->segmentId] = $request->conn;
        
        //send ACK to requesting user
        $answer->segmentOpenAck();
        
        //send lock info to other task users
        $answer->command = 'segmentLocked';
        $this->sendToOthersOnTask($answer);
    }
    
    /**
     * react on a segment click from frontend
     * @param FrontendMsg $request
     */
    public function segmentClick(FrontendMsg $request) {
        $answer = $this->createSegmentAnswerFromFrontend($request, 'segmentselect');
        if(!empty($answer)) {
            $this->sendToOthersOnTask($answer);
        }
    }
    
    /**
     * A task was opened in the browser. As result the browser receives all currently opened segments
     * @param FrontendMsg $request
     */
    public function openTask(FrontendMsg $request) {
        $result = SegmentMsg::createFromFrontend($request);
        $result->command = 'segmentLocked';
        $request->conn->openedTask = $result->taskGuid;

        //sending the requesting user all locked segments
        foreach($this->editedSegments as $segmentId => $conn) {
            $userGuid = $this->instance->getSession($conn->sessionId, 'userGuid');
            if($conn->WebSocket->closing || empty($userGuid)) {
                //session of that connection or whole connection is gone
                continue;
            }
            $result->trackingId = $this->getUserTrackingId($result->taskGuid, $userGuid);
            $result->segmentId = $segmentId;
            $result->connectionId = $conn->connectionId; //send back the locking connection id
            $result->send();
        }
        
        $this->updateOnlineUsers($request->conn->sessionId, $result->taskGuid, true);
    }
    
    /**
     * Sends the list of trackingIds with theire online status to the users of a task
     * @param string $affectedSessionId
     * @param string $taskGuid
     * @param bool $online true if the triggering user is online, false otherwise
     *
     */
    protected function updateOnlineUsers(string $affectedSessionId, string $taskGuid, bool $online) {
        //update online users in task
        $userGuid = $this->instance->getSession($affectedSessionId, 'userGuid');
        if(empty($this->taskUserTracking[$taskGuid][$userGuid])) {
            return;
        }
        $this->taskUserTracking[$taskGuid][$userGuid]['isOnline'] = $online;
        $onlineInTask = [];
        foreach($this->taskUserTracking[$taskGuid] as $tracking) {
            $onlineInTask[$tracking['id']] = !empty($tracking['isOnline']);
        }
        $request = FrontendMsg::create(self::CHANNEL_NAME, 'updateOnlineUsers', [
            'onlineInTask' => $onlineInTask
        ]);
        $this->sendToTaskUsers($taskGuid, $affectedSessionId, $request);
    }
    
    public function segmentCancelAlikes(FrontendMsg $request) {
        $answer = $this->createSegmentAnswerFromFrontend($request, 'segmentSave');
        if(empty($answer)) {
            return;
        }
        
        $alikes = [];
        //get the alike IDs (before releaseLocalSegment, they are removed there)
        if(!empty($this->alikeSegments[$answer->segmentId])) {
            $alikes = $this->alikeSegments[$answer->segmentId];
        }
        
        //we release the master segment
        $this->releaseLocalSegment($answer->segmentId);
        
        //we send a segment save answer to the browsers
        $this->sendToOthersOnTask($answer);
        $this->leaveAlikes($answer, $alikes);
    }
    
    /**
     * send the browsers to leave (unlock) the segment
     * @param FrontendMsg $request
     */
    public function segmentLeave(FrontendMsg $request) {
        $answer = $this->createSegmentAnswerFromFrontend($request, 'segmentLeave');
        if(empty($answer)) {
            return; //task is not opened by session, so we can not process the request
        }
        if(!empty($this->alikeSegments[$answer->segmentId])) {
            $alikes = $this->alikeSegments[$answer->segmentId];
        }
        $this->releaseLocalSegment($answer->segmentId);
        //we delegate the leaving call of the master segment to the leaveAlikes,so we send only one message
        $alikes[] = $answer->segmentId;
        if(!isset($answer->selectedSegmentId)){
            $answer->selectedSegmentId = $answer->segmentId;
        }
        $this->leaveAlikes($answer, $alikes);
    }
    
    /**
     * leave all segments mentioned via ID in $alikes
     * @param SegmentMsg $answer
     * @param array $alikes
     */
    protected function leaveAlikes(SegmentMsg $answer, array $alikes) {
        if(empty($alikes)) {
            return;
        }
        //on cancelling we just leave the alike segments
        $answer->command = 'segmentLeave';
        //if there were alikeIDs we have to process each of it
        foreach($alikes as $alikeId) {
            //release the alikeId locally
            $this->releaseLocalSegment($alikeId);
        }
        $answer->segmentId = $alikes;
        //trigger segment update in the browser
        $this->sendToOthersOnTask($answer);
    }
    
    /**
     * Sends the $result msg to all other connections expect the one where $request is coming from
     * @param FrontendMsg $request
     * @param FrontendMsg $result
     */
    protected function sendToOthersOnTask(SegmentMsg $result) {
        $this->sendToTaskUsers($result->taskGuid, $result->conn->sessionId, $result, $result->conn);
    }
    
    /**
     * Sends the $result FrontendMsg to all connections of a task. Expect to the optionally given $connectionToExclude which will mostly be the initiator itself
     * @param string $taskGuid
     * @param string $currentSessionId
     * @param FrontendMsg $result
     * @param ConnectionInterface $connectionToExclude
     */
    protected function sendToTaskUsers(string $taskGuid, string $currentSessionId, FrontendMsg $result, ConnectionInterface $connectionToExclude = null) {
        if(!$this->isSessionValidForTask($currentSessionId, $taskGuid)) {
            //if current session is not valid for task, someone provided manually a different taskGuid
            return;
        }
        
        foreach($this->instance->getConnections() as $conn) {
            /* @var $conn ConnectionInterface */
            $excludeConnection = !is_null($connectionToExclude) && $conn === $connectionToExclude;
            if($excludeConnection || !$this->isSessionValidForTask($conn->sessionId, $taskGuid)) {
                //ignore myself and ignore all other connections not belonging to that task
                continue;
            }
            //must be logged explicitly, since we directly call send on the connection here
            $result->logSend();
            //and send the message
            $conn->send((string) $result);
        }
    }
    
    /**
     * Release an internally locked segment, returns the locking connection
     * @param integer $segmentId
     * @return ConnectionInterface|null
     */
    protected function releaseLocalSegment(int $segmentId): ?ConnectionInterface {
        if(empty($segmentId)) {
            return null;
        }
        
        if(array_key_exists($segmentId, $this->alikeSegments)) {
            unset($this->alikeSegments[$segmentId]);
        }
        if(array_key_exists($segmentId, $this->editedSegments)) {
            $conn = $this->editedSegments[$segmentId];
            $conn->openedSegmentId = null;
            unset($this->editedSegments[$segmentId]);
            return $conn;
        }
        return null;
    }
    
    /**
     * Returns true if given taskGuid is registered for current session
     * @param string $sessionId
     * @param string $taskGuid
     * @return boolean
     */
    protected function isSessionValidForTask(string $sessionId, string $taskGuid): bool {
        $sessionsForTask = $this->taskToSessionMap[$taskGuid] ?? [];
        //if current session is not valid for task, someone provided manually a different taskGuid
        return in_array($sessionId, $sessionsForTask);
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn) {
        if(!empty($conn->openedTask)) {
            $this->updateOnlineUsers($conn->sessionId, $conn->openedTask, false);
        }
        
        $taskGuid = $conn->openedTask ?? '';
        
        //we just fake a frontend msg triggering the segmentLeave
        $request = FrontendMsg::create(self::CHANNEL_NAME, 'segmentLeave', [$taskGuid, $conn->openedSegmentId ?? 0], $conn);
        
        //we also have to reset selected segments, also the segment answer can be recycled below
        $answer = $this->createSegmentAnswerFromFrontend($request, 'segmentselect');
        if(!empty($answer)) {
            $answer->segmentId = [];
            $answer->taskGuid = $taskGuid;
            $this->sendToOthersOnTask($answer);
        }
        
        //loop over all edited segments and release all segments of the closing connection
        // if there is a concrete opened segment, on reconnect this is tried to be opened again
        $toRelease = [];
        foreach($this->editedSegments as $segmentId => $usedConnection) {
            if($usedConnection !== $conn) {
                continue;
            }
            //if we can't get an segment answer, we just remove the segments locally
            if(empty($answer)) {
                $this->releaseLocalSegment($segmentId);
                continue;
            }
            $toRelease[] = $segmentId;
        }
        //if we have segments to be released, we also have an answer to send to the GUIs
        if(!empty($toRelease)) {
            //leaveAlikes can be used to release multiple segments
            $this->leaveAlikes($answer, $toRelease);
        }
    }
    
    /**
     * trigger garbage collection
     * Must be called after the instance gc, which triggers the stopSession call of this class
     * @param array $existingSessions
     * @return array[] returns the really used taskGuids
     */
    public function garbageCollection(array $existingSessions) {
        //clean up sessions in each task of taskToSessionMap
        foreach($this->taskToSessionMap as $task => $sessions) {
            $this->taskToSessionMap[$task] = array_intersect($sessions, $existingSessions);
        }
        
        //remove task guids without any session
        $this->taskToSessionMap = array_filter($this->taskToSessionMap);
        $todelete = array_diff(array_keys($this->taskUserTracking), array_keys($this->taskToSessionMap));
        foreach($todelete as $del) {
            unset ($this->taskUserTracking[$del]);
        }
        
        //get remaining sessions associated to tasks
        $this->leaveSegmentsFromGarbageSessions();

        //we return here only the taskGuids, really having a session
        return ['usedTaskGuids' => array_keys($this->taskToSessionMap)];
    }
    
    /**
     * unlock segments locked by connections with dead sessions
     */
    private function leaveSegmentsFromGarbageSessions() {
        $allUsedSessions = array_values($this->taskToSessionMap);
        if(empty($allUsedSessions)) {
            return;
        }
        //merge all sessions together into one array
        $allUsedSessions = array_unique(call_user_func_array('array_merge', $allUsedSessions));
        
        //check if there are connections containing non existent sessions, if yes leave open segments by that connections
        $orphanedConnections = [];
        foreach($this->instance->getConnections() as $conn) {
            if(!empty($allUsedSessions) && in_array($conn->sessionId, $allUsedSessions)) {
                continue;
            }
            $orphanedConnections[] = $conn;
            // if the connection has a locked segment, release it.
            $request = FrontendMsg::create(self::CHANNEL_NAME, 'segmentLeave', [$conn->openedTask ?? null, $conn->openedSegmentId ?? 0], $conn);
            if(!empty($conn->openedTask) && !empty($conn->openedSegmentId)) {
                //on connection close we release the segment, on reconnect the segment is tried to open again
                // we just fake a frontend msg triggering the segmentLeave
                $this->segmentLeave($request);
            }
        }
        
        //remove orphaned open segments (on closing task something similar is done too)
        foreach($this->editedSegments as $segId => $conn) {
            /* @var $conn \Ratchet\WebSocket\WsConnection */
            if($conn->WebSocket->closing || in_array($conn, $orphanedConnections)) {
                $this->releaseLocalSegment($segId);
            }
        }
    }
    
    /**
     * Creates a preconfigured SegmentMsg answer from a FrontEnd request
     * @param FrontendMsg $request
     * @param string $command
     * @return SegmentMsg
     */
    protected function createSegmentAnswerFromFrontend(FrontendMsg $request, string $command = null): ?SegmentMsg {
        $answer = SegmentMsg::createFromFrontend($request);
        if(!$this->isSessionValidForTask($request->conn->sessionId, $answer->taskGuid)) {
            return null;
        }
        if(!isset($request->conn->openedTask)) {
            $request->conn->openedTask = '';
        }
        if(!empty($command)) {
            $answer->command = $command;
        }
        $answer->channel = self::CHANNEL_NAME;
        $userGuid = $this->instance->getSession($request->conn->sessionId, 'userGuid');
        $answer->trackingId = $this->getUserTrackingId($request->conn->openedTask, $userGuid);
        return $answer;
    }
    
    /**
     * Creates a preconfigured SegmentMsg answer from a BackEnd request
     * @return SegmentMsg
     */
    protected function createSegmentAnswerFromBackend(string $connectionId, array $segment, string $sessionId, string $command = null): ?SegmentMsg {
        $answer = SegmentMsg::create(self::CHANNEL_NAME, $command);
        /* @var $answer SegmentMsg */
        $answer->segmentId = (int) $segment['id'];
        
        $userGuid = $this->instance->getSession($sessionId, 'userGuid');
        $conn = $this->findConnection($connectionId, $sessionId);
        $answer->trackingId = $this->getUserTrackingId($conn->openedTask, $userGuid);
        
        $answer->connectionId = $connectionId;
        return $answer;
    }
    
    /*************************
     * Backend Methods
     *
     * WARNING: integer IDs must be explicitly converted to INT in the backend methods before sending to the frontend!
     *************************/
    
    /**
     * handles a segment save (PUT) in translate5.
     * Segment saving can not be handled on websocket level, since if the segmentsave is triggered via websockets, the segment data is not yet in the DB.
     * To solve that we would have to invoke in one of the frontend final save callbacks - or as it is done now just on the segment PUT in translate5 backend.
     * @param string $connectionId
     * @param array $segment
     * @param string $sessionId
     */
    public function segmentSave(string $connectionId, array $segment, string $sessionId) {
        //if the segment was a master segment of a changealike request,
        // we may not process save now, but when change alikes were processed!
        if(!empty($this->alikeSegments[$segment['id']])) {
            return;
        }
        
        //check the connection of the saver to exclude it in the answer
            //the session must match too, otherwise the connectionId was spoofed
            //no connection stored to the saved segment, do nothing
        $this->releaseLocalSegment($segment['id']);
        $saverConn = $this->findConnection($connectionId, $sessionId);
        
        if(empty($saverConn) || $saverConn->connectionId !== $connectionId || $saverConn->sessionId !== $sessionId) {
            return;
        }
        
        $answer = $this->createSegmentAnswerFromBackend($connectionId, $segment, $sessionId, 'segmentSave');
        $this->sendToTaskUsers($segment['taskGuid'], $sessionId, $answer, $saverConn);
    }

    public function commentChanged(string $connectionId, string $typeOfChange, array $comment, string $sessionId) {
        $this->sendToTaskUsers($comment['taskGuid'], $sessionId, FrontendMsg::create(self::CHANNEL_NAME, 'commentChanged', [
            'comment' => $comment,
            'connectionId' => $connectionId,
            'typeOfChange' => $typeOfChange,
        ]));
    }

    /**
     * Unlocks all alike segments to a given master segment.
     * @param string $connectionId
     * @param array $segment
     * @param string $sessionId
     */
    public function segmentAlikeSave(string $connectionId, array $segment, string $sessionId) {
        //get the triggering connection via edited segment
        $connectionToExclude = $this->editedSegments[$segment['id']] ?? null;

        //get the alike IDs and remove them from the alike list, so that the master segment can be processed
        if(!empty($this->alikeSegments[$segment['id']])) {
            $alikes = $this->alikeSegments[$segment['id']];
            unset ($this->alikeSegments[$segment['id']]);
        }
        //save the master segment
        $this->segmentSave($connectionId, $segment, $sessionId);
        if(empty($alikes)) {
            return;
        }
        $answer = $this->createSegmentAnswerFromBackend($connectionId, $segment, $sessionId, 'segmentSave');
        //if there were alikeIDs we have to process each of it
        foreach($alikes as $alikeId) {
            //release the alikeId locally
            $this->releaseLocalSegment($alikeId);
            //trigger segment update in the browser
            $answer->segmentId = (int) $alikeId;
            $this->sendToTaskUsers($segment['taskGuid'], $sessionId, $answer, $connectionToExclude);
        }
    }
    
    /**
     * is invoked when the alike segments of a requested master segment are loaded.
     * Triggers locking of the alike segments in the browsers!
     *
     * @param string $connectionId
     * @param array $masterSegment
     * @param string $sessionId
     * @param array $alikeIds
     */
    public function segmentAlikesLoaded(string $connectionId, array $masterSegment, string $sessionId, array $alikeIds) {
        //get current connection
        if(empty($this->editedSegments[$masterSegment['id']])) {
            $this->instance->getLogger()->warn('segmentAlikesLoaded: requested master segment is not edited anymore: '.$masterSegment['id']);
            return;
        }
        $currentConnection = $this->editedSegments[$masterSegment['id']];
        if($currentConnection->connectionId !== $connectionId) {
            $this->instance->getLogger()->error('segmentAlikesLoaded: connection ID mismatch');
            return;
        }
        
        $userGuid = $this->instance->getSession($sessionId, 'userGuid');
        $trackingId = $this->getUserTrackingId($currentConnection->openedTask, $userGuid);
        
        //try to lock all $alikeIds for this connection
        $locked = [];
        foreach($alikeIds as &$alikeId) {
            $alikeId = (int) $alikeId;
            if(empty($this->editedSegments[$alikeId])) {
                $locked[] = $alikeId;
                $this->editedSegments[$alikeId] = $currentConnection;
                continue;
            }
            // if the segment is already locked by myself, all is OK too.
            if($this->editedSegments[$alikeId] === $currentConnection) {
                $locked[] = $alikeId; // it is unclear if this is correct, what is when the lock is coming from a direct open and not an alike open?
                continue;
            }
            //unlock the previously locked segments, since we are going to send a NAK and break the main foreach loop
            foreach($locked as $unlockId) {
                unset($this->editedSegments[$unlockId]);
            }
            
            //the segment with the ID in alikeId is already in use, so NAK that request:
            $this->releaseLocalSegment($masterSegment['id']);
            
            //if one of the alikes is already locked by someone different - we have to send a NAK to the one who opened the master segment
            FrontendMsg::create(self::CHANNEL_NAME, 'segmentOpenNak', [
                'segmentId' => (int) $masterSegment['id'],
                'trackingId' => $trackingId,
                'connectionId' => $currentConnection->connectionId,
            ], $currentConnection)
            ->send();
            
            //now we have to cancel the whole alike locking
            return;
        }
        
        //storing the alikeIds to the current masterSegment
        $this->alikeSegments[$masterSegment['id']] = $alikeIds;
        
        //ensure that master segment ID is in the lock list for the result too
        $alikeIds[] = (int) $masterSegment['id'];
        
        //send all other users that the segments should be locked
        $this->sendToTaskUsers($masterSegment['taskGuid'], $sessionId, FrontendMsg::create(self::CHANNEL_NAME, 'segmentLocked', [
            'segmentId' => $alikeIds, //maybe integer or array of int!
            'trackingId' => $trackingId,
            'connectionId' => $connectionId,
        ]), $currentConnection);
    }
    
    /**
     * Open the task for the session
     * @param array $task
     * @param string $sessionId
     * @param array $userTracking
     */
    public function open(array $task, string $sessionId, array $userTracking) {
        if(!isset($this->taskToSessionMap[$task['taskGuid']]) || !is_array($this->taskToSessionMap[$task['taskGuid']])) {
            $this->taskToSessionMap[$task['taskGuid']] = [];
        }
        $this->taskToSessionMap[$task['taskGuid']][] = $sessionId;
        $this->taskToSessionMap[$task['taskGuid']] = array_unique($this->taskToSessionMap[$task['taskGuid']]);

        $this->updateUserTracking($task['taskGuid'], $userTracking, $sessionId);
    }
    
    /**
     * Close the task for the session
     * @param array $task
     * @param string $sessionId
     */
    public function close(array $task, string $sessionId, string $connectionId) {
        $this->updateOnlineUsers($sessionId, $task['taskGuid'], false);
        
        $this->sendToTaskUsers($task['taskGuid'], $sessionId, FrontendMsg::create(self::CHANNEL_NAME, 'segmentselect', [
            'segmentId' => 0,
            'trackingId' => 0,
            'connectionId' => $connectionId,
        ]));
        
        //release all remaining edited segments on task leave for that task
        foreach($this->editedSegments as $segmentId => $conn) {
            if($conn->connectionId === $connectionId) {
                $this->releaseLocalSegment($segmentId);
            }
        }
        
        $idx = array_search($sessionId, $this->taskToSessionMap[$task['taskGuid']] ?? []);
        if($idx !== false) {
            unset($this->taskToSessionMap[$task['taskGuid']][$idx]);
        }
        
        if(empty($this->taskToSessionMap[$task['taskGuid']])) {
            unset($this->taskUserTracking[$task['taskGuid']]);
        }
        
        $result = FrontendMsg::create($this->instance::CHANNEL_INSTANCE, 'notifyUser', [
            'message' => 'taskClosedInOtherWindow',
            'taskId' => $task['id'],
        ]);
        foreach($this->instance->getConnections() as $conn) {
            /* @var $conn ConnectionInterface */
            //send the notification to all connections of the same session but not to the connection which triggered the logout / task close
            if($conn->sessionId === $sessionId && $conn->connectionId !== $connectionId) {
                //must be logged explicitly, since we directly call send on the connection here
                $result->logSend();
                //and send the message
                $conn->send((string) $result);
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Translate5\FrontEndMessageBus\Channel::stopSession()
     */
    public function stopSession(string $sessionId, string $connectionId) {
        $tasks = array_keys($this->taskToSessionMap);
        foreach($tasks as $taskGuid) {
            //close searches for the given sessionId in the sessions of the task and removes it
            $this->close(['taskGuid' => $taskGuid], $sessionId, $connectionId);
        }
    }
    
    /**
     * Triggers a reload of the active task in the GUI
     * @param int $taskGuid
     * @param int $taskId optional, since not always given in Backend. Since the frontend uses IDs, we also should send the id here where possible, so the lookup in the GUI is faster
     * @param string $excludeConnection optional, a connectionid which should be ignored (mostly the initiator, since he has already the latest task). defaults to null
     */
    public function triggerReload(string $taskGuid, int $taskId = 0, string $excludeConnection = null) {
        $msg = FrontendMsg::create(self::CHANNEL_NAME, 'triggerReload', [
            'taskGuid' =>  $taskGuid,
            'taskId' =>  $taskId,
        ]);
        $msg->logSend();
        foreach($this->instance->getConnections() as $conn) {
            if(empty($excludeConnection) || $excludeConnection !== $conn->connectionId) {
                $conn->send((string) $msg);
            }
        }
    }

    /**
     * Updates the progress of the task in the GUI
     * @param int $taskGuid
     * @param float $progress
     */
    public function updateProgress(string $taskGuid, float $progress) {
        $msg = FrontendMsg::create(self::CHANNEL_NAME, 'updateProgress', [
            'taskGuid' =>  $taskGuid,
            'progress' =>  $progress,
        ]);
        $msg->logSend();
        foreach($this->instance->getConnections() as $conn) {
            $conn->send((string) $msg);
        }
    }
    
    /**
     * Updates the user tracking data for the given taskGuid
     * @param string $taskGuid
     * @param array $userTracking
     * @param string $sessionId optional, if omitted the frontend online state is not updated
     */
    public function updateUserTracking(string $taskGuid, array $userTracking, string $sessionId = '') {
        $userTracking = array_combine(array_column($userTracking, 'userGuid'), $userTracking);
        //first we have to merge back the current isOnline info
        if(!empty($this->taskUserTracking[$taskGuid])) {
            foreach($this->taskUserTracking[$taskGuid] as $userGuid => $tracking) {
                if(empty($userTracking[$userGuid])) {
                    continue;
                }
                settype($tracking['isOnline'], 'boolean');
                $userTracking[$userGuid]['isOnline'] = $tracking['isOnline'];
            }
        }
        
        $this->taskUserTracking[$taskGuid] = $userTracking;
        
        //if we have a sessionId, this session was openeing a task, so lets update the GUI therefore
        if(!empty($sessionId)) {
            $this->updateOnlineUsers($sessionId, $taskGuid, true);
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Translate5\FrontEndMessageBus\Channel::debug()
     */
    public function debug(): array {
        $segments = [];
        foreach($this->editedSegments as $segId => $conn) {
            $segments[$segId] = $conn->connectionId;
        }
        return [
            'userTracking' => $this->taskUserTracking,
            'taskToSessions' => $this->taskToSessionMap,
            'editedSegments' => $segments
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see \Translate5\FrontEndMessageBus\Channel::getName()
     */
    public function getName(): string {
        return self::CHANNEL_NAME;
    }
    
    /**
     * finds a connection instance to a given ID and sessionId
     * @param string $connectionId
     * @param string $sessionId
     * @return ConnectionInterface|null
     */
    protected function findConnection(string $connectionId, string $sessionId): ?ConnectionInterface {
        $conn = $this->instance->getConnection($connectionId);
        //the session must match too, otherwise the connectionId was spoofed
        if(!empty($conn) && isset($conn->sessionId) && $conn->sessionId === $sessionId) {
            if(!isset($conn->openedTask)) {
                $conn->openedTask = '';
            }
            return $conn;
        }
        return null;
    }
    
    /**
     * returns either the userTrackingId to a given task user combination or 0 if nothing found
     * @param string $taskGuid
     * @param string $userGuid
     * @return integer
     */
    protected function getUserTrackingId(string $taskGuid = null, string $userGuid = null): int {
        return $this->taskUserTracking[$taskGuid][$userGuid]['id'] ?? 0;
    }
}
