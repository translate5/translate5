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

namespace MittagQI\Translate5\Test\Unit\Statistics;

use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Dto\StatisticSegmentDTO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class SegmentHistoryAggregationTest extends TestCase
{
    private MockObject|AbstractStatisticsDB $client;

    private ZfExtended_Logger|MockObject $logger;

    private SegmentHistoryAggregation $aggregation;

    protected function setUp(): void
    {
        $this->client = $this->createMock(AbstractStatisticsDB::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);
        $this->aggregation = new SegmentHistoryAggregation($this->client, $this->logger);
    }

    public function testFlushUpsertsQualityScorePositionInMainTable(): void
    {
        $qualityScore = 75;

        $this->aggregation->upsertBuffered(new StatisticSegmentDTO(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            42,
            5,
            3,
            80,
            '',
            0,
            1,
            $qualityScore,
            1,
        ));

        $capturedStatisticsColumns = null;
        $capturedStatisticsRows = null;

        $this->client
            ->expects($this->once())
            ->method('upsert')
            ->willReturnCallback(
                function (string $table, array $rows, array $columns) use (
                    &$capturedStatisticsColumns,
                    &$capturedStatisticsRows,
                ) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME_STATISTICS) {
                        $capturedStatisticsColumns = $columns;
                        $capturedStatisticsRows = $rows;
                    }
                }
            );

        $this->client->method('query');

        $this->aggregation->flushUpserts();

        $this->assertNotNull($capturedStatisticsRows);
        $this->assertNotNull($capturedStatisticsColumns);
        // qualityScore position in the data row must match the columns array
        $this->assertSame($qualityScore, $capturedStatisticsRows[0]['qualityScore']);
        $this->assertSame(1, $capturedStatisticsRows[0]['latestEntry']);
    }

    public function testFlushUpsertsQualityScorePositionInLevTable(): void
    {
        $qualityScore = 42;

        $this->aggregation->upsertBuffered(new StatisticSegmentDTO(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            99,
            8,
            4,
            90,
            '',
            0,
            1,
            $qualityScore,
            1,
        ));

        $capturedLevColumns = null;
        $capturedLevRows = null;

        $this->client
            ->expects($this->once())
            ->method('upsert')
            ->willReturnCallback(
                function (string $table, array $rows, array $columns) use (
                    &$capturedLevColumns,
                    &$capturedLevRows,
                ) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME_STATISTICS) {
                        $capturedLevColumns = $columns;
                        $capturedLevRows = $rows;
                    }
                }
            );

        $this->client->method('query');

        $this->aggregation->flushUpserts();

        $this->assertNotNull($capturedLevRows);
        $this->assertNotNull($capturedLevColumns);

        // qualityScore column must be present and mapped correctly
        $qualityScoreIdx = array_search('qualityScore', $capturedLevColumns, true);
        $this->assertNotFalse($qualityScoreIdx);

        // values must match
        $this->assertSame($qualityScore, $capturedLevRows[0]['qualityScore']);
        $this->assertSame(1, $capturedLevRows[0]['latestEntry']);
    }

    public function testFlushUpsertsNullQualityScoreIsPreserved(): void
    {
        $this->aggregation->upsertBuffered(new StatisticSegmentDTO(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            1,
            0,
            0,
            100,
            '',
            0,
            1,
            null,
            1
        ));

        $capturedStatisticsRows = null;

        $this->client
            ->expects($this->once())
            ->method('upsert')
            ->willReturnCallback(
                function (string $table, array $rows, array $columns) use (&$capturedStatisticsRows) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME_STATISTICS) {
                        $capturedStatisticsRows = $rows;
                    }
                }
            );

        $this->client->method('query');

        $this->aggregation->flushUpserts();

        $this->assertNotNull($capturedStatisticsRows);
        $this->assertNull($capturedStatisticsRows[0]['qualityScore']);
        $this->assertSame(1, $capturedStatisticsRows[0]['latestEntry']);
    }

    public function testFlushUpsertsWritesOnlyStatisticsTable(): void
    {
        $this->aggregation->upsertBuffered(new StatisticSegmentDTO(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            7,
            2,
            1,
            70,
            '',
            0,
            1,
            55,
            1
        ));

        $posteditingTableCallCount = 0;
        $statisticsTableCallCount = 0;

        $this->client
            ->method('upsert')
            ->willReturnCallback(
                function (string $table) use (&$posteditingTableCallCount, &$statisticsTableCallCount) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME_POSTEDITING) {
                        $posteditingTableCallCount++;
                    } elseif ($table === SegmentHistoryAggregation::TABLE_NAME_STATISTICS) {
                        $statisticsTableCallCount++;
                    }
                }
            );

        $this->client->method('query');

        $result = $this->aggregation->flushUpserts();

        $this->assertTrue($result);
        $this->assertSame(0, $posteditingTableCallCount);
        $this->assertSame(1, $statisticsTableCallCount);
    }

    public function testUpdateQualityScorePrefersEditedInStep(): void
    {
        $taskGuid = '{abc-123}';
        $segmentId = 42;
        $qualityScore = 88;
        $capturedBind = [];

        $this->client
            ->expects($this->once())
            ->method('oneAssoc')
            ->willReturn([
                'workflowStepName' => 'review',
            ]);

        $this->client
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $bind) use (&$capturedBind, $qualityScore) {
                $capturedBind = $bind;
                $this->assertStringContainsString(SegmentHistoryAggregation::TABLE_NAME_STATISTICS, $sql);
                $this->assertStringContainsString('qualityScore=' . $qualityScore, $sql);
                $this->assertStringContainsString('workflowStepName = :workflowStepName', $sql);
            });

        $this->aggregation->updateQualityScore($taskGuid, $segmentId, 'review', $qualityScore);

        $this->assertSame('abc-123', $capturedBind['taskGuid']);
        $this->assertSame($segmentId, $capturedBind['segmentId']);
        $this->assertSame('review', $capturedBind['workflowStepName']);
    }

    public function testUpdateQualityScoreFallsBackToInitialStep(): void
    {
        $capturedBind = [];

        $this->client
            ->expects($this->once())
            ->method('oneAssoc')
            ->willReturn([
                'workflowStepName' => '_initial',
            ]);

        $this->client
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $bind) use (&$capturedBind) {
                $capturedBind = $bind;
                $this->assertStringContainsString('qualityScore=NULL', $sql);
            });

        $this->aggregation->updateQualityScore('{task}', 1, 'translation', null);

        $this->assertSame('_initial', $capturedBind['workflowStepName']);
    }

    public function testUpdateQualityScoreSkipsWhenNoMatchingStepExists(): void
    {
        $this->client
            ->expects($this->once())
            ->method('oneAssoc')
            ->willReturn([]);

        $this->client
            ->expects($this->never())
            ->method('query');

        $this->aggregation->updateQualityScore('{abc-def}', 1, 'review', 50);
    }

    public function testIncreaseOrInsertPosteditingDurationDelegatesToClient(): void
    {
        $this->client
            ->expects($this->once())
            ->method('upsertIncrementDuration')
            ->with(
                SegmentHistoryAggregation::TABLE_NAME_POSTEDITING,
                'task-guid',
                11,
                'review',
                'user-guid',
                1250
            );

        $this->aggregation->increaseOrInsertPosteditingDuration('{task-guid}', 11, 'review', '{user-guid}', 1250);
    }

    public function testIncreaseOrInsertPosteditingDurationSkipsZero(): void
    {
        $this->client
            ->expects($this->never())
            ->method('upsertIncrementDuration');

        $this->aggregation->increaseOrInsertPosteditingDuration('{task-guid}', 11, 'review', '{user-guid}', 0);
    }

    public function testUpdateOrInsertEditableUpdatesExistingRow(): void
    {
        $entry = new StatisticSegmentDTO(
            '{task-guid}',
            '{actor-guid}',
            'default',
            'review',
            11,
            2,
            1,
            100,
            'pretranslated;tm',
            3,
            0,
            80
        );

        $this->client
            ->expects($this->once())
            ->method('oneAssoc')
            ->willReturn([
                'segmentId' => 11,
            ]);

        $queryCount = 0;
        $this->client
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $bind) use (&$queryCount): void {
                $queryCount++;
                if ($queryCount === 1) {
                    $this->assertStringContainsString('SET latestEntry = 0', $sql);
                    $this->assertSame('task-guid', $bind['taskGuid']);
                    $this->assertSame(11, $bind['segmentId']);

                    return;
                }

                $this->assertStringContainsString('SET editable = :editable, latestEntry = 1', $sql);
                $this->assertSame('task-guid', $bind['taskGuid']);
                $this->assertSame(11, $bind['segmentId']);
                $this->assertSame('review', $bind['workflowStepName']);
                $this->assertSame(0, $bind['editable']);
            });

        $this->client
            ->expects($this->never())
            ->method('upsert');

        $this->aggregation->updateOrInsertEditable($entry);
    }

    public function testUpdateOrInsertEditableInsertsMissingRow(): void
    {
        $entry = new StatisticSegmentDTO(
            '{task-guid}',
            '{actor-guid}',
            'default',
            'review',
            12,
            3,
            2,
            99,
            'pretranslated;tm',
            4,
            1,
            77
        );

        $this->client
            ->expects($this->once())
            ->method('oneAssoc')
            ->willReturn([]);

        $queryCount = 0;
        $this->client
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $bind) use (&$queryCount): void {
                $queryCount++;
                $this->assertSame(1, $queryCount);
                $this->assertStringContainsString('SET latestEntry = 0', $sql);
                $this->assertSame('task-guid', $bind['taskGuid']);
                $this->assertSame(12, $bind['segmentId']);
            });

        $this->client
            ->expects($this->once())
            ->method('upsert')
            ->with(
                SegmentHistoryAggregation::TABLE_NAME_STATISTICS,
                $this->callback(function (array $rows): bool {
                    return isset($rows[0])
                        && $rows[0][0] === 'task-guid'
                        && $rows[0][1] === 'actor-guid'
                        && $rows[0][3] === 'review'
                        && $rows[0][4] === 12
                        && $rows[0][9] === 4
                        && $rows[0][10] === 1
                        && $rows[0][11] === 1
                        && $rows[0][12] === 77
                        && $rows[0][13] === 0;
                }),
                [
                    'taskGuid',
                    'userGuid',
                    'workflowName',
                    'workflowStepName',
                    'segmentId',
                    'levenshteinOriginal',
                    'levenshteinPrevious',
                    'matchRate',
                    'langResType',
                    'langResId',
                    'editable',
                    'latestEntry',
                    'qualityScore',
                    'segmentlengthPrevious',
                ]
            );

        $this->aggregation->updateOrInsertEditable($entry);
    }
}
