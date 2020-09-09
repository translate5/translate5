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
 * Conver the database,table and table columns charset and collation: 
 * utf8            -> utf8mb4
 * utf8_general_ci -> utf8mb4_unicode_ci
 */
set_time_limit(0);


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
$conf = $db->getConfig();

$dbname = $conf['dbname'];

$res = $db->query('ALTER DATABASE `'.$dbname.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
$res->execute();

//generate the update characterset/COLLATION query for all tables
$tableSql = "SELECT CONCAT( ".
    " 'ALTER TABLE ',  table_name, ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;  ', ".
    " 'ALTER TABLE ',  table_name, ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;  ') as r ".
    " FROM information_schema.TABLES AS T, information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` AS C ".
    " WHERE C.collation_name = T.table_collation ".
    " AND T.table_schema = ? ".
    " AND (C.CHARACTER_SET_NAME != 'utf8mb4' OR C.COLLATION_NAME not like 'utf8mb4%'); ";

//query to collect all utf8 bin colulmns. Those columns should be converted back to utf8mb4_bin
//after all tables are converted to utf8mb4 charset
$utf8BinAlter = "SELECT CONCAT('ALTER TABLE ', 
                t1.table_name, 
                ' MODIFY ', 
                t1.column_name, 
                ' ', 
                if(lower(t1.data_type) = 'varchar',concat(t1.data_type,'(' , CHARACTER_MAXIMUM_LENGTH, ')'),t1.data_type),
                ' CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;'
        )as r
FROM 
    information_schema.columns t1
WHERE 
    t1.TABLE_SCHEMA like ? AND
    t1.COLLATION_NAME IS NOT NULL AND
    t1.COLLATION_NAME IN ('utf8_bin');";

//generate the alter query results
$resUtf8Alter = $db->query($utf8BinAlter,[$dbname]);
$queryesUtf8Alter = $resUtf8Alter->fetchAll();

$res = $db->query($tableSql,[$dbname]);
$queryes = $res->fetchAll();

//merge the query results to single query
$runAlterQuery = function($q,$r=[]){
    array_unshift($r,'SET FOREIGN_KEY_CHECKS=0;');
    array_walk($q, function ($item)  use (&$r){
        $r[] = $item['r'] ?? [];
    });
    $r[]='SET FOREIGN_KEY_CHECKS=1;';
    
    $r = implode(PHP_EOL, $r);
    if(!empty($r)){
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query($r);
    }
};

//prerequisites
$result[] = "
ALTER TABLE `LEK_term_proposal`
CHANGE COLUMN `term` `term` TEXT CHARACTER SET 'utf8mb4' NOT NULL DEFAULT '' COMMENT 'the proposed term' ;
    
ALTER TABLE `LEK_terms`
CHANGE COLUMN `term` `term`  TEXT CHARACTER SET 'utf8mb4' NOT NULL DEFAULT '' ;
    
ALTER TABLE `LEK_plugin_segmentstatistic_terms`
CHANGE COLUMN `term` `term`  TEXT CHARACTER SET 'utf8mb4' NOT NULL DEFAULT '' ;
    
ALTER TABLE `LEK_term_history`
CHANGE COLUMN `term` `term`  TEXT CHARACTER SET 'utf8mb4' NOT NULL DEFAULT '' ;";

$runAlterQuery($queryes,$result);
$runAlterQuery($queryesUtf8Alter);

$db->query("SET NAMES utf8mb4;");

