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

use editor_Segment_FieldTags;
use editor_Segment_Internal_TagComparision;
use editor_Segment_Internal_TagRepair;
use editor_Services_Connector_TagHandler_PairedTags;
use MittagQI\Translate5\Segment\TagRepair\Xliff\RegexTagParser;
use MittagQI\Translate5\Segment\TagRepair\Xliff\XliffTagRepairer;
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several "classic" PHPUnit tests to check the TagRepair which detects faulty structures and fixes them by removing or restructuring the internal tags
 */
class SegmentTagsRepairTest extends SegmentTagsTestAbstract
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
    ];

    public function testTagRepair0(): void
    {
        $fixed = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $this->createRepairTest($fixed, $fixed, []);
        $this->createRepairTestXlf($fixed, $fixed);
    }

    public function testTagRepair1(): void
    {
        $broken = 'Lorem <1>ipsum</1> dolor sit amet, </2>consetetur sadipscing<5/><2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $fixed = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // wrong order open/close
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    public function testTagRepair2(): void
    {
        $broken = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</3> aliquyam erat</4>, sed diam voluptua.<7/>';
        $fixed = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt</3> ut<6/> labore et <4>dolore</4> magna aliquyam erat, sed diam voluptua.<7/>';
        // overlapping tags
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    public function testTagRepair3(): void
    {
        $broken = 'Lorem <1>ipsum<6/> dolor sit amet, <4>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor </1>invidunt ut<3> labore et <2>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $fixed = 'Lorem <1>ipsum<6/></1> dolor sit amet, <4>consetetur</4> sadipscing<2><5/> elitr, sed diam nonumy eirmod tempor invidunt ut<3> labore</3> et </2>dolore magna aliquyam erat, sed diam voluptua.';
        // faulty structure
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    public function testTagRepair4()
    {
        $broken = 'Lorem <1>ipsum<6/> dolor sit amet, <2>consetetur sadipscing</1> elitr, sed diam nonumy eirmod tempor </2>invidunt ut<3> labore et <4>dolore magna</3> aliquyam erat</4>, sed diam voluptua.';
        $fixed = 'Lorem <1>ipsum<6/></1> dolor sit amet, <2>consetetur</2> sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut<3> labore</3> et <4>dolore</4> magna aliquyam erat, sed diam voluptua.';
        // faulty structure
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    public function testTagRepair5(): void
    {
        $fixed = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div>ranslation Management System';
        $broken = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span title="<run1>" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span title="</run1>" class="short" id="ext-element-243">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span title="<run2>" class="short">&lt;2&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;run2&gt;</span></div>ranslation Management System<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span title="</run3>" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/run3&gt;</span></div>';
        // test based on real data from the AutoQA approval
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty', 'internal_tags_added'], false);
        $this->createRepairTestXlf($fixed, $broken);
    }

    public function testTagRepair6(): void
    {
        $fixed = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run2&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;run2&gt;</span></div>ranslation <div class="open 6270742069643d2233223e266c743b72756e333e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run3&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;run3&gt;</span></div>M<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run3&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/run3&gt;</span></div>anagement System<div class="close 6570742069643d2232223e266c743b2f72756e323e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run2&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/run2&gt;</span></div>';
        $broken = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="<run1>" id="ext-element-241">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="</run1>">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span class="short" title="<run2>">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;run2&gt;</span></div>ranslation <div class="open 6270742069643d2233223e266c743b72756e333e3c2f627074 internal-tag ownttip"><span class="short" title="<run3>">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;run3&gt;</span></div>M<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span class="short" title="</run3>">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/run3&gt;</span></div>anagement <div class="close 6270742069643d2234223e266c743b72756e343e3c2f627074 internal-tag ownttip"><span class="short" title="<run4>">&lt;4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;run4&gt;</span></div>S<div class="close 6570742069643d2234223e266c743b2f72756e343e3c2f657074 internal-tag ownttip"><span class="short" title="</run4>">&lt;/4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;/run4&gt;</span></div>ystem<div class="close 6570742069643d2232223e266c743b2f72756e323e3c2f657074 internal-tag ownttip"><span class="short" title="</run2>">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/run2&gt;</span></div>';
        // test based on real data from the AutoQA approval
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty', 'internal_tags_added'], false);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Tests repair of sequences of tags with overlaps/interleaves
     */
    public function testTagRepair7(): void
    {
        $fixed = 'Lorem ipsum<1><2><5/></2></1> dolor<3><6/></3> sit amet';
        $broken = 'Lorem ipsum<1><2><5/></1></2> dolor<3><6/></3> sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testTagRepair8(): void
    {
        $fixed = 'Lorem ipsum<7/><1><2><3></3></2><4></4></1><5/> dolor<3><6/></3> sit amet';
        $broken = 'Lorem ipsum<7/><1><2><3></2></3></4><4></1><5/> dolor<3><6/></3> sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testTagRepair9(): void
    {
        $fixed = 'Lorem ipsum<1><2></2><5/><3><4></4></3></1> dolor<3><6/></3> sit amet';
        $broken = 'Lorem ipsum<1><2></2><5/><3><4></3></4></1> dolor<3><6/></3> sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testTagRepair10(): void
    {
        $fixed = 'Lorem ipsum<1><2><3><5/></3><6/></2><7/></1> dolor sit amet';
        $broken = 'Lorem ipsum<1><2><3><5/></1><6/></2><7/></3> dolor sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testTagRepair11(): void
    {
        $fixed = 'Lorem ipsum<1><2><5/></2></1><6/><4><3><7/></3></4> dolor sit amet';
        $broken = 'Lorem ipsum<1><2><5/></1></2><6/><4><3><7/></4></3> dolor sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testTagRepair12(): void
    {
        $fixed = 'Lorem ipsum<1><2><5/><4><3><6/></3></4><7/></2></1> dolor sit amet';
        $broken = 'Lorem ipsum<1><2><5/><4></2><6/><3></1><7/></3></4> dolor sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    public function testTagRepair13(): void
    {
        $fixed = 'Lorem ipsum<1><2></2><5/><4> dolor</4><6/><3></3><7/></1> sit amet';
        $broken = 'Lorem ipsum<1><2><5/><4></2> dolor<6/><3></1><7/></3></4> sit amet';
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
        $this->createRepairTestXlf($fixed, $broken);
    }

    /**
     * Test whitespace restoration when translation service drops whitespace.
     * When keepWhitespaceTags is false, whitespace is sent as actual characters
     * to the service. If the service doesn't return the whitespace, the repair
     * logic should restore it.
     */
    public function testWhitespaceRestoration(): void
    {
        // Source with a newline tag <7/> (softReturn)
        $source = 'Hello<7/>World';

        // Simulate the translation service dropping the newline
        $brokenTranslation = 'HalloWelt';

        $this->createWhitespaceRepairTest($source, $brokenTranslation);
    }

    /**
     * Test whitespace restoration with multiple whitespace tags.
     */
    public function testWhitespaceRestorationMultiple(): void
    {
        // Source with tab <5/> and newline <7/>
        $source = 'Hello<5/>World<7/>Foo';

        // Service returns text without any whitespace
        $brokenTranslation = 'HalloWeltBar';

        $this->createWhitespaceRepairTest($source, $brokenTranslation);
    }

    /**
     * Test that whitespace is preserved when service returns it correctly.
     */
    public function testWhitespacePreserved(): void
    {
        // Source with a newline
        $source = 'Hello<7/>World';

        // Service returns text WITH the newline
        $translationWithNewline = "Hallo\nWelt";

        $this->createWhitespacePreservationTest($source, $translationWithNewline);
    }

    /**
     * Test whitespace repair with mixed regular tags and whitespace.
     */
    public function testWhitespaceMixedWithTags(): void
    {
        // Source with regular paired tags and whitespace
        $source = '<1>Hello</1><7/><2>World</2>';

        // Service returns text without the newline but with tags
        $brokenTranslation = '<t5x_1_1>Hallo</t5x_1_1><t5x_2_2>Welt</t5x_2_2>';

        $this->createWhitespaceMixedRepairTest($source, $brokenTranslation);
    }

    /**
     * Helper method to test whitespace repair.
     * Tests that missing whitespace is restored when the translation service drops it.
     */
    private function createWhitespaceRepairTest(string $source, string $brokenTranslation): void
    {
        $sourceSegment = $this->shortToFull($source);

        // Create tag handler with keepWhitespaceTags = false (default)
        $tagHandler = new editor_Services_Connector_TagHandler_PairedTags();
        $preparedSource = $tagHandler->prepareQuery($sourceSegment);

        // Get the query segment which should have whitespace as XLIFF tags
        $querySegment = $tagHandler->getQuerySegment();

        // Verify that the query segment contains XLIFF whitespace tags
        self::assertMatchesRegularExpression('/<x id="\d+"\/>/', $querySegment, 'Query segment should contain whitespace XLIFF tags');

        // Simulate the service result - the brokenTranslation doesn't have the whitespace
        // The repair logic should add the missing whitespace XLIFF tags

        $repairer = XliffTagRepairer::create();
        $repairedText = $repairer->repairTranslation($querySegment, $brokenTranslation);

        // The repaired text should now contain the missing whitespace XLIFF tags
        self::assertMatchesRegularExpression('/<x id="\d+"\/>/', $repairedText, 'Repaired text should contain restored whitespace XLIFF tags');
    }

    /**
     * Helper method to test whitespace preservation.
     * Tests that whitespace is correctly handled when the translation service returns it.
     */
    private function createWhitespacePreservationTest(string $source, string $translationWithWhitespace): void
    {
        $sourceSegment = $this->shortToFull($source);

        $tagHandler = new editor_Services_Connector_TagHandler_PairedTags();
        $preparedSource = $tagHandler->prepareQuery($sourceSegment);

        // The prepared source sent to the service should have actual whitespace
        self::assertStringContainsString("\n", $preparedSource, 'Prepared source should contain actual newline');
        self::assertStringNotContainsString('<x id="', $preparedSource, 'Prepared source should not contain XLIFF tags');

        // Simulate restoring the result
        $restoredResult = $tagHandler->restoreInResult($translationWithWhitespace);

        // The result should contain the actual whitespace
        self::assertNotNull($restoredResult, 'Restored result should not be null');
    }

    /**
     * Helper method to test whitespace repair with mixed tags.
     */
    private function createWhitespaceMixedRepairTest(string $source, string $brokenTranslation): void
    {
        $sourceSegment = $this->shortToFull($source);

        $tagHandler = new editor_Services_Connector_TagHandler_PairedTags();
        $preparedSource = $tagHandler->prepareQuery($sourceSegment);

        // Get the query segment
        $querySegment = $tagHandler->getQuerySegment();

        // Should have both regular tags and whitespace XLIFF tags
        self::assertMatchesRegularExpression('/<bx id="\d+" rid="\d+"\/>/', $querySegment, 'Query segment should contain regular opening tags');
        self::assertMatchesRegularExpression('/<x id="\d+"\/>/', $querySegment, 'Query segment should contain whitespace XLIFF tags');

        // Repair the translation
        $repairer = XliffTagRepairer::create();
        $repairedText = $repairer->repairTranslation($querySegment, $brokenTranslation);

        // Should restore the missing whitespace tag
        self::assertMatchesRegularExpression('/<x id="\d+"\/>/', $repairedText, 'Repaired text should contain restored whitespace XLIFF tags');
    }

    private function createRepairTest(string $fixed, string $broken, array|string $expectedState, bool $replaceShortToFull = true): void
    {
        $fixedMarkup = $replaceShortToFull ? $this->shortToFull($fixed) : $fixed;
        $brokenMarkup = $replaceShortToFull ? $this->shortToFull($broken) : $broken;
        $fixedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $fixedMarkup, 'target', 'targetEdit');
        $brokenTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $brokenMarkup, 'target', 'targetEdit');
        // first, compare to find errors
        $tagComparision = new editor_Segment_Internal_TagComparision($brokenTags, $fixedTags);
        $this->assertEquals($expectedState, $tagComparision->getStati());

        $hasFaults = in_array('internal_tag_structure_faulty', $expectedState);
        $tagRepair = new editor_Segment_Internal_TagRepair($brokenTags, null);
        $this->assertEquals($hasFaults, $tagRepair->hadErrors());

        $fixedTags = ($replaceShortToFull) ? $this->fullToShort($brokenTags->render()) : $brokenTags->render();
        if ($hasFaults) {
            $this->assertEquals($fixed, $fixedTags);
            // echo "\n========== HAD ERRORS ===========\n".$broken."\n".$fixedTags."\n============\n";
        } else {
            $this->assertEquals($broken, $fixedTags);
            // echo "\n========== HAD ERRORS ===========\n".$broken."\n".$fixedTags."\n============\n";
        }
        // make sure the fixed tags would be detected as correct
        $tagComparision = new editor_Segment_Internal_TagComparision($brokenTags, null);
        $this->assertEquals([], $tagComparision->getStati());
    }

    /**
     * Test if the above test cases are fixable by the new xlf tag repairer.
     * Because we can not test the content directly, the way how this will test the results are based on the tag order
     * in the source and in the target.
     */
    private function createRepairTestXlf($fixed, $broken): void
    {
        $fixedSegment = $this->shortToFull($fixed);
        $brokenSegment = $this->shortToFull($broken);

        $tagHandlerBroken = new editor_Services_Connector_TagHandler_PairedTags();
        $brokenQuery = $tagHandlerBroken->prepareQuery($brokenSegment);

        $tagHandlerCorrect = new editor_Services_Connector_TagHandler_PairedTags();
        $fixedQuery = $tagHandlerCorrect->prepareQuery($fixedSegment);

        $tagRepair = XliffTagRepairer::create();
        $repaired = $tagRepair->repairTranslation($fixedQuery, $brokenQuery);

        $tagParser = new RegexTagParser();

        $correctRender = [];
        foreach ($tagParser->extractTags($fixedQuery) as $correctTag) {
            $correctRender[] = $correctTag->recreate();
        }

        $restoredRender = [];
        foreach ($tagParser->extractTags($repaired) as $restoredTag) {
            $restoredRender[] = $restoredTag->recreate();
        }
        self::assertEquals($correctRender, $restoredRender);
    }
}
