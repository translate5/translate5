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

namespace Translate5\MaintenanceCli\Command\T5Memory;

use DateTime;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\Workflow\Executors\ReimportSegmentsActionExecutor;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class ReimportFinishedTasksCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:reimport:finished-tasks';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('reimport:finished-tasks')
            ->setDescription('Re-imports finished tasks into master memories based on task logs')
            ->addOption(
                'start-date',
                's',
                InputOption::VALUE_REQUIRED,
                'Start date for filtering task logs (format: YYYY-MM-DD HH:MM:SS)',
            )
            ->addOption(
                'end-date',
                'e',
                InputOption::VALUE_REQUIRED,
                'End date for filtering task logs (format: YYYY-MM-DD HH:MM:SS)',
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $db = \Zend_Db_Table::getDefaultAdapter();
        $logger = \Zend_Registry::get('logger');
        $taskRepository = TaskRepository::create();
        $queue = new ReimportSegmentsQueue();
        $languageResourceRepository = new LanguageResourceRepository();
        $reimportSegmentsActionExecutor = new ReimportSegmentsActionExecutor(
            $logger,
            $queue,
            $languageResourceRepository,
            new TaskTmRepository(),
        );

        $startDate = $input->getOption('start-date');
        $endDate = $input->getOption('end-date');

        if (
            ! DateTime::createFromFormat('Y-m-d H:i:s', $startDate)
            || ! DateTime::createFromFormat('Y-m-d H:i:s', $endDate)
        ) {
            $this->io->error('Invalid date format. Please use YYYY-MM-DD HH:MM:SS.');

            return self::FAILURE;
        }

        $s = $db->select()
            ->from('LEK_task_log', ['taskGuid', 'created'])
            ->where("message like 'job status changed from % to finished'")
            ->where('created > ?', $startDate)
            ->where('created < ?', $endDate)
        ;

        $this->io->block('Re-importing tasks into master memories...', null, 'fg=white;bg=blue', ' ', true);

        $rows = $db->fetchAll($s);
        $guids = array_column($rows, 'taskGuid');

        $table = $this->io->createTable();
        $table->setHeaders(['Task Guid', 'Finished At']);
        foreach ($rows as $row) {
            $table->addRow([$row['taskGuid'], $row['created']]);
        }
        $table->render();

        $this->io->writeln('');

        $question = new ConfirmationQuestion(
            'Do you want to start reimport?',
            false
        );

        $proceed = $this->io->askQuestion($question);

        if (! $proceed) {
            $this->io->info('Reimport cancelled.');

            return self::SUCCESS;
        }

        foreach ($guids as $guid) {
            try {
                $task = $taskRepository->getByGuid($guid);
            } catch (InexistentTaskException) {
                $this->io->warning(sprintf('Task with GUID %s not found', $guid));

                continue;
            }

            try {
                $reimportSegmentsActionExecutor->reimportSegments($task);
                $this->io->info(sprintf('Scheduled Reimported segments for task %s', $guid));
            } catch (\Exception $e) {
                $this->io->error(sprintf('Error scheduling of reimport segments for task %s: %s', $guid, $e->getMessage()));
            }
        }

        return self::SUCCESS;
    }
}
