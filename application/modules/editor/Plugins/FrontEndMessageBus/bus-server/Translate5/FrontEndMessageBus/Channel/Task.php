<?php
namespace Translate5\FrontEndMessageBus\Channel;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\AppInstance;
use Translate5\FrontEndMessageBus\Message;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Channel;
use Translate5\FrontEndMessageBus\Message\BackendMsg;

/**
 * Encapsulates logic specific to an opened task in an instance
 */
class Task extends Channel {
    
    /**
     * Maps a taskGuid to a list of sessions where the task is opened
     * @var array
     */
    protected $taskToSessionMap = [];
    
    /*************************
     * Frontend Methods
     *************************/
    
    /**
     * react on a segment click from frontend
     * FIXME implement segment open / save / close similar to this function. 
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
        
        $result = new FrontendMsg();
        $result->channel = 'task'; //convert to segment channel in frontend??? separation unclear
        $result->command = 'segmentselect';
        $result->payload = [
            'segmentId' => $request->payload[1],
            //FIXME in the frontend the userGuid can be used via the anon user stuff to display the userame or the anonymzed one
            //TODO implement and use a hasSession instead, which returns null or userGuid
            'userGuid' => $this->instance->getSessions()[$currentSessionId]['userGuid'], 
        ];
        
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