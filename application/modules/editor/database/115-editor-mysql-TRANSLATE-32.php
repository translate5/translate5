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
    changes the data structure of the internal tags of translate5
    TRANSLATE-818: internal tag replace id usage with data-originalid and data-filename
    
    Single task processing, fill the $taskGuid array below with the desired taskGuids!
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
$this->doNotSavePhpForDebugging = false;


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
$views = $res->fetchAll(Zend_Db::FETCH_COLUMN);

$res = $db->query('select taskGuid from LEK_task;');
$tasks = $res->fetchAll(Zend_Db::FETCH_COLUMN);
//$tasks = ['{a10a2af3-c69f-490e-885a-770225090766}'];
$existingViews = [];
foreach($tasks as $task) {
    $view = 'LEK_segment_view_'.md5($task);
    if(in_array($view, $views)){
        $existingViews[$task] = $view;
    }
}

//main replacer function to change the internal tag content
$replacer = function($segmentContent){
    return strip_tags(preg_replace('#<span[^>]*>[^<]*<\/span>#','',$segmentContent));
};

foreach ($tasks as $task) {
    
    $res = $db->query('SELECT id, taskGuid, name, segmentId, original, edited 
              FROM LEK_segment_data 
              WHERE taskGuid = \''.$task.'\'');

    $count = $res->rowCount();
    error_log('Task '.$task.': '.$count.' datasets to be changed by '.basename(__FILE__));

    //prepare update statement
    $stmt = $db->prepare('UPDATE LEK_segment_data set originalToSort = :originalToSort, editedToSort = :editedToSort where id = :id');


    $done = 0;
    $donePercent = 0;
    $percentShown = [];
    while($row = $res->fetchObject()) {
        if(is_null($row->edited)) {
            $editedNewValue = $row->edited;
        }
        else {
            $editedNewValue= $replacer($row->edited);
        }

        $originalNewValue = $replacer($row->original);

        
        //when there is a view to the given task update the corresponding row too
        if(!empty($existingViews[$row->taskGuid])) {
            $sql = 'UPDATE `'.$existingViews[$row->taskGuid].'` SET ';
            $sql .= $row->name.'ToSort = ?';
            $params = [$originalNewValue];
            if(!is_null($row->edited)) {
                $sql .= ', '.$row->name.'EditToSort = ?';
                $params[] = $editedNewValue;
            }
            $sql .= ' WHERE id = ?';
            $params[] = $row->segmentId;

            $db->query($sql, $params);
        }

        //first update the view, then the data table
        // if the script dies, this segment is recalculated again, so no old data remain 
        $stmt->execute([
                ':originalToSort' => $originalNewValue,
                ':editedToSort' => $editedNewValue,
                ':id' => $row->id,
        ]);

        //print a nice state
        $percent = 10 * floor(++$done / $count * 10);
        if($percent % 10 == 0 && empty($percentShown[$percent])) {
            $percentShown[$percent] = true;
            error_log('Task '.$task.': '.$percent.'% done ('.$done.' datasets)'); 
        }
    }

    error_log('Task '.$task.': '.$count." toSort fields updated with no tags in segment data.\n");

    usleep(10);
}
