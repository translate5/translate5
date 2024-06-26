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

use MittagQI\ZfExtended\Service\ServiceAbstract;
use Throwable;
use Zend_Cache;
use Zend_Cache_Core;
use Zend_Cache_Exception;
use Zend_Config;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Cache_MySQLMemoryBackend;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Plugin_Exception;
use ZfExtended_Plugin_Manager;

final class Services
{
    private static ?Zend_Cache_Core $memCache = null;

    /**
     * Represents the global/base services we have. They must be given in the format name => Service class name
     * where name usually represents the docker service name, e.g. [ 'someservice' => Service::class ]
     */
    private static array $services = [
        'php' => Php::class,
        'proxy' => Proxy::class,
        't5memory' => T5Memory::class,
    ];

    /**
     * Retrieves a global service by it's name
     * @throws ZfExtended_Exception
     */
    public static function getService(string $serviceName, Zend_Config $config = null): ServiceAbstract
    {
        if (! array_key_exists($serviceName, self::$services)) {
            throw new ZfExtended_Exception('Service "' . $serviceName . '" not configured in the global Services');
        }

        return ZfExtended_Factory::get(self::$services[$serviceName], [$serviceName, null, $config]);
    }

    /**
     * Retrieves all global/base services
     * Returned will be an assoc array like $serviceName => $service
     * @return ServiceAbstract[];
     */
    public static function getServices(Zend_Config $config): array
    {
        $services = [];
        foreach (self::$services as $serviceName => $serviceClass) {
            $services[$serviceName] = ZfExtended_Factory::get($serviceClass, [$serviceName, null, $config]);
        }

        return $services;
    }

    /**
     * Retrieves all global services and all configured plugin services
     * Returned will be an assoc array like $serviceName => $service
     * @return ServiceAbstract[]
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Plugin_Exception
     */
    public static function getAllServices(Zend_Config $config, bool $loadPlugins = false): array
    {
        $services = self::getServices($config);
        /* @var ZfExtended_Plugin_Manager $pluginManager */
        $pluginManager = Zend_Registry::get('PluginManager');
        if ($loadPlugins) {
            $pluginManager->bootstrap();
        }
        foreach ($pluginManager->getInstances() as $pluginInstance) {
            foreach ($pluginInstance->getServices($config) as $serviceName => $service) {
                if (array_key_exists($serviceName, $services)) {
                    // all services must have unique names
                    throw new ZfExtended_Exception('Duplicate Service Name "' . $serviceName . '" in Plugin ' . get_class($pluginInstance));
                }
                $services[$serviceName] = $service;
            }
        }

        return $services;
    }

    /**
     * Retrieves all global services and all available plugin services (of all plugins where the classes are available in the code)
     * Returned will be an assoc array like $serviceName => $service
     * @return ServiceAbstract[]
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Plugin_Exception
     */
    public static function getAllAvailableServices(Zend_Config $config): array
    {
        $services = self::getServices($config);
        /* @var ZfExtended_Plugin_Manager $pluginManager */
        $pluginManager = Zend_Registry::get('PluginManager');
        foreach ($pluginManager->getAvailable() as $pluginClass) {
            foreach ($pluginClass::createAllServices($config) as $serviceName => $service) { /* @var $service ServiceAbstract */
                $services[$serviceName] = $service;
            }
        }

        return $services;
    }

    /**
     * Retrieve the service configs from all base services, that need to be copied or added to the test-DB
     */
    public static function getTestConfigs(): array
    {
        $testConfigs = [];
        foreach (self::getServices(Zend_Registry::get('config')) as $service) {
            $testConfigs[] = $service->getTestConfigs();
        }

        return array_merge(...$testConfigs);
    }

    /**
     * Retrieve the mocked service configs from all base services
     */
    public static function getMockConfigs(): array
    {
        $mockConfigs = [];
        foreach (self::getServices(Zend_Registry::get('config')) as $service) {
            $mockConfigs[] = $service->getMockConfigs();
        }

        return array_merge(...$mockConfigs);
    }

    /**
     * Retrieves the global service with the given name or null if it does not exist FOR THE CURRENTLY CONFIGURED PLUGINS
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public static function findService(Zend_Config $config, string $serviceName, bool $loadPlugins = false): ?ServiceAbstract
    {
        $services = self::getServices($config);
        if (array_key_exists($serviceName, $services)) {
            return $services[$serviceName];
        }

        $pluginManager = Zend_Registry::get('PluginManager');
        if ($loadPlugins) {
            $pluginManager->bootstrap();
        }
        /* @var $pluginManager ZfExtended_Plugin_Manager */
        foreach ($pluginManager->getInstances() as $pluginInstance) {
            if ($pluginInstance::hasService($serviceName)) {
                return $pluginInstance->getService($serviceName, $config);
            }
        }

        return null;
    }

    /**
     * Adds the service checks to the system checks in their special result-format
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     */
    public static function addServiceChecksAsSystemChecks(array &$results, bool $loadPlugins = false)
    {
        $services = self::getAllServices(Zend_Registry::get('config'), $loadPlugins);
        foreach ($services as $serviceName => $service) {
            if (! $service->isCheckSkipped()) {
                $results[$serviceName] = $service->systemCheck();
            }
        }
    }

    /**
     * Retrieves the down-list for a service
     */
    public static function getServiceDownList(string $serviceId): array
    {
        try {
            $list = self::getMemCache()->load($serviceId . 'DownList');

            return (is_array($list)) ? $list : [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Saves the down-list for a service
     */
    public static function saveServiceDownList(string $serviceId, array $offlineUrls)
    {
        try {
            self::getMemCache()->save($offlineUrls, $serviceId . 'DownList');
        } catch (Throwable) {
        }
    }

    /**
     * disables the given service URL via memcache.
     */
    public static function setServiceDown(string $serviceId, string $serviceUrl)
    {
        $list = self::getServiceDownList($serviceId);
        $list[] = $serviceUrl;
        self::saveServiceDownList($serviceId, $list);
    }

    /**
     * @throws Zend_Cache_Exception
     */
    private static function getMemCache(): Zend_Cache_Core
    {
        if (self::$memCache == null) {
            self::$memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend(), [
                'automatic_serialization' => true,
            ]);
        }

        return self::$memCache;
    }
}
