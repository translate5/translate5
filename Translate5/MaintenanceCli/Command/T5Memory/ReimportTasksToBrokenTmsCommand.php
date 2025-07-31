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

use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;

class ReimportTasksToBrokenTmsCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 't5memory:reimport:tasks-to-broken-tms';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('reimport:tasks-to-broken-tms')
            ->setDescription('Re-imports tasks into task memories based on a list of TMs')
            ->addArgument(
                'list',
                InputArgument::REQUIRED,
                'Path to file with list of TM names'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $list = fopen($input->getArgument('list'), 'r');

        $this->io->block('Re-importing tasks into task memories...', null, 'fg=white;bg=blue', ' ', true);

        $persistenceService = PersistenceService::create();
        $tmPrefix = $persistenceService->addTmPrefix('');

        if ('' === $tmPrefix) {
            $question = new ConfirmationQuestion(
                'TM prefix is not set. Usage of command is too dangerous. Sure you want to proceed?',
                false
            );

            $proceed = $this->io->askQuestion($question);

            if (! $proceed) {
                $this->io->info('Aborting command execution due to missing TM prefix.');

                return self::SUCCESS;
            }
        }

        $toRestore = [];

        while ($line = fgets($list)) {
            // check if line starts with $tmPrefix
            if (strpos($line, $tmPrefix) !== 0) {
                continue;
            }

            if (! preg_match('/ID(\d+)-Task TM id _(\d+)_/', $line, $match)) {
                continue;
            }

            $toRestore[] = [
                'lrId' => (int) $match[1],
                'taskId' => (int) $match[2],
                'tm' => $line,
            ];
        }

        $table = $this->io->createTable();
        $table->setHeaders(['TM', 'Task ID', 'Language Resource ID']);
        foreach ($toRestore as $row) {
            $table->addRow([$row['tm'], $row['taskId'], $row['lrId']]);
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

        $queue = new ReimportSegmentsQueue();
        $taskRepository = TaskRepository::create();
        $lrRepo = LanguageResourceRepository::create();

        foreach ($toRestore as $row) {
            try {
                $task = $taskRepository->get($row['taskId']);
            } catch (InexistentTaskException) {
                $this->io->warning(sprintf('Task with ID %s not found', $row['taskId']));

                continue;
            }

            try {
                $languageResource = $lrRepo->get($row['lrId']);
            } catch (\ZfExtended_Models_Entity_NotFoundException) {
                $this->io->warning(sprintf('Language resource with ID %s not found', $row['lrId']));

                continue;
            }

            try {
                $queue->queueSnapshot(
                    $task->getTaskGuid(),
                    (int) $languageResource->getId(),
                    [
                        ReimportSegmentsOptions::FILTER_ONLY_EDITED => true,
                        ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP => true,
                    ]
                );
            } catch (\Exception $e) {
                $this->io->error(sprintf(
                    'Error while queuing task %s : %s for reimport: %s',
                    $task->getId(),
                    $task->getTaskGuid(),
                    $e->getMessage()
                ));

                continue;
            }
        }

        return self::SUCCESS;
    }
}
