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

declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command;

use editor_Models_KPI;
use editor_Models_Task;
use MittagQI\Translate5\Repository\SegmentHistoryAggregationRepository;
use MittagQI\Translate5\Statistics\Dto\AggregationFilter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Models_Entity_NotFoundException;

class StatisticsInspectCommand extends TaskCommand
{
    protected static $defaultName = 'statistics:inspect';

    protected function configure(): void
    {
        $this
            ->setDescription('Shows segment history aggregation data for one or more tasks.')
            ->setHelp('A task-identifier must be given, then the segment history aggregation data is printed.');

        $this->addArgument(
            'taskIdentifier',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );

        $this->addOption(
            'workflow',
            'w',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Filter by workflow step name (e.g. "reviewing" or "default::reviewing").'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();
        $this->writeTitle('Statistics Inspect');

        $taskIdentifiers = $input->getArgument('taskIdentifier');
        $tasks = $this->resolveTasks($taskIdentifiers);

        if (empty($tasks)) {
            $this->io->error('No tasks found.');

            return self::FAILURE;
        }

        $historyAggregationData = SegmentHistoryAggregationRepository::create();
        $aggregationRows = [];
        $levenshteinRows = [];

        $taskData = [];
        foreach ($tasks as $task) {
            $aggregationRows = array_merge(
                $aggregationRows,
                $historyAggregationData->getAggregationRowsByTaskGuid($task->getTaskGuid())
            );
            $levenshteinRows = array_merge(
                $levenshteinRows,
                $historyAggregationData->getLevenshteinRowsByTaskGuid($task->getTaskGuid())
            );
            $taskData[] = (array) $task->getDataObject();
        }

        $this->renderAggregatedData($aggregationRows, $tasks);
        $this->renderLevenshteinData($levenshteinRows, $tasks);

        $workflowFilterValues = $this->normalizeWorkflowFilters($input->getOption('workflow'));

        $kpi = new editor_Models_KPI(SegmentHistoryAggregationRepository::create());
        $kpi->setTasks($taskData);
        $kpiStatistics = $kpi->getStatistics(
            $this->buildWorkflowFilters($workflowFilterValues),
            $this->buildWorkflowAggregationFilters($workflowFilterValues)
        );

        $this->io->section('KPI statistics summary');
        $this->writeAssoc($kpiStatistics);

        return self::SUCCESS;
    }

    /**
     * @return editor_Models_Task[]
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function resolveTasks(array $taskIdentifiers): array
    {
        $tasks = [];
        foreach ($taskIdentifiers as $taskIdentifier) {
            $task = static::findTaskFromArgument(
                $this->io,
                $taskIdentifier,
                false,
                TaskCommand::taskTypesWithData()
            );
            if ($task === null) {
                continue;
            }
            $tasks[$task->getTaskGuid()] = $task;
        }

        return $tasks;
    }

    private function renderAggregatedData(array $aggregationRows, array $tasks): void
    {
        $this->io->section('Segment history aggregation data');

        $table = $this->io->createTable();
        $table->setHeaders(['Task', 'Seg ID', 'Workflow', 'Editable', 'Duration (ms)', 'MatchRate', 'Lang Res ID']);

        $durationSum = 0;
        foreach ($aggregationRows as $row) {
            $durationSum += $row['duration'];
            $table->addRow([
                $tasks['{' . $row['taskGuid'] . '}']->getId() . ' (' . $row['taskGuid'] . ')',
                $row['segmentId'],
                $row['workflowName'] . '::' . $row['workflowStepName'],
                $row['editable'],
                $row['duration'],
                $row['matchRate'],
                $row['langResId'] . ' (' . $row['langResType'] . ')',
            ]);
        }

        $table->addRow([
            'Duration SUM',
            '',
            '',
            '',
            $durationSum,
            '',
            '',
        ]);

        $table->render();
    }

    private function renderLevenshteinData(array $levenshteinRows, array $tasks): void
    {
        $this->io->section('Segment history aggregation levenshtein data');
        $table = $this->io->createTable();
        $table->setHeaders([
            'Task',
            'Seg ID',
            'Workflow',
            'Editable',
            'Is last edit',
            'Levensth. Orig',
            'Levensth. Prev',
            'MatchRate',
            'Lang Res ID',
        ]);
        foreach ($levenshteinRows as $row) {
            $table->addRow([
                $tasks['{' . $row['taskGuid'] . '}']->getId() . ' (' . $row['taskGuid'] . ')',
                $row['segmentId'],
                $row['workflowName'] . '::' . $row['workflowStepName'],
                $row['editable'],
                $row['lastEdit'],
                $row['levenshteinOriginal'],
                $row['levenshteinPrevious'],
                $row['matchRate'],
                $row['langResId'] . ' (' . $row['langResType'] . ')',
            ]);
        }

        $table->render();
    }

    private function normalizeWorkflowFilters(array $workflowOptions): array
    {
        if (empty($workflowOptions)) {
            return [];
        }

        $values = array_map(
            function (string $value): string {
                if (str_contains($value, '::')) {
                    $parts = explode('::', $value, 2);
                    $value = $parts[1];
                }

                return trim($value);
            },
            $workflowOptions
        );

        $values = array_filter($values, static fn (string $value): bool => $value !== '');

        return array_values(array_unique($values));
    }

    private function buildWorkflowFilters(array $workflowSteps): array
    {
        if (empty($workflowSteps)) {
            return [];
        }

        $filter = new \stdClass();
        $filter->property = 'workflowStep';
        $filter->value = $workflowSteps;

        return [$filter];
    }

    /**
     * @return AggregationFilter[]
     */
    private function buildWorkflowAggregationFilters(array $workflowSteps): array
    {
        if (empty($workflowSteps)) {
            return [];
        }

        return [
            new AggregationFilter('workflowStepName', $workflowSteps, true),
        ];
    }
}
