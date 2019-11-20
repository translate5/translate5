<?php
namespace Translate5\FrontEndMessageBus;
use Ratchet\ComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * abstract base channel
 */
abstract class Channel implements ComponentInterface {
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
    
    /**
     * Attach the given conne3ction to the application instance
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn) {
        //currently do nothing
    }
    
    /**
     * remove the given connection from the application instance
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn) {
        //currently do nothing
    }
    
    /**
     * handle connection errors, if needed
     * @param ConnectionInterface $conn
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        //currently do nothing
    }
    
    public function startSession(string $sessionId) {
        //currently do nothing
    }
    
    /**
     * the given session was stop, handle that in the channel 
     * @param string $sessionId
     * @param string $connectionId the connection which requested the stopSession
     */
    public function stopSession(string $sessionId, string $connectionId) {
        //currently do nothing
    }
}