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
    - fills up isTargetRepeated and isSourceRepeated fields for all tasks
    - the script is designed that it can be called multiple times, until all tasks are converted
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '288-TRANSLATE-2315-repeated-segments-migration.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$sql = 'SELECT `taskGuid` FROM `LEK_task` WHERE `taskGuid` NOT IN (
    SELECT `taskGuid` FROM `LEK_task_migration` WHERE `filename` = ?
) AND `state` != "import"';

$res = $db->query($sql, $SCRIPT_IDENTIFIER);
$tasks = $res->fetchAll(Zend_Db::FETCH_COLUMN);

// set this flag to prevent the script to be marked as done in the case of an error
$this->doNotSavePhpForDebugging = false;

$taskCount = count($tasks);
error_log($SCRIPT_IDENTIFIER.' - tasks to be converted: '.$taskCount."\n");

//calculate isRepeated flags for each task (and view)
$i = 1;
foreach ($tasks as $taskGuid) {
    $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($taskGuid);
    if($sfm->getView()->exists()) {
        $view = $sfm->getView()->getName();
        try {
            $db->query('ALTER TABLE '.$view.' ADD isRepeated TINYINT DEFAULT 0 NOT NULL');
        }
        catch(Throwable $e) {
            error_log('On adding the isRepeated column to task view '.$view.' there was an exception '.$e);
        }
    }
    $segment = ZfExtended_Factory::get('editor_Models_Segment');
    /* @var $segment editor_Models_Segment */
    $segment->syncRepetitions($taskGuid, false);
    
    error_log('Task '.$taskGuid.' converted ('.($i++).'/'.$taskCount.')');
    $db->query('INSERT INTO LEK_task_migration (`taskGuid`, `filename`) VALUES (?,?)', [$taskGuid, $SCRIPT_IDENTIFIER]);
}

//if the above loop was successful, we can mark the script as done:
$this->doNotSavePhpForDebugging = true;
