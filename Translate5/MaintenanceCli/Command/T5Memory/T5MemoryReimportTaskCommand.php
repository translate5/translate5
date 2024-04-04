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

use editor_Models_Task as Task;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_OpenTM2_Service as Service;
use MittagQI\Translate5\LanguageResource\ReimportSegments;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use ZfExtended_Factory as Factory;

class T5MemoryReimportTaskCommand extends Translate5AbstractCommand
{
    private const OPTION_USE_SEGMENT_TIMESTAMP = 'use-segment-timestamp';
    private const OPTION_SOURCE_LANGUAGE  = 'source-language';
    private const OPTION_TARGET_LANGUAGE  = 'target-language';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('t5memory:reimport-task')
            ->setDescription('Reimport task segments into the t5memory.' .
                ' Reimports only those segments, that previously have been manually saved by a user.')
            ->addOption(
                self::OPTION_USE_SEGMENT_TIMESTAMP,
                null,
                InputOption::VALUE_NEGATABLE,
                'Use segment timestamp for reimport, otherwise current time is used',
                false
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
            );
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

        foreach ($taskIdsGrouped as $languageResourceId => $taskIds) {
            $languageResource = Factory::get(LanguageResource::class);
            $languageResource->load($languageResourceId);
            $this->io->info('Reimporting segments into ' . $languageResource->getName());

            foreach ($taskIds as $taskId) {
                $task = Factory::get(Task::class);
                $task->load($taskId);
                $this->io->info('Reimporting segments for task ' . $task->getTaskName());

                $reimport = new ReimportSegments($languageResource, $task);
                $reimport->reimport([
                    ReimportSegments::FILTER_ONLY_EDITED => true,
                    ReimportSegments::USE_SEGMENT_TIMESTAMP =>
                        (bool) $input->getOption(self::OPTION_USE_SEGMENT_TIMESTAMP),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function getTaskIdsForReimport(string $strategy): array
    {
        if ($this->isFilteringByLanguages()) {
            $this->io->info('Please note that you have provided language filters. ' .
                'The command will only reimport tasks that match the given language pair.');
        }

        if ($strategy !== 'task') {
            return $this->getTasksByStrategyLanguageResource();
        }

        return $this->getTasksByStrategyTask();
    }

    private function getTasksByStrategyLanguageResource(): array
    {
        $languageResourceId = $this->getLanguageResourceId();

        return $this->getTasksGroupedByLanguageResource(languageResourceId: $languageResourceId);
    }

    private function getTasksByStrategyTask(): array
    {
        $strategy = $this->askTaskStrategy();

        if ($strategy === 'all') {
            return $this->getTasksGroupedByLanguageResource();
        }

        return $this->getTasksGroupedByLanguageResource(taskId: $this->askParticularTaskId());
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

    private function askReimportByStrategy(): string
    {
        $question = new ChoiceQuestion(
            'Do you want to start reimport per task or per language resource',
            ['task', 'language resource'],
            'task'
        );
        return $this->io->askQuestion($question);
    }

    private function askTaskStrategy(): string
    {
        $question = new ChoiceQuestion(
            'Do you want to start reimport for all tasks or for particular task?',
            ['all', 'particular task'],
            'all'
        );

        return $this->io->askQuestion($question);
    }

    private function askParticularTaskId(): int
    {
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
        int $languageResourceId = null
    ): array {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from(['task' => 'LEK_task'], 'task.id as taskId')
            ->joinLeft(
                ['lrt' => 'LEK_languageresources_taskassoc'],
                'lrt.taskGuid = task.taskGuid',
                'lrt.id as assocId'
            )
            ->joinLeft(
                ['lr' => 'LEK_languageresources'],
                'lrt.languageResourceId = lr.id',
                'lr.id as languageResourceId'
            )
            ->where('lr.serviceType = ?', \editor_Services_Manager::SERVICE_OPENTM2);

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
                    ['l' => 'LEK_languageresources_languages'],
                    'lr.id = l.languageResourceId',
                    ['sourceLangCode', 'targetLangCode']
                )
                ->where('l.sourceLangCode = ?', $sourceLanguageCode)
                ->where('l.targetLangCode = ?', $targetLanguageCode);
        }

        $result = [];
        foreach ($db->fetchAll($query) as $row) {
            $result[$row['languageResourceId']][] = $row['taskId'];
        }

        return $result;
    }

    private function getAllTasksHavingT5memoryAssigned()
    {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from(['task' => 'LEK_task'], ['task.id as taskId', 'task.taskName'])
            ->joinLeft(
                ['lrt' => 'LEK_languageresources_taskassoc'],
                'lrt.taskGuid = task.taskGuid',
                'lrt.id as assocId'
            )
            ->joinLeft(
                ['lr' => 'LEK_languageresources'],
                'lrt.languageResourceId = lr.id',
                'lr.id as languageResourceId'
            )
            ->where('lr.serviceType = ?', \editor_Services_Manager::SERVICE_OPENTM2)
            ->group('task.id');

        if ($this->isFilteringByLanguages()) {
            $sourceLanguageCode = $this->input->getOption(self::OPTION_SOURCE_LANGUAGE);
            $targetLanguageCode = $this->input->getOption(self::OPTION_TARGET_LANGUAGE);

            $query->joinLeft(
                ['l' => 'LEK_languageresources_languages'],
                'lr.id = l.languageResourceId',
                ['sourceLangCode', 'targetLangCode']
            )
                ->where('l.sourceLangCode = ?', $sourceLanguageCode)
                ->where('l.targetLangCode = ?', $targetLanguageCode);
        }

        $result = [];
        foreach ($db->fetchAll($query) as $row) {
            $result[] = $row['taskId'] . ' - ' . $row['taskName'];
        }

        return $result;
    }

    protected function getLanguageResourceId(): int
    {
        /** @var \Zend_Db_Table $db */
        $db = \Zend_Registry::get('db');
        $query = $db->select()
            ->from(
                ['lr' => 'LEK_languageresources'],
                ['lr.id as languageResourceId', 'lr.name as languageResourceName']
            )
            ->joinLeft(
                ['l' => 'LEK_languageresources_languages'],
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
                '%s [ %s -> %s ]',
                $item['languageResourceName'],
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
