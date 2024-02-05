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

    protected array $testConfigs = [
        // this leads to the application-db configs being copied to the test-DB
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.route' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.port' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost' => null,
        'runtimeOptions.plugins.FrontEndMessageBus.messageBusURI' => null
    ];

    public function check(): bool
    {
        $checked = true;
        $messageBusUrl = $this->config->runtimeOptions->plugins->FrontEndMessageBus->messageBusURI;

        if (empty($messageBusUrl)) {
            $this->errors[] = 'There is no URL configured.';
            $checked = false;
        } else if (!$this->checkConfiguredServiceUrl($messageBusUrl)) {
            $this->errors[] = 'The configured URL "' . $messageBusUrl . '" is not reachable.';
            $checked = false;
        }

        return $checked;
    }

    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        if(array_key_exists('remove', $config) && $config['remove'] === true){
            // reset all configs
            $this->updateConfigurationConfigs('',$doSave, $io);
            return false;
        }

        if (empty($url)) {
            $url = $this->configurationConfig['url'];
        }
        if (!$this->checkPotentialServiceUrl($this->getName(), $url, $io)) {
            return false;
        }
        $this->updateConfigurationConfigs($url, $doSave, $io);

        return true;
    }

    /**
     * Helper to save all messagebus related stuff
     * @param string $url
     * @param bool $doSave
     * @param SymfonyStyle $io
     * @throws \JsonException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function updateConfigurationConfigs(string $url, bool $doSave, SymfonyStyle $io)
    {
        $this->updateConfigurationConfig('runtimeOptions.plugins.FrontEndMessageBus.messageBusURI', 'string', $url, $doSave, $io);
    }
}
