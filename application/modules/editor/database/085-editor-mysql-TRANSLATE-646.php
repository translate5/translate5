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
  Changes the collation for segment text data for issue: 
    BEOSPHERE-64: Error if Reference file has same name as original file
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
$res = $db->query('show tables from `'.$dbname.'` like "%LEK_segment_view_%";');
$tables = $res->fetchAll(Zend_Db::FETCH_NUM);
if(empty($tables)) {
    return;
}
foreach($tables as $table){
    $tableName = $table[0];
    $table = new Zend_Db_Table($tableName);
    
    $meta = $table->info($table::METADATA);
    $addColTpl = array(); 
    foreach($meta as $name => $metaData) {
        if(!preg_match('/^(source|target|relais)[0-9]*(Edit)?$/', $name) || strtolower($metaData['DATA_TYPE']) !== 'longtext') {
            continue; //no segment content column, so roll over
        }
        
        $sql = 'MODIFY `'.$name.'` '.strtoupper($metaData['DATA_TYPE']);
        if(empty($metaData['NULLABLE'])) {
            $sql .= ' NOT NULL';
        }
        
        $sql .= ' COLLATE utf8_bin';
        
        $addColTpl[] = $sql;
    }
    if(empty($addColTpl)) {
        echo "WARNING: No column found to change collation in ".$tableName."! This should not be!<br />\n";
        continue; //should not be, but we roll over this table then
    }
    $sql = sprintf('ALTER TABLE `%s`.`%s` ', $dbname, $tableName).join(', ', $addColTpl).';';
    $db->query($sql);
    echo "Changed Segment content field collations of table ".$tableName." <br />\n";
}
