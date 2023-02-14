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

namespace MittagQI\Translate5\Plugins\FrontEndMessageBus;

use MittagQI\Translate5\Service\DockerServiceAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Service extends DockerServiceAbstract
{

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.plugins.FrontEndMessageBus.messageBusURI',
        'type' => 'string',
        'url' => 'http://frontendmessagebus.:9057'
    ];

    public function check(): bool
    {
        $checked = true;
        $messageBusUrl = $this->config->runtimeOptions->plugins->FrontEndMessageBus->messageBusURI;
        $socketServer = $this->config->runtimeOptions->plugins->FrontEndMessageBus->socketServer;
        $host = empty($socketServer->host) ? $this->config->runtimeOptions->server->name : $socketServer->host;
        $socketServerUrl = $socketServer->schema . '://' . $host . ':' . $socketServer->port . $socketServer->route;

        if (empty($messageBusUrl)) {
            $this->errors[] = 'There is no URL configured.';
            $checked = false;
        } else if (!$this->checkConfiguredServiceUrl($messageBusUrl)) {
            $this->errors[] = 'The configured URL "' . $messageBusUrl . '" is not reachable.';
            $checked = false;
        }

        if (empty($messageBusUrl)) {
            $this->errors[] = 'There is no URL configured.';
            $checked = false;
        } else if (!$this->checkConfiguredServiceUrl($socketServerUrl)) {
            $this->errors[] = 'The configured socket-server URL "' . $socketServerUrl . '" is not reachable.';
            $checked = false;
        }
        return $checked;
    }

    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        if(array_key_exists('remove', $config) && $config['remove'] === true){
            // reset all configs
            $this->updateConfigurationConfigs('', '', '', '', '', $doSave, $io);
            return false;
        }

        if (empty($url)) {
            $url = $this->configurationConfig['url'];
        }
        if (!$this->checkPotentialServiceUrl($this->getName(), $url, $io)) {
            return false;
        }
        // evaluate the socket-server data
        $isHttps = ($this->config->runtimeOptions->server->protocol === 'https://');
        // may data is set by config-option
        if(array_key_exists('socketServer', $config) && !empty($config['socketServer'])){

            $scheme = parse_url($config['socketServer'], PHP_URL_SCHEME);
            $host = parse_url($config['socketServer'], PHP_URL_HOST);
            $port = parse_url($config['socketServer'], PHP_URL_PORT);
            $path = parse_url($config['socketServer'], PHP_URL_PATH) ?? '';

            if($isHttps && $scheme !== 'wss'){
                $this->output('The socket-server must also be on SSL if the installation runs on SSL/HTTPS', $io, 'warning');
                return false;
            }

        } else {

            // the normal way: evaluating
            $scheme = ($isHttps) ? 'wss' : 'ws';
            $host = $this->config->runtimeOptions->server->name; // TODO FIXME add getenv for especially set different server, like our messagebus.translate5.net
            $port = ($isHttps) ? '443' : '80';
            $path = ($isHttps) ? '/wss/translate5' : '/ws/translate5';

        }
        // save all evaluated configs
        $this->updateConfigurationConfigs($url, $scheme, $host, $port, $path, $doSave, $io);

        return true;
    }

    /**
     * Helper to save all messagebus related stuff
     * @param string $url
     * @param string $schema
     * @param string $host
     * @param int $port
     * @param string $route
     * @param bool $doSave
     * @param SymfonyStyle $io
     * @throws \JsonException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function updateConfigurationConfigs(string $url, string $scheme, string  $host, int $port, string $route, bool $doSave, SymfonyStyle $io)
    {
        $this->updateConfigurationConfig('runtimeOptions.plugins.FrontEndMessageBus.messageBusURI', 'string', $url, $doSave, $io);
        $this->updateConfigurationConfig('runtimeOptions.plugins.FrontEndMessageBus.socketServer.schema', 'string', $scheme, $doSave, $io);
        $this->updateConfigurationConfig('runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost', 'string', $host, $doSave, $io);
        $this->updateConfigurationConfig('runtimeOptions.plugins.FrontEndMessageBus.socketServer.port', 'string', $port, $doSave, $io);
        $this->updateConfigurationConfig('runtimeOptions.plugins.FrontEndMessageBus.socketServer.route', 'string', $route, $doSave, $io);
    }
}
