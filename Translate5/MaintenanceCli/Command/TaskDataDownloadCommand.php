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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Application;
use ZipArchive;

/**
 * Download a task's data-dir
 * TODO FIXME: with ext-pcntl php-extension we could listen to process-signals to clean the temp-data
 */
class TaskDataDownloadCommand extends TaskCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:downloaddata';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Download a tasks data-directory')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('A task-identifier must be given, then the task can be downloaded via URL. Note, that the download will be deleted, when the command terminates.');

        $this->addArgument(
            'taskIdentifier',
            InputArgument::REQUIRED,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $task = static::findTaskFromArgument(
            $this->io,
            $input->getArgument('taskIdentifier'),
            false,
            TaskCommand::taskTypesWithData()
        );

        if ($task === null) {
            return self::FAILURE;
        }

        $this->writeTitle('Download task data');

        // task's data-dir
        $dataPath = realpath(rtrim($task->getAbsoluteTaskDataPath(), '/'));
        $targetFilename = 'task-' . trim($task->getTaskGuid(), '{}');
        // we store the file to the public dir temporarily
        $targetPath = APPLICATION_ROOT . '/public/' . $targetFilename;

        // Initialize archive object
        $zip = new ZipArchive();
        if ($zip->open($targetPath, ZipArchive::CREATE) !== true) {
            $this->io->error('Failed to create archive ' . $targetPath);

            return static::FAILURE;
        }

        // Create recursive directory iterator and add files
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dataPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (! $file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($dataPath) + 1);
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        // offer the zip for downlaod and remove the zip when the command ends
        $this->io->success('You can Download the task-data with the following link:');
        $this->io->writeln(ZfExtended_Application::createUrl() . '/' . $targetFilename);
        $this->io->warning(
            "After you downloaded, please confirm the prompt, this removes the temporary data.\n"
            . 'Do NEVER terminate this command otherwise as '
            . 'this would leave task-data in the public directory of the server!'
        );
        // crucial: remove the data from the servers public dir!
        if ($this->io->confirm('Finished downlaoding ?')) {
            @unlink($targetPath);
        } else {
            @unlink($targetPath);
        }

        return static::SUCCESS;
    }
}
