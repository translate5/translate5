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

use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Dto\StatisticFilterDTO;
use MittagQI\Translate5\Statistics\SegmentStatisticsRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class SegmentHistoryAggregationRepositoryTest extends TestCase
{
    private MockObject|AbstractStatisticsDB $client;

    private SegmentStatisticsRepository $repository;

    protected function setUp(): void
    {
        $this->client = $this->createMock(AbstractStatisticsDB::class);
        $logger = $this->createMock(ZfExtended_Logger::class);
        $this->repository = new SegmentStatisticsRepository($this->client, $logger);
    }

    public function testGetFilteredTaskIdsWithQualityScoreMinAddsCorrectWhereClause(): void
    {
        $this->client->method('isAlive')->willReturn(true);

        $capturedSql = null;
        $this->client
            ->method('select')
            ->willReturnCallback(function (string $sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return [];
            });

        $this->repository->getTaskGuidsMatchingFilter(
            ['{task-guid-1}'],
            StatisticFilterDTO::fromAssocArray([
                'qualityScoreMin' => 30,
            ])
        );

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('AND qualityScore>=30', $capturedSql);
        $this->assertStringNotContainsString('AND latestEntry=1', $capturedSql);
    }

    public function testGetFilteredTaskIdsWithQualityScoreMaxAddsCorrectWhereClause(): void
    {
        $this->client->method('isAlive')->willReturn(true);

        $capturedSql = null;
        $this->client
            ->method('select')
            ->willReturnCallback(function (string $sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return [];
            });

        $this->repository->getTaskGuidsMatchingFilter(
            ['{task-guid-1}'],
            StatisticFilterDTO::fromAssocArray([
                'qualityScoreMax' => 80,
            ])
        );

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('AND qualityScore<=80', $capturedSql);
        $this->assertStringNotContainsString('AND latestEntry=1', $capturedSql);
    }

    public function testGetFilteredTaskIdsWithBothQualityScoreFilters(): void
    {
        $this->client->method('isAlive')->willReturn(true);

        $capturedSql = null;
        $this->client
            ->method('select')
            ->willReturnCallback(function (string $sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return [];
            });

        $this->repository->getTaskGuidsMatchingFilter(
            ['{task-guid-1}'],
            StatisticFilterDTO::fromAssocArray([
                'qualityScoreMin' => 20,
                'qualityScoreMax' => 90,
            ])
        );

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('AND qualityScore>=20', $capturedSql);
        $this->assertStringContainsString('AND qualityScore<=90', $capturedSql);
        $this->assertStringNotContainsString('AND latestEntry=1', $capturedSql);
    }

    public function testGetFilteredTaskIdsWithoutQualityScoreFiltersHasNoQualityScoreClause(): void
    {
        $this->client->method('isAlive')->willReturn(true);

        $capturedSql = null;
        $this->client
            ->method('select')
            ->willReturnCallback(function (string $sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return [];
            });

        $this->repository->getTaskGuidsMatchingFilter(
            ['{task-guid-1}'],
            StatisticFilterDTO::fromAssocArray([
                'matchRateMin' => 75,
            ])
        );

        $this->assertNotNull($capturedSql);
        $this->assertStringNotContainsString('qualityScore', $capturedSql);
        $this->assertStringContainsString('AND matchRate>=75', $capturedSql);
        $this->assertStringNotContainsString('AND latestEntry=1', $capturedSql);
    }

    public function testGetFilteredTaskIdsWithWorkflowStepFilterDoesNotForceLastEdit(): void
    {
        $this->client->method('isAlive')->willReturn(true);

        $capturedSql = null;
        $this->client
            ->method('select')
            ->willReturnCallback(function (string $sql) use (&$capturedSql) {
                $capturedSql = $sql;

                return [];
            });

        $this->repository->getTaskGuidsMatchingFilter(
            ['{task-guid-1}'],
            StatisticFilterDTO::fromAssocArray([
                'qualityScoreMin' => 50,
                'workflowStep' => ['translation'],
            ])
        );

        $this->assertNotNull($capturedSql);
        $this->assertStringContainsString('AND qualityScore>=50', $capturedSql);
        $this->assertStringNotContainsString('AND latestEntry=1', $capturedSql);
    }

    public function testGetFilteredTaskIdsReturnsEmptyWhenClientNotAlive(): void
    {
        $this->client->method('isAlive')->willReturn(false);
        $this->client->expects($this->never())->method('select');

        $result = $this->repository->getTaskGuidsMatchingFilter(
            ['{task-guid-1}'],
            StatisticFilterDTO::fromAssocArray([
                'qualityScoreMin' => 50,
            ])
        );

        $this->assertSame([], $result);
    }

    public function testGetFilteredTaskIdsReturnsGuidWithBraces(): void
    {
        $this->client->method('isAlive')->willReturn(true);
        $this->client
            ->method('select')
            ->willReturn([[
                'taskGuid' => 'abc-123',
            ]]);

        $result = $this->repository->getTaskGuidsMatchingFilter(
            ['{abc-123}'],
            StatisticFilterDTO::fromAssocArray([
                'qualityScoreMin' => 50,
            ])
        );

        $this->assertSame(['{abc-123}'], $result);
    }
}
