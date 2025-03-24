<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Upgrader\UpgraderTo147;
use MittagQI\Translate5\Plugins\Okapi\OkapiService;

set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7 || ! isset($config)) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$bconfUpgradeNeeded = $db->fetchOne('SELECT id FROM LEK_okapi_bconf WHERE versionIdx < 10 LIMIT 1');

if ($bconfUpgradeNeeded) {
    $find147VersionPlus = function (array $serverList, string $serverName = ''): ?string {
        // test configured version
        if (array_key_exists($serverName, $serverList)) {
            $version = OkapiService::fetchServerVersion($serverList[$serverName]);
            if ($version !== null && version_compare($version, '1.47') >= 0) {
                return $serverName;
            }
        }
        // test other servers
        foreach ($serverList as $otherName => $serverUrl) {
            if (preg_match('/(14[7-9]|1[5-9]\d|[2-9]\d\d)/', $otherName) && $otherName !== $serverName) {
                $version = OkapiService::fetchServerVersion($serverList[$otherName]);
                if ($version !== null && version_compare($version, '1.47') >= 0) {
                    return $otherName;
                }
            }
        }

        return null;
    };

    $insideBitbucket = ($_ENV['BITBUCKET_BUILD_NUMBER'] ?? 0);
    $okapiConfig = $config->runtimeOptions->plugins->Okapi;

    // find a proper 147 server in the config
    $serverName = $insideBitbucket ? null : $find147VersionPlus(
        $okapiConfig?->server?->toArray() ?? [],
            $okapiConfig?->serverUsed ?? ''
    );

    // not found, the migration has to be aborted !!
    if ($serverName === null && ! $insideBitbucket) {
        // UGLY SPECIAL FOR API-TESTS:
        // When updating the database, updating the test-configs is done AFTER the db-updates have run
        // it can not be done before, as plugin-configs do not exist then
        // so we have to read the test-config updates and manipulate them
        // these are stored in a global define for that purpose
        if (defined('APPLICATION_TEST_CONFIGS') && Zend_Registry::isRegistered('test_configs')) {
            $testConfigs = Zend_Registry::get('test_configs');
            $serverList = json_decode($testConfigs['runtimeOptions.plugins.Okapi.server'], true) ?? [];
            $serverUsed = $testConfigs['runtimeOptions.plugins.Okapi.serverUsed'] ?? '';

            $serverName = $find147VersionPlus($serverList, $serverUsed);

            if ($serverName === null) {
                // to ensure the file is not marked as processed
                $this->doNotSavePhpForDebugging = false;

                error_log(__FILE__ . ': searching for Okapi 1.47 in the test-configuration FAILED - stop migration script.' .
                    ' You need to add a proper OKAPI 1.47 container and config to your application.');

                return;
            } else {
                // crucial: we must manipulate the registered configs so the tests can run with 1.47 ...
                $testConfigs['runtimeOptions.plugins.Okapi.serverUsed'] = $serverName;
                Zend_Registry::set('test_configs', $testConfigs);
            }
        } else {
            throw new ZfExtended_Exception(
                __FILE__ . ': searching for Okapi 1.47 in config FAILED - stop migration script'
            );
        }
    }
    // update okapi's serverUsed for the general config & all customer configs, adjust the 147-snapshot task-configs

    if ($serverName !== null) {
        $db->query(
            'UPDATE `Zf_configuration` SET `value` = ? WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed"',
            $serverName
        );
        $db->query(
            'UPDATE `LEK_customer_config` SET `value` = ? WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed"',
            $serverName
        );
        $db->query(
            'UPDATE `LEK_task_config` SET `value` = ? WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed" AND `value` LIKE "%147-snapshot"',
            $serverName
        );
    }
}


$db->query(
    "DELETE FROM `Zf_configuration` WHERE `name` IN (" .
    "'runtimeOptions.plugins.Okapi.import.okapiBconfDefaultName'," .
    "'runtimeOptions.plugins.Okapi.export.okapiBconfDefaultName'" .
    ")"
);

// update description of serverUsed in config

$okapiInfo147 = 'No version below 1.47 can be used with t5 file-format-settings, only "bconf-in-zip" works with older versions';
$descr = $db->fetchOne(
    'SELECT `description` FROM `Zf_configuration` WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed"'
);

if (! str_contains($descr, $okapiInfo147)) {
    $db->query(
        'UPDATE `Zf_configuration` SET `description` = :descr WHERE `name` = "runtimeOptions.plugins.Okapi.serverUsed"',
        [
            'descr' => trim($descr, '.') . '. ' . $okapiInfo147,
        ]
    );
}

// update special chars list in config

$NEW_SPECIAL_CHARS_JSON = '[
{
    "unicode": "U+00AD",
    "visualized": "SHY"
},
{
    "unicode": "U+1680",
    "visualized": "OGSP"
},
{
    "unicode": "U+180E",
    "visualized": "MVS"
},
{
    "unicode": "U+2000",
    "visualized": "NQSP"
},
{
    "unicode": "U+2001",
    "visualized": "MQSP"
},
{
    "unicode": "U+2002",
    "visualized": "ENSP"
},
{
    "unicode": "U+2003",
    "visualized": "EMSP"
},
{
    "unicode": "U+2004",
    "visualized": "3/MSP"
},
{
    "unicode": "U+2005",
    "visualized": "4/MSP"
},
{
    "unicode": "U+2006",
    "visualized": "6/MSP"
},
{
    "unicode": "U+2007",
    "visualized": "FSP"
},
{
    "unicode": "U+2008",
    "visualized": "PSP"
},
{
    "unicode": "U+2009",
    "visualized": "THSP"
},
{
    "unicode": "U+200A",
    "visualized": "HSP"
},
{
    "unicode": "U+200B",
    "visualized": "ZWSP"
},
{
    "unicode": "U+200C",
    "visualized": "ZWNJ"
},
{
    "unicode": "U+2011",
    "visualized": "NBH"
},
{
    "unicode": "U+2028",
    "visualized": "LS"
},
{
    "unicode": "U+2029",
    "visualized": "PS"
},
{
    "unicode": "U+202F",
    "visualized": "NNBSP"
},
{
    "unicode": "U+205F",
    "visualized": "MMSP"
},
{
    "unicode": "U+3000",
    "visualized": "IDSP"
},
{
    "unicode": "U+FEFF",
    "visualized": "ZWNBSP"
}
]';

$specialCharacters = json_decode($config->runtimeOptions->editor->segments->editorSpecialCharacters, true);
if (! isset($specialCharacters['all'])) {
    $specialCharacters['all'] = [];
}

$charsToAdd = [];
$newCharsList = json_decode($NEW_SPECIAL_CHARS_JSON, true);
foreach ($newCharsList as $newChar) {
    $charFound = false;
    foreach ($specialCharacters['all'] as $specialChar) {
        if ($newChar['unicode'] === $specialChar['unicode']) {
            $charFound = true;

            break;
        }
    }
    if (! $charFound) {
        $charsToAdd[] = $newChar;
    }
}
if (! empty($charsToAdd)) {
    $specialCharacters['all'] = array_merge($specialCharacters['all'], $charsToAdd);
    $specialCharactersJson = json_encode(
        $specialCharacters,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $db->query(
        'UPDATE `Zf_configuration` SET `value` = :value WHERE `name` = "runtimeOptions.editor.segments.editorSpecialCharacters"',
        [
            'value' => $specialCharactersJson,
        ]
    );
}

if ($bconfUpgradeNeeded) {
    // update Bconfs

    $bconf = new BconfEntity();
    $bconfAll = $bconf->loadAll();

    foreach ($bconfAll as $bconfData) {
        try {
            $bconf = new BconfEntity();
            $bconf->load($bconfData['id']);
            $bconfDir = $bconf->getDataDirectory();

            UpgraderTo147::upgradePipeline($bconfDir);
            UpgraderTo147::upgradeFprms($bconfDir, $bconf->getId(), $bconf->getName());

            $extensionMapping = $bconf->getExtensionMapping();
            $extensionMapping->rescanFilters();
            $bconf->repackIfOutdated(true);
        } catch (Exception $e) {
            $msg = 'ERROR rescanning filters for bconf ' . $bconf->getId() . ', "' . $bconf->getName(
            ) . '": ' . $e->getMessage();
            error_log($msg);
        }
    }

    $db->query('UPDATE LEK_okapi_bconf SET versionIdx=' . editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX);
}
