<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Fix broken memory names in the LEK_languageresources table for t5memory
 * Because of a bug in the connector and the tm reorganize, the original memory names are saved with fuzzy sufix and this
 * leads to a problem where the original memory no longer points to valid memory in t5memory
 */
set_time_limit(0);

//uncomment the following line, so that the file is not marked as processed:
//$this->doNotSavePhpForDebugging = false;

$SCRIPT_IDENTIFIER = '420-TRANSLATE-3810-reorganize-tm-can-save-status.php';

/* @var $this ZfExtended_Models_Installer_DbUpdater */

/**
 * define database credential variables
 */
$argc = count($argv);
if (empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
$res = $db->query(
    "SELECT * FROM LEK_languageresources WHERE name REGEXP '-fuzzy-[0-9]+' OR specificData REGEXP '-fuzzy-[0-9]+'"
);

$result = $res->fetchAll();

$logger = Zend_Registry::get('logger');

if (count($result) === 0) {
    $logger->info(
        'E0000',
        'No resource with -fuzzy- keyword in the name or specific data found'
    );

    return;
}

$logger->error(
    'E1599',
    'Detected resource with -fuzzy- keyword in the name or specific data: ' . count($result) . ' resource'
);

foreach ($result as $row) {
    $logger->info(
        'E1599',
        'Processing detected resource: ' . $row['name'] . ' - ' . $row['id'],
        [
            'specificData' => $row['specificData'],
        ]
    );

    $model = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
    $model->load($row['id']);

    $specificData = $model->getSpecificData(parseAsArray: true);

    $correct = explode('-fuzzy-', $model->getName());

    // in some cases the name is also not correct
    $model->setName($correct[0]);

    $memoryCount = 0;

    foreach ($specificData['memories'] as &$memory) {
        $logger->info(
            'E1599',
            'Processing detected resource memory: ' . $memory['filename'],
            [
                'memory' => $memory,
            ]
        );

        $correct = explode('-fuzzy-', $memory['filename']);
        $correct = $correct[0];
        $memory['filename'] = $correct . ($memoryCount > 0 ? '_' . $memoryCount : '');

        $memoryCount++;
    }

    $model->setSpecificData($specificData);
    $model->save();
    $logger->info(
        'E1599',
        'Resource after correction: ' . $model->getName() . ' - ' . $model->getId(),
        [
            'specificData' => $model->getSpecificData(parseAsArray: true),
        ]
    );
}
