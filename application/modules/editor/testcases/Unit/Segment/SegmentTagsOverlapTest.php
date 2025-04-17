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

use editor_Plugins_TermTagger_Tag;
use editor_Segment_FieldTags;
use editor_Segment_Tag;
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several "classic" PHPUnit tests to check the OOP Tag-Parsing API againsted selected test data in regard of overlapping tags
 */
class SegmentTagsOverlapTest extends SegmentTagsTestAbstract
{
    /**
     * Some Internal Tags to create Tests with
     * tags 1 - 3 are mqm tags, 4 - 7 term tags, 8 - 9 internal tags
     */
    protected array $testTags = [
        '<1>' => '<img class="open critical qmflag ownttip qmflag-1" data-t5qid="111" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-left.png" />',
        '</1>' => '<img class="close critical qmflag ownttip qmflag-1" data-t5qid="111" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-right.png" />',
        '<2>' => '<img class="open critical qmflag ownttip qmflag-2" data-t5qid="222" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-2-left.png" />',
        '</2>' => '<img class="close critical qmflag ownttip qmflag-2" data-t5qid="222" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-2-right.png" />',
        '<3>' => '<img class="open critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-left.png" />',
        '</3>' => '<img class="close critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-right.png" />',
        '<4>' => '<div class="term preferredTerm transNotDefined exact" data-tbxid="term_444" title="">',
        '</4>' => '</div>',
        '<5>' => '<div class="term admittedTerm transFound" data-tbxid="term_555" title="">',
        '</5>' => '</div>',
        '<6>' => '<div class="term legalTerm transFound exact" data-tbxid="term_666" title="">',
        '</6>' => '</div>',
        '<7>' => '<div class="term supersededTerm transNotFound" data-tbxid="term_777" title="">',
        '</7>' => '</div>',
        '<8>' => '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;8&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST</span></div>',
        '</8>' => '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/8&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST</span></div>',
        '<9/>' => '<div class="single tab internal-tag ownttip"><span class="short" title="&lt;9/&gt;: 1 tab character">&lt;9/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div>',
        '<10/>' => '<div class="single newline internal-tag ownttip"><span class="short" title="&lt;10/&gt;: Newline">&lt;10/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>',
    ];

    public function testOverlappingTags1(): void
    {
        $markup = '<8>Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<9/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<10/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.</8>';
        $this->createReplacedTest(12345, $markup);
    }

    public function testOverlappingTags2(): void
    {
        $markup = '<8>Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing</2><9/> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<10/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.</8>';
        $this->createReplacedTest(12345, $markup);
    }

    public function testOverlappingTags3(): void
    {
        $markup = '<1>Lorem ipsum dolor sit amet, <2>consetetur sadipscing elitr</1>, sed diam nonumy eirmod tempor <3>invidunt ut labore et dolore magna aliquyam erat,</2> sed diam voluptua.</3>';
        $this->createReplacedTest(12345, $markup);
    }

    public function testOverlappingTags4(): void
    {
        $markup = '<1>Lorem ipsum dolor <9/>sit amet, <2>consetetur sadipscing elitr</1>, sed <4>diam nonumy</4> eirmod tempor <3>invidunt ut labore et dolore <5>magna</5> aliquyam erat,</2> sed diam voluptua.</3>';
        $this->createReplacedTest(12345, $markup);
    }

    public function testOverlappingTags5(): void
    {
        $markup = '<1>Lorem ipsum dolor <9/>sit amet, <2>consetetur sadipscing elitr</1>, sed <4>diam <3>nonumy</4> eirmod tempor invidunt ut labore et dolore <5>magna</5> aliquyam erat,</2> sed diam voluptua.</3>';
        $this->createReplacedTest(12345, $markup);
    }

    public function testNonOverlappingTermTags(): void
    {
        $tags = $this->createTags();
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(0, 80)->addClass('not_found_in_target')->setTbxId('123'), 0);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(0, 80)->addClass('not_defined_in_target')->setTbxId('124'), 1, 0);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(6, 17)->addClass('forbidden_in_target')->setTbxId('125'), 2, 1);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(28, 38)->addClass('forbidden_in_source')->setTbxId('126'), 3, 1);
        $markup =
            '<div class="term not_found_in_target" data-tbxid="123">'
                . '<div class="term not_defined_in_target" data-tbxid="124">Lorem '
                    . '<div class="term forbidden_in_target" data-tbxid="125">ipsum dolor</div>'
                    . ' sit amet, '
                    . '<div class="term forbidden_in_source" data-tbxid="126">consetetur</div>'
                    . ' sadipscing elitr, sed diam nonumy eirmod.'
                . '</div>'
            . '</div>';
        $this->createTagsTest($tags, $markup);
    }

    public function testOverlappingTermTags1(): void
    {
        $tags = $this->createTags();
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(6, 80)->addClass('not_found_in_target')->setTbxId('123'), 0);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(0, 65)->addClass('not_defined_in_target')->setTbxId('124'), 1);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(6, 17)->addClass('forbidden_in_target')->setTbxId('125'), 2, 1);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(28, 38)->addClass('forbidden_in_source')->setTbxId('126'), 3, 1);
        $markup =
            '<div class="term not_defined_in_target" data-tbxid="124">Lorem </div>'
            . '<div class="term not_found_in_target" data-tbxid="123">'
                . '<div class="term not_defined_in_target" data-tbxid="124">'
                    . '<div class="term forbidden_in_target" data-tbxid="125">ipsum dolor</div>'
                    . ' sit amet, '
                    . '<div class="term forbidden_in_source" data-tbxid="126">consetetur</div>'
                    . ' sadipscing elitr, sed diam'
                . '</div> nonumy eirmod.'
            . '</div>';
        $this->createTagsTest($tags, $markup);
    }

    public function testOverlappingTermTags2(): void
    {
        $tags = $this->createTags();
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(6, 80)->addClass('not_found_in_target')->setTbxId('123'), 0);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(0, 65)->addClass('not_defined_in_target')->setTbxId('124'), 1);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(6, 17)->addClass('forbidden_in_target')->setTbxId('125'), 2, 1);
        $tags->addTag(editor_Plugins_TermTagger_Tag::createNew(28, 72)->addClass('forbidden_in_source')->setTbxId('126'), 3);
        $markup =
            '<div class="term not_defined_in_target" data-tbxid="124">Lorem </div>'
            . '<div class="term not_found_in_target" data-tbxid="123">'
                . '<div class="term not_defined_in_target" data-tbxid="124">'
                    . '<div class="term forbidden_in_target" data-tbxid="125">ipsum dolor</div>'
                    . ' sit amet, '
                . '</div>'
                . '<div class="term forbidden_in_source" data-tbxid="126">'
                    . '<div class="term not_defined_in_target" data-tbxid="124">consetetur sadipscing elitr, sed diam</div>'
                    . ' nonumy'
                . '</div> eirmod.'
            . '</div>';
        $this->createTagsTest($tags, $markup);
    }

    private function createReplacedTest(int $segmentId, string $markup, string $expected = null): void
    {
        $replacedMarkup = $this->shortToFull($markup);
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $replacedMarkup, 'target', 'targetEdit');
        // compare unparsed markup
        if ($expected == null) {
            $this->assertEquals($replacedMarkup, $tags->render());
        } else {
            $this->assertEquals($expected, $tags->render());
        }
        // compare field-texts vs stripped markup
        $this->assertEquals(editor_Segment_Tag::strip($replacedMarkup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($this->getTestTask(), $expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }
}
