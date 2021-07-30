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

namespace Translate5\FrontEndMessageBus;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\Msg;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Message\BackendMsg;

/**
 * contains the connections and channels to one translate5 instance
 */
class AppInstance {
    use FrontendMsgValidator;
    const CHANNEL_INSTANCE = 'instance';
    
    /**
     * sessionId to user map
     * @var array
     */
    protected $sessions = [];
    
    /**
     * Connections to one instance
     * @var \SplObjectStorage
     */
    protected $connections = null;
    
    /**
     * Maps connection IDs to theire connection object
     * @var array
     */
    protected $connectionIdMap = [];
    
    /**
     * Contains all not yet verified connectionIds
     * @var array
     */
    protected $unverifiedConnections = [];
    
    /**
     * @var array
     */
    protected $channels;
    
    /**
     * @var Logger
     */
    protected $logger;
    
    /**
     * @var string
     */
    protected $serverId;
    
    /**
     * @var string
     */
    protected $serverName = 'not set yet';
    
    /**
     * Contains the metrics of the current instance
     * @var array
     */
    protected $metrics;
    
    protected $lastAccess = null;
    
    public function __construct($serverId) {
        $this->connections = new \SplObjectStorage;
        $this->logger = Logger::getInstance()->cloneMe('FrontEndMessageBus - App Instance '.$serverId);
        $this->logger->info('attached new instance');
        $this->channels = [];
        $this->serverId = $serverId;
        $this->updateLastAccess();
    }
    
    /**
     * sets the last access timestamp
     * return int the current timestamp
     */
    protected function updateLastAccess(): int {
        return $this->lastAccess = time();
    }
    
    /**
     * returns the last access timestamp
     */
    public function getLastAccess() {
        return $this->lastAccess;
    }
    
    /**
     * sets the instance name - to identify the instance
     * @param string $name
     */
    public function setInstanceName(string $name) {
        $this->serverName = $name;
    }
    
    /**
     * return the connections of the instance
     * @return \SplObjectStorage
     */
    public function getConnections(): \SplObjectStorage {
        return $this->connections;
    }
    
    /**
     * returns the instance logger
     * @return Logger
     */
    public function getLogger(): Logger {
        return $this->logger;
    }
    
    /**
     * return the sessions of the instance
     * @return array
     */
    public function getSessions(): array {
        return $this->sessions;
    }
    
    /**
     * Returns the user array to the given sessionId, or null if not found.
     * If the optional $field is given, the valueof the user array with same name is returned.
     * @param string $sessionId
     * @param string  $field optional, if given return this field of the stored user array instead the user array itself
     * @return mixed
     */
    public function getSession($sessionId, $field = null) {
        if(empty($this->sessions[$sessionId])) {
            return null;
        }
        if(is_null($field)) {
            return $this->sessions[$sessionId];
        }
        return $this->sessions[$sessionId][$field];
    }
    
    /**
     * Attach the given conne3ction to the application instance
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn) {
        $timestamp = $this->updateLastAccess();
        //if the connection sessionId does not exist, this is either a malicious request or a reconnect after a socket server restart
        if(empty($this->sessions[$conn->sessionId])) {
            $this->unverifiedConnections[$conn->connectionId] = $timestamp;
        }
        $this->connectionIdMap[$conn->connectionId] = $conn;
        $this->connections->attach($conn);
        $this->eachChannel(__FUNCTION__, $conn);
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn) {
        $this->eachChannel(__FUNCTION__, $conn);
        $this->connections->detach($conn);
        unset($this->connectionIdMap[$conn->connectionId]);
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->eachChannel(__FUNCTION__, $conn, $e);
    }
    
    /**
     * calls a method on each channel and collects the results
     * @param string $method
     * @param mixed ...$args
     * @return mixed[]
     */
    protected function eachChannel($method, ... $args){
        $result = [];
        foreach($this->channels as $channel) {
            $result[$channel->getName()] = call_user_func_array([$channel, $method], $args);
        }
        return $result;
    }
    
    /**
     * Passes a back-end message into one Instance
     * @param BackendMsg $msg
     */
    public function passBackendMessage(BackendMsg $msg) {
        $this->updateLastAccess();
        //INFO: this is the handler for all messages comming from the t5 backend
        settype($msg->payload, 'array');
        //for security reasons messages to the instance may only come from the Backend!
        if($msg->channel !== self::CHANNEL_INSTANCE) {
            //in the backend the payload is a numerc array, which we can pass directly as parameters (as result the vars are named in the function then)
            return call_user_func_array([$this->getChannel($msg->channel), $msg->command], $msg->payload);
        }
        if(!method_exists($this, $msg->command)) {
            $this->logger->error('Message command not found!', $msg);
            return null;
        }
        return call_user_func_array([$this, $msg->command], $msg->payload);
    }
    
    /**
     * Passes a front-end message into one Instance
     * @param FrontendMsg $msg
     * @param ConnectionInterface $conn
     */
    public function passFrontendMessage(FrontendMsg $msg, ConnectionInterface $conn) {
        $this->updateLastAccess();
        $this->cleanUpUnverifiedConnections();
        //INFO: here we will get a message from the t5 frontend
        //$conn is Ratchet\WebSocket\WsConnection
        //do we have to diff between frontend and backend messages?
        settype($msg->payload, 'array');
        settype($msg->channel, 'string');
        if($msg->channel === self::CHANNEL_INSTANCE) {
            $channel = $this;
        }
        else {
            $channel = $this->getChannel($msg->channel);
        }
        //this is also triggered if the connection uses an invalid sessionId
        if(empty($this->sessions[$conn->sessionId])) {
            //the session from the GUI is not known to the server, so we trigger a resync per session
            //TODO possible improvement here: only send one resync to the GUIs (mutex here) and this GUI requests then the resync for all sessions
            // pro: only one resync request per instance then
            // con: we sync all sessions from the session table, also API sessions etc, instead only the ones which are only used by GUIs
            // con: this interferes with session security here
            if(empty($conn->messageQueue)) {
                $conn->messageQueue = new \SplQueue;
                FrontendMsg::create(self::CHANNEL_INSTANCE, 'resyncSession', [], $conn)->send();
            }
            $conn->messageQueue->enqueue($msg);
            return;
        }
        //from frontend we receive a list of named parameters in the payload
        if($channel->isValidFrontendCall($msg->command)) {
            call_user_func_array([$channel, $msg->command], [$msg]);
        }
    }

    /**
     * returns the connection to the given conn ID or null, if nothing found
     * @param string $connectionId
     * @return ConnectionInterface|NULL
     */
    public function getConnection($connectionId): ?ConnectionInterface {
        return $this->connectionIdMap[$connectionId] ?? null;
    }
    
    /**
     * returns the channel instance to the given channel
     * @param string $channel
     * @return Channel
     */
    protected function getChannel(string $channel): ?Channel {
        if(!empty($this->channels[$channel])) {
            return $this->channels[$channel];
        }
        
        $channelCls = 'Translate5\\FrontEndMessageBus\\Channel\\'.ucfirst($channel);
        if(!class_exists($channelCls)) {
            $this->logger->error('Channel class not found: '.$channel);
            return null;
        }
        return $this->channels[$channel] = new $channelCls($this);
    }
    
    /************************
     * Instance Backend Methods:
     ************************/
    
    /**
     * Store the session id and user
     * @param string $sessionId
     * @param array $user
     */
    protected function startSession(string $sessionId, array $user) {
        $this->sessions[$sessionId] = $user;
        $this->eachChannel(__FUNCTION__, $sessionId, $user);
    }
    
    /**
     * Store the session id and user
     * @param string $sessionId
     * @param string $connectionId the connection which requested the "stopSession"
     */
    protected function stopSession(string $sessionId, $connectionId) {
        unset($this->sessions[$sessionId]);
        $this->eachChannel(__FUNCTION__, $sessionId, $connectionId);
    }
    
    /**
     * Processes queued frontend messages previously not processable due data not in sync
     * @param string $connectionId
     */
    protected function resyncDone(string $connectionId) {
        //process messages in the queue of that connection
        $conn = $this->connectionIdMap[$connectionId] ?? null;
        if(empty($conn)) {
            return;
        }
        
        //if the resync was successfull, this means the connection has a valid session
        if(!empty($this->unverifiedConnections[$connectionId])) {
            unset($this->unverifiedConnections[$connectionId]);
        }
        
        //TODO implement messagequeue in a more general manner, currently only usable for resync instances
        
        if(!isset($conn->messageQueue)) {
            return;
        }
        
        //we have to decouple the messageQueue from the connection and process it independantly,
        // otherwise we may stuck in an endless loop here, when the dequeued message is directly queued again in passFrontendMessage
        $queue = $conn->messageQueue;
        /* @var $queue \SplQueue */
        unset($conn->messageQueue); //ensure that for that connection the queue is deleted
        while(!$queue->isEmpty()) {
            $message = $queue->dequeue();
            $this->passFrontendMessage($message, $conn);
        }
    }
    
    /**
     * Close and clean up unverfied connections
     */
    protected function cleanUpUnverifiedConnections() {
        if(empty($this->unverifiedConnections)) {
            return;
        }
        foreach($this->unverifiedConnections as $connectionId => $timestamp) {
            //$lastAccess has the stamp of the current run
            if($this->lastAccess - $timestamp < 10) {
                continue;
            }
            //if the unverifiedConnection does not resync with in 10 Seconds we assume a malicious one and we close the connection
            $conn = $this->connectionIdMap[$connectionId] ?? null;
            if($conn) {
                $this->logger->warn('Connection '.$connectionId.' closed since not resynced in 10 seconds');
                $conn->close();
            }
            unset($this->unverifiedConnections[$connectionId]);
        }
    }
    
    /**
     * Triggers a reload of the given store and optionally a record of that store. Only in all connections (no connection exclusion).
     * @param string $storeId
     * @param int $recordId
     */
    protected function triggerReload(string $storeId, int $recordId = null) {
        $msg = FrontendMsg::create(self::CHANNEL_INSTANCE, 'triggerReload',[
            'storeId' => $storeId,
            'recordId' => $recordId,
        ]);
        $msg->logSend();
        foreach($this->getConnections() as $conn) {
            $conn->send((string) $msg);
        }
    }
    /**
     * Logs that the ping was received
     */
    protected function ping(Msg $msg = null) {
        if($msg instanceof FrontendMsg){
            FrontendMsg::create(self::CHANNEL_INSTANCE, 'pong', [], $msg->conn)->send();
            $this->logger->info('Pinged from Frontend');
            return;
        }
        $this->logger->info('Pinged from Backend');
    }
    
    public function debug(): array {
        $data = [
            'instance' => $this->serverId,
            'instanceName' => $this->serverName,
            'metrics' => $this->metrics,
            'lastAccess' => $this->lastAccess,
            'channels' => [],
            'sessions' => $this->sessions,
            'connections' => [],
        ];
        foreach($this->connections as $conn) {
            $data['connections'][] = [
                'sessionId' => $conn->sessionId,
                'instance' => $conn->serverId,
                'connectionId' => $conn->connectionId,
            ];
        }
        foreach($this->channels as $channel) {
            $data['channels'][get_class($channel)] = $channel->debug();
        }
        $data['connectionCount'] = count($this->connections);
        return $data;
    }
    
    /**
     * Updates the metrics from PHP backend and sets instance infos
     * @param array $metrics
     */
    public function updateMetrics(array $metrics) {
        foreach($metrics as &$metric) {
            $metric = (object) $metric;
            if(is_object($metric) && is_array($metric->data)) {
                foreach($metric->data as &$data) {
                    $info = [
                        'serverId' => $this->serverId,
                        'serverName' => $this->serverName,
                    ];
                    if(is_array($data['tags'])) {
                        $data['tags'] = array_merge($data['tags'], $info);
                    }
                    else {
                        $data['tags'] = $info;
                    }
                }
            }
        }
        $this->metrics = $metrics;
    }
    
    public function garbageCollection(array $existingSessions) {
        $this->cleanUpUnverifiedConnections();
        $toDelete = array_diff(array_keys($this->sessions), $existingSessions);
        foreach($toDelete as $sessionId) {
            unset($this->sessions[$sessionId]);
        }
        foreach($this->connections as $conn) {
            if(isset($conn->sessionId) && in_array($conn->sessionId, $toDelete)) {
                // send the GUI a reload message.
                FrontendMsg::create($this::CHANNEL_INSTANCE, 'notifyUser', [
                    'message' => 'sessionDeleted'
                ], $conn)->send();
                $conn->close();
            }
        }
        $result = [
            'sessionsDeletedInInstance' => $toDelete
        ];
        return array_merge($this->eachChannel('garbageCollection', $existingSessions), $result);
    }
}