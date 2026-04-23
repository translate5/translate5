<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\Okapi\Config;

use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use MittagQI\Translate5\Plugins\Okapi\OkapiService;
use Zend_Db_Table;

/**
 * Updates the current OKAPI configuration to the latest supported SNAPSHOT of the most recent version
 * as defined in editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION
 * This upgrade will only happen, if a new version is detected but not available in the configured servers
 */
class ServerConfigUpgrade
{
    private ServerConfigMaintenance $maintenance;

    private array $messages = [];

    public function __construct()
    {
        $this->maintenance = new ServerConfigMaintenance();
    }

    /**
     * Upgrades to the latest OKAPI version if it is available
     * - the latest version defined in editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION
     * - if available, it will be set as default and for all customers
     * - if already set, nothing happens
     * - a warning will be added, if an older version is the current default
     * - returns, if the action was successful or problems occurred
     * @throws OkapiException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Uri_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function update(): bool
    {
        if (! $this->maintenance->isPluginActive()) {
            $this->messages[] = 'The okapi plugin is not active.';

            return true;
        }
        $latestAvailable = $this->maintenance::getLatestAvailableVersion();
        $latestConfigured = $this->maintenance->getLatestConfiguredVersion();
        $serverUsed = $this->maintenance->getServerUsed();

        if ($latestConfigured === null) {
            $this->messages[] = 'No configuration found for okapi server(s)';

            return false;
        }

        if ($latestAvailable === $latestConfigured) {
            $this->messages[] = 'The latest configured server is already the latest available version.';
            $log = '';
            // we write a message that there is a potential problem
            if ($serverUsed !== $latestConfigured) {
                $log = 'The set OKAPI version to be used is not the latest configured version';
            }
            if ($this->customerConfigsNeedUpdate($latestConfigured)) {
                $log .= ($log === '') ?
                    'Some customer-configs are not sing the latest available OKAPI version' :
                    ' and some customer-configs are outdated as well';
            }
            if ($log !== '') {
                $this->addSysLogEntry($log, true);
                $this->messages[] = $log . '.';
            }

            return true;
        } else {
            $serverList = $this->maintenance->getServerList();
            $url = \Zend_Uri_Http::fromString($serverList[$latestConfigured]);
            $url->setPath('');
            $latestUrl = rtrim($url->__toString(), '/') . '/' . $latestAvailable . '/';

            $service = editor_Plugins_Okapi_Init::createService(OkapiService::ID);
            if ($service->checkOkapiServiceUrl($latestUrl)) {
                $this->messages[] = 'The latest configured server was not the latest available version' .
                    ' but the latest could be found in the container.';
                $this->maintenance->addServer($latestUrl, $latestAvailable);
                $this->maintenance->updateServerUsed($latestAvailable);
                $this->messages[] = 'It was added to the available servers and the generally used server was set' .
                    ' to the latest version.';
                $log = 'OKAPI was updated to the latest version “' . $latestAvailable . '” which is the new default';
                // we may need to adjust customer configs as well
                if ($this->customerConfigsNeedUpdate($latestAvailable)) {
                    $this->adjustCustomerConfigs($latestAvailable);
                    $log .= ', all customer specific settings have beed adjusted accordingly';
                    $this->messages[] = 'Customer-configs needed to be adjusted.';
                }
                $this->addSysLogEntry($log, false);

                return true;
            } else {
                $this->messages[] = 'The latest configured server is not the latest available version but' .
                    ' the latest seems not to be available in the container.';

                return false;
            }
        }
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Returns all available Okapi endpoints for the distinct configured server URLs.
     * The result contains one row per available endpoint.
     *
     * @return array<int, array<string, string>>
     * @throws \Zend_Uri_Exception
     */
    public function getAvailableEndpointsByConfiguredServer(): array
    {
        $configuredServerList = $this->maintenance->getServerList();
        if (empty($configuredServerList)) {
            return [];
        }

        $serversByBaseUrl = [];
        foreach ($configuredServerList as $version => $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }
            $uri = \Zend_Uri_Http::fromString($url);
            $uri->setPath('');
            $baseUrl = rtrim($uri->__toString(), '/');
            $normalizedEndpoint = rtrim($url, '/') . '/';
            $serversByBaseUrl[$baseUrl][$version] = $normalizedEndpoint;
        }

        if (empty($serversByBaseUrl)) {
            return [];
        }

        ksort($serversByBaseUrl, SORT_NATURAL);
        $service = editor_Plugins_Okapi_Init::createService(OkapiService::ID);
        $supportedVersions = array_flip(array_map('strtolower', editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION));
        $rows = [];

        foreach ($serversByBaseUrl as $baseUrl => $configuredEndpoints) {
            $availableEndpoints = $service->findAllOkapiVersions($baseUrl);

            // Single-instance setups may not expose the jetty context listing.
            if (empty($availableEndpoints)) {
                $availableEndpoints = $configuredEndpoints;
            }

            ksort($availableEndpoints, SORT_NATURAL);
            foreach ($availableEndpoints as $version => $endpoint) {
                $normalizedEndpoint = rtrim((string) $endpoint, '/') . '/';
                $rows[] = [
                    'server' => $baseUrl,
                    'version' => (string) $version,
                    'endpoint' => $normalizedEndpoint,
                    'configured' => in_array($normalizedEndpoint, $configuredEndpoints, true) ? 'yes' : 'no',
                    't5 supported' => array_key_exists(strtolower((string) $version), $supportedVersions) ? 'yes' : 'no',
                ];
            }
        }

        return $rows;
    }

    private function customerConfigsNeedUpdate(string $latestConfigured): bool
    {
        $updateNeeded = (int) Zend_Db_Table::getDefaultAdapter()->fetchOne(
            'SELECT COUNT(*) FROM LEK_customer_config WHERE name = ? AND value != ?',
            [ServerConfigMaintenance::CONFIG_SERVER_USED, $latestConfigured]
        );

        return $updateNeeded > 0;
    }

    private function adjustCustomerConfigs(string $latestConfigured): void
    {
        Zend_Db_Table::getDefaultAdapter()->query(
            'UPDATE LEK_customer_config SET value = ? WHERE name = ?',
            [$latestConfigured, ServerConfigMaintenance::CONFIG_SERVER_USED]
        );
    }

    private function addSysLogEntry(string $msg, bool $isWarning): void
    {
        $logger = \Zend_Registry::get('logger')->cloneMe('editor.okapi');
        /** @var \ZfExtended_Logger $logger */
        if ($isWarning) {
            $logger->warn('E1781', $msg, [
                'details' => $msg,
            ]);
        } else {
            $logger->info('E1781', $msg, [
                'details' => $msg,
            ]);
        }
    }
}
