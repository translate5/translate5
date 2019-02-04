<?php
/*
--
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or 
--  plugin-exception.txt in the root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

/*
  README:
    Update the long language resources names from the task import to short verssion.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}


$db = Zend_Db_Table::getDefaultAdapter();

//load alll termcollections which where imported on task import
$sql = 'SELECT id,name FROM `LEK_languageresources` WHERE `name` LIKE "Term Collection for Task:%" AND `name` REGEXP "[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}"';
$res = $db->query($sql);
$resources = $res->fetchAll();

$count = count($resources);
error_log('LanguageResources to be converted: '.$count."\n");
$convertedCount=0;
foreach ($resources as $resource) {
    //get the guid from the resource name
    $re = '/(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})/i';
    preg_match($re, $resource['name'], $matches);
    if(!empty($matches) && isset($matches[0])){
        $guid=$matches[0];
        //update the resource name
        $resModel=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $resModell editor_Models_LanguageResources_LanguageResource */
        $resModel->load($resource['id']);
        $resModel->setName('Term Collection for '.$guid);
        $resModel->save();
        $convertedCount++;
    }
}
error_log('LanguageResources converted: '.$convertedCount."\n");