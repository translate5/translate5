<?php
/*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
--
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

set_time_limit(0);

/**
 * Script to clean up test data accidentally pushed to the world
 */

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc...
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '369-TRANSLATE-2932-Okapi-Filters-cleanup.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

if(!class_exists('editor_Plugins_Okapi_Bconf_Entity')) {
    return;
}

$deletaAll = false;
$existingIds = [];
$db = Zend_Db_Table::getDefaultAdapter();
try {
    //get all available IDs
    $existingIds = $db->query('SELECT `id` FROM `LEK_okapi_bconf`')
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Zend_Db_Statement_Exception $e) {
    if(str_contains($e->getMessage(), 'Base table or view not found')) {
        // if table is not there, all files can be deleted
        $deletaAll = true;
    }
    else {
        throw $e;
    }
}
try {
    $rootDir = editor_Plugins_Okapi_Bconf_Entity::getUserDataDir();
    $directories = scandir($rootDir);
    foreach ($directories as $dir) {
        if(in_array($dir, ['.', '..'])) {
            continue;
        }
        if(!in_array($dir, $existingIds) || $deletaAll || $dir == 'tmp') {
            ZfExtended_Utils::recursiveDelete($rootDir.DIRECTORY_SEPARATOR.$dir);
            error_log('DELETED editorOkapiDir/'.$dir);
        }

    }
} catch (editor_Plugins_Okapi_Exception $e) {
    //do nothing here
}

//remove the app-tm-erp dir if its empty, another wrong deploy stuff
@rmdir('app-tm-erp');