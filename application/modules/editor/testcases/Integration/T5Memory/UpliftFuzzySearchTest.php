<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\T5Memory;

use MittagQI\Translate5\T5Memory\Api\Contract\FuzzyInterface;
use MittagQI\Translate5\T5Memory\Api\Response\FuzzySearchResponse;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\ContentProtection\QueryStringGuesser;
use MittagQI\Translate5\T5Memory\FuzzySearchService;
use MittagQI\Translate5\T5Memory\PersistenceService;
use MittagQI\Translate5\T5Memory\ReorganizeService;
use MittagQI\Translate5\T5Memory\RetryService;
use MittagQI\Translate5\T5Memory\TagHandlerProvider;
use PHPUnit\Framework\TestCase;

class UpliftFuzzySearchTest extends TestCase
{
    public function testFuzzySearch(): void
    {
        $reorganizeService = $this->createMock(ReorganizeService::class);
        $retryService = $this->createMock(RetryService::class);
        $logger = $this->createMock(\ZfExtended_Logger::class);
        $guesser = QueryStringGuesser::create();
        $api = $this->createMock(FuzzyInterface::class);
        $persistenceService = $this->createMock(PersistenceService::class);
        $tagHandlerProvider = TagHandlerProvider::create();
        $segmentLengthValidator = SegmentLengthValidator::create();

        $api->method('fuzzyParallel')->willReturnOnConsecutiveCalls(
            [
                'responses' => [
                    't5memory_test' => new FuzzySearchResponse(
                        [
                            'results' => [
                                [
                                    'source' => '2023-09-15 and <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>',
                                    'target' => '2023-09-15 and <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>',
                                    'segmentId' => 147995,
                                    'documentName' => 'none',
                                    'sourceLang' => 'de-DE',
                                    'targetLang' => 'EN-GB',
                                    'type' => 'Manual',
                                    'author' => 'UNKNOWN',
                                    'timestamp' => '20180614T141737Z',
                                    'context' => '',
                                    'additionalInfo' => '',
                                    'internalKey' => '139053:1',
                                    'matchRate' => 80,
                                ],
                            ],
                        ],
                        null,
                        200,
                    ),
                ],
                'failures' => [],
            ],
            [
                'responses' => [
                    't5memory_test' => new FuzzySearchResponse(
                        [
                            'results' => [
                                [
                                    'source' => '2023-09-15 and <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>',
                                    'target' => '2023-09-15 and <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>',
                                    'segmentId' => 147995,
                                    'documentName' => 'none',
                                    'sourceLang' => 'de-DE',
                                    'targetLang' => 'EN-GB',
                                    'type' => 'Manual',
                                    'author' => 'UNKNOWN',
                                    'timestamp' => '20180614T141737Z',
                                    'context' => '',
                                    'additionalInfo' => '',
                                    'internalKey' => '139053:1',
                                    'matchRate' => 100,
                                ],
                            ],
                        ],
                        null,
                        200,
                    ),
                ],
                'failures' => [],
            ],
        );

        $fuzzySearchService = new FuzzySearchService(
            $reorganizeService,
            $retryService,
            $logger,
            $guesser,
            $api,
            $persistenceService,
            $tagHandlerProvider,
            $segmentLengthValidator,
        );

        $resource = $this->createConfiguredMock(\editor_Models_LanguageResources_Resource::class, [
            'getUrl' => 'http://example.com',
        ]);

        $languageResource = $this->createConfiguredMock(\editor_Models_LanguageResources_LanguageResource::class, [
            'isConversionStarted' => false,
            'getResource' => $resource,
            'getSourceLang' => 1,
            'getTargetLang' => 2,
            'getSourceLangCode' => 'de',
            'getTargetLangCode' => 'en',
            'getSpecificData' => [['filename' => 't5memory_test']],
        ]);

        $segment = <<<HTML
<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default Y-m-d" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>
HTML;

        $config = new \Zend_Config(['runtimeOptions' => ['LanguageResources' => []]]);

        $result = $fuzzySearchService->query(
            $languageResource,
            $segment,
            '',
            '',
            fn ($matchRate, $metaData, $filename) => $matchRate,
            $config,
            false,
            true,
        );

        foreach ($result as $match) {
            self::assertSame(
                100,
                $match->matchrate
            );
            self::assertSame(
                '2023-09-15 and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>',
                $match->source
            );
            self::assertSame(
                '2023-09-15 and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>',
                $match->target
            );

            $guessed = false;

            foreach ($match->metaData as $metaData) {
                if ($metaData->name === 'Guessed') {
                    $guessed = true;
                }
            }

            self::assertTrue($guessed);
        }
    }
}