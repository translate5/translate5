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
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Message\BackendMsg;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

/**
 */
class MessageBus implements MessageComponentInterface
{
    /**
     * container of the different instances handled by this MessageBus
     * @var array
     */
    protected $instances = [];
    
    /**
     * @var Logger
     */
    protected $logger;
    
    /**
     * @var Metrics
     */
    protected $metrics;
    
    public function __construct() {
        $this->logger = Logger::getInstance();
        $this->metrics = new Metrics();
    }

    public function onOpen(ConnectionInterface $conn) {
        /* @var $conn->httpRequest GuzzleHttp\Psr7\Request */
        $data = $this->getDataFromConn($conn);
        //we store sessionId and serverId directly in the connection:
        $conn->sessionId = $data['sessionId'];
        $conn->serverId = $data['serverId'];
        $conn->connectionId = $data['connectionId'];
        if(empty($conn->connectionId)) {
            $this->logger->error('connection open: no connection ID was given', LOG_SOCKET);
            $conn->send('{"command":"errorOnOpen", "error": "noConnectionId"}');
            $conn->close();
            return;
        }
        $instance = $this->getInstance($conn->serverId);
        if($data['version'] !== SERVER_VERSION) {
            $this->logger->error('connection open: version mismatch. '.$data['version'].' on client and '.SERVER_VERSION.' on server', LOG_SOCKET);
            $conn->send('{"command":"errorOnOpen", "error": "versionMismatch"}');
            $conn->close();
            return;
        }
        if(empty($instance)) {
            $this->logger->error('connection open: no instance ID was given', LOG_SOCKET);
            $conn->send('{"command":"errorOnOpen", "error": "noInstanceId"}');
            $conn->close();
            return;
        }
        $instance->onOpen($conn);
    }
    
    public function onClose(ConnectionInterface $conn) {
        $instance = $this->getInstance($conn->serverId);
        $instance && $instance->onClose($conn);
        $this->garbageCollection();
    }
    
    public function onMessage(ConnectionInterface $conn, $message) {
        //the serverId and sessionId are stored in the $conn object
        $instance = $this->getInstance($conn->serverId);
        $msg = json_decode($message, true);
        
        //check for JSON errors
        if(json_last_error() > 0){
            $this->logger->error('error on message (JSON) decode: '.json_last_error_msg(), LOG_SOCKET, ['message' => $message]);
            $conn->close();
            return;
        }
        
        settype($msg['channel'], 'string');
        settype($msg['command'], 'string');
        settype($msg['payload'], 'array');
        $msg['conn'] = $conn;
        $this->logger->debug('IN '.$msg['channel'].'::'.$msg['command'].'('.json_encode($msg['payload']).')');
        $instance->passFrontendMessage(new FrontendMsg($msg), $conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo 'Exception '.get_class($e).': '.$e->getMessage()."\n";
        echo ' in '.$e->getFile().' ('.$e->getLine().')'."\n";
        $this->logger->error($e);
        $instance = $this->getInstance($conn->serverId);
        $instance->onError($conn);
    }
    
    /**
     * process the given message coming from translate5 server
     */
    public function processServerRequest(ServerRequestInterface $request) {
        $path = $request->getUri()->getPath();
        if ($path === '/metrics') {
            return $this->metrics();
        }
        $body = $request->getParsedBody();
        if(empty($body)){
            return $this->debugResponse();
        }
        settype($body['instance'], 'string'); //from server side we have to deliver the instance as msg attribute
        settype($body['instanceName'], 'string');
        settype($body['channel'], 'string');
        settype($body['command'], 'string');
        settype($body['payload'], 'string');
        settype($body['debug'], 'string');
        settype($body['version'], 'string');
        

        $this->logger->debug('SERVER '.$body['instanceName'].' '.$body['channel'].'::'.$body['command'].'('.$body['debug'].')', $body);
        if($body['version'] !== SERVER_VERSION) {
            $this->logger->error('Version Mismatch: client '.$body['version'].' and server '.SERVER_VERSION);
            
            return new Response(406, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'version mismatch',
                'instance' => $body['instance'],
                'client' => $body['version'],
                'server' => SERVER_VERSION,
            ]));
        }
        $instance = $this->getInstance($body['instance'], $body['instanceName']);
        $result = $instance->passBackendMessage(new BackendMsg($body));

        //this can be used to pass back information into the instance
        return new Response(200, ['Content-Type' => 'text/plain'], json_encode(['instanceResult' => $result]));
    }
    
    /**
     * Shortcut function to show debug data of all instances on localhost call
     * @return \React\Http\Message\Response
     */
    protected function debugResponse() {
        $data = ['instances' => []];
        foreach($this->instances as $instance) {
            $data['instances'][] = $instance->debug();
        }
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
    }
    
    protected function metrics() {
        $this->metrics->collect($this->instances);
        return new Response(200, ['Content-Type' => 'text/plain'], $this->metrics->__toString());
    }
    
    /**
     * returns the serverId (internal instance id) of a connection object
     */
    protected function getDataFromConn(ConnectionInterface $conn) {
        //get the serverId from the connection open request
        $params= null;
        parse_str($conn->httpRequest->getUri()->getQuery(), $params);
        if(!is_array($params)) {
            $params = [];
        }
        settype($params['version'], 'string');
        settype($params['serverId'], 'string');
        settype($params['sessionId'], 'string');
        settype($params['connectionId'], 'string');
        return $params;
    }
    
    /**
     * returns an instance by serverId, lazy instantiation: creates the instance if not found
     * @param string $serverId
     * @param string $name
     * @return AppInstance|NULL
     */
    protected function getInstance(string $serverId, string $name = null): ?AppInstance {
        if(empty($serverId)) {
            return null;
        }
        if(empty($this->instances[$serverId])) {
            $this->instances[$serverId] = new AppInstance($serverId);
        }
        if(!empty($name)) {
            $this->instances[$serverId]->setInstanceName($name);
        }
        return $this->instances[$serverId];
    }
    
    /**
     * gc cleans unused (deactivated) instances, invocation in onClose is sufficient
     */
    protected function garbageCollection() {
        $overDued = time() - 24 * 3600;
        foreach($this->instances as $serverId => $instance) {
            if($instance->getLastAccess() < $overDued) {
                unset($this->instances[$serverId]);
            }
        }
    }
}