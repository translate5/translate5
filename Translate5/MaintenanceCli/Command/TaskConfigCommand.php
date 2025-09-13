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

use editor_Models_Task as Task;
use editor_Models_TaskConfig as editor_Models_TaskConfigAlias;
use MittagQI\Translate5\Customer\CustomerConfigService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Config;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Models_Entity_NotFoundException;

class TaskConfigCommand extends TaskCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:config';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Prints the task configuration (by default only the ones different from system)')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Prints the task configuration (by default only the ones different from system)');

        $this->addArgument(
            'identifier',
            InputArgument::REQUIRED,
            'Either a complete numeric task ID or External ID, '
                . 'or a part of the task GUID, the order number, the taskname.'
        );

        $this->addOption(
            'id-only',
            'i',
            InputOption::VALUE_NONE,
            'Force to search the identifier only in the ID column '
                . '(to prevent find tasks containing the ID in one of the other searched columns)'
        );

        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Shows all configs - also the ones not different from system'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $this->writeTitle('Task Specific Configuration');

        $tasks = static::searchTasksFromArgument(
            $input->getArgument('identifier'),
            (bool) $input->getOption('id-only')
        );

        $taskCount = count($tasks);
        if ($taskCount === 0) {
            $this->io->warning('No task(s) found matching the given identifier!');

            return self::SUCCESS;
        }
        if ($taskCount > 1) {
            $this->writeTable($tasks);

            return self::SUCCESS;
        }
        $task = new Task();
        $task->load($tasks[0]['ID']);

        $this->writeTaskConfig($task, Zend_Registry::get('config'));

        return self::SUCCESS;
    }

    private function writeTaskConfig(
        Task $task,
        Zend_Config $systemConfig,
    ): void {
        $getAll = (bool) $this->input->getOption('all');
        $taskConfig = new editor_Models_TaskConfigAlias();
        $taskConfigs = $taskConfig->loadByTaskGuid($task->getTaskGuid());
        $customerConfig = CustomerConfigService::create();

        $table = $this->io->createTable();
        $table->setHeaders([
            'config',
            'System Value',
            'Customer Value',
            'Task Value',
        ]);

        foreach ($taskConfigs as $config) {
            $foundSystemConfig = $this->configGetByPath($systemConfig, $config['name']);
            $customerValue = $customerConfig->getConfigValue($task->getCustomerId(), $config['name']);

            if ($foundSystemConfig instanceof Zend_Config) {
                $foundSystemConfig = print_r($foundSystemConfig->toArray(), true);
                $config['value'] = print_r(json_decode($config['value'], true) ?? [], true);
                if ($customerValue !== null) {
                    $customerValue = print_r(json_decode($customerValue, true) ?? [], true);
                }
            } else {
                //very UGLY - since no config type conversion is done here:
                if ($foundSystemConfig === false) {
                    $foundSystemConfig = '0';
                }

                $foundSystemConfig = (string) $foundSystemConfig;
            }

            if ($foundSystemConfig === $config['value']
                && ($customerValue === null || $customerValue === $config['value']) && ! $getAll) {
                continue;
            }

            $table->addRow([
                $config['name'],
                $foundSystemConfig,
                $customerValue ?? '     ø',
                $config['value'],
            ]);
        }

        $table->render();
    }

    private function configGetByPath(Zend_Config $config, $path): mixed
    {
        $parts = explode('.', $path);
        $value = $config;

        foreach ($parts as $part) {
            if ($value instanceof Zend_Config && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return $value;
    }
}
