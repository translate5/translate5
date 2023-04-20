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

use editor_Plugins_Okapi_Init;
use editor_Utils;
use JsonException;
use MittagQI\Translate5\Service\DockerServiceAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Zend_Config;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use Zend_Uri_Exception;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

final class Service extends DockerServiceAbstract
{

    const HEALTH_CHECK_PATH = '/projects';

    /**
     * Creates the corresponding key for a OKAPI-Url
     * @param string $version
     * @return string
     */
    public static function createServerKey(string $version): string
    {
        $suffix = '';
        $parts = explode('-', $version);
        // capture cases like "1.4.4.0-snapshot"
        if (count($parts) > 1) {
            $suffix = substr($version, strlen($parts[0]));
            $version = $parts[0];
        }
        if (str_ends_with($version, '.0')) {
            $version = substr($version, 0, -2);
        }
        $version = $version . $suffix;
        if (empty($version)) {
            return 'okapi-longhorn';
        }
        return 'okapi-longhorn-' . editor_Utils::secureFilename(str_replace('.', '', $version));
    }

    /**
     * Retrieves the OKAPI version. This API is only available since OKAPI 1.40.0
     * Older version will be keyed as before-140 (-> so only one server before 140 can be added - acceptable quirk)
     * A return-value of NULL points to a non-reachable url
     * @param string $okapiUrl
     * @return string|null
     */
    public static function fetchServerVersion(string $okapiUrl): ?string
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
                return 'before-140'; // case can not happen
            }
            $httpClient->setUri(rtrim($okapiUrl, '/') . self::HEALTH_CHECK_PATH);
            $response = $httpClient->request('GET');
            if ($response->getStatus() === 200) {
                return 'before-140'; // okapi-versions without /status endpoint must be 1.40.0 or lower ...
            }
            return null;
        } catch (Throwable) {
            return null;
        }
    }

    protected array $configurationConfig = [
        'name' => 'runtimeOptions.plugins.Okapi.server',
        'type' => 'string',
        'url' => 'http://okapi.:8080/', //path part with version is added automatically on locate call
        'healthcheck' => self::HEALTH_CHECK_PATH
    ];

    /**
     * Differing from the base-implementation we add checking the other configured
     * okapi-services here, not just the used one
     * (non-PHPdoc)
     * @see DockerServiceAbstract::check()
     */
    public function check(): bool
    {
        $result = true;
        $services = $this->config->runtimeOptions->plugins->Okapi->server;
        $serviceUsed = $this->config->runtimeOptions->plugins->Okapi->serverUsed;
        $url = (!empty($services) && !empty($serviceUsed)) ? ($services->$serviceUsed ?? null) : null;
        if (empty($url)) {
            $this->errors[] = 'There is no URL configured for entry "'.$serviceUsed.'".';
            $result = false;
        } else {
            $healthcheckUrl = rtrim($url, '/') . self::HEALTH_CHECK_PATH;
            if (!$this->checkConfiguredHealthCheckUrl($healthcheckUrl, $url)) {
                $this->errors[] = 'A request on "' . $healthcheckUrl .
                    '" did not bring the expected status "200" for entry "'.$serviceUsed.'".';
                $result = false;
            }
        }
        $this->checkOtherConfiguredServers($services, $serviceUsed, $result);
        if ($this->hasWarnings() || $this->hasErrors()) {
            $this->badSummary[] = 'Use "t5 okapi:[list|purge|update]" commands to fix the okapi setup '.
                'or setup the missing servers!';
        }
        return $result;
    }

    /**
     * Differing from base version we check the existing configured URLs for validity and dismiss the ones not reachable
     * (non-PHPdoc)
     * @param SymfonyStyle $io
     * @param mixed $url
     * @param bool $doSave
     * @param array $config
     * @return bool
     * @throws Zend_Http_Client_Exception
     * @throws JsonException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
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
            $newServers = $this->getNewServers($url);
            // add new entry by its version as name-suffix (note: we will overwrite other entries like
            // 'okapi-longhorn-xxx' without further notice) ... we use a scheme that is common on the existing instances
            $foundVersions = $this->getOkapiVersions($url);

            if (empty($foundVersions)) {
                $url = $this->singleOkapiInstanceFallback($url);
                $version = self::fetchServerVersion($url);
                if (is_null($version)) {
                    return false; //if no version returned here, we can not proceed
                }
                $newServers[self::createServerKey($version)] = $url;
            } else {
                $newServers = array_merge($newServers, $foundVersions);
            }

            $okapiServerConfig = new ConfigMaintenance();
            foreach ($newServers as $name => $url) {
                //add update the found new servers
                $okapiServerConfig->addServer($url, $name);
            }

            //purge the unused ones, sort by version so that latest is kept also unused
            $okapiServerConfig->purge($okapiServerConfig->getSummary(), sortByVersion: true);

            return true;
        }
        return false;
    }

    /**
     * Unfortunately we cannot fetch the version directly as older versions do not support the status.json
     * (non-PHPdoc)
     * @param string $responseBody
     * @param string $serviceUrl
     * @return string|null
     * @see DockerServiceAbstract::findVersionInResponseBody()
     */
    protected function findVersionInResponseBody(string $responseBody, string $serviceUrl): ?string
    {
        return self::fetchServerVersion($serviceUrl);
    }

    /**
     * @param Zend_Config $services
     * @param string $serviceUsed
     */
    private function checkOtherConfiguredServers(Zend_Config $services, string $serviceUsed, bool $mainServiceSuccess)
    {
        foreach ($services as $name => $url) {
            if ($name === $serviceUsed) {
                continue;
            }
            if (empty($url)) {
                $this->warnings[] = 'There is no URL configured for entry "'.$name.'.';
                continue;
            }
            $healthcheckUrl = rtrim($url, '/') . self::HEALTH_CHECK_PATH;
            if ($this->checkConfiguredHealthCheckUrl($healthcheckUrl, $url, false)) {
                if (! $mainServiceSuccess) {
                    $this->warnings[] = '';
                    $this->warnings[] = 'A request on "' . $healthcheckUrl . ' for entry "' . $name .
                        '" was successful but it is not set as default okapi service.';
                    $this->warnings[] = '';
                }
            } else {
                $this->warnings[] = 'A request on "' . $healthcheckUrl .
                    '" did not bring the expected status "200" for entry "'.$name.'.';
            }
        }
    }

    /**
     * returns all translate5 supported Okapi versions provided by the queried jetty root URL
     * by default jetty delivers an error page providing all valid contexts (installed war files).
     *
     * @param mixed $url
     * @return array
     * @throws Zend_Uri_Exception
     */
    private function getOkapiVersions(mixed $url): array
    {
        $url = \Zend_Uri_Http::fromString($url);
        $url->setPath(''); //clean path if given to get okapi root server
        $result = [];
        try {
            $httpClient = ZfExtended_Factory::get(Zend_Http_Client::class);
            $httpClient->setUri($url);
            // some endpoints need to be told to return JSON
            $response = $httpClient->request('GET');
            if ($response->getStatus() === 404
                && str_contains($response->getBody(), 'Contexts known to this server are:')
                && preg_match_all('#href="/(okapi-longhorn-[^/]+)/#', $response->getBody(), $matches)) {
                    foreach ($matches[1] as $match) {
                        if (in_array($match, editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION)) {
                            $result[$match] = $url.'/'.$match.'/';
                        }
                    }
            }
        } catch (Throwable) {
            // do nothing here
        }
        ksort($result, SORT_NATURAL);
        return $result;
    }

    /**
     * @param mixed $url
     * @return array
     */
    private function getNewServers(mixed $url): array
    {
        $newServers = [];
        if (!empty($this->config->runtimeOptions->plugins->Okapi->server)) {
            foreach ($this->config->runtimeOptions->plugins->Okapi->server as $name => $otherUrl) {
                if (!empty($otherUrl)
                    && $otherUrl != $url
                    && $this->checkConfiguredHealthCheckUrl(
                        rtrim($otherUrl, '/') . self::HEALTH_CHECK_PATH,
                        $otherUrl,
                        false
                    )) {
                    // make sure, the server-key follows our required naming-scheme
                    if (!in_array($name, editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION)) {
                        $version = self::fetchServerVersion($otherUrl);
                        $newServers[self::createServerKey($version)] = $otherUrl;
                    } else {
                        $newServers[$name] = $otherUrl;
                    }
                }
            }
        }
        return $newServers;
    }

    /**
     * re-add the version-less okapi-longhorn path for containers with only one okapi version
     * @param string $url
     * @return string
     * @throws Zend_Uri_Exception
     */
    private function singleOkapiInstanceFallback(string $url): string
    {
        $uri = \Zend_Uri_Http::fromString($url);
        if (!str_contains($uri->getPath(), 'okapi-longhorn')) {
            // if just the okapi server was passed here, we assume a server with one okapi version installed,
            // so path to okapi itself must be added
            $uri->setPath('/okapi-longhorn');
        }
        return $uri->getUri();
    }
}
