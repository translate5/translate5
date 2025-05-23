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

namespace MittagQI\Translate5\Test\Unit\Segment;

use MittagQI\Translate5\Segment\TagRepair\Tag;
use MittagQI\Translate5\Segment\TagRepair\Tags;
use MittagQI\Translate5\Test\MockedTaskTestAbstract;
use MittagQI\ZfExtended\Tools\Markup;
use ZfExtended_Exception;

/**
 * Several "classic" PHPUnit tests to check the general tag repair (not to mix up with the internal tags repair)
 * When creating test-data notice, that if a "<" shall be used in the markup it must be followed by a " "
 * TODO FIXME: The ::replaceRequestTags to transform the translated request is not able to transform all kinds of tag-combinations
 */
class TagsRepairTest extends MockedTaskTestAbstract
{
    public const DO_DEBUG = false;

    /**
     * Some Internal Tags to create Tests with
     */
    private array $htmlTags = [
        '<1>' => '<div class="test" id="ex12345" data-sth="test">',
        '</1>' => '</div>',
        '<2>' => '<span class="test">',
        '</2>' => '</span>',
        '<3>' => '<a href="http://www.example.com">',
        '</3>' => '</a>',
        '<4>' => '<div class="test2" id="uc3456" data-sth="test2">',
        '</4>' => '</div>',
        '<5>' => '<b>',
        '</5>' => '</b>',
        '<6/>' => '<br />',
        '<7/>' => '<img src="http://www.example.com/image.jpg" />',
        '<8/>' => '<hr />',
        '<9/>' => '<input name="test" type="text" value="test" />',
        '<10/>' => '<!-- Some Comment with some special characters: > " \' -->',
        '<11/>' => '<!-- Some Comment with inner markup: <div><b> ... </b></div>  </span> -->',
        '<12/>' => "<!-- Some multiline Comment which\nspans multiple lines\r\nand contains \r other\t\twhitespace -->",
    ];

    private array $internalTags = [
        /* internal tags */
        '<1>' => '<div class="open 64697620636c6173733d2265672d636f6e74656e742d656469746f722220646174612d747970653d22646f63756d656e7422 internal-tag ownttip"><span class="short" title="&lt;div class=&quot;eg-content-editor&quot; data-type=&quot;document&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;div class=&quot;eg-content-editor&quot; data-type=&quot;document&quot;&gt;</span></div>',
        '</1>' => '<div class="close 2f644976 internal-tag ownttip"><span class="short" title="&lt;/div&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/div&gt;</span></div>',
        '<2>' => '<div class="open 64697620636c6173733d22636f6c756d6e22 internal-tag ownttip"><span class="short" title="&lt;div class=&quot;column&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;div class=&quot;column&quot;&gt;</span></div>',
        '</2>' => '<div class="close 2f646976 internal-tag ownttip"><span class="short" title="&lt;/div&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/div&gt;</span></div>',
        '<3>' => '<div class="open 64697620636c6173733d22726f7720646f63756d656e742d656469746f722220646174612d636f6e74656e742d747970653d22446f63756d656e74456469746f7222 internal-tag ownttip"><span class="short" title="&lt;div class=&quot;row document-editor&quot; data-content-type=&quot;DocumentEditor&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;div class=&quot;row document-editor&quot; data-content-type=&quot;DocumentEditor&quot;&gt;</span></div>',
        '</3>' => '<div class="close 2f648976 internal-tag ownttip"><span class="short" title="&lt;/div&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/div&gt;</span></div>',
        '<4>' => '<div class="open 646976 internal-tag ownttip"><span class="short" title="&lt;div&gt;">&lt;4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;div&gt;</span></div>',
        '</4>' => '<div class="close 2f655976 internal-tag ownttip"><span class="short" title="&lt;/div&gt;">&lt;/4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;/div&gt;</span></div>',
        '<5>' => '<div class="open 64697620636c6173733d22646f63756d656e742d696e666f22 internal-tag ownttip"><span class="short" title="&lt;div class=&quot;document-info&quot;&gt;">&lt;5&gt;</span><span class="full" data-originalid="6" data-length="-1">&lt;div class=&quot;document-info&quot;&gt;</span></div>',
        '</5>' => '<div class="close 2f666976 internal-tag ownttip"><span class="short" title="&lt;/div&gt;">&lt;/5&gt;</span><span class="full" data-originalid="5" data-length="-1">&lt;/div&gt;</span></div>',
        /* mqm tags */
        '<6>' => '<img class="open critical qmflag ownttip qmflag-8" src="/modules/editor/images/imageTags/qmsubsegment-8-left.png" data-t5qid="80029" data-comment="" />',
        '</6>' => '<img class="close critical qmflag ownttip qmflag-8" src="/modules/editor/images/imageTags/qmsubsegment-8-right.png" data-t5qid="80029" data-comment="" />',
        '<7>' => '<img class="open critical qmflag ownttip qmflag-16" src="/modules/editor/images/imageTags/qmsubsegment-16-left.png" data-t5qid="80030" data-comment="" />',
        '</7>' => '<img class="close critical qmflag ownttip qmflag-16" src="/modules/editor/images/imageTags/qmsubsegment-16-right.png" data-t5qid="80030" data-comment="" />',
        /* term tags */
        '<8>' => '<div class="term supersededTerm lowercase" title="" data-tbxid="2ff91169-6e23-4b3c-b1b7-2ec520e2af10" data-t5qid="80588">',
        '</8>' => '</div>',
        '<9>' => '<div class="term preferredTerm exact transNotDefined" title="" data-tbxid="1de47d3f-8332-4b00-b5ac-b0ff0efd5943" data-t5qid="80589">',
        '</9>' => '</div>',
        '<10/>' => '<div class="single gcxgxhjfxcuzfc87c7867cuigcgcuitc78tctgc internal-tag ownttip"><span class="short" title="&lt;iframe width=&quot;100%&quot; height=&quot;550px&quot; frameborder=&quot;0&quot; class=&quot;document-block-iframe&quot; src=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/external-preview&quot;/&gt;">&lt;12/&gt;</span><span class="full" data-originalid="12" data-length="-1">&lt;iframe width=&quot;100%&quot; height=&quot;550px&quot; frameborder=&quot;0&quot; class=&quot;document-block-iframe&quot; src=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/external-preview&quot;/&gt;</span></div>',
        '<11/>' => '<div class="single ufdsuzfdsuzrtd785djhduzfduzfduzd internal-tag ownttip"><span class="short" title="&lt;a href=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/content&quot; target=&quot;_blank&quot; download=&quot;document&quot; class=&quot;download-document-btn&quot;/&gt;">&lt;10/&gt;</span><span class="full" data-originalid="10" data-length="-1">&lt;a href=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/content&quot; target=&quot;_blank&quot; download=&quot;document&quot; class=&quot;download-document-btn&quot;/&gt;</span></div>',
    ];

    public function testMarkupEscape0(): void
    {
        $unescaped = ' what <span>seven is > six</span>  <a href="http://www.google.de">Some < Link</a> <!-- some comment with <b>markup</b> --> AND OTHER " TEXT';
        $escaped = ' what <span>seven is &gt; six</span>  <a href="http://www.google.de">Some &lt; Link</a> <!-- some comment with <b>markup</b> --> AND OTHER &quot; TEXT';
        $this->assertEquals($escaped, Markup::escape($unescaped));
        $this->assertEquals($unescaped, Markup::unescape($escaped));
    }

    public function testMarkupEscape1(): void
    {
        $unescaped = ' < ? " < (\') ' . $this->htmlTags['<10/>'] . ' > ? " < (\') ' . $this->htmlTags['<11/>'] . ' < ? " > (") ' . $this->htmlTags['<12/>'] . ' > ? " > (!) ';
        $escaped = ' &lt; ? &quot; &lt; (\') ' . $this->htmlTags['<10/>'] . ' &gt; ? &quot; &lt; (\') ' . $this->htmlTags['<11/>'] . ' &lt; ? &quot; &gt; (&quot;) ' . $this->htmlTags['<12/>'] . ' &gt; ? &quot; &gt; (!) ';
        $this->assertEquals($escaped, Markup::escape($unescaped));
        $this->assertEquals($unescaped, Markup::unescape($escaped));
    }

    public function testMarkupProtect0(): void
    {
        $expected = ' what <span data-text="(this is a) &gt; it\'s comment">seven is > six</span>  <a href="http://www.google.de">Some < Link</a> <!-- some comment with <b>markup</b> --> AND OTHER " TEXT';
        $protectedData = Markup::protectTags($expected);
        $markup = $protectedData->markup;
        $this->assertEquals($expected, Markup::unprotectTags($markup, $protectedData));
    }

    public function testMarkupProtect1(): void
    {
        $expected = ' < ? " < (\') ' . $this->htmlTags['<10/>'] . ' > ? " < (\') ' . $this->htmlTags['<11/>'] . ' < ? " > (") ' . $this->htmlTags['<12/>'] . ' > ? " > (!) ';
        $protectedData = Markup::protectTags($expected);
        $markup = $protectedData->markup;
        $this->assertEquals($expected, Markup::unprotectTags($markup, $protectedData));
    }

    public function testTagRepair0(): void
    {
        $markup = '<1><8/>Ein kurzer Satz</1>,<6/> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1><8/>Ein kurzer Satz</1>,<6/> der übersetzt<7/> werden <2>muss<9/></2>';
        $this->createHtmlTagsRepairTest($markup, '', $translated, true);
    }

    public function testTagRepair1(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1><6/> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = 'Ein kurzer Satz, der übersetzt werden muss';
        $this->createHtmlTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair2(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1><8/>Ein kurzer Satz, der übersetzt<7/> werden muss<9/></2>';
        $this->createHtmlTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair3(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1><8/>Ein kurzer Satz, der übersetzt<7/> werden muss<9/></2>';
        $this->createHtmlTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair4(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1>Ein kurzer Satz, der übersetzt<7/> werden muss<9/></2>';
        $this->createHtmlTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair5(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1>Ein kurzer Satz, der übersetzt werden muss</2>';
        $this->createHtmlTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair6(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $translated = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair7(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair8(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that <2>has to be</2> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair9(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to <2>be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that has to be</2> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair10(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to <2>be<9/></2> translated<7/>';
        $translated = '<1>A short sentence, that has to be</2> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair11(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to<7/> be <2>translated<9/></2>';
        $translated = 'A short sentence, that has to be translated';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair12(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/></1>NOTHING!<7/><2><9/></2>';
        $translated = 'NOTHING!';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair13(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>JUST</1> THREE<7/> WORDS<2><9/></2>';
        $translated = 'JUST THREE WORDS';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair14(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>JUST</1> THREE <7/>WORDS<2><9/></2>';
        $translated = 'JUST THREE WORDS';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair15(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $translated = '<1><8/>This now is a somehow longer sentence,</1> that is <2>really completely changed<9/></2>, as it<7/> can happen';
        $this->createHtmlTagsRepairTest($markup, $translated, $translated);
    }

    public function testTagRepair16(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>This now is a somehow longer sentence,</1> that is <2>really completely<9/></2> changed, as it<7/> can happen';
        $translated = 'This now is a somehow longer sentence,</1> that is <2>really completely changed, as it<7/> can happen';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair17(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>This now is a somehow longer sentence,</1> that is <2>really completely<9/></2> <7/>changed, as it can happen';
        $translated = 'This now is a somehow longer sentence, that is <2>really completely changed, as it can happen';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair18(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>This now is a somehow longer sentence,</1> that is really completely <7/>changed, as it <2>can happen<9/></2>';
        $translated = 'This now is a somehow longer sentence, that is really completely changed, as it can happen';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair19(): void
    {
        $markup = ' <1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2> ';
        $expected = '<1><8/> a a a a a a a a a a a a a a a a a a a a a a</1> a a a a a a a a a a a a a a a a a a a <7/>a a a a a a a <2>a a a a a a a a<9/></2> a ';
        $translated = ' a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a ';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair20(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>Hier     ist eine      Menge Whitespace</1>     drin,  auch wenn das      <7/>eigentlich <2>nicht vorkommt!<9/></2>';
        $translated = 'Hier     ist eine      Menge Whitespace     drin,  auch wenn das      eigentlich nicht vorkommt!';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagCommentRepair0(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1><10/><6/> der übersetzt<7/> <11/>werden <2><12/>muss<9/></2>';
        $this->createCommentStripTest($markup);
    }

    public function testTagCommentRepair1(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1><10/><6/> der übersetzt<7/> <11/>werden <2><12/>muss<9/></2>';
        $this->createCommentStripTest($markup);
    }

    public function testTagCommentRepair2(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1><10/><6/> der übersetzt<7/> <11/>werden <2><12/>muss<9/></2>';
        $this->createHtmlTagsRepairTest($markup, $markup, $markup, true);
    }

    public function testTagCommentRepair3(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1><10/><6/> der übersetzt<7/> <11/>werden <2><12/>muss<9/></2>';
        $expected = '<1><8/>Ein kurzer Satz,</1><10/><6/> der übersetzt<7/> <11/>werden <2><12/>muss<9/></2>';
        $translated = 'Ein kurzer Satz, der übersetzt werden muss';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true);
    }

    public function testTagCommentRepair4(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2><12/>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to <2><12/>be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that has to be</2> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true);
    }

    public function testTagCommentRepair5(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2><12/>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to <2><12/>be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that has to be</2><12/> translated<7/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true);
    }

    public function testTagCommentRepair6(): void
    {
        $markup = 'Ein <1>kurzer Satz<12/>, der übersetzt<11/> werden</1> muss';
        $expected = 'A <1>short sentence<12/>, that has to<11/> be </1>translated';
        $translated = 'A short sentence<12/>, that has to be translated';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true);
    }

    public function testInternalTagRepair1(): void
    {
        $markup = '<div class="open 64697620636c6173733d2265672d636f6e74656e742d656469746f722220646174612d747970653d22646f63756d656e7422 internal-tag ownttip"><span title="&lt;div class=&quot;eg-content-editor&quot; data-type=&quot;document&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;div class=&quot;eg-content-editor&quot; data-type=&quot;document&quot;&gt;</span></div><div class="open 64697620636c6173733d22636f6c756d6e22 internal-tag ownttip"><span title="&lt;div class=&quot;column&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;div class=&quot;column&quot;&gt;</span></div><div class="open 64697620636c6173733d22726f7720646f63756d656e742d656469746f722220646174612d636f6e74656e742d747970653d22446f63756d656e74456469746f7222 internal-tag ownttip"><span title="&lt;div class=&quot;row document-editor&quot; data-content-type=&quot;DocumentEditor&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;div class=&quot;row document-editor&quot; data-content-type=&quot;DocumentEditor&quot;&gt;</span></div><div class="open 646976 internal-tag ownttip"><span title="&lt;div&gt;" class="short">&lt;4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;div&gt;</span></div><div class="open 64697620636c6173733d22646f63756d656e742d636f6e7461696e65722220646174612d646f63756d656e742d69643d2264346631656339662d636438322d343064652d623533352d3961663835323566643732352220646174612d646f63756d656e742d747970653d227064662220646174612d646f63756d656e742d73697a653d2234383630383822 internal-tag ownttip"><span title="&lt;div class=&quot;document-container&quot; data-document-id=&quot;d4f1ec9f-cd82-40de-b535-9af8525fd725&quot; data-document-type=&quot;pdf&quot; data-document-size=&quot;486088&quot;&gt;" class="short">&lt;5&gt;</span><span data-originalid="5" data-length="-1" class="full">&lt;div class=&quot;document-container&quot; data-document-id=&quot;d4f1ec9f-cd82-40de-b535-9af8525fd725&quot; data-document-type=&quot;pdf&quot; data-document-size=&quot;486088&quot;&gt;</span></div><div class="open 64697620636c6173733d22646f63756d656e742d696e666f22 internal-tag ownttip"><span title="&lt;div class=&quot;document-info&quot;&gt;" class="short">&lt;6&gt;</span><span data-originalid="6" data-length="-1" class="full">&lt;div class=&quot;document-info&quot;&gt;</span></div><div class="open 64697620636c6173733d22646f63756d656e742d7469746c652d7772617070657222 internal-tag ownttip"><span title="&lt;div class=&quot;document-title-wrapper&quot;&gt;" class="short">&lt;7&gt;</span><span data-originalid="7" data-length="-1" class="full">&lt;div class=&quot;document-title-wrapper&quot;&gt;</span></div><div class="open 64697620636c6173733d22646f63756d656e742d7469746c6522 internal-tag ownttip"><span title="&lt;div class=&quot;document-title&quot;&gt;" class="short">&lt;8&gt;</span><span data-originalid="8" data-length="-1" class="full">&lt;div class=&quot;document-title&quot;&gt;</span></div>Kurzbericht_20-001574-PR01<div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/8&gt;</span><span data-originalid="8" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/7&gt;</span><span data-originalid="7" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="open 64697620636c6173733d22646f776e6c6f61642d646f63756d656e742d62746e2d636f6e7461696e657222 internal-tag ownttip"><span title="&lt;div class=&quot;download-document-btn-container&quot;&gt;" class="short">&lt;9&gt;</span><span data-originalid="9" data-length="-1" class="full">&lt;div class=&quot;download-document-btn-container&quot;&gt;</span></div><div class="single 6120687265663d2268747470733a2f2f6d656469612e6561737967656e657261746f722e636f6d2f6170692f6d656469612f646f63756d656e742f64346631656339662d636438322d343064652d623533352d3961663835323566643732352f636f6e74656e7422207461726765743d225f626c616e6b2220646f776e6c6f61643d22646f63756d656e742220636c6173733d22646f776e6c6f61642d646f63756d656e742d62746e222f internal-tag ownttip"><span title="&lt;a href=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/content&quot; target=&quot;_blank&quot; download=&quot;document&quot; class=&quot;download-document-btn&quot;/&gt;" class="short">&lt;10/&gt;</span><span data-originalid="10" data-length="-1" class="full">&lt;a href=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/content&quot; target=&quot;_blank&quot; download=&quot;document&quot; class=&quot;download-document-btn&quot;/&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/9&gt;</span><span data-originalid="9" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/6&gt;</span><span data-originalid="6" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="open 64697620636c6173733d22646f63756d656e742d626c6f636b2d696672616d652d7772617070657222 internal-tag ownttip"><span title="&lt;div class=&quot;document-block-iframe-wrapper&quot;&gt;" class="short">&lt;11&gt;</span><span data-originalid="11" data-length="-1" class="full">&lt;div class=&quot;document-block-iframe-wrapper&quot;&gt;</span></div><div class="single 696672616d652077696474683d223130302522206865696768743d22353530707822206672616d65626f726465723d22302220636c6173733d22646f63756d656e742d626c6f636b2d696672616d6522207372633d2268747470733a2f2f6d656469612e6561737967656e657261746f722e636f6d2f6170692f6d656469612f646f63756d656e742f64346631656339662d636438322d343064652d623533352d3961663835323566643732352f65787465726e616c2d70726576696577222f internal-tag ownttip"><span title="&lt;iframe width=&quot;100%&quot; height=&quot;550px&quot; frameborder=&quot;0&quot; class=&quot;document-block-iframe&quot; src=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/external-preview&quot;/&gt;" class="short">&lt;12/&gt;</span><span data-originalid="12" data-length="-1" class="full">&lt;iframe width=&quot;100%&quot; height=&quot;550px&quot; frameborder=&quot;0&quot; class=&quot;document-block-iframe&quot; src=&quot;https://media.easygenerator.com/api/media/document/d4f1ec9f-cd82-40de-b535-9af8525fd725/external-preview&quot;/&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/11&gt;</span><span data-originalid="11" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/5&gt;</span><span data-originalid="5" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/2&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;/div&gt;</span></div><div class="close 2f646976 internal-tag ownttip"><span title="&lt;/div&gt;" class="short">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/div&gt;</span></div>';
        $this->createRealInternalTagsRepairTest($markup);
    }

    public function testInternalTagRepair2(): void
    {
        $markup = '<div></div>';
        $this->createRealInternalTagsRepairTest($markup);
    }

    public function testInternalTagRepair3(): void
    {
        $markup = 'This <1>is</1> a <2>sentence that</2> needs to be translated. <3>It</3> consists of two sentences, <4>with</4> some interpunctuation and some more words, just to have something to <5>translate</5>.';
        $expectedStripped = 'This <1>is</1> a <2>sentence that</2> needs to be translated. <3>It</3> consists of two sentences, <4>with</4> some interpunctuation and some more words, just to have something to <5>translate.</5>';
        $this->createInternalTagsRepairTest($markup, '', $expectedStripped);
    }

    public function testInternalTagRepair4(): void
    {
        $markup = 'This <1>is</1> a <2>sentence that</2> needs to be translated. <3>It</3> consists<10/> of two sentences, <4>with</4> some interpunctuation and some more words, just to have <11/><5>something</5> to translate.';
        $this->createInternalTagsRepairTest($markup, '');
    }

    public function testInternalTagRepair5(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some interpunctuation and some more words, just to have <11/><5>something</5> to translate.';
        $this->createInternalTagsRepairTest($markup, '');
    }

    public function testInternalTagRepair6(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $this->createInternalTagsRepairTest($markup, '');
    }

    public function testInternalTagRepair7(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $translatedMarkup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $this->createInternalTagsRepairTest($markup, '', '', $translatedMarkup);
    }

    public function testInternalTagRepair8(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $translatedMarkup = 'This <6>is</1> a <2>sentence that</6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $this->createInternalTagsRepairTest($markup, '', '', $translatedMarkup);
    }

    public function testInternalTagRepair9(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $translatedMarkup = 'This <6>is</1> a <2>sentence that</6> needs to be translated. <7>It</3> consists</7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $this->createInternalTagsRepairTest($markup, '', '', $translatedMarkup);
    }

    public function testInternalTagRepair10(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $translatedMarkup = 'This <6>is</1> a <2>sentence that</6> needs to be translated. <7>It</3> consists</7> of two sentences, with</4> some <8>interpunctuation and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $this->createInternalTagsRepairTest($markup, '', '', $translatedMarkup);
    }

    public function testInternalTagRepair11(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $translatedMarkup = 'This <6>is</1> a <2>sentence that</6> needs to be translated. <7>It</3> consists</7> of two sentences, with</4> some <8>interpunctuation and some more words, just to have <5><9>something</9></5> to translate.';
        $this->createInternalTagsRepairTest($markup, '', '', $translatedMarkup);
    }

    public function testInternalTagRepair12(): void
    {
        $markup = 'This <6><1>is</1> a <2>sentence that</2></6> needs to be translated. <7><3>It</3> consists<10/></7> of two sentences, <4>with</4> some <8>interpunctuation</8> and some more words, just to have <11/><5><9>something</9></5> to translate.';
        $translatedMarkup = 'This <6>is</1> a <2>sentence that</6> needs to be translated. <7>It</3> consists</7> of two sentences, with</4> some <8>interpunctuation and some more words, just to have <5>something</9> to translate.';
        $this->createInternalTagsRepairTest($markup, '', '', $translatedMarkup);
    }

    public function testStartingEndingTagCount(): void
    {
        $this->assertEquals(2, Tag::countImgTagsOnlyStartOrEnd('<img src="test"/><img src="test"/>Lorem ipsum sit amet'));
        $this->assertEquals(-3, Tag::countImgTagsOnlyStartOrEnd('Lorem ipsum sit amet <img src="test"/><img src="test"/><img src="test"/>'));
        $this->assertEquals(-1, Tag::countImgTagsOnlyStartOrEnd('Lorem ipsum sit amet <img src="test"/>'));
        $this->assertEquals(0, Tag::countImgTagsOnlyStartOrEnd('<img src="test"/><img src="test"/>Lorem ipsum sit amet<img src="test"/>'));
        $this->assertEquals(0, Tag::countImgTagsOnlyStartOrEnd('<img src="test"/><img src="test"/>Lorem ipsum <img src="test"/> sit amet'));
        $this->assertEquals(0, Tag::countImgTagsOnlyStartOrEnd(' <img src="test"/>Lorem ipsum sit amet'));
    }

    public function testDetectUntranslated1(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<9/>Ein kurzer Satz, der übersetzt<7/></1> werden muss<8/></2><2>';
        $this->createHtmlTagsRepairTest($markup, $markup, $translated, true, true);
    }

    public function testDetectUntranslated2(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '  Ein  kurzer Satz,   der' . "\t" . 'übersetzt <7/>' . "\n" . '</1> werden  muss<8/> </2> <2> ';
        $this->createHtmlTagsRepairTest($markup, $markup, $translated, true, true);
    }

    public function testRepairClusteredTags1(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to<7/> be <2>translated<9/></2>';
        $translated = '<1></1><2></2><7/><8/><9/>A short sentence, that has to be translated';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true, true);
    }

    public function testRepairClusteredTags2(): void
    {
        $markup = '<8/>Ein <1>kurzer Satz,</1> der übersetzt werden muss';
        $expected = '<8/>A <1>short sentence,</1> that has to be translated';
        $translated = '<1></1><8/>A short sentence, that has to be translated';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true, true);
    }

    public function testRepairClusteredTags3(): void
    {
        // this should actually not detect as the cluster-size is to small ...
        $markup = '<1>Ein kurzer Satz</1>, der übersetzt werden muss';
        $expected = '<1></1>A short sentence, that has to be translated';
        $this->createHtmlTagsRepairTest($markup, $expected, $expected, true, true);
    }

    public function testRepairClusteredTags4(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to<7/> be <2>translated<9/></2>';
        $translated = 'A short sentence, that has to be translated<1></1><2></2><7/><8/><9/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true, true);
    }

    public function testRepairClusteredTags5(): void
    {
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence, that has to be translated</1><7/><2><9/></2>';
        $translated = '<1>A short sentence, that has to be translated</1><2></2><7/><8/><9/>';
        $this->createHtmlTagsRepairTest($markup, $expected, $translated, true, true);
    }

    /**
     * @throws ZfExtended_Exception
     */
    protected function createHtmlTagsRepairTest(
        string $originalMarkup,
        string $expectedMarkup,
        string $translatedMarkup,
        bool $preserveComments = false,
        bool $detectUntranslated = false,
    ): void {
        $markup = $this->replaceHtmlTags($originalMarkup);
        $tags = new Tags($markup, $preserveComments);
        $expected = (empty($expectedMarkup)) ? $tags->render() : $this->replaceHtmlTags($expectedMarkup);
        $request = $tags->getRequestHtml();
        $translated = $this->replaceRequestTags($translatedMarkup, $originalMarkup, $request);
        $actual = $tags->recreateTags($translated, $detectUntranslated);

        // debugging @phpstan-ignore-next-line
        if (self::DO_DEBUG) {
            error_log('===================' . "\n");
            error_log('ORIGINAL:' . "\n" . $originalMarkup . "\n");
            error_log('TRANSLATED:' . "\n" . $translatedMarkup . "\n");
            error_log('RECREATED:' . "\n" . $this->revertHtmlTags($actual) . "\n");
            error_log('===================' . "\n" . "\n");
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ZfExtended_Exception
     */
    protected function createInternalTagsRepairTest(
        string $originalMarkup,
        string $expectedMarkup = '',
        string $expectedStripped = '',
        string $translatedMarkup = '',
    ): void {
        $originalMarkupReplaced = $this->replaceInternalTags($originalMarkup);
        $expectedMarkup = ($expectedMarkup == '') ? '' : $this->replaceInternalTags($expectedMarkup);
        $expectedStripped = ($expectedStripped == '') ? '' : $this->replaceInternalTags($expectedStripped);

        $this->createRealInternalTagsRepairTest(
            $originalMarkupReplaced,
            $expectedMarkup,
            $expectedStripped,
            $translatedMarkup,
            $originalMarkup
        );
    }

    /**
     * @throws ZfExtended_Exception
     */
    protected function createRealInternalTagsRepairTest(
        string $originalMarkup,
        string $expectedMarkup = '',
        string $expectedStripped = '',
        string $translatedMarkup = '',
        string $unreplacedOriginal = '',
    ): void {
        $tags = new Tags($originalMarkup, true);
        $expected = (empty($expectedMarkup)) ? $tags->render() : $expectedMarkup;
        $request = $tags->getRequestHtml();
        if (! empty($translatedMarkup)) {
            $request = $this->replaceRequestTags($translatedMarkup, $unreplacedOriginal, $request);
        }
        // we will not detect untranslated requests because most of our tests use untranslated markup
        $actual = $tags->recreateTags($request, false);
        // debugging @phpstan-ignore-next-line
        if (self::DO_DEBUG) {
            error_log('===================' . "\n");
            error_log('ORIGINAL:' . "\n" . $this->revertInternalTags($originalMarkup) . "\n");
            error_log('REQUEST:' . "\n" . $request . "\n");
            error_log('RECREATED:' . "\n" . $this->revertInternalTags($actual) . "\n");
            error_log('===================' . "\n\n");
        }
        $this->assertEquals($expected, $actual);
        // test stripped request
        if ($expectedStripped != 'NO') {
            $strippedRequest = Markup::strip($request);
            $actual = $tags->recreateTags($strippedRequest, false);
            // debugging @phpstan-ignore-next-line
            if (self::DO_DEBUG) {
                error_log('===================' . "\n");
                error_log('ORIGINAL:' . "\n" . $this->revertInternalTags($originalMarkup) . "\n");
                error_log('REQUEST:' . "\n" . $strippedRequest . "\n");
                error_log('RECREATED:' . "\n" . $this->revertInternalTags($actual) . "\n");
                error_log('===================' . "\n\n");
            }
            if (empty($expectedStripped)) {
                $this->assertEquals($expected, $actual);
            } else {
                $this->assertEquals($expectedStripped, $actual);
            }
        }
    }

    protected function createCommentStripTest(string $originalMarkup): void
    {
        $markup = $this->replaceHtmlTags($originalMarkup);
        $strippedMarkup = $this->replaceHtmlTags(preg_replace('~<(10|11|12)/>~i', '', $originalMarkup));
        $this->assertEquals($strippedMarkup, Tag::stripComments($markup));
    }

    /**
     * Replaces short html tags with real html tags
     */
    private function replaceHtmlTags(string $markup): string
    {
        foreach (array_keys($this->htmlTags) as $key) {
            $markup = str_replace($key, $this->htmlTags[$key], $markup);
        }

        return $markup;
    }

    private function revertHtmlTags(string $markup): string
    {
        foreach (array_keys($this->htmlTags) as $key) {
            $markup = str_replace($this->htmlTags[$key], $key, $markup);
        }

        return $markup;
    }

    private function replaceRequestTags(string $markup, string $original, string $request): string
    {
        $pattern = '~(<[^ ][^>]*>)~i';
        $originalParts = preg_split($pattern, $original, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $requestParts = preg_split($pattern, $request, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $num = count($originalParts);
        for ($i = 0; $i < $num; $i++) {
            if (preg_match($pattern, $originalParts[$i]) === 1) {
                $markup = str_replace($originalParts[$i], $requestParts[$i], $markup);
            }
        }

        return $markup;
    }

    /**
     * Replaces short internal tags with real internal tags
     */
    private function replaceInternalTags(string $markup): string
    {
        foreach (array_keys($this->internalTags) as $key) {
            $markup = str_replace($key, $this->internalTags[$key], $markup);
        }

        return $markup;
    }

    private function revertInternalTags(string $markup): string
    {
        foreach (array_keys($this->internalTags) as $key) {
            $markup = str_replace($this->internalTags[$key], $key, $markup);
        }

        return $markup;
    }
}
