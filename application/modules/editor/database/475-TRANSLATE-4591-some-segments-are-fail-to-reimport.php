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

use MittagQI\Translate5\LanguageResource\Exception\ReimportQueueException;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;

ini_set('max_execution_time', 0);

$SCRIPT_IDENTIFIER = '475-TRANSLATE-4591-some-segments-are-fail-to-reimport.php';

$logger = Zend_Registry::get('logger');
$db = Zend_Db_Table::getDefaultAdapter();
$taskRepository = TaskRepository::create();
$languageResourceRepository = LanguageResourceRepository::create();
$queue = new ReimportSegmentsQueue();

$select = $db->select()
    ->from('Zf_errorlog', ['id', 'extra'])
    ->where('eventCode = ?', 'E0000')
    ->where("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.failedSegments')) != '[]'")
    ->order('id ASC');

$result = $db->fetchAll($select);

foreach ($result as $row) {
    if (! isset($row['extra'])) {
        $logger->info('E0000', 'Migration 475-TRANSLATE-4591: Extra is not set', [
            'row' => $row,
        ]);

        continue;
    }

    try {
        $extra = json_decode($row['extra'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        $logger->info('E0000', 'Migration 475-TRANSLATE-4591: Could not decode JSON extra: ', [
            'row' => $row,
        ]);

        continue;
    }

    $taskId = $extra['taskId'] ?? null;
    $languageResourceId = $extra['tmId'] ?? null;
    $failedSegmentsIds = $extra['failedSegments'] ?? null;

    if (! $taskId || ! $languageResourceId || empty($failedSegmentsIds)) {
        $logger->info('E0000', 'Migration 475-TRANSLATE-4591: Extra doesn\'t contain all necessary data for reimport', [
            'row' => $row,
        ]);

        continue;
    }

    try {
        $task = $taskRepository->get($taskId);
    } catch (InexistentTaskException) {
        $logger->info('E0000', 'Migration 475-TRANSLATE-4591: Task not found', [
            'row' => $row,
        ]);

        continue;
    }

    try {
        $queue->queueReimport(
            $task->getTaskGuid(),
            $languageResourceId,
            [
                ReimportSegmentsOptions::FILTER_ONLY_EDITED => false,
                ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP => true,
                ReimportSegmentsOptions::FILTER_ONLY_IDS => $failedSegmentsIds,
            ]
        );
    } catch (ReimportQueueException) {
        $logger->error(
            'E0000',
            'Migration 475-TRANSLATE-4591: Could not init worker for reimporting segments',
            [
                'task' => $taskId,
                'languageResource' => $languageResourceId,
            ]
        );
    }
}
