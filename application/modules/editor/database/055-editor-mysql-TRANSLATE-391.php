<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT
 
  
  README: 
  This script converts the paths of referencefiles in the database as described in TRANSLATE-217.
  The script is to be used in commandline, and has to be called like that:
  
  /usr/bin/php 055-editor-mysql-TRANSLATE-391.php DBHOST DBNAME DBUSER DBPASSWD
  
  Parameters are all mandatory: 
  DBHOST     → the database host as usable for mysqli
  DBNAME     → the database name
  DBUSER     → the database user
  DBPASSWD   → the database password
  DBPORT     → optional, the database connection port, set to 0 to ignore
  DBSOCKET   → optional, the database socket
  
 */
set_time_limit(0);

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($argv) || $argc < 5 || $argc > 7) {
    die("please call the script with the following parameters: \n  /usr/bin/php 055-editor-mysql-TRANSLATE-391.php DBHOST DBNAME DBUSER DBPASSWD [DBPORT [DBSOCKET]]\n\n");
}
if(!empty($argv[1])) {
    $dbhost = $argv[1];
}
if(!empty($argv[2])) {
    $dbname = $argv[2];
}
if(!empty($argv[3])) {
    $dbuser = $argv[3];
}
if(!empty($argv[4])) {
    $dbpwd = $argv[4];
}
$dbport = ini_get("mysqli.default_port");
if(!empty($argv[5])) {
    $dbport = $argv[5];
}
$dbsocket = ini_get("mysqli.default_socket");
if(!empty($argv[6])) {
    $dbsocket = $argv[6];
}

/**
 * setup database connection
 * @var mysqli
 * 
 */
$db = @new mysqli($dbhost, $dbuser, $dbpwd, $dbname, $dbport, $dbsocket);
/* @var $db mysqli */
if ($db->connect_error) {
    die('Connect Error (' . $db->connect_errno . ') '. $db->connect_error."\n");
}
$res = $db->query("update LEK_segment_data set original = replace(original, '&gt;', '_____transGTersetzungTilde_______'),edited = replace(edited, '&gt;', '_____transGTersetzungTilde_______')");

$res = $db->query("update LEK_segment_data set original = replace(original, '&gt', '&gt;'),edited = replace(edited, '&gt', '&gt;')");

$res = $db->query("update LEK_segment_data set original = replace(original,  '_____transGTersetzungTilde_______','&gt;'),edited = replace(edited, '_____transGTersetzungTilde_______','&gt;')");


$res = $db->query("update LEK_segment_history_data set edited = replace(edited, '&gt;', '_____transGTersetzungTilde_______')");

$res = $db->query("update LEK_segment_history_data set edited = replace(edited, '&gt', '&gt;')");

$res = $db->query("update LEK_segment_history_data set edited = replace(edited, '_____transGTersetzungTilde_______','&gt;')");


$res = $db->query("show tables from translate5 like '%LEK_segment_view_%';");

/* @var $res mysqli_result */
/**
 * loop through the fetched foldertree entries, convert them, and save it back to the DB.
 */
while($row = $res->fetch_array()) {
    $table = $row[0];
    $db->query("update ".$table." set source = replace(source, '&gt;', '_____transGTersetzungTilde_______'),target = replace(target, '&gt;', '_____transGTersetzungTilde_______'),targetEdit = replace(targetEdit, '&gt;', '_____transGTersetzungTilde_______')");
    $db->query("update ".$table." set relais = replace(relais, '&gt;', '_____transGTersetzungTilde_______')");
    
    $db->query("update ".$table." set source = replace(source, '&gt', '&gt;'),target = replace(target, '&gt', '&gt;'),targetEdit = replace(targetEdit, '&gt', '&gt;')");
        
    $db->query("update ".$table." set relais = replace(relais, '&gt', '&gt;')");

    
    $db->query("update ".$table." set source = replace(source, '_____transGTersetzungTilde_______','&gt;'),target = replace(target, '_____transGTersetzungTilde_______','&gt;'),targetEdit = replace(targetEdit, '_____transGTersetzungTilde_______','&gt;')");
        
    $db->query("update ".$table." set relais = replace(relais, '_____transGTersetzungTilde_______','&gt;')");
}
