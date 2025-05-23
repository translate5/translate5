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
use editor_Segment_Tag;
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several "classic" PHPUnit tests to check the TagComparision which detects faulty structures and added/removed internal tags
 */
class SegmentTagsComparisionTest extends SegmentTagsTestAbstract
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

    public function testInternalTags1(): void
    {
        // testing "real" segment content
        $segmentId = 688499;
        $markup = '<div class="open 672069643d2233313422 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="314" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>Aktualizacja 07-2<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/1&gt;</span><span class="full" data-originalid="314" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313622 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="316" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>0<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/2&gt;</span><span class="full" data-originalid="316" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313722 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="317" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/3&gt;</span><span class="full" data-originalid="317" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313822 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="318" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>6 (akt.<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/4&gt;</span><span class="full" data-originalid="318" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313922 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;5&gt;</span><span class="full" data-originalid="319" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div> 0<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/5&gt;</span><span class="full" data-originalid="319" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233323022 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;6&gt;</span><span class="full" data-originalid="320" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/6&gt;</span><span class="full" data-originalid="320" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233323122 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;7&gt;</span><span class="full" data-originalid="321" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>)<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/7&gt;</span><span class="full" data-originalid="321" data-length="-1">&lt;/cf&gt;</span></div>';
        $this->createComparisionTest($segmentId, $markup);
    }

    public function testInternalTags2(): void
    {
        // testing "real" segment content
        $segmentId = 688500;
        $markup = 'W<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;13/&gt;: No-Break Space (NBSP)">&lt;13/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>niniejszej dokumentacji przedstawione są zalecenia dotyczące bezpieczeństwa, w<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;14/&gt;: No-Break Space (NBSP)">&lt;14/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>rozdziale<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;15/&gt;: No-Break Space (NBSP)">&lt;15/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="single 70682069643d2231223e266c743b78726566206e616d653d2671756f743b5061726167726170684e756d6265722671756f743b206c696e69643d2671756f743b3131322671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;112&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;2/&gt;</span><span class="full" data-originalid="953b38d526718e8217f4e2d6a4333ec1" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;112&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div> "<div class="single 70682069643d2232223e266c743b78726566206e616d653d2671756f743b506172616772617068546578742671756f743b206c696e69643d2671756f743b3131332671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;2&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;113&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;3/&gt;</span><span class="full" data-originalid="33f52a76d22832a3e44b45f58bfbffc3" data-length="-1">&lt;ph id=&quot;2&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;113&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div>" na stronie<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;16/&gt;: No-Break Space (NBSP)">&lt;16/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="open 6270742069643d223322207269643d2233223e266c743b756620756663617469643d2671756f743b322671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;3&quot; rid=&quot;3&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;17&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;bpt id=&quot;3&quot; rid=&quot;3&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div><div class="single 70682069643d2234223e266c743b78726566206e616d653d2671756f743b506167654e756d6265722671756f743b206c696e69643d2671756f743b3131342671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;4&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;114&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;6/&gt;</span><span class="full" data-originalid="12e9cebb1d9f5aba3b8c5ca68ad33d01" data-length="-1">&lt;ph id=&quot;4&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;114&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div><div class="close 6570742069643d223522207269643d2233223e266c743b2f75662667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;5&quot; rid=&quot;3&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;">&lt;/17&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;ept id=&quot;5&quot; rid=&quot;3&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;</span></div> oraz w<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;18/&gt;: No-Break Space (NBSP)">&lt;18/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>rozdziale <div class="single 70682069643d2236223e266c743b78726566206e616d653d2671756f743b5061726167726170684e756d6265722671756f743b206c696e69643d2671756f743b3131352671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;6&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;115&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;8/&gt;</span><span class="full" data-originalid="ab127e462541fe536923e31bfcee1cad" data-length="-1">&lt;ph id=&quot;6&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;115&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div> "<div class="single 70682069643d2237223e266c743b78726566206e616d653d2671756f743b506172616772617068546578742671756f743b206c696e69643d2671756f743b3131362671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;7&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;116&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;9/&gt;</span><span class="full" data-originalid="24a854f453c613624ce9ba28ad02a42a" data-length="-1">&lt;ph id=&quot;7&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;116&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div>" na stronie<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;19/&gt;: No-Break Space (NBSP)">&lt;19/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="open 6270742069643d223822207269643d2234223e266c743b756620756663617469643d2671756f743b322671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;8&quot; rid=&quot;4&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;20&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;bpt id=&quot;8&quot; rid=&quot;4&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div><div class="single 70682069643d2239223e266c743b78726566206e616d653d2671756f743b506167654e756d6265722671756f743b206c696e69643d2671756f743b3131372671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;9&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;117&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;12/&gt;</span><span class="full" data-originalid="eb7edd2163941ca5554db4d6cac6f432" data-length="-1">&lt;ph id=&quot;9&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;117&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div><div class="close 6570742069643d22313022207269643d2234223e266c743b2f75662667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;10&quot; rid=&quot;4&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;">&lt;/20&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;ept id=&quot;10&quot; rid=&quot;4&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;</span></div>, a<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;21/&gt;: No-Break Space (NBSP)">&lt;21/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>także informacje o<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;22/&gt;: No-Break Space (NBSP)">&lt;22/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>procedurze działań lub instrukcje działań, w<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;23/&gt;: No-Break Space (NBSP)">&lt;23/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>przypadku których występuje zagrożenie powstania szkód osobowych lub materialnych.';
        $this->createComparisionTest($segmentId, $markup);
    }

    public function testInternalTags3(): void
    {
        // testing "real" segment content
        $segmentId = 688501;
        $markup = '<div class="open 672069643d2232353822 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="258" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>-<div class="single 782069643d22323539222f internal-tag ownttip"><span class="short" title="&lt;char name=&quot;Indent&quot;/&gt;">&lt;2/&gt;</span><span class="full" data-originalid="259" data-length="-1">&lt;char name=&quot;Indent&quot;/&gt;</span></div> Supawash - high pressure washing<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/1&gt;</span><span class="full" data-originalid="258" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2232363022 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="260" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;4/&gt;: Newline">&lt;4/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div><div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span class="short" title="&lt;5/&gt;: 1 tab character">&lt;5/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div><div class="open 672069643d2232363122 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;6&gt;</span><span class="full" data-originalid="261" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>system<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/6&gt;</span><span class="full" data-originalid="261" data-length="-1">&lt;/cf&gt;</span></div> <div class="open 672069643d2232363222 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;7&gt;</span><span class="full" data-originalid="262" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>(32 l/min @ 100 bar), c/w<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/7&gt;</span><span class="full" data-originalid="262" data-length="-1">&lt;/cf&gt;</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;8/&gt;: Newline">&lt;8/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div><div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span class="short" title="&lt;9/&gt;: 1 tab character">&lt;9/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div><div class="open 672069643d2232363322 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;10&gt;</span><span class="full" data-originalid="263" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>Handlance,<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/10&gt;</span><span class="full" data-originalid="263" data-length="-1">&lt;/cf&gt;</span></div> <div class="open 672069643d2232363422 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;11&gt;</span><span class="full" data-originalid="264" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>reel,<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/11&gt;</span><span class="full" data-originalid="264" data-length="-1">&lt;/cf&gt;</span></div> <div class="open 672069643d2232363522 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;12&gt;</span><span class="full" data-originalid="265" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>15m hose.<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/12&gt;</span><span class="full" data-originalid="265" data-length="-1">&lt;/cf&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/3&gt;</span><span class="full" data-originalid="260" data-length="-1">&lt;/cf&gt;</span></div>';
        $this->createComparisionTest($segmentId, $markup);
    }

    public function testTagComparision1(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // simple: if input = output we must get no errors
        $this->createReplacedComparisionTest($original, $original, []);
    }

    public function testTagComparision2(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <2>ipsum</2> dolor sit amet, <1>consetetur sadipscing<5/></1> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // changed structure should not change validity
        $this->createReplacedComparisionTest($original, $edited, []);
    }

    public function testTagComparision3(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // missing tag
        $this->createReplacedComparisionTest($original, $edited, ['whitespace_tags_missing']);
    }

    public function testTagComparision4(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et dolore magna aliquyam erat</3>, sed diam voluptua.<7/>';
        // missing tag
        $this->createReplacedComparisionTest($original, $edited, ['internal_tags_missing']);
    }

    public function testTagComparision5(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, </2>consetetur sadipscing<5/><2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // wrong order open/close
        $this->createReplacedComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }

    public function testTagComparision6(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</3> aliquyam erat</4>, sed diam voluptua.<7/>';
        // overlapping tags
        $this->createReplacedComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }

    public function testTagComparision7(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam <6/>nonumy eirmod tempor <3>invidunt ut labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // additional tag
        $this->createReplacedComparisionTest($original, $edited, ['internal_tags_added']);
    }

    public function testTagComparision8(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<7/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        // additional & missing tag
        $this->createReplacedComparisionTest($original, $edited, ['whitespace_tags_added', 'internal_tags_missing']);
    }

    public function testTagComparision9(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum<6/> dolor sit amet, <2>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor </1>invidunt ut<3> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        // changed structure
        $this->createReplacedComparisionTest($original, $edited, []);
    }

    public function testTagComparision10(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum<6/> dolor sit amet, <4>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor </1>invidunt ut<3> labore et <2>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        // faulty structure
        $this->createReplacedComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }

    public function testTagComparision11(): void
    {
        $original = '<7/>Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum<2> dolor sit amet, <3>consetetur sadipscing<4> elitr, sed diam nonumy eirmod tempor </4>invidunt ut<6/><5/> labore et </3>dolore magna</2> aliquyam erat</1>, sed diam voluptua.<7/>';
        // changed structure
        $this->createReplacedComparisionTest($original, $edited, []);
    }

    public function testTagComparision12(): void
    {
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam <6/>nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // additional tag that is a carbon-copy will lead to a faulty structure
        $this->createReplacedComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }

    public function testRealDataComparision1(): void
    {
        // test based on real data from the AutoQA approval
        $source = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="<run1>" id="ext-element-241">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="</run1>">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span class="short" title="<run2>">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;run2&gt;</span></div>ranslation <div class="open 6270742069643d2233223e266c743b72756e333e3c2f627074 internal-tag ownttip"><span class="short" title="<run3>">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;run3&gt;</span></div>M<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span class="short" title="</run3>">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/run3&gt;</span></div>anagement <div class="open 6270742069643d2234223e266c743b72756e343e3c2f627074 internal-tag ownttip"><span class="short" title="<run4>">&lt;4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;run4&gt;</span></div>S<div class="close 6570742069643d2234223e266c743b2f72756e343e3c2f657074 internal-tag ownttip"><span class="short" title="</run4>">&lt;/4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;/run4&gt;</span></div>ystem<div class="close 6570742069643d2232223e266c743b2f72756e323e3c2f657074 internal-tag ownttip"><span class="short" title="</run2>">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/run2&gt;</span></div>';
        $targetOriginal = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span title="<run1>" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span title="</run1>" class="short" id="ext-element-243">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span title="<run2>" class="short">&lt;2&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;run2&gt;</span></div>ranslation Management System<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span title="</run3>" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/run3&gt;</span></div>';
        $targetEdited = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="<run1>" id="ext-element-244">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="</run1>">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div>ranslation Management System';
        $this->createEditedComparisionTest($source, $targetOriginal, ['internal_tag_structure_faulty', 'internal_tags_missing']);
        $this->createEditedComparisionTest($source, $targetEdited, ['internal_tags_missing']);
        $this->createEditedComparisionTest($targetOriginal, $targetEdited, ['internal_tags_missing']);
    }

    /**
     * This test ist for a HOTFIX: TS-2856 / TRANSLATE-3495
     */
    public function testRealDataComparision2(): void
    {
        $original = 'Tolerances apply from 0.05<div class="open 6270742069643d2231223e266c743b5370656369616c43686172616374657220547970653d224e6f6e427265616b696e675370616365222667743b3c2f627074 internal-tag ownttip"><span class="short" title="<SpecialCharacter Type=&quot;NonBreakingSpace&quot;>">&lt;3&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;SpecialCharacter Type="NonBreakingSpace"&gt;</span></div><div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="<1/>: No-Break Space (NBSP)">&lt;1/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="close 6570742069643d2231223e266c743b2f5370656369616c4368617261637465722667743b3c2f657074 internal-tag ownttip"><span class="short" title="</SpecialCharacter>">&lt;/3&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/SpecialCharacter&gt;</span></div>m to 10<div class="open 6270742069643d2232223e266c743b5370656369616c43686172616374657220547970653d224e6f6e427265616b696e675370616365222667743b3c2f627074 internal-tag ownttip"><span class="short" title="<SpecialCharacter Type=&quot;NonBreakingSpace&quot;>">&lt;4&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;SpecialCharacter Type="NonBreakingSpace"&gt;</span></div><div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="<2/>: No-Break Space (NBSP)">&lt;2/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="close 6570742069643d2232223e266c743b2f5370656369616c4368617261637465722667743b3c2f657074 internal-tag ownttip"><span class="short" title="</SpecialCharacter>">&lt;/4&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/SpecialCharacter&gt;</span></div>m with a confidence level of 95%.';
        $edited = 'Tolerancia sa aplikuje od 0,05<div class="open 6270742069643d2231223e266c743b5370656369616c43686172616374657220547970653d224e6f6e427265616b696e675370616365222667743b3c2f627074 internal-tag ownttip"><span class="short" title="<SpecialCharacter Type=&quot;NonBreakingSpace&quot;>">&lt;3&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;SpecialCharacter Type="NonBreakingSpace"&gt;</span></div><div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="<1/>: No-Break Space (NBSP)">&lt;1/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="close 6570742069643d2231223e266c743b2f5370656369616c4368617261637465722667743b3c2f657074 internal-tag ownttip"><span class="short" title="</SpecialCharacter>">&lt;/3&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/SpecialCharacter&gt;</span></div>m do 10<div class="open 6270742069643d2232223e266c743b5370656369616c43686172616374657220547970653d224e6f6e427265616b696e675370616365222667743b3c2f627074 internal-tag ownttip"><span class="short" title="<SpecialCharacter Type=&quot;NonBreakingSpace&quot;>">&lt;4&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;SpecialCharacter Type="NonBreakingSpace"&gt;</span></div><div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="<2/>: No-Break Space (NBSP)">&lt;2/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="close 6570742069643d2232223e266c743b2f5370656369616c4368617261637465722667743b3c2f657074 internal-tag ownttip"><span class="short" title="</SpecialCharacter>">&lt;/4&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/SpecialCharacter&gt;</span></div>m s<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="<1/>: No-Break Space (NBSP)">&lt;1/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>95 % úrovňou spoľahlivosti.';
        $this->createEditedComparisionTest($original, $edited, ['whitespace_tags_added']);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision0(): void
    {
        $markup = 'Lorem ipsum<1><2><5/></1></2> dolor<3><6/></3> sit amet';
        $this->createStructuralTest($markup, ['internal_tag_structure_faulty']);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision1(): void
    {
        $markup = 'Lorem ipsum<1><2><5/></2></1> dolor<3><6/></3> sit amet';
        $this->createStructuralTest($markup, []);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision2(): void
    {
        $markup = 'Lorem ipsum<1><2><5/></2></1> dolor<6/></3><3> sit amet';
        $this->createStructuralTest($markup, ['internal_tag_structure_faulty']);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision3(): void
    {
        $markup = 'Lorem ipsum<7/><1><2></2><3></3><4></4></1><5/> dolor<3><6/></3> sit amet';
        $this->createStructuralTest($markup, []);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision4(): void
    {
        $markup = 'Lorem ipsum<7/><1><2></2><3><4></3></4></1><5/> dolor<3><6/></3> sit amet';
        $this->createStructuralTest($markup, ['internal_tag_structure_faulty']);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision5(): void
    {
        $markup = 'Lorem ipsum<1><2><3><5/></1><6/></2><7/></3>  dolor sit amet';
        $this->createStructuralTest($markup, ['internal_tag_structure_faulty']);
    }

    /**
     * Tests sequences of tags with overlaps/interleaves
     */
    public function testSequenceComparision6(): void
    {
        $markup = 'Lorem ipsum<1><2><5/></1></2><6/><4><3><7/></4></3>  dolor sit amet';
        $this->createStructuralTest($markup, ['internal_tag_structure_faulty']);
    }

    /**
     * Creates a test only to check the structure of the given markup
     */
    private function createStructuralTest(string $markup, array|string $expectedState): void
    {
        $markup = $this->shortToFull($markup);
        $tags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $markup, 'target', 'targetEdit');
        $tagComparision = new editor_Segment_Internal_TagComparision($tags, null);
        $this->assertEquals($expectedState, $tagComparision->getStati());
    }

    private function createComparisionTest(int $segmentId, string $markup): void
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
        // tag comparision is expected to have no errors
        $tagComparision = new editor_Segment_Internal_TagComparision($tags, $jsonTags);
        $this->assertEquals([], $tagComparision->getStati());
    }

    /**
     * Creates a test for the internal tag comparision.
     */
    private function createEditedComparisionTest(string $original, string $edited, array|string $expectedState): void
    {
        if (! is_array($expectedState)) {
            $expectedState = [$expectedState];
        }
        $originalTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $original, 'target', 'targetEdit');
        $editedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $edited, 'target', 'targetEdit');
        $tagComparision = new editor_Segment_Internal_TagComparision($editedTags, $originalTags);
        $this->assertEquals($expectedState, $tagComparision->getStati());
    }

    /**
     * Creates a test for the internal tag comparision. The passed markup will have the following markup replaced with internal tags
     * Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>
     */
    private function createReplacedComparisionTest(string $original, string $edited, array|string $expectedState): void
    {
        if (! is_array($expectedState)) {
            $expectedState = [$expectedState];
        }
        $originalTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->shortToFull($original), 'target', 'targetEdit');
        $editedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->shortToFull($edited), 'target', 'targetEdit');
        $tagComparision = new editor_Segment_Internal_TagComparision($editedTags, $originalTags);
        $this->assertEquals($expectedState, $tagComparision->getStati());
    }
}
