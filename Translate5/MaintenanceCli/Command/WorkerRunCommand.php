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
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Installer_Maintenance;
use ZfExtended_Models_MaintenanceException;
use ZfExtended_Models_Worker;
use ZfExtended_Worker_Abstract;

class WorkerRunCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'worker:run';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Triggers the worker queue - ' .
                'may be necessary after an apache restart or maintenance mode.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Triggers the next runnable worker to be executed');

        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'The worker ID to be started.'
        );

        $this->addOption(
            'debug',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Just an option to pass worker and taskGuid into the real process list to be shown with top'
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
        //$this->initTranslate5AppOrTest();
        $this->initTranslate5(); //woher wissen die worker ob sie als test laufen?

        //if maintenance is near, we disallow starting workers
        if (ZfExtended_Models_Installer_Maintenance::isLoginLock()) {
            throw new ZfExtended_Models_MaintenanceException();
        }

        try {
            $workerModel = ZfExtended_Factory::get(ZfExtended_Models_Worker::class);
            $workerModel->load($input->getArgument('id'));

            if ($workerModel->getState() == $workerModel::STATE_WAITING) {

                $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
                if (!$worker || !$worker->runQueued()) {
                    return self::FAILURE;
                }
            }
        } catch (Throwable $e) {
            \Zend_Registry::get('logger')->exception($e);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
