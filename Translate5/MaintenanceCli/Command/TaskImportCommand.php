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

use editor_Models_Customer_Customer;
use editor_Models_Languages;
use editor_Models_Workflow;
use MittagQI\Translate5\Task\Import\ImportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

class TaskImportCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'task:import';

    protected function configure()
    {
        $this->setDescription('Import task');

        $this->addArgument('path', InputArgument::REQUIRED, 'Path to file');
        $this->addArgument('taskName', InputArgument::REQUIRED, 'Task name');
        $this->addOption(
            'pm',
            '',
            InputOption::VALUE_REQUIRED,
            'Project manager GUID',
        );
        $this->addOption(
            'source',
            's',
            InputOption::VALUE_REQUIRED,
            'Source language RFC5646',
        );
        $this->addOption(
            'targets',
            't',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Target language RFC5646',
        );
        $this->addOption(
            'customer',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Customer number. If not provided - default will be used',
        );
        $this->addOption(
            'description',
            'desc',
            InputOption::VALUE_OPTIONAL,
            'Task description',
        );
        $this->addOption(
            'workflow',
            'w',
            InputOption::VALUE_OPTIONAL,
            'Workflow name. If none provided - default will be used',
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws \Zend_Validate_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->importService = new ImportService();

        $taskName = trim($input->getArgument('taskName'));

        if (! $taskName) {
            $this->io->error('Please provide not empty Task name');

            return self::FAILURE;
        }

        $path = $input->getArgument('path');

        if (! file_exists($path)) {
            $this->io->error(sprintf('File "%s" does not exists', $path));

            return self::FAILURE;
        }

        $pmId = trim($input->getOption('pm'));

        if (! $pmId) {
            $this->io->error('Please provide valid PM GUID / login / id');

            return self::FAILURE;
        }

        try {
            $pm = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            if (is_numeric($pmId)) {
                $pm->load((int) $pmId);
            } elseif ((new \ZfExtended_Validate_Guid())->isValid($pmId)) {
                $pm->loadByGuid($pmId);
            } else {
                $pm->loadByLogin($pmId);
            }
            $pmGuid = $pm->getUserGuid();
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->io->error('Provided PM does not exists');

            return self::FAILURE;
        }

        $customerNumber = trim($input->getOption('customer'));
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);

        if ($customerNumber) {
            try {
                $customer->loadByNumber($customerNumber);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $this->io->error('Provided Customer does not exists');

                return self::FAILURE;
            }
        } else {
            $customer->loadByDefaultCustomer();
        }

        $customerNumber = $customer->getNumber();
        $source = $this->getLanguage($input->getOption('source'))->getId();
        $targets = $this->getLangIds($input->getOption('targets'));
        $description = $input->getOption('description');

        try {
            $workflow = $this->getWorkflow(
                $input->getOption('workflow'),
                $customer->getConfig()->runtimeOptions->workflow->initialWorkflow
            );
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->io->error('Provided workflow does not exists');

            return self::FAILURE;
        }

        $worker = new \editor_Models_Import_CliImportWorker();
        $worker->init(
            null,
            compact('path', 'taskName', 'pmGuid', 'customerNumber', 'source', 'targets', 'description', 'workflow')
        );
        $worker->queue();

        $this->io->success('Import queued');

        return self::SUCCESS;
    }

    private function getLangIds(array $rfcs): array
    {
        $targetLangs = [];

        foreach ($rfcs as $rfc) {
            $targetLangs[] = $this->getLanguage($rfc)->getId();
        }

        return $targetLangs;
    }

    private function getLanguage(string $rfc): editor_Models_Languages
    {
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        $language->loadByRfc5646($rfc);

        return $language;
    }

    private function getWorkflow(?string $name, string $defaultWorkflow): string
    {
        if (null === $name) {
            return $defaultWorkflow;
        }

        $workflow = ZfExtended_Factory::get(editor_Models_Workflow::class);
        $workflow->loadByName($name);

        return $name;
    }
}
