<?php
use Translate5\FrontEndMessageBus\Logger;
use Translate5\FrontEndMessageBus\MessageBus;
use Translate5\FrontEndMessageBus\Configuration;

require __DIR__ . '/bus-server/vendor/autoload.php';
/**
 * For development: use eclipse external tools for running / restart
 */
$loop = React\EventLoop\Factory::create();
$bus = new MessageBus();
$config = new Configuration(__DIR__.'/config.php');
define('LOG_SOCKET', 'FrontEndMessageBus - WebSocket Server');
define('LOG_HTTP', 'FrontEndMessageBus - Application Message Server');
$logger = Logger::getInstance();

// PHP Server: Open internal server for connection from traditional PHP application
$host = $config->messageServer->address;
$port = $config->messageServer->port;

$logger->info('starting on '.$host.':'.$port, LOG_HTTP);
$appMessageServer = new React\Socket\Server($host.':'.$port, $loop);
$server = new React\Http\Server([$bus, 'processServerRequest']);
$server->listen($appMessageServer);
$server->on('error', function(\Exception $error) use ($logger){
    $logger->exception($error, LOG_HTTP);
});

// WebSocket Server: open public server for connections from Browsers
$host = $config->socketServer->httpHost;
$port = $config->socketServer->port;
$listen = $config->socketServer->listen;
$logger->info('starting on '.$host.':'.$port.' listen to '.$listen, LOG_SOCKET);
$app = new \Ratchet\App($host, $port, $listen, $loop);
$app->route($config->socketServer->route, $bus, ['*']); 
$app->run();