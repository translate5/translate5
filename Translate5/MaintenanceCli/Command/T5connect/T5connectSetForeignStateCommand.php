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

namespace Translate5\MaintenanceCli\Command\T5connect;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Command\TaskCommand;

class T5connectSetForeignStateCommand extends TaskCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 't5connect:setforeignstate';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Sets the foreign-state for a t5connect task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Sets the foreign-state for a t5connect task.');

        $this->addArgument(
            'taskIdentifier',
            InputArgument::REQUIRED,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );

        $this->addArgument(
            'foreignState',
            InputArgument::REQUIRED,
            'The state to be set for the identified task'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $task = static::findTaskFromArgument(
            $this->io,
            $input->getArgument('taskIdentifier')
        );

        if ($task === null) {
            return self::FAILURE;
        }

        $foreignState = $input->getArgument('foreignState');
        $task->setForeignState($foreignState);
        $task->save();

        $this->io->success('Set foreignState of Task “' . $task->getTaskName() .
            '” (id: ' . $task->getId() . ') to “' . $foreignState . '”');

        return self::SUCCESS;
    }
}
