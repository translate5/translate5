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

namespace MittagQI\Translate5\Test\Functional\ContentProtection\T5memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourceLanguages;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\Api\Contract\TmxImportPreprocessorInterface;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\ContentProtection\QueryStringGuesser;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\FlushMemoryService;
use MittagQI\Translate5\T5Memory\FuzzySearchService;
use MittagQI\Translate5\T5Memory\Import\CutOffTmx;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\ReorganizeService;
use MittagQI\Translate5\T5Memory\RetryService;
use MittagQI\Translate5\T5Memory\TagHandlerProvider;
use MittagQI\Translate5\T5Memory\WipeMemoryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;

class FuzzySearchServiceTest extends TestCase
{
    private MockObject & ReorganizeService $reorganizeService;

    private MockObject & TagHandlerProvider $tagHandlerProvider;

    private ZfExtended_Logger $logger;

    private LanguageResource $languageResource;

    private ImportService $importService;

    private T5MemoryApi $t5MemoryApi;

    private string $testFile;

    private FuzzySearchService $fuzzySearchService;

    public function setUp(): void
    {
        $this->reorganizeService = $this->createMock(ReorganizeService::class);
        $this->tagHandlerProvider = $this->createMock(TagHandlerProvider::class);
        $this->logger = Zend_Registry::get('logger')->cloneMe('test.FuzzySearchServiceTest');
        $this->t5MemoryApi = T5MemoryApi::create();

        $this->reorganizeService->method('needsReorganizing')->willReturn(false);

        $languageRepository = LanguageRepository::create();
        $de = $languageRepository->findByRfc5646('de');
        $en = $languageRepository->findByRfc5646('en');

        $this->languageResource = new LanguageResource();
        $this->languageResource->setLangResUuid(\ZfExtended_Utils::uuid());
        $this->languageResource->setName('FuzzySearchServiceTest');
        $this->languageResource->setServiceType('editor_Services_OpenTM2');
        $this->languageResource->setResourceId('editor_Services_OpenTM2_1');
        $this->languageResource->addSpecificData('memories', []);
        $this->languageResource->save();

        $lrLang = new LanguageResourceLanguages();
        $lrLang->setSourceLangCode($de->getRfc5646());
        $lrLang->setTargetLangCode($en->getRfc5646());
        $lrLang->setSourceLang((int) $de->getId());
        $lrLang->setTargetLang((int) $en->getId());
        $lrLang->setLanguageResourceId($this->languageResource->getId());
        $lrLang->save();

        $this->importService = $this->getImportService(
            $this->logger,
            $this->t5MemoryApi,
        );

        $this->tagHandlerProvider->method('getTagHandler')
            ->willReturnCallback(
                function (int $sourceLang, int $targetLang, Zend_Config $config) {
                    $handler = new \editor_Services_Connector_TagHandler_T5MemoryXliff([
                        'gTagPairing' => false,
                    ]);
                    $handler->setLanguages($sourceLang, $targetLang);

                    return $handler;
                }
            );

        $this->fuzzySearchService = new FuzzySearchService(
            $this->reorganizeService,
            RetryService::create(),
            $this->logger,
            QueryStringGuesser::create(),
            $this->t5MemoryApi,
            PersistenceService::create(),
            $this->tagHandlerProvider,
            SegmentLengthValidator::create(),
        );
    }

    public function tearDown(): void
    {
        foreach ($this->languageResource->getSpecificData('memories', parseAsArray: true) ?? [] as $memory) {
            $this->t5MemoryApi->deleteTm(
                $this->languageResource->getResource()->getUrl(),
                $memory['filename'],
            );
        }

        $this->languageResource->delete();

        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    /**
     * @dataProvider cases
     */
    public function test(
        string $query,
        string $tmx,
        bool $pretranslation,
        array $expectedMaxRateResult,
        array $otherMatches
    ): void {
        $this->testFile = __DIR__ . '/FuzzySearchServiceTest/' . bin2hex(random_bytes(3)) . '.tmx';
        copy($tmx, $this->testFile);

        $this->importService->importTmx(
            $this->languageResource,
            [$this->testFile],
            new ImportOptions(
                StripFramingTags::None,
                false,
                false,
            )
        );

        $matches = $this->fuzzySearchService->query(
            $this->languageResource,
            $query,
            '',
            '',
            fn ($matchRate, $metaData, $filename) => $matchRate,
            \Zend_Registry::get('config'),
            false,
            $pretranslation,
        );

        $maxMatchRate = 0;
        $maxMatchRateResult = null;
        $timestamp = 0;
        $allMatches = [];

        foreach ($matches as $match) {
            $allMatches[] = $match;

            if (
                isset($match->matchrate) && $match->matchrate > $maxMatchRate
                && (
                    ! isset($match->timestamp)
                    || $match->timestamp >= $timestamp
                )
            ) {
                $timestamp = $match->timestamp ?? 0;
                $maxMatchRate = $match->matchrate;
                $maxMatchRateResult = $match;
            }
        }

        self::assertNotNull($maxMatchRateResult, 'No result found');
        self::assertSame($expectedMaxRateResult['matchRate'], $maxMatchRateResult->matchrate);
        self::assertSame($expectedMaxRateResult['source'], $maxMatchRateResult->source);
        self::assertSame($expectedMaxRateResult['rawTarget'], $maxMatchRateResult->rawTarget);
        foreach ($expectedMaxRateResult['metaData'] as $key => $value) {
            foreach ($maxMatchRateResult->metaData as $metaData) {
                if ($metaData->name === $key) {
                    self::assertSame($value, $metaData->value);

                    continue 2;
                }
            }

            self::fail("Meta data key '$key' not found in max match rate result");
        }

        if ($pretranslation) {
            self::assertCount(1, $allMatches, 'With pretranslation only one match should be returned');

            return;
        }

        foreach ($otherMatches as $index => $match) {
            $matchId = $index + 1;

            self::assertSame($match['matchRate'], $allMatches[$matchId]->matchrate);
            self::assertSame($match['rawTarget'], $allMatches[$matchId]->rawTarget);

            foreach ($match['metaData'] as $key => $value) {
                foreach ($allMatches[$matchId]->metaData as $metaData) {
                    if ($metaData->name === $key) {
                        self::assertSame($value, $metaData->value);

                        continue 2;
                    }
                }

                self::fail("Meta data key '$key' not found in match [$matchId] result");
            }
        }
    }

    public function cases(): iterable
    {
        yield 'Very similar sentences' => [
            'query' => 'Unser schönes <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d22616e79206e756d62657220286e6f742070617274206f662068797068616e74656420636f6d706f756e64292220736f757263653d2234222069736f3d223422207461726765743d2234222072656765783d22303965494e7443316a4e5857314c42586a4e474e547453746374534e4f727a6b384c624465773633484a353265453673706a3441222f number internal-tag ownttip"><span title="&lt;0/&gt; CP: any number (not part of hyphanted compound)" class="short">&lt;0/&gt;</span><span data-originalid="number" data-length="1" data-source="4" data-target="4" class="full"></span></div> Segment',
            'tmx' => __DIR__ . '/FuzzySearchServiceTest/fuzzy-search.tmx',
            'pretranslation' => false,
            'maxRateResult' => [
                'matchRate' => 100,
                'source' => 'Unser schönes 4 Segment',
                'rawTarget' => 'Our nice 4 segment',
                'metaData' => [
                    'timestamp' => '2016-03-23 16:24:28 CET',
                    'Guessed' => 'Some content was unprotected to get a better match',
                ],
            ],
            'otherMatches' => [
                [
                    'matchRate' => 100,
                    'rawTarget' => 'Our nice 4 segment',
                    'metaData' => [
                        'timestamp' => '2016-03-23 16:24:28 CET',
                    ],
                ],
                [
                    'matchRate' => 97,
                    'rawTarget' => 'Our nice 4 segment<ph id="2"/>',
                    'metaData' => [
                        'timestamp' => '2017-03-23 16:24:28 CET',
                    ],
                ],
            ],
        ];

        yield 'Very similar sentences (pretranslation)' => [
            'query' => 'Unser schönes <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d22616e79206e756d62657220286e6f742070617274206f662068797068616e74656420636f6d706f756e64292220736f757263653d2234222069736f3d223422207461726765743d2234222072656765783d22303965494e7443316a4e5857314c42586a4e474e547453746374534e4f727a6b384c624465773633484a353265453673706a3441222f number internal-tag ownttip"><span title="&lt;0/&gt; CP: any number (not part of hyphanted compound)" class="short">&lt;0/&gt;</span><span data-originalid="number" data-length="1" data-source="4" data-target="4" class="full"></span></div> Segment',
            'tmx' => __DIR__ . '/FuzzySearchServiceTest/fuzzy-search.tmx',
            'pretranslation' => true,
            'maxRateResult' => [
                'matchRate' => 100,
                'source' => 'Unser schönes 4 Segment',
                'rawTarget' => 'Our nice 4 segment',
                'metaData' => [
                    'timestamp' => '2016-03-23 16:24:28 CET',
                    'Guessed' => 'Some content was unprotected to get a better match',
                ],
            ],
            'otherMatches' => [],
        ];
    }

    private function getImportService(
        ZfExtended_Logger $logger,
        T5MemoryApi $t5MemoryApi,
    ): ImportService {
        $tmxImportPreprocessor = new class() implements TmxImportPreprocessorInterface {
            public function process(
                string $filepath,
                int $sourceLangId,
                int $targetLangId,
                ImportOptions $importOptions,
            ): string {
                return $filepath;
            }
        };

        return new ImportService(
            Zend_Registry::get('config'),
            $logger,
            $t5MemoryApi,
            $tmxImportPreprocessor,
            PersistenceService::create(),
            FlushMemoryService::create(),
            CreateMemoryService::create(),
            RetryService::create(),
            WipeMemoryService::create(),
            CutOffTmx::create(),
        );
    }
}
