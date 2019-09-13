<?php
namespace Translate5\FrontEndMessageBus;
use Translate5\FrontEndMessageBus\Message\FrontendMsg;
use Translate5\FrontEndMessageBus\Message\BackendMsg;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

/**
 * FIXME use WampServerInterface with subscribing?
 */
class MessageBus implements MessageComponentInterface
{
    /** 
     * @var array $instanceConnections keep the connections divided by translate5 instances using this service
     */
    protected $instanceConnections = [];
    
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

    public function onMessage(ConnectionInterface $conn, $message) {
        //$message from frontend comes without serverid, must be added via connection mapping
        
        $serverId = $this->getServerIdFromConn($conn);
        $instance = $this->getInstance($serverId);
        $msg = json_decode($message, true);
        //FIXME react on JSON errors
        settype($msg['payload'], 'array');
        $msg['instance'] = $serverId; //from connected URL
        $this->logger->debug('Data from frontend', $msg);
        $msg['conn'] = $conn;
        $instance->passMessage(new FrontendMsg($msg));
    }

    public function onOpen(ConnectionInterface $conn) {
        /* @var $conn->httpRequest GuzzleHttp\Psr7\Request */
        
        $instance = $this->getInstance($this->getServerIdFromConn($conn));
        if(empty($instance)) {
            //FIXME log that no serverId was given and close there fore the connection
            $conn->close();
            return;
        }
        $instance->connect($conn);
    }
    
    public function onClose(ConnectionInterface $conn) {
        $instance = $this->getInstance($this->getServerIdFromConn($conn));
        $instance->close($conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        //FIXME what means onError? 
        //close connection then too?
        $instance = $this->getInstance($this->getServerIdFromConn($conn));
        $instance->close($conn);
    }
    
    /**
     * process the given message coming from translate5 server
     */
    public function processServerRequest(ServerRequestInterface $request) {
        $body = $request->getParsedBody();
        settype($body['instance'], 'string');
        settype($body['channel'], 'string');
        settype($body['command'], 'string');
        settype($body['payload'], 'string');

        $this->logger->debug('server request', $body);
        $this->handleMessage($body);
        
        //FIXME define default values which trigger a log entry (if channel / message is empty)
        
        return new Response(
            200,
            array(
                'Content-Type' => 'text/plain'
            ),
            "Hello World!\n" //FIXME what to return here?
            );
    }
    
    protected function handleMessage(array $msgData) {
        $msg = new BackendMsg($msgData);
        $instance = $this->getInstance($msg->instance);
        $instance->passMessage($msg);
    }
    
    /**
     * returns the serverId (internal instance id) of a connection object
     */
    protected function getServerIdFromConn(ConnectionInterface $conn) {
        //get the serverId from the connection open request
        $params= null;
        parse_str(ltrim($conn->httpRequest->getRequestTarget(), '/?'), $params);
        if(empty($params) || empty($params['serverId'])) {
            return null;
        }
        return $params['serverId'];
    }
    
    protected function getInstance($serverId): AppInstance {
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