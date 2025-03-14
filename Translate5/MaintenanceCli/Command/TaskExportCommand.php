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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskExportCommand extends TaskCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:export';

    protected function configure()
    {
        $this
            ->setDescription('Exports a task by given id / taskGuid, optionally only specific files, filtered by fileId')
            ->setHelp('Exports a task by given id / taskGuid, optionally only specific files, filtered by fileId');

        $this->addArgument(
            'identifier',
            InputArgument::REQUIRED,
            'Either a complete numeric task ID or or taskGuid'
        );

        $this->addOption(
            'file',
            'f',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'give one or more file ids, to export only that files instead all of them',
            []
        );

        $this->addOption(
            'diff',
            'd',
            InputOption::VALUE_NEGATABLE,
            'give one or more file ids, to export only that files instead all of them',
            false,
        );

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Give the output directory, must exist. Otherwise data is written to data/cliexport/',
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $this->writeTitle('Task Export');

        try {
            $task = static::findTaskFromArgument(
                $this->io,
                $input->getArgument('identifier'),
            );
        } catch (\Zend_Exception $e) {
            $this->io->error('Task not found');

            return self::FAILURE;
        }

        $export = new \editor_Models_Export();
        $export->setTaskToExport($task, (bool) $input->getOption('diff'));

        $target = $input->getOption('output') ?? \APPLICATION_DATA . '/cliexport';
        if (file_exists($target)) {
            if (! is_dir($target)) {
                $this->io->error('Target "' . $target . '" is not a directory');

                return self::FAILURE;
            }
            if (! is_writable($target)) {
                $this->io->error('Target "' . $target . '" is not writable');

                return self::FAILURE;
            }
        } else {
            if (! mkdir($target, 0777, true)) {
                $this->io->error('Target directory "' . $target . '" is not writable');

                return self::FAILURE;
            }
        }

        $fileIds = $input->getOption('file');

        $export->export($target, 0, fileIdFilter: $fileIds, cleanTarget: false);

        $output = [];
        $output[] = 'Writing task ' . ' (' . $task->getId() . ' / ' . $task->getTaskGuid() . ')';
        $output[] = '  name ' . $task->getTaskName();
        $output[] = '  to target ' . $target;
        if (! empty($fileIds)) {
            $fileTree = new \editor_Models_Foldertree();
            $files = $fileTree->getPaths($task->getTaskGuid(), $fileTree::TYPE_FILE);
            foreach ($files as $fileId => $path) {
                if (in_array($fileId, $fileIds)) {
                    $output[] = ' with file (' . $fileId . ') ' . $path;
                }
            }
        }
        $this->io->success($output);

        return self::SUCCESS;
    }
}
