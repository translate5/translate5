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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/***
 * Rename the table if exist. This is fix for the problem where the sql was added in editor database files
 * instead in matchanalysis database files
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '019-TRANSLATE-2855-pre-translate-pivot';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$db->query("UPDATE `Zf_configuration`
SET `name` = 'runtimeOptions.LanguageResources.Pretranslation.enableBatchQuery',
    `description` = 'Enables batch query requests for pretranslations only for the associated language resource that support batch query. Batch query is much faster for many language resources for imports and InstantTranslate'
WHERE (`name` = 'runtimeOptions.plugins.MatchAnalysis.enableBatchQuery');");

try {
    $db->query("ALTER TABLE `LEK_match_analysis_batchresults` RENAME TO  `LEK_languageresources_batchresults` ;");
} catch (Exception $exception) {
    // if this throws exception, the table is already renamed
}
