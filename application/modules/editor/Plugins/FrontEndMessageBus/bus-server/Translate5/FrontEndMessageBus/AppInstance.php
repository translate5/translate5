<?php
namespace Translate5\FrontEndMessageBus;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\Msg;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Message\BackendMsg;

/**
 * FIXME general problems todos: 
 * - catch up valid sessions and connections and open tasks in instance after server reload
 * - error handling if socket server not reachable
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
    
    public function __construct($serverId) {
        error_log("NEW INSTANCE ".$serverId);
        $this->connections = new \SplObjectStorage;
        $this->logger = clone Logger::getInstance();
        $this->logger->setDomain('FrontEndMessageBus - App Instance '.$serverId);
        $this->channels = [];
        $this->serverId = $serverId;
    }
    
    /**
     * return the connections of the instance
     * @return \SplObjectStorage
     */
    public function getConnections(): \SplObjectStorage {
        return $this->connections;
    }
    
    /**
     * return the sessions of the instance
     * @return array
     */
    public function getSessions(): array {
        return $this->sessions;
    }
    
    /**
     * Attach the given conne3ction to the application instance
     * @param ConnectionInterface $conn
     */
    public function connect(ConnectionInterface $conn) {
        $this->connections->attach($conn);
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function close(ConnectionInterface $conn) {
        error_log("CLOSE ".$conn->sessionId);
        $this->connections->detach($conn);
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
            $this->logger->info('back-end instance call', $msg);
            call_user_func_array([$this, $msg->command], $msg->payload);
            $channel = $this;
        }
        else {
            $channel = $this->getChannel($msg->channel);
        }
        $this->logger->info('back-end call', $msg);
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
            //currently we do nothing here, since the session from the GUI is not known to the server! 
            // TODO should we trigger a resync of the sessions into the MessageBus via the frontend? (mutex run on the server side) 
            return;
        }
        $this->logger->info('front-end call', $msg->toDbgArray());
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
        //TODO a map from a user to his sessions will also be needed. By userId or userGuid? probably guid, since in task useage we are also using the guids
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
     * Logs that the ping was received
     */
    protected function ping($msg = null) {
        if($msg instanceof FrontendMsg){
            $result = new FrontendMsg();
            $result->channel = self::CHANNEL_INSTANCE;
            $result->command = 'pong';
            $msg->conn->send((string) $result);
            $this->logger->info('Pinged from Frontend');
            return;
        }
        $this->logger->info('Pinged from Backend');
    }
    
    public function debug(): array {
        $data = [
            'channels' => [],
            'sessions' => $this->sessions,
            'connections' => [],
        ];
        foreach($this->connections as $conn) {
            $data['connections'][] = [
                'sessionId' => $conn->sessionId,
                'instance' => $conn->serverId,
            ];
        }
        foreach($this->channels as $channel) {
            $data['channels'][get_class($channel)] = $channel->debug();
        }
        $data['connectionCount'] = count($this->connections);
        return $data;
    }
}