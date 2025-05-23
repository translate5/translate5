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

use editor_Models_Segment_TrackChangeTag;
use editor_Segment_FieldTags;
use editor_Segment_NewlineTag;
use editor_Segment_Tag;
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several "classic" PHPUnit tests to check the FieldTags Cloning without TrackChanges tags
 * TODO: create test with additional quality-tags e.g. MQM
 */ class SegmentTagsCloneTest extends SegmentTagsTestAbstract
{
    /**
     * Some Internal Tags to create Tests with
     */
    protected array $testTags = [
        '<1>' => '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;1&gt;</span><span class="full" data-originalid="123" data-length="-1">TEST</span></div>',
        '</1>' => '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/1&gt;</span><span class="full" data-originalid="123" data-length="-1">TEST</span></div>',
        '<2>' => '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;2&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>',
        '</2>' => '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/2&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>',
        '<3>' => '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;3&gt;</span><span class="full" data-originalid="125" data-length="-1">TEST</span></div>',
        '</3>' => '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/3&gt;</span><span class="full" data-originalid="125" data-length="-1">TEST</span></div>',
        '<4>' => '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;4&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST</span></div>',
        '</4>' => '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/4&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST</span></div>',
        '<5/>' => '<div class="single tab internal-tag ownttip"><span class="short" title="&lt;5/&gt;: 1 tab character">&lt;5/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div>',
        '<6/>' => '<div class="single internal-tag ownttip"><span class="short" title="&lt;char name=&quot;Indent&quot;/&gt;">&lt;6/&gt;</span><span class="full" data-originalid="259" data-length="-1">&lt;char name=&quot;Indent&quot;/&gt;</span></div>',
        '<7/>' => '<div class="single newline internal-tag ownttip"><span class="short" title="&lt;7/&gt;: Newline">&lt;7/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>',
        '<8/>' => '<div class="single newline internal-tag ownttip"><span class="short" title="&lt;8/&gt;: Newline">&lt;8/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>',
        '<9/>' => '<div class="single newline internal-tag ownttip"><span class="short" title="&lt;9/&gt;: Newline">&lt;9/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>',
    ];

    private string $insX = '<ins class="trackchanges ownttip" data-usertrackingid="1868" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2021-05-27T20:04:17+02:00">';

    private string $delX = '<del class="trackchanges ownttip deleted" data-usertrackingid="1868" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2021-05-27T22:51:24+02:00">';

    public function testEmptyMarkup(): void
    {
        $markup = '';
        $expected = '';
        $this->createTrackChangesCloneTest($expected, $markup);
    }

    public function testSimpleMarkup1(): void
    {
        // testing srings without any tags
        $markup = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        $expected = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }

    public function testSimpleMarkup2(): void
    {
        // testing srings without any tags
        $markup = '<del>Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</del> Sed diam nonumy eirmod tempor invidunt ut labore et<ins> dolore magna aliquyam erat</ins>, sed <del>diam</del> voluptua.';
        $expected = ' Sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }

    public function testSimpleMarkup3(): void
    {
        // testing srings without any tags
        $markup = '<del>Lorem ipsum dolor <del>sit amet, consetetur</del> sadipscing elitr. </del>Sed diam nonumy eirmod tempor invidunt ut labore et<ins> dolore magna aliquyam erat</ins>, sed diam voluptua.';
        $expected = 'Sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        // NOTE: since we have nested-del tags here (should not happen in real content), the RegEx Code will not be able to cope with this and must be skipped
        $this->createTrackChangesCloneTest($expected, $markup, false);
    }

    public function testSimpleMarkup4(): void
    {
        // testing srings without any tags
        $markup = '<del>Lorem ipsum dolor sit amet, consetetur sadipscing elitr. </del>Sed diam nonumy eirmod tempor invidunt ut labore et<ins> dolore magna aliquyam erat</ins>, sed<del> diam voluptua</del>.';
        $expected = 'Sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }

    public function testSimpleMarkup5(): void
    {
        // testing srings without any tags
        $markup = 'Lorem ipsum dolor sit <ins>amet</ins>, consetetur <ins>sadipscing</ins> elitr, sed diam nonumy eirmod tempor invi<del>dunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</del>';
        $expected = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invi';
        $this->createTrackChangesCloneTest($expected, $markup);
    }

    public function testMarkup1(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <1>ipsum dolor</1> sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $expected = 'Lorem <1>ipsum dolor</1> sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup2(): void
    {
        $markup = 'Lorem <1>ipsum</1> dolor sit amet, <del><2>consetetur sadipscing<5/></2></del> elitr, sed diam <ins>nonumy eirmod tempor</ins> <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $expected = 'Lorem <1>ipsum</1> dolor sit amet, elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup3(): void
    {
        $markup = 'Lorem <1>ipsum <del>dolor</del></1> <del>sit</del> amet, <2><del>consetetur sadipscing<5/></del></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> <del>labore et <4>dolore magna</4> aliquyam erat</del></3>, sed diam voluptua.';
        $expected = 'Lorem <1>ipsum </1> amet, <2></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> </3>, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup4(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <2>consetetur sadipscing<del><5/></del></2> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>,</del> sed diam voluptua.';
        $expected = 'Lorem sit amet, <2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <4>dolore magna</4> aliquyam erat</3> sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup5(): void
    {
        // testing content without ins/del
        $markup = '<del><1>Lorem ipsum dolor</1> sit amet, </del><2>consetetur sadipscing<del><5/></del></2> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>,</del> sed diam voluptua.';
        $expected = '<2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <4>dolore magna</4> aliquyam erat</3> sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup6(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <2>consetetur sadipscing<del><5/></del></2> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>, sed diam voluptua.</del><7/>';
        $expected = 'Lorem sit amet, <2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <4>dolore magna</4> aliquyam erat</3><7/>';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup7(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <2>consetetur sadipscing<del><5/></del></2> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>, sed diam voluptua.<7/></del>';
        $expected = 'Lorem sit amet, <2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <4>dolore magna</4> aliquyam erat</3>';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup8(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <del><2>consetetur sadipscing<5/></2></del> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>, sed</del> diam voluptua.';
        $expected = 'Lorem sit amet, elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <4>dolore magna</4> aliquyam erat</3> diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkupMultipleBlanks(): void
    {
        // testing content without ins/del
        $markup = 'Lorem  <del><1>ipsum dolor</1></del> sit amet,    <del><2>consetetur sadipscing<5/></2></del>    elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins>   <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>, sed</del> diam voluptua.';
        $expected = 'Lorem sit amet, elitr, sed diam nonumy eirmod tempor <3>invidunt   <6/> labore et <4>dolore magna</4> aliquyam erat</3> diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testLines1(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <del><2>consetetur sadipscing<5/></2></del> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><8/>dolore magna</ins> aliquyam erat</3><del>, sed</del> diam<9/> voluptua.';
        $expected = 'Lorem sit amet, elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <8/>dolore magna aliquyam erat</3> diam<9/> voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createMarkupLinesTest($expected, $markup);
    }

    public function testLines2(): void
    {
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <2>consetetur<8/> sadipscing<del><5/></del></2> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><9/><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>, sed diam voluptua.<7/></del>';
        $expected = 'Lorem sit amet, <2>consetetur<8/> sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt <9/><6/> labore et <4>dolore magna</4> aliquyam erat</3>';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createMarkupLinesTest($expected, $markup);
    }

    public function testLines3(): void
    {
        // testing content without ins/del
        $markup = 'Lorem  <del><1>ipsum dolor</1></del> sit amet,    <del><2>consetetur sadipscing<5/></2></del>    elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins>   <del>ut</del><6/> labore et <ins><8/>dolore magna</ins> aliquyam erat</3><del>, <9/>sed</del> diam voluptua.<7/>';
        $expected = 'Lorem sit amet, elitr, sed diam nonumy eirmod tempor <3>invidunt   <6/> labore et <8/>dolore magna aliquyam erat</3> diam voluptua.<7/>';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createMarkupLinesTest($expected, $markup);
    }

    /**
     * Creates a test for the tags cloning. The passed markup will have the following short-tags replaced with "real" internal tags
     */
    private function createTrackChangesCloneTest(string $expected, string $markup, bool $testAgainstRegEx = true): void
    {
        $markupConverted = $this->replaceTags($markup);
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $markupConverted, 'target', 'targetEdit');
        $markupRendered = $markupTags->render();
        $this->assertEquals($markupConverted, $markupRendered);
        $markupUnconverted = $this->revertTags($markupRendered);
        $this->assertEquals($markup, $markupUnconverted);
        // create clone without trackchanges
        $markupTagsNoTrackChanges = $markupTags->cloneWithoutTrackChanges();
        // process the expectation
        $expectedConverted = $this->replaceTags($expected);
        $expectedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $expectedConverted, 'target', 'targetEdit');
        // render the cloned tags
        $renderedCloned = $markupTagsNoTrackChanges->render();
        // revert the structure to a human readable form
        $reconvertedCloned = $this->revertTags($renderedCloned);
        // compare in various ways
        $this->assertEquals($expected, $reconvertedCloned);
        $this->assertEquals($expectedConverted, $renderedCloned);
        $this->assertEquals($expectedTags->render(), $renderedCloned);
        $this->assertEquals($expectedTags->getFieldText(), $markupTags->getFieldText(true));
        // ther order in the cloned json still has the old values, so we ignore the ordering
        $this->assertEquals($this->cleanOrderInJSON($expectedTags->toJson()), $this->cleanOrderInJSON($markupTagsNoTrackChanges->toJson()));
        // make sure the original tags do not become manipulated.
        $this->assertEquals($markupConverted, $markupTags->render());
        if ($testAgainstRegEx) {
            // pitch the new trackchanges removal against the old regex implementation
            $remover = new editor_Models_Segment_TrackChangeTag();
            $this->assertEquals($renderedCloned, $remover->removeTrackChanges($markupConverted));
        }
    }

    /**
     * Creates a test for the tags cloning with filtering for internal tags only
     * The passed markup will have the following short-tags replaced with "real" internal tags
     */
    private function createTrackChangesFilterCloneTest(string $expected, string $markup): void
    {
        // we filter for internal tags only
        $filter = [editor_Segment_Tag::TYPE_INTERNAL];
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->replaceTags($markup), 'target', 'targetEdit');
        // a full clone without filter
        $markupTagsCloned = $markupTags->cloneFiltered();
        // create clone without trackchanges and only filtered tags
        $markupTagsNoTrackChanges = $markupTags->cloneWithoutTrackChanges($filter);
        // also process the expectation
        $expectedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->replaceTags($expected), 'target', 'targetEdit');
        // create expected clone and only filtered tags
        $expectedTags = $expectedTags->cloneFiltered($filter);
        // compare
        $this->assertEquals($expectedTags->render(), $markupTagsNoTrackChanges->render());
        $this->assertEquals($this->cleanOrderInJSON($expectedTags->toJson()), $this->cleanOrderInJSON($markupTagsNoTrackChanges->toJson()));
        $this->assertEquals($expectedTags->getFieldText(), $markupTags->getFieldText(true));
        // compare full clone
        $this->assertEquals($markupTags->render(), $markupTagsCloned->render());
        $this->assertEquals($this->cleanOrderInJSON($markupTags->toJson()), $this->cleanOrderInJSON($markupTagsCloned->toJson()));
    }

    /**
     * Creates a test for the tags cloning with filtering for MQM tags only, what will effectively remove all tags as we only have internal tags
     * The passed markup will have the following short-tags replaced with "real" internal tags
     */
    private function createTrackChangesMqmFilterCloneTest(string $expected, string $markup): void
    {
        // we filter for internal tags only
        $filter = [editor_Segment_Tag::TYPE_MQM];
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->replaceTags($markup), 'target', 'targetEdit');
        // a full clone without filter
        $markupTagsCloned = $markupTags->cloneFiltered();
        // Remove all tags from the full Clone
        $markupTagsNoTags = $markupTags->cloneWithoutTrackChanges($filter);
        // also process the expectation
        $expectedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->replaceTags($expected), 'target', 'targetEdit');
        // create expected clone and only filtered tags
        $expectedTags = $expectedTags->cloneFiltered($filter);
        // compare expected. Note, we cann't compare the whitespace as the cloned expected tags still have multiple blanks
        $this->assertEquals(preg_replace('~ +~', ' ', $expectedTags->render()), $markupTagsNoTags->render());
        // compare full clone
        $this->assertEquals($markupTags->render(), $markupTagsCloned->render());
        $this->assertEquals($this->cleanOrderInJSON($markupTags->toJson()), $this->cleanOrderInJSON($markupTagsCloned->toJson()));
    }

    /**
     * Test the ::getFieldTextLines API of the Fieldtags (which uses the clone-API internally)
     */
    private function createMarkupLinesTest(string $expected, string $markup): void
    {
        $markupConverted = $this->replaceTags($markup);
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $markupConverted, 'target', 'targetEdit');
        $expectedMarkup = $this->replaceNewlineTags($expected);
        $this->assertEquals(explode(editor_Segment_NewlineTag::RENDERED, $expectedMarkup), $markupTags->getFieldTextLines(true));
    }

    /**
     * Removes the order / parentOrder props in the json-data as they are not sequenced (but valid!) in the cloned tags
     */
    private function cleanOrderInJSON(string $json): string
    {
        $json = preg_replace('/"parentOrder":[0-9]+,/', '"parentOrder":-1,', $json);

        return preg_replace('/"order":[0-9]+,/', '"order":-1,', $json);
    }

    private function replaceTags(string $markup): string
    {
        $markup = $this->shortToFull($markup);
        $markup = $this->replaceInsDelTags($markup);

        return $markup;
    }

    private function revertTags(string $markup): string
    {
        $markup = $this->fullToShort($markup);
        $markup = $this->revertInsDelTags($markup);

        return $markup;
    }

    private function replaceInsDelTags(string $markup): string
    {
        $markup = $this->replaceMultipleTags($markup, '<ins>', $this->insX);
        $markup = $this->replaceMultipleTags($markup, '<del>', $this->delX);

        return $markup;
    }

    /**
     * Replaces multiple ins/del tags and fills them with correct numberings
     */
    private function replaceMultipleTags(string $markup, string $search, string $replace): string
    {
        $count = -1;
        $result = preg_replace_callback('~' . $search . '~', function ($matches) use ($count, $replace) {
            $count++;

            return str_replace('{X}', (string) $count, $replace);
        }, $markup);

        return $result;
    }

    private function revertInsDelTags(string $markup): string
    {
        $markup = preg_replace('~<ins[^>]+>~', '<ins>', $markup);
        $markup = preg_replace('~<del[^>]+>~', '<del>', $markup);

        return $markup;
    }

    /**
     * Replaces all Internal Linebreak tags with Linebreaks, removes all other tags
     */
    private function replaceNewlineTags(string $markup): string
    {
        $markup = str_replace('<7/>', editor_Segment_NewlineTag::RENDERED, $markup);
        $markup = str_replace('<8/>', editor_Segment_NewlineTag::RENDERED, $markup);
        $markup = str_replace('<9/>', editor_Segment_NewlineTag::RENDERED, $markup);
        $markup = preg_replace('~</*[1-6]/*>~', '', $markup);

        return $markup;
    }
}
