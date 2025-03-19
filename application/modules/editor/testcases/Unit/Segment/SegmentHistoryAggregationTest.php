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

namespace MittagQI\Translate5\Test\Unit\Segment;

use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\MariaDB;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class SegmentHistoryAggregationTest extends TestCase
{
    private $statDb;

    private SegmentHistoryAggregation $aggregation;

    protected function setUp(): void
    {
        $this->statDb = $this->getMockBuilder(MariaDB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAlive', 'upsert', 'query'])
            ->getMock();

        $this->statDb->method('isAlive')->willReturn(true);
        $this->aggregation = new SegmentHistoryAggregation($this->statDb, $this->createMock(ZfExtended_Logger::class));
    }

    public function testInserts(): void
    {
        $entry = [
            'f31882a2-6ad3-4049-b0b5-090744ae6dd0',
            'f31882a2-6ad3-4049-b0b5-090744ae6dd1',
            'simple',
            'review',
            123,
            120,
            4,
            5,
            100,
            'import;tm',
            1,
            1,
        ];

        $this->statDb
            ->expects(self::exactly(4))
            ->method('upsert');

        $this->aggregation->upsert(...$entry);

        $this->aggregation->upsertBuffered(...$entry);
        $this->aggregation->flushUpserts();

        // Test flush on no data
        $this->statDb
            ->expects(self::never())
            ->method('upsert');
        $this->aggregation->flushUpserts();
    }

    public function testRemoveTaskData(): void
    {
        $taskGuid = '{f31882a2-6ad3-4049-b0b5-090744ae6dd0}';
        $this->statDb
            ->expects(self::exactly(2))
            ->method('query')
            ->with(
                self::callback(fn ($sql): bool => stripos($sql, 'DELETE') !== false),
                [
                    'taskGuid' => trim($taskGuid, '{}'),
                ]
            );

        $this->aggregation->removeTaskData($taskGuid);
    }
}
