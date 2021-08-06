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

use Translate5\FrontEndMessageBus\Logger;
use Translate5\FrontEndMessageBus\MessageBus;
use Translate5\FrontEndMessageBus\Configuration;

/**
 * Increase me on each change! (also CLIENT_VERSION in Init.php!)
 */
const SERVER_VERSION = '1.1';

//errors should go to stderr only!
ini_set('display_errors', "stderr");

//WARNING: the next line is automatically changed on our productive server, so do not change it unless you know what you do!
require __DIR__ . '/../../../../../vendor/autoload.php';

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

ob_start(); //capture boot up info for bootup mail if configured
$logger->info('version '.SERVER_VERSION);
$logger->info('starting on '.$host.':'.$port, LOG_HTTP);
$appMessageServer = new React\Socket\Server($host.':'.$port, $loop);
$server = new React\Http\Server($loop, [$bus, 'processServerRequest']);
$server->listen($appMessageServer);
$server->on('error', function(\Exception $error) use ($logger){
    $logger->exception($error, LOG_HTTP);
});

// WebSocket Server: open public server for connections from Browsers
$host = $config->socketServer->httpHost;
$port = $config->socketServer->port;
$listen = $config->socketServer->listen;
$logger->info('starting on '.$host.':'.$port.' listen to '.$listen, LOG_SOCKET);

//send boot up email, if configured
$bootUpOutput = ob_get_flush();
if(!empty($config->bootMailReceiver)) {
    mail($config->bootMailReceiver, gethostname().': FrontEndMessageBus (re)started', $bootUpOutput);
}

$app = new \Ratchet\App($host, $port, $listen, $loop);
$app->route($config->socketServer->route, $bus, ['*']);
$app->run();