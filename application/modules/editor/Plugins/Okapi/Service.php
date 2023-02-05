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

namespace MittagQI\Translate5\Plugins\Okapi;

use MittagQI\Translate5\Service\DockerService;
use Symfony\Component\Console\Style\SymfonyStyle;
use ZfExtended_Factory;
use editor_Plugins_Okapi_Connector;

final class Service extends DockerService {

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.plugins.Okapi.server',
        'type' => 'string',
        'url' => 'http://okapi.:8080',
        'healthcheck' => '/projects'
    ];

    public function check(): bool
    {
        $services = $this->config->runtimeOptions->plugins->Okapi->server;
        $serviceUsed = $this->config->runtimeOptions->plugins->Okapi->serverUsed;
        $url = (!empty($services) && !empty($serviceUsed)) ? ($services->$serviceUsed ?? null) : null;
        if(empty($url)){
            $this->errors[] = 'There is no URL configured.';
            return false;
        }
        $healthcheckUrl = rtrim($url, '/') . '/projects';
        if (!$this->checkConfiguredHealthCheckUrl($healthcheckUrl)) {
            $this->errors[] = 'A request on "' . $healthcheckUrl . '" did not bring the expected status "200".';
            return false;
        }
        return true;
    }

    public function locate(SymfonyStyle $io, bool $writeToConfig, mixed $url, bool $doSave=false): bool
    {
        if(empty($url)){
            $url = $this->configurationConfig['url'];
        }
        if($this->checkPotentialServiceUrl($this->getName(), $url, $io)){
            // TODO FIXME multiple servers / versions ?
            $this->updateConfigurationConfig('runtimeOptions.plugins.Okapi.server', 'string', '{"okapi-longhorn":"' . $url . '"}', $doSave, $io);
            $this->updateConfigurationConfig('runtimeOptions.plugins.Okapi.serverUsed', 'string', 'okapi-longhorn', $doSave, $io);
            return true;
        }
        return false;
    }
}
