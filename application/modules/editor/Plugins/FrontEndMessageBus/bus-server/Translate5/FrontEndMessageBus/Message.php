<?php
namespace Translate5\FrontEndMessageBus;

/**
 */
class Message {
    /**
     * The channel (task|segment etc)
     * @var string
     */
    public $channel;
    
    /**
     * The command
     * @var string
     */
    public $command;
    
    /**
     * The serverId of the translate5 instance from where the message came
     * @var string
     */
    public $instance;
    
    /**
     * the payload data
     * @var mixed
     */
    public $payload;
    
    public function __construct(array $msgData = null) {
        if(!empty($msgData)) {
            // we assume all data must be given if there is a msgData to init from
            $keys = array_keys(get_object_vars($this));
            foreach($keys as $key) {
                $this->$key = $msgData[$key];
            }
        }
    }
    
    public function __toString() {
        //FIXME error handling?
        return json_encode($this);
    }
}