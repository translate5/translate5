<?php
namespace Translate5\FrontEndMessageBus;

/**
 * 
 * @method void fatal() fatal(string $message, $domain = null)
 * @method void error() error(string $message, $domain = null)
 * @method void warn() warn  (string $message, $domain = null)
 * @method void info() info  (string $message, $domain = null)
 * @method void debug() debug(string $message, $domain = null)
 * @method void trace() trace(string $message, $domain = null)
 */
class Logger {
    
    /**
     * @var Logger
     */
    protected static $instance;
    
    /**
     * @var string
     */
    protected $domain = 'FrontEndMessageBus';
    
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
    
    /**
     * Clone the logger with customized logging domain
     * @param string $domain
     */
    public function cloneMe(string $domain) {
        $this->domain = $domain;
    }
    
    public function exception(\Exception $e, $domain) {
        $this->__call('Exception', [$domain, $e->__toString()]);
    }
    
    public function __call(string $name, array $args) {
        $message = array_shift($args);
        $second = array_shift($args);
        if(!is_null($second) && is_string($second)) {
            $domain = $second;
            $second = array_shift($args);
        }
        else {
            $domain = $this->domain;
        }
        $msg = strtoupper($name).' - '.$domain.': '.$message;
        if(!is_null($second) && (is_array($second) || is_object($second))) {
            $msg .= "\n".print_r($second,1);
        }
        echo $msg."\n";
        error_log($msg);
    }
}