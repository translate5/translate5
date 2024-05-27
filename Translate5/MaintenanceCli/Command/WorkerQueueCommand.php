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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory;
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

        $this->addOption(
            'wakeup',
            'w',
            InputOption::VALUE_NONE,
            'Wakeup scheduled workers before triggering the queue'
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
        $this->initTranslate5AppOrTest();

        $this->writeTitle('trigger worker queue');

        $worker = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);

        if ($this->input->getOption('wakeup')) {
            $this->io->text('wakeup workers...');
            $worker->wakeupScheduled();
            sleep(2);
        }

        $workerQueue = ZfExtended_Factory::get(Queue::class);
        if ($workerQueue->lockAcquire()) {
            $foundWorkers = $workerQueue->process();
            while ($foundWorkers) {
                sleep(1);
                $foundWorkers = $workerQueue->process();
            }
            $workerQueue->lockRelease();
        }

        $this->io->text('scheduling workers...');

        if ($this->isPorcelain) {
            return self::SUCCESS;
        }

        sleep(4);

        $allWorker = $worker->loadByState($worker::STATE_PREPARE);
        if (empty($allWorker)) {
            $this->io->info('No worker set to running.');

            return 0;
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

        return 0;
    }
}
