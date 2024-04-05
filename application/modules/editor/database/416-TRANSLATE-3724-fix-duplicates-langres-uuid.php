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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 *
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc...
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '416-TRANSLATE-3724-fix-duplicates-langres-uuid.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

// Get db adapter
$db = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class)->db->getAdapter();

// Get [langResUuid => ids] pairs where ids are comma-separated ids of language resources having same langResUuid
$duplicateA = $db->query("
    SELECT `langResUuid`, GROUP_CONCAT(`id`) 
    FROM `LEK_languageresources` 
    GROUP BY `langResUuid`
    HAVING COUNT(`id`) > 1
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Foreach duplicated uuid
foreach ($duplicateA as $langResUuid_old => $ids) {

    // Foreach language resource record id
    foreach (explode(',', $ids) as $id) {

        // Create new uuid
        $langResUuid_new = ZfExtended_Utils::uuid();

        // Get tasks where current language resource is used
        $taskGuidA = $db->query("
            SELECT `taskGuid` 
            FROM `LEK_languageresources_taskassoc` 
            WHERE `languageResourceId` = ?"
        , $id)->fetchAll(PDO::FETCH_COLUMN);

        // Foreach task where current language resource is used
        foreach ($taskGuidA as $taskGuid) {

            // Update value of preTransLangResUuid in segments meta
            $db->query("
                UPDATE `LEK_segments_meta`
                SET `preTransLangResUuid` = ?
                WHERE `taskGuid` = ? AND `preTransLangResUuid` = ? 
            ", [$langResUuid_new, $taskGuid, $langResUuid_old]);
        }

        // Update within language resource record
        $db->query("UPDATE `LEK_languageresources` SET `langResUuid` = ? WHERE `id` = ?", [$langResUuid_new, $id]);
    }
}

// Add unique index on langResUuid-column
$db->query('ALTER TABLE `LEK_languageresources` ADD UNIQUE INDEX `langResUuid` (`langResUuid`)');