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
 * Several PHPUnit tests to check the Tag-merging
 */
class SegmentTagsMergeTest extends SegmentTagsTestAbstract
{
    public function testTagMerging1(): void
    {
        $original = 'This is <ins1>Some </ins1><ins1>insertion</ins1> in a text';
        $expected = 'This is <ins1>Some insertion</ins1> in a text';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging2(): void
    {
        $original = 'This is <ins1>Some insertion</ins1><ins1> a </ins1><ins2>little longer</ins2> in a text';
        $expected = 'This is <ins1>Some insertion a </ins1><ins2>little longer</ins2> in a text';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging3(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1>insertion</1></ins1> in a text';
        $expected = 'This is <ins1><1>Some insertion</1></ins1> in a text';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging4(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1>insertion</1></ins1> in a text';
        $expected = 'This is <ins1><1>Some insertion</1></ins1> in a text';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging5(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1>insertion</1></ins1> <ins1>in a text</ins1>';
        $expected = 'This is <ins1><1>Some insertion</1></ins1> <ins1>in a text</ins1>';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging6(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1><3/>insertion</1></ins1> in a text';
        $expected = 'This is <ins1><1>Some <3/>insertion</1></ins1> in a text';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging7(): void
    {
        $original = 'This is <ins1><1>Some </ins1><ins1><3/>insertion</1></ins1><ins1> in a text</ins1>';
        $expected = 'This is <ins1><1>Some <3/>insertion</1> in a text</ins1>';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging8(): void
    {
        $original = 'This is <ins1><1><4/>Some </ins1><ins1><3/>insertion</1></ins1><ins1> <term1>in a text</term1></ins1>';
        $expected = 'This is <ins1><1><4/>Some <3/>insertion</1> <term1>in a text</term1></ins1>';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging9(): void
    {
        $original = '<ins1>This is</ins1> <ins1><1><4/>Some </ins1><ins1><3/>insertion</1></ins1><ins1> <term1>in a text</term1></ins1>';
        $expected = '<ins1>This is</ins1> <ins1><1><4/>Some <3/>insertion</1> <term1>in a text</term1></ins1>';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging10(): void
    {
        $original = '<ins3>Lorem <3/></ins3><ins3><4/></ins3>ipsum';
        $expected = '<ins3>Lorem <3/><4/></ins3>ipsum';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testTagMerging11(): void
    {
        $original = '<ins3>Lorem <3/></ins3><ins3><5/><4/></ins3>ipsum';
        $expected = '<ins3>Lorem <3/><5/><4/></ins3>ipsum';
        $this->createShortTagsMergingTest($original, $expected);
    }

    /**
     * The inbetween <ins> must prevent the merging of the first/third <ins> !
     */
    public function testTagMerging12(): void
    {
        $original = '<ins3>Lorem <3/></ins3><ins1><5/></ins1><ins3><4/></ins3>ipsum';
        $expected = '<ins3>Lorem <3/></ins3><ins1><5/></ins1><ins3><4/></ins3>ipsum';
        $this->createShortTagsMergingTest($original, $expected);
    }

    /**
     * The inbetween <5/> must prevent the merging of the first/third <ins> !
     */
    public function testTagMerging13(): void
    {
        $original = '<ins3>Lorem <3/></ins3><5/><ins3><4/></ins3>ipsum';
        $expected = '<ins3>Lorem <3/></ins3><5/><ins3><4/></ins3>ipsum';
        $this->createShortTagsMergingTest($original, $expected);
    }

    public function testRealDataTagmerging1(): void
    {
        $original = '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="ph" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div>Ichbin<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1">einTerm </ins><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1">Ichbin</ins>einTerm';
        $expected = '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="ph" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div>Ichbin<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1">einTerm Ichbin</ins>einTerm';
        $this->createMergingTest($original, $expected);
    }

    public function testRealDataTagmerging2(): void
    {
        $original = '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="ph" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div><div title="" class="term preferredTerm exact" data-tbxid="889df480-8788-4945-8e76-5082137db6b4">Ichbin<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">einTerm</ins></div><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00"> </ins><div title="" class="term preferredTerm exact" data-tbxid="889df480-8788-4945-8e76-5082137db6b4"><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">Ichbin</ins>einTerm</div>';
        $expected = '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="ph" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div>Ichbin<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">einTerm Ichbin</ins>einTerm';
        $this->createMergingTest($original, $expected, true);
    }

    public function testRealDataTagmerging3(): void
    {
        $original = '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="ph" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">Ichbin </ins><div class="term preferredTerm exact" title="" data-tbxid="e280ca36-5448-4044-b3d9-1802e7fe460f"><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">keinTerm</ins> </div><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00"><div class="term preferredTerm exact" title="" data-tbxid="e280ca36-5448-4044-b3d9-1802e7fe460f">Ichbin</div> keinTerm</ins>x';
        $expected = '<div class="single 70682069643d2231223e266c743b73796d2667743bee80a3266c743b2f73796d2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="ph" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;sym&amp;gt;&amp;lt;/sym&amp;gt;&lt;/ph&gt;</span></div><ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">Ichbin keinTerm</ins> <ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00"><div class="term preferredTerm exact" title="" data-tbxid="e280ca36-5448-4044-b3d9-1802e7fe460f">Ichbin</div> keinTerm</ins>x';
        $this->createMergingTest($original, $expected, true);
    }

    protected function createShortTagsMergingTest(string $original, string $expected, bool $cleanTerms = false): void
    {
        $original = $this->shortToFull($original);
        $expected = $this->shortToFull($expected);
        $segmentId = rand(111111, 999999);
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $original, 'target', 'targetEdit');
        if ($cleanTerms) {
            $tags->fixTermTaggerTags();
        }
        // to make error-catching easier we assign comparing the reverted tags
        $this->assertEquals($this->fullToShort($expected), $this->fullToShort($tags->render()));
        $this->createTagsTest($tags, $expected);
    }

    protected function createMergingTest(string $original, string $expected, bool $cleanTerms = false): void
    {
        $segmentId = rand(111111, 999999);
        $tags = new editor_Segment_FieldTags($this->getTestTask(), $segmentId, $original, 'target', 'targetEdit');
        if ($cleanTerms) {
            $tags->fixTermTaggerTags();
        }
        $this->createTagsTest($tags, $expected);
    }
}
