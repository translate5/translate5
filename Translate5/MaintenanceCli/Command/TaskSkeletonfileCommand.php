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

use MittagQI\Translate5\Task\Import\SkeletonFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * List and show the content of a tasks import data skeleton file(s)
 */
class TaskSkeletonfileCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:skeletonfile';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('List and show the content of a tasks import data skeleton file(s)')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Task ID or GUID must be given, then the available skeleton files could be listed or the content of one or all skeleton files can be shown.');

        $this->addArgument('identifier', InputArgument::REQUIRED, 'Either a complete numeric task ID or the task GUID (with or without curly braces)');

        $this->addOption(
            'list-files',
            'l',
            InputOption::VALUE_NONE,
            'List the available files only'
        );

        $this->addOption(
            'dump-all',
            'a',
            InputOption::VALUE_NONE,
            'Dumps all files with file names as sections - output not usable as plain file after redirecting'
        );

        $this->addOption(
            'dump-one',
            'd',
            InputOption::VALUE_REQUIRED,
            'Dumps one raw file for redirecting on CLI, needs the fileid as argument'
        );

        $this->addOption(
            'save-all',
            's',
            InputOption::VALUE_NONE,
            'Unpacks all files to the source-directory with additional extension ".xliff"'
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
        $this->initTranslate5();

        if (! $this->input->getOption('dump-one')) {
            $this->writeTitle('Task Skeletonfiles');
        }

        $task = new \editor_Models_Task();
        $id = $input->getArgument('identifier');
        if (is_numeric($id)) {
            $task->load($id);
        } else {
            $id = trim($id, '{}');
            $task->loadByTaskGuid('{' . $id . '}');
        }

        $fileTree = new \editor_Models_Foldertree();
        $files = $fileTree->getPaths($task->getTaskGuid(), $fileTree::TYPE_FILE);

        if ($this->input->getOption('list-files')) {
            $this->io->section('Available files');
            $data = [];
            foreach ($files as $fileId => $path) {
                $data[] = [$fileId, $path];
            }
            $this->io->table(['id', 'path'], $data);

            return 0;
        }

        $dumpAll = $this->input->getOption('dump-all');
        $saveAll = $this->input->getOption('save-all');

        $skeletonFile = new SkeletonFile($task);
        if ($dumpAll || $saveAll) {
            foreach ($files as $fileId => $path) {
                $file = new \editor_Models_File();
                $file->load($fileId);

                $skel = $skeletonFile->loadFromDisk($file);
                if ($dumpAll) {
                    $this->io->section($fileId . ': ' . $path);
                    $this->io->write($skel);
                } else {
                    $tmpName = $skeletonFile->getSkeletonPath($file) . '.xliff';
                    @file_put_contents($tmpName, $skel);
                    $this->io->success('saved file ' . $fileId . ' as ' . basename($tmpName));
                }
            }

            return static::SUCCESS;
        }

        if (! ($fileId = $this->input->getOption('dump-one'))) {
            $data = [];
            foreach ($files as $fileId => $path) {
                $data[] = $fileId . ': ' . $path;
            }
            $fileId = $this->io->choice('Dump which file?', $data);
            $fileId = explode(':', $fileId)[0];
        }

        $file = new \editor_Models_File();
        $file->load($fileId);
        echo $skeletonFile->loadFromDisk($file);

        return static::SUCCESS;
    }
}
