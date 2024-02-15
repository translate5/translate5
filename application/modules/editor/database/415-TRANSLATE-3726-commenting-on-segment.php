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

/**
  README: 415-TRANSLATE-3726-commenting-on-segment
 * Fix segment finish count for tasks
 */
set_time_limit(0);

// INFO: This migration script is disabled by default, because it operates over all tasks. In case someone needs
// this fix, we can uncomment it and run it manually.
return;
defined('ZFEXTENDED_IS_WORKER_THREAD') || define('ZFEXTENDED_IS_WORKER_THREAD', true);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '415-TRANSLATE-3726-commenting-on-segment.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$task=ZfExtended_Factory::get(editor_Models_Task::class);
$db = $task->db;
$s = $db->select()->from('task', ['*'])->where('state NOT IN(?)', [
    editor_Models_Task::STATE_ERROR,
    editor_Models_Task::STATE_END,
    editor_Models_Task::STATE_IMPORT,
    editor_Models_Task::STATE_PROJECT
]);
$allTasks=$task->loadAll();
foreach ($allTasks as $t){
    $task->init($t);
    // update the segment finish count
    $task->updateSegmentFinishCount();
}