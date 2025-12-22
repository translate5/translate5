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

set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renaming etc...
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '416-TRANSLATE-2753-add-segmentEditableCount.php';

/** @var ZfExtended_Models_Installer_DbUpdater $this */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) { // @phpstan-ignore-line
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$tuaM = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
$taskM = ZfExtended_Factory::get(editor_Models_Task::class);
$taskProgressM = ZfExtended_Factory::get(editor_Models_TaskProgress::class);

/** @var Zend_Db_Adapter_Pdo_Mysql $db */
$db = $tuaM->db->getAdapter();

// this is workaround just to be able to use the system user when we check the workflow bellow.
defined('ZFEXTENDED_IS_WORKER_THREAD') || define('ZFEXTENDED_IS_WORKER_THREAD', true);

// Task states to skip progress calculation for
$skipStates = [
    editor_Models_Task::STATE_ERROR,
    editor_Models_Task::STATE_PROJECT,
    editor_Models_Task::STATE_IMPORT,
];

// Foreach task - recalculate values for segmentFinishCount and
// segmentFinishCount fields for the task itself and it's associated users
foreach ($taskM->loadAll() as $task) {
    if (! in_array($task['state'], $skipStates)
        && $task['workflowStepName'] !== editor_Workflow_Default::STEP_WORKFLOW_ENDED) {
        $taskM->load($task['id']);
        $taskProgressM->refreshProgress($taskM);
    }
}
