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

namespace MittagQI\Translate5\Test\Functional\T5Memory\Reorganize;

use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Exception\ReorganizeException;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\Reorganize\ManualReorganizeService;
use MittagQI\Translate5\T5Memory\UpdateRetryService;
use MittagQI\Translate5\Test\Fixtures\LanguageResourceFixtures;
use PHPUnit\Framework\TestCase;

class ManualReorganizeServiceTest extends TestCase
{
    private const TM_NAME = 'ManualReorganizeServiceTest';

    private ?string $backupBeforeTm = null;

    private ?string $backupAfterTm = null;

    private ?string $beforeFlushTmx = null;

    private ?string $afterFlushTmx = null;

    private ?string $afterReorganizeTmx = null;
    
    private \editor_Models_LanguageResources_LanguageResource $languageResource;

    private PersistenceService $persistenceService;

    private T5MemoryApi $api;

    public function setUp(): void
    {
        $this->api = T5MemoryApi::create();
        $this->persistenceService = PersistenceService::create();
    }

    public function tearDown(): void
    {
        dump('Tear down ManualReorganizeServiceTest');
        if (null !== $this->beforeFlushTmx) {
            unlink($this->beforeFlushTmx);
        }
        if (null !== $this->afterFlushTmx) {
            unlink($this->afterFlushTmx);
        }
        if (null !== $this->afterReorganizeTmx) {
            unlink($this->afterReorganizeTmx);
        }

        $this->deleteTm(self::TM_NAME);

        if (null !== $this->backupBeforeTm) {
            $this->deleteTm($this->backupBeforeTm);
        }
        if (null !== $this->backupAfterTm) {
            $this->deleteTm($this->backupAfterTm);
        }

        foreach ($this->languageResource->getSpecificData('memories', true) as $memory) {
            $this->deleteTm($memory['filename']);
        }

        $this->languageResource->delete();
    }

    public function testReorganizeTm(): void
    {
        $lrFixture = LanguageResourceFixtures::create();
        $this->languageResource = $lrFixture->createT5MemoryLanguageResource('de', 'en');

        $tmName = self::TM_NAME;

        $persistenceService = PersistenceService::create();
        $createMemoryService = CreateMemoryService::create();
        $updateService = UpdateRetryService::create();
        $reorganize = ManualReorganizeService::create();
        $importService = ImportService::create();

        $persistenceService->addMemoryToLanguageResource($this->languageResource, $tmName);

        $createMemoryService->createEmptyMemory(
            $this->languageResource,
            $tmName,
        );
        $importService->importTmxInMemory(
            $this->languageResource,
            __DIR__ . '/ManualReorganizeServiceTest/test.tmx',
            $tmName,
            new ImportOptions(StripFramingTags::None),
        );

        $updateService->updateWithRetryInMemory(
            $this->languageResource,
            $tmName,
            new UpdateSegmentDTO(
                'Eine Segment',
                'One segment',
                'file.pdf',
                time(),
                'Some Editor',
                '',
            ),
            new UpdateOptions(
                useSegmentTimestamp: true,
                saveToDisk: false,
                saveDifferentTargetsForSameSource: false,
                recheckOnUpdate: false,
            ),
            \Zend_Registry::get('config'),
        );

        try {
            $reorganize->reorganizeTm(
                $this->languageResource,
                $tmName,
                new ReorganizeOptions(false)
            );
        } catch (ReorganizeException $e) {
            self::fail($e->getMessage());
        }

        $url = $this->languageResource->getResource()->getUrl();

        $memories = $this->api->getMemories($url)->memories;

        self::assertNotEmpty($memories, 'Error on t5memory side');

        $tmFullName = $persistenceService->addTmPrefix($tmName);

        foreach ($memories as $memory) {
            if ($memory->name === $tmFullName) {
                self::fail('Memory was not deleted');
            }

            if (str_contains($memory->name, "$tmFullName.reorganise.before-flush")) {
                $this->backupBeforeTm = $memory->name;

                continue;
            }

            if (str_contains($memory->name, "$tmFullName.reorganise.after-flush")) {
                $this->backupAfterTm = $memory->name;
            }
        }

        self::assertNotNull($this->backupBeforeTm, 'TM before flush was not saved');
        self::assertNotNull($this->backupAfterTm, 'TM after flush was not saved');

        $tempDir = sys_get_temp_dir() . '/test_cleanup_' . uniqid();
        mkdir($tempDir);

        $this->beforeFlushTmx = $tempDir . '/beforeFlushTmx.tmx';
        $tmxStream = $this->api->downloadTmx($url, $this->backupBeforeTm, 100);
        foreach ($tmxStream as $stream) {
            file_put_contents($this->beforeFlushTmx, $stream->getContents());
        }

        self::assertNotNull($this->beforeFlushTmx, 'Before flush backup TM has problems');
        self::assertFileExists($this->beforeFlushTmx);

        self::assertStringNotContainsString(
            '<seg>Eine Segment</seg>',
            file_get_contents($this->beforeFlushTmx)
        );

        $this->afterFlushTmx = $tempDir . '/afterFlushTmx.tmx';
        $tmxStream = $this->api->downloadTmx($url, $this->backupAfterTm, 100);
        foreach ($tmxStream as $stream) {
            file_put_contents($this->afterFlushTmx, $stream->getContents());
        }

        self::assertNotNull($this->afterFlushTmx, 'After flush backup TM has problems');
        self::assertFileExists($this->afterFlushTmx);

        $afterFlushTmx = file_get_contents($this->afterFlushTmx);
        self::assertStringContainsString(
            '<seg>Eine Segment</seg>',
            $afterFlushTmx,
            'New segment is missing in backup'
        );

        $lrMemories = $this->languageResource->getSpecificData('memories', true);

        self::assertCount(1, $lrMemories, 'Memory was not replaced');

        $currentTm = $lrMemories[0]['filename'];

        $this->afterReorganizeTmx = $tempDir . '/afterReorganizeTmx.tmx';
        $tmxStream = $this->api->downloadTmx($url, $currentTm, 100);
        foreach ($tmxStream as $stream) {
            file_put_contents($this->afterReorganizeTmx, $stream->getContents());
        }

        $afterReorganizeTmx = file_get_contents($this->afterReorganizeTmx);
        self::assertStringContainsString(
            '<seg>Eine Segment</seg>',
            $afterReorganizeTmx,
            'New segment is missing after reorganize'
        );

        $countInBackup = preg_match_all('#<tu #', $afterFlushTmx);
        $countAfterReorganize = preg_match_all('#<tu #', $afterReorganizeTmx);

        self::assertSame($countInBackup, $countAfterReorganize, 'Some segments are missing after reorganize');
    }

    private function deleteTm(string $tmName): void
    {
        $this->api->deleteTm(
            $this->languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName)
        );
    }
}
