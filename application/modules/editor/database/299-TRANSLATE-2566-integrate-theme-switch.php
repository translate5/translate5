<?php 
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/***
 * Change the theme css file to single string theme. The complete path is resolved on the backend.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '299-TRANSLATE-2566-integrate-theme-switch.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();

$sql = 'SELECT * FROM `Zf_configuration` WHERE `name` = "runtimeOptions.extJs.cssFile"';

$db = Zend_Db_Table::getDefaultAdapter();
$res = $db->query($sql);
$result = $res->fetchAll();
$result = reset($result);

// the ols name should look like: /build/classic/theme-triton/resources/theme-triton-all.css
$re = '/\/build\/classic\/theme-(.*?)\/resources\/theme-.*/';
preg_match($re, $result['value'], $matches, PREG_OFFSET_CAPTURE, 0);
if(empty($matches)){
    return;
}
$newName = $matches[1][0];

$stmt = $db->prepare('UPDATE `Zf_configuration`  SET `value` = :newName WHERE `id` = :id');
$stmt->execute([
    ':newName' => $newName,
    ':id' => $result['id'],
]);
