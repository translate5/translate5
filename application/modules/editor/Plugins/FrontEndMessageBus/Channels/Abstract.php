<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * encapsulates defined messages to the MessageBus 
 */
abstract class editor_Plugins_FrontEndMessageBus_Channels_Abstract {
    
    /**
     * By default pass all functions directly to the MessageBus
     * @param string $name
     * @param array $args
     */
    public function __call($name, array $args) {
        $this->notify(static::CHANNEL, $name, $args);       
    }
    
    protected function notify($channel, $command, $data = null) {
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $http->setUri('http://localhost:9057');
        
        //FIXME implement a configurable instance key? Similar to OpenTM2 prefix, to identify / distinguish translate5 instances when message bus is reused in one instance
        // Should replace the serverId in library/ZfExtended/Worker/TriggerByHttp.php then too
        $http->setParameterPost('instance', ZfExtended_Utils::installationHash('MessageBus')); 
        $http->setParameterPost('channel', $channel);
        $http->setParameterPost('command', $command);
        $http->setParameterPost('payload', json_encode($data));
        $resp = $http->request($http::POST);

        //TODO default adapter ist Zend_Http_Client_Adapter_Socket, kann man den in non blocking mode setzen? Dann warten wir nicht auf eine Antwort.
        
        //TODO may not bring an exception to the frontend if an error happens here, just log it and good.
        //TODO log response, log JSON encode errors
    }
}