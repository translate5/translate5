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
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$sql = 'SELECT COUNT(*) AS `num_rows` FROM `LEK_workflow_action` 
        WHERE LEK_workflow_action.trigger = "doCronDaily" 
        AND (LEK_workflow_action.action = "notifyOverdueDeadline" OR LEK_workflow_action.action = "notifyDeadlineApproaching") ';

$db = Zend_Db_Table::getDefaultAdapter();
$res = $db->query($sql);
$result = $res->fetchAll()[0] ?? [];


if(!empty($result) && $result['num_rows'] > 0){
    //replace daily with periodical
    $sql="UPDATE LEK_workflow_action SET LEK_workflow_action.trigger='doCronPeriodical' ".
         "WHERE LEK_workflow_action.trigger='doCronDaily' ".
         "AND (LEK_workflow_action.action='notifyOverdueDeadline' OR LEK_workflow_action.action='notifyDeadlineApproaching') ";
    
}else{
    //insert default periodical trigger for notifyOverdueDeadline and notifyDeadlineApproaching
    $sql="INSERT INTO `LEK_workflow_action` (`workflow`, `trigger`, `actionClass`, `action`)
          VALUES ('default', 'doCronPeriodical', 'editor_Workflow_Notification', 'notifyOverdueDeadline'),
          ('default', 'doCronPeriodical', 'editor_Workflow_Notification', 'notifyDeadlineApproaching');";
}
$db->query($sql);
