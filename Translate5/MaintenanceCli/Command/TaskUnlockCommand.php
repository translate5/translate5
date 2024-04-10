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

use MittagQI\Translate5\Task\Lock;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//FIXME https://github.com/bamarni/symfony-console-autocomplete

class TaskUnlockCommand extends TaskInfoCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:unlock';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Unlocks a locked task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Unlocks a locked task - ensure that you know what you are doing when using that command!');

        $this->addArgument('taskId', InputArgument::REQUIRED, 'Just the numeric task ID.');
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

        $task = new \editor_Models_Task();
        $task->load($input->getArgument('taskId'));
        $this->writeTask($task);
        Lock::taskUnlock($task);
        $this->io->success('Task unlocked');

        return self::SUCCESS;
    }
}
