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
     * Returns the channel name
     */
    abstract function getName(): string;
    
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