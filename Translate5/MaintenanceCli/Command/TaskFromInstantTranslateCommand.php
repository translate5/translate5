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
use MittagQI\Translate5\Task\FileTranslation\FileTranslationType;
use MittagQI\Translate5\Task\TaskLockService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            'taskIdentifier',
            InputArgument::REQUIRED,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->io->info('HINT: sysadmins can now anyway see InstantTranslate tasks in UI!');

        $task = static::findTaskFromArgument(
            $this->io,
            $input->getArgument('taskIdentifier'),
            false,
            [editor_Models_Task::STATE_OPEN, editor_Models_Task::STATE_ERROR],
            [FileTranslationType::ID]
        );

        if ($task === null) {
            return self::FAILURE;
        }

        $wasError = $task->getState() === editor_Models_Task::STATE_ERROR;
        TaskLockService::create()->unlockTask($task);
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
