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
use MittagQI\Translate5\T5Memory\TmxFilter\SameTuvFilter;
use MittagQI\Translate5\T5Memory\UpdateRetryService;
use MittagQI\Translate5\Test\Fixtures\LanguageResourceFixtures;
use PHPUnit\Framework\TestCase;

class ManualReorganizeServiceTest extends TestCase
{
    private const TM_NAME = 'ManualReorganizeServiceTest';

    private static ?string $afterReorganizeTmx = null;

    private static ?string $beforeReorganizeTmx = null;

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
        if (null !== self::$afterReorganizeTmx) {
            unlink(self::$afterReorganizeTmx);
        }

        if (null !== self::$beforeReorganizeTmx) {
            unlink(self::$beforeReorganizeTmx);
        }

        $this->deleteTm(self::TM_NAME);

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
        $sameTuFilter = SameTuvFilter::create();

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

        $tempDir = sys_get_temp_dir() . '/test_cleanup_' . uniqid();
        mkdir($tempDir);

        $url = $this->languageResource->getResource()->getUrl();

        self::$beforeReorganizeTmx = $tempDir . '/beforeReorganizeTmx.tmx';
        $tmxStream = $this->api->downloadTmx(
            $url,
            $this->persistenceService->addTmPrefix($tmName),
            100
        );
        foreach ($tmxStream as $stream) {
            file_put_contents(self::$beforeReorganizeTmx, $stream->getContents());
        }

        $sameTuFilter->filter(self::$beforeReorganizeTmx);

        $beforeReorganizeTmx = file_get_contents(self::$beforeReorganizeTmx);

        $countBefore = preg_match_all('#<tu #', $beforeReorganizeTmx);

        try {
            $reorganize->reorganizeTm(
                $this->languageResource,
                $tmName,
                new ReorganizeOptions(false)
            );
        } catch (ReorganizeException $e) {
            self::fail($e->getMessage());
        }

        $memories = $this->api->getMemories($url)->memories;

        self::assertNotEmpty($memories, 'Error on t5memory side');

        $tmFullName = $persistenceService->addTmPrefix($tmName);

        foreach ($memories as $memory) {
            if ($memory->name === $tmFullName) {
                self::fail('Memory was not deleted');
            }
        }

        $lrMemories = $this->languageResource->getSpecificData('memories', true);

        self::assertCount(1, $lrMemories, 'Memory was not replaced');

        $currentTm = $lrMemories[0]['filename'];

        self::$afterReorganizeTmx = $tempDir . '/afterReorganizeTmx.tmx';
        $tmxStream = $this->api->downloadTmx(
            $url,
            $this->persistenceService->addTmPrefix($currentTm),
            100
        );
        foreach ($tmxStream as $stream) {
            file_put_contents(self::$afterReorganizeTmx, $stream->getContents());
        }

        $sameTuFilter->filter(self::$afterReorganizeTmx);

        $afterReorganizeTmx = file_get_contents(self::$afterReorganizeTmx);
        self::assertStringContainsString(
            '<seg>Eine Segment</seg>',
            $afterReorganizeTmx,
            'New segment is missing after reorganize'
        );

        $countAfterReorganize = preg_match_all('#<tu #', $afterReorganizeTmx);

        self::assertSame($countBefore, $countAfterReorganize, 'Some segments are missing after reorganize');

        self::assertSame(
            $persistenceService->addTmPrefix($currentTm),
            $persistenceService->addTmPrefix($lrMemories[0]['filename']),
            'New memory name is not as expected'
        );
    }

    private function deleteTm(string $tmName): void
    {
        $this->api->deleteTm(
            $this->languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName)
        );
    }
}
