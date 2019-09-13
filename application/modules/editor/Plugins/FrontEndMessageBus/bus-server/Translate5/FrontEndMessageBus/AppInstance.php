<?php
namespace Translate5\FrontEndMessageBus;
use Translate5\FrontEndMessageBus\Message\Msg;

use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;

/**
 * FIXME general problems todos: 
 * catch up valid sessions and connections in instance after server reload
 * error handling if socket server not reachable
 */
class AppInstance {
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
     * @var Logger
     */
    protected $logger;
    
    public function __construct($serverId) {
        //TODO store serverId internally
        $this->connections = new \SplObjectStorage;
        $this->logger = Logger::getInstance();
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
    
    public function connect(ConnectionInterface $conn) {
        $this->connections->attach($conn);
    }
    
    public function close(ConnectionInterface $conn) {
        $this->connections->detach($conn);
    }
    
    /**
     * Passes a message to one Instance
     * @param Msg $msg
     */
    public function passMessage(Msg $msg) {
        //do we have to diff between frontend and backend messages?
        settype($msg->payload, 'array');
        if($msg->channel == 'instance') {
            if(!method_exists($this, $msg->command)) {
                $this->logger->error('Message command not found!', $msg);
                return;
            }
            call_user_func_array([$this, $msg->command], $msg->payload);
            $channel = $this;
        }
        else {
            //TODO Keep instances of the Channels in memory or create new instances on handle?
            $channelCls = 'Translate5\\FrontEndMessageBus\\Channel\\'.ucfirst($msg->channel);
            if(!class_exists($channelCls)) {
                //FIXME errorhandling if class not found
                error_log("Not Found channel ".$msg->channel);
                return;
            }
            $channel = new $channelCls($this);
        }
        //from frontend we receive a list of named paramaters in the payload
        if($msg instanceof FrontendMsg) {
            call_user_func_array([$channel, $msg->command], [$msg]);
        }
        //in the backend the payload is a numerc array, which we can pass directly as parameters (as result the vars are named in the function then)
        else {
            call_user_func_array([$channel, $msg->command], $msg->payload);
        }
    }
    
    /*
     * Backend Methods: 
     */
    
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
    }
}