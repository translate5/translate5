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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
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


/* @var $this ZfExtended_Models_Installer_DbUpdater */


//USAGE WARNING: 
//add the taskGuids to be processed to this array!
$taskGuids = array();
//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;


//if nothing entered, do nothing
if(empty($taskGuids)){
    return;
}

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

$existingViews = [];
foreach($tasks as $task) {
    $view = 'LEK_segment_view_'.md5($task);
    if(in_array($view, $views)){
        $existingViews[$task] = $view;
    }
}

$res = $db->query('SELECT id, taskGuid, name, segmentId, original, edited 
              FROM LEK_segment_data 
              WHERE taskGuid in ('.implode(',', $taskGuids).') and (edited like "%</span><span id=%" or original like "%</span><span id=%")');

$count = $res->rowCount();
error_log($count.' datasets to be changed by '.basename(__FILE__));

//prepare update statement
$stmt = $db->prepare('UPDATE LEK_segment_data set original = :original, edited = :edited where id = :id');


//main replacer function to change the internal tag content
$replacer = function($subject){
    return preg_replace_callback('#</span><span id="([^-"]*)-([^"]*)"#', function($matches){
        $id = $matches[1];
        //this is the same logic as used in JS to get the hash:
        $hash = explode('-', $matches[2]);
        $hash = array_pop($hash);
        return '</span><span data-originalid="'.$id.'" data-filename="'.$hash.'"';
    }, $subject);
};

$done = 0;
$donePercent = 0;
$percentShown = [];
while($row = $res->fetchObject()) {
    if(is_null($row->edited)) {
        $edited = $row->edited;
    }
    else {
        $edited = $replacer($row->edited);
    }
    
    $original = $replacer($row->original);
    
    //when there is a view to the given task update the corresponding row too
    if(!empty($existingViews[$row->taskGuid])) {
        $sql = 'UPDATE `'.$existingViews[$row->taskGuid].'` SET ';
        $sql .= $row->name.' = ?';
        $params = [$original];
        if(!is_null($row->edited)) {
            $sql .= ', '.$row->name.'Edit = ?';
            $params[] = $edited;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $row->segmentId;
        
        $db->query($sql, $params);
    }
    
    //first update the view, then the data table
    // if the script dies, this segment is recalculated again, so no old data remain 
    $stmt->execute([
        ':original' => $original,
        ':edited' => $edited,
        ':id' => $row->id,
    ]);
    
    //print a nice state
    $percent = 10 * floor(++$done / $count * 10);
    if($percent % 10 == 0 && empty($percentShown[$percent])) {
        $percentShown[$percent] = true;
        error_log($percent.'% done ('.$done.' datasets)'); 
    }
}

echo $count." internal tags converted in segment data.\n";


// Same loop for history data

$res = $db->query('SELECT id, edited 
              FROM LEK_segment_history_data 
              WHERE taskGuid in ('.implode(',', $taskGuids).') and edited like "%</span><span id=%"');

$stmt = $db->prepare('UPDATE LEK_segment_history_data set edited = :edited where id = :id');

$count = $res->rowCount();
error_log($count.' datasets to be changed in history data!');

$done = 0;
$donePercent = 0;
$percentShown = [];
while($row = $res->fetchObject()) {
    $edited = $replacer($row->edited);
    
    //first update the view, then the data table
    // if the script dies, this segment is recalculated again, so no old data remain 
    $stmt->execute([
        ':edited' => $edited,
        ':id' => $row->id,
    ]);
    
    //print a nice state
    $percent = 10 * floor(++$done / $count * 10);
    if($percent % 10 == 0 && empty($percentShown[$percent])) {
        $percentShown[$percent] = true;
        error_log($percent.'% done ('.$done.' history datasets)'); 
    }
}

echo $count." internal tags converted in history data.\n";
