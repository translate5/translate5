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
 * Migrate the helpWindow value as helpWindow url state
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

$updateHelpUrl=function($table,$url) {
    
    $db = Zend_Db_Table::getDefaultAdapter();
    $sql="SELECT * FROM `".$table."` where name like '%runtimeOptions.frontend.defaultState.helpWindow.%';";
    

    $hasPlaceolder=strpos($url,'{0}')!==false;
    $result = $db->query($sql)->fetchAll();
    
    if(empty($result)){
        return;
    }
    
    foreach ($result as $row) {
        if(empty($row['value']) && empty($url)){
            continue;
        }
        try {
            $value=json_decode($row['value']) ?? '';
            if(empty($value)){
                $value=new stdClass();
                settype($value->doNotShowAgain,'bool');
                $value->doNotShowAgain=false;
            }
            
            if(!isset($value->loaderUrl)){
                settype($value->loaderUrl,'string');
            }
            
            if($hasPlaceolder || $row['name']=='runtimeOptions.frontend.defaultState.helpWindow.taskoverview'){
                //the url is with placeholder (for each view separate page)
                //when the url is without placeholder(one url for all pages), add the url only for the taskoverview
                $value->loaderUrl=$url;
            }
            
            $sql="UPDATE `".$table."` SET value=? WHERE id=?";
            $db->query($sql,[json_encode($value),$row['id']]);
        } catch (Exception $e) {
        }
    }
};

$config = Zend_Registry::get('config');
$helpUrl=$config->runtimeOptions->helpUrl ?? '';
$updateHelpUrl('LEK_user_config',$helpUrl);
$updateHelpUrl('Zf_configuration',$helpUrl);

