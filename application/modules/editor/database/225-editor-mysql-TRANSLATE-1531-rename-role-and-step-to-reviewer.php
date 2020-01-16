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
 * Find and replace all occurrences of lector/lectoring with reviewer/reviewing
 * in all tables where those values can be found.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '225-editor-mysql-TRANSLATE-1531-rename-role-and-step-to-reviewer.php'; 

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
$conf = $db->getConfig();
$dbname = $conf['dbname'];
$res = $db->query('show tables from `'.$dbname.'` like "%LEK_segment_view_%";');
$tables = $res->fetchAll(Zend_Db::FETCH_NUM);
if(!empty($tables)) {
    foreach($tables as $table){
        $table = $table[0];
        $sql='UPDATE %s SET workflowStep="reviewing" WHERE workflowStep="lectoring"';
        $db->query(sprintf($sql,$table));
        echo "workflowStep renamed (lectoring->reviewing) for the table ".$table." <br />\n";
    }
}

//rename the fixed values in the tables
$sql="UPDATE LEK_segment_history SET workflowStep='reviewing'
    WHERE workflowStep='lectoring';
    
    UPDATE LEK_segments SET workflowStep='reviewing'
    WHERE workflowStep='lectoring';
    
    UPDATE LEK_task SET workflowStepName='reviewing'
    WHERE workflowStepName='lectoring';
    
    UPDATE LEK_workflow_action SET inStep='reviewing'
    WHERE inStep='lectoring';
    
    UPDATE LEK_taskUserAssoc SET role='reviewer'
    WHERE role='lector';
    
    UPDATE LEK_taskUserTracking SET role='reviewer'
    WHERE role='lector';
    
    UPDATE LEK_workflow_action SET byRole='reviewer'
    WHERE byRole='lector';";
$db->query($sql);

//search the parametars field in the lek_workflow_action table and rename the matches
$sql="SELECT id,parameters from LEK_workflow_action
      WHERE parameters REGEXP 'lector';";
$res = $db->query($sql);
$lectorMatch = $res->fetchAll();
if(!empty($lectorMatch)){
    foreach ($lectorMatch as $match) {
        //replace lector with reviewer and save back the value to the db
        $value = str_replace("lector","reviewer",$match['parameters']);
        $sql="UPDATE LEK_workflow_action SET parameters=? WHERE id=?";
        $db->query($sql,[$value,$match['id']]);
        
    }
}

$task=ZfExtended_Factory::get('editor_Models_Task');
/* @var $task editor_Models_Task */
$allTasks=$task->loadAll();
//update task segmentCount and segmentFinishCount for all available tasks
foreach ($allTasks as $t){
    /* @var $t editor_Models_Task */
    $task->init($t);
    //update the segmentCount if it is not set
    if($task->getSegmentCount()==null || $task->getSegmentCount()<1){
        $segmentCount=$task->getTotalSegmentsCount($task->getTaskGuid());
        $sql="UPDATE LEK_task SET segmentCount=? WHERE taskGuid=?";
        $db->query($sql,[$segmentCount,$task->getTaskGuid()]);
    }
    //update the segment finish count
    $task->updateSegmentFinishCount($task);
}


