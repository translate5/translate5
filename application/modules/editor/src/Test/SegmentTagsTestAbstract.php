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

namespace MittagQI\Translate5\Test;

use editor_Segment_FieldTags;
use editor_Segment_Tag;
use MittagQI\Translate5\Tag\TagSequence;

/**
 * Abstraction layer for performing API tests which involve comparing Segment Texts.
 * This solves the problem, that Tags in segment text are enriched with quality-id's in some cases that contain auto-increment id's and thus have to be stripped
 * Also, the attributes in tags may be in a different order because historically there have been different attribute orders for differen tags
 */
abstract class SegmentTagsTestAbstract extends MockedTaskTestAbstract
{
    /* abstract helper-classs to easily create tests for segment tags */

    /**
     * Some Internal Tags to create Tests with
     */
    protected array $testTags = [
        '<1>' => '<div class="open 54455354 internal-tag ownttip"><span class="short" title="TEST">&lt;1&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>',
        '</1>' => '<div class="close 54455354 internal-tag ownttip"><span class="short" title="TEST">&lt;/1&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>',
        '<2>' => '<div class="open 54455354 internal-tag ownttip"><span class="short" title="TEST">&lt;2&gt;</span><span class="full" data-originalid="125" data-length="-1">TEST</span></div>',
        '</2>' => '<div class="close 54455354 internal-tag ownttip"><span class="short" title="TEST">&lt;/2&gt;</span><span class="full" data-originalid="125" data-length="-1">TEST</span></div>',
        '<3/>' => '<div class="single 313930 number internal-tag ownttip"><span class="short" title="&amp;lt;3/&amp;gt;: Number">&lt;3/&gt;</span><span class="full" data-originalid="number" data-length="3" data-source="190" data-target="190"></span></div>',
        '<4/>' => '<div class="single 3c63686172206e616d653d22496e64656e74222f3e internal-tag ownttip"><span class="short" title="&lt;char name=&quot;Indent&quot;/&gt;">&lt;4/&gt;</span><span class="full" data-originalid="259" data-length="-1">&lt;char name=&quot;Indent&quot;/&gt;</span></div>',
        '<5/>' => '<div class="single 70682069643d2233223ee280a63c2f7068 internal-tag ownttip"><span class="short" title="…">&lt;5/&gt;</span><span class="full" data-originalid="131" data-length="-1">…</span></div>',
        '<ins1>' => '<ins class="trackchanges ownttip" title="ins1" data-usertrackingid="12477" data-usercssnr="usernr1" data-workflowstep="review1ndlanguage1" data-timestamp="2022-03-11T11:13:07+02:00">',
        '<ins2>' => '<ins class="trackchanges ownttip" title="ins2" data-usertrackingid="35288" data-usercssnr="usernr3" data-workflowstep="review2ndlanguage1" data-timestamp="2022-03-13T09:45:48+02:00">',
        '<ins3>' => '<ins class="trackchanges ownttip" title="ins3" data-usertrackingid="2345" data-usercssnr="usernr2" data-workflowstep="no workflow1" data-timestamp="2023-11-03T12:33:09+02:00">',
        // all <dels> are newer than <ins> but not <del3>...
        '<del1>' => '<del class="trackchanges ownttip deleted" title="del1" data-usertrackingid="4270" data-usercssnr="usernr3" data-workflowstep="review1sttechnical4" data-timestamp="2024-07-05T14:14:44+02:00" data-historylist="1625486496000" data-action_history_1625486496000="INS" data-usertrackingid_history_1625486496000="4269">',
        '<del2>' => '<del class="trackchanges ownttip deleted" title="del2" data-usertrackingid="4987" data-usercssnr="usernr4" data-workflowstep="review1sttechnical2" data-timestamp="2024-08-05T14:14:45+02:00" data-historylist="5412486496000" data-action_history_4534486496000="INS" data-usertrackingid_history_4534486496000="7635">',
        '<del3>' => '<del class="trackchanges ownttip deleted" title="del3" data-usertrackingid="4993" data-usercssnr="usernr5" data-workflowstep="review1sttechnical8" data-timestamp="2020-08-05T14:14:46+02:00" data-historylist="1675237636565" data-action_history_4534486496000="INS" data-usertrackingid_history_4534486496000="5432">',
        '<term1>' => '<div class="term preferredTerm exact" title="term1" data-tbxid="71a5458c-c4b3-49e9-af3b-c8222b91275a">',
        '<term2>' => '<div class="term deprecatedTerm" title="term2" data-tbxid="81a5458c-c4b3-49e9-af3b-c6222b91275a">',
        '<term3>' => '<div class="term standardizedTerm" title="term3" data-tbxid="91a5458c-c4b3-49e9-af3b-c4222b91275a">',
    ];

    protected function createTags(): editor_Segment_FieldTags
    {
        $segmentId = 1234567;
        $segmentText = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.'; // 80 characters

        return new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $segmentText, 'target', 'targetEdit');
    }

    protected function createTagsTest(editor_Segment_FieldTags $tags, string $expectedMarkup): void
    {
        // compare rendered Markup
        $this->assertEquals($expectedMarkup, $tags->render());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
        // unparse test
        $unparseTags = new editor_Segment_FieldTags($this->getTestTask(), $tags->getSegmentId(), $tags->getFieldText(), $tags->getField(), $tags->getDataField());
        $unparseTags->unparse($expectedMarkup);
        $this->assertEquals($expectedMarkup, $unparseTags->render());
    }

    /**
     * @throws \Exception
     */
    protected function createDataTest(int $segmentId, string $markup): void
    {
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-texts vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }

    /**
     * @param string $original The Markup to compare against
     * @param string $markup The Markup to test
     * @param string|null $replacedLabeled If given, the labeled-rendering of the sequenced markup is compared
     * against this. "labeled" rendering replaces internal (whiotespace) tags with placeholders like
     * @throws \ZfExtended_Exception
     */
    protected function createOriginalDataTest(
        int $segmentId,
        string $original,
        string $markup,
        string $replacedLabeled = null,
    ): void {
        $originalTags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $original, 'target', 'targetEdit');
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-text original vs "sorted" markup
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        // compare field-text vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
        // rendering replaced stripped should match the field-text with trackchanges stripped!
        $this->assertEquals($tags->getFieldText(true), $tags->renderReplaced());
        // test the replaced rendering with the labeled whitespace placeholders
        if ($replacedLabeled !== null) {
            $this->assertEquals($replacedLabeled, $tags->renderReplaced(TagSequence::MODE_LABELED));
        }
    }

    protected function createMqmDataTest(int $segmentId, string $markup, string $compare = null): void
    {
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // compare unparsed markup
        if ($compare == null) {
            $this->assertEquals($markup, $tags->render());
        } else {
            // if the markup cpontaines invalid mqm we may need a special compare markup
            $this->assertEquals($compare, $tags->render());
        }
        // compare field-texts vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $tags->getFieldText());
        if ($compare != null) {
            $this->assertEquals(editor_Segment_Tag::strip($compare), $tags->getFieldText());
        }
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }

    /**
     * Creates a test for testing Track-Changes capabilities
     * @param string $markup The passed Markup is expected to have <ins> and <del>-tags
     * @param string $expectedTextNoTC The expected field-text after without track-changes
     * @throws \Exception
     */
    protected function createTrackChangesTest(
        int $segmentId,
        string $markup,
        string $expectedTextNoTC,
        string $expectedMarkupNoTC = null
    ): void {
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $markup, 'target', 'targetEdit');
        // make sure there are track-changes-tags in the test-scenario ...
        $this->assertNotEmpty($tags->getByType(editor_Segment_Tag::TYPE_TRACKCHANGES, true));
        $fieldTextBefore = $tags->getFieldText();
        $jsonBefore = $tags->toJson();
        // compare field-text vs. stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($markup), $fieldTextBefore);
        // get text without track-changes
        $fieldTextNoTC = $tags->getFieldText(true);
        $this->assertEquals($expectedTextNoTC, $fieldTextNoTC);
        // make sure this did not change the field-text
        $msg = 'Calling editor_Segment_FieldTags::getFieldText(true, true) changed the tags !';
        $this->assertEquals($fieldTextBefore, $tags->getFieldText(), $msg);
        $this->assertEquals($jsonBefore, $tags->toJson(), $msg);
        // clone without track-changes
        $tagsNoTC = $tags->cloneWithoutTrackChanges();
        $this->assertEquals($expectedTextNoTC, $tagsNoTC->getFieldText());
        // make sure, clone does not have track-changes
        $this->assertEmpty(
            $tagsNoTC->getByType(editor_Segment_Tag::TYPE_TRACKCHANGES, true),
            'Calling editor_Segment_FieldTags::cloneWithoutTrackChanges() did not remove all track-changes!'
        );
        if ($expectedMarkupNoTC !== null) {
            $this->assertEquals($expectedMarkupNoTC, $tagsNoTC->render());
        }
    }

    /**
     * Reverts double-encoding of the base XML entities
     * Currently unused
     */
    protected function unescapeDoubleEscaped(string $text): string
    {
        foreach (['lt', 'gt', 'quot', 'apos', 'amp'] as $entity) {
            $text = str_replace('&amp;' . $entity . ';', '&' . $entity . ';', $text);
        }

        return $text;
    }

    /**
     * Replaces short tags with real internal tags
     */
    protected function shortToFull(string $markup): string
    {
        foreach ($this->testTags as $short => $full) {
            $markup = str_replace($short, $full, $markup);
        }
        // end-tags
        $markup = preg_replace('~</ins[0-3]>~', '</ins>', $markup);
        $markup = preg_replace('~</del[0-3]>~', '</del>', $markup);
        $markup = preg_replace('~</term[0-3]?>~', '</div>', $markup);

        return $markup;
    }

    /**
     * Replaces full internal tags back to their short versions
     * This will and can not replace end-tags back, please keep that in mind!
     */
    protected function fullToShort(string $markup): string
    {
        foreach ($this->testTags as $short => $full) {
            $markup = str_replace($full, $short, $markup);
        }
        // revert </div> from terminology
        $markup = str_replace('</div>', '</term>', $markup);

        return $markup;
    }
}
