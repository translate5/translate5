<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\T5Memory;

use editor_Models_Segment_InternalTag;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\T5Memory\SegmentsCompare;
use PHPUnit\Framework\TestCase;

class SegmentsCompareTest extends TestCase
{
    private SegmentsCompare $segmentsCompare;

    private editor_Models_Segment_InternalTag $internalTagHelper;

    protected function setUp(): void
    {
        $this->internalTagHelper = new editor_Models_Segment_InternalTag();
        $this->segmentsCompare = new SegmentsCompare(
            $this->internalTagHelper,
            ContentProtector::create(),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testAreSegmentsEqual(string $segmentA, string $segmentB, bool $equal): void
    {
        $this->assertSame($equal, $this->segmentsCompare->areSegmentsEqual($segmentA, $segmentB));
    }

    public function cases(): iterable
    {
        yield 'simple equal segments' => [
            'Hello World',
            'Hello World',
            true,
        ];

        yield 'different segments' => [
            'Hello World',
            'Hello Translate5',
            false,
        ];

        yield 'segments with internal tags equal' => [
            'Operating pressures (see <div class="single 78206d69643d226164646974696f6e616c2d3122202f ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;x mid=&quot;additional-1&quot; /&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">AdditionalTagFromTM: &lt;x mid=&quot;additional-1&quot; /&gt;</span></div>).',
            'Operating pressures (see <div class="single 782069643d2231222f ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;x id=&quot;1&quot;/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">AdditionalTagFromTM: &lt;x id=&quot;1&quot;/&gt;</span></div>).',
            true,
        ];

        yield 'segments with different internal tags and CP tags' => [
            'Section <div class="single 78206d69643d226164646974696f6e616c2d3122202f ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;x mid=&quot;additional-1&quot; /&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">AdditionalTagFromTM: &lt;x mid=&quot;additional-1&quot; /&gt;</span></div> so that the drilled holes of the mounting bracket are congruent <div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>acc. to Section <div class="single 78206d69643d226164646974696f6e616c2d3222202f ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;x mid=&quot;additional-2&quot; /&gt;" class="short">&lt;2/&gt;</span><span data-originalid="toignore-2" data-length="-1" class="full">AdditionalTagFromTM: &lt;x mid=&quot;additional-2&quot; /&gt;</span></div> (tolerance +-<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d22616e79206e756d62657220286e6f742070617274206f662068797068616e74656420636f6d706f756e6429202d20696e20544d206f6e6c792220736f757263653d2231222069736f3d223122207461726765743d2231222072656765783d22303965777431474d4e7443316a4e58554146506132706f61396f713630596d365659363655596558484e3532654d2f686c73505444732b4a3164514841413d3d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: any number (not part of hyphanted compound) - in TM only" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="1" data-source="1" data-target="1" class="full"></span></div> mm).',
            'Section <div class="single 782069643d2231222f ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;x id=&quot;1&quot;/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">AdditionalTagFromTM: &lt;x id=&quot;1&quot;/&gt;</span></div> so that the drilled holes of the mounting bracket are congruent <div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>acc. to Section <div class="single 782069643d2232222f ignoreInEditor internal-tag ownttip"><span title="AdditionalTagFromTM: &lt;x id=&quot;2&quot;/&gt;" class="short">&lt;2/&gt;</span><span data-originalid="toignore-2" data-length="-1" class="full">AdditionalTagFromTM: &lt;x id=&quot;2&quot;/&gt;</span></div> (tolerance +-<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d22616e79206e756d62657220286e6f742070617274206f662068797068616e74656420636f6d706f756e6429202d20696e20544d206f6e6c792220736f757263653d2231222069736f3d223122207461726765743d2231222072656765783d22303965777431474d4e7443316a4e58554146506132706f61396f713630596d365659363655596558484e3532654d2f686c73505444732b4a3164514841413d3d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: any number (not part of hyphanted compound) - in TM only" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="1" data-source="1" data-target="1" class="full"></span></div> mm).',
            true,
        ];

        yield 'x => id' => [
            'Section <ph x="101"/> so that the drilled holes',
            'Section <ph id="101"/> so that the drilled holes',
            true,
        ];

        yield 'rid => i' => [
            'Section <bpt x="501" i="1"/> so that the drilled holes<ept i="1"/>',
            'Section <bpt x="501" rid="1"/> so that the drilled holes<ept rid="1"/>',
            true,
        ];
    }
}
