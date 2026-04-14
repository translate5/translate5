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

use editor_Models_Segment_AutoStates as AutoStates;
use editor_Workflow_Default as WfDef;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Factory;
use MittagQI\Translate5\Statistics\Helpers\AggregateTaskStatistics;
use MittagQI\Translate5\Statistics\SegmentStatisticsRepository;
use MittagQI\Translate5\Test\Enums\TestUser as U;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;
use Random\RandomException;

class AggregateTaskHistorySeedTest extends JsonTestAbstract
{
    private const string SEED_TIMESTAMP = '2024-01-01 00:00:00';

    private static Zend_Db_Adapter_Abstract $db;

    private static AggregateTaskStatistics $aggregateTaskHistory;

    private static AbstractStatisticsDB $statDb;

    private static SegmentStatisticsRepository $aggregation;

    /**
     * @var string[]
     */
    private array $taskGuidsToCleanup = [];

    /**
     * @throws Zend_Exception
     */
    protected static function setupImport(Config $config): void
    {
        self::$db = Zend_Db_Table::getDefaultAdapter();
        self::$aggregateTaskHistory = AggregateTaskStatistics::create();
        self::$statDb = Factory::createDb();
        self::$aggregation = SegmentStatisticsRepository::create();
    }

    /**
     * @dataProvider aggregateDataProvider
     *
     * @param array{
     *   workflowName: string,
     *   workflowLogSteps?: list<string>,
     *   rows: list<array{
     *     segmentKey: string,
     *     userLogin: U,
     *     autoStateId: int,
     *     levenshteinOriginal: int,
     *     levenshteinPrevious: int,
     *     segmentlengthPrevious: int,
     *     matchRate: int,
     *     matchRateType: string,
     *     duration: int,
     *     editable?: int,
     *     workflowStep?: string|null,
     *     editedInStep?: string
     *   }>,
     *   statistics: list<array{
     *     segmentKey: string,
     *     userLogin: U,
     *     workflowStep: string,
     *     editable?: int,
     *     levenshteinOriginal: int,
     *     levenshteinPrevious: int,
     *     segmentlengthPrevious: int,
     *     matchRate: int,
     *     latestEntry?: int
     *   }>,
     *   posteditingTime: list<array{
     *     segmentKey: string,
     *     userLogin: U,
     *     workflowStep: string,
     *     duration: int
     *   }>,
     *   kpi?: array<string, float|int|string|null>
     * } $dataset
     *
     * @throws RandomException
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function testAggregateDataAndKpiWithSeededRows(array $dataset): void
    {
        $config = Zend_Registry::get('config');
        if (! $config->resources->db->statistics?->enabled) {
            self::markTestSkipped('Runs only with resources.db.statistics.enabled = 1');
        }

        $taskGuid = $this->newGuid();
        $this->taskGuidsToCleanup[] = $taskGuid;

        $segmentIdsByKey = $this->seedTaskWithRows(
            $taskGuid,
            $dataset['rows'],
            $dataset['workflowLogSteps'] ?? []
        );

        self::$aggregateTaskHistory->aggregateHistoricalData($taskGuid);

        $this->assertStatisticsRows($taskGuid, $dataset['statistics'], $segmentIdsByKey, $dataset['workflowName']);
        $this->assertPosteditingRows($taskGuid, $dataset['posteditingTime'], $segmentIdsByKey);
        if (isset($dataset['kpi'])) {
            $this->assertKpiRows($taskGuid, $dataset['kpi']);
        }
    }

    /**
     * @return array<string, array{0: array{
     *   workflowName: string,
     *   workflowLogSteps?: list<string>,
     *   rows: list<array<string, int|string|U|null>>,
     *   statistics: list<array<string, int|string|U>>,
     *   posteditingTime: list<array<string, int|string|U>>,
     *   kpi?: array<string, float|int|string|null>
     * }}>
     */
    public static function aggregateDataProvider(): array
    {
        return [
            'single-segment-translation-step' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    'translation',
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::NOT_TRANSLATED, 0, 0, 0, 'import;empty', 0),
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 0, 0, 103, 'pretranslated;tm;T5Memory - MatchAnalysisTest_TM;auto-propag', 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 4, 4, 103, 'pretranslated;tm;T5Memory - MatchAnalysisTest_TM;auto-propag', 42),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 103),
                    self::stat('seg-1', U::TestTranslator, 'translation', 4, 4, 103, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 42),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 4.0,
                    'levenshteinOriginal' => 4.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 42.0,
                    'posteditingInAllWorkflowSteps' => 42.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'two-segments-mixed-steps' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    editor_Workflow_Default::STEP_NO_WORKFLOW,
                    'translation',
                    'reviewing',
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::REVIEWED_PM, 3, 3, 100, 'import', 10, 1, null, editor_Workflow_Default::STEP_NO_WORKFLOW),
                    self::row('seg-2', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 90, 'fuzzy', 15),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 3, 1, 70, 'fuzzy', 31),
                    self::row('seg-2', U::TestManager2, AutoStates::REVIEWED, 5, 5, 95, 'fuzzy', 25),
                    self::row('seg-2', U::TestLector, AutoStates::REVIEWED, 1, 1, 95, 'fuzzy', 25),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, editor_Workflow_Default::STEP_NO_WORKFLOW, 3, 3, 100),
                    self::stat('seg-1', U::TestTranslator, 'translation', 3, 1, 70, latestEntry: 1),
                    self::stat('seg-1', U::TestTranslator, 'reviewing', 3, 0, 70, 1, 1), // synthetic cloned row
                    self::stat('seg-2', U::TestTranslator, 'translation', 2, 2, 90),
                    self::stat('seg-2', U::TestLector, 'reviewing', 1, 1, 95, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestManager, editor_Workflow_Default::STEP_NO_WORKFLOW, 10),
                    self::post('seg-1', U::TestTranslator, 'translation', 31),
                    self::post('seg-2', U::TestTranslator, 'translation', 15),
                    self::post('seg-2', U::TestManager2, 'reviewing', 25),
                    self::post('seg-2', U::TestLector, 'reviewing', 25),
                ),
                'kpi' => [
                    'affectedSegments' => 2,
                    'levenshteinPrevious' => 1, // (1/2 + 3/2) / 2
                    'levenshteinOriginal' => 2.0,
                    'levenshteinStart' => 3.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 10.0,
                    'posteditingInWorkflowStep' => 31.75,
                    'posteditingInAllWorkflowSteps' => 48.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'original-contains-also-non-workflow-steps' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    editor_Workflow_Default::STEP_NO_WORKFLOW,
                    'translation',
                    'reviewing',
                    editor_Workflow_Default::STEP_WORKFLOW_ENDED,
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::REVIEWED_PM, 3, 3, 100, 'import', 10, 1, null, editor_Workflow_Default::STEP_NO_WORKFLOW),
                    self::row('seg-2', U::TestManager, AutoStates::PRETRANSLATED, 0, 0, 90, 'fuzzy', 0),
                    self::row('seg-2', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 90, 'fuzzy', 15),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 3, 1, 70, 'fuzzy', 31),
                    self::row('seg-2', U::TestManager2, AutoStates::REVIEWED, 5, 5, 95, 'fuzzy', 25),
                    self::row('seg-2', U::TestLector, AutoStates::REVIEWED, 1, 1, 95, 'fuzzy', 25),
                    self::row('seg-3', U::TestManager, AutoStates::TRANSLATED, 0, 0, 100, 'import', 10),
                    self::row('seg-3', U::TestManager, AutoStates::REVIEWED_PM, 3, 3, 100, 'import', 10, 1, null, editor_Workflow_Default::STEP_WORKFLOW_ENDED),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, WfDef::STEP_NO_WORKFLOW, 3, 3, 100),
                    self::stat('seg-1', U::TestTranslator, 'translation', 3, 1, 70, latestEntry: 1),
                    self::stat('seg-1', U::TestTranslator, 'reviewing', 3, 0, 70, 1, 1), // synthetic cloned row
                    self::stat('seg-1', U::TestTranslator, WfDef::STEP_WORKFLOW_ENDED, 3, 0, 70, 1, 1), // synthetic cloned row
                    self::stat('seg-2', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 90),
                    self::stat('seg-2', U::TestManager, WfDef::STEP_NO_WORKFLOW, 0, 0, 90, 1), // synthetic cloned row
                    self::stat('seg-2', U::TestLector, 'reviewing', 1, 1, 95, latestEntry: 1),
                    self::stat('seg-2', U::TestTranslator, 'translation', 2, 2, 90),
                    self::stat('seg-2', U::TestLector, WfDef::STEP_WORKFLOW_ENDED, 1, 0, 95, 1, 1), // synthetic cloned row
                    self::stat('seg-3', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 100),
                    self::stat('seg-3', U::TestManager, WfDef::STEP_NO_WORKFLOW, 0, 0, 100), //synthetic
                    self::stat('seg-3', U::TestManager, WfDef::STEP_WORKFLOW_ENDED, 3, 3, 100, 1, 3, latestEntry: 1),
                    self::stat('seg-3', U::TestManager, 'translation', 0, 0, 100),
                    self::stat('seg-3', U::TestManager, 'reviewing', 0, 0, 100),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestManager, editor_Workflow_Default::STEP_NO_WORKFLOW, 10),
                    self::post('seg-1', U::TestTranslator, 'translation', 31),
                    self::post('seg-2', U::TestTranslator, 'translation', 15),
                    self::post('seg-2', U::TestManager2, 'reviewing', 25),
                    self::post('seg-2', U::TestLector, 'reviewing', 25),
                    self::post('seg-3', U::TestManager, 'translation', 10),
                    self::post('seg-3', U::TestManager, WfDef::STEP_WORKFLOW_ENDED, 10),
                ),
                'kpi' => [
                    'affectedSegments' => 3,
                    'levenshteinPrevious' => 2 / 3, // (1/2 + 3/2) / 3
                    'levenshteinOriginal' => 7 / 3,
                    'levenshteinStart' => 1,
                    'levenshteinEnd' => 1,
                    'posteditingNoWorkflow' => 10 / 3,
                    'posteditingInWorkflowStep' => 24.5,
                    'posteditingInAllWorkflowSteps' => 106 / 3,
                    'posteditingWorkflowEnded' => 10 / 3,
                ],
            ]],
            'preworkflow-only-pm-edits' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 3, 3, 98, 'pretranslated;tm', 20),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, editor_Workflow_Default::STEP_NO_WORKFLOW, 3, 3, 98, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestManager, editor_Workflow_Default::STEP_NO_WORKFLOW, 20),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 0.0,
                    'levenshteinOriginal' => 3.0,
                    'levenshteinStart' => 3.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 20.0,
                    'posteditingInWorkflowStep' => 0.0,
                    'posteditingInAllWorkflowSteps' => 0.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'multiple-saves-same-step-keep-latest' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 85, 'fuzzy', 10),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 5, 3, 88, 'fuzzy', 15),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 5, 3, 88, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 25),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 3.0,
                    'levenshteinOriginal' => 5.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 25.0,
                    'posteditingInAllWorkflowSteps' => 25.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'untouched-creates-synthetic-initial' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 0, 0, 100, 'pretranslated;tm', 0),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 100, latestEntry: 1),
                ),
                'posteditingTime' => [],
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 0.0,
                    'levenshteinOriginal' => 0.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 0.0,
                    'posteditingInAllWorkflowSteps' => 0.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'pretranslated-cutoff-in-history' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::NOT_TRANSLATED, 0, 0, 0, 'import;empty', 0),
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 0, 0, 103, 'pretranslated;tm', 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 4, 4, 103, 'pretranslated;tm', 20),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 103),
                    self::stat('seg-1', U::TestTranslator, 'translation', 4, 4, 103, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 20),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 4.0,
                    'levenshteinOriginal' => 4.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 20.0,
                    'posteditingInAllWorkflowSteps' => 20.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'duration-grouping-and-zero-filter' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 1, 1, 100, 'pretranslated;tm', 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 90, 'fuzzy', 3),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 4, 3, 95, 'fuzzy', 5),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, editor_Workflow_Default::STEP_NO_WORKFLOW, 1, 1, 100),
                    self::stat('seg-1', U::TestTranslator, 'translation', 4, 3, 95, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 8),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 3.0,
                    'levenshteinOriginal' => 4.0,
                    'levenshteinStart' => 1.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 8.0,
                    'posteditingInAllWorkflowSteps' => 8.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'single-segment-step-chain-translation-reviewing' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 6, 6, 91, 'fuzzy', 12),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED, 8, 2, 91, 'fuzzy', 18),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 6, 6, 91),
                    self::stat('seg-1', U::TestLector, 'reviewing', 8, 2, 91, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 12),
                    self::post('seg-1', U::TestLector, 'reviewing', 18),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 4.0,
                    'levenshteinOriginal' => 8.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 15.0,
                    'posteditingInAllWorkflowSteps' => 30.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'single-segment-step-chain-translation-translator-check' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    'translation',
                    'translatorCheck',
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 1, 1, 91, 'fuzzy', 4),
                    self::row('seg-1', U::TestManager2, AutoStates::REVIEWED_TRANSLATOR, 2, 1, 91, 'fuzzy', 6),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 1, 1, 91),
                    self::stat('seg-1', U::TestManager2, 'translatorCheck', 2, 1, 91, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 4),
                    self::post('seg-1', U::TestManager2, 'translatorCheck', 6),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 1.0,
                    'levenshteinOriginal' => 2.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 5.0,
                    'posteditingInAllWorkflowSteps' => 10.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'single-segment-review-revert-reduces-distance' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    'translation',
                    'reviewing',
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 3, 3, 89, 'fuzzy', 10),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED, 1, 2, 89, 'fuzzy', 5),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 3, 3, 89),
                    self::stat('seg-1', U::TestLector, 'reviewing', 1, 2, 89, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 10),
                    self::post('seg-1', U::TestLector, 'reviewing', 5),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 2.5,
                    'levenshteinOriginal' => 1.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 7.5,
                    'posteditingInAllWorkflowSteps' => 15.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'post-workflow-ended-pm-change' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 4, 4, 89, 'fuzzy', 10),
                    self::row('seg-1', U::TestManager, AutoStates::REVIEWED_PM, 5, 1, 89, 'fuzzy', 7, 1, editor_Workflow_Default::STEP_PM_CHECK, editor_Workflow_Default::STEP_WORKFLOW_ENDED),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 4, 4, 89),
                    self::stat('seg-1', U::TestManager, editor_Workflow_Default::STEP_WORKFLOW_ENDED, 5, 1, 89, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 10),
                    self::post('seg-1', U::TestManager, editor_Workflow_Default::STEP_WORKFLOW_ENDED, 7),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 4.0,
                    'levenshteinOriginal' => 5.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 1.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 10.0,
                    'posteditingInAllWorkflowSteps' => 10.0,
                    'posteditingWorkflowEnded' => 7.0,
                ],
            ]],
            'reviewed-untouched-is-no-edit' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    'translation',
                    'reviewing',
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 3, 3, 84, 'fuzzy', 9),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED_UNTOUCHED, 3, 3, 84, 'fuzzy', 0),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 3, 3, 84, latestEntry: 1),
                    self::stat('seg-1', U::TestTranslator, 'reviewing', 3, 0, 84, 1, 3),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 9),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 1.5,
                    'levenshteinOriginal' => 3.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 9.0,
                    'posteditingInAllWorkflowSteps' => 9.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'reviewed-untouched-history-with-empty-step-regression' => [[
                'workflowName' => 'default',
                'workflowLogSteps' => [
                    'translation',
                    'reviewing',
                    editor_Workflow_Default::STEP_WORKFLOW_ENDED,
                ],
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::TRANSLATED, 0, 0, 90, 'import;tm', 0, 1, '', '', 13),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 90, 'import;tm', 0, 1, 'translation', 'translation', 13),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED_UNTOUCHED, 2, 0, 90, 'import;tm', 666, 1, 'translation', 'translation', 13),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 90, 1, 13, 0),
                    self::stat('seg-1', U::TestTranslator, 'translation', 2, 2, 90, 1, 13, 1),
                    self::stat('seg-1', U::TestTranslator, 'reviewing', 2, 0, 90, 1, 13, 0),
                    self::stat('seg-1', U::TestTranslator, editor_Workflow_Default::STEP_WORKFLOW_ENDED, 2, 0, 90, 1, 13, 0),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestLector, 'translation', 666),
                ),
            ]],
            'user-responsibility-last-saver-in-step' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 80, 'fuzzy', 6),
                    self::row('seg-1', U::TestLector, AutoStates::TRANSLATED, 5, 3, 80, 'fuzzy', 8),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestLector, 'translation', 5, 3, 80, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 6),
                    self::post('seg-1', U::TestLector, 'translation', 8),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 3.0,
                    'levenshteinOriginal' => 5.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 14.0,
                    'posteditingInAllWorkflowSteps' => 14.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'manual-translation-without-pretranslation' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::NOT_TRANSLATED, 0, 0, 0, 'import;empty', 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 7, 7, 0, 'manual', 14),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 0),
                    self::stat('seg-1', U::TestTranslator, 'translation', 7, 7, 0, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 14),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 7.0,
                    'levenshteinOriginal' => 7.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 14.0,
                    'posteditingInAllWorkflowSteps' => 14.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'complex-three-segments-five-edits-pm-before-and-inbetween' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::REVIEWED_PM, 1, 1, 98, 'fuzzy', 5, 1, editor_Workflow_Default::STEP_PM_CHECK, 'translation'),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 4, 3, 90, 'fuzzy', 7),
                    self::row('seg-1', U::TestManager2, AutoStates::TRANSLATED, 6, 2, 88, 'fuzzy', 4),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED, 7, 1, 88, 'fuzzy', 6),
                    self::row('seg-1', U::TestManager, AutoStates::REVIEWED_PM, 9, 2, 85, 'fuzzy', 3, 1, editor_Workflow_Default::STEP_PM_CHECK, 'reviewing'),
                    self::row('seg-2', U::TestManager, AutoStates::REVIEWED_PM, 2, 2, 97, 'fuzzy', 6, 1, editor_Workflow_Default::STEP_PM_CHECK, 'translation'),
                    self::row('seg-2', U::TestTranslator, AutoStates::TRANSLATED, 3, 1, 92, 'fuzzy', 5),
                    self::row('seg-2', U::TestManager2, AutoStates::TRANSLATED, 5, 2, 89, 'fuzzy', 8),
                    self::row('seg-2', U::TestLector, AutoStates::REVIEWED, 6, 1, 89, 'fuzzy', 4, editable: 1),
                    self::row('seg-2', U::TestManager, AutoStates::REVIEWED_PM, 8, 2, 86, 'fuzzy', 2, 1, editor_Workflow_Default::STEP_PM_CHECK, 'reviewing'),
                    self::row('seg-3', U::TestManager, AutoStates::TRANSLATED, 1, 1, 96, 'fuzzy', 3),
                    self::row('seg-3', U::TestTranslator, AutoStates::TRANSLATED, 2, 1, 93, 'fuzzy', 9),
                    self::row('seg-3', U::TestManager2, AutoStates::TRANSLATED, 4, 2, 90, 'fuzzy', 7),
                    self::row('seg-3', U::TestLector, AutoStates::REVIEWED, 5, 1, 90, 'fuzzy', 5),
                    self::row('seg-3', U::TestLector, AutoStates::REVIEWED, 8, 4, 90, 'fuzzy', 26),
                    self::row('seg-3', U::TestManager, AutoStates::REVIEWED, 6, 1, 87, 'fuzzy', 4),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager2, 'translation', 6, 2, 88),
                    self::stat('seg-1', U::TestManager, 'reviewing', 9, 2, 85, latestEntry: 1),
                    self::stat('seg-2', U::TestManager2, 'translation', 5, 2, 89),
                    self::stat('seg-2', U::TestManager, 'reviewing', 8, 2, 86, latestEntry: 1),
                    self::stat('seg-3', U::TestManager2, 'translation', 4, 2, 90),
                    self::stat('seg-3', U::TestManager, 'reviewing', 6, 1, 87, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestManager, 'translation', 5),
                    self::post('seg-1', U::TestTranslator, 'translation', 7),
                    self::post('seg-1', U::TestManager2, 'translation', 4),
                    self::post('seg-1', U::TestLector, 'reviewing', 6),
                    self::post('seg-1', U::TestManager, 'reviewing', 3),
                    self::post('seg-2', U::TestManager, 'translation', 6),
                    self::post('seg-2', U::TestTranslator, 'translation', 5),
                    self::post('seg-2', U::TestManager2, 'translation', 8),
                    self::post('seg-2', U::TestLector, 'reviewing', 4),
                    self::post('seg-2', U::TestManager, 'reviewing', 2),
                    self::post('seg-3', U::TestManager, 'translation', 3),
                    self::post('seg-3', U::TestTranslator, 'translation', 9),
                    self::post('seg-3', U::TestManager2, 'translation', 7),
                    self::post('seg-3', U::TestLector, 'reviewing', 31),
                    self::post('seg-3', U::TestManager, 'reviewing', 4),
                ),
                'kpi' => [
                    'affectedSegments' => 3,
                    'levenshteinPrevious' => 11 / 6, // ((2+2)/2 + (2+2)/2 + (2+1) / 2) / 3
                    'levenshteinOriginal' => 23 / 3, // (9 + 8 + 6) / 3
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 52 / 3, // (time sum per segment / stepcount) / segment count
                    'posteditingInAllWorkflowSteps' => 104 / 3, //all time summed / segment count
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'complex-four-segments-pm-handover-between-steps' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::TRANSLATED, 1, 1, 99, 'fuzzy', 2),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 3, 2, 93, 'fuzzy', 5),
                    self::row('seg-1', U::TestManager, AutoStates::REVIEWED, 4, 1, 93, 'fuzzy', 3),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED, 5, 1, 91, 'fuzzy', 6),
                    self::row('seg-2', U::TestManager, AutoStates::TRANSLATED, 2, 2, 98, 'fuzzy', 3),
                    self::row('seg-2', U::TestTranslator, AutoStates::TRANSLATED, 4, 2, 92, 'fuzzy', 6),
                    self::row('seg-2', U::TestManager, AutoStates::REVIEWED, 6, 2, 92, 'fuzzy', 2),
                    self::row('seg-2', U::TestLector, AutoStates::REVIEWED, 7, 1, 90, 'fuzzy', 4),
                    self::row('seg-3', U::TestManager, AutoStates::TRANSLATED, 1, 1, 97, 'fuzzy', 4),
                    self::row('seg-3', U::TestTranslator, AutoStates::TRANSLATED, 2, 1, 94, 'fuzzy', 7),
                    self::row('seg-3', U::TestManager, AutoStates::REVIEWED, 3, 1, 94, 'fuzzy', 3),
                    self::row('seg-3', U::TestLector, AutoStates::REVIEWED, 5, 2, 92, 'fuzzy', 5),
                    self::row('seg-4', U::TestManager, AutoStates::TRANSLATED, 2, 2, 96, 'fuzzy', 5),
                    self::row('seg-4', U::TestTranslator, AutoStates::TRANSLATED, 5, 3, 91, 'fuzzy', 8),
                    self::row('seg-4', U::TestManager, AutoStates::REVIEWED, 6, 1, 91, 'fuzzy', 4),
                    self::row('seg-4', U::TestLector, AutoStates::REVIEWED, 8, 2, 89, 'fuzzy', 6),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 3, 2, 93),
                    self::stat('seg-1', U::TestLector, 'reviewing', 5, 1, 91, latestEntry: 1),
                    self::stat('seg-2', U::TestTranslator, 'translation', 4, 2, 92),
                    self::stat('seg-2', U::TestLector, 'reviewing', 7, 1, 90, latestEntry: 1),
                    self::stat('seg-3', U::TestTranslator, 'translation', 2, 1, 94),
                    self::stat('seg-3', U::TestLector, 'reviewing', 5, 2, 92, latestEntry: 1),
                    self::stat('seg-4', U::TestTranslator, 'translation', 5, 3, 91),
                    self::stat('seg-4', U::TestLector, 'reviewing', 8, 2, 89, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestManager, 'translation', 2),
                    self::post('seg-1', U::TestTranslator, 'translation', 5),
                    self::post('seg-1', U::TestManager, 'reviewing', 3),
                    self::post('seg-1', U::TestLector, 'reviewing', 6),
                    self::post('seg-2', U::TestManager, 'translation', 3),
                    self::post('seg-2', U::TestTranslator, 'translation', 6),
                    self::post('seg-2', U::TestManager, 'reviewing', 2),
                    self::post('seg-2', U::TestLector, 'reviewing', 4),
                    self::post('seg-3', U::TestManager, 'translation', 4),
                    self::post('seg-3', U::TestTranslator, 'translation', 7),
                    self::post('seg-3', U::TestManager, 'reviewing', 3),
                    self::post('seg-3', U::TestLector, 'reviewing', 5),
                    self::post('seg-4', U::TestManager, 'translation', 5),
                    self::post('seg-4', U::TestTranslator, 'translation', 8),
                    self::post('seg-4', U::TestManager, 'reviewing', 4),
                    self::post('seg-4', U::TestLector, 'reviewing', 6),
                ),
                'kpi' => [
                    'affectedSegments' => 4,
                    'levenshteinPrevious' => 1.75,
                    'levenshteinOriginal' => 6.25,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 9.125,
                    'posteditingInAllWorkflowSteps' => 18.25,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'non-editable-segment-editable-zero' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 0, 0, 100, 'pretranslated;tm', 0, 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 92, 'fuzzy', 4, 0),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 100, 0),
                    self::stat('seg-1', U::TestTranslator, 'translation', 2, 2, 92, 0, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 4),
                ),
                'kpi' => [
                    'affectedSegments' => 0,
                    'levenshteinPrevious' => 0.0,
                    'levenshteinOriginal' => 0.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 0.0,
                    'posteditingInAllWorkflowSteps' => 0.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'non-editable-two-segments-mixed-steps' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestManager, AutoStates::PRETRANSLATED, 0, 0, 100, 'pretranslated;tm', 0, 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 3, 3, 92, 'fuzzy', 6, 0),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED, 4, 1, 92, 'fuzzy', 5, 0),
                    self::row('seg-2', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 89, 'fuzzy', 7),
                    self::row('seg-2', U::TestLector, AutoStates::REVIEWED, 3, 1, 89, 'fuzzy', 4),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestManager, SegmentHistoryAggregation::INITIAL_WORKFLOW_STEP, 0, 0, 100, 0),
                    self::stat('seg-1', U::TestTranslator, 'translation', 3, 3, 92, 0),
                    self::stat('seg-1', U::TestLector, 'reviewing', 4, 1, 92, 0, latestEntry: 1),
                    self::stat('seg-2', U::TestTranslator, 'translation', 2, 2, 89),
                    self::stat('seg-2', U::TestLector, 'reviewing', 3, 1, 89, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 6),
                    self::post('seg-1', U::TestLector, 'reviewing', 5),
                    self::post('seg-2', U::TestTranslator, 'translation', 7),
                    self::post('seg-2', U::TestLector, 'reviewing', 4),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 1.5,
                    'levenshteinOriginal' => 3.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 5.5,
                    'posteditingInAllWorkflowSteps' => 11.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
            'editable-switch-in-step-last-row-wins' => [[
                'workflowName' => 'default',
                'rows' => self::rows(
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 2, 2, 93, 'fuzzy', 4, 0),
                    self::row('seg-1', U::TestTranslator, AutoStates::TRANSLATED, 5, 3, 90, 'fuzzy', 6, 1),
                    self::row('seg-1', U::TestLector, AutoStates::REVIEWED, 6, 1, 90, 'fuzzy', 5, 1),
                ),
                'statistics' => self::statistics(
                    self::stat('seg-1', U::TestTranslator, 'translation', 5, 3, 90, 1),
                    self::stat('seg-1', U::TestLector, 'reviewing', 6, 1, 90, 1, latestEntry: 1),
                ),
                'posteditingTime' => self::postediting(
                    self::post('seg-1', U::TestTranslator, 'translation', 10),
                    self::post('seg-1', U::TestLector, 'reviewing', 5),
                ),
                'kpi' => [
                    'affectedSegments' => 1,
                    'levenshteinPrevious' => 2.0,
                    'levenshteinOriginal' => 6.0,
                    'levenshteinStart' => 0.0,
                    'levenshteinEnd' => 0.0,
                    'posteditingNoWorkflow' => 0.0,
                    'posteditingInWorkflowStep' => 7.5,
                    'posteditingInAllWorkflowSteps' => 15.0,
                    'posteditingWorkflowEnded' => 0.0,
                ],
            ]],
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->taskGuidsToCleanup as $taskGuid) {
            $this->cleanupByTaskGuid($taskGuid);
        }
        $this->taskGuidsToCleanup = [];
        parent::tearDown();
    }

    /**
     * @param list<array<string, int|string|U|null>> $rows
     * @return array<string, int>
     * @throws Zend_Db_Adapter_Exception
     */
    private function seedTaskWithRows(string $taskGuid, array $rows, array $workflowLogSteps = []): array
    {
        [$sourceLangId, $sourceLangCode] = $this->fetchLanguageTuple(1);
        [$targetLangId, $targetLangCode] = $this->fetchLanguageTuple(2);
        $pmGuid = self::getUserGuidByLogin(U::TestManager->value);
        $preTransLangResUuid = $this->getValidPreTransLangResUuid();

        self::$db->insert('LEK_task', [
            'taskGuid' => $taskGuid,
            'taskName' => 'AggregateTaskHistorySeedTest-' . substr($taskGuid, 1, 8),
            'sourceLang' => $sourceLangId,
            'targetLang' => $targetLangId,
            'relaisLang' => 0,
            'pmGuid' => $pmGuid,
            'pmName' => U::TestManager->value,
            'wordCount' => 0,
            'description' => '',
            'workflow' => 'default',
            'workflowStep' => 1,
            'workflowStepName' => 'translation',
            'state' => editor_Models_Task::STATE_OPEN,
        ]);

        foreach ($workflowLogSteps as $workflowLogStep) {
            self::$db->insert('LEK_task_workflow_log', [
                'taskGuid' => $taskGuid,
                'workflowName' => 'default',
                'workflowStepName' => $workflowLogStep,
                'userGuid' => $pmGuid,
            ]);
        }

        self::$db->insert('LEK_files', [
            'taskGuid' => $taskGuid,
            'fileName' => 'seeded-test.xlf',
            'fileParser' => 'editor_Models_Import_FileParser_Xlf',
            'sourceLang' => $sourceLangCode,
            'targetLang' => $targetLangCode,
            'relaisLang' => 0,
            'fileOrder' => 1,
            'encoding' => 'UTF-8',
        ]);
        $fileId = (int) self::$db->lastInsertId();

        $rowsBySegment = $this->groupRowsBySegmentKeyWithGuids($rows);
        $segmentIdsByKey = [];

        foreach ($rowsBySegment as $segmentKey => $segmentRows) {
            $lastIdx = count($segmentRows) - 1;
            $lastRow = $segmentRows[$lastIdx];
            $segmentNr = $this->segmentNrFromKey($segmentKey);

            self::$db->insert('LEK_segments', [
                'segmentNrInTask' => $segmentNr,
                'fileId' => $fileId,
                'mid' => (string) $segmentNr,
                'userGuid' => $lastRow['userLogin'],
                'userName' => '',
                'taskGuid' => $taskGuid,
                'editable' => (int) ($lastRow['editable'] ?? 1),
                'pretrans' => 0,
                'matchRate' => (int) $lastRow['matchRate'],
                'matchRateType' => (string) $lastRow['matchRateType'],
                'stateId' => 0,
                'autoStateId' => (int) $lastRow['autoStateId'],
                'fileOrder' => $segmentNr,
                'workflowStepNr' => 0,
                'workflowStep' => $lastRow['workflowStep'],
                'isRepeated' => 0,
                'editedInStep' => (string) $lastRow['editedInStep'],
                'timestamp' => self::SEED_TIMESTAMP,
            ]);
            $segmentId = (int) self::$db->lastInsertId();
            $segmentIdsByKey[$segmentKey] = $segmentId;

            self::$db->insert('LEK_segment_statistics', [
                'taskGuid' => $taskGuid,
                'segmentId' => $segmentId,
                'historyId' => 0,
                'levenshteinOriginal' => (int) $lastRow['levenshteinOriginal'],
                'levenshteinPrevious' => (int) $lastRow['levenshteinPrevious'],
                'segmentlengthPrevious' => (int) $lastRow['segmentlengthPrevious'],
            ]);

            self::$db->insert('LEK_segment_data', [
                'taskGuid' => $taskGuid,
                'segmentId' => $segmentId,
                'name' => editor_Models_SegmentField::TYPE_TARGET,
                'mid' => (string) $segmentNr,
                'original' => 'orig-' . $segmentKey,
                'originalMd5' => md5('orig-' . $segmentKey),
                'edited' => 'edit-' . $segmentKey,
                'duration' => (int) $lastRow['duration'],
            ]);

            self::$db->insert('LEK_segments_meta', [
                'taskGuid' => $taskGuid,
                'segmentId' => $segmentId,
                'preTransLangResUuid' => $preTransLangResUuid,
            ]);

            for ($i = 0; $i < $lastIdx; $i++) {
                $historyRow = $segmentRows[$i];

                self::$db->insert('LEK_segment_history', [
                    'segmentId' => $segmentId,
                    'taskGuid' => $taskGuid,
                    'userGuid' => $historyRow['userLogin'],
                    'userName' => '',
                    'editable' => (int) ($historyRow['editable'] ?? 1),
                    'pretrans' => 0,
                    'autoStateId' => (int) $historyRow['autoStateId'],
                    'workflowStepNr' => 0,
                    'workflowStep' => $historyRow['workflowStep'],
                    'matchRate' => (int) $historyRow['matchRate'],
                    'matchRateType' => (string) $historyRow['matchRateType'],
                    'editedInStep' => (string) $historyRow['editedInStep'],
                    'timestamp' => self::SEED_TIMESTAMP,
                ]);
                $historyId = (int) self::$db->lastInsertId();

                self::$db->insert('LEK_segment_statistics', [
                    'taskGuid' => $taskGuid,
                    'segmentId' => $segmentId,
                    'historyId' => $historyId,
                    'levenshteinOriginal' => (int) $historyRow['levenshteinOriginal'],
                    'levenshteinPrevious' => (int) $historyRow['levenshteinPrevious'],
                    'segmentlengthPrevious' => (int) $historyRow['segmentlengthPrevious'],
                ]);

                self::$db->insert('LEK_segment_history_data', [
                    'segmentHistoryId' => $historyId,
                    'segmentId' => $segmentId,
                    'taskGuid' => $taskGuid,
                    'name' => editor_Models_SegmentField::TYPE_TARGET,
                    'original' => 'hist-orig-' . $segmentKey,
                    'originalMd5' => md5('hist-orig-' . $segmentKey),
                    'edited' => 'hist-edit-' . $segmentKey,
                    'duration' => (int) $historyRow['duration'],
                ]);
            }
        }

        return $segmentIdsByKey;
    }

    /**
     * @param list<array<string, int|string|U|null>> $rows
     * @return array<string, list<array<string, int|string|null>>>
     */
    private function groupRowsBySegmentKeyWithGuids(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $segmentKey = (string) $row['segmentKey'];
            /** @var U $testUser */
            $testUser = $row['userLogin'];
            $row['userLogin'] = self::getUserGuidByLogin($testUser->value);

            if (! isset($grouped[$segmentKey])) {
                $grouped[$segmentKey] = [];
            }
            $grouped[$segmentKey][] = $row;
        }

        return $grouped;
    }

    /**
     * @param list<array<string, int|string|U>> $expectedRows
     * @param array<string, int> $segmentIdsByKey
     */
    private function assertStatisticsRows(
        string $taskGuid,
        array $expectedRows,
        array $segmentIdsByKey,
        string $workflowName,
    ): void {
        $actualRows = self::$statDb->select(
            'SELECT segmentId,userGuid,workflowStepName,editable,latestEntry,
                        levenshteinOriginal,levenshteinPrevious,segmentlengthPrevious,matchRate,workflowName
             FROM LEK_statistics_segment_aggregation WHERE taskGuid = :taskGuid',
            [
                'taskGuid' => $this->trimBrackets($taskGuid),
            ]
        );

        $actualNormalized = [];
        foreach ($actualRows as $row) {
            $actualNormalized[] = [
                'segmentId' => (int) $row['segmentId'],
                'userGuid' => (string) $row['userGuid'],
                'workflowStepName' => (string) $row['workflowStepName'],
                'editable' => (int) $row['editable'],
                'latestEntry' => (int) $row['latestEntry'],
                'levenshteinOriginal' => (int) $row['levenshteinOriginal'],
                'levenshteinPrevious' => (int) $row['levenshteinPrevious'],
                'segmentlengthPrevious' => (int) $row['segmentlengthPrevious'],
                'matchRate' => (int) $row['matchRate'],
                'workflowName' => (string) $row['workflowName'],
            ];
        }

        $expectedNormalized = [];
        foreach ($expectedRows as $row) {
            $expectedNormalized[] = [
                'segmentId' => $segmentIdsByKey[(string) $row['segmentKey']],
                'userGuid' => self::getUserGuidByLogin($row['userLogin']->value),
                'workflowStepName' => (string) $row['workflowStep'],
                'editable' => (int) ($row['editable'] ?? 1),
                'latestEntry' => (int) ($row['latestEntry'] ?? 0),
                'levenshteinOriginal' => (int) $row['levenshteinOriginal'],
                'levenshteinPrevious' => (int) $row['levenshteinPrevious'],
                'segmentlengthPrevious' => (int) $row['segmentlengthPrevious'],
                'matchRate' => (int) $row['matchRate'],
                'workflowName' => $workflowName,
            ];
        }

        $sortFields = ['segmentId', 'userGuid', 'workflowStepName', 'editable', 'latestEntry', 'levenshteinOriginal', 'levenshteinPrevious', 'segmentlengthPrevious', 'matchRate', 'workflowName'];

        usort($actualNormalized, fn (array $a, array $b): int => strcmp($this->rowSortKey($a, $sortFields), $this->rowSortKey($b, $sortFields)));
        usort($expectedNormalized, fn (array $a, array $b): int => strcmp($this->rowSortKey($a, $sortFields), $this->rowSortKey($b, $sortFields)));

        $this->assertRowsWithLabels(
            $expectedNormalized,
            $actualNormalized,
            $sortFields,
            'statistics',
        );
    }

    /**
     * @param list<array<string, int|string|U>> $expectedRows
     * @param array<string, int> $segmentIdsByKey
     */
    private function assertPosteditingRows(string $taskGuid, array $expectedRows, array $segmentIdsByKey): void
    {
        $actualRows = self::$statDb->select(
            'SELECT segmentId,userGuid,workflowStepName,duration
             FROM LEK_statistics_postediting_aggregation WHERE taskGuid = :taskGuid',
            [
                'taskGuid' => $this->trimBrackets($taskGuid),
            ]
        );

        $actualNormalized = [];
        foreach ($actualRows as $row) {
            $actualNormalized[] = [
                'segmentId' => (int) $row['segmentId'],
                'userGuid' => (string) $row['userGuid'],
                'workflowStepName' => (string) $row['workflowStepName'],
                'duration' => (int) $row['duration'],
            ];
        }

        $expectedNormalized = [];
        foreach ($expectedRows as $row) {
            $expectedNormalized[] = [
                'segmentId' => $segmentIdsByKey[(string) $row['segmentKey']],
                'userGuid' => self::getUserGuidByLogin($row['userLogin']->value),
                'workflowStepName' => (string) $row['workflowStep'],
                'duration' => (int) $row['duration'],
            ];
        }

        $sortFields = ['segmentId', 'userGuid', 'workflowStepName', 'duration'];

        usort($actualNormalized, fn (array $a, array $b): int => strcmp($this->rowSortKey($a, $sortFields), $this->rowSortKey($b, $sortFields)));
        usort($expectedNormalized, fn (array $a, array $b): int => strcmp($this->rowSortKey($a, $sortFields), $this->rowSortKey($b, $sortFields)));

        $this->assertRowsWithLabels(
            $expectedNormalized,
            $actualNormalized,
            $sortFields,
            'postediting',
        );
    }

    /**
     * @param array<string, float|int|string|null> $expectedKpi
     */
    private function assertKpiRows(string $taskGuid, array $expectedKpi): void
    {
        $actualKpi = $this->getKpi([$taskGuid]);

        foreach ($expectedKpi as $key => $expectedValue) {
            self::assertArrayHasKey($key, $actualKpi, sprintf('kpi field %s missing', $key));
            self::assertEquals($expectedValue, $actualKpi[$key], sprintf('kpi field %s mismatch', $key));
        }
    }

    /**
     * @param list<array<string, int|string>> $expectedRows
     * @param list<array<string, int|string>> $actualRows
     * @param list<string> $fieldNames
     */
    private function assertRowsWithLabels(
        array $expectedRows,
        array $actualRows,
        array $fieldNames,
        string $context,
    ): void {
        self::assertCount(
            count($expectedRows),
            $actualRows,
            sprintf('%s statistic row result count mismatch', $context),
        );

        foreach ($expectedRows as $rowIndex => $expectedRow) {
            self::assertArrayHasKey(
                $rowIndex,
                $actualRows,
                sprintf('%s missing row at index %d', $context, $rowIndex),
            );
            $actualRow = $actualRows[$rowIndex];

            foreach ($fieldNames as $fieldName) {
                $expectedValue = $expectedRow[$fieldName] ?? null;
                $actualValue = $actualRow[$fieldName] ?? null;

                if ($fieldName === 'userGuid' && is_string($expectedValue)) {
                    $expectedValue = $this->trimBrackets($expectedValue);
                }

                self::assertSame(
                    $expectedValue,
                    $actualValue,
                    sprintf(
                        '%s field %s mismatch, expected %s given %s',
                        $context,
                        $fieldName,
                        print_r($expectedRow, true),
                        print_r($actualRow, true),
                    ),
                );
            }
        }
    }

    private function cleanupByTaskGuid(string $taskGuid): void
    {
        self::$statDb->query('DELETE FROM LEK_statistics_postediting_aggregation WHERE taskGuid = :taskGuid', [
            'taskGuid' => $taskGuid,
        ]);
        self::$statDb->query('DELETE FROM LEK_statistics_segment_aggregation WHERE taskGuid = :taskGuid', [
            'taskGuid' => $taskGuid,
        ]);
        self::$db->delete('LEK_task_workflow_log', [
            'taskGuid = ?' => $taskGuid,
        ]);
        self::$db->delete('LEK_task', [
            'taskGuid = ?' => $taskGuid,
        ]);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function fetchLanguageTuple(int $offset): array
    {
        $row = self::$db->fetchRow(
            'SELECT id, rfc5646 FROM LEK_languages ORDER BY id ASC LIMIT 1 OFFSET ' . max(0, $offset - 1)
        );

        return [(int) $row['id'], (string) $row['rfc5646']];
    }

    private static function getUserGuidByLogin(string $login): string
    {
        return (string) self::$db->fetchOne('SELECT userGuid FROM Zf_users WHERE login = ?', [$login]);
    }

    private function getValidPreTransLangResUuid(): string
    {
        $uuid = (string) self::$db->fetchOne('SELECT langResUuid FROM LEK_languageresources ORDER BY id ASC LIMIT 1');
        if ($uuid !== '') {
            return $uuid;
        }

        $uuid = trim($this->newGuid(), '{}');
        self::$db->insert('LEK_languageresources', [
            'langResUuid' => $uuid,
            'name' => 'AggregateTaskHistorySeedTest LR',
        ]);

        return $uuid;
    }

    /**
     * @throws RandomException
     */
    private function newGuid(): string
    {
        $hex = bin2hex(random_bytes(16));

        return sprintf(
            '{%s-%s-%s-%s-%s}',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    private function trimBrackets(string $guid): string
    {
        return trim($guid, '{}');
    }

    /**
     * @param array<int, string> $taskGuids
     * @return array<string, float|int|string|null>
     */
    private function getKpi(array $taskGuids): array
    {
        $stat = self::$aggregation->getStatistics($taskGuids, null);

        return [
            'affectedSegments' => $stat['affectedSegments'] ?? null,
            'levenshteinPrevious' => $stat['levenshteinDistanceInWorkflowStep'] ?? null,
            'levenshteinOriginal' => $stat['levenshteinDistanceOriginal'] ?? null,
            'levenshteinStart' => $stat['levenshteinDistanceNoWorkflow'] ?? null,
            'levenshteinEnd' => $stat['levenshteinDistanceWorkflowEnded'] ?? null,
            'posteditingNoWorkflow' => $stat['posteditingTimeNoWorkflow'] ?? null,
            'posteditingInWorkflowStep' => $stat['posteditingTimeInWorkflowStep'] ?? null,
            'posteditingInAllWorkflowSteps' => $stat['posteditingTimeInAllWorkflowSteps'] ?? null,
            'posteditingWorkflowEnded' => $stat['posteditingTimeWorkflowEnded'] ?? null,
        ];
    }

    private function segmentNrFromKey(string $segmentKey): int
    {
        if (preg_match('/(\d+)$/', $segmentKey, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * @return list<array<string, int|string|U|null>>
     */
    private static function rows(array ...$rows): array
    {
        return $rows;
    }

    /**
     * @return list<array<string, int|string|U>>
     */
    private static function statistics(array ...$rows): array
    {
        return $rows;
    }

    /**
     * @return list<array<string, int|string|U>>
     */
    private static function postediting(array ...$rows): array
    {
        return $rows;
    }

    /**
     * @return array{
     *   segmentKey: string,
     *   userLogin: U,
     *   autoStateId: int,
     *   levenshteinOriginal: int,
     *   levenshteinPrevious: int,
     *   segmentlengthPrevious: int|null,
     *   matchRate: int,
     *   matchRateType: string,
     *   duration: int,
     *   editable: int,
     *   workflowStep: string|null,
     *   editedInStep: string
     * }
     */
    private static function row(
        string $segmentKey,
        U $userLogin,
        int $autoStateId,
        int $levenshteinOriginal,
        int $levenshteinPrevious,
        int $matchRate,
        string $matchRateType,
        int $duration,
        int $editable = 1,
        ?string $workflowStep = null,
        ?string $editedInStep = null,
        ?int $segmentlengthPrevious = null,
    ): array {
        [$mappedWorkflowStep, $mappedEditedInStep] = self::mapStepsByAutoState($autoStateId);
        $resolvedWorkflowStep = $workflowStep ?? $mappedWorkflowStep;
        $resolvedEditedInStep = $editedInStep
            ?? $resolvedWorkflowStep
            ?? $mappedEditedInStep;
        $resolvedSegmentLengthPrevious = $segmentlengthPrevious ?? $levenshteinPrevious; //just fake length if not given

        return [
            'segmentKey' => $segmentKey,
            'userLogin' => $userLogin,
            'autoStateId' => $autoStateId,
            'levenshteinOriginal' => $levenshteinOriginal,
            'levenshteinPrevious' => $levenshteinPrevious,
            'segmentlengthPrevious' => $resolvedSegmentLengthPrevious,
            'matchRate' => $matchRate,
            'matchRateType' => $matchRateType,
            'duration' => $duration,
            'editable' => $editable,
            'workflowStep' => $resolvedWorkflowStep,
            'editedInStep' => $resolvedEditedInStep,
        ];
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private static function mapStepsByAutoState(int $autoStateId): array
    {
        return match ($autoStateId) {
            AutoStates::NOT_TRANSLATED,
            AutoStates::PRETRANSLATED => [null, editor_Workflow_Default::STEP_NO_WORKFLOW],
            AutoStates::TRANSLATED,
            AutoStates::TRANSLATED_AUTO => ['translation', 'translation'],
            AutoStates::REVIEWED,
            AutoStates::REVIEWED_AUTO,
            AutoStates::REVIEWED_UNTOUCHED,
            AutoStates::REVIEWED_UNCHANGED,
            AutoStates::REVIEWED_UNCHANGED_AUTO => ['reviewing', 'reviewing'],
            AutoStates::REVIEWED_TRANSLATOR,
            AutoStates::REVIEWED_TRANSLATOR_AUTO => ['translatorCheck', 'translatorCheck'],
            AutoStates::REVIEWED_PM,
            AutoStates::REVIEWED_PM_AUTO,
            AutoStates::REVIEWED_PM_UNCHANGED,
            AutoStates::REVIEWED_PM_UNCHANGED_AUTO => [
                editor_Workflow_Default::STEP_PM_CHECK,
                editor_Workflow_Default::STEP_PM_CHECK,
            ],
            default => [null, editor_Workflow_Default::STEP_NO_WORKFLOW],
        };
    }

    /**
     * @return array{
     *   segmentKey: string,
     *   userLogin: U,
     *   workflowStep: string,
     *   editable: int,
     *   levenshteinOriginal: int,
     *   levenshteinPrevious: int,
     *   segmentlengthPrevious: int|null,
     *   matchRate: int,
     *   latestEntry: int
     * }
     */
    private static function stat(
        string $segmentKey,
        U $userLogin,
        string $workflowStep,
        int $levenshteinOriginal,
        int $levenshteinPrevious,
        int $matchRate,
        int $editable = 1,
        ?int $segmentlengthPrevious = null,
        int $latestEntry = 0,
    ): array {
        $resolvedSegmentLengthPrevious = $segmentlengthPrevious ?? $levenshteinPrevious;

        return [
            'segmentKey' => $segmentKey,
            'userLogin' => $userLogin,
            'workflowStep' => $workflowStep,
            'editable' => $editable,
            'levenshteinOriginal' => $levenshteinOriginal,
            'levenshteinPrevious' => $levenshteinPrevious,
            'segmentlengthPrevious' => $resolvedSegmentLengthPrevious,
            'matchRate' => $matchRate,
            'latestEntry' => $latestEntry,
        ];
    }

    /**
     * @return array{segmentKey: string, userLogin: U, workflowStep: string, duration: int}
     */
    private static function post(string $segmentKey, U $userLogin, string $workflowStep, int $duration): array
    {
        return [
            'segmentKey' => $segmentKey,
            'userLogin' => $userLogin,
            'workflowStep' => $workflowStep,
            'duration' => $duration,
        ];
    }

    /**
     * @param array<string, int|string> $row
     * @param list<string> $fields
     */
    private function rowSortKey(array $row, array $fields): string
    {
        $parts = [];

        foreach ($fields as $field) {
            $parts[] = (string) ($row[$field] ?? '');
        }

        return implode('|', $parts);
    }
}
