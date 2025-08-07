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

namespace LanguageResource\ReimportSegments\Action;

use DateTimeImmutable;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use editor_Models_Task as Task;
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Action\CreateSnapshot;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\LanguageResource\ReimportSegments\SegmentsProvider;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;

class CreateSnapshotTest extends TestCase
{
    private editor_Services_Manager|MockObject $serviceManagerMock;

    private ReimportSegmentRepositoryInterface|MockObject $segmentsRepositoryMock;

    private LanguageResourceRepository|MockObject $languageResourceRepositoryMock;

    private SegmentsProvider|MockObject $reimportSegmentsProviderMock;

    private CreateSnapshot $snapshot;

    protected function setUp(): void
    {
        $this->serviceManagerMock = $this->createMock(editor_Services_Manager::class);
        $this->segmentsRepositoryMock = $this->createMock(ReimportSegmentRepositoryInterface::class);
        $this->languageResourceRepositoryMock = $this->createMock(LanguageResourceRepository::class);
        $this->reimportSegmentsProviderMock = $this->createMock(SegmentsProvider::class);

        $this->snapshot = new CreateSnapshot(
            $this->serviceManagerMock,
            $this->segmentsRepositoryMock,
            $this->languageResourceRepositoryMock,
            $this->reimportSegmentsProviderMock,
        );
    }

    public function testCreateSnapshot(): void
    {
        $languageResourceId = 123;
        $runId = 'test-run-id';
        $timestamp = (new DateTimeImmutable('+' . random_int(1, 10) . ' days'))->format('Y-m-d H:i:s');
        $onlyEdited = true;
        $useSegmentTimestamp = true;
        $taskGuid = 'test-task-guid';

        $languageResourceMock = $this->createMock(LanguageResource::class);
        $connectorMock = $this->createMock(UpdatableAdapterInterface::class);

        $taskMock = $this->createMock(Task::class);
        $taskMock->method('__call')->willReturnMap([
            ['getTaskGuid', [], $taskGuid],
            ['getCustomerId', [], 1],
        ]);

        $taskMock->method('getConfig')->willReturn($this->createMock(Zend_Config::class));

        $this->languageResourceRepositoryMock->method('get')
            ->with($languageResourceId)
            ->willReturn($languageResourceMock);

        $this->serviceManagerMock->method('getConnector')
            ->willReturn($connectorMock);

        $filters = [
            ReimportSegmentsOptions::FILTER_TIMESTAMP => $timestamp,
            ReimportSegmentsOptions::FILTER_ONLY_EDITED => $onlyEdited,
        ];

        $segmentMock1 = $this->createMock(Segment::class);
        $segmentMock2 = $this->createMock(Segment::class);
        $segments = new \ArrayIterator([$segmentMock1, $segmentMock2]);
        $this->reimportSegmentsProviderMock->method('getSegments')
            ->with($taskGuid, $filters)
            ->willReturn($segments);

        $updateOptions = new UpdateOptions($useSegmentTimestamp, true, false, false);

        $updateDTOMock1 = $this->getMockBuilder(UpdateSegmentDTO::class)->disableOriginalConstructor()->getMock();
        $updateDTOMock2 = $this->getMockBuilder(UpdateSegmentDTO::class)->disableOriginalConstructor()->getMock();
        $connectorMock->method('getUpdateDTO')
            ->with(
                self::callback(static function (Segment $segmentMock) {
                    return true;
                }),
                $updateOptions
            )
            ->willReturnCallback(
                static function (Segment $segmentMock) use ($segmentMock1, $updateDTOMock1, $updateDTOMock2) {
                    return $segmentMock === $segmentMock1 ? $updateDTOMock1 : $updateDTOMock2;
                }
            );

        $i = 0;
        $this->segmentsRepositoryMock->expects(self::exactly(2))
            ->method('save')
            ->with($runId, self::callback(
                static function (UpdateSegmentDTO $updateDTO) use (&$i, $updateDTOMock1, $updateDTOMock2) {
                    return $updateDTO === ($i++ === 0 ? $updateDTOMock1 : $updateDTOMock2);
                }
            ));

        $this->snapshot->createSnapshot(
            $taskMock,
            $runId,
            $languageResourceId,
            $timestamp,
            $onlyEdited,
            $useSegmentTimestamp
        );
    }
}
