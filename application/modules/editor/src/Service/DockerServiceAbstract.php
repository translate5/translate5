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

use JetBrains\PhpStorm\ArrayShape;
use Throwable;
use Zend_Exception;
use JsonException;
use Zend_Db_Statement_Exception;
use Zend_Http_Client;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Exception;
use ZfExtended_Factory;
use editor_Models_Config;
use ZfExtended_DbConfig_Type_CoreTypes;
use MittagQI\ZfExtended\Service\ServiceAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This is a service that is represented by a single config-value that either is a simple string or a list
 * Consrete Implementations must have a valid $configurationConfig!
 */
abstract class DockerServiceAbstract extends ServiceAbstract
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
     * Services neccessary only for full dockerized setups should not show up in other context's and can be marked as "optional"
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function isCheckSkipped(): bool
    {
        return
            array_key_exists('optional', $this->configurationConfig)
            && $this->configurationConfig['optional'] === true
            && empty($this->getConfigValueFromName($this->configurationConfig['name'], $this->configurationConfig['type']));
    }

    /**
     * Retrieves the service-url for a simple service
     * In case of multiple configured this will be the first
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function getServiceUrl(): ?string
    {
        $values = $this->getConfigValueFromName($this->configurationConfig['name'], $this->configurationConfig['type'], true);
        if (count($values) > 0) {
            return $values[0];
        }
        return null;
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
                $this->addCheckResult($serviceUrl, $this->findVersionInRequestBody($response->getBody(), $serviceUrl));
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
    protected function findVersionInRequestBody(string $responseBody, string $serviceUrl): ?string
    {
        return null;
    }

    #[ArrayShape(['host' => 'string', 'port' => 'int'])]
    protected function parseUrl(string $url): array
    {
        return [
            'host' => parse_url($url, PHP_URL_HOST),
            // if port can not be parsed from given URL, use the default port from the default config:
            'port' => parse_url($url, PHP_URL_PORT) ?: parse_url($this->configurationConfig['url'], PHP_URL_PORT),
        ];
    }

    /**
     * @param string $host
     * @param int $port
     * @return bool
     */
    protected function isDnsSet(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        return false;
    }

    /**
     * Retrieves the value with the config-name like "" out of the global config object
     * @param string $configName
     * @param string $configType
     * @param bool $asArray : if set, the result will be an array for simple types
     * @return mixed
     * @throws ZfExtended_Exception
     */
    protected function getConfigValueFromName(string $configName, string $configType, bool $asArray = false): mixed
    {
        $value = $this->config;
        try {
            foreach (explode('.', $configName) as $section) {
                $value = $value->$section;
            }
        } catch (Throwable) {
            throw new ZfExtended_Exception('Global Config did not contain "' . $configName . '"');
        }
        if ($configType === ZfExtended_DbConfig_Type_CoreTypes::TYPE_LIST) {
            return $value->toArray();
        }
        if ($asArray) {
            return $this->convertValueToArray($value);
        }
        return $value;
    }

    /**
     * Updates Config values for the service
     * @param string $name
     * @param string $type
     * @param mixed $newValue
     * @param bool $doSave
     * @param SymfonyStyle|null $io
     * @param bool $addToExisting
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws JsonException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function updateConfigurationConfig(string $name, string $type, mixed $newValue, bool $doSave, SymfonyStyle $io = null, bool $addToExisting = false)
    {
        $config = new editor_Models_Config();
        $config->loadByName($name);

        if ($config->hasIniEntry()) {
            $this->output($config->getName() . ' is set in ini and can not be updated!', $io, 'warning');
            return;
        }

        if ($doSave) {

            if ($type === ZfExtended_DbConfig_Type_CoreTypes::TYPE_LIST) {

                $newValue = is_array($newValue) ? $newValue : [$newValue];
                $oldValue = empty($config->getValue()) ? [] : json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($newValue) || !is_array($oldValue)) {
                    throw new ZfExtended_Exception('Updating List: old value or new value are not of type array');
                }

                if ($addToExisting && count(array_unique(array_merge($newValue, $oldValue))) === count($oldValue)) {

                    $this->output($config->getName() . ' already contains [ ' . implode(', ', $newValue) . ' ]', $io, 'note');

                } else if (array_diff($oldValue, $newValue) === [] && array_diff($newValue, $oldValue) === []) {

                    $this->output($config->getName() . ' is already set to [ ' . implode(', ', $newValue) . ' ]', $io, 'note');

                } else {

                    $config->setValue($this->createConfigurationUpdateValue($type, $newValue));
                    $config->save();
                }

            } else {

                $updateValue = $this->createConfigurationUpdateValue($type, $newValue);
                if ($updateValue === $config->getValue()) {

                    $this->output($config->getName() . ' is already set to ' . $updateValue, $io, 'note');

                } else {

                    $config->setValue($updateValue);
                    $config->save();
                }
            }

        } else {

            $msg = $this->createConfigurationUpdateMsg($config, '; discovered value is ' . $this->createConfigurationUpdateValue($type, $newValue));
            $this->output($msg, $io, 'writeln');
        }
    }

    /**
     * Stringifies an value for a config-update
     * @param string $type
     * @param mixed $value
     * @return string
     * @throws ZfExtended_Exception
     */
    protected function createConfigurationUpdateValue(string $type, mixed $value): string
    {
        switch ($type) {

            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_STRING:
            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_FLOAT:
            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_INTEGER:
                if (is_array($value)) {
                    return (count($value) === 0) ? '' : strval($value[0]);
                }
                return strval($value);

            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_BOOLEAN:
                return ($value === true) ? '1' : '0';

            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_LIST:
                if (!is_array($value)) {
                    $value = [$value];
                }
                return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

            default:
                throw new ZfExtended_Exception('Unsupported config-type "' . $type . '"');
        }
    }

    /**
     * Creates an info-message about the passed config
     * @param editor_Models_Config $config
     * @param string $suffix
     * @return string
     * @throws Zend_Exception
     */
    protected function createConfigurationUpdateMsg(editor_Models_Config $config, string $suffix = ''): string
    {
        $is = $config->hasIniEntry() ? ' is in INI: ' : ' is: ';
        return '  config ' . $config->getName() . $is . $config->getValue() . $suffix;
    }

    /**
     * @param mixed $value
     * @return array
     */
    protected function convertValueToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        return (empty($value) && $value != '0') ? [] : [$value];
    }
}
