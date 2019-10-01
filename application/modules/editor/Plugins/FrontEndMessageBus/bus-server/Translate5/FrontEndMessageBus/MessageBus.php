<?php
namespace Translate5\FrontEndMessageBus;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Message\BackendMsg;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

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
    
    public function __construct() {
        $this->logger = Logger::getInstance();
    }

    public function onOpen(ConnectionInterface $conn) {
        /* @var $conn->httpRequest GuzzleHttp\Psr7\Request */
        $data = $this->getDataFromConn($conn);
        //we store sessionId and instanceId directly in the connection:
        $conn->sessionId = $data['sessionId'];
        $conn->serverId = $data['serverId'];
        $instance = $this->getInstance($conn->serverId);
        if(empty($instance)) {
            //FIXME log that no serverId was given and close there fore the connection
            $conn->close();
            return;
        }
        $instance->connect($conn);
    }
    
    public function onClose(ConnectionInterface $conn) {
        $instance = $this->getInstance($conn->serverId);
        $instance->close($conn);
    }
    
    public function onMessage(ConnectionInterface $conn, $message) {
        //the serverId and sessionId are stored in the $conn object
        $instance = $this->getInstance($conn->serverId);
        $msg = json_decode($message, true);
        //FIXME react on JSON errors
        settype($msg['payload'], 'array');
        $this->logger->debug('Data from frontend', $msg);
        $msg['conn'] = $conn;
        $instance->passFrontendMessage(new FrontendMsg($msg), $conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        //FIXME what means onError? 
        $this->logger->error($e);
        //close connection on error? May make sense only in special cases, not in general
        //$instance = $this->getInstance($conn->serverId);
        //$instance->close($conn);
    }
    
    /**
     * process the given message coming from translate5 server
     */
    public function processServerRequest(ServerRequestInterface $request) {
        $body = $request->getParsedBody();
        if(empty($body)){
            return $this->debugResponse();
        }
        settype($body['instance'], 'string'); //from server side we have to deliver the instance as msg attribute
        settype($body['channel'], 'string');
        settype($body['command'], 'string');
        settype($body['payload'], 'string');

        $this->logger->debug('server request', $body);
        $instance = $this->getInstance($body['instance']);
        $instance->passBackendMessage(new BackendMsg($body));
        
        //FIXME define default values which trigger a log entry (if channel / message is empty)
        
        return new Response(
            200,
            array(
                'Content-Type' => 'text/plain'
            ),
            "Hello World!\n" //FIXME what to return here?
            );
    }
    
    protected function debugResponse() {
        $data = ['instances' => []];
        foreach($this->instances as $instance) {
            $data['instances'][] = $instance->debug();
        }
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
    }
    
    /**
     * returns the serverId (internal instance id) of a connection object
     */
    protected function getDataFromConn(ConnectionInterface $conn) {
        //get the serverId from the connection open request
        $params= null;
        parse_str($conn->httpRequest->getUri()->getQuery(), $params);
        if(empty($params) || empty($params['serverId'])) {
            return [];
        }
        settype($params['sessionId'], 'string');
        return $params;
    }
    
    protected function getInstance($serverId): ?AppInstance {
        if(empty($serverId)) {
            return null;
        }
        if(empty($this->instances[$serverId])) {
            $this->instances[$serverId] = new AppInstance($serverId);
        }
        return $this->instances[$serverId];
    }
    
    public function garbageCollection() {
        //TODO clean up unused instances. How to define unused? No Connections?
        //also pass gc call to instances to clean up unused sessions
    }
}