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

namespace Translate5\FrontEndMessageBus\Message;
use Ratchet\ConnectionInterface;
use Translate5\FrontEndMessageBus\Logger;

/**
 */
class FrontendMsg extends Msg {
    /**
     * @var ConnectionInterface
     */
    public $conn;
    
    /**
     * Creates a FrontendMsg instance with the given data
     * @param string $channel
     * @param string $command
     * @param array $payload
     * @param ConnectionInterface $conn
     * @return FrontendMsg
     */
    public static function create(string $channel, string $command, array $payload = [], ConnectionInterface $conn = null): FrontendMsg  {
        $msg = new static();
        $msg->channel = $channel;
        $msg->command = $command;
        $msg->payload = $payload;
        $msg->conn = $conn;
        return $msg;
    }
    
    /**
     * sends the message to the internal connection (if set) 
     */
    public function send() {
        $this->logSend();
        $this->conn->send((string) $this);
    }
    
    /**
     * Log the message send
     * Since we not always can send messages with $this->send, but has to use $conn->send(frontendMsg) in some situations, there should be used this log method too 
     */
    public function logSend() {
        Logger::getInstance()->debug('OUT '.$this->channel.'::'.$this->command.'('.json_encode($this->payload).')');
    }
}