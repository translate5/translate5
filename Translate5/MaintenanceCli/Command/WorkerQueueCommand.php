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

use MittagQI\ZfExtended\Worker\Queue;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Models_Worker;

class WorkerQueueCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'worker:queue';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription(
                'Triggers the worker queue - may be necessary after an apache restart or maintenance mode.'
            )

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Triggers the next runnable worker to be executed');

        // during API-tests the test-flag is set via option
        $this->addOption(
            'test',
            't',
            InputOption::VALUE_NONE,
            'Use the test-database to trigger workers during API-tests.'
        );
        $this->addOption(
            'apptest',
            null,
            InputOption::VALUE_NONE,
            'Use the test-database to run a worker during API-tests.'
        );

        $this->addOption(
            'daemon',
            null,
            InputOption::VALUE_NONE,
            'Keep the worker running until all scheduled workers are started'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @return int
     * @throws ReflectionException
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);

        if ($this->input->getOption('test')) {
            // no choice in case test-environment is forced
            $this->initTranslate5('test');
        } elseif ($this->input->getOption('apptest')) {
            // also the apptest has an own mode - in lack of having an origin
            $this->initTranslate5('apptest');
        } elseif ($this->isPorcelain) {
            // also no choice for porcelain calls
            $this->initTranslate5();
        } else {
            $this->initTranslate5AppOrTest();
        }

        $this->writeTitle('trigger worker queue');

        if ($this->input->getOption('daemon')) {
            // runs the queue loop if possible
            $this->io->text('run permanent worker queue');
            Queue::processQueueMutexed(true);
        } else {
            // triggers the queue loop asynchronously
            $this->io->text('scheduling workers asynchronously');
            Queue::processQueueMutexed();
        }

        if ($this->isPorcelain) {
            return self::SUCCESS;
        }

        sleep(2);

        $worker = new ZfExtended_Models_Worker();
        $allWorker = $worker->loadByState($worker::STATE_RUNNING);
        if (empty($allWorker)) {
            $this->io->info('No worker set to running or they are already done.');

            return self::SUCCESS;
        }

        $headlines = [
            'id' => 'DB id:',
            'parentId' => 'DB par. id:',
            'state' => 'state:',
            'pid' => 'Process id:',
            'starttime' => 'Starttime:',
            'endtime' => 'Endtime:',
            'taskGuid' => 'TaskGuid:',
            'worker' => 'Worker:',
        ];

        $rows = [];
        $tpl = array_fill_keys(array_keys($headlines), '');
        foreach ($allWorker as $worker) {
            $rows[] = array_replace($tpl, array_intersect_key($worker, $tpl));
        }

        $this->io->table($headlines, $rows);

        return self::SUCCESS;
    }
}
