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

use MittagQI\Translate5\Repository\SegmentHistoryAggregationRepository;
use MittagQI\Translate5\Statistics\Helpers\AggregateTaskHistory;
use MittagQI\Translate5\Statistics\Helpers\LevenshteinCalcTaskHistory;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\Task;
use MittagQI\Translate5\Test\JsonTestAbstract;

class StatisticsAggregateTest extends JsonTestAbstract
{
    private static SegmentHistoryAggregationRepository $aggregation;

    private static AggregateTaskHistory $aggregateTask;

    private static LevenshteinCalcTaskHistory $levenshteinCalc;

    private static Zend_Db_Adapter_Abstract $db;

    private array $segments = [];

    private array $userGuids = [];

    private bool $testRecreatingFromHistory = false;

    /*protected static bool $skipIfOptionsMissing = true;

    protected static array $requiredRuntimeOptions = [
        'resources.db.statistics.enabled' => 1,
    ];*/

    protected static function setupImport(Config $config): void
    {
        self::$aggregation = SegmentHistoryAggregationRepository::create();
        self::$aggregateTask = new AggregateTaskHistory();
        self::$levenshteinCalc = new LevenshteinCalcTaskHistory();
        self::$db = Zend_Db_Table::getDefaultAdapter();

        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '7_trans_units.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        // Tasks for filter tests
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments.xlf');
        // Tasks w/o pretranslation
        $config->addTask('de', 'en', -1, '2_trans_unit_no_pretranslation.xlf');
    }

    public function testLevenshteinDistanceChanges(): void
    {
        /*if (!(int)static::api()->getConfig('resources.db.statistics.enabled')) {
            self::markTestSkipped('runs only if resources.db.statistics.enabled = 1');
        }*/
        $this->testRecreatingFromHistory = true;
        $taskIdx = 0;
        $this->runTest_01(static::getTaskAt($taskIdx), 3);
        $this->runTest_02(static::getTaskAt($taskIdx + 1), 3);
        $this->runTest_03([static::getTaskAt($taskIdx)->getTaskGuid(), static::getTaskAt($taskIdx + 1)->getTaskGuid()]);
        $this->runTest_04(static::getTaskAt($taskIdx + 2), 7);
        $this->runTest_05(
            [static::getTaskAt($taskIdx + 2)->getTaskGuid(), static::getTaskAt($taskIdx + 1)->getTaskGuid()]
        );
        $this->runTest_06(static::getTaskAt($taskIdx + 3), 3);

        $this->runFilterTest_01(static::getTaskAt($taskIdx + 4), 3);
        $this->runFilterTest_02(static::getTaskAt($taskIdx + 5), 3);
        $this->runFilterTest_03(
            [static::getTaskAt($taskIdx + 4)->getTaskGuid(), static::getTaskAt($taskIdx + 5)->getTaskGuid()]
        );
        $this->runFilterTest_04(static::getTaskAt($taskIdx + 6), 3);

        $this->runTest_NoPretranslation(static::getTaskAt($taskIdx + 7), 2);
    }

    private function runTest_NoPretranslation(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        // add manual translation to be seen as first version
        $this->addJobAndMakeChange($task, [
            0 => 10,
            1 => 10,
        ], 0, 0, 0, 0, 'translation', false);
        $this->addJobAndMakeChange($task, [
            0 => 3,
        ], 3, 3, 1.5, 1.5);
        //$this->assertTrue(false, 'Res: ' . print_r($res, true));
    }

    private function runTest_01(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        $this->runTestSteps_01_04($task);

        // test aggregation filtering by additional filter
        $res = self::getKpiViaApi([$task->getTaskGuid()], [
            [
                'property' => 'matchRateMin',
                'operator' => 'gteq',
                'value' => '90',
            ],
        ]);

        self::assertEquals(2, $res->levenshteinOriginal);
        self::assertEquals(2, $res->levenshteinPrevious);

        // step 5: PM makes one more change in segment #2
        $this->updateSegment($task->getId(), 1, 1);

        // @phpstan-ignore-next-line
        for ($i = 1; $i <= 2; $i++) {
            $res = self::getKpi([$task->getTaskGuid()]);
            self::assertEquals(1, $res['levenshteinEnd']);
            self::assertEquals(0.66667, $res['levenshteinOriginal']);
            self::assertEquals(0.66667, $res['levenshteinPrevious']);

            if ($i === 1 && $this->testRecreatingFromHistory) {
                self::recreateKPIsFromHistory($task->getTaskGuid());
            } else {
                break;
            }
        }

        // step 6: end task
        static::api()->setTaskToFinished($task->getId());
        static::api()->endTask($task->getId());

        $res = self::getKpi([$task->getTaskGuid()]);
        self::assertEquals(0.33333, $res['levenshteinEnd']);
        self::assertEquals(0.66667, $res['levenshteinOriginal']);
        self::assertEquals(0.66667, $res['levenshteinPrevious']);
    }

    private function runTest_02(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        $this->runTestSteps_01_04($task, false);
        // steps 5 and 6
        $this->addJobAndMakeChange($task, [
            1 => 1,
        ], 1, 0.75, 1, 0.5);
    }

    private function runTest_03(array $taskGuids): void
    {
        $res = self::getKpi($taskGuids);
        self::assertEquals(0.83333, $res['levenshteinOriginal']);
        self::assertEquals(0.55556, $res['levenshteinPrevious']);
    }

    private function runTest_04(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        $this->addJobAndMakeChange($task, [
            1 => 1,
        ], 1, 1, 0.16667, 0.16667);
    }

    private function runTest_05(array $taskGuids): void
    {
        $res = self::getKpi($taskGuids);
        self::assertEquals(0.44444, $res['levenshteinOriginal']);
        self::assertEquals(0.33333, $res['levenshteinPrevious']);
    }

    private function runTest_06(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        $this->runTestSteps_01_04($task);
        $this->addJobAndMakeChange($task, [
            0 => -1,
        ], 0.33333, 0.75, 0.33333, 0.5);
    }

    private function runTestSteps_01_04(Task $task, bool $verifyKPIs = true): void
    {
        // step 1: PM makes 3 changes in segment #03 and saves it
        $this->updateSegment($task->getId(), 2, 3, false);

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals(3, $res['levenshteinStart']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        static::api()->setTaskToOpen($task->getId());

        // step 2: user for translation is assigned to the task
        sleep(1);
        self::addUserToTask($task->getTaskGuid(), TestUser::TestTranslator->value, 'translation');
        sleep(1);
        // levenshteinStart changes from 3 to 1 now (0 changed segments are added)

        static::api()->login(TestUser::TestTranslator->value);
        self::assertLogin(TestUser::TestTranslator->value);

        // step 3: make 2 changes, both in segment #01 and save it
        $this->updateSegment($task->getId(), 0, 2);

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals(1, $res['levenshteinStart']);
                self::assertEquals(2, $res['levenshteinOriginal']);
                self::assertEquals(2, $res['levenshteinPrevious']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        // step 4: finish workflow-step
        static::api()->setTaskToFinished($task->getId());

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals(0.66667, $res['levenshteinOriginal']);
                self::assertEquals($res['levenshteinOriginal'], $res['levenshteinPrevious']);
                self::assertEquals(1, $res['levenshteinStart']);
                self::assertEquals('-', $res['levenshteinEnd']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        static::api()->login(TestUser::TestManager->value);
        self::assertLogin(TestUser::TestManager->value);
    }

    private function runFilterTest_01(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        $this->runFilterTestSteps_01_03($task);
    }

    private function runFilterTest_02(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        $this->runFilterTestSteps_01_03($task, false);

        // steps 4 and 5: reviewer REVERTS change in segment 2 and finishes his job
        $this->addJobAndMakeChange($task, [
            1 => -1,
        ], 0.33333, 0.75, 0.33333, 0.5);
    }

    private function runFilterTest_03(array $taskGuids): void
    {
        // we inspect the 2 tasks from test #01 and #02 and sum up the results
        $res = self::getKpi($taskGuids);
        self::assertEquals(0.5, $res['levenshteinOriginal']);
        self::assertEquals(0.55556, $res['levenshteinPrevious']);

        $res = self::getKpi($taskGuids, self::getFilters([
            'userName' => $this->userGuids[TestUser::TestTranslator->value],
        ]));
        self::assertEquals(1, $res['levenshteinPrevious']);

        $res = self::getKpi($taskGuids, self::getFilters([
            'userName' => $this->userGuids[TestUser::TestManager2->value],
        ]));
        self::assertEquals(0.5, $res['levenshteinPrevious']);

        $res = self::getKpi($taskGuids, self::getFilters([
            'userName' => $this->userGuids[TestUser::TestLector->value],
        ]));

        self::assertEquals(0.33333, $res['levenshteinPrevious']);
    }

    private function runFilterTest_04(Task $task, int $segmentsCount): void
    {
        $this->setTask($task, $segmentsCount);
        // steps 1 and 2: translator makes one change in segment #01, reviewer makes one change in segment #02
        $this->runFilterTestSteps_01_03($task, false, 'reviewing');

        $res = self::getKpi([$task->getTaskGuid()]);
        self::assertEquals(0.66667, $res['levenshteinOriginal']);
        self::assertEquals(0.33333, $res['levenshteinPrevious']);

        // step 3: checker makes one change in segment #03 and finishes his WS
        $this->addJobAndMakeChange($task, [
            2 => 1,
        ], 1, 0.42857, 1, 0.33333, 'translatorCheck');

        // @phpstan-ignore-next-line
        for ($i = 1; $i <= 2; $i++) {
            $res = self::getKpi(
                [$task->getTaskGuid()],
                self::getFilters([
                    'workflowStep' => ['reviewing', 'translation'],
                ])
            );
            self::assertEquals(0.33333, $res['levenshteinPrevious']);

            if ($i === 1 && $this->testRecreatingFromHistory) {
                self::recreateKPIsFromHistory($task->getTaskGuid());
            } else {
                break;
            }
        }
    }

    private function runFilterTestSteps_01_03(
        Task $task,
        bool $verifyKPIs = true,
        string $workflowStep2 = 'translation',
    ): void {
        $tuaJson1 = self::addUserToTask($task->getTaskGuid(), TestUser::TestTranslator->value, 'translation');
        $this->userGuids[TestUser::TestTranslator->value] = $tuaJson1->userGuid;
        $tuaJson2 = self::addUserToTask($task->getTaskGuid(), TestUser::TestManager2->value, $workflowStep2);
        $this->userGuids[TestUser::TestManager2->value] = $tuaJson2->userGuid;

        sleep(1);

        static::api()->login(TestUser::TestTranslator->value);
        self::assertLogin(TestUser::TestTranslator->value);

        // step 1: translator1 makes one change in segment #01 and finishes his work
        $this->updateSegment($task->getId(), 0, 1);

        static::api()->setTaskToFinished($task->getId());

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals(1, $res['levenshteinOriginal']);
                self::assertEquals(1, $res['levenshteinPrevious']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        // step 2: translator2 makes one change in segment #02
        static::api()->login(TestUser::TestManager2->value);
        self::assertLogin(TestUser::TestManager2->value);

        $this->updateSegment($task->getId(), 1, 1);
        static::api()->setTaskToOpen($task->getId());

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals(1, $res['levenshteinOriginal']);
                self::assertEquals(1, $res['levenshteinPrevious']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        // step 3: work of translator2 is finished
        static::api()->setTaskToFinished($task->getId());

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);

                self::assertEquals(0.66667, $res['levenshteinOriginal']);
                self::assertEquals(0.66667, $res['levenshteinPrevious']);

                $res = self::getKpi([$task->getTaskGuid()], self::getFilters([
                    'userName' => $tuaJson1->userGuid,
                ]));
                self::assertEquals(1, $res['levenshteinPrevious']);

                $res = self::getKpi([$task->getTaskGuid()], self::getFilters([
                    'userName' => $tuaJson2->userGuid,
                ]));
                self::assertEquals(0.5, $res['levenshteinPrevious']);

                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        static::api()->login(TestUser::TestManager->value);
        self::assertLogin(TestUser::TestManager->value);
    }

    private function addJobAndMakeChange(
        Task $task,
        array $charsAddedBySegmentIndex,
        float $levenshteinOriginal1,
        float $levenshteinPrevious1,
        float $levenshteinOriginal2,
        float $levenshteinPrevious2,
        string $workflowStep = 'reviewing',
        bool $verifyKPIs = true,
    ): void {
        static::api()->setTaskToOpen($task->getId());

        sleep(1);
        $tuaJson = self::addUserToTask($task->getTaskGuid(), TestUser::TestLector->value, $workflowStep);
        sleep(1);

        static::api()->login(TestUser::TestLector->value);
        self::assertLogin(TestUser::TestLector->value);

        $this->userGuids[TestUser::TestLector->value] = $tuaJson->userGuid;

        // step 1: make a change per segment and save it
        foreach ($charsAddedBySegmentIndex as $segmentIndex => $charsAdded) {
            $this->updateSegment($task->getId(), $segmentIndex, $charsAdded);
        }

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals($levenshteinOriginal1, $res['levenshteinOriginal']);
                self::assertEquals($levenshteinPrevious1, $res['levenshteinPrevious']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        // step 2: reviewer finishes his work
        static::api()->setTaskToFinished($task->getId());

        if ($verifyKPIs) {
            // @phpstan-ignore-next-line
            for ($i = 1; $i <= 2; $i++) {
                $res = self::getKpi([$task->getTaskGuid()]);
                self::assertEquals($levenshteinOriginal2, $res['levenshteinOriginal']);
                self::assertEquals($levenshteinPrevious2, $res['levenshteinPrevious']);
                if ($i === 1 && $this->testRecreatingFromHistory) {
                    self::recreateKPIsFromHistory($task->getTaskGuid());
                } else {
                    break;
                }
            }
        }

        static::api()->login(TestUser::TestManager->value);
        self::assertLogin(TestUser::TestManager->value);
    }

    private function setTask(Task $task, int $segmentsCount): void
    {
        //open task for editing. This should not produce any error
        static::api()->setTaskToEdit($task->getId()); // locked otherwise
        static::api()->setTask($task->getAsObject());

        $this->segments = static::api()->getSegments(null, $segmentsCount + 1);
        self::assertEquals($segmentsCount, count($this->segments));
    }

    private function updateSegment(int $taskId, int $segmentIndex, int $charsAdded, bool $setToEdit = true): void
    {
        if ($setToEdit) {
            // locked otherwise
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
        static::api()->saveSegment((int) $this->segments[$segmentIndex]->id, $this->segments[$segmentIndex]->targetEdit);
    }

    private static function getFilters(array $filtersIn): array
    {
        $filters = [];
        foreach ($filtersIn as $k => $v) {
            $filter = new StdClass();
            $filter->isNative = true;
            $filter->property = $k;
            $filter->value = is_array($v) ? $v : [$v];
            $filters[] = $filter;
        }

        return $filters;
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

    private static function getKpi(array $taskGuids, array $filters = []): array
    {
        $stat = self::$aggregation->getStatistics($taskGuids, $filters);
        $result = [];
        foreach (['levenshteinPrevious', 'levenshteinOriginal', 'levenshteinStart', 'levenshteinEnd'] as $key) {
            $result[$key] = $stat["{$key}Avg"] ?: '-';
        }

        return $result;
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

    private static function recreateKPIsFromHistory(string $taskGuid): void
    {
        self::$db->query('UPDATE LEK_segments SET timestamp=timestamp,levenshteinOriginal=0,levenshteinPrevious=0');
        self::$db->query('UPDATE LEK_segment_history SET levenshteinOriginal=0,levenshteinPrevious=0');
        self::$levenshteinCalc->calculate($taskGuid, 'default');
        self::$aggregateTask->removeData($taskGuid);
        self::$aggregateTask->aggregateData($taskGuid, 'default');
    }
}
