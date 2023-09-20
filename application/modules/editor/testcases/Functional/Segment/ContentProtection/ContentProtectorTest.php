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

namespace MittagQI\Translate5\Test\Functional\Segment\ContentProtection;

use editor_Models_Import_FileParser_WhitespaceTag;
use editor_Models_Segment_Whitespace;
use editor_Test_UnitTest;
use MittagQI\Translate5\NumberProtection\NumberProtector;
use MittagQI\Translate5\NumberProtection\Tag\NumberTag;
use MittagQI\Translate5\Segment\ContentProtection\ContentProtector;
use MittagQI\Translate5\Segment\ContentProtection\WhitespaceProtector;

class ContentProtectorTest extends editor_Test_UnitTest
{
    /**
     * @dataProvider casesProvider
     */
    public function testProtect(string $node, string $expected): void
    {
        $contentProtector = ContentProtector::create(new editor_Models_Segment_Whitespace());

        self::assertEquals($expected, $contentProtector->protect($node, null, null));
    }

    /**
     * @dataProvider casesProvider
     */
    public function testUnprotect(string $expected, string $node, bool $runTest = true): void
    {
        $contentProtector = ContentProtector::create(new editor_Models_Segment_Whitespace());

        if (!$runTest) {
            // Test case designed for `protect` test only
            self::assertTrue(true);

            return;
        }
        self::assertSame($expected, $contentProtector->unprotect($node));
    }

    public function casesProvider(): iterable
    {
        yield 'NNBSP in tag is safe' => [
            'text' => "text with 1 234 [\r\n] in it",
            'expected' => 'text with <number type="integer" name="default generic with not standard separator" source="1 234" iso="1234" target=""/> [<hardReturn/>] in it'
        ];

        yield 'non DOM whitespaces' => [
            'text' => 'string [] 1 234 [] string',
            'expected' => 'string [<char ts="03" length="1"/>] <number type="integer" name="default generic with not standard separator" source="1 234" iso="1234" target=""/> [<char ts="08" length="1"/>] string'
        ];
    }

    /**
     * @dataProvider protectAndConvertProvider
     */
    public function testProtectAndConvert(string $segment, string $converted, int $finalTagIdent): void
    {
        $shortTagIdent = 1;
        $contentProtector = ContentProtector::create(new editor_Models_Segment_Whitespace());

        self::assertSame($converted, $contentProtector->protectAndConvert($segment, null, null, $shortTagIdent));
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function protectAndConvertProvider(): iterable
    {
        $nbsp = ' ';
        $convertedNbsp = '<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;1/&gt;: No-Break Space (NBSP)" class="short">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>';

        yield [
            'segment' => "string [$nbsp] string",
            'converted' => "string [$convertedNbsp] string",
            'finalTagIdent' => 2,
        ];

        $number = '20231020';
        $convertedNumber = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420596d642220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22222f number internal-tag ownttip"><span title="&lt;2/&gt;: Number" class="short">&lt;2/&gt;</span><span data-originalid="number" data-length="-1" data-source="20231020" data-target="" class="full"></span></div>';

        yield [
            'segment' => "string [$nbsp] string $number string",
            'converted' => "string [$convertedNbsp] string $convertedNumber string",
            'finalTagIdent' => 3,
        ];
    }

    /**
     * @dataProvider internalTagsProvider
     */
    public function testConvertToInternalTags(string $segment, string $converted, int $finalTagIdent): void
    {
        $shortTagIdent = 1;
        $contentProtector = ContentProtector::create(new editor_Models_Segment_Whitespace());

        self::assertSame($converted, $contentProtector->convertToInternalTags($segment, $shortTagIdent));
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function internalTagsProvider(): iterable
    {
        $tag1 = '<hardReturn/>';
        $converted1 = '<div class="single 6861726452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="hardReturn" data-length="1" class="full">↵</span></div>';

        yield [
            'segment' => "string $tag1 string",
            'converted' => "string $converted1 string",
            'finalTagIdent' => 2,
        ];

        $tag2 = '<number type="date" name="default" source="20231020" iso="2023-10-20" target="2023-10-20"/>';
        $converted2 = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c742220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22323032332d31302d3230222f number internal-tag ownttip"><span title="&lt;2/&gt;: Number" class="short">&lt;2/&gt;</span><span data-originalid="number" data-length="-1" data-source="20231020" data-target="2023-10-20" class="full"></span></div>';

        yield [
            'segment' => "string $tag1 string $tag2 string",
            'converted' => "string $converted1 string $converted2 string",
            'finalTagIdent' => 3,
        ];

        $tagNBSP = '<char ts="c2a0" length="1"/>';
        $convertedNBSP = '<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;1/&gt;: No-Break Space (NBSP)" class="short">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>';

        yield [
            'segment' => "string $tagNBSP string $tag2 string",
            'converted' => "string $convertedNBSP string $converted2 string",
            'finalTagIdent' => 3,
        ];
    }

    /**
     * @dataProvider internalTagsInChunksProvider
     */
    public function testConvertToInternalTagsInChunks(string $segment, array $xmlChunks, int $finalTagIdent): void
    {
        $shortTagIdent = 1;
        $contentProtector = ContentProtector::create(new editor_Models_Segment_Whitespace());

        self::assertEquals($xmlChunks, $contentProtector->convertToInternalTagsInChunks($segment, $shortTagIdent));
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function internalTagsInChunksProvider(): iterable
    {
        $tag1 = '<number type="date" name="default" source="20231020" iso="2023-10-20" target="2023-10-20"/>';
        $converted1 = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c742220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22323032332d31302d3230222f number internal-tag ownttip"><span title="&lt;1/&gt;: Number" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="-1" data-source="20231020" data-target="2023-10-20" class="full"></span></div>';

        $parsedTag1 = new NumberTag();
        $parsedTag1->originalContent = $tag1;
        $parsedTag1->tagNr = 1;
        $parsedTag1->id = 'number';
        $parsedTag1->tag = 'number';
        $parsedTag1->text = '{"source":"20231020","target":"2023-10-20"}';
        $parsedTag1->renderedTag = $converted1;

        yield [
            'segment' => "string $tag1 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string'],
            'finalTagIdent' => 2,
        ];

        $tag2 = '<hardReturn/>';
        $converted2 = '<div class="single 6861726452657475726e2f newline internal-tag ownttip"><span title="&lt;2/&gt;: Newline" class="short">&lt;2/&gt;</span><span data-originalid="hardReturn" data-length="1" class="full">↵</span></div>';

        $parsedTag2 = new editor_Models_Import_FileParser_WhitespaceTag();
        $parsedTag2->originalContent = $tag2;
        $parsedTag2->rawContent = "\r\n";
        $parsedTag2->tagNr = 2;
        $parsedTag2->id = 'hardReturn';
        $parsedTag2->tag = 'hardReturn';
        $parsedTag2->text = '↵';
        $parsedTag2->renderedTag = $converted2;

        yield [
            'segment' => "string $tag1 string $tag2 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string ', $parsedTag2, ' string'],
            'finalTagIdent' => 3,
        ];

        $tagNBSP = '<char ts="c2a0" length="1"/>';
        $convertedNBSP = '<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>';

        $parsedNBSP = new editor_Models_Import_FileParser_WhitespaceTag();
        $parsedNBSP->originalContent = $tagNBSP;
        $parsedNBSP->rawContent = " ";
        $parsedNBSP->tagNr = 2;
        $parsedNBSP->id = 'char';
        $parsedNBSP->tag = 'char';
        $parsedNBSP->text = '⎵';
        $parsedNBSP->renderedTag = $convertedNBSP;

        yield [
            'segment' => "string $tag1 string [$tagNBSP] string",
            'xmlChunks' => ['string ', $parsedTag1, ' string [', $parsedNBSP, '] string'],
            'finalTagIdent' => 3,
        ];
    }
}
