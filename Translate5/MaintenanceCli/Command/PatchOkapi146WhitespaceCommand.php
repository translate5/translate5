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

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Task;
use editor_Task_Type;
use MittagQI\Translate5\Task\Import\SkeletonFile;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\FixScript\MissingMrkSegmentsFixer;
use Zend_Db_Table;

class PatchOkapi146WhitespaceCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'patch:okapi146whitespace';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Analyzes tasks data for lost whitespaces.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Analyzes tasks data for lost whitespaces.');

        $this->addOption(
            'fix',
            null,
            InputOption::VALUE_NONE,
            'Fix the skeleton files'
        );

        $this->addOption(
            'taskId',
            't',
            InputOption::VALUE_OPTIONAL,
            'Provide Task Id for which the data should be analyzed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $fixIt = (bool) $this->input->getOption('fix');
        $taskId = (int) $input->getOption('taskId');

        $this->io->warning(
            'The existing task' . ($taskId ? '' : 's') . ' data will be ' . ($fixIt ? ' fixed adding' : 'analyzed for') .
            ' lost whitespaces by walking over all skeleton files.' .
            ($taskId ? '' : ' This can take a while.')
        );
        if ($this->input->isInteractive() && ! $this->io->confirm('Do you really want to proceed?', false)) {
            return self::SUCCESS;
        }

        $db = Zend_Db_Table::getDefaultAdapter();

        if (! empty($taskId)) {
            if (empty($db->fetchOne('SELECT taskGuid FROM LEK_task WHERE id=' . $taskId))) {
                $this->io->warning("Could not find task with id $taskId");

                return self::FAILURE;
            }
        }

        $this->io->writeln(($fixIt ? 'Processing' : 'Analyzing') . " tasks..\n");

        $allTasks = $db->fetchCol(
            'SELECT taskGuid FROM LEK_task WHERE ' .
            ($taskId ? 'id=' . $taskId : 'taskType IN ("' .
                implode('","', editor_Task_Type::getInstance()->getTaskTypes()) . '") ORDER BY id')
        );

        $progressBar = new ProgressBar($output, count($allTasks));
        $mrkFinder = new MissingMrkSegmentsFixer($fixIt);

        $stats = [
            'taskGuids' => [],
            'filesCount' => 0,
        ];

        foreach ($allTasks as $taskGuid) {
            $taskFiles = $db->fetchCol('SELECT id FROM LEK_files WHERE taskGuid="' . $taskGuid . '"');
            $task = new editor_Models_Task();
            $task->loadByTaskGuid($taskGuid);
            foreach ($taskFiles as $fileId) {
                $zlibPath = $task->getAbsoluteTaskDataPath() . sprintf(SkeletonFile::SKELETON_PATH, $fileId);
                if (is_file($zlibPath)) {
                    try {
                        $found = $mrkFinder->hasMissingMrkSegments(gzuncompress(file_get_contents($zlibPath)));
                    } catch (\editor_Models_Import_FileParser_InvalidXMLException $e) {
                        $this->io->warning('Invalid XML in task ' . $taskGuid . ' file: ' . $zlibPath);

                        continue;
                    }

                    if ($found) {
                        // gather stats
                        $stats['filesCount']++;
                        if (! isset($stats['taskGuids'][$taskGuid])) {
                            $stats['taskGuids'][$taskGuid] = 1;
                        }
                    } elseif ($fixIt) {
                        $fixedData = $mrkFinder->getFixedData();
                        if (! empty($fixedData)) {
                            $tempFile = $zlibPath . '.tmp';
                            if (file_put_contents($tempFile, gzcompress($fixedData)) === false) {
                                $this->io->error("Error writing to file: " . basename($tempFile));

                                return self::FAILURE;
                            }
                            if (copy($zlibPath, $zlibPath . '.146whitespace.bak') === false) {
                                $this->io->error("Error backing up file: " . basename($zlibPath));

                                return self::FAILURE;
                            }
                            if (rename($tempFile, $zlibPath) === false) {
                                $this->io->error("Error creating file: " . basename($zlibPath));

                                return self::FAILURE;
                            }
                        }
                    }
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->writeln("\n");

        $this->io->writeln(
            "Tasks affected: " . count($stats['taskGuids']) . ", files affected: " . $stats['filesCount']
        );
        if (! empty($stats['taskGuids'])) {
            $okapiServers = $db->fetchPairs(
                'SELECT `value`,COUNT(*) FROM LEK_task_config' .
                ' WHERE name="runtimeOptions.plugins.Okapi.serverUsed" AND taskGuid IN ("' . implode(
                    '","',
                    array_keys($stats['taskGuids'])
                ) . '") GROUP BY `value`'
            );

            if (! empty($okapiServers)) {
                $this->io->writeln("Okapi version(s):");
                foreach ($okapiServers as $server => $count) {
                    $this->io->writeln("$server ($count)");
                }
            }
        }

        $this->io->success('Processing done');

        return self::SUCCESS;
    }
}
