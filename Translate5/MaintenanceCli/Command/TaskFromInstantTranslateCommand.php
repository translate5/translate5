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
use editor_Task_Type_Default;
use MittagQI\Translate5\Task\Lock;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class TaskFromInstantTranslateCommand extends TaskInfoCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:frominstant';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Turns an task created with InstantTranslate to a normal task')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Turns an task created with InstantTranslate to a normal task after it was imported and is available.'
            );

        $this->addArgument(
            'taskIdOrName',
            InputArgument::REQUIRED,
            'The numeric task ID or the name as given in instant translate.'
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

        $task = new editor_Models_Task();
        $taskIdOrName = $input->getArgument('taskIdOrName');
        $matches = [];
        $withDatePattern = '/^(.+)\s+(\([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\))$/';
        $select = $task->db->select();
        // we should use editor_Plugins_InstantTranslate_TaskType::ID here but this is from a private plugin ...
        $select
            ->where('taskType = ?', 'instanttranslate-pre-translate')
            ->where('state IN (?)', [editor_Models_Task::STATE_OPEN, editor_Models_Task::STATE_ERROR]);
        if (is_numeric($taskIdOrName)) {
            $select->where('id = ?', $taskIdOrName);
        } elseif (preg_match($withDatePattern, $taskIdOrName, $matches) === 1) {
            $select
                ->where('taskName LIKE ?', '%' . $matches[1] . '%')
                ->where('created LIKE ?', '%' . $matches[2] . '%');
        } else {
            $select->where('taskName LIKE ?', '%' . $taskIdOrName . '%');
        }

        $tasks = $task->db->fetchAll($select)->toArray();

        if (count($tasks) === 0) {
            $this->io->error('InstantTranslate-task "' . $taskIdOrName . '" not found.');

            return self::FAILURE;
        } elseif (count($tasks) === 1) {
            $taskId = (int) $tasks[0]['id'];
        } else {
            $taskNames = [];
            foreach ($tasks as $data) {
                $taskNames[] = $data['taskName'] . ' (id: ' . $data['id'] . ')';
            }
            $question = new ChoiceQuestion('Please choose a Task', $taskNames, null);
            $taskName = $this->io->askQuestion($question);
            $parts = explode(' (id: ', $taskName);
            $taskId = (int) rtrim(array_pop($parts), ')');
        }

        $task->load($taskId);
        $wasError = $task->getState() === editor_Models_Task::STATE_ERROR;
        Lock::taskUnlock($task);
        $task->setState($task::STATE_OPEN);
        $task->setTaskType(editor_Task_Type_Default::ID);
        $task->save();

        $name = '"' . $task->getTaskName() . '" (id: ' . $task->getId() . ')';
        if ($wasError) {
            $this->io->warning('Turned erroneous InstantTranslate task ' . $name . ' to a "real" one.');
        } else {
            $this->io->success('Turned InstantTranslate task ' . $name . ' to a "real" one.');
        }

        return self::SUCCESS;
    }
}
