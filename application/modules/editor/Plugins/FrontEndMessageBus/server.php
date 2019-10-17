<?php
use Translate5\FrontEndMessageBus\MessageBus;
use Translate5\FrontEndMessageBus\Configuration;

require __DIR__ . '/bus-server/vendor/autoload.php';
/**
 * TODO use eclipse external tools for running / restart while development
 */
$loop = React\EventLoop\Factory::create();
$bus = new MessageBus();
$config = new Configuration(__DIR__.'/config.php');

// PHP Server: Open internal server for connection from traditional PHP application
$webSockPhp = new React\Socket\Server($config->messageServer->address.':'.$config->messageServer->port, $loop);
$server = new React\Http\Server([$bus, 'processServerRequest']);
$server->listen($webSockPhp);
$server->on('error', function(\Exception $error) {
    //FIXME error handling
    error_log($error);
    //TODO: use the Translate5\FrontEndMessageBus for the error handling
});

// WebSocket Server: open public server for connections from Browsers
$app = new \Ratchet\App($config->socketServer->httpHost,$config->socketServer->port, $config->socketServer->listen, $loop);
$app->route($config->socketServer->route, $bus);
$app->run();