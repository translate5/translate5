<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Statistics\Dto\StatisticFilterDTO;
use MittagQI\Translate5\Statistics\Helpers\AggregateTaskStatistics;
use MittagQI\Translate5\Statistics\Helpers\LevenshteinCalcTaskHistory;
use MittagQI\Translate5\Statistics\SegmentStatisticsRepository;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\Task;
use MittagQI\Translate5\Test\JsonTestAbstract;
use PHPUnit\Framework\AssertionFailedError;

class StatisticsAggregateTest extends JsonTestAbstract
{
    private static SegmentStatisticsRepository $aggregation;

    private static AggregateTaskStatistics $aggregateTask;

    private static LevenshteinCalcTaskHistory $levenshteinCalc;

    private static Zend_Db_Adapter_Abstract $db;

    private array $segments = [];

    private const int TASK_IDX_RECREATE_PARITY = 0;

    private const int TASK_IDX_MULTI_A = 1;

    private const int TASK_IDX_MULTI_B = 2;

    private const int TASK_IDX_FILTER = 3;

    /**
     * @throws Zend_Exception
     */
    protected static function setupImport(Config $config): void
    {
        self::$aggregation = SegmentStatisticsRepository::create();
        self::$aggregateTask = AggregateTaskStatistics::create();
        self::$levenshteinCalc = LevenshteinCalcTaskHistory::create();
        self::$db = Zend_Db_Table::getDefaultAdapter();

        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $config = Zend_Registry::get('config');
        if (! (bool) $config->resources->db->statistics?->enabled) {
            self::markTestSkipped('Runs only with resources.db.statistics.enabled = 1');
        }
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Db_Statement_Exception
     * @throws \MittagQI\Translate5\Segment\Exception\InvalidInputForLevenshtein
     */
    public function testStatisticsRecreateFromHistoryParity(): void
    {
        $task = static::getTaskAt(self::TASK_IDX_RECREATE_PARITY);
        $this->preparePmAndTranslatorFlow($task);

        $before = self::getKpi([$task->getTaskGuid()]);
        $this->assertLevenshteinData([
            'levenshteinPrevious' => 2 / 3,
            'levenshteinOriginal' => 5 / 3,
            'levenshteinStart' => 1.0,
            'levenshteinEnd' => 0.0,
        ], $before, $task);

        self::recreateKPIsFromHistory($task->getTaskGuid());

        $after = self::getKpi([$task->getTaskGuid()]);
        $this->assertSame($before, $after);
        $this->assertLevenshteinData([
            'levenshteinPrevious' => 2 / 3,
            'levenshteinOriginal' => 5 / 3,
            'levenshteinStart' => 1.0,
            'levenshteinEnd' => 0.0,
        ], $after, $task);
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testStatisticsAggregateAcrossMultipleTasks(): void
    {
        $taskA = static::getTaskAt(self::TASK_IDX_MULTI_A);
        $taskB = static::getTaskAt(self::TASK_IDX_MULTI_B);

        $this->prepareSingleTranslationTask($taskA, 0, 1);
        $this->prepareSingleTranslationTask($taskB, 1, 2);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 1 / 3,
            'levenshteinOriginal' => 1 / 3,
        ], self::getKpi([$taskA->getTaskGuid()]), $taskA);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 2 / 3,
            'levenshteinOriginal' => 2 / 3,
        ], self::getKpi([$taskB->getTaskGuid()]), $taskB);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 1 / 2,
            'levenshteinOriginal' => 1 / 2,
        ], self::getKpi([$taskA->getTaskGuid(), $taskB->getTaskGuid()]), [
            $taskA->getTaskGuid(),
            $taskB->getTaskGuid(),
        ]);
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws JsonException
     */
    public function testStatisticsFilteringEndToEnd(): void
    {
        $task = static::getTaskAt(self::TASK_IDX_FILTER);
        [$translatorGuid, $reviewerGuid] = $this->prepareFilteredTask($task);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 1 / 2,
            'levenshteinOriginal' => 1,
        ], self::getKpi([$task->getTaskGuid()]), $task);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 0.5,
            'levenshteinOriginal' => '-',
        ], self::getKpi([$task->getTaskGuid()], StatisticFilterDTO::fromAssocArray([
            'userName' => [$translatorGuid],
        ])), $task);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 2.0,
            'levenshteinOriginal' => '-',
        ], self::getKpi([$task->getTaskGuid()], StatisticFilterDTO::fromAssocArray([
            'userName' => [$reviewerGuid],
        ])), $task);

        $this->assertLevenshteinData([
            'levenshteinPrevious' => 2 / 3,
            'levenshteinOriginal' => '-',
        ], self::getKpi([$task->getTaskGuid()], StatisticFilterDTO::fromAssocArray([
            'workflowStep' => ['reviewing'],
        ])), $task);

        $apiResult = (array) self::getKpiViaApi([$task->getTaskGuid()], [
            [
                'property' => 'workflowStep',
                'operator' => 'in',
                'value' => ['reviewing'],
            ],
        ]);
        $this->assertLevenshteinData([
            'levenshteinPrevious' => 0.66667,
            'levenshteinOriginal' => '-',
        ], $apiResult, $task);
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function preparePmAndTranslatorFlow(Task $task): void
    {
        $this->setTask($task, 3);

        $this->updateSegment($task->getId(), 2, 3, false);

        static::api()->setTaskToOpen($task->getId());

        sleep(1);
        self::addUserToTask($task->getTaskGuid(), TestUser::TestTranslator->value, 'translation');
        sleep(1);

        static::api()->login(TestUser::TestTranslator->value);
        self::assertLogin(TestUser::TestTranslator->value);

        $this->updateSegment($task->getId(), 0, 2);
        static::api()->setTaskToFinished($task->getId());

        static::api()->login(TestUser::TestManager->value);
        self::assertLogin(TestUser::TestManager->value);
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function prepareSingleTranslationTask(Task $task, int $segmentIndex, int $charsAdded): void
    {
        $this->setTask($task, 3);

        sleep(1);
        self::addUserToTask($task->getTaskGuid(), TestUser::TestTranslator->value, 'translation');
        sleep(1);

        static::api()->login(TestUser::TestTranslator->value);
        self::assertLogin(TestUser::TestTranslator->value);

        $this->updateSegment($task->getId(), $segmentIndex, $charsAdded);
        static::api()->setTaskToFinished($task->getId());

        static::api()->login(TestUser::TestManager->value);
        self::assertLogin(TestUser::TestManager->value);
    }

    /**
     * @return array{0: string, 1: string}
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function prepareFilteredTask(Task $task): array
    {
        $this->setTask($task, 3);

        $translator = self::addUserToTask($task->getTaskGuid(), TestUser::TestTranslator->value, 'translation');
        $reviewer = self::addUserToTask($task->getTaskGuid(), TestUser::TestManager2->value, 'reviewing');

        sleep(1);

        static::api()->login(TestUser::TestTranslator->value);
        self::assertLogin(TestUser::TestTranslator->value);
        $this->updateSegment($task->getId(), 0, 1);
        static::api()->setTaskToFinished($task->getId());

        static::api()->login(TestUser::TestManager2->value);
        self::assertLogin(TestUser::TestManager2->value);
        $this->updateSegment($task->getId(), 1, 2);
        static::api()->setTaskToFinished($task->getId());

        static::api()->login(TestUser::TestManager->value);
        self::assertLogin(TestUser::TestManager->value);

        return [$translator->userGuid, $reviewer->userGuid];
    }

    private function setTask(Task $task, int $segmentsCount): void
    {
        static::api()->setTaskToEdit($task->getId());
        static::api()->setTask($task->getAsObject());

        $this->segments = static::api()->getSegments(null, $segmentsCount + 1);
        self::assertEquals($segmentsCount, count($this->segments));
    }

    private function updateSegment(int $taskId, int $segmentIndex, int $charsAdded, bool $setToEdit = true): void
    {
        if ($setToEdit) {
            static::api()->setTaskToEdit($taskId);
        }
        if ($charsAdded > 0) {
            $this->segments[$segmentIndex]->targetEdit .= str_repeat('1', $charsAdded);
        } elseif ($charsAdded < 0) {
            $this->segments[$segmentIndex]->targetEdit = substr(
                $this->segments[$segmentIndex]->targetEdit,
                0,
                $charsAdded
            );
        }
        static::api()->saveSegment(
            (int) $this->segments[$segmentIndex]->id,
            $this->segments[$segmentIndex]->targetEdit
        );
    }

    /**
     * @param array<string, float|int|string> $expectedData
     * @param array<string, mixed> $actualData
     * @param Task|array<int, string> $taskOrTaskGuids
     */
    private function assertLevenshteinData(array $expectedData, array $actualData, Task|array $taskOrTaskGuids): void
    {
        $actualSubset = array_intersect_key($actualData, $expectedData);

        try {
            self::assertEquals($expectedData, $actualSubset);
        } catch (AssertionFailedError $e) {
            $this->dumpStatisticsInspect($taskOrTaskGuids);

            throw $e;
        }
    }

    /**
     * @param Task|array<int, string> $taskOrTaskGuids
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function dumpStatisticsInspect(Task|array $taskOrTaskGuids): void
    {
        $taskGuids = $taskOrTaskGuids instanceof Task
            ? [$taskOrTaskGuids->getTaskGuid()]
            : $taskOrTaskGuids;

        $taskGuids = array_map('escapeshellarg', $taskGuids);

        try {
            $output = shell_exec(sprintf(
                '/var/www/translate5/translate5.sh statistics:inspect -n %s 2>&1',
                implode(' ', $taskGuids),
            ));
            fwrite(
                STDERR,
                PHP_EOL . '[statistics:inspect ' . implode(' ', $taskGuids) . ']'
                . PHP_EOL . ($output ?? '(no output)') . PHP_EOL
            );
        } catch (\Throwable) {
            // keep original assertion failure untouched
        }
    }

    private static function addUserToTask(string $taskGuid, string $user, string $step): StdClass
    {
        return static::api()->addUserToTask(
            $taskGuid,
            $user,
            editor_Workflow_Default::STATE_OPEN,
            $step
        );
    }

    private static function getKpi(array $taskGuids, ?StatisticFilterDTO $statisticFilter = null): array
    {
        $stat = self::$aggregation->getStatistics($taskGuids, $statisticFilter);

        return [
            'levenshteinPrevious' => $stat['levenshteinDistanceInWorkflowStep'] ?? '-',
            'levenshteinOriginal' => $stat['levenshteinDistanceOriginal'] ?? '-',
            'levenshteinStart' => $stat['levenshteinDistanceNoWorkflow'] ?? '-',
            'levenshteinEnd' => $stat['levenshteinDistanceWorkflowEnded'] ?? '-',
        ];
    }

    private static function getKpiViaApi(array $taskGuids, array $filters = []): StdClass
    {
        $filters[] = [
            'property' => 'taskGuid',
            'operator' => 'in',
            'value' => $taskGuids,
        ];

        return static::api()->getJson(
            'editor/task/kpi?filter=' . urlencode(json_encode($filters, flags: JSON_THROW_ON_ERROR)),
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Segment\Exception\InvalidInputForLevenshtein
     * @throws Zend_Db_Statement_Exception
     */
    private static function recreateKPIsFromHistory(string $taskGuid): void
    {
        self::$db->query(
            'DELETE FROM LEK_segment_statistics WHERE taskGuid = ?',
            [trim($taskGuid, '{}')]
        );
        self::$aggregateTask->removeData($taskGuid);
        self::$levenshteinCalc->calculate($taskGuid);
        self::$aggregateTask->aggregateHistoricalData($taskGuid);
    }
}
