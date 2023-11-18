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
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Exception;
use editor_Models_Config;
use ZfExtended_DbConfig_Type_CoreTypes;
use MittagQI\ZfExtended\Service\ServiceAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Represents a HTTP based service that is represented by a single config-value that either is a simple string or a list
 * Concrete Implementations must have a valid $configurationConfig!
 */
abstract class AbstractHttpService extends ServiceAbstract
{

    /**
     * An assoc array that has to have at least 2 props:
     * "name": of the config
     * "type": of the config (either string, integer, boolean or list)
     * @var array
     */
    protected array $configurationConfig;

    /**
     * Retrieves an ID for the service to use for all database-purposes where the service must be identified uniquely
     * To have this seperate from ->getName() is purely for future developments
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->name;
    }

    /**
     * Retrieves, if the service works with different pools or manages load-balancing in the image or external endpoint
     * @return bool
     */
    public function isPooled(): bool
    {
        return false;
    }

    /**
     * Evaluates if the configuration is set
     * @return bool
     */
    public function isConfigured(): bool
    {
        try {
            return !empty($this->configHelper->getValue($this->configurationConfig['name'], $this->configurationConfig['type']));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Services neccessary only for full dockerized setups should not show up in other context's and can be marked as "optional"
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function isCheckSkipped(): bool
    {
        return !$this->mandatory && !$this->isProperlySetup();
    }

    /**
     * In case of simple HTTP services this is the same as isConfigured()
     * @return bool
     */
    public function isProperlySetup(): bool
    {
        return $this->isConfigured();
    }

    /**
     * Retrieves the configured service-url as array (or maybe there are several configured - not common though)
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getServiceUrls(): array
    {
        return $this->configHelper->getValue($this->configurationConfig['name'], $this->configurationConfig['type'], true);
    }

    /**
     * Retrieves the service-url for a simple service
     * In case of multiple configured this will be a random one
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function getServiceUrl(): ?string
    {
        $urls = $this->getServiceUrls();
        if (count($urls) === 1) {
            return $urls[0];
        }
        if (count($urls) > 1) {
            $max = count($urls) - 1;
            return $urls[random_int(0, $max)];
        }
        return null;
    }

    /**
     * Disables the given service URL via the Services memcache
     * Returns true, if all Services are down, otherwise false
     * @param string $serviceUrl
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function setServiceUrlDown(string $serviceUrl): bool
    {
        $allServices = $this->getServiceUrls();
        $downList = Services::getServiceDownList($this->getServiceId());
        // make sure, the down list contains just configured entries
        $downList = array_values(array_intersect($allServices, $downList));
        $downList[] = $serviceUrl;
        Services::saveServiceDownList($this->getServiceId(), $downList);
        return (count($downList) >= count($allServices));
    }

    /**
     * Retrieves the value with the config-name like "" out of the global config object
     * @param string $configName
     * @param string $configType
     * @param bool $asArray : if set, the result will be an array for simple types
     * @return mixed
     * @throws ZfExtended_Exception
     */
    public function getConfigValueFromName(string $configName, string $configType, bool $asArray = false): mixed
    {
        return $this->configHelper->getValue($configName, $configType, $asArray);
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
     * @throws JsonException
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


    #[ArrayShape(['host' => 'string', 'port' => 'int'])]
    protected function parseUrl(string $url): array
    {
        $port = parse_url($url, PHP_URL_PORT);
        if(empty($port) && array_key_exists('url', $this->configurationConfig)){
            $port = parse_url($this->configurationConfig['url'], PHP_URL_PORT);
        }
        if(empty($port)){
            $port = 80; // questionable default but will fit in most cases
        }
        return [
            'host' => parse_url($url, PHP_URL_HOST),
            // if port can not be parsed from given URL, use the default port from the default config:
            'port' => $port,
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
}
