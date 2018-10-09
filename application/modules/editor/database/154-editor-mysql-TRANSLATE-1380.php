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

/**
  README:
    - saves all skeleton files from DB to disk
    - the script is designed that it can be called multiple times, until all tasks are converted
    - a single task processing is also possible, fill the $tasks array below (line 66) with the desired taskGuids!
    - if the LEK_skeletonfiles table is empty at the end, it will be dropped
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;
define('SCRIPT_IDENTIFIER', '154-editor-mysql-TRANSLATE-1380.php'); //should be not __FILE__ in the case of wanted restarts / renamings etc

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$sql = 'SELECT `taskGuid` FROM `LEK_task` WHERE `taskGuid` NOT IN (SELECT `taskGuid` FROM `LEK_task_migration` WHERE `filename` = ?)';

//FIXME status import und error ausklammern! Oder gleich eine Fehlermeldung?

$res = $db->query($sql, SCRIPT_IDENTIFIER);

$tasks = $res->fetchAll(Zend_Db::FETCH_COLUMN);

/*
 * The tasks array can also be filled manually): 
 */
//$tasks = ['{a10a2af3-c69f-490e-885a-770225090766}'];

/*
 * If tasks are chosen manually, set doNotSavePhpForDebugging = false so that the script can be called multiple times! 
 */
//$this->doNotSavePhpForDebugging = false

$taskCount = count($tasks);
$tasksDone = 1;
error_log('Tasks to be converted: '.$taskCount."\n");


$stmt = $db->prepare('SELECT `LEK_skeletonfiles`.`file`, `LEK_skeletonfiles`.`fileId`, `LEK_skeletonfiles`.`fileName`
                       FROM `LEK_skeletonfiles`, `LEK_files`
                       WHERE `LEK_skeletonfiles`.`fileId` = `LEK_files`.`id` AND `LEK_files`.`taskGuid` = :taskGuid');

//converts skeleton files per task to disk
foreach ($tasks as $taskGuid) {
    $task = ZfExtended_Factory::get('editor_Models_Task');
    /* @var $task editor_Models_Task */
    $task->loadByTaskGuid($taskGuid);
    
    $stmt->execute([
        ':taskGuid' => $taskGuid,
    ]);
    $count = $stmt->rowCount();
    error_log('  Task '.$taskGuid.': '.$count.' skeleton files to be saved to disk by '.basename(__FILE__));

    while($row = $stmt->fetchObject()) {
        $skelFilePath = $task->getAbsoluteTaskDataPath().sprintf(editor_Models_File::SKELETON_PATH, $row->fileId);
        $skelDir = dirname($skelFilePath);
        if(!file_exists($skelDir)) {
            @mkdir($skelDir);
        }
        $size = file_put_contents($skelFilePath, $row->file);
        
        if($size && file_exists($skelFilePath) && md5($row->file) == md5_file($skelFilePath)) {
            //$db->query('DELETE FROM `LEK_skeletonfiles` WHERE `fileId` = ?', $row->fileId);
            error_log('  Task '.$taskGuid.': converted file '.$row->fileName); 
        }
        else {
            error_log('  Task '.$taskGuid.': file could not be saved completely to disk: '.$row->fileName); 
        }
    }

    error_log('Task '.($tasksDone++).' of '.$taskCount." done.\n");
    $res = $db->query('INSERT INTO LEK_task_migration (`taskGuid`, `filename`) VALUES (?,?)', [$taskGuid, SCRIPT_IDENTIFIER]);
}

$res = $db->query('SELECT COUNT(*) `cnt` FROM `LEK_skeletonfiles`');
if($res && ($row = $res->fetchObject()) && $row->cnt === "0") {
    //$db->query('DROP TABLE `LEK_skeletonfiles`;') && 
    error_log('Table LEK_skeletonfiles dropped!');
}
else {
    error_log('Could not drop table LEK_skeletonfiles since there are still some skeleton file entries which could not be converted Please check that!');
}
