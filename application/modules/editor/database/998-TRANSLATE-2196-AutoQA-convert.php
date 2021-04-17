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
 * Simple script to create quality entries from the segments qmId field
 */
set_time_limit(0);

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
$stmt = $db->query('SELECT `taskGuid` FROM `LEK_task`');
$tasks = $stmt->fetchAll();

if(empty($tasks)){
    //nothing to migrate
    return;
}

foreach ($tasks as $taskRow){
    
    $taskGuid = $taskRow['taskGuid'];
    $stmt = $db->query("SELECT `id`, `qmId` FROM `LEK_segments` WHERE `taskGuid` = '".$taskGuid."' AND `qmId` IS NOT NULL AND `qmId` != '' AND `qmId` != ';' AND `qmId` != ';;'");
    
    foreach($stmt->fetchAll() AS $row){
        
        $inserts = [];
        $segmentId = $row['id'];
        $qmIds = explode(';', trim($row['qmId'], ';'));
        // every qm becomes a row in the new qualities model
        foreach($qmIds as $qmId){
            if($qmId != ''){                
                $category = 'qm_'.$qmId;
                $inserts[] = "('".$taskGuid."','".$segmentId."','','qm','".$category."','".$qmId."')";
            }
        }
        
        if(count($inserts) > 0){
            $db->query(
                'INSERT INTO `LEK_segment_quality` (`taskGuid`, `segmentId`, `field`, `type`, `category`, `categoryIndex`) VALUES '
                .implode(', ', $inserts));
        }
    }  
}

    