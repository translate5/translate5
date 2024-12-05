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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\LanguageResource\ReimportSegments;

use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_Iterator;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsProvider;
use MittagQI\Translate5\Segment\FilteredIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Models_Filter_ExtJs6;

class ReimportSegmentsProviderTest extends TestCase
{
    private Segment|MockObject $segmentMock;

    private ReimportSegmentsProvider $provider;

    protected function setUp(): void
    {
        $this->segmentMock = $this->createMock(Segment::class);
        $this->provider = new ReimportSegmentsProvider($this->segmentMock);
    }

    public function testGetSegmentsWithEmptyFilters(): void
    {
        $taskGuid = 'test-task-guid';
        $filters = [];

        // Expect that filterAndSort is never called when filters are empty
        $this->segmentMock->expects(self::never())->method('filterAndSort');

        $result = $this->provider->getSegments($taskGuid, $filters);

        self::assertInstanceOf(editor_Models_Segment_Iterator::class, $result);
        self::assertTrue($result->isEmpty());
    }

    public function testGetSegmentsWithTimestampFilter(): void
    {
        $taskGuid = 'test-task-guid';
        $timestamp = '2021-01-01 00:00:00';
        $filters = [
            ReimportSegmentsOptions::FILTER_TIMESTAMP => $timestamp,
        ];

        // Create a mock for the filter
        $filterMock = $this->createMock(ZfExtended_Models_Filter_ExtJs6::class);

        // Expect filterAndSort to be called with the filter mock
        $this->segmentMock->expects(self::atLeast(1))
            ->method('filterAndSort')
            ->with(self::isInstanceOf(ZfExtended_Models_Filter_ExtJs6::class));

        // Simulate getFilter() returning the filter mock
        $this->segmentMock->method('getFilter')->willReturn($filterMock);

        // Expect addFilter to be called with a filter object matching the timestamp
        $filterMock->expects(self::once())
            ->method('addFilter')
            ->with(self::callback(static function ($filterObject) use ($timestamp) {
                return $filterObject->field === 'timestamp' &&
                    $filterObject->comparison === 'eq' &&
                    $filterObject->value === $timestamp;
            }));

        $result = $this->provider->getSegments($taskGuid, $filters);

        $this->assertInstanceOf(FilteredIterator::class, $result);
    }

    public function testGetSegmentsWithOnlyEditedFilter(): void
    {
        $taskGuid = 'test-task-guid';
        $filters = [
            ReimportSegmentsOptions::FILTER_ONLY_EDITED => true,
        ];

        // Create a mock for the filter
        $filterMock = $this->createMock(ZfExtended_Models_Filter_ExtJs6::class);

        // Expect filterAndSort to be called
        $this->segmentMock->expects(self::atLeast(1))
            ->method('filterAndSort')
            ->with(self::isInstanceOf(ZfExtended_Models_Filter_ExtJs6::class));

        // Simulate getFilter() returning the filter mock
        $this->segmentMock->method('getFilter')->willReturn($filterMock);

        // Expect addFilter to be called with a filter object matching the autoStateId
        $filterMock->expects(self::once())
            ->method('addFilter')
            ->with(self::callback(static function ($filterObject) {
                return $filterObject->field === 'autoStateId' &&
                    $filterObject->type === 'notInList' &&
                    $filterObject->comparison === 'in' &&
                    $filterObject->value === [
                        AutoStates::NOT_TRANSLATED,
                        AutoStates::PRETRANSLATED,
                        AutoStates::LOCKED,
                        AutoStates::BLOCKED,
                    ];
            }));

        $result = $this->provider->getSegments($taskGuid, $filters);

        self::assertInstanceOf(FilteredIterator::class, $result);
    }

    public function testGetSegmentsWithBothFilters(): void
    {
        $taskGuid = 'test-task-guid';
        $timestamp = '2021-01-01 00:00:00';
        $filters = [
            ReimportSegmentsOptions::FILTER_TIMESTAMP => $timestamp,
            ReimportSegmentsOptions::FILTER_ONLY_EDITED => true,
        ];

        // Create a mock for the filter
        $filterMock = $this->createMock(ZfExtended_Models_Filter_ExtJs6::class);

        // Expect filterAndSort to be called
        $this->segmentMock->expects(self::atLeast(1))
            ->method('filterAndSort')
            ->with(self::isInstanceOf(ZfExtended_Models_Filter_ExtJs6::class));

        // Simulate getFilter() returning the filter mock
        $this->segmentMock->method('getFilter')->willReturn($filterMock);

        // Expect addFilter to be called twice
        $filterMock->expects(self::exactly(2))
            ->method('addFilter');

        $result = $this->provider->getSegments($taskGuid, $filters);

        $this->assertInstanceOf(FilteredIterator::class, $result);
    }
}
