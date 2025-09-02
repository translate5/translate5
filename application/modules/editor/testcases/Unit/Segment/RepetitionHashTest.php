<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Test\Unit\Segment;

use PHPUnit\Framework\TestCase;

class RepetitionHashTest extends TestCase
{
    /**
     * @dataProvider repetitionCases
     */
    public function testRepetitions(string $one, string $two, bool $isRepetition): void
    {
        $task = $this->createMock(\editor_Models_Task::class);
        $task->method('__call')->willReturn(false);

        $config = new \Zend_Config([
            'runtimeOptions' => [
                'alike' => [
                    'segmentMetaFields' => [],
                ],
            ],
        ]);
        $task->method('getConfig')->willReturn($config);

        $repetitionHash = new \editor_Models_Segment_RepetitionHash($task);
        $repetitionHash->setSegmentAttributes(new \editor_Models_Import_FileParser_SegmentAttributes());

        $segment = $this->createMock(\editor_Models_Segment::class);
        $meta = $this->createMock(\editor_Models_Segment_Meta::class);
        $meta->method('__call')->willReturn('');
        $segment->method('meta')->willReturn($meta);
        $repetitionHash->setSegment($segment);

        self::assertSame(
            $isRepetition,
            $repetitionHash->hashSource($one, $one) === $repetitionHash->hashSource($two, $two),
            'The two segments are expected to ' . ($isRepetition ? '' : 'not ') . 'be repetitions of each other.'
        );
    }

    public function repetitionCases(): iterable
    {
        yield 'same string with no tags' => [
            'Hello World',
            'Hello World',
            true,
        ];

        yield 'not same string with no tags' => [
            'Hello World!',
            'Hello World',
            false,
        ];

        yield 'same string with same tags at same positions' => [
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            true,
        ];

        yield 'same string with same tags at different positions' => [
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> system button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            false,
        ];

        yield 'same string with different tags of same type at same positions' => [
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'Press the <div class="open 6270742069643d223122207269643d2231222061783a732d69643d2232222061783a732d666f6e742d6e616d653d22417269616c222061783a732d666f6e742d73697a653d2234342e30222061783a732d666f72652d636f6c6f723d222d3136373737323136222061783a732d626f6c643d2246616c7365222061783a732d737472696b653d2246616c736522202f internal-tag ownttip"><span title="&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; ax:s-id=&quot;2&quot; ax:s-font-name=&quot;Arial&quot; ax:s-font-size=&quot;44.0&quot; ax:s-fore-color=&quot;-16777216&quot; ax:s-bold=&quot;False&quot; ax:s-strike=&quot;False&quot; /&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; ax:s-id=&quot;2&quot; ax:s-font-name=&quot;Arial&quot; ax:s-font-size=&quot;44.0&quot; ax:s-fore-color=&quot;-16777216&quot; ax:s-bold=&quot;False&quot; ax:s-strike=&quot;False&quot; /&gt;</span></div>Powder supply system<div class="close 6570742069643d223222207269643d223122202f internal-tag ownttip"><span title="&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;/1&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            true,
        ];

        yield 'same string with different tags of same type at different positions' => [
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'Press the <div class="open 6270742069643d223122207269643d2231222061783a732d69643d2232222061783a732d666f6e742d6e616d653d22417269616c222061783a732d666f6e742d73697a653d2234342e30222061783a732d666f72652d636f6c6f723d222d3136373737323136222061783a732d626f6c643d2246616c7365222061783a732d737472696b653d2246616c736522202f internal-tag ownttip"><span title="&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; ax:s-id=&quot;2&quot; ax:s-font-name=&quot;Arial&quot; ax:s-font-size=&quot;44.0&quot; ax:s-fore-color=&quot;-16777216&quot; ax:s-bold=&quot;False&quot; ax:s-strike=&quot;False&quot; /&gt;" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;bpt id=&quot;1&quot; rid=&quot;1&quot; ax:s-id=&quot;2&quot; ax:s-font-name=&quot;Arial&quot; ax:s-font-size=&quot;44.0&quot; ax:s-fore-color=&quot;-16777216&quot; ax:s-bold=&quot;False&quot; ax:s-strike=&quot;False&quot; /&gt;</span></div>Powder supply<div class="close 6570742069643d223222207269643d223122202f internal-tag ownttip"><span title="&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;" class="short">&lt;/1&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;ept id=&quot;2&quot; rid=&quot;1&quot; /&gt;</span></div> system button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            false,
        ];

        yield 'same string with singular tags instead of open/close at same positions' => [
            'Press the <div class="open 6270742069643d2231222063747970653d22782d656d706861736973223e266c743b656d70686173697320747970653d2671756f743b6b65792671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-emphasis&quot;&gt;&amp;lt;emphasis type=&amp;quot;key&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div>Powder supply system<div class="close 6570742069643d2231223e266c743b2f656d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/emphasis&amp;gt;&lt;/ept&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            'Press the <div class="single 69742069643d2233222063747970653d22782d622220706f733d226f70656e223e266c743b622667743b3c2f6974 internal-tag ownttip"><span class="short" title="&lt;it id=&quot;3&quot; ctype=&quot;x-b&quot; pos=&quot;open&quot;&gt;&amp;lt;b&amp;gt;&lt;/it&gt;">&lt;1/&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;it id="3" ctype="x-b" pos="open"&gt;&amp;lt;b&amp;gt;&lt;/it&gt;</span></div>Powder supply system<div class="single 69742069643d2233222063747970653d22782d622220706f733d226f70656e223e266c743b622667743b3c2f6974 internal-tag ownttip"><span class="short" title="&lt;it id=&quot;3&quot; ctype=&quot;x-b&quot; pos=&quot;open&quot;&gt;&amp;lt;b&amp;gt;&lt;/it&gt;">&lt;2/&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;it id="3" ctype="x-b" pos="open"&gt;&amp;lt;b&amp;gt;&lt;/it&gt;</span></div> button in menu bar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;3/&gt; CP: default simple">&lt;3/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div>.',
            false,
        ];

        yield 'same string with singular tags instead of CP tag at same position' => [
            'Press the <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232222069736f3d223222207461726765743d2232222072656765783d22303965494b61364a71346e52304e534950725178526c6337316c346a326c44584d6a596d5262736d4a6b565455304d6a4f6b5a507839724b586a45577046524655374d47524e5845614772716c774941222f number internal-tag ownttip"><span class="short" title="&lt;1/&gt; CP: default simple">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="2" data-target="2"></span></div> button.',
            'Press the <div class="single 69742069643d2233222063747970653d22782d622220706f733d226f70656e223e266c743b622667743b3c2f6974 internal-tag ownttip"><span class="short" title="&lt;it id=&quot;3&quot; ctype=&quot;x-b&quot; pos=&quot;open&quot;&gt;&amp;lt;b&amp;gt;&lt;/it&gt;">&lt;1/&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;it id="3" ctype="x-b" pos="open"&gt;&amp;lt;b&amp;gt;&lt;/it&gt;</span></div> button.',
            false,
        ];

        yield 'same string with singular tags instead of whitespace tag at same position' => [
            'Press the <div class="single 636861722074733d2265323830616622206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;1/&gt;: Narrow No-Break Space (NNBSP)" class="short" id="ext-element-476">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full" id="ext-element-477">[NNBSP]</span></div> button.',
            'Press the <div class="single 69742069643d2233222063747970653d22782d622220706f733d226f70656e223e266c743b622667743b3c2f6974 internal-tag ownttip"><span class="short" title="&lt;it id=&quot;3&quot; ctype=&quot;x-b&quot; pos=&quot;open&quot;&gt;&amp;lt;b&amp;gt;&lt;/it&gt;">&lt;1/&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;it id="3" ctype="x-b" pos="open"&gt;&amp;lt;b&amp;gt;&lt;/it&gt;</span></div> button.',
            false,
        ];

        yield 'same string with singular tags instead of placable tag at same position' => [
            'Press the <div class="single 69742069643d2233222063747970653d22782d622220706f733d226f70656e223e266c743b622667743b3c2f6974 t5placeable internal-tag ownttip"><span class="short" title="&lt;it id=&quot;3&quot; ctype=&quot;x-b&quot; pos=&quot;open&quot;&gt;&amp;lt;b&amp;gt;&lt;/it&gt;">&lt;1/&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;it id="3" ctype="x-b" pos="open"&gt;&amp;lt;b&amp;gt;&lt;/it&gt;</span></div> button.',
            'Press the <div class="single 69742069643d2233222063747970653d22782d622220706f733d226f70656e223e266c743b622667743b3c2f6974 internal-tag ownttip"><span class="short" title="&lt;it id=&quot;3&quot; ctype=&quot;x-b&quot; pos=&quot;open&quot;&gt;&amp;lt;b&amp;gt;&lt;/it&gt;">&lt;1/&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;it id="3" ctype="x-b" pos="open"&gt;&amp;lt;b&amp;gt;&lt;/it&gt;</span></div> button.',
            false,
        ];
    }
}
