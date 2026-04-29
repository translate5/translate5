<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

set_time_limit(0);

$SCRIPT_IDENTIFIER = '510-TRANSLATE-5476-fix-task-config-for-multitarget-tasks.php';

/* @var ZfExtended_Models_Installer_DbUpdater $this */

// uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

// no need to run this in database recreations
if ($this->isTestOrInstallEnvironment()) { // @phpstan-ignore-line
    return;
}

// bug was released on that day
$bugDate = '2021-08-04 00:00:00';

$db = Zend_Db_Table::getDefaultAdapter();
$projectIds = $db->fetchCol(
    "SELECT id FROM `LEK_task` WHERE taskType = '" . editor_Task_Type_Project::ID .
    "' AND created > '" . $bugDate . "'"
);
$warnings = [];

// we need to process all projects
foreach ($projectIds as $projectId) {
    $projectTasks = $db->fetchAll(
        "SELECT id, taskGuid FROM `LEK_task` WHERE taskType = '" . editor_Task_Type_ProjectTask::ID .
        "' AND projectId = " . $projectId . " ORDER BY id DESC"
    );

    // no need to process single-task projects
    if (count($projectTasks) < 2) {
        continue;
    }

    // if we have more than one project, the last task (which should be the first fetched) should hold the config
    // we implement it in a way, that it will work, even if this is different
    $sourceGuid = null;
    $taskGuids = [];
    $configsByGuid = [];

    foreach ($projectTasks as $projectTask) {
        $taskGuids[] = $projectTask['taskGuid'];
        $configsByGuid[$projectTask['taskGuid']] = [];
    }

    // all configs for the tasks-guids in question
    $configs = $db->fetchAll(
        "SELECT taskGuid, name, value FROM `LEK_task_config` WHERE `taskGuid` IN ('" .
        implode("','", $taskGuids) . "')"
    );

    // split the configs by task-guid
    foreach ($configs as $config) {
        $configsByGuid[$config['taskGuid']][$config['name']] = $config['value'];
    }

    // find the one with the most configs
    $last = 0;
    foreach ($taskGuids as $guid) {
        $count = count($configsByGuid[$guid]);
        if ($count > $last) {
            $last = $count;
            $sourceGuid = $guid;
        }
    }

    // if not found, add warning. We cannot evaluate what configs to set for such projects - which should not exist
    if ($sourceGuid === null) {
        $warnings[] = 'Could not evaluate task-configs of project-tasks for project with ID ' . $projectId;

        continue;
    }

    // now, prepare the inserts
    $inserts = [];
    $sourceConfigs = $configsByGuid[$sourceGuid];

    foreach ($taskGuids as $guid) {
        if ($guid !== $sourceGuid) {
            foreach ($sourceConfigs as $name => $value) {
                // only ad a insert for those configs that do not already have an entry
                if (! array_key_exists($name, $configsByGuid[$guid])) {
                    $inserts[] = '(' . $db->quote($guid) . ',' . $db->quote($name) . ',' . $db->quote($value) . ')';
                }
            }
        }
    }

    // insert all new values
    if (count($inserts) > 0) {
        $db->query(
            'INSERT INTO `LEK_task_config` (`taskGuid`, `name`, `value`) VALUES ' .
            implode(',', $inserts) . ' ON DUPLICATE KEY UPDATE `value`=`value`'
        );

        // sleep 50 milliseconds to avoid deadlocks and distribute db-strain a bit
        usleep(5000);
    }
}

foreach ($warnings as $warning) {
    echo 'WARNING: ' . $warning . "\n";
}
