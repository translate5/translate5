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

use editor_Services_T5Memory_Service as Service;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class T5MemoryReimportTaskCommand extends Translate5AbstractCommand
{
    private const OPTION_USE_SEGMENT_TIMESTAMP = 'use-segment-timestamp';

    private const OPTION_SOURCE_LANGUAGE = 'source-language';

    private const OPTION_TARGET_LANGUAGE = 'target-language';

    private const OPTION_ONLY_EDITED_SEGMENTS = 'only-edited-segments';

    private const OPTION_FINISHED_TASKS = 'finished-tasks';

    private const OPTION_START_DATE = 'start-date';

    private const OPTION_END_DATE = 'end-date';

    private const OPTION_TASK_ID = 'task-id';

    private const OPTION_LANGUAGE_RESOURCE_ID = 'language-resource-id';

    private const OPTION_YES = 'yes';

    protected static $defaultName = 't5memory:reimport-task';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Reimport task segments into the t5memory.' .
                ' Reimports only those segments, that previously have been manually saved by a user.')
            ->addOption(
                self::OPTION_USE_SEGMENT_TIMESTAMP,
                null,
                InputOption::VALUE_NEGATABLE,
                'Use segment timestamp for reimport, otherwise current time is used (default true)',
                true
            )
            ->addOption(
                self::OPTION_SOURCE_LANGUAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Source language code to filter tasks or language resources'
            )
            ->addOption(
                self::OPTION_TARGET_LANGUAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Target language code to filter tasks or language resources'
            )
            ->addOption(
                self::OPTION_ONLY_EDITED_SEGMENTS,
                null,
                InputOption::VALUE_NEGATABLE,
                'Specifies if only user edited segments should be reimported (default true)',
                true
            )
            ->addOption(
                self::OPTION_FINISHED_TASKS,
                'f',
                InputOption::VALUE_NEGATABLE,
                'Specifies if only tasks that are finished in workflow will be reimported (default true).' .
                    ' If not set, tasks will be filtered by creation date. If set tasks will be filtered by finish date.',
                true
            )
            ->addOption(
                self::OPTION_START_DATE,
                's',
                InputOption::VALUE_REQUIRED,
                'Start date for filtering tasks (format: YYYY-MM-DD HH:MM:SS)',
            )
            ->addOption(
                self::OPTION_END_DATE,
                'e',
                InputOption::VALUE_REQUIRED,
                'End date for filtering tasks (format: YYYY-MM-DD HH:MM:SS)',
            )
            ->addOption(
                self::OPTION_TASK_ID,
                't',
                InputOption::VALUE_REQUIRED,
                'Particular task id to reimport (overrides strategy selection, dates are ignored)',
            )
            ->addOption(
                self::OPTION_LANGUAGE_RESOURCE_ID,
                'l',
                InputOption::VALUE_REQUIRED,
                'Particular language resource id to reimport (overrides strategy selection, languages are ignored)',
            )
            ->addOption(
                self::OPTION_YES,
                'y',
                InputOption::VALUE_NONE,
                'Automatic yes to prompts; run non-interactively when possible'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        if ($this->isFilteringByLanguages()) {
            $this->io->info('Please note that you have provided language filters. ' .
                'The command will only reimport tasks that match the given language pair.');
        }

        $strategy = $this->askReimportByStrategy();
        $taskIdsGrouped = $this->getTaskIdsForReimport($strategy);

        if (empty($taskIdsGrouped)) {
            $this->io->info('No tasks for your criteria.');

            return self::SUCCESS;
        }

        $onlyEdited = $this->input->getOption(self::OPTION_ONLY_EDITED_SEGMENTS);
        $useSegmentTimestamp = (bool) $input->getOption(self::OPTION_USE_SEGMENT_TIMESTAMP);

        $this->showWhatIsAboutToBeDone($taskIdsGrouped, $onlyEdited, $useSegmentTimestamp);

        $question = new ConfirmationQuestion(
            'Do you want to start reimport?',
            false,
        );

        $proceed = $input->getOption(self::OPTION_YES) || $this->io->askQuestion($question);

        if (! $proceed) {
            $this->io->info('Aborted by user.');

            return self::SUCCESS;
        }

        $this->reimport($taskIdsGrouped, $onlyEdited, $useSegmentTimestamp);

        return self::SUCCESS;
    }

    private function showWhatIsAboutToBeDone(array $taskIdsGrouped, bool $onlyEdited, bool $useSegmentTimestamp): void
    {
        $this->io->block('The following tasks will be reimported into the following memories', null, 'fg=white;bg=blue', ' ', true);

        $table = $this->io->createTable();
        $table->setHeaders(['Task id', 'Language Resource id']);

        foreach ($taskIdsGrouped as $languageResourceId => $taskIds) {
            foreach ($taskIds as $taskId) {
                $table->addRow([$taskId, $languageResourceId]);
            }
        }
        $table->render();

        $this->io->writeln('');

        $this->io->info(sprintf(
            'The command will reimport %d tasks into %d language resources. ' .
            'Only user edited segments: %s. Use segment timestamp: %s',
            array_sum(array_map('count', $taskIdsGrouped)),
            count($taskIdsGrouped),
            $onlyEdited ? 'yes' : 'no',
            $useSegmentTimestamp ? 'yes' : 'no'
        ));
    }

    private function reimport(array $taskIdsGrouped, bool $onlyEdited, bool $useSegmentTimestamp): void
    {
        $languageResourceRepository = new LanguageResourceRepository();
        $taskRepository = TaskRepository::create();
        $queue = new ReimportSegmentsQueue();

        foreach ($taskIdsGrouped as $languageResourceId => $taskIds) {
            $languageResource = $languageResourceRepository->get($languageResourceId);
            $this->io->info('Reimporting segments into ' . $languageResource->getName());

            foreach ($taskIds as $taskId) {
                $task = $taskRepository->get((int) $taskId);
                $this->io->info('Reimporting segments for task ' . $task->getTaskName());

                try {
                    $queue->queueSnapshot(
                        $task->getTaskGuid(),
                        (int) $languageResource->getId(),
                        [
                            ReimportSegmentsOptions::FILTER_ONLY_EDITED => $onlyEdited,
                            ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP => $useSegmentTimestamp,
                        ]
                    );

                    $this->io->info(
                        sprintf(
                            'Scheduled Reimported segments for task %d (%s) and language resource %s',
                            $task->getId(),
                            $task->getTaskGuid(),
                            $languageResource->getId()
                        )
                    );
                } catch (\Exception $e) {
                    $this->io->error(sprintf('Error scheduling of reimport segments for task %s: %s', $task->getTaskGuid(), $e->getMessage()));
                }
            }
        }
    }

    private function getTaskIdsForReimport(string $strategy): array
    {
        if (
            $this->isFilteringByLanguages()
            && ! $this->input->getOption(self::OPTION_LANGUAGE_RESOURCE_ID)
            && ! $this->input->getOption(self::OPTION_TASK_ID)
        ) {
            $this->io->info('Please note that you have provided language filters. ' .
                'The command will only reimport tasks that match the given language pair.');
        }

        if ($this->isFilteringByDateRange() && ! $this->input->getOption(self::OPTION_TASK_ID)) {
            $this->io->info('Please note that you have provided date range filters. ' .
                'The command will only reimport tasks that match the given period.');
        }

        if ($strategy !== 'task') {
            return $this->getTasksByStrategyLanguageResource();
        }

        return $this->getTasksByStrategyTask($this->getLanguageResourceFromOption());
    }

    private function getTasksByStrategyLanguageResource(): array
    {
        $languageResourceId = $this->getLanguageResourceId();

        return $this->getTasksGroupedByLanguageResource(languageResourceId: $languageResourceId);
    }

    private function getTasksByStrategyTask(?int $languageResourceId = null): array
    {
        $strategy = $this->askTaskStrategy();

        if ($strategy === 'all') {
            return $this->getTasksGroupedByLanguageResource(languageResourceId: $languageResourceId);
        }

        return $this->getTasksGroupedByLanguageResource(taskId: $this->askParticularTaskId(), languageResourceId: $languageResourceId);
    }

    private function isFilteringOnlyFinishedTasks(): bool
    {
        return (bool) $this->input->getOption(self::OPTION_FINISHED_TASKS);
    }

    private function getLanguageResourceFromOption(): ?int
    {
        $languageResourceId = null;

        if ($this->input->getOption(self::OPTION_LANGUAGE_RESOURCE_ID)) {
            $languageResourceId = (int) $this->input->getOption(self::OPTION_LANGUAGE_RESOURCE_ID);
        }

        return $languageResourceId;
    }

    private function isFilteringByLanguages(): bool
    {
        $sourceLanguage = $this->input->getOption(self::OPTION_SOURCE_LANGUAGE);
        $targetLanguage = $this->input->getOption(self::OPTION_TARGET_LANGUAGE);

        if (($sourceLanguage !== null && $targetLanguage === null)
            || ($sourceLanguage === null && $targetLanguage !== null)
        ) {
            throw new \RuntimeException('Both source and target language must be provided');
        }

        return $sourceLanguage !== null
            && $targetLanguage !== null;
    }

    private function isFilteringByDateRange(): bool
    {
        return $this->input->getOption(self::OPTION_START_DATE) !== null
            || $this->input->getOption(self::OPTION_END_DATE) !== null;
    }

    private function getStartDate(): \DateTimeImmutable
    {
        $optionStartDate = $this->input->getOption(self::OPTION_START_DATE);

        if (! $optionStartDate) {
            $optionStartDate = '1970-01-01 00:00:00';
        }

        return $this->getDate($optionStartDate);
    }

    private function getEndDate(): \DateTimeImmutable
    {
        $optionEndDate = $this->input->getOption(self::OPTION_END_DATE);

        if (! $optionEndDate) {
            $optionEndDate = (new \DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');
        }

        return $this->getDate($optionEndDate);
    }

    private function getDate(string $dateOption): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateOption);

        if ($date !== false) {
            return $date;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateOption);

        if ($date !== false) {
            return $date;
        }

        throw new \RuntimeException('Invalid date format. Please use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.');
    }

    private function askReimportByStrategy(): string
    {
        if ($this->input->getOption(self::OPTION_TASK_ID)) {
            $this->io->info('Task id provided, single task will be reimported only.');

            return 'task';
        }

        if ($this->input->getOption(self::OPTION_LANGUAGE_RESOURCE_ID)) {
            $this->io->info('Language resource id provided, all tasks for this language resource will be reimported.');

            return 'language resource';
        }

        $question = new ChoiceQuestion(
            'Do you want to start reimport per task or per language resource',
            ['task', 'language resource'],
            'task'
        );

        return $this->io->askQuestion($question);
    }

    private function askTaskStrategy(): string
    {
        if ($this->input->getOption(self::OPTION_TASK_ID)) {
            return 'particular task';
        }

        $question = new ChoiceQuestion(
            'Do you want to start reimport for all tasks or for particular task?',
            ['all', 'particular task'],
            'all'
        );

        return $this->io->askQuestion($question);
    }

    private function askParticularTaskId(): int
    {
        if ($this->input->getOption(self::OPTION_TASK_ID)) {
            return (int) $this->input->getOption(self::OPTION_TASK_ID);
        }

        $tasks = $this->getAllTasksHavingT5memoryAssigned();

        $question = new ChoiceQuestion(
            'Please select the task you want to reimport',
            $tasks,
            'all'
        );

        $chosen = $this->io->askQuestion($question);

        return (int) explode(' - ', $chosen)[0];
    }

    #region queries
    public function getTasksGroupedByLanguageResource(
        int $taskId = null,
        int $languageResourceId = null,
    ): array {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from(
                [
                    'task' => 'LEK_task',
                ],
                'task.id as taskId'
            )
            ->joinLeft(
                [
                    'lrt' => 'LEK_languageresources_taskassoc',
                ],
                'lrt.taskGuid = task.taskGuid',
                'lrt.id as assocId, lrt.segmentsUpdateable'
            )
            ->joinLeft(
                [
                    'lr' => 'LEK_languageresources',
                ],
                'lrt.languageResourceId = lr.id',
                'lr.id as languageResourceId'
            )
            ->where('lr.serviceType = ?', \editor_Services_Manager::SERVICE_T5_MEMORY)
            ->where('lrt.segmentsUpdateable = 1')
        ;

        if ($taskId !== null) {
            $query->where('task.id = ?', $taskId);
        }

        if ($languageResourceId !== null) {
            $query->where('lr.id = ?', $languageResourceId);
        }

        if ($this->isFilteringByLanguages()) {
            $sourceLanguageCode = $this->input->getOption(self::OPTION_SOURCE_LANGUAGE);
            $targetLanguageCode = $this->input->getOption(self::OPTION_TARGET_LANGUAGE);

            $query->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'lr.id = l.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            )
                ->where('l.sourceLangCode = ?', $sourceLanguageCode)
                ->where('l.targetLangCode = ?', $targetLanguageCode);
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if ($this->isFilteringOnlyFinishedTasks()) {
            $taskRepository = TaskRepository::create();
            $guids = $taskRepository->getTaskGuidsFinishedBetween($startDate, $endDate);

            if (empty($guids)) {
                return [];
            }

            $query->where('task.taskGuid IN (?)', $guids);
        } else {
            $query->where('task.created >= ?', $startDate->format('Y-m-d H:i:s'))
                ->where('task.created <= ?', $endDate->format('Y-m-d H:i:s'));
        }

        $result = [];
        foreach ($db->fetchAll($query) as $row) {
            $result[$row['languageResourceId']][] = $row['taskId'];
        }

        return $result;
    }

    private function getAllTasksHavingT5memoryAssigned(): array
    {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from([
                'task' => 'LEK_task',
            ], ['task.id as taskId', 'task.taskName'])
            ->joinLeft(
                [
                    'lrt' => 'LEK_languageresources_taskassoc',
                ],
                'lrt.taskGuid = task.taskGuid',
                'lrt.id as assocId, lrt.segmentsUpdateable'
            )
            ->joinLeft(
                [
                    'lr' => 'LEK_languageresources',
                ],
                'lrt.languageResourceId = lr.id',
                'lr.id as languageResourceId'
            )
            ->where('lr.serviceType = ?', \editor_Services_Manager::SERVICE_T5_MEMORY)
            ->where('lrt.segmentsUpdateable = 1')
            ->group('task.id');

        if ($this->isFilteringByLanguages()) {
            $sourceLanguageCode = $this->input->getOption(self::OPTION_SOURCE_LANGUAGE);
            $targetLanguageCode = $this->input->getOption(self::OPTION_TARGET_LANGUAGE);

            $query->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'lr.id = l.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            )
                ->where('l.sourceLangCode = ?', $sourceLanguageCode)
                ->where('l.targetLangCode = ?', $targetLanguageCode);
        }

        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if ($this->isFilteringOnlyFinishedTasks()) {
            $taskRepository = TaskRepository::create();
            $guids = $taskRepository->getTaskGuidsFinishedBetween($startDate, $endDate);

            if (empty($guids)) {
                return [];
            }

            $query->where('task.taskGuid IN (?)', $guids);
        } else {
            $query->where('task.created >= ?', $startDate->format('Y-m-d H:i:s'))
                ->where('task.created <= ?', $endDate->format('Y-m-d H:i:s'));
        }

        $result = [];
        foreach ($db->fetchAll($query) as $row) {
            $result[] = $row['taskId'] . ' - ' . $row['taskName'];
        }

        return $result;
    }

    protected function getLanguageResourceId(): int
    {
        $optionLanguageResourceId = $this->getLanguageResourceFromOption();
        if ($optionLanguageResourceId !== null) {
            return $optionLanguageResourceId;
        }

        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from(
                [
                    'lr' => 'LEK_languageresources',
                ],
                ['lr.id as languageResourceId', 'lr.name as languageResourceName']
            )
            ->joinLeft(
                [
                    'l' => 'LEK_languageresources_languages',
                ],
                'lr.id = l.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            )
            ->where('lr.serviceName = ?', Service::NAME);

        if ($this->isFilteringByLanguages()) {
            $sourceLanguageCode = $this->input->getOption(self::OPTION_SOURCE_LANGUAGE);
            $targetLanguageCode = $this->input->getOption(self::OPTION_TARGET_LANGUAGE);
            $query
                ->where('l.sourceLangCode = ?', $sourceLanguageCode)
                ->where('l.targetLangCode = ?', $targetLanguageCode);
        }

        $tmsList = [];
        foreach ($db->fetchAll($query) as $item) {
            $tmsList[$item['languageResourceId']] = sprintf(
                '%s [ID %d] [ %s -> %s ]',
                $item['languageResourceName'],
                $item['languageResourceId'],
                $item['sourceLangCode'],
                $item['targetLangCode']
            );
        }

        $askMemories = new ChoiceQuestion('Please choose a Memory:', array_values($tmsList), null);
        $tmName = $this->io->askQuestion($askMemories);

        return array_search($tmName, $tmsList);
    }
    #endregion queries
}
