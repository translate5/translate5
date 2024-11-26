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

use editor_Models_Import_Worker_FinalStep;
use editor_Models_Languages;
use editor_Models_Logger_Task;
use editor_Models_Task as Task;
use editor_Task_Operation_FinishingWorker;
use JsonException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

//FIXME https://github.com/bamarni/symfony-console-autocomplete

class TaskInfoCommand extends TaskCommand
{
    protected const WORKER_SECTION_END = [
        editor_Models_Import_Worker_FinalStep::class,
        editor_Task_Operation_FinishingWorker::class,
    ];

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:info';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Queries the task table and provides a listing of all found tasks, '
                . 'or detailed information if found only a single task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Called with out parameters a overview of affected tasks is shown');

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
            'detail',
            'd',
            InputOption::VALUE_NONE,
            'Shows all data fields of the task (expect qmSubsegmentFlags) and task meta instead the overview'
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

        $this->writeTitle('Task Information');

        $tasks = static::searchTasksFromArgument($input->getArgument('identifier'));
        $taskCount = count($tasks);
        if ($taskCount === 0) {
            $this->io->warning('No task(s) found matching the given identifier!');

            return 1;
        }
        if ($taskCount > 1) {
            $this->writeTable($tasks);

            return 0;
        }
        $task = new Task();
        $task->load($tasks[0]['ID']);
        $data = (array) $task->getDataObject();
        unset($data['qmSubsegmentFlags']);
        if (empty($input->getOption('detail'))) {
            $this->writeTask($task);
        } else {
            $this->writeAssoc($data);
            $this->io->section('Meta Table:');
            $this->writeAssoc((array) $task->meta()->getDataObject());
        }

        return 0;
    }

    public function writeTask(Task $task): void
    {
        $lang = new editor_Models_Languages();
        $languages = array_column($lang->loadByIds([
            $task->getSourceLang(),
            $task->getTargetLang(),
            $task->getRelaisLang(),
        ]), 'rfc5646', 'id');
        $data = [
            'ID' => $task->getId(),
            'Project ID' => $task->getProjectId(),
            'TaskGUID' => $task->getTaskGuid() . ' / LEK_segment_view_' . md5($task->getTaskGuid()),
            'Name (Order No)' => $task->getTaskName()
                . (strlen($task->getTaskNr()) ? ' (' . $task->getTaskNr() . ')' : ''),
            'Type (Proj. ID)' => $task->getTaskType() . ' (' . $task->getProjectId() . ') - '
                . $task->isTranslation() ? 'translation' : 'review',
            'Status' => $task->getState(),
            'Usage Mode / Lock' => $task->getUsageMode() . ' / ' . ($task->getLocked() ?: '-na-'),
            'Workflow' => $task->getWorkflow() . ' in step "' . $task->getWorkflowStepName()
                . '" (' . $task->getWorkflowStep() . ')',
            'Languages' => ($languages[$task->getSourceLang()] ?? '-na-') . ' => '
                . ($languages[$task->getTargetLang()] ?? '-na-')
                . ($task->getRelaisLang() ? (' Pivot: ' . $languages[$task->getRelaisLang()] ?? '-na-') : ''),
            'Segment progress' => $task->getSegmentFinishCount() . ' / ' . $task->getSegmentEditableCount(),
            'Data Dir' => $task->getAbsoluteTaskDataPath(),
        ];
        $this->writeAssoc($data);
        $this->writeLastErrors($task);
        $this->writeTimings($task);
        if ($task->isProject()) {
            $this->writeTasks($task);
        }

        /*
        //TODO info about the task
        // list errors like in systemctl status the log
        // list the associated users (and their locking status)
        // list the associated languageresources
        // worker
        task:info command
        - termtagger status
        - which worker is running if any
         * */
    }

    protected function writeLastErrors(Task $task): void
    {
        $events = \ZfExtended_Factory::get(editor_Models_Logger_Task::class);
        $errors = $events->loadLastErrors($task->getTaskGuid());

        if (empty($errors)) {
            return;
        }

        $this->io->section('Last Log (Errors / Warnings)');
        foreach ($errors as $row) {
            $this->io->text('  ' . $row['created'] . ' ' .
                LogCommand::LEVELS[$row['level']] . ' <options=bold>' . $row['eventCode'] . '</> ' .
                OutputFormatter::escape((string) $row['domain']) . ' → ' .
                OutputFormatter::escape((string) str_replace("\n", ' ', $row['message'])));
        }
    }

    private function writeTimings(Task $task): void
    {
        $segmentCount = (int) $task->getSegmentCount();
        $segmentDivisor = max(1, $segmentCount / 100);

        $events = \ZfExtended_Factory::get(editor_Models_Logger_Task::class);
        $workerLog = array_reverse($events->getByTaskGuidAndEventCodes($task->getTaskGuid(), ['E1547']));

        if (empty($workerLog)) {
            return;
        }

        $this->io->section('Worker timings');

        $table = $this->io->createTable();
        $table->setHeaders([
            'Task Status',
            'Worker',
            'id',
            'start',
            'end',
            'duration',
            'ø 100 Seg.',
            'sum',
            'state',
        ]);

        $idx = 0;
        $workerCount = count($workerLog);
        $sum = 0;
        foreach ($workerLog as $item) {
            try {
                $extra = json_decode($item['extra'], flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $table->addRow([$item['state'], 'Can not decode extra data: ' . $e->getMessage(), 0]);
                $idx++;

                continue;
            }
            if ($idx === 0) {
                $sum = $this->addGapRow($extra, $task, $table, $sum);
            }
            $table->addRow([
                $item['state'],
                $extra->worker,
                $extra->id,
                $extra->start,
                $extra->end,
                $extra->duration,
                round($extra->duration / $segmentDivisor),
                $sum += $extra->duration,
                $extra->state,
            ]);
            if ((($idx + 1) < $workerCount) && in_array($extra->worker, self::WORKER_SECTION_END)) {
                $sum = 0;
                $table->addRow(new TableSeparator());
            }
            $idx++;
        }

        $table->render();
    }

    private function addGapRow(mixed $extra, Task $task, Table $table, int $sum): int
    {
        $gapDuration = strtotime($extra->start) - strtotime($task->getCreated());
        $table->addRow([
            'created',
            'gap between task creation and worker start',
            '',
            $task->getCreated(),
            $extra->start, //start of first worker
            $gapDuration,
            '-',
            $sum += $gapDuration,
            $extra->state,
        ]);

        return $sum;
    }

    private function writeTasks(Task $project)
    {
        $tasks = (new Task())->loadProjectTasks((int) $project->getId(), true);

        $table = $this->io->createTable();
        $table->setHeaders([
            'id',
            'created',
            'Task name',
            'status',
            'import start',
            'import end',
            'duration d:h:m:s (s)',
        ]);

        $projectStart = $project->getCreated();
        $projectEnd = null;

        $events = \ZfExtended_Factory::get(editor_Models_Logger_Task::class);
        foreach ($tasks as $task) {
            $startImport = null;
            $endImport = null;
            $workerLog = $events->getByTaskGuidAndEventCodes($task['taskGuid'], ['E1547']);
            foreach ($workerLog as $item) {
                try {
                    $extra = json_decode($item['extra'], flags: JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    continue;
                }
                if ($extra->worker == editor_Models_Import_Worker_FinalStep::class) {
                    $endImport = $extra->end;
                } elseif ($extra->worker == \editor_Models_Import_Worker::class) {
                    $startImport = $extra->start;
                }
            }

            $table->addRow([
                $task['id'],
                $task['created'],
                $task['taskName'],
                $task['state'],
                $startImport,
                $endImport,
                $this->printDuration($startImport, $endImport),
            ]);

            $projectStart = min($projectStart, $startImport);
            $projectEnd = max($projectEnd, $endImport);
        }

        //project itself:
        $table->addRow([
            $project->getId(),
            $project->getCreated(),
            $project->getTaskName(),
            $project->getState(),
            $projectStart,
            $projectEnd,
            $this->printDuration($projectStart, $projectEnd),
        ]);

        $table->render();
    }
}
