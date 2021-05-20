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
 * Enables plugins by default(if not enabled)
 * - ModelFront
 * - IpAuthentication
 * - PangeaMt
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '286-TRANSLATE-2494_Plugins_enabled_by_default.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$manager = Zend_Registry::get('PluginManager');
/* @var $manager ZfExtended_Plugin_Manager */
$plugins = [
    'PangeaMt'=>'editor_Plugins_PangeaMt_Init',
    'IpAuthentication'=>'editor_Plugins_IpAuthentication_Init',
    'ModelFront'=>'editor_Plugins_ModelFront_Init'
];

$activeByDefault = [];
foreach($plugins as $plugin=>$class) {
    if($manager->setActive($plugin, true)){
        $activeByDefault[] = $class;
    }
}
// update the activated plugins also as defaults.
$config = ZfExtended_Factory::get('editor_Models_Config');
/* @var $config editor_Models_Config */
$config->loadByName('runtimeOptions.plugins.active');

try {
    $defaults = [];
    if(!empty($config->getDefault())){
        $defaults = Zend_Json::decode($config->getDefault());
    }
    $defaults = Zend_Json::encode(array_unique(array_merge($defaults,$activeByDefault)));
    $config->setDefault($defaults);
    $config->save();
} catch (Zend_Json_Exception $e) {
}