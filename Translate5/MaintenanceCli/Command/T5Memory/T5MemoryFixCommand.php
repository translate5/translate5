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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command\T5Memory;

use editor_Services_OpenTM2_HttpApi as T5MemoryApi;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\PersistenceService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class T5MemoryFixCommand extends Translate5AbstractCommand
{
    private const REIMPORT_SEGMENTS = 'Reimport segments';

    private const CLEAR_LOG_ENTRIES = 'Clear log entries';

    private const GET_CORRUPT_MEMORIES_LIST = 'Get list of corrupt memories';

    private ?Zend_Db_Adapter_Abstract $db = null;

    protected static $defaultName = 't5memory:fix';

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Temporary command to fix t5memory issues caused by broken reimport')
            ->setHelp(
                <<<'HELP'
                Caution: This command is a temporary solution to fix t5memory issues caused by broken reimports.
                This command can do 3 things:
                  - Reimport segments: Reimports segments for tasks that failed to reimport for some reason
                  - Clear log entries: Sets log level for entries related to broken reimport to INFO, 
                  so they are not shown in UI as errors
                  - Get list of corrupt memories: Lists language resources that are corrupt and need to be 
                  fixed manually
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $question = new ChoiceQuestion(
            'Please select the type of issue you want to fix',
            [self::REIMPORT_SEGMENTS, self::CLEAR_LOG_ENTRIES, self::GET_CORRUPT_MEMORIES_LIST],
            null
        );

        $answer = $this->io->askQuestion($question);

        return match ($answer) {
            self::REIMPORT_SEGMENTS => $this->reimportSegments(),
            self::CLEAR_LOG_ENTRIES => $this->clearLogEntries(),
            self::GET_CORRUPT_MEMORIES_LIST => $this->getCorruptMemoriesList(),
            default => self::SUCCESS
        };
    }

    #region Get corrupt memories list

    private function getCorruptMemoriesList(): int
    {
        $db = $this->getDb();

        $select = $db->select()
            ->from('Zf_errorlog', ['extra'])
            ->where('eventCode = ?', 'E1547')
            ->where('message LIKE \'%ReimportSegmentsWorker%\'')
            ->where('created > ?', '2025-01-09')
            ->order('id ASC');

        $rows = $db->fetchAll($select);

        $taskGuids = [];

        foreach ($rows as $row) {
            if (! isset($row['extra'])) {
                $this->io->warning('Extra is not set');

                continue;
            }

            try {
                $extra = json_decode($row['extra'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $this->io->warning('Could not decode JSON extra');

                continue;
            }

            $taskGuid = $extra['task'] ?? null;

            if (! $taskGuid) {
                $this->io->warning('Task or language resource not found');

                continue;
            }

            $taskGuids[] = $taskGuid;
        }

        $taskGuids = array_unique($taskGuids);

        $languageResourceRepository = new LanguageResourceRepository();

        $possibleLanguageResourceIds = [];

        foreach ($taskGuids as $taskGuid) {
            $languageResources = $languageResourceRepository->getAssociatedToTaskGroupedByType($taskGuid);
            $languageResources = $languageResources[\editor_Services_Manager::SERVICE_OPENTM2] ?? [];

            foreach ($languageResources as $languageResource) {
                $possibleLanguageResourceIds[] = (int) $languageResource['id'];
            }
        }

        $possibleLanguageResourceIds = array_unique($possibleLanguageResourceIds);

        $persistenceService = PersistenceService::create();
        $api = new T5MemoryApi();
        $corruptMemories = [];

        foreach ($possibleLanguageResourceIds as $languageResourceId) {
            $languageResource = $languageResourceRepository->get($languageResourceId);
            $api->setLanguageResource($languageResource);
            $tmName = $persistenceService->getWritableMemory($languageResource);
            $successful = $api->update(
                source: 'Test writing segment source',
                target: 'Test writing segment target',
                userName: 'Test user',
                context: 'Test context',
                timestamp: $api->getNowDate(),
                filename: 'Test filename',
                tmName: $tmName,
                saveDifferentTargetsForSameSource: false,
                save2disk: false,
            );

            if (! $successful) {
                $corruptMemories[] = $languageResourceId .
                    ' - ' . $languageResource->getName() .
                    ' - ' . $languageResource->getSourceLangCode() .
                    ' -> ' . $languageResource->getTargetLangCode();

                continue;
            }

            $internalKey = $api->getResult()->internalKey;
            [$recordKey, $targetKey] = explode(':', $internalKey);
            $segmentId = $api->getResult()->segmentId;

            $api->deleteEntry($tmName, (int) $segmentId, (int) $recordKey, (int) $targetKey);
        }

        $table = $this->io->createTable();

        foreach ($corruptMemories as $corruptMemory) {
            $table->addRow([$corruptMemory]);
        }

        $table->render();

        return self::SUCCESS;
    }

    #endregion

    #region Clear log entries

    private function clearLogEntries(): int
    {
        $db = $this->getDb();

        $db->update(
            'Zf_errorlog',
            [
                'level' => \ZfExtended_Logger::LEVEL_INFO,
            ],
            [
                'eventCode = ?' => 'E9999',
                'level = ?' => \ZfExtended_Logger::LEVEL_ERROR,
                'message LIKE \'%Call to a member function loadFirst()%\' OR message LIKE \'%unescape%\'',
            ]
        );

        $this->io->success('General log entries cleared');

        $db->update(
            'LEK_task_log',
            [
                'level' => \ZfExtended_Logger::LEVEL_INFO,
            ],
            [
                'eventCode = ?' => 'E9999',
                'level = ?' => \ZfExtended_Logger::LEVEL_ERROR,
                'message LIKE \'%Call to a member function loadFirst()%\' OR message LIKE \'%unescape%\'',
            ]
        );

        $db->update(
            'LEK_task_log',
            [
                'level' => \ZfExtended_Logger::LEVEL_INFO,
            ],
            [
                'eventCode = ?' => 'E1169',
                'level = ?' => \ZfExtended_Logger::LEVEL_ERROR,
            ]
        );

        $db->update(
            'LEK_task_log',
            [
                'level' => \ZfExtended_Logger::LEVEL_INFO,
            ],
            [
                'eventCode = ?' => 'E1370',
                'level = ?' => \ZfExtended_Logger::LEVEL_ERROR,
            ]
        );

        $this->io->success('Task log entries cleared');

        return self::SUCCESS;
    }

    #endregion

    #region Reimport segments

    private function reimportSegments(): int
    {
        $taskIds = $this->getTaskIdsForReimport();

        if (empty($taskIds)) {
            $this->io->success('No tasks found for reimport');

            return self::SUCCESS;
        }

        if (! $this->io->confirm('Do you want to reimport segments for ' . count($taskIds) . ' tasks?')) {
            return self::SUCCESS;
        }

        $db = $this->getDb();
        $queue = new ReimportSegmentsQueue();

        foreach ($taskIds as $logId => [$taskGuid, $languageResourceId]) {
            $this->io->writeln(
                'Reimporting segments for task ' . $taskGuid . ' and language resource ' . $languageResourceId
            );

            $queue->queueSnapshot($taskGuid, (int) $languageResourceId);
            $this->io->writeln('Reimport queued');

            $db->query('UPDATE `Zf_errorlog` SET `level` = ? WHERE `id` = ?', [\ZfExtended_Logger::LEVEL_INFO, $logId]);
        }

        $this->clearLogEntries();

        return self::SUCCESS;
    }

    private function getTaskIdsForReimport(): array
    {
        $db = $this->getDb();

        $select = $db->select()
            ->from('Zf_errorlog', ['id', 'extra'])
            ->where('eventCode = ?', 'E1169')
            ->where('level = ?', \ZfExtended_Logger::LEVEL_ERROR)
            ->order('id ASC');

        $rows = $db->fetchAll($select);

        $result = [];
        foreach ($rows as $row) {
            if (! isset($row['extra'])) {
                continue;
            }

            try {
                $extra = json_decode($row['extra'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $this->io->warning('Could not decode JSON extra ID:' . $row['id']);

                continue;
            }

            $languageResourceId = $extra['languageResource']['id'] ?? null;
            $taskGuid = $extra['task']['taskGuid'] ?? null;

            if (! $languageResourceId || ! $taskGuid) {
                $this->io->warning('Task or language resource not found in extra data ID: ' . $row['id']);

                continue;
            }

            $result[$row['id']] = [
                $taskGuid,
                $languageResourceId,
            ];
        }

        return $result;
    }

    #endregion

    private function getDb(): Zend_Db_Adapter_Abstract
    {
        if (! $this->db) {
            $this->db = Zend_Db_Table::getDefaultAdapter();
        }

        return $this->db;
    }
}
