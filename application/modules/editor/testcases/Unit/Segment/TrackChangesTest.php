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

use MittagQI\Translate5\Test\SegmentTagsTestAbstract;

/**
 * Several "classic" PHPUnit tests to check the OOP Tag-Parsing API with TrackChanges specialities
 */
class TrackChangesTest extends SegmentTagsTestAbstract
{
    public function testTrackChanges1(): void
    {
        $segmentId = 766543;
        $markup = 'Lorem ipsum <ins1>dolor sit amet<3/></ins><ins2>, consetetur</ins> sadipscing elitr, <del1>sed diam nonumy<4/></del1> eirmod<del2> tempor invidunt ut</del2>.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, eirmod.';
        $markupNoTC = 'Lorem ipsum dolor sit amet<3/>, consetetur sadipscing elitr, eirmod.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges2(): void
    {
        $segmentId = 766543;
        $markup = 'Lorem ipsum <ins1>dolor sit amet<3/></ins1>, <ins2><1>consetetur</1></ins2> sadipscing elitr, sed<del1> diam</del1><del2> nonumy<4/> eirmod</del2><del3> tempor invidunt ut</del3>.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed.';
        $markupNoTC = 'Lorem ipsum dolor sit amet<3/>, <1>consetetur</1> sadipscing elitr, sed.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges3(): void
    {
        $segmentId = 766543;
        $markup = 'Lorem ipsum <ins1>dolor sit <ins2>amet<3/>, </ins2><1>consetetur</1></ins1> sadipscing elitr, sed <del1>diam</del1><del2> nonumy<4/> eirmod</del2><del3> tempor invidunt </del3>ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed ut.';
        $markupNoTC = 'Lorem ipsum dolor sit amet<3/>, <1>consetetur</1> sadipscing elitr, sed ut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges4(): void
    {
        $segmentId = 766543;
        $markup = 'Lorem ipsum <ins1><ins2>dolor sit amet<3/>, </ins2><1>consetetur</1></ins1> sadipscing elitr, sed<del1> diam</del1><del2> nonumy<4/> eirmod</del2><del3> tempor invidunt </del3>ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sedut.';
        $markupNoTC = 'Lorem ipsum dolor sit amet<3/>, <1>consetetur</1> sadipscing elitr, sedut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges5(): void
    {
        // double whitespace arounrd d<del>s will be condensed
        $segmentId = 766543;
        $markup = 'Lorem ipsum <ins1><ins2>dolor sit amet, <1>consetetur</1></ins2></ins1> sadipscing elitr, sed <del1>diam<3/></del1><del2> nonumy eirmod<4/></del2><del3> tempor invidunt</del3> ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed ut.';
        $markupNoTC = 'Lorem ipsum dolor sit amet, <1>consetetur</1> sadipscing elitr, sed ut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges6(): void
    {
        // ins/del will invalidate inner ins/dels
        $segmentId = 766543;
        $markup = 'Lorem <ins1>ipsum <del2>dolor<4/></del2> sit</ins1> amet, consetetur sadipscing elitr<del1>, sed <ins2>diam nonumy </ins2>eirmod tempor</del1> invidunt ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr invidunt ut.';
        $markupNoTC = 'Lorem ipsum dolor<4/> sit amet, consetetur sadipscing elitr invidunt ut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges7(): void
    {
        // ins/del will invalidate inner ins/dels
        $segmentId = 766543;
        $markup = 'Lorem <ins1>ipsum <ins2><ins3>dolor</ins3><4/></ins2> sit amet</ins1>, consetetur sadipscing elitr<del1>, sed <del2>diam<3/><del3> nonumy</del3> eirmod</del2> tempor</del1> invidunt ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr invidunt ut.';
        $markupNoTC = 'Lorem ipsum dolor<4/> sit amet, consetetur sadipscing elitr invidunt ut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges8(): void
    {
        // ins/del will invalidate inner ins/dels
        $segmentId = 766543;
        $markup = 'Lorem <ins1>ipsum <del2><ins3>dolor</ins3><4/></del2> sit amet</ins1>, consetetur sadipscing elitr<del1>, sed <ins2>diam<3/><del3> nonumy</del3> eirmod</ins2> tempor</del1> invidunt ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr invidunt ut.';
        $markupNoTC = 'Lorem ipsum dolor<4/> sit amet, consetetur sadipscing elitr invidunt ut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges9(): void
    {
        // ins/del will invalidate inner ins/dels
        $segmentId = 766543;
        $markup = 'Lorem <ins1>ipsum <del2><del3>dolor</del3><4/></del2> sit amet</ins1>, consetetur sadipscing elitr<del1>, sed <ins2>diam<3/><ins3> nonumy</ins3> eirmod</ins2> tempor</del1> invidunt ut.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr invidunt ut.';
        $markupNoTC = 'Lorem ipsum dolor<4/> sit amet, consetetur sadipscing elitr invidunt ut.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges10(): void
    {
        // ins/del will invalidate inner ins/dels
        $segmentId = 766543;
        $markup = 'Lorem ipsum <del1>dolor sit amet<ins1><3/><4/></ins1></del1>, consetetur sadipscing elitr.';
        $textNoTC = 'Lorem ipsum , consetetur sadipscing elitr.';
        $markupNoTC = 'Lorem ipsum , consetetur sadipscing elitr.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testTrackChanges11(): void
    {
        // ins/del will invalidate inner ins/dels
        $segmentId = 766543;
        $markup = 'Lorem ipsum <ins1>dolor sit amet<del1><3/><4/></del1></ins1>, consetetur sadipscing elitr.';
        $textNoTC = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.';
        $markupNoTC = 'Lorem ipsum dolor sit amet<3/><4/>, consetetur sadipscing elitr.';

        $this->createReplacedTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testRealData1(): void
    {
        $segmentId = 766543;
        // testing "real" segment content with a sequence of connected segment-tags
        $markup = '<div class="open 6270742069643d2231222063747970653d22782d6974616c69633b636f6c6f723a3030303030303b756e6465726c696e653a73696e676c653b223e266c743b72756e312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-italic;color:000000;underline:single;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-italic;color:000000;underline:single;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;</span></div>L<ins class="trackchanges ownttip" data-usertrackingid="17935" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-08-22T19:18:16+02:00">’</ins>organisation nationale (réseau de <div class="term admittedTerm stemmed" title="" data-tbxid="393dbd3f-9609-41bb-9d35-117b124da0b1">distribution)</div><div class="close 6570742069643d2231223e266c743b2f72756e312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;</span></div><ins class="trackchanges ownttip" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:42+02:00"> </ins>agit <ins class="trackchanges ownttip" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:43+02:00">de façon à ce </ins><del class="trackchanges ownttip deleted" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:46+02:00">en</del><del class="trackchanges ownttip deleted" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:47+02:00"> sorte</del><del class="trackchanges ownttip deleted" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:48+02:00"> </del>que, dans le <div class="term admittedTerm exact" title="" data-tbxid="d2a84c5d-c9a5-4307-bb77-285fb9735fb0">cadre</div> de la philosophie Neuroth et des <div class="term admittedTerm exact" title="" data-tbxid="8f1f90ff-45c6-428b-aa7e-ba0682d4e9aa">normes</div> du groupe, le plus de personnes possible deviennent et restent des clientes et clients actifs et heureux grâce à ses produits et <div class="term admittedTerm exact" title="" data-tbxid="55f30ba9-d01b-4a78-a79a-c774aeebbabb">services</div> de qualité supérieure.';
        $markupNoTC = '<div class="open 6270742069643d2231222063747970653d22782d6974616c69633b636f6c6f723a3030303030303b756e6465726c696e653a73696e676c653b223e266c743b72756e312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-italic;color:000000;underline:single;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-italic;color:000000;underline:single;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;</span></div>L’organisation nationale (réseau de <div class="term admittedTerm stemmed" title="" data-tbxid="393dbd3f-9609-41bb-9d35-117b124da0b1">distribution)</div><div class="close 6570742069643d2231223e266c743b2f72756e312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;</span></div> agit de façon à ce que, dans le <div class="term admittedTerm exact" title="" data-tbxid="d2a84c5d-c9a5-4307-bb77-285fb9735fb0">cadre</div> de la philosophie Neuroth et des <div class="term admittedTerm exact" title="" data-tbxid="8f1f90ff-45c6-428b-aa7e-ba0682d4e9aa">normes</div> du groupe, le plus de personnes possible deviennent et restent des clientes et clients actifs et heureux grâce à ses produits et <div class="term admittedTerm exact" title="" data-tbxid="55f30ba9-d01b-4a78-a79a-c774aeebbabb">services</div> de qualité supérieure.';
        // the same markup but TrackChanges merged to a single tag - must obviously bring identical results ...
        $markup2 = '<div class="open 6270742069643d2231222063747970653d22782d6974616c69633b636f6c6f723a3030303030303b756e6465726c696e653a73696e676c653b223e266c743b72756e312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-italic;color:000000;underline:single;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-italic;color:000000;underline:single;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;</span></div>L<ins class="trackchanges ownttip" data-usertrackingid="17935" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-08-22T19:18:16+02:00">’</ins>organisation nationale (réseau de <div class="term admittedTerm stemmed" title="" data-tbxid="393dbd3f-9609-41bb-9d35-117b124da0b1">distribution)</div><div class="close 6570742069643d2231223e266c743b2f72756e312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;</span></div><ins class="trackchanges ownttip" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:42+02:00"> </ins>agit <ins class="trackchanges ownttip" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:43+02:00">de façon à ce </ins><del class="trackchanges ownttip deleted" data-usertrackingid="17874" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-08-21T16:20:46+02:00">en sorte </del>que, dans le <div class="term admittedTerm exact" title="" data-tbxid="d2a84c5d-c9a5-4307-bb77-285fb9735fb0">cadre</div> de la philosophie Neuroth et des <div class="term admittedTerm exact" title="" data-tbxid="8f1f90ff-45c6-428b-aa7e-ba0682d4e9aa">normes</div> du groupe, le plus de personnes possible deviennent et restent des clientes et clients actifs et heureux grâce à ses produits et <div class="term admittedTerm exact" title="" data-tbxid="55f30ba9-d01b-4a78-a79a-c774aeebbabb">services</div> de qualité supérieure.';
        $textNoTC = 'L’organisation nationale (réseau de distribution) agit de façon à ce que, dans le cadre de la philosophie Neuroth et des normes du groupe, le plus de personnes possible deviennent et restent des clientes et clients actifs et heureux grâce à ses produits et services de qualité supérieure.';

        $this->createTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
        $this->createTrackChangesTest($segmentId, $markup2, $textNoTC, $markupNoTC);
    }

    public function testRealData2(): void
    {
        $segmentId = 766543;
        // testing "real" segment content with a sequence of connected segment-tags
        $markup = 'Senzor tlaka AD<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d22616e79206e756d62657220286e6f742070617274206f662068797068656e6174656420636f6d706f756e64292220736f757263653d2237222069736f3d223722207461726765743d2237222072656765783d22303965777431474d4e7443316a4e58554146506132706f61396f713630596d365659363655596558484e3532654d2f686c73505444732b4a3164514841413d3d222f number internal-tag ownttip"><span class="short" title="&lt;1/&gt; CP: any number (not part of hyphenated compound)">&lt;1/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="7" data-target="7"></span></div> ... <del class="trackchanges ownttip deleted" data-usertrackingid="65812" data-usercssnr="usernr2" data-workflowstep="review2ndlanguage1" data-timestamp="2025-08-27T14:56:49+02:00"><div class="single 6e756d62657220747970653d226b6565702d636f6e74656e7422206e616d653d226361706974616c206c6574746572202b206469676974202d20696e20544d206f6e6c792220736f757263653d2241443130222069736f3d224144313022207461726765743d2241443130222072656765783d223039654971346b70726f6e5271446d3051464e44493970524e796f6d4a565a544738794b6a556d7069556b42737a515255706f614b6a58524d586f36316c6232696a48464d5a71484673527136674d41222f number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: capital letter + digit - in TM only">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="4" data-source="AD10" data-target="AD10"></span></div></del>';
        $textNoTC = 'Senzor tlaka AD ... ';
        $markupNoTC = 'Senzor tlaka AD<div class="single 6e756d62657220747970653d22696e746567657222206e616d653d22616e79206e756d62657220286e6f742070617274206f662068797068656e6174656420636f6d706f756e64292220736f757263653d2237222069736f3d223722207461726765743d2237222072656765783d22303965777431474d4e7443316a4e58554146506132706f61396f713630596d365659363655596558484e3532654d2f686c73505444732b4a3164514841413d3d222f number internal-tag ownttip"><span class="short" title="&lt;1/&gt; CP: any number (not part of hyphenated compound)">&lt;1/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="7" data-target="7"></span></div> ... ';

        $this->createTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testRealData3(): void
    {
        $segmentId = 766543;
        // testing "real" segment content with a sequence of connected segment-tags
        $markup = 'Utilise<ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:46+04:00">z</ins> <div class="open 6270742069643d2231222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id="1" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;</span></div>13<div class="close 6570742069643d2231223e266c743b2f72756e312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id="1"&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;</span></div> <div class="open 6270742069643d2232222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:49+04:00">p</ins><del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:48+04:00">P</del>laques de poids<div class="close 6570742069643d2232223e266c743b2f72756e322667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;2&quot;&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;ept id="2"&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;</span></div> + <del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:57+04:00">Étui de plongée invisible</del><del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:59+04:00"> </del><div class="open 6270742069643d2233222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e332667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;3&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;bpt id="3" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;</span></div><del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:02+04:00">Bloc de poids</del><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:03+04:00">plomb </ins><div class="close 6570742069643d2233223e266c743b2f72756e332667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;3&quot;&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;ept id="3"&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;</span></div><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:08+04:00"> </ins><ins class="trackchanges ownttip" data-usertrackingid="137513" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-09-02T14:35:20+08:00">et le</ins><del class="trackchanges ownttip deleted" data-usertrackingid="137513" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-09-02T14:35:20+08:00" data-historylist="1756706348000" data-action_history_1756706348000="INS" data-usertrackingid_history_1756706348000="137172">d</del><del class="trackchanges ownttip deleted" data-usertrackingid="137513" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-09-02T14:35:19+08:00" data-historylist="1756706348000" data-action_history_1756706348000="INS" data-usertrackingid_history_1756706348000="137172">e</del><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:08+04:00"> <div class="term admittedTerm lowercase" title="" data-tbxid="272dc402-4588-4fd2-9717-c84bec38fb92">caisson de plongée</div> invisible</ins>.';
        $textNoTC = 'Utilisez 13 plaques de poids + plomb  et le caisson de plongée invisible.';
        $markupNoTC = 'Utilisez <div class="open 6270742069643d2231222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id="1" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run1&amp;gt;&lt;/bpt&gt;</span></div>13<div class="close 6570742069643d2231223e266c743b2f72756e312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id="1"&gt;&amp;lt;/run1&amp;gt;&lt;/ept&gt;</span></div> <div class="open 6270742069643d2232222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div>plaques de poids<div class="close 6570742069643d2232223e266c743b2f72756e322667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;2&quot;&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;ept id="2"&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;</span></div> + <div class="open 6270742069643d2233222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e332667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;3&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;bpt id="3" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;</span></div>plomb <div class="close 6570742069643d2233223e266c743b2f72756e332667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;3&quot;&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;ept id="3"&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;</span></div> et le <div class="term admittedTerm lowercase" title="" data-tbxid="272dc402-4588-4fd2-9717-c84bec38fb92">caisson de plongée</div> invisible.';

        $this->createTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testRealData4(): void
    {
        $segmentId = 766543;
        // testing "real" segment content with a sequence of connected segment-tags
        $markup = '<del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:57+04:00">Étui de plongée invisible</del><del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:58:59+04:00"> </del><div class="open 6270742069643d2233222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e332667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;3&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;bpt id="3" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;</span></div><del class="trackchanges ownttip deleted" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:02+04:00">Bloc de poids</del><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:03+04:00">plomb </ins><div class="close 6570742069643d2233223e266c743b2f72756e332667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;3&quot;&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;ept id="3"&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;</span></div><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:08+04:00"> </ins><ins class="trackchanges ownttip" data-usertrackingid="137513" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-09-02T14:35:20+08:00">et le</ins><del class="trackchanges ownttip deleted" data-usertrackingid="137513" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-09-02T14:35:20+08:00" data-historylist="1756706348000" data-action_history_1756706348000="INS" data-usertrackingid_history_1756706348000="137172">d</del><del class="trackchanges ownttip deleted" data-usertrackingid="137513" data-usercssnr="usernr2" data-workflowstep="reviewing2" data-timestamp="2025-09-02T14:35:19+08:00" data-historylist="1756706348000" data-action_history_1756706348000="INS" data-usertrackingid_history_1756706348000="137172">e</del><ins class="trackchanges ownttip" data-usertrackingid="137172" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-01T09:59:08+04:00">    <div class="term admittedTerm lowercase" title="" data-tbxid="272dc402-4588-4fd2-9717-c84bec38fb92">caisson de plongée</div>    invisible</ins>';
        $textNoTC = 'plomb  et le    caisson de plongée    invisible';
        $markupNoTC = '<div class="open 6270742069643d2233222063747970653d22782d626f6c643b666f6e74733a417269616c3b223e266c743b72756e332667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;3&quot; ctype=&quot;x-bold;fonts:Arial;&quot;&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;bpt id="3" ctype="x-bold;fonts:Arial;"&gt;&amp;lt;run3&amp;gt;&lt;/bpt&gt;</span></div>plomb <div class="close 6570742069643d2233223e266c743b2f72756e332667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;3&quot;&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;ept id="3"&gt;&amp;lt;/run3&amp;gt;&lt;/ept&gt;</span></div> et le    <div class="term admittedTerm lowercase" title="" data-tbxid="272dc402-4588-4fd2-9717-c84bec38fb92">caisson de plongée</div>    invisible';

        $this->createTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    public function testRealData5(): void
    {
        $segmentId = 766543;
        // testing "real" segment content with a sequence of connected segment-tags
        $markup = '<del class="trackchanges ownttip deleted" data-usertrackingid="140480" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-22T16:28:22+04:00"><div class="open 6270742069643d2232222063747970653d22782d626f6c643b636f6c6f723a3146323332393b666f6e74733a43616c696272693b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;color:1F2329;fonts:Calibri;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;color:1F2329;fonts:Calibri;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div>Pour le cloud, alimenté par AWS, <div class="close 6570742069643d2232223e266c743b2f72756e322667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;2&quot;&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;ept id="2"&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;</span></div>nous respectons les normes de conformité les plus strictes en matière de sécurité des données pour que tes fichiers soient sécurisés et tes données protégées (ISO 27001/27701, ISO 27017/27018 et CSA STAR).<ins class="trackchanges ownttip" data-usertrackingid="140480" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-22T16:28:22+04:00"><div class="open 6270742069643d2232222063747970653d22782d626f6c643b636f6c6f723a3146323332393b666f6e74733a43616c696272693b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;color:1F2329;fonts:Calibri;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;color:1F2329;fonts:Calibri;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div><div class="open 6270742069643d2232222063747970653d22782d626f6c643b636f6c6f723a3146323332393b666f6e74733a43616c696272693b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;color:1F2329;fonts:Calibri;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;color:1F2329;fonts:Calibri;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div></ins></del><ins class="trackchanges ownttip" data-usertrackingid="140480" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-22T16:28:22+04:00"><div class="open 6270742069643d2232222063747970653d22782d626f6c643b636f6c6f723a3146323332393b666f6e74733a43616c696272693b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;color:1F2329;fonts:Calibri;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;color:1F2329;fonts:Calibri;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div>Pour le cloud, propulsé par AWS,</ins><ins class="trackchanges ownttip" data-usertrackingid="140569" data-usercssnr="usernr3" data-workflowstep="translation1" data-timestamp="2025-09-23T16:26:51+02:00"> </ins><ins class="trackchanges ownttip" data-usertrackingid="140480" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2025-09-22T16:28:22+04:00"><div class="close 6570742069643d2232223e266c743b2f72756e322667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;2&quot;&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;ept id="2"&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;</span></div>nous respectons les normes de conformité les plus strictes en matière de sécurité des données afin de protéger vos fichiers et vos informations (ISO 27001/27701, ISO 27017/27018 et CSA STAR).</ins>';
        $textNoTC = 'Pour le cloud, propulsé par AWS, nous respectons les normes de conformité les plus strictes en matière de sécurité des données afin de protéger vos fichiers et vos informations (ISO 27001/27701, ISO 27017/27018 et CSA STAR).';
        $markupNoTC = '<div class="open 6270742069643d2232222063747970653d22782d626f6c643b636f6c6f723a3146323332393b666f6e74733a43616c696272693b223e266c743b72756e322667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;2&quot; ctype=&quot;x-bold;color:1F2329;fonts:Calibri;&quot;&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;bpt id="2" ctype="x-bold;color:1F2329;fonts:Calibri;"&gt;&amp;lt;run2&amp;gt;&lt;/bpt&gt;</span></div>Pour le cloud, propulsé par AWS, <div class="close 6570742069643d2232223e266c743b2f72756e322667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;2&quot;&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;ept id="2"&gt;&amp;lt;/run2&amp;gt;&lt;/ept&gt;</span></div>nous respectons les normes de conformité les plus strictes en matière de sécurité des données afin de protéger vos fichiers et vos informations (ISO 27001/27701, ISO 27017/27018 et CSA STAR).';
        $this->createTrackChangesTest($segmentId, $markup, $textNoTC, $markupNoTC);
    }

    private function createReplacedTrackChangesTest(
        int $segmentId,
        string $markup,
        string $expectedTextNoTC,
        string $expectedMarkupNoTC = null
    ): void {
        $this->createTrackChangesTest(
            $segmentId,
            $this->shortToFull($markup),
            $expectedTextNoTC,
            $this->shortToFull($expectedMarkupNoTC)
        );
    }
}
