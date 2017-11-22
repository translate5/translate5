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
  Fixes the &amp;#39; characters back to ' characters 
    TRANSLATE-984: The editor converts single quotes to the corresponding HTML entity
 */
set_time_limit(0);

// disabled this migration script, since it produced some DB errors in live usage. 
// Keeping it for reference, since the script should be run manually if needed!
return;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

//$this->doNotSavePhpForDebugging = false;

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$db->query('LOCK TABLES `LEK_segment_data` WRITE,  `LEK_segment_history_data` WRITE');
$db->query('UPDATE `LEK_segment_data` SET `edited` = replace(`edited`,  "&amp;#39;","\'")');
$db->query('UPDATE `LEK_segment_history_data` SET `edited` = replace(`edited`,  "&amp;#39;","\'")');
$db->query('UNLOCK TABLES');


$conf = $db->getConfig();
$dbname = $conf['dbname'];
$res = $db->query('show tables from `'.$dbname.'` like "%LEK_segment_view_%";');
$tables = $res->fetchAll(Zend_Db::FETCH_NUM);
if(empty($tables)) {
    return;
}
foreach($tables as $table){
    $tableName = $table[0];
    $db->query('LOCK TABLES `'.$tableName.'` WRITE');
    $db->query('UPDATE `'.$tableName.'` SET `targetEdit` = replace(`targetEdit`,  "&amp;#39;","\'")');
    $db->query('UNLOCK TABLES');
}
