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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Output\TaskTable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Exception\RuntimeException;


//FIXME https://github.com/bamarni/symfony-console-autocomplete

class TaskCleanCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:clean';
    
    /**
     * @var InputInterface
     */
    protected $input;
    
    /**
     * @var OutputInterface
     */
    protected $output;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('provides information about and the possibility to delete hanging import / erroneous tasks and orphaned task data directories')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Called with out parameters a overview of affected tasks is shown');
        
        $this->addOption(
            'delete-error',
            'e',
            InputOption::VALUE_OPTIONAL,
            'deletes one (with ID) or all tasks with errors',
            false);
        
        $this->addOption(
            'delete-import',
            'i',
            InputOption::VALUE_REQUIRED,
            'deletes one task in state import');
        
        $this->addOption(
            'set-to-error',
            null,
            InputOption::VALUE_REQUIRED,
            'sets a task to status error (for example to gain access to clone/delete/download of a hanging import task)');
        
        $this->addOption(
            'delete-data',
            'd',
            InputOption::VALUE_NONE,
            'deletes all orphaned data folders');
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
        
        $this->writeTitle('Cleaning up tasks (errors, hanging imports, orphaned data directories)');
        
        $task = new \editor_Models_Task();
        $allTasks = $task->loadAll();
        $availableDataDirs = [];
        $stateError = [];
        $stateImport = [];
        foreach($allTasks as $taskData) {
            //$availableDataDirs[$taskData['id']] = $task->getRelativeTaskDataPath($taskData['taskGuid']);
            $availableDataDirs[] = $task->getRelativeTaskDataPath($taskData['taskGuid']);
            if($taskData['state'] == $task::STATE_IMPORT) {
                $stateImport[$taskData['id']] = $taskData;
            }
            if($taskData['state'] == $task::STATE_ERROR) {
                $stateError[$taskData['id']] = $taskData;
            }
        }
        
        $this->handleErrorTasks($stateError);
        $this->handleImportTasks($stateImport);
        $this->handleOrphanedTaskData($availableDataDirs);
        $this->handleSetToError();
        $this->io->section('Use the following option parameters to delete the listed tasks:');
        $this->io->text([
            '--delete-error [ID]  - deletes one (with ID) or all tasks with errors',
            '--delete-import ID   - deletes one task in state import',
            '--set-to-error ID    - sets a task to status error (for example to gain access to clone/delete/download of a hanging import task)',
            '--delete-data        - deletes all orphaned data folders',
        ]);
        return 0;
    }
    
    protected function handleErrorTasks(array $errorTasks) {
        $this->io->section('Tasks with errors:');
        if(empty($errorTasks)) {
            $this->io->text('<info>No tasks with status "error" found!</>');
            return;
        }
        $table = new TaskTable($this->output);
        $table->setRows($errorTasks);
        $table->render();
        $this->output->writeln(['','']);
        
        $toDelete = $this->input->getOption('delete-error');
        if($toDelete === false) {
            return;
        }
        $task = new \editor_Models_Task();
        //if a single task to be deleted is given:
        if(!empty($toDelete)) {
            $toDelete = (int) $toDelete;
            if(!$this->isInList($toDelete, $errorTasks)) {
                $this->io->error('Given task ID is not in the list of tasks with status "error" and can not be deleted here!');
                return;
            }
            $task->load($toDelete);
            $errorTasks = [(array) $task->getDataObject()];
        }
        if(empty($errorTasks)) {
            return;
        }
        foreach($errorTasks as $oneTask) {
            $task->init($oneTask);
            $remover = new \editor_Models_Task_Remover($task);
            $remover->remove(true);
        }
        if(count($errorTasks) === 1) {
            $oneTask = reset($errorTasks);
            $this->io->success('The task "'.$oneTask['taskName'].' ('.$oneTask['id'].' - '.$oneTask['taskGuid'].') was successfully deleted!');
        }
        else {
            $this->io->success('The above listed tasks with status error were successfully deleted!');
        }
    }
    
    protected function handleImportTasks(array $stateImport) {
        $this->io->section('Tasks with status import:');
        if(empty($stateImport)) {
            $this->io->text('<info>No tasks with status "import" found!</>');
            return;
        }
        $table = new TaskTable($this->output);
        $table->setRows($stateImport);
        
        
        $toDelete = (int) $this->input->getOption('delete-import');
        if(empty($toDelete)) {
            $table->render();
            $this->output->writeln(['','']);
            return;
        }
        if(!$this->isInList($toDelete, $stateImport)) {
            $table->render();
            $this->output->writeln(['','']);
            $this->io->error('Given task ID is not in the list of tasks with status "import" and can not be deleted here!');
            return;
        }
        
        $task = new \editor_Models_Task();
        $task->load((int) $toDelete);
        $msg = 'The task "'.$task->getTaskName().' ('.$task->getId().' - '.$task->getTaskGuid().') was successfully deleted!';
        $remover = new \editor_Models_Task_Remover($task);
        $remover->remove(true);
        
        unset($stateImport[$toDelete]);
        $table->setRows($stateImport);
        $table->render();
        $this->output->writeln(['','']);
        
        $this->io->success($msg);
    }
    
    /**
     * find orphaned data directories
     * @param array $availableDataDirs
     * @return array
     */
    protected function handleOrphanedTaskData(array $availableDataDirs) {
        
        //we just add the dot directories as available, so they are ignored
        $availableDataDirs[] = '.';
        $availableDataDirs[] = '..';
        
        $config = \Zend_Registry::get('config');
        $taskDataPath = $config->runtimeOptions->dir->taskData;
        $taskDirectories = scandir($taskDataPath);
        
        $orphaned = array_diff($taskDirectories, $availableDataDirs);
        
        $delete = $this->input->getOption('delete-data');
        
        $table = new Table($this->output);
        $table->setHeaders(['Folder', 'Modification Time']);
        $hasAtLeastOneOrphaned = false;
        $totalSize = 0;
        foreach($orphaned as $dir) {
            $absDir = realpath($taskDataPath.DIRECTORY_SEPARATOR.$dir);
            if(!is_dir($absDir) || $dir == '.' || $dir == '..') {
                continue;
            }
            $hasAtLeastOneOrphaned = true;
            $table->addRow([$dir, date('Y-m-d H:i:s', filemtime($absDir))]);
            $totalSize += $this->getDirectorySize($absDir);
            if($delete) {
                $absRoot = realpath(APPLICATION_ROOT);
                if(strpos($absDir, $absRoot) !== 0) {
                    throw new RuntimeException('The to be deleted directory "'.$absDir.'" is not under application root "'.$absRoot.'"!');
                }
                \ZfExtended_Utils::recursiveDelete($absDir);
            }
        }
        $this->io->section('Data Directories without a task:');
        if($hasAtLeastOneOrphaned) {
            $table->render();
            $this->output->writeln(['']);
            $usage = number_format($totalSize / 1048576, 2) . ' MB';
            if($delete) {
                $this->io->success('The above listed folders were successfully deleted ('.$usage.' freed) !');
            }
            else {
                $this->io->text('Disk usage of the above folders <info>'.$usage.'</>!');
            }
        }
        else {
            $this->io->text('<info>No orphaned data directories found!</>');
        }
    }
    
    /**
     * set the task given by ID to status error
     */
    protected function handleSetToError() {
        $taskId = (int) $this->input->getOption('set-to-error');
        if(empty($taskId)) {
            return;
        }
        
        $task = new \editor_Models_Task();
        $task->load((int) $taskId);
        if($task->setErroneous()) {
            $this->io->success('The task "'.$task->getTaskName().' ('.$task->getId().' - '.$task->getTaskGuid().') was successfully set to status error!');
        }
        else {
            $this->io->error('The task "'.$task->getTaskName().' ('.$task->getId().' - '.$task->getTaskGuid().') could not be set to status error!');
        }
    }
    
    /**
     * rerturns the total size of given directory in bytes
     * @param string $dir
     * @return int
     */
    protected function getDirectorySize(string $dir): int {
        $bytes = 0;
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $it){
            try {
                $bytes += $it->getSize();
            } catch(\Throwable){

            }
        }
        return $bytes;
    }
    
    /**
     * checks if the given taskId is in the given tasklist
     * @param int $taskId
     * @param array $taskList
     * @return boolean
     */
    protected function isInList(int $taskId, array $taskList): bool {
        return in_array($taskId, array_column($taskList, 'id'));
    }
}
