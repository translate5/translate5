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

namespace MittagQI\Translate5\Test\Integration\ContentProtection;

use editor_Models_Import_FileParser_Tag as Tag;
use editor_Models_Import_FileParser_WhitespaceTag;
use editor_Models_Segment_Whitespace as Whitespace;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\DateProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\FloatProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IntegerProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Tag\NumberTag;
use MittagQI\Translate5\Test\UnitTestAbstract;
use ZfExtended_Factory;

class ContentProtectorTest extends UnitTestAbstract
{
    protected function setUp(): void
    {
        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        foreach ($inputMapping->loadAll() as $item) {
            $inputMapping->load($item['id']);
            $inputMapping->delete();
        }

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        foreach ($outputMapping->loadAll() as $item) {
            $outputMapping->load($item['id']);
            $outputMapping->delete();
        }

        $crDate1 = ZfExtended_Factory::get(ContentRecognition::class);
        $crDate1->loadBy(DateProtector::getType(), 'default Y-m-d');
        $crDate1->setEnabled(true);
        $crDate1->save();

        $crDate2 = ZfExtended_Factory::get(ContentRecognition::class);
        $crDate2->loadBy(DateProtector::getType(), 'default d/m/y');
        $crDate2->setEnabled(true);
        $crDate2->save();

        $crFloat1 = ZfExtended_Factory::get(ContentRecognition::class);
        $crFloat1->loadBy(FloatProtector::getType(), 'default with comma thousand decimal dot');
        $crFloat1->setEnabled(true);
        $crFloat1->save();

        $crFloat2 = ZfExtended_Factory::get(ContentRecognition::class);
        $crFloat2->loadBy(FloatProtector::getType(), 'default with dot thousand decimal comma');
        $crFloat2->setEnabled(true);
        $crFloat2->save();

        $crInt1 = ZfExtended_Factory::get(ContentRecognition::class);
        $crInt1->loadBy(IntegerProtector::getType(), 'default generic with Middle dot separator');
        $crInt1->setEnabled(true);
        $crInt1->save();

        $goba = ZfExtended_Factory::get(ContentRecognition::class);
        $goba->setName('Goba');
        $goba->setType(KeepContentProtector::getType());
        $goba->setEnabled(true);
        $goba->setKeepAsIs(true);
        $goba->setRegex('#\<goba\>#');
        $goba->setMatchId(0);
        $goba->save();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId(5);
        $inputMapping->setContentRecognitionId($goba->getId());
        $inputMapping->setPriority(4);
        $inputMapping->save();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId(5);
        $inputMapping->setContentRecognitionId($crDate1->getId());
        $inputMapping->setPriority(3);
        $inputMapping->save();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId(5);
        $inputMapping->setContentRecognitionId($crFloat1->getId());
        $inputMapping->setPriority(2);
        $inputMapping->save();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId(5);
        $inputMapping->setContentRecognitionId($crInt1->getId());
        $inputMapping->setPriority(1);
        $inputMapping->save();

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        $outputMapping->setLanguageId(6);
        $outputMapping->setInputContentRecognitionId($crDate1->getId());
        $outputMapping->setOutputContentRecognitionId($crDate2->getId());
        $outputMapping->save();

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        $outputMapping->setLanguageId(6);
        $outputMapping->setInputContentRecognitionId($crFloat1->getId());
        $outputMapping->setOutputContentRecognitionId($crFloat2->getId());
        $outputMapping->save();
    }

    protected function tearDown(): void
    {
        $contentRecognition = ZfExtended_Factory::get(ContentRecognition::class);
        $contentRecognition->loadBy(DateProtector::getType(), 'default Y-m-d');
        $contentRecognition->setEnabled(false);
        $contentRecognition->save();

        $contentRecognition->loadBy(DateProtector::getType(), 'default d/m/y');
        $contentRecognition->setEnabled(false);
        $contentRecognition->save();

        $contentRecognition->loadBy(FloatProtector::getType(), 'default with comma thousand decimal dot');
        $contentRecognition->setEnabled(false);
        $contentRecognition->save();

        $contentRecognition->loadBy(FloatProtector::getType(), 'default with dot thousand decimal comma');
        $contentRecognition->setEnabled(false);
        $contentRecognition->save();

        $contentRecognition->loadBy(IntegerProtector::getType(), 'default generic with Middle dot separator');
        $contentRecognition->setEnabled(false);
        $contentRecognition->save();

        $contentRecognition->loadBy(KeepContentProtector::getType(), 'Goba');
        $contentRecognition->delete();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        foreach ($inputMapping->loadAll() as $item) {
            $inputMapping->load($item['id']);
            $inputMapping->delete();
        }

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        foreach ($outputMapping->loadAll() as $item) {
            $outputMapping->load($item['id']);
            $outputMapping->delete();
        }
    }

    /**
     * @dataProvider casesProvider
     */
    public function testProtect(string $node, string $expected, string $entityHandling): void
    {
        $contentProtector = ContentProtector::create(new Whitespace());

        self::assertEquals($expected, $contentProtector->protect($node, true, 5, 6, $entityHandling));
    }

    public function testUnprotectSpecificCase(): void
    {
        $contentProtector = ContentProtector::create(new Whitespace());

        self::assertSame(
            '123.456,789 Übersetzungsbüro [ ] 24translate 15/09/23 and 19/10/24',
            $contentProtector->unprotect(
                '<number type="float" name="default with comma thousand decimal dot" source="123,456.789" iso="123456.789" target="123.456,789"/> Übersetzungsbüro [<char ts="c2a0" length="1"/>] 24translate <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23"/> and <number type="date" name="default Y-m-d" source="2024-10-19" iso="2024-10-19" target="19/10/24"/>',
                false
            )
        );
    }

    /**
     * @dataProvider casesProvider
     */
    public function testUnprotect(string $expected, string $node): void
    {
        $contentProtector = ContentProtector::create(new Whitespace());
        self::assertSame($expected, $contentProtector->unprotect($node, true));
    }

    public function casesProvider(): iterable
    {
        yield 'whitespace right after number' => [
            'text' => 'string 123,456.789 mm',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="123,456.789" iso="123456.789" target="123.456,789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/><char ts="c2a0" length="1"/>mm',
            'entityHandling' => ContentProtector::ENTITY_MODE_RESTORE,
        ];

        yield 'tag like entity <goba>' => [
            'text' => 'string <goba> string',
            'expected' => 'string <number type="keep-content" name="Goba" source="&lt;goba&gt;" iso="&lt;goba&gt;" target="&lt;goba&gt;" regex="U46xSc9PSoyxUwYA"/> string',
            'entityHandling' => ContentProtector::ENTITY_MODE_RESTORE,
        ];

        yield 'int without output format' => [
            'text' => 'Übersetzungsbüro 123·456·789 24translate',
            'expected' => 'Übersetzungsbüro 123·456·789 24translate',
            'entityHandling' => ContentProtector::ENTITY_MODE_RESTORE,
        ];

        yield '&#128; is protected as char' => [
            'text' => 'Test &amp;para; entities ¶ and umlauts öäü¶ and [&#128;] TRANSLATE',
            'expected' => 'Test &amp;para; entities ¶ and umlauts öäü¶ and [<char ts="26233132383b" length="1"/>] TRANSLATE',
            'entityHandling' => ContentProtector::ENTITY_MODE_RESTORE,
        ];

        yield 'float in the beginning' => [
            'text' => '123,456.789 Übersetzungsbüro [ ] 24translate 2023-09-15 and 2024-10-19',
            'expected' => '<number type="float" name="default with comma thousand decimal dot" source="123,456.789" iso="123456.789" target="123.456,789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/> Übersetzungsbüro [<char ts="c2a0" length="1"/>] 24translate <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="PUy5DYAwDFyG4k4iJA40zBKbig0oMbvjIIXmfl2GXn64gtDz3p6E0iTt5tJKquaf4Z8GVosm5BokY0BAl341kY55qE6uZH4B"/> and <number type="date" name="default Y-m-d" source="2024-10-19" iso="2024-10-19" target="19/10/24" regex="PUy5DYAwDFyG4k4iJA40zBKbig0oMbvjIIXmfl2GXn64gtDz3p6E0iTt5tJKquaf4Z8GVosm5BokY0BAl341kY55qE6uZH4B"/>',
            'entityHandling' => ContentProtector::ENTITY_MODE_RESTORE,
        ];

        yield 'zero between tags' => [
            'text' => 'Text mit <protectedTag data-type="open" data-id="1" data-content="3c623e"/>0<protectedTag data-type="close" data-id="1" data-content="3c2f623e"/> in tags gab Probleme.',
            'expected' => 'Text mit <protectedTag data-type="open" data-id="1" data-content="3c623e"/>0<protectedTag data-type="close" data-id="1" data-content="3c2f623e"/> in tags gab Probleme.',
            'entityHandling' => ContentProtector::ENTITY_MODE_OFF,
        ];

        yield 'end of text character' => [
            'text' => "03: ",
            'expected' => '03: <char ts="03" length="1"/>',
            'entityHandling' => ContentProtector::ENTITY_MODE_RESTORE,
        ];
    }

    /**
     * @dataProvider protectAndConvertProvider
     */
    public function testProtectAndConvert(string $segment, string $converted, int $finalTagIdent): void
    {
        $shortTagIdent = 1;
        $contentProtector = ContentProtector::create(new Whitespace());

        self::assertSame(
            $converted,
            $contentProtector->protectAndConvert($segment, true, 5, 6, $shortTagIdent, ContentProtector::ENTITY_MODE_OFF)
        );
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function protectAndConvertProvider(): iterable
    {
        $number = '2023-10-20';
        $convertedNumber = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d31302d3230222069736f3d22323032332d31302d323022207461726765743d2232302f31302f3233222072656765783d22505579354459417744467947346b34694a4134307a424b626967306f4d62766a4949586d666c3247586e36346774447a337036453069547435744a4b71756166345a3847566f736d35426f6b593042416c3334316b593535714536755a483442222f number internal-tag ownttip"><span title="&lt;1/&gt;: Number" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="8" data-source="2023-10-20" data-target="20/10/23" class="full"></span></div>';

        yield 'Protect date' => [
            'segment' => "string $number string",
            'converted' => "string $convertedNumber string",
            'finalTagIdent' => 2,
        ];

        $nbsp = ' ';
        $convertedNbsp = '<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>';

        yield 'Protect [NBSP] and date' => [
            'segment' => "string [$nbsp] string $number string",
            'converted' => "string [$convertedNbsp] string $convertedNumber string",
            'finalTagIdent' => 3,
        ];

        $numberIdentTwo = '2023-10-30';
        $convertedNumberIdentTwo = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d31302d3330222069736f3d22323032332d31302d333022207461726765743d2233302f31302f3233222072656765783d22505579354459417744467947346b34694a4134307a424b626967306f4d62766a4949586d666c3247586e36346774447a337036453069547435744a4b71756166345a3847566f736d35426f6b593042416c3334316b593535714536755a483442222f number internal-tag ownttip"><span title="&lt;2/&gt;: Number" class="short">&lt;2/&gt;</span><span data-originalid="number" data-length="8" data-source="2023-10-30" data-target="30/10/23" class="full"></span></div>';

        yield 'Protect 2 dates' => [
            'segment' => "$number string $numberIdentTwo",
            'converted' => "$convertedNumber string $convertedNumberIdentTwo",
            'finalTagIdent' => 3,
        ];

        $hardReturn = "\r\n";
        $convertedHardReturn = '<div class="single 6861726452657475726e2f newline internal-tag ownttip"><span title="&lt;2/&gt;: Newline" class="short">&lt;2/&gt;</span><span data-originalid="hardReturn" data-length="1" class="full">↵</span></div>';

        yield 'Protect date and hard return' => [
            'segment' => "$number string $hardReturn",
            'converted' => "$convertedNumber string $convertedHardReturn",
            'finalTagIdent' => 3,
        ];
    }

    /**
     * @dataProvider internalTagsProvider
     */
    public function testConvertToInternalTags(string $segment, string $converted, int $finalTagIdent): void
    {
        $shortTagIdent = 1;
        $contentProtector = ContentProtector::create(new Whitespace());

        self::assertSame($converted, $contentProtector->convertToInternalTags($segment, $shortTagIdent));
        self::assertSame($finalTagIdent, $shortTagIdent);
    }

    public function internalTagsProvider(): iterable
    {
        $tagN = '<number type="date" name="default" source="20231020" iso="2023-10-20" target="2023-10-20"/>';
        $convertedN = '<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c742220736f757263653d223230323331303230222069736f3d22323032332d31302d323022207461726765743d22323032332d31302d3230222f number internal-tag ownttip"><span title="&lt;1/&gt;: Number" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="10" data-source="20231020" data-target="2023-10-20" class="full"></span></div>';

        yield [
            'segment' => "$tagN string",
            'converted' => "$convertedN string",
            'finalTagIdent' => 2,
        ];

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
        $contentProtector = ContentProtector::create(new Whitespace());

        self::assertEquals($xmlChunks, $contentProtector->convertToInternalTagsInChunks($segment, $shortTagIdent));
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
        $parsedTag1->renderedTag = $converted1;
        $parsedTag1->iso = '2023-10-20';
        $parsedTag1->source = '20231020';

        yield [
            'segment' => "string $tag1 string",
            'xmlChunks' => ['string ', $parsedTag1, ' string'],
            'finalTagIdent' => 2,
        ];

        yield [
            'segment' => "$tag1 string",
            'xmlChunks' => [$parsedTag1, ' string'],
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

        $bTag = new Tag(Tag::TYPE_OPEN);
        $bTag->originalContent = '<b>';
        $bTag->rid = '1';
        $bTag->tagNr = 1;
        $bTag->id = '1';
        $bTag->tag = 'protectedTag';
        $bTag->text = '&lt;b&gt;';
        $bTag->renderedTag = '<div class="open 62 internal-tag ownttip"><span title="&lt;b&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;b&gt;</span></div>';

        $bCloseTag = new Tag(Tag::TYPE_CLOSE);
        $bCloseTag->originalContent = '</b>';
        $bCloseTag->rid = '1';
        $bCloseTag->tagNr = 1;
        $bCloseTag->id = '1';
        $bCloseTag->tag = 'protectedTag';
        $bCloseTag->text = '&lt;/b&gt;';
        $bCloseTag->renderedTag = '<div class="close 2f62 internal-tag ownttip"><span title="&lt;/b&gt;" class="short">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/b&gt;</span></div>';

        yield 'zero between tags' => [
            'segment' => 'Text mit <protectedTag data-type="open" data-id="1" data-content="3c623e"/>0<protectedTag data-type="close" data-id="1" data-content="3c2f623e"/> in tags gab Probleme.',
            'xmlChunks' => ['Text mit ', $bTag, '0', $bCloseTag, ' in tags gab Probleme.'],
            'finalTagIdent' => 2,
        ];
    }
}
