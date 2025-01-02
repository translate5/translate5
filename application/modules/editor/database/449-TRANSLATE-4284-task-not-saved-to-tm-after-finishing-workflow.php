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

use MittagQI\Translate5\LanguageResource\ReimportSegments\PrepareReimportSegmentsWorker;

$SCRIPT_IDENTIFIER = '449-TRANSLATE-4284-task-not-saved-to-tm-after-finishing-workflow.php';

$logger = Zend_Registry::get('logger');
$db = Zend_Db_Table::getDefaultAdapter();

$select = $db->select()
    ->from('Zf_errorlog', 'extra')
    ->where('eventCode = ?', 'E1169')
    ->order('id ASC');

$result = $db->fetchCol($select);

foreach ($result as $row) {
    try {
        $extra = json_decode($row, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $logger->info('E0000', 'Could not decode JSON extra: ', [
            'row' => $row,
        ]);

        continue;
    }

    $languageResourceId = $extra['languageResource']['id'] ?? null;
    $taskGuid = $extra['task']['taskGuid'] ?? null;

    if (! $languageResourceId || ! $taskGuid) {
        $logger->info('E0000', 'Migration 449-TRANSLATE-4284: Task or language resource not found', [
            'row' => $row,
        ]);

        continue;
    }

    $worker = new PrepareReimportSegmentsWorker();

    $options['languageResourceId'] = $languageResourceId;

    try {
        $success = $worker->init($taskGuid, $options);
    } catch (\ZfExtended_Models_Entity_NotFoundException $e) {
        $logger->info('E0000', 'Migration 449-TRANSLATE-4284: Task not found', [
            'row' => $row,
        ]);

        continue;
    }

    if (! $success) {
        $logger->info('E0000', 'Migration 449-TRANSLATE-4284: Failed to init a worker', [
            'row' => $row,
        ]);

        continue;
    }

    $worker->queue();
}
