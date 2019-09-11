<?php
namespace Translate5\FrontEndMessageBus;
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

    public function onMessage(ConnectionInterface $conn, $message) {
        //$message from frontend comes without serverid, must be added via connection mapping
        
        
        
        
        return;
        //TODO if given sessionId is not know in requested instance, we close the connection
        // $conn is a Ratchet\WebSocket\WsConnection
        // $conn->httpRequest->getRequestTarget();
        
        $this->subscribedTopics[substr($conn->httpRequest->getRequestTarget(), 7)] = $conn;

        $message = json_decode($message);
        //FIXME JSON decode error handling
        if (
            isset($message['command'])
            and $message['command'] == 'update_data'
            and isset($this->subscribedTopics[$message['user']])
        ) {
            $this->subscribedTopics[$message['user']]->send('It works!');
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        /* @var $conn->httpRequest GuzzleHttp\Psr7\Request */
        
        //get the serverId from the connection open request
        $serverId = $this->getServerIdFromConn($conn);
        if(empty($serverId)) {
            //FIXME log that no serverId was given and close there fore the connection
            $conn->close();
        }
        if(!array_key_exists($serverId, $this->instanceConnections)) {
            $this->instanceConnections[$serverId] = [];
        }
        $this->instanceConnections[$serverId][$this->hash($conn)] = $conn;
    }
    
    public function onClose(ConnectionInterface $conn) {
        $serverId = $this->getServerIdFromConn($conn);
        unset($this->instanceConnections[$serverId][$this->hash($conn)]);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        //FIXME what means onError? 
        //close connection then too?
        // unset($this->connections[$this->hash($conn)]);
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
        $msg = new Message($msgData);
        //TODO Keep instances of the Channels in memory or create new instances on handle?
        
        $channelCls = 'Translate5\\FrontEndMessageBus\\Channel\\'.ucfirst($msg->channel);
        if(!class_exists($channelCls)) {
            //FIXME errorhandling if class not found
            error_log("Not Found channel ".$msg->channel);
            return;
        }
        error_log("Found channel for ".$msg);
        $channel = new $channelCls($this->instanceConnections[$msg->instance] ?? []);
        $method = $msg->command;
        $channel->$method($msg);
    }
    
    /**
     * returns the hash of a connection object
     */
    protected function hash(ConnectionInterface $conn) {
        return spl_object_hash($conn);
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
    
}