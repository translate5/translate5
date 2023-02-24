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

use editor_Utils;
use MittagQI\Translate5\Service\DockerServiceAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Zend_Config;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use ZfExtended_Factory;

final class Service extends DockerServiceAbstract
{

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.plugins.Okapi.server',
        'type' => 'string',
        'url' => 'http://okapi.:8080/okapi-longhorn/',
        'healthcheck' => '/projects'
    ];

    /**
     * Differing from the base-implementation we add checking the other configured okapi-services here, not just the used one
     * (non-PHPdoc)
     * @see DockerServiceAbstract::check()
     */
    public function check(): bool
    {
        $services = $this->config->runtimeOptions->plugins->Okapi->server;
        $serviceUsed = $this->config->runtimeOptions->plugins->Okapi->serverUsed;
        $url = (!empty($services) && !empty($serviceUsed)) ? ($services->$serviceUsed ?? null) : null;
        if (empty($url)) {
            $this->errors[] = 'There is no URL configured for entry "'.$serviceUsed.'".';
            return false;
        }
        $healthcheckUrl = rtrim($url, '/') . '/projects';
        if (!$this->checkConfiguredHealthCheckUrl($healthcheckUrl, $url)) {
            $this->errors[] = 'A request on "' . $healthcheckUrl . '" did not bring the expected status "200" for entry "'.$serviceUsed.'".';
            return false;
        }
        $this->checkOtherConfiguredServers($services, $serviceUsed);
        return true;
    }

    /**
     * Differing from base version we check the existing configured URLs for validity and dismiss the ones not reachable
     * (non-PHPdoc)
     * @see DockerServiceAbstract::locate()
     */
    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        if (array_key_exists('remove', $config) && $config['remove'] === true) {
            $this->updateConfigurationConfig('runtimeOptions.plugins.Okapi.server', 'string', '', $doSave, $io);
            $this->updateConfigurationConfig('runtimeOptions.plugins.Okapi.serverUsed', 'string', '', $doSave, $io);
            return false;
        }
        if (empty($url)) {
            $url = $this->configurationConfig['url'];
        }
        if ($this->checkPotentialServiceUrl($this->getName(), $url, $io)) {
            // validate existing entries, dismiss those not available
            $newServers = [];
            if(!empty($this->config->runtimeOptions->plugins->Okapi->server)){
                foreach($this->config->runtimeOptions->plugins->Okapi->server as $name => $otherUrl){
                    if(!empty($otherUrl) && $otherUrl != $url && $this->checkConfiguredHealthCheckUrl(rtrim($otherUrl, '/') . '/projects', $otherUrl, false)){
                        $newServers[$name] = $otherUrl;
                    }
                }
            }
            // add new entry by it's version as name-suffix (note: we will overwrite other entries like 'okapi-longhorn-xxx' without further notice) ... we use a scheme that is common on the existing instances
            $version = $this->fetchVersion($url, '');
            $version = str_ends_with($version, '.0') ? substr($version, 0, -2) : $version;
            $newName = (empty($version)) ? 'okapi-longhorn' : 'okapi-longhorn-' . editor_Utils::secureFilename(str_replace('.', '', $version));
            $newServers[$newName] = $url;
            $this->updateConfigurationConfig('runtimeOptions.plugins.Okapi.server', 'string', json_encode($newServers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $doSave, $io);
            $this->updateConfigurationConfig('runtimeOptions.plugins.Okapi.serverUsed', 'string', $newName, $doSave, $io);
            return true;
        }
        return false;
    }

    /**
     * Unfortunately we cannot fetch the version directly as older versions do not support the status.json
     * (non-PHPdoc)
     * @see DockerServiceAbstract::findVersionInResponseBody()
     */
    protected function findVersionInResponseBody(string $responseBody, string $serviceUrl): ?string
    {
        return $this->fetchVersion($serviceUrl, 'unknown / before 1.40.0');
    }

    /**
     * Retrieves the OKAPI version. This API is only available since OKAPI 1.40.0
     * @param string $okapiUrl
     * @param string|null $default
     * @return string|null
     * @throws Zend_Http_Client_Exception
     */
    private function fetchVersion(string $okapiUrl, ?string $default): ?string
    {
        try {
            $httpClient = ZfExtended_Factory::get(Zend_Http_Client::class);
            $httpClient->setUri(rtrim($okapiUrl, '/') . '/status.json');
            $response = $httpClient->request('GET');
            if ($response->getStatus() === 200) {
                $status = json_decode($response->getBody());
                if (property_exists($status, 'version')) {
                    return $status->version;
                }
            }
            return $default;
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * @param Zend_Config $services
     * @param string $serviceUsed
     */
    private function checkOtherConfiguredServers(Zend_Config $services, string $serviceUsed)
    {
        foreach($services as $name => $url){
            if($name !== $serviceUsed){
                if (empty($url)) {
                    $this->warnings[] = 'There is no URL configured for entry "'.$name.'.';
                } else {
                    $healthcheckUrl = rtrim($url, '/') . '/projects';
                    if (!$this->checkConfiguredHealthCheckUrl($healthcheckUrl, $url, false)) {
                        $this->warnings[] = 'A request on "' . $healthcheckUrl . '" did not bring the expected status "200" for entry "'.$name.'.';
                    }
                }
            }
        }
    }
}
