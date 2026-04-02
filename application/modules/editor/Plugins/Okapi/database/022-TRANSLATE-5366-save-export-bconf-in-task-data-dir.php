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

use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Worker\OkapiWorkerHelper;

/**
 * Script to copy the export-bconfs to the task's OKAPI-DATA folder
 */

set_time_limit(0);

/** @var ZfExtended_Models_Installer_DbUpdater $this */

$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7 || ! isset($config)) { // @phpstan-ignore-line
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$config = Zend_Registry::get('config');
$tasksDataPath = realpath($config->runtimeOptions->dir->taskData) . DIRECTORY_SEPARATOR;
$okapiDataPath = realpath($config->runtimeOptions->plugins->Okapi->dataDir) . DIRECTORY_SEPARATOR;

$limit = 1000;
$offset = 0;
$db = Zend_Db_Table::getDefaultAdapter();
$query = $db->query("SELECT * FROM `LEK_okapi_taskBconfAssoc` LIMIT $limit");
$rows = $query->fetchAll();
$messages = '';

while (count($rows) > 0) {
    foreach ($rows as $row) {
        $bconfId = empty($row['bconfId']) ? -1 : (int) $row['bconfId'];
        // we process only tasks with a referenced BCONF (not in ZIP)
        if ($bconfId > 0) {
            $sourcePath = $okapiDataPath .
                $bconfId . DIRECTORY_SEPARATOR .
                'bconf-export-' . $bconfId . '.' . BconfEntity::EXTENSION;
            $targetPath = $tasksDataPath .
                trim($row['taskGuid'], '{}') . DIRECTORY_SEPARATOR .
                OkapiWorkerHelper::OKAPI_REL_DATA_DIR . DIRECTORY_SEPARATOR .
                OkapiWorkerHelper::EXPORT_BCONF_FILE;

            if (! file_exists($sourcePath)) {
                error_log('Problem: A BCONF refernced by an existing task was already removed: ' . $bconfId);
            } elseif (! file_exists($targetPath)) {
                copy($sourcePath, $targetPath);
                chmod($targetPath, 0777);
            }
        }
    }
    $offset += $limit;
    $query = $db->query("SELECT * FROM `LEK_okapi_taskBconfAssoc` LIMIT $limit OFFSET $offset");
    $rows = $query->fetchAll();
}
