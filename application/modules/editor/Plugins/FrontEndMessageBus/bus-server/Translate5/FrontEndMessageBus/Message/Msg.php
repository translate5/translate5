<?php
namespace Translate5\FrontEndMessageBus\Message;

/**
 */
abstract class Msg {
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
     * the payload data
     * @var mixed
     */
    public $payload;
    
    public function __construct(array $msgData = null) {
        if(!empty($msgData)) {
            // we assume all data must be given if there is a msgData to init from
            $keys = array_keys(get_object_vars($this));
            foreach($keys as $key) {
                if($key == 'payload' && $this instanceof BackendMsg) {
                    $msgData[$key] = json_decode($msgData[$key], true);
                    //FIXME JSON error handling / logging
                }
                $this->$key = $msgData[$key];
            }
        }
    }
    
    /**
     * @return string[]|mixed[]
     */
    public function toDbgArray() {
        return [
            'class' => get_class($this),
            'channel' => $this->channel,
            'command' => $this->command,
            'payload' => $this->payload,
        ];
    }
    
    public function __toString() {
        //FIXME error handling?
        return json_encode($this);
    }
}