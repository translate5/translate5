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
                    $this->$key = json_decode($msgData[$key], true);
                    //check for JSON errors
                    if(json_last_error() > 0){
                        $this->logger->error('error on BackendMsg payload (JSON) decode: '.json_last_error_msg(), LOG_HTTP, ['payload' => $msgData[$key]]);
                    }
                    continue;
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
        $result = json_encode($this);
        if($result === false && json_last_error() > 0){
            $this->logger->error('error on BackendMsg (JSON) encode: '.json_last_error_msg(), LOG_HTTP, ['msg' => $this]);
        }
        return $result;
    }
}