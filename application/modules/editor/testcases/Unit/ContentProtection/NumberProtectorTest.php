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

namespace MittagQI\Translate5\Test\Unit\ContentProtection;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IPAddressProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\NumberProtectorInterface;
use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTag;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class NumberProtectorTest extends TestCase
{
    public function testHasEntityToProtect(): void
    {
        /** @var NumberProtectorInterface $processor */
        $processor = new class() implements NumberProtectorInterface {
            public static function getType(): string
            {
                return 'test';
            }

            public function protect(
                string $number,
                ContentProtectionDto $protectionDto,
                ?editor_Models_Languages $sourceLang,
                ?editor_Models_Languages $targetLang
            ): string {
                return 'test';
            }

            public function validateFormat(string $format): bool
            {
                return true;
            }

            public function getFormatedExample(string $format): string
            {
                return '';
            }
        };
        $numberRepository = $this->createConfiguredMock(ContentProtectionRepository::class, []);
        $languageRepository = $this->createConfiguredMock(LanguageRepository::class, []);
        $logger = $this->createConfiguredMock(ZfExtended_Logger::class, []);

        $protector = new NumberProtector([$processor], $numberRepository, $languageRepository, $logger);

        self::assertTrue($protector->hasEntityToProtect('text with number [2] in it'));
        self::assertTrue($protector->hasEntityToProtect('text with part of mac [aa:] in it'));
        self::assertTrue($protector->hasEntityToProtect('text with part of mac [aa-] in it'));
    }

    public function testCheatTypeUsage(): void
    {
        $sourceLang = new editor_Models_Languages();
        $sourceLang->setId(5);
        $sourceLang->setRfc5646('en');

        $getAllForSource = function () {
            yield new ContentProtectionDto(
                'ip-address',
                'test-cheat',
                '/some text/',
                0,
                '',
                true,
                null,
                1
            );
        };

        $numberRepository = $this->createConfiguredMock(
            ContentProtectionRepository::class,
            [
                'getAllForSource' => $getAllForSource(),
            ]
        );
        $languageRepository = $this->createConfiguredMock(LanguageRepository::class, [
            'find' => $sourceLang,
        ]);
        $logger = $this->createConfiguredMock(ZfExtended_Logger::class, []);

        $protector = new NumberProtector(
            [new IPAddressProtector($numberRepository)],
            $numberRepository,
            $languageRepository,
            $logger
        );

        self::assertSame(
            '1. Here we have <number type="ip-address" name="test-cheat" source="some text" iso="some text" target="some text" regex="0y/Oz01VKEmtKNEHAA=="/> case',
            $protector->protect('1. Here we have some text case', true, 5, 6)
        );
    }

    public function testRuleWithoutOutputFormatHandling(): void
    {
        $sourceLang = new editor_Models_Languages();
        $sourceLang->setId(5);
        $sourceLang->setRfc5646('en');

        $getAllForSource = function () {
            yield new ContentProtectionDto(
                'date',
                'test-no-output',
                '/some text/',
                0,
                '',
                false,
                null,
                1
            );
        };

        $numberRepository = $this->createConfiguredMock(
            ContentProtectionRepository::class,
            [
                'getAllForSource' => $getAllForSource(),
            ]
        );
        $languageRepository = $this->createConfiguredMock(LanguageRepository::class, [
            'find' => $sourceLang,
        ]);
        $logger = $this->createConfiguredMock(ZfExtended_Logger::class, []);
        $logger->expects($this->once())->method('__call')->with('warn');

        $protector = new NumberProtector(
            [new IPAddressProtector($numberRepository)],
            $numberRepository,
            $languageRepository,
            $logger
        );

        self::assertSame(
            '1. Here we have some text some text some text case',
            $protector->protect('1. Here we have some text some text some text case', true, 5, 6)
        );
    }

    /**
     * @dataProvider internalTagsProvider
     */
    public function testConvertToInternalTags(
        string $segment,
        string $converted,
        int $finalTagIdent,
    ): void {
        $protector = NumberProtector::create();
        $shortTagIdent = 1;

        $result = $protector->convertToInternalTagsWithShortcutNumberMapCollecting($segment, $shortTagIdent);

        self::assertSame($converted, $result->segment);
        self::assertSame($finalTagIdent, $result->shortTagIdent);
    }

    public function internalTagsProvider(): iterable
    {
        $tag1 = '<number type="date" name="default" source="20231020" iso="2023-10-20" target="2023-10-20"/>';
        $converted1 = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c742220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22323032332d31302d3230222f number internal-tag ownttip"><span title="&lt;1/&gt;: Number" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="10" data-source="20231020" data-target="2023-10-20" class="full"></span></div>';

        yield [
            'segment' => "string $tag1 string",
            'converted' => "string $converted1 string",
            'finalTagIdent' => 2,
        ];

        $tag2 = '<number type="integer" name="default" source="1234" iso="1234" target=""/>';
        $converted2 = '<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742220736f757263653d2231323334222069736f3d223132333422207461726765743d22222f number internal-tag ownttip"><span title="&lt;2/&gt;: Number" class="short">&lt;2/&gt;</span><span data-originalid="number" data-length="4" data-source="1234" data-target="" class="full"></span></div>';

        yield [
            'segment' => "string $tag1 string $tag2 string",
            'converted' => "string $converted1 string $converted2 string",
            'finalTagIdent' => 3,
        ];
    }

    /**
     * @dataProvider internalTagsInChunksProvider
     */
    public function testConvertToInternalTagsInChunks(string $segment, array $xmlChunks, int $finalTagIdent): void
    {
        $number = NumberProtector::create();
        $shortTagIdent = 1;

        self::assertEquals($xmlChunks, $number->convertToInternalTagsInChunks($segment, $shortTagIdent, true));
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function internalTagsInChunksProvider(): iterable
    {
        $tag1 = '<number type="date" name="default" source="20231020" iso="2023-10-20" target="2023-10-20"/>';
        $converted1 = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c742220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22323032332d31302d3230222f number internal-tag ownttip"><span title="&lt;1/&gt;: Number" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="10" data-source="20231020" data-target="2023-10-20" class="full"></span></div>';

        $parsedTag1 = new NumberTag();
        $parsedTag1->originalContent = $tag1;
        $parsedTag1->tagNr = 1;
        $parsedTag1->id = 'number';
        $parsedTag1->tag = 'number';
        $parsedTag1->text = '{"source":"20231020","target":"2023-10-20"}';
        $parsedTag1->iso = 'iso:2023-10-20';
        $parsedTag1->source = '20231020';
        $parsedTag1->renderedTag = $converted1;

        yield [
            'segment' => "string $tag1 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string'],
            'finalTagIdent' => 2,
        ];

        $tag2 = '<number type="integer" name="default" source="1234" iso="1234" target=""/>';
        $converted2 = '<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742220736f757263653d2231323334222069736f3d223132333422207461726765743d22222f number internal-tag ownttip"><span title="&lt;2/&gt;: Number" class="short">&lt;2/&gt;</span><span data-originalid="number" data-length="4" data-source="1234" data-target="" class="full"></span></div>';

        $parsedTag2 = new NumberTag();
        $parsedTag2->originalContent = $tag2;
        $parsedTag2->tagNr = 2;
        $parsedTag2->id = 'number';
        $parsedTag2->tag = 'number';
        $parsedTag2->text = '{"source":"1234","target":""}';
        $parsedTag2->iso = 'iso:1234';
        $parsedTag2->source = '1234';
        $parsedTag2->renderedTag = $converted2;

        yield [
            'segment' => "string $tag1 string $tag2 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string ', $parsedTag2, ' string'],
            'finalTagIdent' => 3,
        ];
    }
}
