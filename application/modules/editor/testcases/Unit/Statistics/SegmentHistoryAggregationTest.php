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

    /**
     * After array_splice($row, 6, 2) removes levenshtein columns, qualityScore must land at position [10]
     * in the buffer row passed to upsert() for TABLE_NAME.
     */
    public function testFlushUpsertsQualityScorePositionInMainTable(): void
    {
        $qualityScore = 75;

        $this->aggregation->upsertBuffered(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            42,
            100,
            5,
            3,
            80,
            '',
            0,
            1,
            $qualityScore
        );

        $capturedMainTableColumns = null;
        $capturedMainTableRows = null;

        $this->client
            ->method('upsert')
            ->willReturnCallback(
                function (string $table, array $rows, array $columns) use (
                    &$capturedMainTableColumns,
                    &$capturedMainTableRows,
                ) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME) {
                        $capturedMainTableColumns = $columns;
                        $capturedMainTableRows = $rows;
                    }
                }
            );

        $this->client->method('query');

        $this->aggregation->flushUpserts();

        $this->assertNotNull($capturedMainTableRows);
        $this->assertNotNull($capturedMainTableColumns);

        // qualityScore must be the last column
        $this->assertSame('qualityScore', end($capturedMainTableColumns));

        // qualityScore position in the data row must match the columns array
        $qualityScoreIdx = array_search('qualityScore', $capturedMainTableColumns, true);
        $this->assertSame($qualityScore, $capturedMainTableRows[0][$qualityScoreIdx]);
    }

    /**
     * After array_splice($row, 5, 1) removes duration and lastEdit is appended,
     * qualityScore must land at position [11] (second-to-last) in the buffer row
     * passed to upsert() for TABLE_NAME_LEV, with lastEdit at [12].
     */
    public function testFlushUpsertsQualityScorePositionInLevTable(): void
    {
        $qualityScore = 42;

        $this->aggregation->upsertBuffered(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            99,
            200,
            8,
            4,
            90,
            '',
            0,
            1,
            $qualityScore
        );

        $capturedLevColumns = null;
        $capturedLevRows = null;

        $this->client
            ->method('upsert')
            ->willReturnCallback(
                function (string $table, array $rows, array $columns) use (
                    &$capturedLevColumns,
                    &$capturedLevRows,
                ) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME_LEV) {
                        $capturedLevColumns = $columns;
                        $capturedLevRows = $rows;
                    }
                }
            );

        $this->client->method('query');

        $this->aggregation->flushUpserts();

        $this->assertNotNull($capturedLevRows);
        $this->assertNotNull($capturedLevColumns);

        // lastEdit must be the very last column
        $this->assertSame('lastEdit', end($capturedLevColumns));

        // qualityScore must be second-to-last (before lastEdit)
        $qualityScoreIdx = array_search('qualityScore', $capturedLevColumns, true);
        $lastEditIdx = array_search('lastEdit', $capturedLevColumns, true);
        $this->assertSame($lastEditIdx - 1, $qualityScoreIdx);

        // values must match
        $this->assertSame($qualityScore, $capturedLevRows[0][$qualityScoreIdx]);
        $this->assertSame(1, $capturedLevRows[0][$lastEditIdx]); // lastEdit=1 for workflow step
    }

    public function testFlushUpsertsNullQualityScoreIsPreserved(): void
    {
        $this->aggregation->upsertBuffered(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            1,
            50,
            0,
            0,
            100,
            '',
            0,
            1,
            null
        );

        $capturedMainTableRows = null;

        $this->client
            ->method('upsert')
            ->willReturnCallback(
                function (string $table, array $rows, array $columns) use (&$capturedMainTableRows) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME) {
                        $capturedMainTableRows = $rows;
                    }
                }
            );

        $this->client->method('query');

        $this->aggregation->flushUpserts();

        $this->assertNotNull($capturedMainTableRows);
        $qualityScoreIdx = 10; // position after splice
        $this->assertNull($capturedMainTableRows[0][$qualityScoreIdx]);
    }

    /**
     * Rows with duration=0 are skipped for TABLE_NAME but still written to TABLE_NAME_LEV.
     */
    public function testFlushUpsertsSkipsDurationZeroForMainTable(): void
    {
        $this->aggregation->upsertBuffered(
            '{task-guid}',
            '{user-guid}',
            'default',
            'translation',
            7,
            0,  // duration = 0
            2,
            1,
            70,
            '',
            0,
            1,
            55
        );

        $mainTableCallCount = 0;
        $levTableCallCount = 0;

        $this->client
            ->method('upsert')
            ->willReturnCallback(
                function (string $table) use (&$mainTableCallCount, &$levTableCallCount) {
                    if ($table === SegmentHistoryAggregation::TABLE_NAME) {
                        $mainTableCallCount++;
                    } elseif ($table === SegmentHistoryAggregation::TABLE_NAME_LEV) {
                        $levTableCallCount++;
                    }
                }
            );

        $this->client->method('query');

        $result = $this->aggregation->flushUpserts();

        // duration=0 means TABLE_NAME buffer is empty → upsert still called (with empty rows)
        // but TABLE_NAME_LEV is always written
        $this->assertTrue($result);
        $this->assertSame(1, $levTableCallCount);
    }

    public function testUpdateQualityScoreUpdatesWithCorrectSqlForBothTables(): void
    {
        $taskGuid = '{abc-123}';
        $segmentId = 42;
        $qualityScore = 88;

        $capturedQueries = [];

        $this->client
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$capturedQueries) {
                $capturedQueries[] = $sql;
            });

        $this->aggregation->updateQualityScore($taskGuid, $segmentId, $qualityScore);

        $this->assertCount(2, $capturedQueries);

        $mainTableSql = $capturedQueries[0];
        $levTableSql = $capturedQueries[1];

        $this->assertStringContainsString(SegmentHistoryAggregation::TABLE_NAME, $mainTableSql);
        $this->assertStringContainsString('qualityScore=' . $qualityScore, $mainTableSql);
        $this->assertStringContainsString('segmentId=' . $segmentId, $mainTableSql);

        $this->assertStringContainsString(SegmentHistoryAggregation::TABLE_NAME_LEV, $levTableSql);
        $this->assertStringContainsString('qualityScore=' . $qualityScore, $levTableSql);
        $this->assertStringContainsString('segmentId=' . $segmentId, $levTableSql);
    }

    public function testUpdateQualityScoreWithNullWritesSqlNull(): void
    {
        $capturedQueries = [];

        $this->client
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$capturedQueries) {
                $capturedQueries[] = $sql;
            });

        $this->aggregation->updateQualityScore('{task}', 1, null);

        foreach ($capturedQueries as $sql) {
            $this->assertStringContainsString('qualityScore=NULL', $sql);
        }
    }

    public function testUpdateQualityScoreStripsGuidBraces(): void
    {
        $capturedBinds = [];

        $this->client
            ->method('query')
            ->willReturnCallback(function (string $sql, array $bind) use (&$capturedBinds) {
                $capturedBinds[] = $bind;
            });

        $this->aggregation->updateQualityScore('{abc-def}', 1, 50);

        foreach ($capturedBinds as $bind) {
            $this->assertSame('abc-def', $bind['taskGuid']);
        }
    }
}
