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

use MittagQI\ZfExtended\Service\ConfigHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigDiffCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'config:diff';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Compare configurations of tasks or customers')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Compare configurations of tasks or customers');

        $this->addArgument(
            'taskCustomer1',
            InputArgument::REQUIRED,
            'The ID, GUID (in case of tasks) or name (in case of customers) ' .
            'of the item which config shall be compared.'
        );

        $this->addArgument(
            'taskCustomer2',
            InputArgument::OPTIONAL,
            'The ID, GUID (in case of tasks) or name (in case of customers) ' .
            'of the item which config shall be compared against. If empty, the task-config will be compared ' .
            'against the default-values, customers will be compared against the “defaultcustomer”.'
        );

        $this->addOption(
            'customer',
            'c',
            InputOption::VALUE_NONE,
            'With this option customer configs are comparewd. ' .
            'The arguments are expected to be customer-ids or customer-names. ' .
            'If the second argument is empty, the “defaultcustomer” is used for comparision.'
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
        $this->writeTitle('Diff Translate5 configuration.');

        $isCustomer = (bool) $this->input->getOption('customer');
        $identifier1 = $this->input->getArgument('taskCustomer1');
        $identifier2 = $this->input->getArgument('taskCustomer2');
        if (empty($identifier2) && $isCustomer) {
            $identifier2 = 'defaultcustomer';
        }

        $error = '';
        $config1 = null;
        $config2 = null;

        try {
            if ($isCustomer) {
                $error = 'Could not find customer with identifier “' . $identifier1 . '”';
                $customer = $this->loadCustomer($identifier1);
                $name1 = $customer->getName();
                $config1 = $customer->getConfig();

                $error = 'Could not find customer with identifier “' . $identifier2 . '”';
                $customer = $this->loadCustomer($identifier2);
                $name2 = $customer->getName();
                $config2 = $customer->getConfig();
            } else {
                $error = 'Could not find task with identifier “' . $identifier1 . '”';
                $task = $this->loadTask($identifier1);
                $name1 = $task->getTaskName();
                $config1 = $task->getConfig(true);

                if (! empty($identifier2)) {
                    $error = 'Could not find task with identifier “' . $identifier2 . '”';
                    $task = $this->loadTask($identifier2);
                    $name2 = $task->getTaskName();
                    $config2 = $task->getConfig(true);
                } else {
                    $name2 = 'Default value';
                }
            }
        } catch (\Throwable $e) {
            $this->io->error($error . ': ' . $e->getMessage());

            return self::FAILURE;
        }

        $isAgainstDefault = ($config2 === null);
        $helper1 = new ConfigHelper($config1);
        $helper2 = $isAgainstDefault ? null : new ConfigHelper($config2);

        $entity = $isCustomer ? 'customer' : 'task';
        $diffsHeader = $isAgainstDefault ?
            ['Config name', 'Value ' . $entity . ' “' . $name1 . '”', $name2] :
            ['Config name', 'Value ' . $entity . ' “' . $name1 . '”', 'Value ' . $entity . ' “' . $name2 . '”'];
        $diffs = [];

        $where = "`name` LIKE 'runtimeOptions.%' AND " .
            "`name` NOT LIKE 'runtimeOptions.frontend.defaultState%' AND " .
            "`name` != 'runtimeOptions.viewfilters'";
        $sql = $isAgainstDefault ?
            'SELECT `name`, `type`, `typeClass`, `default` FROM `Zf_configuration` WHERE `default` IS NOT NULL AND ' . $where . ' ORDER BY `name`' :
            'SELECT `name`, `type` FROM `Zf_configuration` WHERE ' . $where . ' ORDER BY `name`';
        $db = \Zend_Db_Table::getDefaultAdapter();
        $configs = $db->fetchAssoc($sql);

        if ($isAgainstDefault) {
            // in case we are comaring against the fdefaults, we create a config/config-helper out of the default values
            $count = 1;
            $defaultConfig = [];
            foreach ($configs as $configData) {
                $defaultConfig[] = [
                    'id' => $count,
                    'name' => $configData['name'],
                    'value' => $configData['default'],
                    'type' => $configData['type'],
                    'typeClass' => $configData['typeClass'],
                ];
                $count++;
            }

            $configOperator = \ZfExtended_Factory::get(\ZfExtended_Resource_DbConfig::class);
            $configOperator->initDbOptionsTree($defaultConfig);

            $config2 = new \Zend_Config($configOperator->getDbOptionTree());
            $helper2 = new ConfigHelper($config2);
        }

        foreach ($configs as $configData) {
            $name = $configData['name'];
            $type = $configData['type'];
            $value = $helper1->getValue($name, $type);
            $against = $helper2->getValue($name, $type);

            if (! $this->valuesAreEqual($value, $against)) {
                $diffs[] = [$name, $this->valueToString($value, $type), $this->valueToString($against, $type)];
            }
        }

        if (count($diffs) === 0) {
            $this->io->success('The two ' . $entity . 's (“' . $name1 . '”, “' . $name1 . '”) have equal configurations.');
        } else {
            $this->io->warning('The two ' . $entity . 's have different configurations:');
            $colWidth = 60;
            $table = new Table($this->output);
            $table
                ->setHeaders($diffsHeader)
                ->setRows($diffs)
                ->setColumnWidths([$colWidth, $colWidth, $colWidth])
                ->setColumnMaxWidth(0, $colWidth)
                ->setColumnMaxWidth(1, $colWidth)
                ->setColumnMaxWidth(2, $colWidth);
            $table->render();
        }

        return self::SUCCESS;
    }

    /**
     * @throws \ReflectionException
     * @throws \Zend_Exception
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \editor_Models_ConfigException
     */
    private function loadTask(string $identifier): \editor_Models_Task
    {
        $task = new \editor_Models_Task();
        if (preg_match('~^[0-9]+$~', $identifier) === 1) {
            $task->load((int) $identifier);
        } else {
            $identifier = '{' . trim($identifier, '{}') . '}';
            $task->loadByTaskGuid($identifier);
        }

        return $task;
    }

    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    private function loadCustomer(string $identifier): \editor_Models_Customer_Customer
    {
        $customer = new \editor_Models_Customer_Customer();
        if (preg_match('~^[0-9]+$~', $identifier) === 1) {
            $customer->load((int) $identifier);
        } else {
            $customer->loadByName($identifier);
        }

        return $customer;
    }

    private function valuesAreEqual(mixed $value1, mixed $value2): bool
    {
        if (is_array($value1) && is_array($value2)) {
            if (count($value1) !== count($value2)) {
                return false;
            }

            $keys = array_keys($value1);
            if (array_keys($keys) !== $keys) {
                // hashed array
                ksort($value1, SORT_STRING);
                ksort($value2, SORT_STRING);

                return json_encode($value1, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ===
                    json_encode($value2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                // numeric indexed array
                sort($value1);
                sort($value2);

                return json_encode($value1, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ===
                    json_encode($value2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $value1 === $value2;
    }

    private function valueToString(mixed $value, string $type): string
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($type === \ZfExtended_DbConfig_Type_CoreTypes::TYPE_BOOLEAN) {
            $value = $value ? 'true' : 'false';
        } else {
            $value = (string) $value;
        }
        $value = str_replace(["\r", "\t"], ['', ' '], $value);
        $value = preg_replace('~\s+~', ' ', $value);

        if (mb_strlen($value) > 300) {
            return mb_substr($value, 0, 100) . ' ...';
        }

        return $value;
    }
}
