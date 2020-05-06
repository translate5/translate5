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
 * Removes the loaderUrl from the section state object to separate loaderUrl config for the section
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

$updateHelpUrl=function($table,$updateNewConfig) {
    
    $db = Zend_Db_Table::getDefaultAdapter();
    $sql="SELECT * FROM `".$table."` where name like '%runtimeOptions.frontend.defaultState.helpWindow.%';";

    $result = $db->query($sql)->fetchAll();
    
    if(empty($result)){
        return;
    }
    //config template for the loaderUrl section config
    $configNameTemplate='runtimeOptions.frontend.helpWindow.{0}.loaderUrl';
    
    foreach ($result as $row) {
        try {
            $value=json_decode($row['value']) ?? '';
            if(empty($value)){
                continue;
            }
            if(isset($value->loaderUrl)){
                
                //for zf_configuration table update the new loader config
                if($updateNewConfig){
                    $sql="UPDATE `".$table."` SET value=? WHERE name=?";
                    $sectionName=str_replace('runtimeOptions.frontend.defaultState.helpWindow.','',$row['name']);
                    $configName=str_replace('{0}',$sectionName,$configNameTemplate);
                    $db->query($sql,[$value->loaderUrl,$configName]);
                }
                unset($value->loaderUrl);
            }
            //save the new value
            $sql="UPDATE `".$table."` SET value=? WHERE id=?";
            $db->query($sql,[json_encode($value),$row['id']]);
        } catch (Exception $e) {
        }
    }
};

$updateHelpUrl('LEK_user_config',false);
$updateHelpUrl('Zf_configuration',true);

