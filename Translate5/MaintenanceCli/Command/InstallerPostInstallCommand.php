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

use DirectoryIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class InstallerPostInstallCommand extends Translate5AbstractCommand
{
    private const string SKIPPED = 'skipped';
    private const string PROCESSED = 'processed';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'installer:post-install';

    protected function configure(): void
    {
        $this
            ->setDescription('Executes post installation scripts from application/post-installation-scripts')
            ->setHelp(
                'Executes post installation / update scripts from application/post-installation-scripts.

Files have to end on .update.t5cli or .installation.t5cli and may contain only t5 CLI commands!

    .update.t5cli files are called only on updates,
    .installation.t5cli on installations only

this is controlled by the --update option'
            );

        $this->addOption(
            'update',
            null,
            InputOption::VALUE_NONE,
            'Executes post update files only, if omitted (default) only installation files are executed.'
        );

        $this->addOption(
            'not-done',
            null,
            InputOption::VALUE_NONE,
            'Do not log the files as executed in data/logs/post-installation-scripts-executed.log'
        );

        $this->addOption(
            'list-executed',
            'l',
            InputOption::VALUE_NONE,
            'List the executed scripts as logged in the log'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $postScriptDir = APPLICATION_PATH . '/post-installation-scripts';
        if (! is_dir($postScriptDir)) {
            throw new RuntimeException('Directory not found: ' . $postScriptDir);
        }

        $this->getNewScriptFiles($postScriptDir);

        return self::SUCCESS;
    }

    private function getNewScriptFiles(string $postScriptDir): void
    {
        $executedFilesLog = APPLICATION_DATA . '/logs/post-installation-scripts-executed.log';
        $loadedFiles = $this->loadExecutedFiles($executedFilesLog);

        if ($this->input->getOption('list-executed')) {
            $table = $this->io->createTable();
            $table->setHeaders(['File', 'Timestamp and status']);
            foreach ($loadedFiles as $id => $file) {
                $table->addRow([$id, $file]);
            }
            $table->render();
            return;
        }


        foreach (new DirectoryIterator($postScriptDir) as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if ($fileInfo->isDot()
                || $fileInfo->isDir()
                || $fileInfo->isFile()
                && ! $this->isFileToProcess($fileInfo)
            ) {
                continue;
            }

            if (array_key_exists($filename, $loadedFiles)) {
                continue;
            }

            if (! $this->isTypeToProcess($fileInfo)) {
                $this->io->info('Skipped ' . $filename);
                $this->logAsProcessed($executedFilesLog, $filename, self::SKIPPED);

                continue;
            }

            $this->io->info('Executing ' . $filename);
            $commands = file($postScriptDir . '/' . $fileInfo);
            $step = 0;
            foreach ($commands as $command) {
                //allow comments
                if (preg_match('/^\s*(#|;|\/\/)/', $command)) {
                    continue;
                }
                $step++;
                //commands must run in own php process
                passthru('./translate5.sh ' . $command, $result_code);
                if ($result_code === 0) {
                    $this->io->success('Finished successful step ' . $step . ' of ' . $filename . '.');
                } else {
                    $this->io->warning(
                        'Finished step ' . $step . ' of ' . $filename . ' with result code ' . $result_code
                    );
                }
            }

            $this->logAsProcessed($executedFilesLog, $filename, self::PROCESSED);
        }
    }

    protected function isFileToProcess(SplFileInfo $file): bool
    {
        $suffix = strtolower(substr($file->getFilename(), -6));

        return $suffix === '.t5cli';
    }

    protected function isTypeToProcess(SplFileInfo $file): bool
    {
        $fileName = strtolower($file->getFilename());

        if ($this->input->getOption('update')) {
            return str_ends_with($fileName, '.update.t5cli');
        }

        return str_ends_with($fileName, '.installation.t5cli');
    }

    private function loadExecutedFiles(string $executedFilesLog): array
    {
        $result = [];
        if (! is_file($executedFilesLog)) {
            return $result;
        }
        $executedFiles = file($executedFilesLog, FILE_IGNORE_NEW_LINES);
        foreach ($executedFiles as $file) {
            $line = explode('#', $file);
            $result[$line[1]] = $line[0];
            if(array_key_exists(2, $line)) {
                $result[$line[1]] .= ': '.$line[2];
            }
        }

        return $result;
    }

    private function logAsProcessed(string $executedFilesLog, $filename, string $type): void
    {
        if (! $this->input->getOption('not-done')) {
            error_log(date('Y-m-d H:i:s') . '#' . $filename . '#' . $type . PHP_EOL, 3, $executedFilesLog);
        }
    }
}
