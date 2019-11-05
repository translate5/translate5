<?php
namespace Translate5\FrontEndMessageBus\Channel;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Channel;

/**
 * Encapsulates logic specific to an opened task in an instance
 */
class Task extends Channel {
    const CHANNEL_NAME = 'task';
    
    /**
     * Maps a taskGuid to a list of sessions where the task is opened
     * @var array
     */
    protected $taskToSessionMap = [];
    
    protected $editedSegments = [];
    
    /*************************
     * Frontend Methods
     *************************/
    public function segmentEditRequest(FrontendMsg $request) {
        $currentSessionId = $request->conn->sessionId;
        
        //the taskGuid
        settype($request->payload[0], 'string');
        $taskGuid = $request->payload[0];
        
        //the segmentId
        settype($request->payload[1], 'integer');
        $segmentId = $request->payload[1];
        
        $sessionsForTask = $this->taskToSessionMap[$taskGuid] ?? [];
        if(!in_array($currentSessionId, $sessionsForTask)) {
            //if current session is not valid for task, someone provided manually a different taskGuid
            return;
        }
        
        //release other segment(s) of this session/connection
        $this->releaseLocalSegment($request->conn->openedSegmentId ?? 0);
        
        //sending the other users that the segment is locked
        $result = FrontendMsg::create(self::CHANNEL_NAME, 'segmentLocked', [
            'segmentId' => $segmentId,
            'userGuid' => $this->instance->getSession($currentSessionId, 'userGuid'),
            'connectionId' => $request->conn->connectionId,
        ]);
        
        if(!empty($this->editedSegments[$segmentId])) {
            $result->command = 'segmentOpenNak'; //NAK
            $request->conn->send((string) $result);
            return;
        }
        
        //send ACK to requesting user
        $result->command = 'segmentOpenAck';
        $request->conn->send((string) $result);
        
        //registered opened segment locally
        $request->conn->openedSegmentId = $segmentId;
        $this->editedSegments[$segmentId] = $request->conn;
        
        //send lock info to other task users
        $result->command = 'segmentLocked';
        $this->sendToTaskUsers($taskGuid, $currentSessionId, $result, $request->conn);
    }
    
    /**
     * react on a segment click from frontend
     * @param FrontendMsg $request
     */
    public function segmentClick(FrontendMsg $request) {
        settype($request->payload[1], 'integer');
        
        $result = FrontendMsg::create(self::CHANNEL_NAME, 'segmentselect', [
            'segmentId' => $request->payload[1],
            'userGuid' => $this->instance->getSession($request->conn->sessionId, 'userGuid'),
            'connectionId' => $request->conn->connectionId,
        ]);
        
        $this->sendToOthersOnTask($request, $result);
    }
    
    public function segmentLeave(FrontendMsg $request) {
        $this->releaseLocalSegment($request->conn->openedSegmentId ?? 0);
        $this->sendToOthersOnTask($request, FrontendMsg::create(self::CHANNEL_NAME, 'segmentLeave', [
            'segmentId' => $request->payload[1],
            'userGuid' => $this->instance->getSession($request->conn->sessionId, 'userGuid'),
            'connectionId' => $request->conn->connectionId,
        ]));
    }
    
    /**
     * Sends the $result msg to all other connections expect the one where $request is coming from
     * @param FrontendMsg $request
     * @param FrontendMsg $result
     */
    protected function sendToOthersOnTask(FrontendMsg $request, FrontendMsg $result) {
        settype($request->payload[0], 'string');
        $this->sendToTaskUsers($request->payload[0], $request->conn->sessionId, $result, $request->conn);
    }
    
    /**
     * Sends the $result FrontendMsg to all connections of a task. Expect to the optionally given $connectionToExclude which will mostly be the initiator itself
     * @param string $taskGuid
     * @param string $currentSessionId
     * @param FrontendMsg $result
     * @param ConnectionInterface $connectionToExclude
     */
    protected function sendToTaskUsers(string $taskGuid, string $currentSessionId, FrontendMsg $result, ConnectionInterface $connectionToExclude = null) {
        $sessionsForTask = $this->taskToSessionMap[$taskGuid] ?? [];
        
        if(!in_array($currentSessionId, $sessionsForTask)) {
            //if current session is not valid for task, someone provided manually a different taskGuid
            return;
        }
        
        foreach($this->instance->getConnections() as $conn) {
            /* @var $conn ConnectionInterface */
            $excludeConnection = !is_null($connectionToExclude) && $conn === $connectionToExclude;
            if($excludeConnection || !in_array($conn->sessionId, $sessionsForTask)) {
                //ignore myself and ignore all other connections not belonging to that task
                continue;
            }
            $conn->send((string) $result);
        }
    }
    
    /**
     * Release an internally locked segment
     * @param ConnectionInterface $conn
     */
    protected function releaseLocalSegment(int $segmentId) {
        if(!empty($segmentId) && !empty($this->editedSegments[$segmentId])) {
            $conn = $this->editedSegments[$segmentId];
            $conn->openedSegmentId = null;
            unset($this->editedSegments[$segmentId]);
        }
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn) {
        $this->releaseLocalSegment($conn->openedSegmentId ?? 0); 
    }
    
    /*************************
     * Backend Methods
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
        $this->releaseLocalSegment($segment['id']);

        //find the connection of the saver to exclude it in the answer
        $connectionToExclude = null;
        foreach($this->instance->getConnections() as $conn) {
            //the session must match too, otherwise the connectionId was spoofed
            if($conn->connectionId === $connectionId && $conn->sessionId === $sessionId) {
                $connectionToExclude = $conn;
                break;
            }
        }
        
        //$exlcudeMySelf über connection Liste und connectionId
        $this->sendToTaskUsers($segment['taskGuid'], $sessionId, FrontendMsg::create(self::CHANNEL_NAME, 'segmentSave', [
            'segmentId' => (int) $segment['id'],
            'userGuid' => $this->instance->getSession($sessionId, 'userGuid'),
            'connectionId' => $connectionId,
        ]), $connectionToExclude);
    }
    
    /**
     * Open the task for the session
     * @param array $task
     * @param string $sessionId
     */
    public function open(array $task, string $sessionId) {
        if(!isset($this->taskToSessionMap[$task['taskGuid']]) || !is_array($this->taskToSessionMap[$task['taskGuid']])) {
            $this->taskToSessionMap[$task['taskGuid']] = [];
        }
        $this->taskToSessionMap[$task['taskGuid']][] = $sessionId;
        $this->taskToSessionMap[$task['taskGuid']] = array_unique($this->taskToSessionMap[$task['taskGuid']]);
    }
    
    /**
     * Close the task for the session
     * @param array $task
     * @param string $sessionId
     */
    public function close(array $task, string $sessionId) {
        $idx = array_search($sessionId, $this->taskToSessionMap[$task['taskGuid']]);
        if($idx !== false) {
            unset($this->taskToSessionMap[$task['taskGuid']][$idx]);
        }
    }
    
    public function debug(): array {
        $segments = [];
        foreach($this->editedSegments as $segId => $conn) {
            $segments[$segId] = $conn->connectionId;
        }
        return [
            'taskToSessions' => $this->taskToSessionMap,
            'editedSegments' => $segments
        ];
    }
}