<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Statistics\Dto\StatisticFilterDTO;
use MittagQI\Translate5\Statistics\SegmentStatisticsRepository;

/**
 * KPI (Key Point Indicators) are handled in this class.
 */
class editor_Models_KPI
{
    public const string KPI_REVIEWER = 'averageProcessingTimeReviewer';

    public const string KPI_TRANSLATOR = 'averageProcessingTimeTranslator';

    public const string KPI_TRANSLATOR_CHECK = 'averageProcessingTimeSecondTranslator';

    public const string KPI_DURATION = 'posteditingTime';

    public const string KPI_DURATION_TOTAL = 'posteditingTimeTotal';

    public const string KPI_DURATION_START = 'posteditingTimeStart';

    public const string KPI_DURATION_END = 'posteditingTimeEnd';

    public const string KPI_LEVENSHTEIN_PREVIOUS = 'levenshteinPrevious';

    public const string KPI_LEVENSHTEIN_ORIGINAL = 'levenshteinOriginal';

    public const string KPI_LEVENSHTEIN_START = 'levenshteinStart';

    public const string KPI_AFFECTED_SEGMENTS = 'affectedSegments';

    public const string KPI_LEVENSHTEIN_END = 'levenshteinEnd';

    public const array ROLE_TO_KPI_KEY = [
        editor_Workflow_Default::ROLE_TRANSLATOR => self::KPI_TRANSLATOR,
        editor_Workflow_Default::ROLE_REVIEWER => self::KPI_REVIEWER,
        editor_Workflow_Default::ROLE_TRANSLATORCHECK => self::KPI_TRANSLATOR_CHECK,
    ];

    /**
     * Tasks the KPI are to be calculated for.
     */
    protected array $tasks = [];

    protected ZfExtended_Zendoverwrites_Translate $translate;

    public function __construct(
        private readonly SegmentStatisticsRepository $aggregation
    ) {
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }

    public static function getAggregateMetrics(): array
    {
        return [
            self::KPI_LEVENSHTEIN_START,
            self::KPI_DURATION_START,
            self::KPI_DURATION,
            self::KPI_LEVENSHTEIN_PREVIOUS,
            self::KPI_DURATION_TOTAL,
            self::KPI_LEVENSHTEIN_ORIGINAL,
            self::KPI_LEVENSHTEIN_END,
            self::KPI_DURATION_END,
        ];
    }

    /**
     * Set the tasks the KPI are to be calculated for.
     */
    public function setTasks(array $rows): void
    {
        $this->tasks = $rows;
    }

    /**
     * Can KPI-statistics be calculated at all?
     */
    protected function hasStatistics(): bool
    {
        // no tasks? no statistics!
        return count($this->tasks) > 0;
    }

    /**
     * Get the KPI-statistics.
     * @throws ReflectionException
     */
    public function getStatistics(?StatisticFilterDTO $statisticFilter): array
    {
        $statistics = $this->getAverageProcessingTime($statisticFilter);
        $statistics['excelExportUsage'] = $this->getExcelExportUsage();

        return array_merge($statistics, $this->getAggregateStats($statisticFilter));
    }

    /**
     * Calculate and return the average processing time for the tasks by role.
     * return array of strings or '-' if statistics can't be calculated
     */
    protected function getAverageProcessingTime(?StatisticFilterDTO $statisticFilter): array
    {
        $results = [];
        foreach (self::ROLE_TO_KPI_KEY as $label) {
            $results[$label] = '-';
        }
        if (! $this->hasStatistics()) {
            return $results;
        }
        $taskGuids = array_column($this->tasks, 'taskGuid');

        $workflowSteps = [];
        $workflowStepsGrouped = [];
        $workflowStepTypes = array_keys(self::ROLE_TO_KPI_KEY);
        if (! empty($statisticFilter->workflowStep)) {
            $workflowSteps = $statisticFilter->workflowStep;
            $workflowStepsGrouped = $statisticFilter->getGroupWorkflowStepsByWorkflow();
        }
        if (! empty($statisticFilter->workflowUserRole)) {
            $workflowStepTypes = $statisticFilter->workflowUserRole;
        }

        //load all task assocs for the filtered tasks
        //only reviewer,translator and translatorCheck roles are loaded
        $tua = new editor_Models_TaskUserAssoc();
        $timeResult = $tua->loadKpiData($taskGuids, $workflowStepTypes, $workflowStepsGrouped);
        $time = [];
        foreach ($timeResult as $row) {
            $time[$row['timeBy']] = $row['time'];
        }

        if (empty($workflowSteps)) {
            $timeToResultKey = self::ROLE_TO_KPI_KEY;
        } else {
            // limited to 3 by current KPI window height
            $kpiLimit = 3;
            $workflowStepsWithData = array_slice(array_keys($time), 0, $kpiLimit);
            if (count($workflowStepsWithData) >= $kpiLimit) {
                $workflowSteps = array_slice($workflowStepsWithData, 0, $kpiLimit);
            } else {
                $stepsWithoutData = array_diff($workflowSteps, $workflowStepsWithData);
                if (! empty($stepsWithoutData)) {
                    $workflowSteps = array_merge(
                        $workflowStepsWithData,
                        array_slice($stepsWithoutData, 0, $kpiLimit - count($workflowStepsWithData))
                    );
                }
            }
            $timeToResultKey = [];
            foreach ($workflowSteps as $workflowStep) {
                $timeToResultKey[$workflowStep] = $workflowStep;
            }
            $results = [];
            $results['byWorkflowSteps'] = implode(',', $workflowSteps);
        }

        foreach ($timeToResultKey as $timeBy => $key) {
            if (! array_key_exists($timeBy, $time)) {
                continue;
            }
            $results[$key] = (isset($time[$timeBy]) ? round(
                $time[$timeBy]
            ) : 0) . ' ' . $this->translate->_('days');
        }

        return $results;
    }

    /**
     * Calculate and return the Excel-export-usage of the tasks
     * (= percent of the tasks exported at least once).
     * @return string Percentage (0-100%) or '-' if statistics can't be calculated
     * @throws ReflectionException
     */
    protected function getExcelExportUsage(): string
    {
        if (! $this->hasStatistics()) {
            return '-';
        }
        $nrExported = 0;

        // If this is will ever be needed for showing the taskGrid, we should not
        // iterate through all filtered tasks, but change to pure SQL.
        $allTaskGuids = array_column($this->tasks, 'taskGuid');
        $excelExport = ZfExtended_Factory::get('editor_Models_Task_ExcelExport');
        /* @var $excelExport editor_Models_Task_ExcelExport */
        foreach ($allTaskGuids as $taskGuid) {
            if ($excelExport->isExported($taskGuid)) {
                $nrExported++;
            }
        }

        $taskCount = count($allTaskGuids);

        if (0 === $taskCount) {
            return '-';
        }

        // after $this->hasStatistics(), count($allTaskGuids) will always be > 0
        $percentage = ($nrExported / count($allTaskGuids)) * 100;

        return round($percentage, 2) . '%';
    }

    /**
     * Calculate and return avg duration and Levenshtein distance for the tasks
     * returns hash of strings '2.5' or '-' if statistics can't be calculated
     */
    protected function getAggregateStats(?StatisticFilterDTO $statisticFilter): array
    {
        $results = [];
        foreach (self::getAggregateMetrics() as $key) {
            $results[$key] = '-';
        }

        if (! $this->hasStatistics()) {
            return $results;
        }

        $taskTypes = array_flip(editor_Task_Type::getInstance()->getNonInternalTaskTypes());

        $stats = $this->aggregation->getStatistics(
            array_column(array_filter($this->tasks, function ($task) use ($taskTypes) {
                return isset($taskTypes[$task['taskType']]);
            }), 'taskGuid'),
            $statisticFilter
        );

        if (empty($stats)) {
            $results[self::KPI_DURATION] = $this->translate->_('Data unavailable');

            return $results;
        }

        $roundDuration = static fn (?float $value): string|float => $value === null ? '-' : round($value / 1000, 2);
        $roundLevenshtein = static fn (?float $value): string|float => $value === null
            ? '-'
            : round($value, SegmentStatisticsRepository::LEVENSHTEIN_PRECISION);

        $results[self::KPI_AFFECTED_SEGMENTS] = $roundLevenshtein($stats['affectedSegments']);
        $results[self::KPI_LEVENSHTEIN_START] = $roundLevenshtein($stats['levenshteinDistanceNoWorkflow'] ?? 0.0);
        $results[self::KPI_DURATION_START] = $roundDuration($stats['posteditingTimeNoWorkflow'] ?? null);
        $results[self::KPI_DURATION] = $roundDuration($stats['posteditingTimeInWorkflowStep']);
        $results[self::KPI_LEVENSHTEIN_PREVIOUS] = $roundLevenshtein(
            $stats['levenshteinDistanceInWorkflowStep']
        );
        $results[self::KPI_DURATION_TOTAL] = $roundDuration($stats['posteditingTimeInAllWorkflowSteps']);
        $results[self::KPI_LEVENSHTEIN_ORIGINAL] = $roundLevenshtein($stats['levenshteinDistanceOriginal'] ?? null);
        $results[self::KPI_LEVENSHTEIN_END] = $roundLevenshtein($stats['levenshteinDistanceWorkflowEnded'] ?? null);
        $results[self::KPI_DURATION_END] = $roundDuration($stats['posteditingTimeWorkflowEnded'] ?? null);

        return $results;
    }
}
