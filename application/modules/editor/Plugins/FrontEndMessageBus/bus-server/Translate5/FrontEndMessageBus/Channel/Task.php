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
        if(!empty($request->conn->openedSegmentId)) {
            unset($this->editedSegments[$request->conn->openedSegmentId]);
        }
        
        if(empty($this->editedSegments[$segmentId])) {
            $commandToSender = 'segmentOpenAck'; //ACK
            $request->conn->openedSegmentId = $segmentId;
            $this->editedSegments[$segmentId] = $request->conn;
        }
        else {
            $commandToSender = 'segmentOpenNak'; //NAK
        }

        //sending the other users that the segment is locked
        $result = FrontendMsg::create(self::CHANNEL_NAME, 'segmentLocked', [
            'segmentId' => $segmentId,
            'userGuid' => $this->instance->getSession($currentSessionId, 'userGuid'),
            'sessionHash' => $this->instance->getSession($currentSessionId, 'sessionHash'),
        ]);
        
        foreach($this->instance->getConnections() as $conn) {
            /* @var $conn ConnectionInterface */
            if(!in_array($conn->sessionId, $sessionsForTask)) {
                //ignore myself and ignore all other connections not belonging to that task
                continue;
            }
            //since we reuse the FrontendMsg we have to change the command on each answer
            if($conn === $request->conn) {
                $result->command = $commandToSender;
            }
            else {
                $result->command = 'segmentLocked';
            }
            $conn->send((string) $result);
        }
    }
    
    /**
     * react on a segment click from frontend
     * @param FrontendMsg $request
     */
    public function segmentClick(FrontendMsg $request) {
        $currentSessionId = $request->conn->sessionId;
        
        settype($request->payload[0], 'string');
        settype($request->payload[1], 'integer');
        $taskGuid = $request->payload[0];
        
        $sessionsForTask = $this->taskToSessionMap[$taskGuid] ?? [];
        
        if(!in_array($currentSessionId, $sessionsForTask)) {
            //if current session is not valid for task, someone provided manually a different taskGuid
            return;
        }
        
        $result = FrontendMsg::create(self::CHANNEL_NAME, 'segmentselect', [
            'segmentId' => $request->payload[1],
            'userGuid' => $this->instance->getSession($currentSessionId, 'userGuid'),
            'sessionHash' => $this->instance->getSession($currentSessionId, 'sessionHash'),
        ]);
        
        foreach($this->instance->getConnections() as $conn) {
            /* @var $conn ConnectionInterface */
            if($conn === $request->conn || !in_array($conn->sessionId, $sessionsForTask)) {
                //ignore myself and ignore all other connections not belonging to that task
                continue;
            }
            $conn->send((string) $result);
        }
    }
    
    public function segmentOpen(FrontendMsg $request) {
        //we have to send a true / false answer to the current connection wether the segment can be locked or not. 
        // If a segment is already locked, it can not be locked again.
        //since segmentId is unique per instance, a storage like $this->lockedSegments[$segmentId] = $userGuid should be sufficient.
        //return false here should stop 
    }
    
    public function segmentSave(FrontendMsg $request) {
        
    }
    
    /*************************
     * Backend Methods
     *************************/
    
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
        return ['taskToSessions' => $this->taskToSessionMap];
    }
}