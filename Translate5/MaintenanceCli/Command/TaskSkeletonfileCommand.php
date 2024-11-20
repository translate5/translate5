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
class TaskSkeletonfileCommand extends TaskCommand
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
            ->setHelp('A task-identifier must be given, then the available skeleton files could be listed or the content of one or all skeleton files can be shown.');

        $this->addArgument(
            'taskIdentifier',
            InputArgument::REQUIRED,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );

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
            'Dumps one raw file for redirecting on CLI, needs the file-id as argument'
        );

        $this->addOption(
            'save-all',
            's',
            InputOption::VALUE_NONE,
            'Unpacks all files to the source-directory with additional extension ".xliff"'
        );

        $this->addOption(
            'pack-all',
            'p',
            InputOption::VALUE_NONE,
            'Packs all files in the source-directory that have the additional extension ".xliff". Only existing files will be packed'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $silent = ! empty($this->input->getOption('dump-one'));

        $task = static::findTaskFromArgument(
            $this->io,
            $input->getArgument('taskIdentifier'),
            $silent,
            TaskCommand::taskTypesWithData()
        );

        if ($task === null) {
            return self::FAILURE;
        }

        if (! $silent) {
            $this->writeTitle('Task Skeletonfiles');
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
        $saveAll = ! $dumpAll && $this->input->getOption('save-all');
        $packAll = ! $saveAll && $this->input->getOption('pack-all');

        $skeletonFile = new SkeletonFile($task);
        if ($dumpAll || $saveAll || $packAll) {
            foreach ($files as $fileId => $path) {
                $file = new \editor_Models_File();
                $file->load($fileId);
                if ($packAll) {
                    $packedFile = $skeletonFile->getSkeletonPath($file);
                    $unpackedFile = $packedFile . '.xliff';
                    if (file_exists($unpackedFile)) {
                        unlink($packedFile);
                        $skeletonFile->saveToDisk($file, file_get_contents($unpackedFile));
                        $this->io->success('Repacked file ' . $fileId . ' from ' . basename($unpackedFile));
                    }
                } else {
                    $skel = $skeletonFile->loadFromDisk($file);
                    if ($dumpAll) {
                        $this->io->section($fileId . ': ' . $path);
                        $this->io->write($skel);
                    } else {
                        $unpackedFile = $skeletonFile->getSkeletonPath($file) . '.xliff';
                        @file_put_contents($unpackedFile, $skel);
                        $this->io->success('Saved file ' . $fileId . ' as ' . basename($unpackedFile));
                    }
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
