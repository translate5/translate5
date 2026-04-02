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

namespace MittagQI\Translate5\Test\Integration\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_T5Memory_Resource as LanguageResourceResource;
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\T5Memory\Api\Contract\FuzzyInterface;
use MittagQI\Translate5\T5Memory\Api\Response\FuzzySearchResponse;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\ContentProtection\QueryStringGuesser;
use MittagQI\Translate5\T5Memory\DTO\FuzzyMatchDTO;
use MittagQI\Translate5\T5Memory\FuzzySearchService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\ReorganizeService;
use MittagQI\Translate5\T5Memory\RetryService;
use MittagQI\Translate5\T5Memory\StatusService;
use MittagQI\Translate5\T5Memory\TagHandler\TagHandlerProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;
use ZfExtended_Logger;

/**
 * Integration tests for FuzzySearchService::query()
 *
 * Only the $api dependency (FuzzyInterface) is mocked — all other collaborators
 * are real instances so that the full processing pipeline is exercised.
 */
class FuzzySearchServiceQueryTest extends TestCase
{
    private const array TAGS_MAP = [
        '<tagOpen1>' => '<div class="open 67206373732d7374796c653d22666f6e742d66616d696c793a27417269616c273b666f6e742d666163653a27526567756c6172273b636f6c6f723a233030303030303b666f6e742d73697a653a32302e307074222063747970653d22782d63702d666f6e74222069643d22313235372d31392d312d3122 internal-tag ownttip"><span class="short" title="&lt;g css-style=&quot;font-family:\'Arial\';font-face:\'Regular\';color:#000000;font-size:20.0pt&quot; ctype=&quot;x-cp-font&quot; id=&quot;1257-19-1-1&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="1257-19-1-1" data-length="-1">&lt;g css-style=&quot;font-family:\'Arial\';font-face:\'Regular\';color:#000000;font-size:20.0pt&quot; ctype=&quot;x-cp-font&quot; id=&quot;1257-19-1-1&quot;&gt;</span></div>',
        '<tagClose1>' => '<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/g&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1257-19-1-1" data-length="-1">&lt;/g&gt;</span></div>',
        '<tagOpen2>' => '<div class="open 67206373732d7374796c653d22666f6e742d66616d696c793a27417269616c273b666f6e742d666163653a27526567756c6172273b636f6c6f723a233030303030303b666f6e742d73697a653a32302e3070743b666f6e743a7374796c653a6974616c696373222063747970653d22782d63702d666f6e74222069643d22313235372d31392d312d3222 internal-tag ownttip"><span class="short" title="&lt;g css-style=&quot;font-family:\'Arial\';font-face:\'Regular\';color:#000000;font-size:20.0pt;font:style:italics&quot; ctype=&quot;x-cp-font&quot; id=&quot;1257-19-1-2&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="1257-19-1-2" data-length="-1">&lt;g css-style=&quot;font-family:\'Arial\';font-face:\'Regular\';color:#000000;font-size:20.0pt;font:style:italics&quot; ctype=&quot;x-cp-font&quot; id=&quot;1257-19-1-2&quot;&gt;</span></div>',
        '<tagClose2>' => '<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/g&gt;">&lt;/2&gt;</span><span class="full" data-originalid="1257-19-1-2" data-length="-1">&lt;/g&gt;</span></div>',
        '<tagOpen3>' => '<div class="open 67206373732d7374796c653d22666f6e742d66616d696c793a27417269616c273b666f6e742d666163653a27526567756c6172273b636f6c6f723a233030303030303b666f6e742d73697a653a32302e307074222063747970653d22782d63702d666f6e74222069643d22313235372d31392d312d3322 internal-tag ownttip"><span class="short" title="&lt;g css-style=&quot;font-family:\'Arial\';font-face:\'Regular\';color:#000000;font-size:20.0pt&quot; ctype=&quot;x-cp-font&quot; id=&quot;1257-19-1-3&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="1257-19-1-3" data-length="-1">&lt;g css-style=&quot;font-family:\'Arial\';font-face:\'Regular\';color:#000000;font-size:20.0pt&quot; ctype=&quot;x-cp-font&quot; id=&quot;1257-19-1-3&quot;&gt;</span></div>',
        '<tagClose3>' => '<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/g&gt;">&lt;/3&gt;</span><span class="full" data-originalid="1257-19-1-3" data-length="-1">&lt;/g&gt;</span></div>',

        '<unknownTag1>' => '<div class="open 3c6270742069643d223722207269643d2234222f3e ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;bpt id=&quot;7&quot; rid=&quot;4&quot;/&gt;" class="short">&lt;7&gt;</span><span data-originalid="toignore-7" data-length="-1" class="full">AdditionalTagFromTM: &lt;bpt id=&quot;7&quot; rid=&quot;4&quot;/&gt;</span></div>',
        '<unknownTag2>' => '<div class="close 3c6570742069643d223822207269643d2235222f3e ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;ept id=&quot;8&quot; rid=&quot;5&quot;/&gt;" class="short">&lt;/8&gt;</span><span data-originalid="toignore-8" data-length="-1" class="full">AdditionalTagFromTM: &lt;ept id=&quot;8&quot; rid=&quot;5&quot;/&gt;</span></div>',
        '<unknownTag3>' => '<div class="open 3c6270742069643d223922207269643d2236222f3e ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;bpt id=&quot;9&quot; rid=&quot;6&quot;/&gt;" class="short">&lt;9&gt;</span><span data-originalid="toignore-9" data-length="-1" class="full">AdditionalTagFromTM: &lt;bpt id=&quot;9&quot; rid=&quot;6&quot;/&gt;</span></div>',
        '<unknownTag4>' => '<div class="close 3c6570742069643d22313022207269643d2237222f3e ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;ept id=&quot;10&quot; rid=&quot;7&quot;/&gt;" class="short">&lt;/10&gt;</span><span data-originalid="toignore-10" data-length="-1" class="full">AdditionalTagFromTM: &lt;ept id=&quot;10&quot; rid=&quot;7&quot;/&gt;</span></div>',
        '<unknownTag5>' => '<div class="open 3c6270742069643d22313122207269643d2238222f3e ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;bpt id=&quot;11&quot; rid=&quot;8&quot;/&gt;" class="short">&lt;11&gt;</span><span data-originalid="toignore-11" data-length="-1" class="full">AdditionalTagFromTM: &lt;bpt id=&quot;11&quot; rid=&quot;8&quot;/&gt;</span></div>',
        '<unknownTag6>' => '<div class="close 3c6570742069643d22313222207269643d2239222f3e ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;ept id=&quot;12&quot; rid=&quot;9&quot;/&gt;" class="short">&lt;/12&gt;</span><span data-originalid="toignore-12" data-length="-1" class="full">AdditionalTagFromTM: &lt;ept id=&quot;12&quot; rid=&quot;9&quot;/&gt;</span></div>',
    ];

    private MockObject&FuzzyInterface $api;

    private FuzzySearchService $service;

    private LanguageResource&MockObject $languageResource;

    private Zend_Config $config;

    protected function setUp(): void
    {
        $this->api = $this->createMock(FuzzyInterface::class);

        $reorganizeService = $this->createMock(ReorganizeService::class);
        $reorganizeService->method('needsReorganizing')->willReturn(false);
        $reorganizeService->method('isReorganizingAtTheMoment')->willReturn(false);

        $retryService = $this->createMock(RetryService::class);
        $retryService->method('canWaitLongTaskFinish')->willReturn(false);

        $logger = $this->createMock(ZfExtended_Logger::class);

        $persistenceService = $this->createMock(PersistenceService::class);
        $persistenceService->method('addTmPrefix')->willReturnArgument(0);

        $statusService = $this->createMock(StatusService::class);
        $statusService->method('getStatus')->willReturn(Status::AVAILABLE);

        $this->service = new FuzzySearchService(
            $reorganizeService,
            $retryService,
            $logger,
            QueryStringGuesser::create(),
            $this->api,
            $persistenceService,
            TagHandlerProvider::create(),
            SegmentLengthValidator::create(),
            $statusService,
        );

        $resource = $this->createConfiguredMock(LanguageResourceResource::class, [
            'getUrl' => 'http://t5memory.example.com',
            'getName' => 'T5Memory Test',
        ]);

        $this->languageResource = $this->createConfiguredMock(LanguageResource::class, [
            'isConversionStarted' => false,
            'getResource' => $resource,
            'getSourceLang' => 1,
            'getTargetLang' => 2,
            'getSourceLangCode' => 'de',
            'getTargetLangCode' => 'en',
            'getSpecificData' => [[
                'filename' => 'test-tm',
            ]],
        ]);
    }

    /**
     * @dataProvider queryProvider
     *
     * @param array<array<string, mixed>> $apiRows Rows to return from the mocked API.
     * @param string $queryString Query segment (may contain TAGS_MAP placeholders).
     * @param string $expectedTarget Expected target (may contain TAGS_MAP placeholders).
     * @param bool $guessUnrecognizedTags Value for guessUnrecognizedTagsFromTm config key.
     */
    public function testQuery(
        array $apiRows,
        string $queryString,
        int $segmentIndexToCheck,
        int $expectedMatchRate,
        string $expectedTarget,
        bool $guessUnrecognizedTags = true,
    ): void {
        $this->config = new Zend_Config([
            'runtimeOptions' => [
                'LanguageResources' => [
                    't5memory' => [
                        'guessUnrecognizedTagsFromTm' => $guessUnrecognizedTags,
                    ],
                ],
            ],
        ]);

        $this->api->method('fuzzyParallel')
            ->willReturn($this->makeApiResponse(array_map($this->makeMatchRow(...), $apiRows)));

        $results = $this->runQuery($this->replaceTags($queryString));

        self::assertSame($expectedMatchRate, $results[$segmentIndexToCheck]->matchrate);
        self::assertSame($this->replaceTags($expectedTarget), $results[$segmentIndexToCheck]->target);
    }

    public function queryProvider(): iterable
    {
        yield 'same rids, no guessing' => [
            'apiRows' => [
                [
                    'matchRate' => 100,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt einer E-Mail in einem <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>-Notizbuch ablegen?<ex id="6" rid="3"/>',
                    'target' => '<bx id="1" rid="1"/>Do you want to add the contents of an email to a <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>Notebook?<ex id="6" rid="3"/>',
                ],
            ],
            'queryString' => '<tagOpen1>Möchten Sie den Inhalt einer E-Mail in einem <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>-Notizbuch ablegen?<tagClose3>',
            0,
            'expectedMatchRate' => 100,
            'expectedTarget' => '<tagOpen1>Do you want to add the contents of an email to a <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>Notebook?<tagClose3>',
            'guessUnrecognizedTags' => false,
        ];

        yield 'different rids, no guessing' => [
            'apiRows' => [
                [
                    'matchRate' => 100,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt einer E-Mail in einem <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>-Notizbuch ablegen?<ex id="6" rid="3"/>',
                    'target' => '<bpt id="7" rid="4"/>Do you want to add the contents of an email to a <ept id="8" rid="5"/> <bpt id="9" rid="6"/>OneNote<ept id="10" rid="7"/> <bpt id="11" rid="8"/>Notebook?<ept id="12" rid="9"/>',
                ],
            ],
            'queryString' => '<tagOpen1>Möchten Sie den Inhalt einer E-Mail in einem <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>-Notizbuch ablegen?<tagClose3>',
            0,
            'expectedMatchRate' => 100,
            'expectedTarget' => '<unknownTag1>Do you want to add the contents of an email to a <unknownTag2> <unknownTag3>OneNote<unknownTag4> <unknownTag5>Notebook?<unknownTag6>',
            'guessUnrecognizedTags' => false,
        ];

        yield 'same rids, with guessing' => [
            'apiRows' => [
                [
                    'matchRate' => 100,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt einer E-Mail in einem <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>-Notizbuch ablegen?<ex id="6" rid="3"/>',
                    'target' => '<bpt id="1" rid="1"/>Do you want to add the contents of an email to a <ept id="2" rid="1"/> <bpt id="3" rid="2"/>OneNote<ept id="4" rid="2"/> <bpt id="5" rid="3"/>Notebook?<ept id="6" rid="3"/>',
                ],
            ],
            'queryString' => '<tagOpen1>Möchten Sie den Inhalt einer E-Mail in einem <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>-Notizbuch ablegen?<tagClose3>',
            0,
            'expectedMatchRate' => 100,
            'expectedTarget' => '<tagOpen1>Do you want to add the contents of an email to a <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>Notebook?<tagClose3>',
            'guessUnrecognizedTags' => true,
        ];

        yield 'different rids, with guessing' => [
            'apiRows' => [
                [
                    'matchRate' => 100,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt einer E-Mail in einem <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>-Notizbuch ablegen?<ex id="6" rid="3"/>',
                    'target' => '<bpt id="7" rid="4"/>Do you want to add the contents of an email to a <ept id="8" rid="5"/> <bpt id="9" rid="6"/>OneNote<ept id="10" rid="7"/> <bpt id="11" rid="8"/>Notebook?<ept id="12" rid="9"/>',
                ],
            ],
            'queryString' => '<tagOpen1>Möchten Sie den Inhalt einer E-Mail in einem <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>-Notizbuch ablegen?<tagClose3>',
            0,
            'expectedMatchRate' => 100,
            'expectedTarget' => '<tagOpen1>Do you want to add the contents of an email to a <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>Notebook?<tagClose3>',
            'guessUnrecognizedTags' => true,
        ];

        yield 'multiple api results, different rids, with guessing' => [
            'apiRows' => [
                [
                    'matchRate' => 100,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt einer E-Mail in einem <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>-Notizbuch ablegen?<ex id="6" rid="3"/>',
                    'target' => '<bpt id="7" rid="4"/>Do you want to add the contents of an email to a <ept id="8" rid="5"/> <bpt id="9" rid="6"/>OneNote<ept id="10" rid="7"/> <bpt id="11" rid="8"/>Notebook?<ept id="12" rid="9"/>',
                ],
                [
                    'matchRate' => 20,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt der Folien übernehmen, das Design aber an die aktuelle Präsentation anpassen? Deaktivieren Sie dafür die Option <ex id="2" rid="1"/> <bx id="3" rid="2"/>Use source formatting<ex id="4" rid="2"/> <bx id="5" rid="3"/>.<ex id="6" rid="3"/>',
                    'target' => '<bpt id="7" rid="4"/>Do you want to keep the content of the slides but adapt the design to the current presentation? Then deactivate the option <ex id="2" rid="1"/> <bpt id="8" rid="5"/>Use source formatting<ex id="4" rid="2"/> <bpt id="9" rid="6"/>.<ex id="6" rid="3"/>',
                ],
            ],
            'queryString' => '<tagOpen1>Möchten Sie den Inhalt einer E-Mail in einem <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>-Notizbuch ablegen?<tagClose3>',
            0,
            'expectedMatchRate' => 100,
            'expectedTarget' => '<tagOpen1>Do you want to add the contents of an email to a <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>Notebook?<tagClose3>',
            'guessUnrecognizedTags' => true,
        ];

        yield 'multiple api results, different rids, with guessing, different ordering' => [
            'apiRows' => [
                [
                    'matchRate' => 19,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt der Folien übernehmen, das Design aber an die aktuelle Präsentation anpassen? Deaktivieren Sie dafür die Option <ex id="2" rid="1"/> <bx id="3" rid="2"/>Use source formatting<ex id="4" rid="2"/> <bx id="5" rid="3"/>.<ex id="6" rid="3"/>',
                    'target' => '<bpt id="7" rid="4"/>Do you want to keep the content of the slides but adapt the design to the current presentation? Then deactivate the option <ex id="2" rid="1"/> <bpt id="8" rid="5"/>Use source formatting<ex id="4" rid="2"/> <bpt id="9" rid="6"/>.<ex id="6" rid="3"/>',
                ],
                [
                    'matchRate' => 100,
                    'source' => '<bx id="1" rid="1"/>Möchten Sie den Inhalt einer E-Mail in einem <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>-Notizbuch ablegen?<ex id="6" rid="3"/>',
                    'target' => '<bpt id="7" rid="4"/>Do you want to add the contents of an email to a <ept id="8" rid="5"/> <bpt id="9" rid="6"/>OneNote<ept id="10" rid="7"/> <bpt id="11" rid="8"/>Notebook?<ept id="12" rid="9"/>',
                ],
            ],
            'queryString' => '<tagOpen1>Möchten Sie den Inhalt einer E-Mail in einem <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>-Notizbuch ablegen?<tagClose3>',
            1,
            'expectedMatchRate' => 100,
            'expectedTarget' => '<tagOpen1>Do you want to add the contents of an email to a <tagClose1> <tagOpen2>OneNote<tagClose2> <tagOpen3>Notebook?<tagClose3>',
            'guessUnrecognizedTags' => true,
        ];
    }

    #region Helper methods

    /**
     * Builds a minimal FuzzySearchResponse with the given result rows.
     *
     * @param array<array<string, mixed>> $results Raw result rows as returned by the API.
     */
    private function makeApiResponse(array $results, int $statusCode = 200): array
    {
        return [
            'responses' => [
                'test-tm' => new FuzzySearchResponse(
                    'test-tm',
                    [
                        'results' => $results,
                    ],
                    null,
                    $statusCode,
                ),
            ],
            'failures' => [],
        ];
    }

    /**
     * Builds one minimal result row.
     *
     * @param array<string, mixed> $overrides Any fields to override or extend.
     */
    private function makeMatchRow(array $overrides = []): array
    {
        return array_merge([
            'source' => 'Source text',
            'target' => 'Target text',
            'segmentId' => 1,
            'documentName' => 'doc.xlf',
            'sourceLang' => 'de',
            'targetLang' => 'en',
            'type' => 'Manual',
            'author' => 'tester',
            'timestamp' => '20240101T000000Z',
            'context' => '',
            'additionalInfo' => '',
            'internalKey' => random_int(1, 10) . ':' . random_int(1, 1000),
            'matchType' => 'Fuzzy',
            'matchRate' => 80,
        ], $overrides);
    }

    private function replaceTags(string $text): string
    {
        return str_replace(array_keys(self::TAGS_MAP), array_values(self::TAGS_MAP), $text);
    }

    /**
     * Runs FuzzySearchService::query() with default parameters and collects all yielded results.
     *
     * @return FuzzyMatchDTO[]
     */
    private function runQuery(
        string $queryString = 'Source text',
        bool $pretranslation = false,
        bool $isInternalFuzzy = false,
    ): array {
        $queryString = $this->replaceTags($queryString);

        $iterator = $this->service->query(
            $this->languageResource,
            $queryString,
            '',
            '',
            fn (int $matchRate, array $metaData, string $fileName): int => $matchRate,
            $this->config,
            $isInternalFuzzy,
            $pretranslation,
        );

        return iterator_to_array($iterator, false);
    }

    #endregion
}
