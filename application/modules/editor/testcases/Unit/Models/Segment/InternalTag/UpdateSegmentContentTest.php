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

namespace MittagQI\Translate5\Test\Unit\Models\Segment\InternalTag;

use PHPUnit\Framework\TestCase;

class UpdateSegmentContentTest extends TestCase
{
    /**
     * @dataProvider casesProvider
     */
    public function test(
        string $originalContent,
        string $segmentContent,
        bool $ignoreWhitespace,
        string $expectedContent,
        string $expectedSegmentContent
    ): void {
        $tagHandler = new \editor_Models_Segment_InternalTag();
        $tagHandler->updateSegmentContent(
            $originalContent,
            $segmentContent,
            function ($processedOriginal, $processedSegment) use ($expectedContent, $expectedSegmentContent) {
                self::assertEquals($expectedContent, $processedOriginal);
                self::assertEquals($expectedSegmentContent, $processedSegment);
            },
            $ignoreWhitespace,
            true,
        );
    }

    public function casesProvider(): iterable
    {
        yield 'en -> ja after DeepL from real client task' => [
            'originalContent' => 'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'segmentContent' => 'メニューバー<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>の<div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> ボタンを押します。',
            'ignoreWhitespace' => false,
            'expectedContent' => 'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'expectedSegmentContent' => 'メニューバー<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>の<div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> ボタンを押します。',
        ];
    }
}
