<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

use MittagQI\Translate5\Plugins\Okapi\ConfigMaintenance;
use MittagQI\Translate5\Plugins\Okapi\OkapiService;

set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7 || ! isset($config)) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$sqlConfigWhere = 'name="runtimeOptions.plugins.Okapi.serverUsed" AND value IN("okapi-longhorn-147","okapi-longhorn-148-snapshot-1")';

$db = Zend_Db_Table::getDefaultAdapter();
$configNeedsUpdating = (int) $db->fetchOne('SELECT COUNT(*) FROM Zf_configuration WHERE ' . $sqlConfigWhere);
$customerConfigNeedsUpdating = (int) $db->fetchOne('SELECT COUNT(*) FROM LEK_customer_config WHERE ' . $sqlConfigWhere);
if (! $configNeedsUpdating && ! $customerConfigNeedsUpdating) {
    return;
}

$insideBitbucket = ($_ENV['BITBUCKET_BUILD_NUMBER'] ?? 0);
if ($insideBitbucket) {
    return;
}

$okapiServerConfig = new ConfigMaintenance();
$okapiList = $okapiServerConfig->getServerList();
if (empty($okapiList)) {
    return;
}

// to ensure the file is not marked as processed
$this->doNotSavePhpForDebugging = false;

$serverUsed = $okapiServerConfig->getServerUsed();
$server147_148 = $server148_sp2 = '';

if (! empty($serverUsed) && ! empty($okapiList[$serverUsed]) && str_contains($okapiList[$serverUsed], 'okapi-longhorn-148-snapshot-2')) {
    $version = OkapiService::fetchServerVersion($okapiList[$serverUsed]);
    if ($version === '1.48.0-SNAPSHOT') {
        $server148_sp2 = $serverUsed;
    }
}
if (empty($server148_sp2)) {
    // loop through configured servers
    foreach ($okapiList as $serverName => $serverUrl) {
        if (str_contains($serverUrl, 'okapi-longhorn-148-snapshot-2')) {
            $version = OkapiService::fetchServerVersion($serverUrl);
            if ($version === '1.48.0-SNAPSHOT') {
                $server148_sp2 = $serverName;

                break;
            }
        } elseif (empty($server147_148) && str_starts_with($serverUrl, 'http') && preg_match('/-14[78]/', $serverUrl)) {
            $version = OkapiService::fetchServerVersion($serverUrl);
            if (version_compare($version, '1.47') >= 0 && version_compare($version, '1.49') < 0) {
                $server147_148 = $serverName;
            }
        }
    }
}
if (empty($server148_sp2) && ! empty($server147_148)) {
    // detect 148-2 by url
    $server148_2Url = str_replace(parse_url($okapiList[$server147_148], PHP_URL_PATH), '/okapi-longhorn-148-snapshot-2/', $okapiList[$server147_148]);
    $version = OkapiService::fetchServerVersion($server148_2Url);
    if ($version === '1.48.0-SNAPSHOT') {
        $server148_sp2 = 'okapi-longhorn-148-snapshot-2';
        if (! empty($okapiList[$server148_sp2])) {
            // extra safety, should rarely happen if ever
            $this->errors[] = 'Url for ' . $server148_sp2 . ' needs updating from ' . $okapiList[$server148_sp2] . ' to ' . $server148_2Url;

            return;
        }
        // add new server entry if detected
        $okapiServerConfig->addServer($server148_2Url, $server148_sp2);

        echo "Added $server148_sp2 with $server148_2Url\n";
    }
}

if (empty($server148_sp2)) {
    $this->errors[] = 'Could not find okapi-longhorn-148-snapshot-2: it contains important bugfixes and is highly recommended';

    return;
}

$db->query('UPDATE Zf_configuration SET value="' . $server148_sp2 . '" WHERE ' . $sqlConfigWhere);
$db->query('UPDATE LEK_customer_config SET value="' . $server148_sp2 . '" WHERE ' . $sqlConfigWhere);

echo "Updated okapi default server to $server148_sp2\n";

// file can be marked as processed
$this->doNotSavePhpForDebugging = true;
