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

use editor_Models_Segment;
use editor_Models_SegmentField;
use MittagQI\Translate5\Repository\SegmentHistoryDataRepository;
use MittagQI\Translate5\Repository\SegmentHistoryRepository;
use MittagQI\Translate5\Segment\Exception\InvalidInputForLevenshtein;
use MittagQI\Translate5\Segment\LevenshteinCalculationService;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\Dto\SegmentLevenshteinDTO;
use MittagQI\Translate5\Statistics\Dto\StatisticSegmentDTO;
use MittagQI\Translate5\Statistics\SegmentLevenshteinRepository;
use MittagQI\Translate5\Statistics\UpdateSegmentService;
use MittagQI\Translate5\Test\UnitTestAbstract;
use PHPUnit\Framework\MockObject\MockObject;
use Zend_Config;
use Zend_Exception;

class UpdateSegmentServiceTest extends UnitTestAbstract
{
    private SegmentHistoryRepository|MockObject $history;

    private SegmentHistoryDataRepository|MockObject $historyData;

    private SegmentHistoryAggregation|MockObject $aggregator;

    private LevenshteinCalculationService $levenshtein;

    private SegmentLevenshteinRepository|MockObject $segmentLevenshteinRepository;

    protected function setUp(): void
    {
        static::setConfig(new Zend_Config([
            'resources' => [
                'db' => [
                    'statistics' => [
                        'enabled' => 1,
                    ],
                ],
            ],
        ]));

        $this->history = $this->createMock(SegmentHistoryRepository::class);
        $this->historyData = $this->createMock(SegmentHistoryDataRepository::class);
        $this->aggregator = $this->createMock(SegmentHistoryAggregation::class);
        $this->levenshtein = LevenshteinCalculationService::create();
        $this->segmentLevenshteinRepository = $this->createMock(SegmentLevenshteinRepository::class);
    }

    /**
     * @throws InvalidInputForLevenshtein
     * @throws Zend_Exception
     */
    public function testUpdateForDelegatesPosteditingDurationToAggregator(): void
    {
        $segmentId = 77;
        $duration = 1234;
        $taskGuid = '{task-guid}';
        $userGuid = '{user-guid}';
        $editedInStep = 'translation';

        $segmentMeta = new class() {
            public function getPreTransLangResUuid(): string
            {
                return '';
            }
        };

        $segment = $this->getMockBuilder(editor_Models_Segment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDuration', 'meta'])
            ->addMethods([
                'getId',
                'getTaskGuid',
                'getUserGuid',
                'getEditedInStep',
                'getTarget',
                'getTargetEdit',
                'getMatchRate',
                'getMatchRateType',
                'getEditable',
                'getQualityScore',
            ])
            ->getMock();
        $segment->method('getId')->willReturn($segmentId);
        $segment->method('getTaskGuid')->willReturn($taskGuid);
        $segment->method('getUserGuid')->willReturn($userGuid);
        $segment->method('getEditedInStep')->willReturn($editedInStep);
        $segment->method('getDuration')->with(editor_Models_SegmentField::TYPE_TARGET)->willReturn($duration);
        $segment->method('getTarget')->willReturn('source-text');
        $segment->method('getTargetEdit')->willReturn('edited-text');
        $segment->method('getMatchRate')->willReturn(98);
        $segment->method('getMatchRateType')->willReturn('fuzzy');
        $segment->method('getEditable')->willReturn(1);
        $segment->method('getQualityScore')->willReturn(null);
        $segment->method('meta')->willReturn($segmentMeta);

        $this->history->expects($this->once())
            ->method('loadLatestForSegment')
            ->with($segmentId, [
                'editedInStep != ?' => $editedInStep,
            ])
            ->willReturn([]);

        $this->historyData->expects($this->never())->method('loadByHistoryId');

        $this->aggregator->expects($this->once())
            ->method('increaseOrInsertPosteditingDuration')
            ->with($taskGuid, $segmentId, $editedInStep, $userGuid, $duration);

        $this->aggregator->expects($this->once())
            ->method('resetLastEdit')
            ->with($taskGuid, $segmentId);

        $this->aggregator->expects($this->once())
            ->method('upsert')
            ->with($this->callback(static function (StatisticSegmentDTO $dto): bool {
                return $dto->segmentlengthPrevious === 11;
            }))
            ->willReturn(true);

        $this->segmentLevenshteinRepository->expects($this->once())
            ->method('upsert')
            ->with($this->callback(static function (SegmentLevenshteinDTO $dto) use ($taskGuid, $segmentId): bool {
                return $dto->taskGuid === $taskGuid
                    && $dto->segmentId === $segmentId
                    && $dto->historyId === 0
                    && $dto->levenshteinOriginal === 6
                    && $dto->levenshteinPrevious === 6
                    && $dto->segmentlengthPrevious === 11;
            }));

        $service = new UpdateSegmentService(
            $this->history,
            $this->historyData,
            $this->aggregator,
            $this->levenshtein,
            $this->segmentLevenshteinRepository,
        );

        $service->updateFor($segment, 'default');
    }
}
