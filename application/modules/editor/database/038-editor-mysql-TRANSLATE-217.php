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
  This script converts the paths of referencefiles in the database as described in TRANSLATE-217.
  The script is to be used in commandline, and has to be called like that:
  
  /usr/bin/php 038-editor-mysql-TRANSLATE-217.php DBHOST DBNAME DBUSER DBPASSWD
  
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
    die("please call the script with the following parameters: \n  /usr/bin/php 038-editor-mysql-TRANSLATE-217.php DBHOST DBNAME DBUSER DBPASSWD [DBPORT [DBSOCKET]]\n\n");
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
 */
$db = @new mysqli($dbhost, $dbuser, $dbpwd, $dbname, $dbport, $dbsocket);
if ($db->connect_error) {
    die('Connect Error (' . $db->connect_errno . ') '. $db->connect_error."\n");
}
$res = $db->query("select * from LEK_foldertree where referenceFileTree != ''");
/**
 * remove the system dependant prefix from a single path
 * @param string $path
 */
function convertPath($path) {
    return preg_replace('#^/([^/]+/)+referencefile/#', 'referencefile/', $path);
}

/**
 * walk through the given file tree recursivly and convert the paths
 * @param array $nodes
 */
function convertPaths(array $nodes) {
    if(empty($nodes)) {
        return;
    }
    foreach($nodes as $node) {
        if(isset($node->href)) {
            $node->href = convertPath($node->href);
        }
        if(isset($node->path)) {
            $node->path = convertPath($node->path);
        }
        if(!empty($node->children) && is_array($node->children)) {
            convertPaths($node->children);
        }
    }
};

/**
 * setup update statement
 */
$id = null;
$tree = null;
$stmt = $db->prepare('update LEK_foldertree set referenceFileTree = ? where id = ?');
$stmt->bind_param('si', $tree, $id);

/**
 * loop through the fetched foldertree entries, convert them, and save it back to the DB.
 */
while($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $refs = json_decode($row['referenceFileTree']);
    if($refs === null) {
        echo 'WARNING: referenceFileTree for ID '.$id." cant be decoded!\n";
    }
    convertPaths($refs);
    $tree = json_encode($refs);
    if(! $stmt->execute()) {
        echo 'WARNING: cant update referenceFileTree for ID '.$id."\n";
    }
}
