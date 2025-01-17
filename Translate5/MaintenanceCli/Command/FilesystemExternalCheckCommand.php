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

use JsonException;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperationFailed;
use MittagQI\Translate5\Tools\FilesystemFactoryInterface;
use MittagQI\Translate5\Tools\FlysystemFactory;
use ReflectionException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class FilesystemExternalCheckCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    public const EVENT_CHECK = 'checkFilesystemConfig';

    protected static $defaultName = 'filesystem:external:check';

    private int $idx = 0;
    private int $idToListContents;
    private ?DirectoryListing $toListContents = null;

    protected function configure()
    {
        $this
            ->setDescription('List and test all flysystem configurations in the application')
            ->setHelp(
                'List and test all flysystem configurations in the application'
            );

        $this->addArgument(
            'id',
            InputArgument::OPTIONAL,
            'The ID of the configuration to be tested / contents listed'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws ReflectionException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('List / test / execute task archiving configurations');

        $this->idToListContents = (int) $input->getArgument('id');

        $actions = \ZfExtended_Factory::get(\editor_Models_Workflow_Action::class);
        $actionEntries = $actions->loadByAction(\editor_Workflow_Actions::class, 'deleteOldEndedTasks');

        $this->io->section('Check workflow action deleteOldEndedTasks:');
        $table = $this->io->createTable();
        $table->setHeaders(['ID', 'Workflow', 'Trigger', 'Status', 'Config']);
        foreach ($actionEntries as $entry) {
            $row = [
                'id' => $this->idx++,
                'workflow' => $entry['workflow'],
                'trigger' => $entry['trigger'],
            ];

            list($status, $configPretty) = $this->checkFlySystemConfig($entry['parameters']);
            $row['status'] = $status;
            $row['config'] = $configPretty;

            $table->addRow($row);
        }
        $table->render();

        $events = \ZfExtended_Factory::get('ZfExtended_EventManager', [get_class($this)]);
        $responses = $events->trigger(self::EVENT_CHECK, $this);
        foreach ($responses as $response) {
            list($name, $cls, $configName) = $response;
            $this->io->section('Check plug-in ' . $name . ' config ' . $configName);
            $config = new \editor_Models_Config();
            $config->loadByName($configName);

            $table = $this->io->createTable();
            $table->setHeaders(['ID', 'Customer', 'Status', 'ConfigCheck', 'Config']);

            $this->makeTableRow($table, '-na- (Main Config)', $config->getValue(), $cls);

            $customerConfig = new \editor_Models_Customer_CustomerConfig();
            $customerValues = $customerConfig->loadByName($config->getName());

            foreach ($customerValues as $customerValue) {
                $this->makeTableRow($table, $customerValue['customerId'], $customerValue['value'], $cls);
            }
            $table->render();
        }

        if ($this->toListContents !== null) {
            $content = $this->toListContents->toArray();
            $this->io->section('Content found in chosen config: ');
            if(empty($content)) {
                $this->io->warning('No content in chosen config');
            } else {
                foreach ($content as $dir) {
                    $this->io->text('#'.$this->idToListContents.'#: '.$dir->path());
                }
            }
        }

        return self::SUCCESS;
    }

    private function checkFlySystemConfig(string $config): array
    {
        $result = 'OK';
        $jsonConfig = null;

        if (empty($config)) {
            return ['not set', '', null];
        }

        try {
            $jsonConfig = json_decode($config, flags: JSON_THROW_ON_ERROR);
            $config = json_encode($jsonConfig, JSON_PRETTY_PRINT);
            $filesystem = FlysystemFactory::create($jsonConfig->filesystem ?? $jsonConfig->type, $jsonConfig);
            $filesystem->directoryExists('/');

            if (($this->idx - 1) === $this->idToListContents) {
                $this->toListContents = $filesystem->listContents('/');
            }

        } catch (FilesystemException|FilesystemOperationFailed $e) {
            $result = $e->getMessage();
            while ($e->getPrevious()) {
                $e = $e->getPrevious();
                $result .= "\n" . $e->getMessage();
            }
        } catch (JsonException $e) {
            $result = 'Invalid JSON in config: ' . $e->getMessage();
        }

        return [$result, $config, $jsonConfig];
    }

    private function makeTableRow(Table $table, string $customer, string $config, string $cls): void
    {
        $row = [
            'id' => $this->idx++,
            'Customer' => $customer,
            'status' => '-na-', //needed so that fields are in correct order!
            'configCheck' => '-na-',
        ];

        list($status, $pretty, $confObj) = $this->checkFlySystemConfig($config);
        $row['status'] = $status;
        $row['config'] = $pretty;
        if (is_a($cls, FilesystemFactoryInterface::class, true)) {
            $row['configCheck'] = $cls::isValidFilesystemConfig($confObj) ? 'OK' : 'NOT OK';
        }
        $table->addRow($row);
    }
}
