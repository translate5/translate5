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

namespace LanguageResource\ReimportSegments;

use DateTimeImmutable;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use editor_Models_Task as Task;
use editor_Services_Manager;
use editor_Services_OpenTM2_Connector as Connector;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegments;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsLoggerProvider;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\SegmentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;
use ZfExtended_Logger;

class ReimportSegmentsTest extends TestCase
{
    private ReimportSegmentRepositoryInterface&MockObject $reimportSegmentRepositoryMock;

    private LanguageResourceRepository&MockObject $languageResourceRepositoryMock;

    private editor_Services_Manager&MockObject $serviceManagerMock;

    private ReimportSegmentsLoggerProvider&MockObject $loggerProviderMock;

    private ReimportSegments $reimportSegments;

    private SegmentRepository|MockObject $segmentRepositoryMock;

    private TmConversionService|MockObject $tmConversionServiceMock;

    protected function setUp(): void
    {
        $this->reimportSegmentRepositoryMock = $this->createMock(ReimportSegmentRepositoryInterface::class);
        $this->languageResourceRepositoryMock = $this->createMock(LanguageResourceRepository::class);
        $this->serviceManagerMock = $this->createMock(editor_Services_Manager::class);
        $this->loggerProviderMock = $this->createMock(ReimportSegmentsLoggerProvider::class);
        $this->segmentRepositoryMock = $this->createMock(SegmentRepository::class);
        $this->tmConversionServiceMock = $this->createMock(TmConversionService::class);

        $this->reimportSegments = new ReimportSegments(
            reimportSegmentRepository: $this->reimportSegmentRepositoryMock,
            languageResourceRepository: $this->languageResourceRepositoryMock,
            serviceManager: $this->serviceManagerMock,
            loggerProvider: $this->loggerProviderMock,
            segmentRepository: $this->segmentRepositoryMock,
            tmConversionService: $this->tmConversionServiceMock,
        );
    }

    public function segmentsProvider(): array
    {
        $taskGuid = bin2hex(random_bytes(16));
        $segments = [];

        for ($i = 0; $i < random_int(2, 10); $i++) {
            $segments[] = $this->getUpdateDto(
                $taskGuid,
                $i,
                bin2hex(random_bytes(16)),
                bin2hex(random_bytes(16))
            );
        }

        return [
            [$taskGuid, $segments],
        ];
    }

    /**
     * @dataProvider segmentsProvider
     */
    public function testReimportWithSuccessfulUpdate(string $taskGuid, array $segments): void
    {
        $languageResourceId = random_int(1, 100);
        $runId = bin2hex(random_bytes(16));
        $taskId = random_int(1, 100);

        $taskMock = $this->getTaskMock($taskId, $taskGuid);
        $taskMock->method('__call')
            ->willReturnMap([
                ['getSourceLang', [], 4],
                ['getTargetLang', [], 5],
            ]);

        $languageResourceMock = $this->createMock(LanguageResource::class);
        $languageResourceMock->method('__call')
            ->willReturnMap([
                ['getId', [], $languageResourceId],
            ]);
        $this->languageResourceRepositoryMock
            ->method('get')
            ->with($languageResourceId)
            ->willReturn($languageResourceMock);

        $this->reimportSegmentRepositoryMock
            ->method('getByTask')
            ->with($runId, $taskGuid)
            ->willReturn($segments);

        $this->reimportSegmentRepositoryMock->expects(self::once())
            ->method('cleanByTask')
            ->with($runId, $taskGuid);

        $connectorMock = $this->createMock(Connector::class);

        $this->serviceManagerMock
            ->method('getConnector')
            ->willReturn($connectorMock);

        $this->tmConversionServiceMock->method('convertPair')
            ->willReturnCallback(
                static function (string $source, string $target) {
                    return [
                        $source,
                        $target,
                    ];
                }
            );

        $connectorMock->expects(self::exactly(count($segments)))
            ->method('updateWithDTO')
            ->with(
                self::callback(static fn ($updateDTO) => $updateDTO instanceof UpdateSegmentDTO),
                options: [
                    UpdatableAdapterInterface::SAVE_TO_DISK => false,
                ],
                segment: self::callback(static fn ($segment) => $segment instanceof Segment)
            );

        $connectorMock->expects(self::exactly(2))
            ->method('checkUpdatedSegment')
            ->with(self::callback(static fn ($segment) => $segment instanceof Segment));

        $loggerMock = $this->createMock(ZfExtended_Logger::class);
        $this->loggerProviderMock
            ->method('getLogger')
            ->willReturn($loggerMock);

        $this->reimportSegments->reimport(task: $taskMock, runId: $runId, languageResourceId: $languageResourceId);
    }

    public function testUpdateEmpty(): void
    {
        $taskGuid = bin2hex(random_bytes(16));
        $languageResourceId = random_int(1, 100);
        $runId = bin2hex(random_bytes(16));
        $taskId = random_int(0, 100);

        $taskMock = $this->getTaskMock($taskId, $taskGuid);

        $languageResourceMock = $this->createMock(LanguageResource::class);
        $languageResourceMock->method('__call')
            ->willReturnMap([
                ['getId', [], $languageResourceId],
            ]);
        $this->languageResourceRepositoryMock
            ->method('get')
            ->with($languageResourceId)
            ->willReturn($languageResourceMock);

        $updateDTOMock1 = $this->getUpdateDto(
            $taskGuid,
            1,
            '',
            bin2hex(random_bytes(16))
        );

        $updateDTOMock2 = $this->getUpdateDto(
            $taskGuid,
            2,
            bin2hex(random_bytes(16)),
            '',
        );

        $this->reimportSegmentRepositoryMock
            ->method('getByTask')
            ->with($runId, $taskGuid)
            ->willReturn([$updateDTOMock1, $updateDTOMock2]);

        $this->reimportSegmentRepositoryMock->expects(self::once())
            ->method('cleanByTask')
            ->with($runId, $taskGuid);

        $connectorMock = $this->createMock(Connector::class);
        $connectorMock->expects(self::never())->method('updateWithDTO');
        $connectorMock->expects(self::never())->method('checkUpdatedSegment');

        $this->serviceManagerMock
            ->method('getConnector')
            ->willReturn($connectorMock);

        $loggerMock = $this->createMock(ZfExtended_Logger::class);
        $this->loggerProviderMock
            ->method('getLogger')
            ->willReturn($loggerMock);

        $loggerMock->expects(self::once())
            ->method('__call')
            ->with(
                'info',
                [
                    'E1713',
                    'Task {taskId} re-imported into the desired TM {tmId}',
                    [
                        'taskId' => $taskId,
                        'tmId' => $languageResourceId,
                        'emptySegments' => 2,
                        'successfulSegments' => 0,
                        'failedSegments' => [],
                    ],
                ]
            );

        $this->reimportSegments->reimport(task: $taskMock, runId: $runId, languageResourceId: $languageResourceId);
    }

    public function testUpdateWithError(): void
    {
        $taskGuid = bin2hex(random_bytes(16));
        $languageResourceId = random_int(1, 100);
        $runId = bin2hex(random_bytes(16));
        $taskId = random_int(0, 100);

        $taskMock = $this->getTaskMock($taskId, $taskGuid);

        $languageResourceMock = $this->createMock(LanguageResource::class);
        $languageResourceMock->method('__call')
            ->willReturnMap([
                ['getId', [], $languageResourceId],
            ]);
        $this->languageResourceRepositoryMock
            ->method('get')
            ->with($languageResourceId)
            ->willReturn($languageResourceMock);

        $updateDTOMock1 = $this->getUpdateDto(
            $taskGuid,
            1,
            bin2hex(random_bytes(16)),
            bin2hex(random_bytes(16))
        );

        $updateDTOMock2 = $this->getUpdateDto(
            $taskGuid,
            2,
            bin2hex(random_bytes(16)),
            bin2hex(random_bytes(16))
        );

        $this->reimportSegmentRepositoryMock
            ->method('getByTask')
            ->with($runId, $taskGuid)
            ->willReturn([$updateDTOMock1, $updateDTOMock2]);

        $this->reimportSegmentRepositoryMock->expects(self::once())
            ->method('cleanByTask')
            ->with($runId, $taskGuid);

        $connectorMock = $this->createMock(Connector::class);
        $connectorMock->expects(self::never())->method('checkUpdatedSegment');
        $connectorMock->method('updateWithDTO')
            ->willThrowException(new SegmentUpdateException());

        $this->serviceManagerMock
            ->method('getConnector')
            ->willReturn($connectorMock);

        $loggerMock = $this->createMock(ZfExtended_Logger::class);
        $this->loggerProviderMock
            ->method('getLogger')
            ->willReturn($loggerMock);

        $call = 0;
        $loggerMock->method('__call')
            ->with(
                'info',
                self::callback(function (array $params) use (&$call) {
                    $call++;

                    // 10 here is an amount of retries during reimport having failed segments
                    /** @see ReimportSegments::MAX_TRIES */
                    if ($call <= 10) {
                        self::assertEquals('E1714', $params[0]);
                        self::assertEquals('Task reimport finished with failed segments, trying to reimport them', $params[1]);
                        self::assertEquals([1, 2], $params[2]['failedSegments']);

                        return true;
                    }

                    // After we reached the max tries we should log the final result having a list of failed segments
                    self::assertEquals('E1713', $params[0]);
                    self::assertEquals('Task {taskId} re-imported into the desired TM {tmId}', $params[1]);
                    self::assertEquals(0, $params[2]['emptySegments']);
                    self::assertEquals(0, $params[2]['successfulSegments']);
                    self::assertEquals([1, 2], $params[2]['failedSegments']);

                    return true;
                })
            );

        $this->tmConversionServiceMock->method('convertPair')
            ->willReturnCallback(
                static function (string $source, string $target) {
                    return [
                        $source,
                        $target,
                    ];
                }
            );

        $this->reimportSegments->reimport(task: $taskMock, runId: $runId, languageResourceId: $languageResourceId);
    }

    private function getUpdateDto(
        string $taskGuid,
        int $segmentId,
        string $sourceText,
        string $targetText
    ): UpdateSegmentDTO {
        return new UpdateSegmentDTO(
            $taskGuid,
            $segmentId,
            $sourceText,
            $targetText,
            bin2hex(random_bytes(16)),
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            bin2hex(random_bytes(8)),
            bin2hex(random_bytes(8)),
        );
    }

    private function getTaskMock(int $taskId, string $taskGuid): MockObject|Task
    {
        $taskMock = $this->createMock(Task::class);
        $taskMock->method('__call')
            ->willReturnMap([
                ['getTaskGuid', [], $taskGuid],
                ['getConfig', [], $this->createMock(Zend_Config::class)],
                ['getCustomerId', [], 1],
                ['getId', [], $taskId],
            ]);

        return $taskMock;
    }
}
