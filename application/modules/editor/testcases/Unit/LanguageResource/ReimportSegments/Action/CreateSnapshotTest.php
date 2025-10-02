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
use Faker\Factory;
use MittagQI\Translate5\Integration\Contract\SegmentUpdateDtoFactoryInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\Integration\SegmentUpdateDtoFactory;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Action\CreateSnapshot;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentDTO;
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
    private SegmentUpdateDtoFactory|MockObject $segmentUpdateDtoFactory;

    private ReimportSegmentRepositoryInterface|MockObject $segmentsRepositoryMock;

    private LanguageResourceRepository|MockObject $languageResourceRepositoryMock;

    private SegmentsProvider|MockObject $reimportSegmentsProviderMock;

    private CreateSnapshot $snapshot;

    protected function setUp(): void
    {
        $this->segmentUpdateDtoFactory = new SegmentUpdateDtoFactory([]);
        $this->segmentsRepositoryMock = $this->createMock(ReimportSegmentRepositoryInterface::class);
        $this->languageResourceRepositoryMock = $this->createMock(LanguageResourceRepository::class);
        $this->reimportSegmentsProviderMock = $this->createMock(SegmentsProvider::class);

        $this->snapshot = new CreateSnapshot(
            $this->segmentsRepositoryMock,
            $this->languageResourceRepositoryMock,
            $this->reimportSegmentsProviderMock,
            $this->segmentUpdateDtoFactory,
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

        $taskMock = $this->createMock(Task::class);
        $taskMock->method('__call')->willReturnMap([
            ['getTaskGuid', [], $taskGuid],
            ['getCustomerId', [], 1],
        ]);

        $taskMock->method('getConfig')->willReturn($this->createMock(Zend_Config::class));

        $this->languageResourceRepositoryMock->method('get')
            ->with($languageResourceId)
            ->willReturn($languageResourceMock);

        $filters = [
            ReimportSegmentsOptions::FILTER_TIMESTAMP => $timestamp,
            ReimportSegmentsOptions::FILTER_ONLY_EDITED => $onlyEdited,
        ];

        $segmentMock1 = $this->createMock(Segment::class);
        $segmentMock1->method('__call')->willReturnMap([
            ['getId', [], 1],
            ['getTaskGuid', [], $taskGuid],
        ]);
        $segmentMock2 = $this->createMock(Segment::class);
        $segmentMock2->method('__call')->willReturnMap([
            ['getId', [], 2],
            ['getTaskGuid', [], $taskGuid],
        ]);
        $segments = new \ArrayIterator([$segmentMock1, $segmentMock2]);
        $this->reimportSegmentsProviderMock->method('getSegments')
            ->with($taskGuid, $filters)
            ->willReturn($segments);

        $faker = Factory::create();

        $updateDTOMock1 = new UpdateSegmentDTO(
            $faker->paragraph(1),
            $faker->paragraph(1),
            $faker->filePath(),
            $faker->dateTime->getTimestamp(),
            $faker->userName(),
            $faker->company(),
        );
        $updateDTOMock2 = new UpdateSegmentDTO(
            $faker->paragraph(1),
            $faker->paragraph(1),
            $faker->filePath(),
            $faker->dateTime->getTimestamp(),
            $faker->userName(),
            $faker->company(),
        );

        $dtoFactoryStub = new class($segmentMock1, $updateDTOMock1, $updateDTOMock2) implements SegmentUpdateDtoFactoryInterface {
            public static SegmentUpdateDtoFactoryInterface $self;

            public function __construct(
                private Segment $segmentMock1,
                private UpdateSegmentDTO $updateDTOMock1,
                private UpdateSegmentDTO $updateDTOMock2,
            ) {
            }

            public static function create(): SegmentUpdateDtoFactoryInterface
            {
                return self::$self;
            }

            public function supports(LanguageResource $languageResource): bool
            {
                return true;
            }

            public function getUpdateDTO(
                LanguageResource $languageResource,
                Segment $segment,
                Zend_Config $config,
                ?UpdateOptions $updateOptions,
            ): UpdateSegmentDTO {
                return $segment === $this->segmentMock1 ? $this->updateDTOMock1 : $this->updateDTOMock2;
            }
        };
        $dtoFactoryStub::$self = $dtoFactoryStub;

        $this->segmentUpdateDtoFactory->addService(get_class($dtoFactoryStub));

        $i = 0;
        $this->segmentsRepositoryMock->expects(self::exactly(2))
            ->method('save')
            ->with($runId, self::callback(
                static function (ReimportSegmentDTO $reimportSegmentDTO) use (&$i, $updateDTOMock1, $updateDTOMock2, $taskGuid) {
                    if ($i++ === 0) {
                        return $reimportSegmentDTO->source === $updateDTOMock1->source
                            && $reimportSegmentDTO->target === $updateDTOMock1->target
                            && $reimportSegmentDTO->fileName === $updateDTOMock1->fileName
                            && $reimportSegmentDTO->timestamp === $updateDTOMock1->timestamp
                            && $reimportSegmentDTO->userName === $updateDTOMock1->userName
                            && $reimportSegmentDTO->context === $updateDTOMock1->context
                            && $reimportSegmentDTO->segmentId === 1
                            && $reimportSegmentDTO->taskGuid === $taskGuid
                        ;
                    }

                    return $reimportSegmentDTO->source === $updateDTOMock2->source
                        && $reimportSegmentDTO->target === $updateDTOMock2->target
                        && $reimportSegmentDTO->fileName === $updateDTOMock2->fileName
                        && $reimportSegmentDTO->timestamp === $updateDTOMock2->timestamp
                        && $reimportSegmentDTO->userName === $updateDTOMock2->userName
                        && $reimportSegmentDTO->context === $updateDTOMock2->context
                        && $reimportSegmentDTO->segmentId === 2
                        && $reimportSegmentDTO->taskGuid === $taskGuid
                    ;
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
