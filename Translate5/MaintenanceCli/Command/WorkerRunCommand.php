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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Installer_Maintenance;
use ZfExtended_Models_MaintenanceException;
use ZfExtended_Models_Worker;
use ZfExtended_Worker_Abstract;

class WorkerRunCommand extends Translate5AbstractCommand
{
    public const CMD_TITLE = 'translate5 worker #%s (%s)%s';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'worker:run';

    private bool $doDebug = false;

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Tries to run a specific waiting worker identified by its ID.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Give the worker ID as argument to run it. ' .
                'Must be in state waiting.' .
                'Is used for process based worker start up.'
            );

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The worker ID to be started.'
        );

        $this->addOption(
            'force-delayed',
            null,
            InputOption::VALUE_NONE,
            'Forces a delayed worker to be run.'
        );

        // during API-tests the test-flag is set via option
        $this->addOption(
            'test',
            null,
            InputOption::VALUE_NONE,
            'Use the test-database to run a worker during API-tests.'
        );
        $this->addOption(
            'apptest',
            null,
            InputOption::VALUE_NONE,
            'Use the test-database to run a worker during API-tests.'
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

        $this->doDebug = \ZfExtended_Debug::hasLevel('core', 'Workers');

        //if maintenance is near, we disallow starting workers
        if (ZfExtended_Models_Installer_Maintenance::isLoginLock()) {
            throw new ZfExtended_Models_MaintenanceException();
        }

        $workerId = $input->getArgument('id');

        try {
            $workerModel = new ZfExtended_Models_Worker();
            $workerModel->load($workerId);
            $this->debug($workerId, 'LOADED');

            $this->changeProcessTitle($workerModel);

            if ($this->input->getOption('force-delayed')
                && $workerModel->getState() == $workerModel::STATE_DELAYED) {
                $workerModel->setState($workerModel::STATE_WAITING);
                $workerModel->save();
            }

            if ($workerModel->getState() == $workerModel::STATE_WAITING) {
                $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
                if (! $worker || ! $worker->runQueued()) {
                    $this->debug($workerId, ! $worker ? 'No Model' : 'Not running');

                    return self::FAILURE;
                }
            } elseif ($workerModel->getState() == $workerModel::STATE_DELAYED && ! $this->isPorcelain) {
                $this->debug($workerId, 'delayed');
                $this->io->warning([
                    'Worker is delayed! ',
                    'To run explicitly a delayed worker please use --force-delayed option.',
                ]);
            }
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            // if a worker was gone before we don't log that
            $this->debug($workerId, 'worker gone');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->debug($workerId, 'worker exception');
            Zend_Registry::get('logger')->exception($e);

            return self::FAILURE;
        }

        $this->debug($workerId, 'DONE ' . ($workerModel->getState()));

        return self::SUCCESS;
    }

    /**
     * @throws Zend_Exception
     */
    private function changeProcessTitle(?ZfExtended_Models_Worker $workerModel): void
    {
        $additionalInfos = '';
        $dbName = Zend_Registry::get('config')->resources->db->params->dbname;
        if ($dbName != 'translate5') { //we ignore default tables in debugging
            $additionalInfos .= ' instance: ' . $dbName;
        }
        if (! empty($workerModel->getTaskGuid())) {
            $additionalInfos .= ' task: ' . $workerModel->getTaskGuid();
        }

        cli_set_process_title(
            sprintf(
                self::CMD_TITLE,
                $workerModel->getId(),
                $workerModel->getWorker(),
                $additionalInfos,
            )
        );
    }

    private function debug(string $id, string $message): void
    {
        if ($this->doDebug) {
            error_log('WORKER RUN #' . $id . ' ' . $message);
        }
    }
}
