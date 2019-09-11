<?php
namespace Translate5\FrontEndMessageBus\Channel;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Message;

/**
 * Pendant in the message bus for editor_Plugins_FrontEndMessageBus_Messages_Task in the server
 */
class Task {
    
    /**
     * @var array
     */
    protected $connections = [];
    
    //FIXME into abstract
    public function __construct(array $connections) {
        error_log("FOOBAR 1");
        $this->connections = $connections;
    }
    
    /**
     * Dummy function to show how to send messages and do stuff
     * @param Message $msg
     */
    public function test(Message $msg) {
        error_log("FOOBAR 2");
        /*
         * Do stuff here with $msg
         */
        
        //for segments the logic that mesage goes only to the users / connections with the same task
        //DUMMY send data
        
        foreach($this->connections as $conn) {
            /* @var $conn ConnectionInterface */
            error_log("Sent to Clients ".$msg);
            $conn->send((string) $msg);
        }
    }
    
    public function open() {
        error_log("OPEN FOO");
    }
    
    public function __call($name, $args) {
        //FIXME error handling, move me in a abstract
        throw new \BadMethodCallException($name. ' not found');
    }
}