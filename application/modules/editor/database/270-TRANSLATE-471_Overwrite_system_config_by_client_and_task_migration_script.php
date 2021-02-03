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
 * Migrate the zf_configuration values in installation.ini to the database.
 * This will also remove the matched configs in the installation.ini
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

/* @var $this ZfExtended_Models_Installer_DbUpdater */

$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

//load the installation ini file
$filePath = APPLICATION_PATH.'/config/installation.ini';
$file = parse_ini_file($filePath);

if (!copy($filePath, $filePath.'.bak')) {
    echo "Failed to create a copy of [ $filePath ] File content was: \n";
    error_log("Failed to create a copy of [ $filePath ] File content was: \n");
    error_log(print_r($file,1));
}

$config = ZfExtended_Factory::get('editor_Models_Config');
/* @var $config editor_Models_Config */

$notFound = [];
//foreach zf config in installation ini, update the config value into the database
foreach ($file as $key=>$value){
    if(substr($key, 0, strlen("runtimeOptions")) !== "runtimeOptions"){
        continue;
    }
    try {
        $config->loadByName($key);
        if($config->getType() == ZfExtended_Resource_DbConfig::TYPE_LIST){
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $config->setValue($value);
        $config->save();
        error_log("Config updated : ".$config->getName(). '; value'.$config->getValue());
    } catch (ZfExtended_Models_Entity_NotFoundException $e) {
        $notFound[] = $key;
    }
}
if(!empty($notFound)){
    error_log("Configs not found in zf_configuration table : [".implode(',', $notFound)."]");
    echo "Configs not found in zf_configuration table : [".implode(',', $notFound)."]";
}

function startsWith($string, $startString){
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

$file = file($filePath);
$collectedComments = [];
//remove the configs from the ini file
foreach( $file as $key=>$line ) {
    $line = trim($line);
    if(startsWith($line,';')) {
        $collectedComments[] = $line;
        unset($file[$key]);
        continue;
    }
    if(startsWith($line,'runtimeOptions')) {
        unset($file[$key]);
    }
}
if(!empty($collectedComments)){
    error_log("Comments removed from installation.ini : \n".print_r($collectedComments,1));
    echo "Comments removed from installation.ini : \n".print_r($collectedComments,1);
}
file_put_contents($filePath, implode("", $file));

