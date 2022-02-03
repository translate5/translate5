<?php 
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * README: 345-TRANSLATE-2797-tbx-import-damage-datatypes
 * This script checks if the datatypes are stored as assumed, if not an error is logged.
 * Autorepairing is not done, since in the instance must be evaluated if the missing entry does exist (regarding the id) and if just the values are wrong.
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '345-TRANSLATE-2797-tbx-import-damage-datatypes.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

/** @var ZfExtended_Logger $log */
$log = Zend_Registry::get('logger');

$checker = new editor_Models_Terminology_DataTypeConsistencyCheck();
$invalidDataTypes = $checker->checkAttributesAgainstDataTypes();
if(!empty($invalidDataTypes)){
    $log->error('E9999', 'Script 345-TRANSLATE-2797-tbx-import-damage-datatypes found inconsistent data!', [
        'invalidDataTypes' => $invalidDataTypes
    ]);
}

$invalidAgainstDefault = $checker->checkDataTypesAgainstDefault();

if(!empty($invalidAgainstDefault['notFound']) || !empty($invalidAgainstDefault['differentContent'])) {
    $log->error('E9999', 'Script 345-TRANSLATE-2797-tbx-import-damage-datatypes found modified default datatypes!', [
        'invalidAgainstDefault' => $invalidAgainstDefault
    ]);
}