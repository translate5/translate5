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

namespace MittagQI\Translate5\Plugins\Okapi;

use editor_Plugins_Okapi_Init;
use Zend_Db_Table;

/**
 * Updates the current OKAPI configuration to the latest supported SNAPSHOT of the most recent version
 * as defined in editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION
 * The constructor needs the string provided that the longhorn built reports as its version !
 */
class OkapiVersionConfigUpdater
{
    private string $targetVersion;

    private int $major;

    private int $minor;

    private int $patch; // @phpstan-ignore-line

    private array $errors;

    private string $successMsg = '';

    /**
     * @param string $reportedVersion The version as reported by OKAPI itself like '1.48.0-SNAPSHOT'
     */
    public function __construct(
        private readonly string $reportedVersion
    ) {
        $versions = editor_Plugins_Okapi_Init::SUPPORTED_OKAPI_VERSION;
        $this->targetVersion = $versions[count($versions) - 1];
        $matches = []; // smth like 1.48.0-SNAPSHOT
        $matched = preg_match(
            '~([0-9]{1})\.([0-9]{1,2})\.([0-9]{1,2})(-snapshot)?~i',
            strtolower($this->reportedVersion),
            $matches
        );
        if ($matched !== 1) {
            throw new \ZfExtended_Exception('Invalid OKAPI version: “' . $this->reportedVersion . '”');
        }
        $this->major = (int) $matches[1];
        $this->minor = (int) $matches[2];
        $this->patch = (int) $matches[3];

        if (! str_contains($this->targetVersion, '-' . $this->major . $this->minor)) {
            throw new \ZfExtended_Exception(
                'Provided OKAPI version: “' . $this->reportedVersion .
                '” does not match the latest supported version.'
            );
        }
    }

    public function update(): bool
    {
        $this->errors = [];
        // end any update if we are in a Bitbucket-Environment
        $insideBitbucket = ($_ENV['BITBUCKET_BUILD_NUMBER'] ?? 0);
        if ($insideBitbucket) {
            // we pretend to have run successfully
            return true;
        }

        // get the configured server list
        $okapiServerConfig = new ConfigMaintenance();
        $okapiList = $okapiServerConfig->getServerList();
        if (empty($okapiList)) {
            // this is a problem
            return false;
        }

        // check if we need an upgrade
        $db = Zend_Db_Table::getDefaultAdapter();
        $where = 'name="runtimeOptions.plugins.Okapi.serverUsed" AND value<>"' . $this->targetVersion . '"';
        $configNeedsUpdating = (int) $db->fetchOne('SELECT COUNT(*) FROM Zf_configuration WHERE ' . $where);
        $customerConfigNeedsUpdating = (int) $db->fetchOne('SELECT COUNT(*) FROM LEK_customer_config WHERE ' . $where);

        if (! $configNeedsUpdating && ! $customerConfigNeedsUpdating) {
            return true;
        }

        $serverUsed = $okapiServerConfig->getServerUsed();
        $nearestServer = $currenServerUsed = '';

        if (! empty($serverUsed) &&
            ! empty($okapiList[$serverUsed]) &&
            str_contains($okapiList[$serverUsed], $this->targetVersion)
        ) {
            $version = OkapiService::fetchServerVersion($okapiList[$serverUsed]);
            if (strtolower($version) === strtolower($this->reportedVersion)) {
                $currenServerUsed = $serverUsed;
            }
        }
        if (empty($currenServerUsed)) {
            // loop through configured servers
            $neededServerPattern = '/-' . $this->major . '(' . ($this->minor - 1) . '|' . $this->minor . ')/';
            foreach ($okapiList as $serverName => $serverUrl) {
                if (str_contains($serverUrl, $this->targetVersion)) {
                    $version = OkapiService::fetchServerVersion($serverUrl);
                    if (strtolower($version) === strtolower($this->reportedVersion)) {
                        $currenServerUsed = $serverName;

                        break;
                    }
                } elseif (empty($nearestServer) &&
                    str_starts_with($serverUrl, 'http') &&
                    preg_match($neededServerPattern, $serverUrl) === 1
                ) {
                    $version = OkapiService::fetchServerVersion($serverUrl);
                    // make szúre the version is of the correct minor
                    if (version_compare($version, $this->major . '.' . ($this->minor - 1)) >= 0 &&
                        version_compare($version, $this->major . '.' . ($this->minor + 1)) < 0
                    ) {
                        $nearestServer = $serverName;
                    }
                }
            }
        }
        if (empty($currenServerUsed) && ! empty($nearestServer)) {
            // detect nearest server version by url
            $nearestServerUrl = str_replace(
                parse_url($okapiList[$nearestServer], PHP_URL_PATH),
                '/' . $this->targetVersion . '/',
                $okapiList[$nearestServer]
            );
            $version = OkapiService::fetchServerVersion($nearestServerUrl);
            if (! empty($version) && strtolower($version) === strtolower($this->reportedVersion)) {
                $currenServerUsed = $this->targetVersion;
                if (! empty($okapiList[$currenServerUsed])) {
                    // extra safety, should rarely happen if ever
                    $this->errors[] =
                        'Url for ' . $currenServerUsed . ' needs updating from ' . $okapiList[$currenServerUsed] .
                        ' to ' . $nearestServerUrl;

                    return false;
                }
                // add new server entry if detected
                $okapiServerConfig->addServer($nearestServerUrl, $currenServerUsed);

                $this->successMsg .= "Added $currenServerUsed with $nearestServerUrl\n";
            }
        }

        if (empty($currenServerUsed)) {
            $this->errors[] =
                'Could not find ' . $this->targetVersion .
                ': it contains important bugfixes and is highly recommended';

            return false;
        }

        $db->query(
            'UPDATE Zf_configuration SET value="' . $currenServerUsed .
            '" WHERE name="runtimeOptions.plugins.Okapi.serverUsed"'
        );
        $db->query(
            'UPDATE LEK_customer_config SET value="' . $currenServerUsed .
            '" WHERE name="runtimeOptions.plugins.Okapi.serverUsed"'
        );

        $this->successMsg .= "Updated okapi default server to $currenServerUsed\n";

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessMsg(): string
    {
        return $this->successMsg;
    }
}
