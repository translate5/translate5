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

namespace MittagQI\Translate5\Test\Unit\LanguageResource\ProjectTm\Workflow\Executors;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\Workflow\Executors\ReimportSegmentsActionExecutor;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class ReimportSegmentsActionExecutorTest extends TestCase
{
    private ZfExtended_Logger|MockObject $logger;

    private editor_Models_Task|MockObject $task;

    private ReimportSegmentsQueue|MockObject $reimportQueue;

    private LanguageResourceRepository|MockObject $languageResourceRepository;

    private TaskTmRepository|MockObject $taskTmRepository;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(ZfExtended_Logger::class);
        $this->task = $this->createMock(editor_Models_Task::class);
        $this->reimportQueue = $this->createMock(ReimportSegmentsQueue::class);
        $this->languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $this->taskTmRepository = $this->createMock(TaskTmRepository::class);
    }

    public function testReimportSegmentsSkipAll(): void
    {
        $taskGuid = bin2hex(random_bytes(16));
        $this->task->method('__call')->willReturnMap([
            ['getTaskGuid', [], $taskGuid],
        ]);

        $this->languageResourceRepository
            ->method('getAssociatedToTaskGroupedByType')
            ->willReturn([]);

        $this->reimportQueue->expects(self::never())
            ->method('queueReimport');

        $executor = $this->createExecutor();
        $executor->reimportSegments($this->task);
    }

    public function testReimportSegmentsLogNoProjectTm(): void
    {
        $taskGuid = bin2hex(random_bytes(16));
        $this->task->method('__call')->willReturnMap([
            ['getTaskGuid', [], $taskGuid],
        ]);

        $data = [
            'supported' => [
                [
                    'id' => 1,
                ],
            ],
        ];

        $this->languageResourceRepository
            ->method('getAssociatedToTaskGroupedByType')
            ->willReturn($data);

        $createdForTaskTm = $this->createMock(LanguageResource::class);

        $this->taskTmRepository->expects(self::once())
            ->method('findOfTypeCreatedForTask')
            ->with($taskGuid, 'supported')
            ->willReturn($createdForTaskTm);

        $this->logger->expects(self::once())
            ->method('__call')->with('warning');

        $executor = $this->createExecutor();
        $executor->reimportSegments($this->task);
    }

    public function testReimportSegmentsSkipUnsupported(): void
    {
        $taskGuid = bin2hex(random_bytes(16));
        $this->task->method('__call')->willReturnMap([
            ['getTaskGuid', [], $taskGuid],
        ]);

        $data = [
            'unsupported' => [
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
                [
                    'id' => 3,
                ],
            ],
            'supported' => [
                [
                    'id' => 4,
                ],
                [
                    'id' => 5,
                ],
                [
                    'id' => 6,
                ],
                [
                    'id' => 7,
                ],
            ],
        ];

        $this->languageResourceRepository
            ->method('getAssociatedToTaskGroupedByType')
            ->willReturn($data);

        $createdForTaskTm = $this->createMock(LanguageResource::class);

        $this->taskTmRepository->expects(self::exactly(2))
            ->method('findOfTypeCreatedForTask')
            ->willReturnCallback(
                static function ($taskGuid, $serviceType) use ($createdForTaskTm) {
                    return 'supported' === $serviceType ? $createdForTaskTm : null;
                }
            );

        $languageResourceIds = [4, 5, 6, 7];
        $loopIndex = 0;
        $this->reimportQueue->expects(self::exactly(count($languageResourceIds)))
            ->method('queueReimport')
            ->with(
                $taskGuid,
                self::callback(static function ($languageResourceId) use (&$loopIndex, $languageResourceIds) {
                    return $languageResourceIds[$loopIndex++] === $languageResourceId;
                }),
                [
                    ReimportSegmentsOptions::FILTER_ONLY_EDITED => true,
                    ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP => true,
                ]
            );

        $executor = $this->createExecutor();
        $executor->reimportSegments($this->task);
    }

    private function createExecutor(): ReimportSegmentsActionExecutor
    {
        return new ReimportSegmentsActionExecutor(
            $this->logger,
            $this->reimportQueue,
            $this->languageResourceRepository,
            $this->taskTmRepository
        );
    }
}
