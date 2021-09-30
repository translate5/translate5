<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

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
     * @return self
     */
    public function cloneMe(string $domain) {
        $new = clone $this;
        $new->domain = $domain;
        return $new;
    }
    
    public function exception(\Exception $e, $domain) {
        $this->__call('Exception', [$domain, $e->__toString()]);
    }
    
    public function __call(string $name, array $args) {
        $date = date('Y-m-d H:i:s');
        $message = array_shift($args);
        $second = array_shift($args);
        if(!is_null($second) && is_string($second)) {
            $domain = $second;
            $second = array_shift($args);
        }
        else {
            $domain = $this->domain;
        }
        $msg = $date.' '.strtoupper($name).' - '.$domain.': '.$message;
        switch ($name) {
            case 'error':
            case 'Exception':
                fwrite(STDERR, $msg."\n");
            break;
            
            default:
                echo $msg."\n";
            break;
        }
        //FIXME log data only on verbose!
        if(false && !is_null($second) && (is_array($second) || is_object($second))) {
            $msg .= "\n".print_r($second,1);
            echo $msg;
        }
    }
}