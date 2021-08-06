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
    
    /**
     * @param array $msgData
     */
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