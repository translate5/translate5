<?php
namespace Translate5\FrontEndMessageBus\Channel;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\AppInstance;
use Translate5\FrontEndMessageBus\Message;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;

/**
 * Pendant in the message bus for editor_Plugins_FrontEndMessageBus_Messages_Task in the server
 */
class Task {
    
    /**
     * @var AppInstance
     */
    protected $instance;
    
    //FIXME into abstract?
    public function __construct(AppInstance $instance) {
        $this->instance = $instance;
    }
    
    /*
     * Frontend Methods
     */
    
    /**
     * react on a segment click from frontend
     * @param FrontendMsg $request
     */
    public function segmentClick(FrontendMsg $request) {
        $result = new FrontendMsg();
        $result->channel = 'task'; //convert to segment channel in frontend??? separation unclear
        $result->command = 'segmentlock';
        //FIXME check valid session from request! session id is currently not yet send from frontend
        //FIXME check internally in Task Channel if session has opened a specific task (implement with already exisiting task open)
        //FIXME implement also a task close
        
        foreach($this->instance->getConnections() as $conn) {
            if($conn !== $request->conn) { //FIXME filter here also the connections belonging to a specific task only
                /* @var $conn ConnectionInterface */
                $conn->send((string) $result);
            }
        }
    }
    
    /*
     * Backend Methods
     */
    
    /**
     * Dummy function to show how to send messages and do stuff
     * @param Message $msg
     */
    public function test() {
        /*
         * Do stuff here with $msg
         */
        
        //for segments the logic that mesage goes only to the users / connections with the same task
        //DUMMY send data
        $msg = new FrontendMsg();
        $msg->channel = 'task'; //convert to segment channel in frontend??? separation unclear
        $msg->command = 'test';
        foreach($this->instance->getConnections() as $conn) {
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