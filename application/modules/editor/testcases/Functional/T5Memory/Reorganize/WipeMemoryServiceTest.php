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

use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\WipeMemoryService;
use MittagQI\Translate5\Test\Fixtures\LanguageResourceFixtures;
use PHPUnit\Framework\TestCase;

class WipeMemoryServiceTest extends TestCase
{
    private const TM_NAME = 'WipeMemoryServiceTest';

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
        $this->deleteTm(self::TM_NAME);

        foreach ($this->languageResource->getSpecificData('memories', true) as $memory) {
            $this->deleteTm($memory['filename']);
        }

        $this->languageResource->delete();
    }

    public function test(): void
    {
        $lrFixture = LanguageResourceFixtures::create();
        $this->languageResource = $lrFixture->createT5MemoryLanguageResource('de', 'en');

        $tmName = self::TM_NAME;

        $createMemoryService = CreateMemoryService::create();

        $wipeService = WipeMemoryService::create();

        $this->persistenceService->addMemoryToLanguageResource($this->languageResource, $tmName);

        $createMemoryService->createEmptyMemory(
            $this->languageResource,
            $tmName,
        );

        $newMemory = $wipeService->wipeMemory($this->languageResource, $tmName);

        $url = $this->languageResource->getResource()->getUrl();

        $memories = $this->api->getMemories($url)->memories;

        self::assertNotEmpty($memories, 'Error on t5memory side');

        $tmFullName = $this->persistenceService->addTmPrefix($tmName);

        foreach ($memories as $memory) {
            if ($memory->name === $tmFullName) {
                self::fail('Memory was not deleted');
            }
        }

        $lrMemories = $this->languageResource->getSpecificData('memories', true);

        self::assertCount(1, $lrMemories, 'Memory was not replaced');

        self::assertSame(
            $this->persistenceService->addTmPrefix($newMemory),
            $this->persistenceService->addTmPrefix($lrMemories[0]['filename']),
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
