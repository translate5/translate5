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
 * Convenient wrapper of a FrontendMsg, holding data needed for communications about segments in task channel
 * also wrapping commands to functions and providing virtual properties needed for segment communication
 * 
 * Should be a private class to Task Channel only!
 *  
 * @property string $connectionId virtual, is passed to the underlying payload container
 * @property string $userGuid virtual, is passed to the underlying payload container
 * @property string $taskGuid virtual, is passed to the underlying payload container
 * @property string $segmentId virtual, is passed to the underlying payload container
 * @method void segmentOpenNak() segmentOpenNak()
 * @method void segmentOpenAck() segmentOpenAck()
 * @method void segmentSave() segmentSave()
 */
class SegmentMsg extends FrontendMsg {
    
    /**
     * returns a SegmentMsg prepared with the data and connection from the triggering front-end request
     */
    public static function createFromFrontend(FrontendMsg $request): SegmentMsg  {
        $msg = new self();
        $msg->payload = [];
        $msg->conn = $request->conn;
        $msg->channel = $request->channel;
        
        //set internally the given taskGuid 
        settype($request->payload[0], 'string');
        $msg->taskGuid = $request->payload[0];
        
        //set internally the given segmentId
        settype($request->payload[1], 'integer');
        $msg->segmentId = $request->payload[1];
        
        $msg->connectionId = $request->conn->connectionId;
        
        return $msg;
    }
    
    public function __call($name, $args) {
        $this->command = $name;
        $this->send();
    }
    
    public function __set($name, $value) {
        return $this->payload[$name] = $value;
    }
    
    public function __get($name) {
        return $this->payload[$name];
    }
}