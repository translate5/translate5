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
class SegmentTagsMergeTest extends SegmentTagsTestAbstract
{
    public function testTagMerging1(): void
    {
        $original = 'This is <ins1>Some </ins1><ins1>insertion</ins1> in a text';
        $expected = 'This is <ins1>Some insertion</ins1> in a text';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging2(): void
    {
        $original = 'This is <ins1>Some insertion</ins1><ins1> a </ins1><ins2>little longer</ins2> in a text';
        $expected = 'This is <ins1>Some insertion a </ins1><ins2>little longer</ins2> in a text';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging3(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1>insertion</1></ins1> in a text';
        $expected = 'This is <ins1><1>Some insertion</1></ins1> in a text';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging4(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1>insertion</1></ins1> in a text';
        $expected = 'This is <ins1><1>Some insertion</1></ins1> in a text';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging5(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1>insertion</1></ins1> <ins1>in a text</ins1>';
        $expected = 'This is <ins1><1>Some insertion</1></ins1> <ins1>in a text</ins1>';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging6(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1><3/>insertion</1></ins1> in a text';
        $expected = 'This is <ins1><1>Some <3/>insertion</1></ins1> in a text';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging7(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1><3/>insertion</1></ins1><ins1> in a text</ins1>';
        $expected = 'This is <ins1><1>Some <3/>insertion</1> in a text</ins1>';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging8(): void
    {
        $original = 'This is <ins1><1><4/>Some </ins1><ins1><3/>insertion</1></ins1><ins1> <term1>in a text</term1></ins1>';
        $expected = 'This is <ins1><1><4/>Some <3/>insertion</1> <term1>in a text</term1></ins1>';
        $this->createMergingTest($original, $expected);
    }

    public function testTagMerging9(): void
    {
        $original = '<ins1>This is</ins1> <ins1><1><4/>Some </ins1><ins1><3/>insertion</1></ins1><ins1> <term1>in a text</term1></ins1>';
        $expected = '<ins1>This is</ins1> <ins1><1><4/>Some <3/>insertion</1> <term1>in a text</term1></ins1>';
        $this->createMergingTest($original, $expected);
    }

    protected function createMergingTest(string $original, string $expected): void
    {
        $segmentId = rand(111111, 999999);
        $originalMarkup = $this->shortToFull($original);
        $expectedMarkup = $this->shortToFull($expected);
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $originalMarkup, 'target', 'targetEdit');
        error_log(htmlspecialchars($tags->debugStructure())); // TODO REMOVE
        // to make error-catching easier we assign comparing the reverted tags
        $this->assertEquals($this->fullToShort($expectedMarkup), $this->fullToShort($tags->render()));
        $this->createTagsTest($tags, $expectedMarkup);
    }
}
