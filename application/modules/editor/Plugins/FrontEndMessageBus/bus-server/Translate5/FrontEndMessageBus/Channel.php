<?php
namespace Translate5\FrontEndMessageBus;

/**
 * abstract base channel
 */
abstract class Channel {
    /**
     * @var AppInstance
     */
    protected $instance;
    
    public function __construct(AppInstance $instance) {
        $this->instance = $instance;
    }
    
    public function __call($name, $args) {
        throw new \BadMethodCallException($name. ' not found');
    }
    
    /**
     * Returns debug information about this channel
     */
    abstract function debug(): array;
}