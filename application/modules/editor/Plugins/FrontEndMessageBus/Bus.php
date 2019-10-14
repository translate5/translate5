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
 * encapsulates defined commands directly to the MessageBus
 * @method void startSession() startSession($sessionId, stdClass $userData)
 * @method void stopSession() stopSession($sessionId)
 * 
 * TODO is this class layer necessary?, currently all functions can be moved without a problem into the Init.php 
 */
require __DIR__.'/bus-server/Configuration.php';//message bus config
class editor_Plugins_FrontEndMessageBus_Bus {
    const CHANNEL = 'instance';
    
    //here methods could be implemented if more logic is needed as just passing the arguments directly to the MessageBus via __call 
    // this could be for example necessary to convert entities like editor_Models_Task to native stdClass / array data. 
    // Since only the latter ones can be send to the MessageBus 
    
    /**
     * By default pass all functions directly to the MessageBus
     * @param string $name
     * @param array $args
     */
    public function __call($name, array $args) {
        $this->notify(static::CHANNEL, $name, $args);
    }
    
    public function notify($channel, $command, $data = null) {
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $uri=MESSAGE_BUS_SERVER_PROTOCOL.'://'.MESSAGE_BUS_SERVER_IP.':'.MESSAGE_BUS_SERVER_PORT;
        $http->setUri($uri);
        //FIXME from config, see server.php for notes about config
        
        //FIXME the value behind "instance" is used to identify the current instance via a unique hash
        // Should we implement insted a configurable instance key? Similar to OpenTM2 prefix, to identify / distinguish translate5 instances
        // when message bus is reused in one instance? Very danger: if the value is not changed on copying the instance this enabled security risks.
        // If the calculation is changed Should replace the serverId in library/ZfExtended/Worker/TriggerByHttp.php then too
        $http->setParameterPost('instance', ZfExtended_Utils::installationHash('MessageBus'));
        $http->setParameterPost('channel', $channel);
        $http->setParameterPost('command', $command);
        $http->setParameterPost('payload', json_encode($data));
        
        try {
            $this->processResponse($http->request($http::POST));
        }
        catch (Exception $e) {
            error_log('Error on message bus channel notify. The error was: '.PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString());
        }
        
        //FIXME if host is not reachable, deactivate plugin temporarly (like termtagger DOWN check)
        // for performance reasons we should alternativly use unix sockets (https://stackoverflow.com/questions/4489975/how-to-send-datagrams-through-a-unix-socket-from-php) if possoble.
        // this should be used automatically (like in mysql) if it is a unixoid OS and host is localhost or 127.0.0.x
        // another improvement, set the underlying Zend_Http_Client_Adapter_Socket to non blocking
        // see https://reactphp.org/socket/#unixconnector
        
        //TODO may not bring an exception to the frontend if an error happens here, just log it and good.
        //TODO log response, log JSON encode errors
    }
    
    /**
     * Parses and processes the response
     * 
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response) {
        $validStates = [200, 201];
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            error_log('Invalid response type in message bus channel notify process response. Response status was:'.$response->getStatus());
            error_log(print_r($response,1));
            return false;
        }
        
        $responseBody = trim($response->getBody());
        $result = (empty($responseBody)) ? '' : json_decode($responseBody);
        
        //TODO: why this returns error ? see the error log on page reload
        //check for JSON errors
        if(json_last_error() > 0){
            error_log('Invalid json response in message bus channel notify process response. The json error was:'.PHP_EOL.json_last_error_msg());
            error_log(print_r($response,1));
            return false;
        }
        if(empty($result)){
            error_log('Empty json response in message bus channel notify process response.');
            error_log(print_r($response,1));
            return false;
        }
        
        return true;
    }
}