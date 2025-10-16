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

namespace MittagQI\Translate5\Plugins\TMMaintenance\test\Functional\Service;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\Plugins\TMMaintenance\Service\TuBatchDeleteService;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\TmxFilter\SameTuvFilter;
use MittagQI\Translate5\Test\Fixtures\LanguageResourceFixtures;
use PHPUnit\Framework\TestCase;

class TuBatchDeleteServiceTest extends TestCase
{
    private const TMX_FILE = __DIR__ . '/TuBatchDeleteServiceTest/test.tmx';

    private static string $testFile = '';

    private static string $actualFile = '';

    private LanguageResource $languageResource;

    public function setUp(): void
    {
        $fixtures = LanguageResourceFixtures::create();

        $this->languageResource = $fixtures->createT5MemoryLanguageResource('de', 'en');

        self::$testFile = sys_get_temp_dir() . '/test_' . bin2hex(random_bytes(8)) . '.tmx';
        copy(self::TMX_FILE, self::$testFile);
    }

    public function tearDown(): void
    {
        if (file_exists(self::$testFile)) {
            unlink(self::$testFile);
        }

        if (file_exists(self::$actualFile)) {
            unlink(self::$actualFile);
        }

        $this->languageResource->delete();
    }

    public function test(): void
    {
        $createMemoryService = CreateMemoryService::create();
        $persistenceService = PersistenceService::create();
        $importService = ImportService::create();
        $exportService = ExportService::create();
        $sameTuvFilter = SameTuvFilter::create();

        $memory = $createMemoryService->createEmptyMemoryWithRetry($this->languageResource);
        $persistenceService->addMemoryToLanguageResource($this->languageResource, $memory);

        $importService->importTmx(
            $this->languageResource,
            [self::$testFile],
            new ImportOptions(
                StripFramingTags::None,
                forceLongWait: true,
            ),
        );

        $service = TuBatchDeleteService::create();

        $service->deleteBatch(
            $this->languageResource,
            new SearchDTO(
                source: '',
                sourceMode: '',
                target: '',
                targetMode: '',
                sourceLanguage: '',
                targetLanguage: '',
                author: 'OTHER MANAGER',
                authorMode: 'exact',
                creationDateFrom: 0,
                creationDateTo: 0,
                additionalInfo: '',
                additionalInfoMode: '',
                document: '',
                documentMode: '',
                context: '',
                contextMode: '',
                onlyCount: false,
                caseSensitive: true,
            ),
        );

        self::$actualFile = $exportService->export($this->languageResource, TmFileExtension::TMX);

        self::assertFileExists(self::$actualFile);

        $sameTuvFilter->filter(self::$actualFile);

        $tmx = file_get_contents(self::$actualFile);
        $tmx = preg_replace('#<header.+(</header>|/>)\s?#', '', $tmx);
        $tmx = preg_replace('#tuid="(\d+)"#', 'tuid="_1_"', $tmx);
        $tmx = preg_replace(
            '#[^\r\n] *(<prop type="tmgr:segId">(\d+)</prop>|<prop type="t5:InternalKey">(\d+):(\d+)</prop>)\s?#',
            '',
            $tmx
        );

        self::assertSame(
            file_get_contents(__DIR__ . '/TuBatchDeleteServiceTest/expected_author_filtered.tmx'),
            $tmx,
            'The filtered TMX file is not as expected.',
        );
    }
}
