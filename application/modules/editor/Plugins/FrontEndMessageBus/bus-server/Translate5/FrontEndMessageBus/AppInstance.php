<?php
namespace Translate5\FrontEndMessageBus;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Message\BackendMsg;

/**
 */
class AppInstance {
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
    
    public function __construct($serverId) {
        $this->connections = new \SplObjectStorage;
        $this->logger = Logger::getInstance()->cloneMe('FrontEndMessageBus - App Instance '.$serverId);
        $this->logger->info('attached new instance');
        $this->channels = [];
        $this->serverId = $serverId;
    }
    
    public function setInstanceName($name) {
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
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->eachChannel(__FUNCTION__, $conn, $e);
    }
    
    protected function eachChannel($method, ... $args){
        foreach($this->channels as $channel) {
            call_user_func_array([$channel, $method], $args);
        }
    }
    
    /**
     * Passes a back-end message into one Instance
     * @param BackendMsg $msg
     */
    public function passBackendMessage(BackendMsg $msg) {
        //INFO: this is the handler for all messages comming from the t5 backend
        settype($msg->payload, 'array');
        //for security reasons messages to the instance may only come from the Backend! 
        if($msg->channel == self::CHANNEL_INSTANCE) {
            if(!method_exists($this, $msg->command)) {
                $this->logger->error('Message command not found!', $msg);
                return;
            }
            call_user_func_array([$this, $msg->command], $msg->payload);
            $channel = $this;
        }
        else {
            $channel = $this->getChannel($msg->channel);
        }
        //in the backend the payload is a numerc array, which we can pass directly as parameters (as result the vars are named in the function then)
        call_user_func_array([$channel, $msg->command], $msg->payload);
        
    }
    
    /**
     * Passes a front-end message into one Instance
     * @param FrontendMsg $msg
     * @param ConnectionInterface $conn
     */
    public function passFrontendMessage(FrontendMsg $msg, ConnectionInterface $conn) {
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
        if(empty($this->sessions[$conn->sessionId])) {
            //the session from the GUI is not known to the server, so we trigger a resync per session
            //TODO possible improvement here: only send one resync to the GUIs (mutex here) and this GUI requests then the resync for all sessions
            // pro: only one resync request per instance then
            // con: we sync all sessions from the session table, also API sessions etc, instead only the ones which are only used by GUIs
            FrontendMsg::create(self::CHANNEL_INSTANCE, 'resyncSession', [], $conn)->send();
            return;
        }
        //from frontend we receive a list of named parameters in the payload
        call_user_func_array([$channel, $msg->command], [$msg]);
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
    }
    
    /**
     * Store the session id and user
     * @param string $sessionId
     */
    protected function stopSession(string $sessionId) {
        unset($this->sessions[$sessionId]);
        //TODO clean session from user in user session map
        //TODO notify all Channels about the session removall??? At least in task the sessionIds are implicitly used in taskToSessionMap and should be cleaned up
    }
    
    /**
     * Triggers a reload of the given store and optionally a record of that store only in all connections
     * @param string $storeId
     * @param int $recordId
     */
    protected function triggerReload(string $storeId, int $recordId = null) {
        $msg = FrontendMsg::create(self::CHANNEL_INSTANCE, 'triggerReload');
        $msg->logSend();
        foreach($this->getConnections() as $conn) {
            $conn->send((string) $msg);
        }
    }
    /**
     * Logs that the ping was received
     */
    protected function ping($msg = null) {
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
}