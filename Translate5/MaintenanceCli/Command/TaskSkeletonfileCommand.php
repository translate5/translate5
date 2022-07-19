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
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Output\TaskTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\RuntimeException;

class TaskSkeletonfileCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:skeletonfile';
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('');

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

        if(!$this->input->getOption('dump-one')) {
            $this->writeTitle('Task Skeletonfiles');
        }

        $task = new \editor_Models_Task();
        $id = $input->getArgument('identifier');
        if(is_numeric($id)) {
            $task->load($id);
        }
        else {
            $id = trim($id, '{}');
            $task->loadByTaskGuid('{'.$id.'}');
        }


        $fileTree = new \editor_Models_Foldertree();
        $files = $fileTree->getPaths($task->getTaskGuid(), $fileTree::TYPE_FILE);

        if($this->input->getOption('list-files')) {
            $this->io->section('Available files');
            $data = [];
            foreach($files as $fileId => $path) {
                $data[] = [$fileId, $path];
            }
            $this->io->table(['id', 'path'], $data);
            return 0;
        }

        if($this->input->getOption('dump-all')) {
            foreach($files as $fileId => $path) {
                $file = new \editor_Models_File();
                $file->load($fileId);
                $this->io->section($fileId.': '.$path);
                $skel = $file->loadSkeletonFromDisk($task);
                $this->io->write($skel);
            }
            return 0;
        }

        if(!($fileId = $this->input->getOption('dump-one'))) {
            $data = [];
            foreach($files as $fileId => $path) {
                $data[] = $fileId.': '.$path;
            }
            $fileId = $this->io->choice('Dump which file?', $data);
            $fileId = explode(':', $fileId)[0];
        }

        $file = new \editor_Models_File();
        $file->load($fileId);
        echo $file->loadSkeletonFromDisk($task);
        return 0;
    }
}
