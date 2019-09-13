<?php
use Translate5\FrontEndMessageBus\MessageBus;

require __DIR__ . '/bus-server/vendor/autoload.php';

/**
 * TODO use eclipse external tools for running / restart while development 
 */
$loop   = React\EventLoop\Factory::create();
$bus = new MessageBus();

// PHP Server: Open internal server for connection from traditional PHP application
$webSockPhp = new React\Socket\Server('127.0.0.1:9057', $loop);
$server = new React\Http\Server([$bus, 'processServerRequest']);
$server->listen($webSockPhp);
$server->on('error', function() {
    //FIXME error handling
    error_log(print_r(func_get_args()),1);
});


// Web Server: open public server for connections from Browsers 
$webSockClient = new React\Socket\Server('0.0.0.0:9056', $loop);
$socketServer = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            $bus
        )
    ),
    $webSockClient
);

$loop->run();