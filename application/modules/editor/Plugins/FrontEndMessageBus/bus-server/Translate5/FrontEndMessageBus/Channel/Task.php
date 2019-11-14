<?php
namespace Translate5\FrontEndMessageBus\Channel;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\SegmentMsg;
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
        if(!empty($this->editedSegments[$answer->segmentId])) {
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
        $this->sendToOthersOnTask($answer);
    }
    
    /**
     * The Browser sending this request receives all locked segments
     * TODO alse send the selected segments
     * @param FrontendMsg $request
     */
    public function resyncTask(FrontendMsg $request) {
        //sending the requesting user all locked segments
        $result = FrontendMsg::create(self::CHANNEL_NAME, 'segmentLocked', [], $request->conn);
        foreach($this->editedSegments as $segmentId => $conn) {
            $result->payload['segmentId'] = $segmentId;
            $result->payload['userGuid'] = $this->instance->getSession($conn->sessionId, 'userGuid');
            $result->payload['connectionId'] = $conn->connectionId;
            $result->send();
        }
    }
    
    public function segmentCancelAlikes(FrontendMsg $request) {
        $answer = $this->createSegmentAnswerFromFrontend($request, 'segmentSave');
        if(empty($answer)) {
            return;
        }
        
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
        if(!empty($this->alikeSegments[$answer->segmentId])) {
            $alikes = $this->alikeSegments[$answer->segmentId];
        }
        $this->releaseLocalSegment($request->conn->openedSegmentId ?? 0);
        //we delegate the leaving call to the leaveAlikes,so we send only one message
        $alikes[] = $answer->segmentId;
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
        
        if(!empty($this->alikeSegments[$segmentId])) {
            unset($this->alikeSegments[$segmentId]);
        }
        if(!empty($this->editedSegments[$segmentId])) {
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
        $this->releaseLocalSegment($conn->openedSegmentId ?? 0); 
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
        if(!empty($command)) {
            $answer->command = $command;
        }
        $answer->channel = self::CHANNEL_NAME;
        $answer->userGuid = $this->instance->getSession($request->conn->sessionId, 'userGuid');
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
        $answer->userGuid = $this->instance->getSession($sessionId, 'userGuid');
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
     * @param array $alikes
     */
    public function segmentAlikesLoaded(string $connectionId, array $masterSegment, string $sessionId, array $alikes) {
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
        //try to lock all $alikes for this connection
        foreach($alikes as &$alikeId) {
            $alikeId = (int) $alikeId;
            if(empty($this->editedSegments[$alikeId])) {
                $this->editedSegments[$alikeId] = $currentConnection;
                continue;
            }
            //the segment with the ID in alikeId is already in use, so NAK that request:
            $this->releaseLocalSegment($masterSegment['id']);
            //if one of the alikes is already locked by someone different - we have to send a NAK to the one who opened the master segment
            FrontendMsg::create(self::CHANNEL_NAME, 'segmentOpenNak', [
                'segmentId' => (int) $masterSegment['id'],
                'userGuid' => $this->instance->getSession($sessionId, 'userGuid'),
                'connectionId' => $currentConnection->connectionId,
            ], $currentConnection)
            ->send();
            return;
        }
        
        //storing the alikeIds to the current masterSegment
        $this->alikeSegments[$masterSegment['id']] = $alikes;
        
        //ensure that master segment ID is in the lock list for the result too
        $alikes[] = (int) $masterSegment['id'];
        
        //send all other users that the segments should be locked
        $this->sendToTaskUsers($masterSegment['taskGuid'], $sessionId, FrontendMsg::create(self::CHANNEL_NAME, 'segmentLocked', [
            'segmentId' => $alikes, //maybe integer or array of int!
            'userGuid' => $this->instance->getSession($sessionId, 'userGuid'),
            'connectionId' => $connectionId,
        ]), $currentConnection);
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
    
    /**
     * Triggers a reload of the given store and optionally a record of that store only in all connections
     * @param string $storeId
     * @param string $excludeConnection optional, a connectionid which should be ignored (mostly the initiator, since he has already the latest task). defaults to null
     * @param int $recordId
     */
    public function triggerReload(string $taskGuid, string $excludeConnection = null) {
        $sessionsForTask = $this->taskToSessionMap[$taskGuid] ?? [];
        $msg = FrontendMsg::create(self::CHANNEL_NAME, 'triggerReload');
        $msg->logSend();
        foreach($this->instance->getConnections() as $conn) {
            $include = empty($excludeConnection) || $excludeConnection !== $conn->connectionId;
            if(in_array($conn->sessionId, $sessionsForTask) && $include) {
                $conn->send((string) $msg);
            }
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
            'taskToSessions' => $this->taskToSessionMap,
            'editedSegments' => $segments
        ];
    }
    
    /**
     * finds a connection instance to a given ID and sessionId
     * @param string $connectionId
     * @param string $sessionId
     * @return ConnectionInterface|null
     */
    protected function findConnection(string $connectionId, string $sessionId): ?ConnectionInterface {
        foreach($this->instance->getConnections() as $conn) {
            //the session must match too, otherwise the connectionId was spoofed
            if($conn->connectionId === $connectionId && $conn->sessionId === $sessionId) {
                return $conn;
            }
        }
        return null;
    }
}