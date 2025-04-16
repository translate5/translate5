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
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several PHPUnit tests to check the TermTagger tag-repair with real-life & constructed data
 */
class SegmentTermTagsRepairTest extends SegmentTagsTestAbstract
{
    public function testFaultyTermTags1(): void
    {
        $original = '<term1><ins1>Priključek</ins1></term1> za EMMT-AS-150, <3/>';
        $expected = '<ins1><term1>Priključek</term1></ins1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags2(): void
    {
        $original = '<term1><ins1>Priključek</ins1></term1><ins1> za EMMT-AS-150, <3/></ins1>';
        $expected = '<ins1><term1>Priključek</term1> za EMMT-AS-150, <3/></ins1>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags3(): void
    {
        $original = '<term1><ins1>Priključek</ins1></term1> za EMMT-AS-150, <term2><ins3>Something else</ins3></term2>';
        $expected = '<ins1><term1>Priključek</term></ins> za EMMT-AS-150, <ins3><term2>Something else</term></ins>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags4(): void
    {
        $original = '<term1><ins1>P</ins1>riključek</term1> za EMMT-AS-150, <3/>';
        $expected = '<term1>Priključek</term1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags5(): void
    {
        $original = '<term1>Priključe<ins1>k</ins1></term1> za EMMT-AS-150, <3/>';
        $expected = 'Priključe<ins1>k</ins1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags6(): void
    {
        $original = '<ins3>Priključek <4/> za </ins3><term1><ins1>P</ins1>riključek</term1> za EMMT-AS-150, <3/>';
        $expected = '<ins3>Priključek <4/> za </ins3><term1>Priključek</term1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags7(): void
    {
        $original = '<ins1>Priključek <4/> za </ins1><term1><ins2>P</ins2>riključek</term1> za EMMT-AS-150, <3/>';
        $expected = '<ins1>Priključek <4/> za </ins1><term1>Priključek</term1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags8(): void
    {
        $original = '<ins1>Priključek <4/> za </ins1><term1><ins1>P</ins1>riključek</term1> za EMMT-AS-150, <3/>';
        $expected = '<ins1>Priključek <4/> za </ins1><term1>Priključek</term1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags9(): void
    {
        // singular-tags in reality can not be in a term-tag, as they have textual content ... that may changes in the future
        // when we get rid of the div/span
        $original = '<term1><1>Priključek</1></term1> za EMMT-AS-150, <3/>';
        $expected = '<1>Priključek</1> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    public function testFaultyTermTags10(): void
    {
        // this is a allowed nesting and nothing must be changed
        $original = 'Some text <2><term1>Priključek</term1></2> za EMMT-AS-150, <3/>';
        $this->createTermTagsRepairTest($original, $original);
    }

    public function testFaultyTermTags11(): void
    {
        // this is a allowed nesting and nothing must be changed
        $original = 'Some text <2><term1>Priključek</term1></2> za <1><term2>EMMT-AS-150</term2></1>, <3/>';
        $this->createTermTagsRepairTest($original, $original);
    }

    public function testFaultyTermTags12(): void
    {
        // this is a allowed nesting and nothing must be changed
        $original = 'Some text <2><term1>Priključek</term1></2> za <1><term2>EMMT<4/>AS-150</term2></1>, <3/>';
        $expected = 'Some text <2><term1>Priključek</term1></2> za <1>EMMT<4/>AS-150</1>, <3/>';
        $this->createTermTagsRepairTest($original, $expected);
    }

    protected function createTermTagsRepairTest(string $original, string $expected): void
    {
        $segmentId = rand(111111, 999999);
        $originalMarkup = $this->shortToFull($original);
        $expectedMarkup = $this->shortToFull($expected);
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $originalMarkup, 'target', 'targetEdit');
        // error_log(htmlspecialchars($tags->debugStructure()));
        $tags->fixTermTaggerTags();
        // error_log(htmlspecialchars($tags->debugStructure()));
        // to make error-catching easier we assign comparing the reverted tags
        $this->assertEquals($this->fullToShort($expectedMarkup), $this->fullToShort($tags->render()));
        $this->createTagsTest($tags, $expectedMarkup);
    }
}
