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

use LogicException;
use MittagQI\ZfExtended\Worker\Queue;
use MittagQI\ZfExtended\Worker\Trigger\Factory;
use MittagQI\ZfExtended\Worker\Trigger\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Installer_Maintenance;
use ZfExtended_Models_MaintenanceException;
use ZfExtended_Models_Worker;

class WorkerRerunCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'worker:rerun';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Tries to re-run a specific worker identified by its ID.' .
                'Must be in state done defunct or running.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Give the worker ID as argument to re-run it. ' .
                'Must be in state done, defunct or running.' .
                '!!! It is dangerous to use that without knowing what you are doing !!!');

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The worker ID to be re-started.'
        );

        $this->addOption(
            'set-done',
            mode: InputOption::VALUE_NONE,
            description: 'Just sets a stalled running / defunct worker to status done - do not rerun it'
        );
    }

    /**
     * @throws ZfExtended_Models_MaintenanceException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Throwable
     * @throws Zend_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        if (! Factory::create() instanceof Process) {
            throw new LogicException('This command can only be used with worker trigger type "process"');
        }

        if (! is_dir('/proc')) {
            throw new LogicException('This command can run only under unix like operating systems with a /proc folder');
        }

        //if maintenance is near, we disallow starting workers
        if (ZfExtended_Models_Installer_Maintenance::isLoginLock()) {
            throw new ZfExtended_Models_MaintenanceException();
        }

        try {
            $workerModel = new ZfExtended_Models_Worker();
            $workerModel->load($input->getArgument('id'));
            $cmdline = '/proc/' . $workerModel->getPid() . '/cmdline';

            if (file_exists($cmdline) && str_starts_with(file_get_contents($cmdline), sprintf(
                WorkerRunCommand::CMD_TITLE,
                $workerModel->getId(),
                $workerModel->getWorker(),
                ''
            ))) {
                $this->io->warning('Could not rerun worker, process is already / still running, check ps output');

                return self::FAILURE;
            }

            $allowedStates = [
                ZfExtended_Models_Worker::STATE_RUNNING,
                ZfExtended_Models_Worker::STATE_DEFUNCT,
                ZfExtended_Models_Worker::STATE_DONE,
            ];

            if (! in_array($workerModel->getState(), $allowedStates)) {
                $this->io->error('Worker is in state "' . $workerModel->getState()
                    . '" instead in one of the allowed states');

                return self::FAILURE;
            }

            if ($input->getOption('set-done')) {
                $workerModel->setState($workerModel::STATE_DONE);
                $workerModel->save();
                $this->io->success('Set worker to done #' . $workerModel->getId());
            } else {
                $workerModel->setState($workerModel::STATE_WAITING);
                $workerModel->save();
                $this->io->success('Rerun worker #' . $workerModel->getId());
                ZfExtended_Factory::get(Queue::class)->trigger();
            }
        } catch (Throwable $e) {
            Zend_Registry::get('logger')->exception($e);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
