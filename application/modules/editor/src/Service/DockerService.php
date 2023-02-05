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
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Exception;
use ZfExtended_Factory;
use editor_Models_Config;
use ZfExtended_DbConfig_Type_CoreTypes;
use MittagQI\ZfExtended\Service\AbstractService;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This is a service that is represented by a single config-value that either is a simple string or a list
 * Consrete Implementations must have a valid $configurationConfig!
 */
abstract class DockerService extends AbstractService
{

    /**
     * An assoc array that has to have at least 3 props:
     * "name": of the config
     * "type": of the config (either string, integer, boolean or list)
     * "url": the default-url, must have protocol, host & port
     * "additive": optional, if set to true, the configured value is added to the existing config if it does not exist
     * "optional": optional, if set to true, this will result in only a warning if the service is not configured
     * "healthcheck": optional, if set to a value like /status, this will be used to check the health of the service with a GET request that is expected to return status "200"
     * @var array
     */
    protected array $configurationConfig;

    /**
     * Base implementation for simple docker-services
     * @param SymfonyStyle $io
     * @param mixed $url
     * @param bool $doSave
     * @param array $config: optional to inject further dependencies
     * @return bool
     */
    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        $configType = $this->configurationConfig['type'];
        $configName = $this->configurationConfig['name'];
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
     */
    public function check(): bool
    {
        $checked = true;
        $this->isOptional = array_key_exists('optional', $this->configurationConfig) ? $this->configurationConfig['optional'] : false;
        $healthCheck = array_key_exists('healthcheck', $this->configurationConfig) ? $this->configurationConfig['healthcheck'] : null;
        $urls = $this->getConfigValueFromName($this->configurationConfig['name'], $this->configurationConfig['type']);

        if (empty($urls)) {
            $urls = [];
        } else if (!is_array($urls)) {
            $urls = [$urls];
        }
        if (count($urls) === 0) {
            if ($this->isOptional) {
                $this->warnings[] = 'There is no URL configured.';
            } else {
                $this->errors[] = 'There is no URL configured.';
                $checked = false;
            }
        } else {
            foreach ($urls as $url) {
                if (empty($url)) {
                    $this->errors[] = 'There is an empty URL set.';
                    $checked = false;
                } else if (empty($healthCheck)) {
                    if (!$this->checkConfiguredServiceUrl($url)) {
                        $this->errors[] = 'The configured URL "' . $url . '" is not reachable.';
                        $checked = false;
                    }
                } else {
                    $healthcheckUrl = rtrim($url, '/') . $healthCheck;
                    if (!$this->checkConfiguredHealthCheckUrl($healthcheckUrl)) {
                        $this->errors[] = 'A request on "' . $healthcheckUrl . '" did not bring the expected status "200".';
                        $checked = false;
                    }
                }
            }
        }
        return $checked;
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
     * @param string $url
     * @return bool
     */
    protected function checkConfiguredHealthCheckUrl(string $url): bool
    {
        $httpClient = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $httpClient->setUri($url);
        $response = $httpClient->request('GET');
        // the status request must return 200
        return ($response->getStatus() === 200);
    }

    /**
     * Checks a single URL (usually pointing to a docker container)
     * @param string $url
     * @return bool
     */
    protected function checkConfiguredServiceUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        if (empty($host)) {
            return false;
        }
        if (empty($port) || $port === false) {
            $port = null;
        }
        return $this->isDnsSet($host, $port);
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
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        if (!$this->isDnsSet($host, $port)) {
            $url = 'NONE (expected: ' . $url . ')';
            $result = false;
        }
        $this->output('Found "' . $label . '": ' . $url, $io, 'info');
        return $result;
    }

    /**
     * @param string $host
     * @param int $port
     * @return bool
     */
    protected function isDnsSet(string $host, int $port = null): bool
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
                $oldValue = json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);

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

                // compatibility: if a list shall be saved as single value, we simply take the first item
                if(is_array($newValue)){
                    $newValue = (count($newValue) === 0) ? '' : $newValue[0];
                }

                $updateValue = $this->createConfigurationUpdateValue($type, $newValue);
                if ($updateValue === $config->getValue()) {

                    $this->output($config->getName() . ' is already set to ' . $updateValue, $io, 'note');

                } else {

                    $config->setValue($updateValue);
                    $config->save();
                }
            }
            $config->setValue($updateValue);
            $config->save();

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
                return strval($value);

            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_BOOLEAN:
                return ($value === true) ? '1' : '0';

            case ZfExtended_DbConfig_Type_CoreTypes::TYPE_LIST:
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
