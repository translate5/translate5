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

set_time_limit(0);

/* @var ZfExtended_Models_Installer_DbUpdater $this */

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '406-TRANSLATE-3206-seed-number-protection-input-mapping.php';

if (APPLICATION_ENV !== ZfExtended_BaseIndex::ENVIRONMENT_TEST) {

    $db = Zend_Db_Table::getDefaultAdapter();

    $lang = ZfExtended_Factory::get(editor_Models_Languages::class);

    $deId = $enId = $enUsId = $enGbId = $frId = null;

    try {
        $deId = $lang->getLangIdByRfc5646('de');
    } catch (\ZfExtended_Models_Entity_NotFoundException) {}
    try {
        $enId = $lang->getLangIdByRfc5646('en');
    } catch (\ZfExtended_Models_Entity_NotFoundException) {}
    try {
        $enUsId = $lang->getLangIdByRfc5646('en-US');
    } catch (\ZfExtended_Models_Entity_NotFoundException) {}
    try {
        $enGbId = $lang->getLangIdByRfc5646('en-GB');
    } catch (\ZfExtended_Models_Entity_NotFoundException) {}
    try {
        $frId = $lang->getLangIdByRfc5646('fr');
    } catch (\ZfExtended_Models_Entity_NotFoundException) {}

    $inputMappings = [];

// region Dates
    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default m/d/Y');

    if ($enUsId) {
        $inputMappings[] = "($enUsId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default m/d/y');

    if ($enUsId) {
        $inputMappings[] = "($enUsId, {$db->query($s)->fetchColumn()})";
    }
    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default d/m/Y');

    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }
    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($enGbId) {
        $inputMappings[] = "($enGbId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default d/m/y');

    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }
    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($enGbId) {
        $inputMappings[] = "($enGbId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default Y-m-d');

    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }
    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($enGbId) {
        $inputMappings[] = "($enGbId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default d-m-Y');

    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }
    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($enGbId) {
        $inputMappings[] = "($enGbId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default d.m.Y');

    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($deId) {
        $inputMappings[] = "($deId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default d.m.y');

    if ($deId) {
        $inputMappings[] = "($deId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'date')->where('name = ?', 'default Y.m.d');

    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($enGbId) {
        $inputMappings[] = "($enGbId, {$db->query($s)->fetchColumn()})";
    }
// endregion Dates block

// region Floats
    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'float')->where('name = ?', 'default with dot thousand decimal comma');

    if ($deId) {
        $inputMappings[] = "($deId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'float')->where('name = ?', 'default with comma thousand decimal dot');

    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'float')->where('name = ?', 'default with whitespace thousand decimal comma');

    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }

    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'float')->where('name = ?', 'default generic');

    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($deId) {
        $inputMappings[] = "($deId, {$db->query($s)->fetchColumn()})";
    }
    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }
// endregion

// region Integers
    $s = $db->select()->from('LEK_number_protection_number_recognition', 'id')
        ->where('type = ?', 'integer')->where('name = ?', 'default simple');

    if ($enId) {
        $inputMappings[] = "($enId, {$db->query($s)->fetchColumn()})";
    }
    if ($deId) {
        $inputMappings[] = "($deId, {$db->query($s)->fetchColumn()})";
    }
    if ($frId) {
        $inputMappings[] = "($frId, {$db->query($s)->fetchColumn()})";
    }
// endregion

    $db->query('INSERT INTO `LEK_number_protection_input_mapping` (`languageId`, `numberRecognitionId`) VALUES ' . implode(',', $inputMappings));
}