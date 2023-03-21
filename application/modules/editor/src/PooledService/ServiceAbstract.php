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

namespace MittagQI\Translate5\PooledService;

use JsonException;
use MittagQI\Translate5\Service\DockerServiceAbstract;
use MittagQI\Translate5\Service\Services;
use stdClass;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend_Cache_Exception;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Exception;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

/**
 * This represents a multi-url service where the URLs are pooled for "gui" (for editing), "import" and "default"
 * The default-service is represented by $configurationConfig while gui and import have their own setup-data
 */
abstract class ServiceAbstract extends DockerServiceAbstract
{
    /**
     * Structure see DockerServiceAbstract::configurationConfig
     * @var array
     */
    protected array $guiConfigurationConfig;

    /**
     * Structure see DockerServiceAbstract::configurationConfig
     * @var array
     */
    protected array $importConfigurationConfig;

    /**
     * Retrieves one of our Pools
     * The URLs will be filtered for services that are marked as DOWN via the global services mem-cache
     * @param string $pool
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getPooledServiceUrls(string $pool): array
    {
        switch ($pool) {

            case 'default':
                return $this->filterDownedServiceUrls($this->getDefaultServiceUrls());

            case 'gui':
                return $this->filterDownedServiceUrls($this->getGuiServiceUrls());

            case 'import':
                return $this->filterDownedServiceUrls($this->getImportServiceUrls());

            default:
                throw new ZfExtended_Exception('PooledService::getPooledServiceUrls: pool must be: default | gui | import');
        }
    }

    /**
     * Retrieves a random url out of one of our Pools
     * The URLs will be filtered for services that are marked as DOWN via the global services mem-cache
     * @param string $pool
     * @return string|null
     * @throws ZfExtended_Exception
     */
    public function getPooledServiceUrl(string $pool): ?string
    {
        $urls = $this->getPooledServiceUrls($pool);
        if (count($urls) > 1) {
            $max = count($urls) - 1;
            return $urls[random_int(0, $max)];
        }
        return (count($urls) > 0) ? $urls[0] : null;
    }

    /**
     * Compat with non-pooled services
     * Retrieves the unique sum of all our configured URLs
     * @return array
     * @throws ZfExtended_Exception
     */
    public function getServiceUrls(): array
    {
        return array_unique(array_merge($this->getDefaultServiceUrls(), $this->getGuiServiceUrls(), $this->getImportServiceUrls()));
    }

    /**
     * Retrieves an administrative overview of the services. This API is intended to be called periodically
     * Flushes all detected services being down to the mem-cache's down-list
     * @param bool $saveStateToMemCache
     * @return stdClass
     * @throws ZfExtended_Exception
     * @throws Zend_Cache_Exception
     */
    public function getServiceState(bool $saveStateToMemCache=true): stdClass
    {
        $state = new stdClass();
        $state->running = [];
        $state->version = [];
        $state->runningAll = true;
        $downServices = [];

        foreach ($this->getServiceUrls() as $url) {
            $result = $this->checkServiceUrl($url);
            $state->running[$url] = $result['success'];
            $state->version[$url] = $result['version'];
            if(!$result['success']){
                $state->runningAll = false;
                $downServices[] = $url;
            }
        }
        // save the down-list
        if($saveStateToMemCache){
            Services::saveServiceDownList($this->getServiceId(), $downServices);
        }
        return $state;
    }

    /**
     * To enable a transition from pooled services (providing an t5-based load-balancing) to docker services which include load-balancing,
     * a pooled-service with just one default-url configured will count as non-pooled service.
     * This will lead to parallel workers up to IPs behind the single URL
     * @return bool
     */
    public function isPooled(): bool
    {
        if (count($this->getDefaultServiceUrls()) === 1 && count($this->getGuiServiceUrls()) === 0 && count($this->getImportServiceUrls()) === 0) {
            return false;
        }
        return true;
    }

    /**
     * @param string $pool
     * @return bool
     */
    public function isValidPool(string $pool): bool
    {
        return ($pool === 'default' || $pool === 'import' || $pool === 'gui');
    }

    /**
     * @return bool
     * @throws ZfExtended_Exception
     */
    public function check(): bool
    {
        $allUniqueUrls = $this->getServiceUrls();
        if (empty($allUniqueUrls)) {
            $this->errors[] = 'There are no service-URLs at all configured.';
        } else if (empty($this->getDefaultServiceUrls())) {
            $this->errors[] = 'There are no default-URLs configured.';
        }
        $checked = true;
        foreach ($allUniqueUrls as $url) {
            if (empty($url)) {
                $this->errors[] = 'There is an empty service-URL set.';
                $checked = false;
            } else if (!$this->checkConfiguredServiceUrl($url)) {
                $this->errors[] = 'The configured service-URL "' . $url . '" is not reachable.';
                $checked = false;
            } else {
                $result = $this->checkServiceUrl($url);
                $this->addCheckResult($url, $result['version']);
                if (!$result['success']) {
                    $this->errors[] = 'The configured service-URL "' . $url . '" is not working properly.';
                    $checked = false;
                }
            }
        }
        return $checked;
    }

    /**
     * Implementation for pooled services.
     * In this case, it is expected, the $url-param is an assoc array containing 3 further arrays with urls: 'default, 'gui', 'import' OR the config "autodetect" is set and $url is a simple value
     * @param SymfonyStyle $io
     * @param mixed $url
     * @param bool $doSave
     * @param array $config : optional to inject further dependencies. With pooled-services, a "autodetect" integer can be set, that leads to detecting services by a special namimg-scheme "service-gui-1"
     * @return bool
     */
    public function locate(SymfonyStyle $io, mixed $url, bool $doSave = false, array $config = []): bool
    {
        $pooledUrls = ['default' => [], 'gui' => [], 'import' => []];

        if (array_key_exists('remove', $config) && $config['remove'] === true) {
            $this->updatePooledConfigurationConfig($pooledUrls, $doSave, $io);
            return false;
        }

        $autodetect = (array_key_exists('autodetect', $config) && is_int($config['autodetect'])) ? $config['autodetect'] : -1;
        if ($autodetect > 1) {

            if (is_array($url) || empty($url)) {
                throw new ZfExtended_Exception('PooledService::locate: in case of autodetection the url must be a simple value');
            }
            $host = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT);
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            if (empty($host) || empty($port)) {
                throw new ZfExtended_Exception('PooledService::locate: the url did not contain host and port');
            }
            // detecting the url's to use
            $types = array_keys($pooledUrls);
            for ($i = 1; $i <= $autodetect; $i++) {
                $potentialHost = $host . '_' . $i . '.';
                if ($this->isDnsSet($potentialHost, $port)) {
                    $pooledUrls['default'][] = 'http://' . $potentialHost . ':' . $port . $path;
                }
                foreach ($types as $type) {
                    $potentialHost = $host . '_' . $type . '_' . $i . '.';
                    if ($this->isDnsSet($potentialHost, $port)) {
                        $pooledUrls[$type][] = 'http://' . $potentialHost . ':' . $port . $path;
                    }
                }
            }
            if (empty($pooledUrls['default'])) {
                $this->output('No default pooled URLs have been found for "' . $this->getName() . '"', $io, 'info');
                return false;
            }
            $this->updatePooledConfigurationConfig($pooledUrls, $doSave, $io);
            return true;

        } else {

            // setting the URLs to use explicitly
            // first collecting defaults if no urls passed
            if (!is_array($url)) {
                $url = [];
            }
            if (!array_key_exists('default', $url) || empty($url['default'])) {
                $url['default'] = $this->configurationConfig['url'];
            }
            if (!array_key_exists('gui', $url) || empty($url['gui'])) {
                $url['gui'] = $this->guiConfigurationConfig['url'];
            }
            if (!array_key_exists('import', $url) || empty($url['import'])) {
                $url['import'] = $this->importConfigurationConfig['url'];
            }
            if (empty($url['default'])) {
                throw new ZfExtended_Exception('PooledService::locate: param gui must be an assoc array with entries "default", "gui" and "import"');
            }
            $pooledUrls['default'] = $this->convertValueToArray($url['default']);
            $pooledUrls['gui'] = $this->convertValueToArray($url['gui']);
            $pooledUrls['import'] = $this->convertValueToArray($url['import']);
            // checking the urls
            $checked = true;
            $urls = array_unique(array_merge($pooledUrls['default'], $pooledUrls['gui'], $pooledUrls['import']));
            foreach ($urls as $url) {
                if (!$this->checkPotentialServiceUrl($this->getName(), $url, $io)) {
                    $checked = false;
                }
            }
            if ($checked) {
                $this->updatePooledConfigurationConfig($pooledUrls, $doSave, $io);
                return true;
            }
            return false;
        }
    }

    /**
     * This method needs to be implemented for all Pooled services
     * It Requests the Service and retrieves the success and the version
     * @return array{
     *     success: bool,
     *     version: string|null
     * }
     */
    abstract protected function checkServiceUrl(string $url): array;

    /**
     * Updates the 3 pooled configs with the found URLs
     * @param array $pooledUrls
     * @param bool $doSave
     * @param SymfonyStyle|null $io
     * @throws ZfExtended_Exception
     * @throws JsonException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function updatePooledConfigurationConfig(array $pooledUrls, bool $doSave, SymfonyStyle $io = null)
    {
        $this->updateConfigurationConfig($this->configurationConfig['name'], $this->configurationConfig['type'], $pooledUrls['default'], $doSave, $io);
        $this->updateConfigurationConfig($this->guiConfigurationConfig['name'], $this->guiConfigurationConfig['type'], $pooledUrls['gui'], $doSave, $io);
        $this->updateConfigurationConfig($this->importConfigurationConfig['name'], $this->importConfigurationConfig['type'], $pooledUrls['import'], $doSave, $io);
    }

    /**
     * Retrieves the unfiltered default service url's for a pooled service
     * @return array
     * @throws ZfExtended_Exception
     */
    private function getDefaultServiceUrls(): array
    {
        return $this->getConfigValueFromName($this->configurationConfig['name'], $this->configurationConfig['type'], true);
    }

    /**
     * Retrieves the unfiltered default service url's for a pooled service
     * @return array
     * @throws ZfExtended_Exception
     */
    private function getGuiServiceUrls(): array
    {
        return $this->getConfigValueFromName($this->guiConfigurationConfig['name'], $this->guiConfigurationConfig['type'], true);
    }

    /**
     * Retrieves the unfiltered default service url's for a pooled service
     * @return array
     * @throws ZfExtended_Exception
     */
    private function getImportServiceUrls(): array
    {
        return $this->getConfigValueFromName($this->importConfigurationConfig['name'], $this->importConfigurationConfig['type'], true);
    }

    /**
     * Will exclude service-urls, that have been marked as "down" via the global services mem-cache
     * @param array $serviceUrls
     * @return array
     */
    private function filterDownedServiceUrls(array $serviceUrls): array
    {
        return array_diff($serviceUrls, Services::getServiceDownList($this->getServiceId()));
    }
}
