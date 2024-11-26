<?php

namespace Segment\BatchOperations;

use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_Exception;
use editor_Models_Segment_InternalTag as InternalTag;
use editor_Models_Segment_Iterator as SegmentIterator;
use editor_Models_Segment_Meta as SegmentMeta;
use editor_Models_SegmentHistory as SegmentHistory;
use editor_Models_Task as Task;
use MittagQI\Translate5\Segment\BatchOperations\ApplyEditFullMatchOperation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplyEditFullMatchOperationTest extends TestCase
{
    /**
     * @dataProvider segmentDataProvider
     * @throws editor_Models_Segment_Exception
     */
    public function testUpdateSegmentsEdit100PercentMatch(
        bool $edit100PercentMatch,
        Segment&MockObject $segment,
    ) {
        $segmentsMock = $this->getSingleSegmentIterator($segment);

        $autoStates = $this->getMockBuilder(AutoStates::class)
            ->onlyMethods(['recalculateLockedState', 'recalculateUnLockedState'])
            ->getMock();

        // Create an instance of the class under test
        $operation = new ApplyEditFullMatchOperation(
            $autoStates,
            new InternalTag(),
            $this->createMock(SegmentMeta::class),
        );

        $autoStates
            ->expects($this->any())
            ->method('recalculateLockedState')
            ->willReturn($autoStates::TRANSLATED);

        $autoStates
            ->method('recalculateUnLockedState')
            ->willReturn($autoStates::TRANSLATED);

        $taskMock = $this->getMockBuilder(Task::class)
            ->addMethods(['getLockLocked'])
            ->getMock();
        $taskMock
            ->method('getLockLocked')
            ->willReturn(true); //can be always true, test cases are controlled by segment meta getLocked

        // Call the method under test
        $operation->updateSegmentsEdit100PercentMatch(
            $taskMock,
            /* @phpstan-ignore-next-line */
            $segmentsMock,
            $edit100PercentMatch
        );

        // Add assertions to verify the expected behavior
        // Example: $this->assertTrue(...);
    }

    public function segmentDataProvider(): iterable
    {
        yield 'blocked protected 1' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::BLOCKED,
            ], false),
        ];

        yield 'blocked protected 2' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::BLOCKED,
            ], false),
        ];

        yield 'already not editable' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => false,
            ], false),
        ];

        yield 'already editable' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
            ], false),
        ];

        yield 'no full match' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 99,
            ], false),
        ];

        yield 'no full match editing' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 99,
            ], false),
        ];

        yield 'enable full match editing' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => false,
                'getMatchRate' => 100,
                'getSource' => 'Has Text',
            ], true),
        ];

        yield 'enable full match editing locked' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => false,
                'getMatchRate' => 100,
                'getSource' => 'Has Text',
            ], false, locked: true),
        ];

        yield 'enable full match editing no text' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => false,
                'getMatchRate' => 100,
                'getSource' => '',
            ], false),
        ];

        yield 'enable full match editing no text locked' => [
            'edit100PercentMatch' => true,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => false,
                'getMatchRate' => 100,
                'getSource' => '',
            ], false, locked: true),
        ];

        yield 'disable full match editing pretrans' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 100,
                'getPretrans' => Segment::PRETRANS_INITIAL,
                'getMatchRateType' => 'pretranslated;tm',
            ], false),
        ];

        yield 'disable full match editing pretrans locked' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 100,
                'getPretrans' => Segment::PRETRANS_INITIAL,
                'getMatchRateType' => 'pretranslated;tm',
            ], true, locked: true),
        ];

        yield 'disable full match editing no-pretrans locked' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 100,
                'getPretrans' => Segment::PRETRANS_NOTDONE,
                'getMatchRateType' => 'pretranslated;tm',
            ], false, locked: true),
        ];

        yield 'disable full match editing pretrans termcollection locked' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 100,
                'getPretrans' => Segment::PRETRANS_INITIAL,
                'getMatchRateType' => 'pretranslated;termcollection',
            ], true, locked: true),
        ];

        yield 'disable full match editing pretrans mt locked' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 100,
                'getPretrans' => Segment::PRETRANS_INITIAL,
                'getMatchRateType' => 'pretranslated;mt',
            ], false, locked: true),
        ];

        yield 'disable full match not locked' => [
            'edit100PercentMatch' => false,
            'segment' => $this->getSegmentMock([
                'getAutoStateId' => AutoStates::TRANSLATED,
                'getEditable' => true,
                'getMatchRate' => 100,
                'getPretrans' => Segment::PRETRANS_INITIAL,
                'getMatchRateType' => 'pretranslated;mt',
            ], false),
        ];
    }

    private function getSegmentMock(
        array $getters,
        bool $save,
        bool $locked = false,
        bool $calculatedEditable = true,
    ): Segment&MockObject {
        $segmentMock = $this->getMockBuilder(Segment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNewHistoryEntity', 'save', 'meta'])
            ->addMethods(array_merge(array_keys($getters), ['setAutoStateId', 'setEditable']))
            ->getMock();
        foreach ($getters as $getter => $value) {
            $segmentMock->method($getter)->willReturn($value);
        }
        $segmentMock->method('getNewHistoryEntity')->willReturnCallback(function () {
            $history = $this->createMock(SegmentHistory::class);
            $history->expects($this->any())
                ->method('save');

            return $history;
        });

        $segmentMock->method('meta')->willReturnCallback(function () use ($locked) {
            $metaMock = $this->getMockBuilder(SegmentMeta::class)
                ->addMethods(['getLocked', 'getAutopropagated'])
                ->getMock();
            $metaMock
                ->method('getLocked')
                ->willReturn($locked);

            $metaMock
                ->method('getAutopropagated')
                ->willReturn(true);

            return $metaMock;
        });

        if ($save) {
            $segmentMock->expects($this->once())
                ->method('save');
            $segmentMock->expects($this->once())
                ->method('setAutoStateId');
            $segmentMock->expects($this->once())
                ->method('setEditable')
                ->with($this->equalTo($calculatedEditable));
        } else {
            $segmentMock->expects($this->never())
                ->method('save');
        }

        return $segmentMock;
    }

    /**
     * @return (SegmentIterator&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getSingleSegmentIterator($segment): SegmentIterator|\PHPUnit\Framework\MockObject\MockObject
    {
        // Create a mock for SegmentIterator and configure it as an iterator
        $segmentsMock = $this->getMockBuilder(SegmentIterator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['current', 'next', 'key', 'valid', 'rewind'])
            ->getMock();

        // Set up the iterator behavior using the provided segments
        $segmentsMock->method('rewind');

        $segmentsMock->method('valid')->willReturnOnConsecutiveCalls(true, false);

        $segmentsMock->method('current')
            ->willReturnCallback(function () use ($segment) {
                return $segment;
            });

        $segmentsMock->method('next');

        return $segmentsMock;
    }
}
