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
 * Remove all old term collection tbx cache folders from the disc.
 * Only folders for non-existing term-collections in the translate5 will be removed.
 */

use MittagQI\Translate5\NumberProtection\Model\NumberRecognition;

set_time_limit(0);

/* @var ZfExtended_Models_Installer_DbUpdater $this */

//uncomment the following line, so that the file is not marked as processed:
// $this->doNotSavePhpForDebugging = false;

//should be not __FILE__ in the case of wanted restarts / renamings etc
// and must not be a constant since in installation the same named constant would we defined multiple times then
$SCRIPT_IDENTIFIER = '406-TRANSLATE-3206-seed-number-protection-input-mapping.php';

if (APPLICATION_ENV === ZfExtended_BaseIndex::ENVIRONMENT_TEST) {

    $db = Zend_Db_Table::getDefaultAdapter();

    $lang = ZfExtended_Factory::get(editor_Models_Languages::class);

    $deId = $lang->getLangIdByRfc5646('de');
    $enId = $lang->getLangIdByRfc5646('en');
    $enUsId = $lang->getLangIdByRfc5646('en-US');
    $enGbId = $lang->getLangIdByRfc5646('en-GB');
    $frId = $lang->getLangIdByRfc5646('fr');

    $numberRecognition = ZfExtended_Factory::get(NumberRecognition::class);

    $inputMappings = [];

// region Dates
    $numberRecognition->loadBy('date', 'default m/d/Y');
    $inputMappings[] = "($enUsId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default m/d/y');
    $inputMappings[] = "($enUsId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default d/m/Y');

    $inputMappings[] = "($frId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enGbId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default d/m/y');

    $inputMappings[] = "($frId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enGbId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default Y-m-d');

    $inputMappings[] = "($frId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($deId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default d-m-Y');

    $inputMappings[] = "($frId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enGbId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default d.m.Y');

    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($deId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default d.m.y');

    $inputMappings[] = "($deId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('date', 'default Y.m.d');

    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($enGbId, {$numberRecognition->getId()})";
// endregion Dates block

// region Floats
    $numberRecognition->loadBy('float', 'default with dot thousand decimal comma');
    $inputMappings[] = "($deId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('float', 'default with comma thousand decimal dot');
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('float', 'default with whitespace thousand decimal comma');
    $inputMappings[] = "($frId, {$numberRecognition->getId()})";

    $numberRecognition->loadBy('float', 'default generic');
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($deId, {$numberRecognition->getId()})";
    $inputMappings[] = "($frId, {$numberRecognition->getId()})";
// endregion

// region Integers
    $numberRecognition->loadBy('integer', 'default simple');
    $inputMappings[] = "($enId, {$numberRecognition->getId()})";
    $inputMappings[] = "($deId, {$numberRecognition->getId()})";
    $inputMappings[] = "($frId, {$numberRecognition->getId()})";
// endregion

    $db->query('INSERT INTO `LEK_number_protection_input_mapping` (`languageId`, `numberRecognitionId`) VALUES ' . implode(',', $inputMappings));
}