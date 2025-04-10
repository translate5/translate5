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

use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\Workflow\Executors\ReimportSegmentsActionExecutor;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;

ini_set('max_execution_time', 0);

$SCRIPT_IDENTIFIER = '473-TRANSLATE-4585-reimport-task-doesnt-work-with-special-characters.php';

$logger = Zend_Registry::get('logger');
$db = Zend_Db_Table::getDefaultAdapter();
$taskRepository = TaskRepository::create();
$queue = new ReimportSegmentsQueue();
$languageResourceRepository = new LanguageResourceRepository();
$reimportSegmentsActionExecutor = new ReimportSegmentsActionExecutor(
    $logger,
    $queue,
    $languageResourceRepository,
    new TaskTmRepository(),
);

$select = $db->select()
    ->from('Zf_errorlog', ['id', 'extra'])
    ->where('eventCode = ?', 'E9999')
    ->where('level = ?', \ZfExtended_Logger::LEVEL_ERROR)
    ->where('message LIKE ?', '%UpdateSegmentDTO%')
    ->order('id ASC');

$result = $db->fetchAll($select);

foreach ($result as $row) {
    if (! isset($row['extra'])) {
        $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Extra is not set', [
            'row' => $row,
        ]);

        continue;
    }

    try {
        $extra = json_decode($row['extra'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $logger->info('E0000', 'Could not decode JSON extra: ', [
            'row' => $row,
        ]);

        continue;
    }

    $taskId = $extra['task']['id'] ?? null;

    if (! $taskId) {
        $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Task not found', [
            'row' => $row,
        ]);

        continue;
    }

    try {
        $task = $taskRepository->get($taskId);
    } catch (InexistentTaskException) {
        $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Task not found', [
            'row' => $row,
        ]);

        continue;
    }

    $reimportSegmentsActionExecutor->reimportSegments($task);

    $db->query('UPDATE `Zf_errorlog` SET `level` = ? WHERE `id` = ?', [\ZfExtended_Logger::LEVEL_INFO, $row['id']]);
}
