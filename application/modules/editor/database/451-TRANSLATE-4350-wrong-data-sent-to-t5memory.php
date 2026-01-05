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

use MittagQI\Translate5\LanguageResource\Adapter\LanguagePairDTO;
use MittagQI\Translate5\LanguageResource\Exception\ReimportQueueException;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\Workflow\Executors\ReimportSegmentsActionExecutor;
use MittagQI\Translate5\Plugins\TMMaintenance\Service\MaintenanceService;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;

ini_set('max_execution_time', 0);

$SCRIPT_IDENTIFIER = '451-TRANSLATE-4350-wrong-data-sent-to-t5memory.php';

$logger = Zend_Registry::get('logger');
$db = Zend_Db_Table::getDefaultAdapter();
$queue = new ReimportSegmentsQueue();
$languageResourceRepository = new LanguageResourceRepository();
$reimportSegmentsActionExecutor = new ReimportSegmentsActionExecutor(
    $logger,
    $queue,
    $languageResourceRepository,
    new TaskTmRepository(),
);

#region Check for version

$query = 'SELECT message
    FROM Zf_errorlog
    WHERE eventCode = \'E1598\'
    ORDER BY id DESC
    LIMIT 1;
';

$result = $db->query($query)->fetchColumn();

$pattern = '/\b\d+\.\d+\.\d+\b/';

// Perform the match
if (! preg_match($pattern, $result, $matches)) {
    $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Could not find version in error log', [
        'result' => $result,
    ]);

    return;
}

$version = $matches[0];

if (! in_array($version, ['7.15.0', '7.15.1', '7.16.0'])) {
    $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Previous version does not need fixing t5memory data', [
        'result' => $result,
    ]);

    return;
}
#endregion

#region trigger reimport for tasks where wrong data was sent to t5memory

$query = 'SELECT id FROM LEK_task WHERE taskGuid IN (
            SELECT taskGuid
            FROM LEK_task_log
            WHERE created > \'2025-01-07 00:00:00\' AND created < \'2025-01-10 00:00:00\'
            AND domain = \'editor.workflow\'
            AND extra LIKE \'%"newStep":"workflowEnded"%\'
        )';

$result = $db->query($query)->fetchAll();

$ids = array_column($result, 'id');

foreach ($ids as $id) {
    $task = new editor_Models_Task();
    $task->load($id);

    $data = $languageResourceRepository->getAssociatedToTaskGroupedByType($task->getTaskGuid());

    $maintenanceService = new MaintenanceService();

    //clear language resources from broken data
    foreach ($data[\editor_Services_Manager::SERVICE_T5_MEMORY] ?? [] as $languageResourceData) {
        $languageResource = new \editor_Models_LanguageResources_LanguageResource();
        $languageResource->load($languageResourceData['id']);

        $maintenanceService->connectTo(
            $languageResource,
            LanguagePairDTO::fromLanguageResource($languageResource)
        );

        foreach (['2024', '2025'] as $year) {
            $searchFields = [
                'source' => '\\',
                'target' => '\\',
            ];

            foreach ($searchFields as $field => $value) {
                $searchCriteria = [
                    'source' => '',
                    'sourceMode' => 'contains',
                    'target' => '',
                    'targetMode' => 'contains',
                    'sourceLanguage' => '',
                    'targetLanguage' => '',
                    'author' => '2024',
                    'authorMode' => 'contains',
                    'creationDateFrom' => (new \DateTime('1970-01-01'))->getTimestamp(),
                    'creationDateTo' => (new \DateTime('tomorrow'))->getTimestamp(),
                    'additionalInfo' => '',
                    'additionalInfoMode' => 'contains',
                    'document' => '',
                    'documentMode' => 'contains',
                    'context' => '',
                    'contextMode' => 'contains',
                    'onlyCount' => 0,
                ];

                $searchCriteria[$field] = $value;

                $searchDto = SearchDTO::fromArray($searchCriteria);

                $timeElapsed = 0;
                while (true) {
                    if ($timeElapsed >= 3200) {
                        $logger->error(
                            'E0000',
                            'Migration 451-TRANSLATE-4350: Failed to check if memory has broken data',
                            [
                                'languageResource' => $languageResource,
                            ]
                        );

                        continue 2;
                    }

                    try {
                        $result = $maintenanceService->concordanceSearch('', $field, '', searchDTO: $searchDto);
                    } catch (\editor_Services_Connector_Exception) {
                        sleep(2);
                        $timeElapsed += 2;

                        continue;
                    }

                    break;
                }

                while (count($result->getResult()) > 0) {
                    try {
                        $maintenanceService->deleteBatch($searchDto);
                    } catch (\Throwable $e) {
                        $logger->error('E0000', 'Migration 451-TRANSLATE-4350: Failed to delete segments', [
                            'languageResource' => $languageResource,
                            'error' => $e->getMessage(),
                        ]);

                        continue;
                    }

                    // Wait for deletion to finish
                    $timeElapsed = 0;
                    while (true) {
                        $status = $maintenanceService->getStatus($languageResource->getResource(), $languageResource);

                        if (Status::AVAILABLE === $status || $timeElapsed >= 3200) {
                            break;
                        }

                        sleep(2);
                        $timeElapsed += 2;
                    }

                    try {
                        $result = $maintenanceService->concordanceSearch('', $field, '', searchDTO: $searchDto);
                    } catch (\editor_Services_Connector_Exception) {
                        $logger->error(
                            'E0000',
                            'Migration 451-TRANSLATE-4350: Failed to check if memory has broken data',
                            [
                                'languageResource' => $languageResource,
                            ]
                        );

                        break;
                    }
                }
            }
        }
    }

    $reimportSegmentsActionExecutor->reimportSegments($task);
}

#endregion

#region Trigger reimport one more time due to previous fails

$select = $db->select()
    ->from('Zf_errorlog', ['id', 'extra'])
    ->where('eventCode = ?', 'E1169')
    ->where('level = ?', \ZfExtended_Logger::LEVEL_ERROR)
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

    $languageResourceId = $extra['languageResource']['id'] ?? null;
    $taskGuid = $extra['task']['taskGuid'] ?? null;

    if (! $languageResourceId || ! $taskGuid) {
        $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Task or language resource not found', [
            'row' => $row,
        ]);

        continue;
    }

    try {
        $queue->queueSnapshot($taskGuid, $languageResourceId);
        /** @phpstan-ignore-next-line */
    } catch (\ZfExtended_Models_Entity_NotFoundException $e) {
        $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Task not found', [
            'row' => $row,
        ]);

        continue;
    } catch (ReimportQueueException $e) {
        $logger->info('E0000', 'Migration 451-TRANSLATE-4350: Failed to init a worker', [
            'row' => $row,
        ]);

        continue;
    }

    $db->query('UPDATE `Zf_errorlog` SET `level` = ? WHERE `id` = ?', [\ZfExtended_Logger::LEVEL_INFO, $row['id']]);
}

#endregion
