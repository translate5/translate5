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


//FIXME https://github.com/bamarni/symfony-console-autocomplete

class TaskInfoCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:info';
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Queries the task table and provides a listing of all found tasks, or detailed information if found only a single task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Called with out parameters a overview of affected tasks is shown');

        $this->addArgument('identifier', InputArgument::REQUIRED, 'Either a complete numeric task ID or External ID, or a part of the task GUID, the order number, the taskname.');
        $this->addOption(
            'id-only',
            'i',
            InputOption::VALUE_NONE,
            'Force to search the identifier only in the ID column (to prevent find tasks containing the ID in one of the other searched columns)'
        );

        $this->addOption(
            'detail',
            'd',
            InputOption::VALUE_NONE,
            'Shows all data fields of the task (expect qmSubsegmentFlags) instead the overview'
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
        
        $this->writeTitle('Task Information');
        
        $task = new \editor_Models_Task();
        $search = $input->getArgument('identifier');
        $s = $task->db->select()
            //languages here too?
            ->from($task->db, ['ID' => 'id', 'TaskGUID' => 'taskGuid', 'Order No.' => 'taskNr', 'Task name' => 'taskName', 'External ID' => 'foreignId'])
            ->where('id = ?', $search);
        if(empty($input->getOption('id-only'))) {
            $s->orWhere('foreignId = ?', $search)
            ->orWhere('taskGuid like ?', '%'.$search.'%')
            ->orWhere('taskName like ?', '%'.$search.'%')
            ->orWhere('taskNr like ?', '%'.$search.'%');
        }
        $allFound = $task->db->fetchAll($s);
        $tasks = $allFound->toArray();
        $taskCount = count($tasks);
        if($taskCount === 0) {
            $this->io->warning('No task(s) found matching the given identifier!');
            return 1;
        }
        if($taskCount > 1) {
                $this->writeTable($tasks);
            return 0;
        }
        $task->load($tasks[0]['ID']);
        $data = (array) $task->getDataObject();
        unset($data['qmSubsegmentFlags']);
        if(empty($input->getOption('detail'))) {
            $this->writeTask($task);
        }
        else {
            $this->writeAssoc($data);
        }
        return 0;
    }

    public function writeTask (\editor_Models_Task $task) {
        $lang = new \editor_Models_Languages;
        $langs = array_column($lang->loadByIds([$task->getSourceLang(), $task->getTargetLang(), $task->getRelaisLang()]), 'rfc5646', 'id');
        $data = [
            'ID' => $task->getId(),
            'Project ID' => $task->getProjectId(),
            'TaskGUID' => $task->getTaskGuid().' / LEK_segment_view_'.md5($task->getTaskGuid()),
            'Name (Order No)' => $task->getTaskName().(strlen($task->getTaskNr()) ? ' ('.$task->getTaskNr().')' : ''),
            'Type (Proj. ID)' => $task->getTaskType().' ('.$task->getProjectId().') - '.$task->isTranslation() ? 'translation' : 'review',
            'Status' => $task->getState(),
            'Usage Mode / Lock' => $task->getUsageMode().' / '.($task->getLocked() ?: '-na-'),
            'Workflow' => $task->getWorkflow().' in step "'.$task->getWorkflowStepName().'" ('.$task->getWorkflowStep().')',
            'Languages' => ($langs[$task->getSourceLang()] ?? '-na-') . ' => ' . ($langs[$task->getTargetLang()] ?? '-na-') . ($task->getRelaisLang() ? (' Pivot: '.$langs[$task->getRelaisLang()] ?? '-na-') : ''),
            'Segment progress' => $task->getSegmentFinishCount().' / '.$task->getSegmentCount(),
            'Data Dir' => $task->getAbsoluteTaskDataPath(),
        ];
        $this->writeAssoc($data);

        /*
        //TODO info about the task
        // list errors like in systemctl status the log
        // list the associated users (and their locking status)
        // list the associated languageresources
        // worker
        task:info command
        - termtagger status
        - which worker is running if any
         * */

    }
}
