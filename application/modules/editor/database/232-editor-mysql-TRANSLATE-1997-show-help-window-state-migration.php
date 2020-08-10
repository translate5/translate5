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
 * Migrate the values from runtimeOptions->frontend->defaultState to separate zf config entry for each defined value in defaultState.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '233-editor-mysql-TRANSLATE-1997-show-help-window-state-migration.php';

//since on updating the segment finish count, internal workflow stuff is triggered, the system user will be loaded, 
// to enalbe that we let assume the script that we are in a worker:
if(!defined('ZFEXTENDED_IS_WORKER_THREAD')) {
    define('ZFEXTENDED_IS_WORKER_THREAD', true);
}

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables 
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$config = Zend_Registry::get('config');
$defaultState=$config->runtimeOptions->frontend->defaultState ?? null;

if(empty($defaultState)){
    return;
}

try {
    $configModel=ZfExtended_Factory::get('ZfExtended_Models_Config');
    /* @var $configModel ZfExtended_Models_Config */
    
    $defaultState=$defaultState->toArray();
    $prefix='runtimeOptions.frontend.defaultState.';
    foreach ($defaultState as $key=>$value){
        $key=str_replace('#','',$key);
        $configModel->setName($prefix.$key);
        $configModel->setConfirmed(1);
        $configModel->setModule('editor');
        $configModel->setCategory('system');
        $configModel->setValue(json_encode($value));
        $configModel->setDefault('');
        $configModel->setDefaults('');
        $configModel->setType('map');
        $configModel->setDescription('Default state configuration for the grid component :'.$key);
        $configModel->save();
    }
} catch (Exception $e) {
}


