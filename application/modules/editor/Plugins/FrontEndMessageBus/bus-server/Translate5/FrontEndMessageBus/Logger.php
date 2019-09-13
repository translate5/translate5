<?php
namespace Translate5\FrontEndMessageBus;

/**
 * 
 * @method void fatal() fatal(string $message, $extra = null)
 * @method void error() error(string $message, $extra = null)
 * @method void warn() warn  (string $message, $extra = null)
 * @method void info() info  (string $message, $extra = null)
 * @method void debug() debug(string $message, $extra = null)
 * @method void trace() trace(string $message, $extra = null)
 */
class Logger {
    
    /**
     * @var Logger
     */
    protected static $instance;
    
    protected function __construct() {
        //singleton only
    }
    
    /**
     * @return \Translate5\FrontEndMessageBus\Logger
     */
    public static function getInstance(): Logger {
        if(empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __call($name, $args) {
        $trace = debug_backtrace(2, 2);
        settype($trace[1], 'array');
        settype($trace[1]['class'], 'string');
        settype($trace[1]['function'], 'string');
        $msg = strtoupper($name).' '.$trace[1]['class'].'::'.$trace[1]['function'].': '.$args[0]."\n".print_r($args[1],1);
        echo $msg;
        error_log($msg);
    }
}