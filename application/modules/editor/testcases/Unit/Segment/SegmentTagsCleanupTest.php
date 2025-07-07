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

use MittagQI\Translate5\Segment\Tag\TagCleanup;
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several "classic" PHPUnit tests to check the SegmentCleanup which cleans tags from Internal tags
 */
class SegmentTagsCleanupTest extends SegmentTagsTestAbstract
{
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
        '<5/>' => '<div class="single 1a2345f char internal-tag ownttip"><span class="short" title="&lt;5/&gt;: Line Tabulation (VT)">&lt;5/&gt;</span><span class="full" data-originalid="char" data-length="1">[VT]</span></div>',
        '<6/>' => '<div class="single 1a5678f char internal-tag ownttip"><span class="short" title="&lt;6/&gt;: Non-Breaking Hyphen (‑)">&lt;6/&gt;</span><span class="full" data-originalid="char" data-length="1">‑</span></div>',
        '<7/>' => '<div class="single tab internal-tag ownttip"><span class="short" title="&lt;7/&gt;: 1 tab character">&lt;7/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div>',
        '<8/>' => '<div class="single newline internal-tag ownttip"><span class="short" title="&lt;8/&gt;: Newline">&lt;8/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>',
    ];

    public function testCleanup0(): void
    {
        $markup = 'Lorem ipsum <1><2><3/></1></2> dolor <5/>sit<6/>sit <4/>amet';
        $expected = ['Lorem ipsum ', '190', ' dolor sit‑sit ', 'amet'];
        $this->createCleanupTest($markup, $expected);
    }

    /**
     * @depends testCleanup0
     */
    public function testCleanup1(): void
    {
        $markup = '<1>Lorem ipsum<6/></1> dolor <2><7/>sit</2> <8/>amet';
        $expected = ['Lorem ipsum‑', ' dolor ', ' &emsp;', 'sit', ' <br/>', 'amet'];
        $this->createCleanupTest($markup, $expected);
    }

    /**
     * @depends testCleanup1
     */
    public function testCleanup2(): void
    {
        $markup = 'Lorem ipsum <1><2><3/></1></2> dolor <5/>sit<6/>sit <4/>amet';
        $expected = ['Lorem ipsum ', '190', ' dolor sit‑sit ', 'amet'];
        $this->createStripWhitespaceCleanupTest($markup, $expected);
    }

    /**
     * @depends testCleanup2
     */
    public function testCleanup3(): void
    {
        $markup = '<1>Lorem ipsum<6/></1> dolor <2><7/>sit</2> <8/>amet';
        $expected = ['Lorem ipsum‑', ' dolor ', 'sit', ' ', 'amet'];
        $this->createStripWhitespaceCleanupTest($markup, $expected);
    }

    private function createCleanupTest(string $markup, array $expected): void
    {
        $cleanup = new TagCleanup($this->getTestTask());
        $this->assertEquals($expected, $cleanup->clean($this->shortToFull($markup)));
    }

    private function createStripWhitespaceCleanupTest(string $markup, array $expected): void
    {
        $task = $this->getTestTask();
        $cleanup = new TagCleanup($task, true);
        $this->assertEquals($expected, $cleanup->clean($this->shortToFull($markup)));
    }
}
