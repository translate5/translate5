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

namespace MittagQI\Translate5\Service;

use Throwable;
use Zend_Exception;
use JsonException;
use Zend_Db_Statement_Exception;
use Zend_Http_Client;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Exception;
use ZfExtended_Factory;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Represents a docker service that is represented by a single config-value that either is a simple string or a list
 TODO FIXME: rename to AbstractDockerService
 */
abstract class DockerServiceAbstract extends AbstractHttpService
{
    /**
     * An assoc array that has to have at least 3 props:
     * "name": of the config
     * "type": of the config (either string, integer, boolean or list)
     * "url": the default-url, must have protocol, host & port
     * "additive": optional, if set to true, the configured value is added to the existing config if it does not exist
     * "optional": optional, if set to true, this will result in only a warning if the service is not configured
     * "healthcheck": optional, if set to a value like /status, this will be used to check the health of the service with a GET request that is expected to return status "200"
     * "healthcheckIsJson": optional, if set to true, the helthcheck-url will be configured to fetch JSON via Accept header
     * @var array
     */
    protected array $configurationConfig;

    /**
     * Base implementation for simple docker-services
     * @param SymfonyStyle $io
     * @param mixed $url
     * @param bool $doSave
     * @param array $config : optional to inject further dependencies
     * @return bool
     * @throws JsonException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        $configName = $this->configurationConfig['name'];
        $configType = $this->configurationConfig['type'];

        if (array_key_exists('remove', $config) && $config['remove'] === true) {
            $this->updateConfigurationConfig($configName, $configType, [], $doSave, $io);
            return false;
        }

        $isAdditive = array_key_exists('additive', $this->configurationConfig) ? $this->configurationConfig['additive'] : false; // TODO: do we need this ?
        if (empty($url)) {
            $url = $this->configurationConfig['url'];
        }
        if ($this->checkPotentialServiceUrl($this->getName(), $url, $io)) {
            $this->updateConfigurationConfig($configName, $configType, $url, $doSave, $io, $isAdditive);
            return true;
        }
        return false;
    }

    /**
     * Base implementation for simple docker-services
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function check(): bool
    {
        $checked = true;
        $healthCheck = array_key_exists('healthcheck', $this->configurationConfig) ? $this->configurationConfig['healthcheck'] : null;
        $urls = $this->getConfigValueFromName($this->configurationConfig['name'], $this->configurationConfig['type']);

        if (empty($urls)) {
            $urls = [];
        } else if (!is_array($urls)) {
            $urls = [$urls];
        }
        if (count($urls) === 0) {
            $this->errors[] = 'There is no URL configured.';
            $checked = false;
        } else {
            foreach ($urls as $url) {
                if(!$this->checkUrl($url, $healthCheck)){
                    $checked = false;
                }
            }
        }
        return $checked;
    }

    public function checkUrl(string $url, ?string $healthCheck): bool
    {
        if (empty($url)) {
            $this->errors[] = 'There is an empty URL set.';
            return false;
        } else if (empty($healthCheck)) {
            if (!$this->checkConfiguredServiceUrl($url)) {
                $this->errors[] = 'The configured URL "' . $url . '" is not reachable.';
                return false;
            }
        } else {
            $healthcheckUrl = rtrim($url, '/') . $healthCheck;
            if (!$this->checkConfiguredHealthCheckUrl($healthcheckUrl, $url)) {
                $this->errors[] = 'A request on "' . $healthcheckUrl . '" did not bring the expected status "200".';
                return false;
            }
        }
        return true;
    }

    /**
     * Checks a dedicated status-url on a service or an URL that could be used in such manner
     * @param string $healthcheckUrl
     * @param string $serviceUrl
     * @param bool $addResult : normally we want the result of a healthcheck to be part of the output
     * @return bool
     */
    protected function checkConfiguredHealthCheckUrl(string $healthcheckUrl, string $serviceUrl, bool $addResult = true): bool
    {
        try {
            $httpClient = ZfExtended_Factory::get(Zend_Http_Client::class);
            $httpClient->setUri($healthcheckUrl);
            // some endpoints need to be told to return JSON
            if (array_key_exists('healthcheckIsJson', $this->configurationConfig) && $this->configurationConfig['healthcheckIsJson'] === true) {
                $httpClient->setHeaders('Accept', 'application/json');
            }
            $response = $httpClient->request('GET');
            // the status request must return 200
            if ($addResult && $response->getStatus() === 200) {
                $this->addCheckResult($serviceUrl, $this->findVersionInResponseBody($response->getBody(), $serviceUrl));
            }
            return ($response->getStatus() === 200);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Checks a single URL (usually pointing to a docker container)
     * @param string $url
     * @return bool
     */
    protected function checkConfiguredServiceUrl(string $url): bool
    {
        $urlParsed = $this->parseUrl($url);
        if (empty($urlParsed['host'])) {
            return false;
        }
        return $this->isDnsSet(... $urlParsed);
    }

    /**
     * Checks if the expected service URL is reachable
     * @param string $label
     * @param string $url
     * @param SymfonyStyle|null $io
     * @return bool
     */
    protected function checkPotentialServiceUrl(string $label, string $url, SymfonyStyle $io = null): bool
    {
        $result = true;
        if (!$this->isDnsSet(... $this->parseUrl($url))) {
            $url = 'NONE (expected: ' . $url . ')';
            $result = false;
        }
        $this->output('Found "' . $label . '": ' . $url, $io, 'info');
        return $result;
    }

    /**
     * Can be implemented to retrieve the version-result out of the health-checks request-body
     * @param string $responseBody
     * @param string $serviceUrl
     * @return string|null
     */
    protected function findVersionInResponseBody(string $responseBody, string $serviceUrl): ?string
    {
        return null;
    }
}
