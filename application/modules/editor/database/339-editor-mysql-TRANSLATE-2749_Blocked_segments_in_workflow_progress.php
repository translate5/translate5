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
 * Update the segmentCount and the segmentFinishCount for all existing tasks.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '339-editor-mysql-TRANSLATE-2749_Blocked_segments_in_workflow_progress.php';

//since on updating the segment finish count, internal workflow stuff is triggered, the system user will be loaded,
// to enalbe that we let assume the script that we are in a worker:
if (! defined('ZFEXTENDED_IS_WORKER_THREAD')) {
    define('ZFEXTENDED_IS_WORKER_THREAD', true);
}

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

//dummy object just to initialize the acl constants
$acl = ZfExtended_Acl::getInstance(true);

if (! defined('ACL_ROLE_PM')) {
    define('ACL_ROLE_PM', 'pm');
}

$db       = Zend_Db_Table::getDefaultAdapter();
$task     = ZfExtended_Factory::get(editor_Models_Task::class);
$progress = ZfExtended_Factory::get(editor_Models_TaskProgress::class);
$segment  = ZfExtended_Factory::get(editor_Models_Segment::class);

/* @var $segment editor_Models_Segment */
$s = $task->db->select()
    ->where('state = ?', editor_Workflow_Default::STATE_OPEN)
    ->where('segmentCount != segmentFinishCount')
    ->where('segmentFinishCount>0')
    ->where('workflowStepName NOT IN(?)', [editor_Workflow_Default::STEP_NO_WORKFLOW, editor_Workflow_Default::STEP_WORKFLOW_ENDED]);

$allTasks = $task->db->fetchAll($s)->toArray();
//update task segmentCount and segmentFinishCount for all available tasks
foreach ($allTasks as $t) {
    /* @var $t editor_Models_Task */
    $task->init($t);

    error_log("Before: Task :[" . $task->getTaskGuid() . '] segmentFinishCount:' . $task->getSegmentFinishCount());
    //update the segment finish count
    $progress->updateSegmentFinishCount($task);

    $task->load($task->getId());
    error_log("After: Task :[" . $task->getTaskGuid() . '] segmentFinishCount:' . $task->getSegmentFinishCount());
    error_log(" ########################################### ");
}
