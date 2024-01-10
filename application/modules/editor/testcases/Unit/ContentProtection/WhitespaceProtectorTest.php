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

use editor_Models_Segment_Whitespace;
use MittagQI\Translate5\ContentProtection\WhitespaceProtector;
use PHPUnit\Framework\TestCase;

class WhitespaceProtectorTest extends TestCase
{
    /**
     * @dataProvider caseProvider
     */
    public function testProtect(string $text, string $expected): void
    {
        $protector = new WhitespaceProtector(new editor_Models_Segment_Whitespace());

        self::assertEquals($expected, $protector->protect($text, true, 5, 6));
    }

    /**
     * @dataProvider caseProvider
     */
    public function testUnprotect(string $expected, string $text): void
    {
        $protector = new WhitespaceProtector(new editor_Models_Segment_Whitespace());

        self::assertEquals($expected, $protector->unprotect($text, true));
    }

    public function caseProvider(): iterable
    {
        yield 'hardReturn' => [
            'text' => "text with [\r\n] in it",
            'expected' => 'text with [<hardReturn/>] in it'
        ];
        yield 'softReturn' => [
            'text' => "text with [\n] in it",
            'expected' => 'text with [<softReturn/>] in it'
        ];
        yield 'macReturn' => [
            'text' => "text with [\r] in it",
            'expected' => 'text with [<macReturn/>] in it'
        ];


        yield 'End of Text (ETX)' => [
            'text' => "text with [] in it",
            'expected' => 'text with [<char ts="03" length="1"/>] in it'
        ];
        yield 'Backspace (BS)' => [
            'text' => "text with [] in it",
            'expected' => 'text with [<char ts="08" length="1"/>] in it'
        ];

        yield 'Character Tabulation (HT,TAB)' => [
            'text' => "text with [	] in it",
            'expected' => 'text with [<tab ts="09" length="1"/>] in it'
        ];
        yield 'Line Tabulation (VT)' => [
            'text' => "text with [] in it",
            'expected' => 'text with [<char ts="0b" length="1"/>] in it'
        ];
        yield 'Form Feed (FF)' => [
            'text' => "text with [] in it",
            'expected' => 'text with [<char ts="0c" length="1"/>] in it'
        ];
        yield 'Next Line (NEL)' => [
            'text' => "text with [] in it",
            'expected' => 'text with [<char ts="c285" length="1"/>] in it'
        ];
        yield 'No-Break Space (NBSP)' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="c2a0" length="1"/>] in it'
        ];
        yield 'Ogham Space Mark' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e19a80" length="1"/>] in it'
        ];
        yield 'Mongolian Vowel Separator (MVS)' => [
            'text' => "text with [᠎] in it",
            'expected' => 'text with [<char ts="e1a08e" length="1"/>] in it'
        ];
        yield 'En Quad' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28080" length="1"/>] in it'
        ];
        yield 'Em Quad' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28081" length="1"/>] in it'
        ];
        yield 'En Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28082" length="1"/>] in it'
        ];
        yield 'Em Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28083" length="1"/>] in it'
        ];
        yield 'Three-Per-Em Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28084" length="1"/>] in it'
        ];
        yield 'Four-Per-Em Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28085" length="1"/>] in it'
        ];
        yield 'Six-Per-Em Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28086" length="1"/>] in it'
        ];
        yield 'Figure Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28087" length="1"/>] in it'
        ];
        yield 'Punctuation Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28088" length="1"/>] in it'
        ];
        yield 'Thin Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e28089" length="1"/>] in it'
        ];
        yield 'Hair Space' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e2808a" length="1"/>] in it'
        ];
        yield 'Zero Width Space (ZWSP)' => [
            'text' => "text with [​] in it",
            'expected' => 'text with [<char ts="e2808b" length="1"/>] in it'
        ];
        yield 'Line Separator' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e280a8" length="1"/>] in it'
        ];
        yield 'Paragraph Separator' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e280a9" length="1"/>] in it'
        ];
        yield 'Narrow No-Break Space (NNBSP)' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e280af" length="1"/>] in it'
        ];
        yield 'Medium Mathematical Space (MMSP)' => [
            'text' => "text with [ ] in it",
            'expected' => 'text with [<char ts="e2819f" length="1"/>] in it'
        ];
        yield 'Ideographic Space' => [
            'text' => "text with [　] in it",
            'expected' => 'text with [<char ts="e38080" length="1"/>] in it'
        ];
    }

    /**
     * @dataProvider internalTagsProvider
     */
    public function testConvertToInternalTags(string $segment, string $converted, int $finalTagIdent): void
    {
        $shortTagIdent = 1;
        $whitespace = new WhitespaceProtector(new editor_Models_Segment_Whitespace());

        self::assertSame($converted, $whitespace->convertToInternalTags($segment, $shortTagIdent));
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

        $tag2 = '<softReturn/>';
        $converted2 = '<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;2/&gt;: Newline" class="short">&lt;2/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>';

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
        $whitespace = new WhitespaceProtector(new editor_Models_Segment_Whitespace());
        $shortTagIdent = 1;

        self::assertEquals($xmlChunks, $whitespace->convertToInternalTagsInChunks($segment, $shortTagIdent));
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function internalTagsInChunksProvider(): iterable
    {
        $tag1 = '<hardReturn/>';
        $converted1 = '<div class="single 6861726452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="hardReturn" data-length="1" class="full">↵</span></div>';

        $parsedTag1 = new \editor_Models_Import_FileParser_WhitespaceTag();
        $parsedTag1->originalContent = $tag1;
        $parsedTag1->rawContent = "\r\n";
        $parsedTag1->tagNr = 1;
        $parsedTag1->id = 'hardReturn';
        $parsedTag1->tag = 'hardReturn';
        $parsedTag1->text = '↵';
        $parsedTag1->renderedTag = $converted1;

        yield [
            'segment' => "string $tag1 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string'],
            'finalTagIdent' => 2,
        ];

        $tag2 = '<softReturn/>';
        $converted2 = '<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;2/&gt;: Newline" class="short">&lt;2/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>';

        $parsedTag2 = new \editor_Models_Import_FileParser_WhitespaceTag();
        $parsedTag2->originalContent = $tag2;
        $parsedTag2->rawContent = "\n";
        $parsedTag2->tagNr = 2;
        $parsedTag2->id = 'softReturn';
        $parsedTag2->tag = 'softReturn';
        $parsedTag2->text = '↵';
        $parsedTag2->renderedTag = $converted2;

        yield [
            'segment' => "string $tag1 string $tag2 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string ', $parsedTag2, ' string'],
            'finalTagIdent' => 3,
        ];
    }
}
