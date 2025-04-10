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

namespace MittagQI\Translate5\Test\Integration\LanguageResource\ReimportSegments\Repository;

use Faker\Factory;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\JsonlReimportSegmentsRepository;
use PHPUnit\Framework\TestCase;

class JsonlReimportSegmentsRepositoryTest extends TestCase
{
    private JsonlReimportSegmentsRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new JsonlReimportSegmentsRepository();
    }

    protected function tearDown(): void
    {
        $dir = APPLICATION_DATA . '/' . 'ReimportSegments';

        if (is_dir($dir)) {
            array_map('unlink', glob("$dir/*"));
            rmdir($dir);
        }
    }

    public function testSaveSavesDataToFile(): void
    {
        $runId = bin2hex(random_bytes(5));
        $taskGuid = 'testTaskGuid';

        $dto = new UpdateSegmentDTO(
            $taskGuid,
            1,
            'source text',
            'target text',
            'file.txt',
            '2025-01-01 12:00:00',
            'user',
            'context',
        );

        $this->repository->save($runId, $dto);
        $filename = APPLICATION_DATA . '/' . 'ReimportSegments' . '/' . $runId . '_' . $taskGuid . '.jsonl';
        self::assertFileExists($filename);
    }

    public function testGetByTaskReturnsSavedData(): void
    {
        $runId = bin2hex(random_bytes(5));
        $taskGuid = bin2hex(random_bytes(10));

        $data = [];

        for ($i = 1; $i < random_int(2, 10); $i++) {
            $dto = new UpdateSegmentDTO(
                $taskGuid,
                1,
                'source text',
                'target text',
                'file.txt',
                '2025-01-01 12:00:00',
                'user',
                'context',
            );
            $data[] = $dto;
            $this->repository->save($runId, $dto);
        }

        $savedData = iterator_to_array($this->repository->getByTask($runId, $taskGuid));
        self::assertEquals($data, $savedData);
    }

    public function testCleanByTaskRemovesFile(): void
    {
        $runId = bin2hex(random_bytes(5));
        $taskGuid = 'testTaskGuid';

        $dto = new UpdateSegmentDTO(
            $taskGuid,
            1,
            'source text',
            'target text',
            'file.txt',
            '2025-01-01 12:00:00',
            'user',
            'context',
        );

        $this->repository->save($runId, $dto);
        $filename = APPLICATION_DATA . '/' . 'ReimportSegments' . '/' . $runId . '_' . $taskGuid . '.jsonl';
        self::assertFileExists($filename);

        $this->repository->cleanByTask($runId, $taskGuid);
        self::assertFileDoesNotExist($filename);
    }

    public function testGetByTaskReturnsEmptyIterableForNonExistentFile(): void
    {
        $result = iterator_to_array(
            $this->repository->getByTask(
                bin2hex(random_bytes(5)),
                bin2hex(random_bytes(10))
            )
        );
        self::assertEmpty($result);
    }

    public function testSaveCreatesDirectoryIfNotExists(): void
    {
        $dir = APPLICATION_DATA . '/' . 'ReimportSegments';

        if (is_dir($dir)) {
            rmdir($dir);
        }

        $this->repository->save(
            bin2hex(random_bytes(5)),
            new UpdateSegmentDTO(
                'testTaskGuid',
                1,
                'source text',
                'target text',
                'file.txt',
                '2025-01-01 12:00:00',
                'user',
                'context',
            )
        );
        $this->assertDirectoryExists($dir);
    }

    public function provideData(): iterable
    {
        $faker = Factory::create();

        yield 'some random data' => [
            [
                'taskGuid' => $faker->uuid(),
                'segmentId' => $faker->numberBetween(1, 1000000),
                'source' => $faker->realText(),
                'target' => $faker->realText(),
                'fileName' => $faker->word() . '.' . $faker->fileExtension(),
                'timestamp' => $faker->dateTime()->format(\DateTimeImmutable::ATOM),
                'userName' => $faker->userName(),
                'context' => $faker->word(),
            ],
        ];

        yield 'source and target with special characters' => [
            [
                'taskGuid' => $faker->uuid(),
                'segmentId' => $faker->numberBetween(1, 1000000),
                'source' => $faker->realText(10) . '~`!@#$%^&*()_+-=}]{["\':;?/|\\>.<,' . $faker->realText(10),
                'target' => $faker->realText(),
                'fileName' => $faker->word() . '.' . $faker->fileExtension(),
                'timestamp' => $faker->dateTime()->format(\DateTimeImmutable::ATOM),
                'userName' => $faker->userName(),
                'context' => $faker->word(),
            ],
        ];

        yield 'source and target with backslash at the end' => [
            [
                'taskGuid' => $faker->uuid(),
                'segmentId' => $faker->numberBetween(1, 1000000),
                'source' => $faker->realText(100) . '\\',
                'target' => $faker->realText(),
                'fileName' => $faker->word() . '.' . $faker->fileExtension(),
                'timestamp' => $faker->dateTime()->format(\DateTimeImmutable::ATOM),
                'userName' => $faker->userName(),
                'context' => $faker->word(),
            ],
        ];
    }

    /**
     * @dataProvider provideData
     */
    public function testSaveAndLoadReturnsSameData(array $data): void
    {
        $runId = bin2hex(random_bytes(5));
        $dto = new UpdateSegmentDTO(
            $data['taskGuid'],
            $data['segmentId'],
            $data['source'],
            $data['target'],
            $data['fileName'],
            $data['timestamp'],
            $data['userName'],
            $data['context'],
        );

        $this->repository->save($runId, $dto);

        $loaded = iterator_to_array($this->repository->getByTask($runId, $data['taskGuid']));

        self::assertCount(1, $loaded);
        $loadedDto = $loaded[0];
        self::assertEquals($data['taskGuid'], $loadedDto->taskGuid);
        self::assertEquals($data['segmentId'], $loadedDto->segmentId);
        self::assertEquals($data['source'], $loadedDto->source);
        self::assertEquals($data['target'], $loadedDto->target);
        self::assertEquals($data['fileName'], $loadedDto->fileName);
        self::assertEquals($data['timestamp'], $loadedDto->timestamp);
        self::assertEquals($data['userName'], $loadedDto->userName);
        self::assertEquals($data['context'], $loadedDto->context);
        self::assertEquals($dto, $loadedDto);
    }
}
