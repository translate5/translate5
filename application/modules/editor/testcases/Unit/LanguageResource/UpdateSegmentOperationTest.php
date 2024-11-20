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

namespace LanguageResource;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment;
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Operation\UpdateSegmentOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class UpdateSegmentOperationTest extends TestCase
{
    private MockObject|TaskRepository $taskRepository;

    private MockObject|LanguageResourceRepository $languageResourceRepository;

    private MockObject|editor_Services_Manager $serviceManager;

    private MockObject|TaskTmRepository $taskTmRepository;

    private MockObject|ZfExtended_Logger $logger;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $this->taskTmRepository = $this->createMock(TaskTmRepository::class);
        $this->serviceManager = $this->createMock(editor_Services_Manager::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);
    }

    public function provideTestUpdateSegmentSkip(): iterable
    {
        yield [false, true];
        yield [true, false];
        yield [true, true];
    }

    /**
     * @dataProvider provideTestUpdateSegmentSkip
     */
    public function testUpdateSegmentEmptySourceOrTargetSkip(bool $hasEmptySource, bool $hasEmptyTarget): void
    {
        $segment = $this->getSegmentMock($hasEmptySource, $hasEmptyTarget);

        $this->serviceManager->expects(self::never())->method('getConnector');

        $service = new UpdateSegmentOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->taskTmRepository,
            $this->serviceManager,
            $this->logger
        );
        $service->updateSegment($segment);
    }

    public function testUpdateSegmentAllNotUpdatable(): void
    {
        $segment = $this->getSegmentMock(false, false);

        $this->languageResourceRepository->method('getAssociatedToTaskGroupedByType')->willReturn([
            'serviceType' => [
                [
                    'id' => 1,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
                [
                    'id' => 2,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
            ],
        ]);

        $this->serviceManager->method('getCreateTaskTmOperation')
            ->with('serviceType')
            ->willReturn(null);
        $this->serviceManager->expects(self::never())->method('getConnector');

        $service = new UpdateSegmentOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->taskTmRepository,
            $this->serviceManager,
            $this->logger
        );
        $service->updateSegment($segment);
    }

    public function testUpdateSegmentAll(): void
    {
        $segment = $this->getSegmentMock(false, false);

        $this->languageResourceRepository->method('getAssociatedToTaskGroupedByType')->willReturn([
            'serviceType' => [
                [
                    'id' => 1,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 2,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 3,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
            ],
        ]);

        $connectorMock1 = $this->createMock(UpdatableAdapterInterface::class);
        $connectorMock2 = $this->createMock(UpdatableAdapterInterface::class);

        $this->serviceManager->method('getCreateTaskTmOperation')
            ->with('serviceType')
            ->willReturn(null);
        $this->serviceManager
            ->method('getConnector')
            ->willReturnOnConsecutiveCalls($connectorMock1, $connectorMock2);

        $expectedOptions = [
            UpdatableAdapterInterface::RECHECK_ON_UPDATE => true,
            UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR => true,
        ];

        $connectorMock1->expects(self::once())->method('update')->with($segment, $expectedOptions);
        $connectorMock2->expects(self::once())->method('update')->with($segment, $expectedOptions);

        $service = new UpdateSegmentOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->taskTmRepository,
            $this->serviceManager,
            $this->logger
        );
        $service->updateSegment($segment);
    }

    public function testUpdateSegmentProjectNoProjectTmCreatedForTask(): void
    {
        $segment = $this->getSegmentMock(false, false);

        $this->languageResourceRepository->method('getAssociatedToTaskGroupedByType')->willReturn([
            'serviceType' => [
                [
                    'id' => 1,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 2,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 3,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
            ],
        ]);

        $this->taskTmRepository->method('getAllCreatedForTask')->willReturn([]);

        $this->serviceManager->expects(self::never())->method('getCreateTaskTmOperation');
        // 2 times because of the 2 language resources that are actually updatable
        $this->serviceManager->expects(self::exactly(2))
            ->method('getConnector')
            ->willReturn($this->createMock(UpdatableAdapterInterface::class));

        $service = new UpdateSegmentOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->taskTmRepository,
            $this->serviceManager,
            $this->logger
        );
        $service->updateSegment($segment);
    }

    public function testUpdateSegmentProjectNoProjectTmsAssigned(): void
    {
        $segment = $this->getSegmentMock(false, false);

        $this->languageResourceRepository->method('getAssociatedToTaskGroupedByType')->willReturn([
            'serviceType' => [
                [
                    'id' => 1,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 3,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
            ],
        ]);

        $taskTmMock = $this->createMock(LanguageResource::class);
        $taskTmMock->method('__call')->willReturnMap([
            ['getServiceType', [], 'serviceType'],
        ]);

        $this->taskTmRepository->method('getAllCreatedForTask')->willReturn([
            $taskTmMock,
        ]);

        $this->serviceManager->expects(self::never())->method('getCreateTaskTmOperation');
        $this->serviceManager->expects(self::never())
            ->method('getConnector')
            ->willReturn($this->createMock(UpdatableAdapterInterface::class));

        $this->logger->expects(self::once())->method('__call')->with('error');

        $service = new UpdateSegmentOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->taskTmRepository,
            $this->serviceManager,
            $this->logger
        );
        $service->updateSegment($segment);
    }

    public function testUpdateSegmentProject(): void
    {
        $segment = $this->getSegmentMock(false, false);

        $this->languageResourceRepository->method('getAssociatedToTaskGroupedByType')->willReturn([
            'serviceType' => [
                [
                    'id' => 1,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 3,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
            ],
            'anotherServiceType' => [
                [
                    'id' => 3,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => true,
                ],
                [
                    'id' => 4,
                    'serviceType' => 'serviceType',
                    'segmentsUpdateable' => false,
                ],
            ],
        ]);

        $taskTmMock = $this->createMock(LanguageResource::class);
        $taskTmMock->method('__call')->willReturnMap([
            ['getServiceType', [], 'serviceType'],
        ]);

        $this->taskTmRepository->method('getAllCreatedForTask')->willReturn([
            $taskTmMock,
        ]);

        $this->taskTmRepository->method('getOfTypeAssociatedToTask')->willReturn([
            $taskTmMock,
        ]);

        $this->serviceManager->expects(self::never())->method('getCreateTaskTmOperation');
        // 2 times because of 1 updatable language resource and 1 updatable project TM
        $this->serviceManager->expects(self::exactly(2))
            ->method('getConnector')
            ->willReturn($this->createMock(UpdatableAdapterInterface::class));

        $this->logger->expects(self::never())->method('__call')->with('error');

        $service = new UpdateSegmentOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->taskTmRepository,
            $this->serviceManager,
            $this->logger
        );
        $service->updateSegment($segment);
    }

    private function getSegmentMock($hasEmptySource, $hasEmptyTarget): MockObject|editor_Models_Segment
    {
        $segment = $this->createMock(editor_Models_Segment::class);
        $segment->method('hasEmptySource')->willReturn($hasEmptySource);
        $segment->method('hasEmptyTarget')->willReturn($hasEmptyTarget);
        $segment->method('__call')->willReturnMap([
            ['getTaskGuid', [], bin2hex(random_bytes(16))],
        ]);

        return $segment;
    }
}
