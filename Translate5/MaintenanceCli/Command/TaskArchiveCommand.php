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
use editor_Models_Workflow_Action;
use editor_Workflow_Actions;
use JsonException;
use MittagQI\Translate5\Workflow\ArchiveConfigDTO;
use MittagQI\Translate5\Workflow\ArchiveTaskActions;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Conflict;

class TaskArchiveCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:archive';

    protected function configure()
    {
        $this
            ->setDescription('Triggers the configured task delete and archive entries for testing purposes')
            ->setHelp(
                'Triggers the configured task delete and archive entries from Workflow Configuration as ' .
                'configured in Zf_workflow_action. By default the entries are triggerd by cron job, this command ' .
                'is mainly for testing purposes. By default only the affected tasks are listed. Use --execute ' .
                'to perform execution of the deletion / backup'
            );

        $this->addOption(
            'keep-task',
            'k',
            InputOption::VALUE_NONE,
            'Does not delete the tasks (but does logging and backup - if configured)',
        );

        $this->addOption(
            'execute',
            'e',
            InputOption::VALUE_NONE,
            'Executes all / or the given archiving configuration(s)'
        );

        $this->addArgument(
            'actionId',
            InputArgument::OPTIONAL,
            'The ID of the workflow archive entry to be shown / tested / executed. '
            . 'If omitted list / --test / --execute all available entries)'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Conflict
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('List / test / execute task archiving configurations');

        $actionId = $input->getArgument('actionId');
        $keepTasks = $input->getOption('keep-task');
        $execute = $input->getOption('execute');

        $actions = ZfExtended_Factory::get(editor_Models_Workflow_Action::class);
        $actionEntries = $actions->loadByAction(editor_Workflow_Actions::class, 'deleteOldEndedTasks');
        foreach ($actionEntries as $actionEntry) {
            if (! empty($actionId) && $actionId != $actionEntry['id']) {
                continue;
            }
            $this->io->section('Found worker action ID: ' . $actionEntry['id']);
            $this->io->info('With parameters: ' . $actionEntry['parameters']);

            try {
                $config = ArchiveConfigDTO::fromObject(
                    (object) json_decode($actionEntry['parameters'], flags: JSON_THROW_ON_ERROR)
                );
            } catch (JsonException $e) {
                $this->io->error('Worker action has invalid JSON in parameters: ' . $e->getMessage());

                continue;
            }

            /** @var ArchiveTaskActions $taskActions */
            $taskActions = ZfExtended_Factory::get(ArchiveTaskActions::class, [$config]);

            $task = $this->renderTaskTable($taskActions);

            if (empty($task)) {
                $this->io->warning('No task found for workflow action ID ' . $actionEntry['id']);
            }

            if (! $execute) {
                continue;
            }

            if (empty($config->filesystemConfig) && empty($config->targetPath)) {
                $this->io->success($keepTasks ? 'Test removing tasks (but keep them)' : 'Removing tasks');
                $taskActions->removeOldTasks($keepTasks);
            } else {
                $this->io->success($keepTasks
                    ? 'Backup (check workers and log) only'
                    : 'Backup (check workers and log) and remove tasks');
                $taskActions->backupThenRemove($keepTasks);
            }
        }

        if (empty($taskActions)) {
            $this->io->warning('No matching workflow action found!');
        }

        return self::SUCCESS;
    }

    private function renderTaskTable(ArchiveTaskActions $taskActions): ?Task
    {
        $task = null;
        $table = $this->io->createTable();
        $table->setHeaders([
            'P-ID',
            'Task-ID',
            'Task Name',
            'Status',
            'Workflow',
            'TaskGuid',
            'ClientId',
            'Modified',
            'Created',
        ]);
        foreach ($taskActions->getTasks() as $task) {
            $table->addRow([
                $task->getProjectId(),
                $task->getId(),
                $task->getTaskName(),
                $task->getState(),
                $task->getWorkflowStepName(),
                $task->getTaskGuid(),
                $task->getCustomerId(),
                $task->getModified(),
                $task->getCreated(),
            ]);
        }
        $table->render();

        return $task;
    }
}
