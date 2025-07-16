<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\Services\Connector\TagHandler;

use editor_Services_Connector_TagHandler_T5MemoryXliff;
use PHPUnit\Framework\TestCase;
use ZfExtended_Factory;

class T5MemoryXliffTest extends TestCase
{
    public function testPrepareQuery(): void
    {
        $tagHandler = ZfExtended_Factory::get(
            editor_Services_Connector_TagHandler_T5MemoryXliff::class,
            [[
                'gTagPairing' => false,
            ]]
        );
        $tagHandler->setLanguages(5, 6);

        self::assertSame(
            '<t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA=" n="123,456.789"/> Übersetzungsbüro [ ] 24translate <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/> and <t5:n id="3" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>',
            $tagHandler->prepareQuery('<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default simple" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123,456.789" class="full"></span></div> Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default simple" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default simple" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>')
        );
    }

    /**
     * @dataProvider restoreProvider
     */
    public function testRestoreInResult(string $source, string $expected, string $resultString): void
    {
        $tagHandler = ZfExtended_Factory::get(
            editor_Services_Connector_TagHandler_T5MemoryXliff::class,
            [[
                'gTagPairing' => false,
            ]]
        );
        $tagHandler->setLanguages(5, 6);

        // we need this step to fill \editor_Services_Connector_TagHandler_T5MemoryXliff::$numberTagMap
        $tagHandler->prepareQuery($source);

        self::assertSame(
            $expected,
            $tagHandler->restoreInResult($resultString)
        );
    }

    public function restoreProvider(): iterable
    {
        yield 'Direct tag order' => [
            'source' => '<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default with comma thousand decimal dot" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123.456,789" class="full"></span></div> Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default Y-m-d" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>',
            'expected' => '<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default with comma thousand decimal dot" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123.456,789" class="full"></span></div> Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default Y-m-d" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>',
            'resultString' => '<t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA=" n="123,456.789"/> Übersetzungsbüro [ ] 24translate <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/> and <t5:n id="3" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>',
        ];

        yield 'mix tag order' => [
            'source' => '<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default with comma thousand decimal dot" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123.456,789" class="full"></span></div> Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default Y-m-d" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>',
            'expected' => 'Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default Y-m-d" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> float here <div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default with comma thousand decimal dot" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123.456,789" class="full"></span></div>',
            'resultString' => 'Übersetzungsbüro [ ] 24translate <t5:n id="3" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/> float here <t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA=" n="123,456.789"/>',
        ];

        yield 'mix tag order with not found matches' => [
            'source' => '<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default with comma thousand decimal dot" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123.456,789" class="full"></span></div> Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032332d30392d3135222069736f3d22323032332d30392d313522207461726765743d22392f31352f3233222f number internal-tag ownttip"><span title="&lt;3/&gt; CP: default Y-m-d" class="short">&lt;3/&gt;</span><span data-originalid="number" data-length="7" data-source="2023-09-15" data-target="9/15/23" class="full"></span></div> and <div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420592d6d2d642220736f757263653d22323032342d31302d3139222069736f3d22323032342d31302d313922207461726765743d2231302f31392f3234222f number internal-tag ownttip"><span title="&lt;4/&gt; CP: default Y-m-d" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="8" data-source="2024-10-19" data-target="10/19/24" class="full"></span></div>',
            'expected' => 'Übersetzungsbüro [<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span title="&lt;2/&gt;: No-Break Space (NBSP)" class="short">&lt;2/&gt;</span><span data-originalid="char" data-length="1" class="full">⎵</span></div>] 24translate 2024-10-19 and 2023-09-15 float here <div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c74207769746820636f6d6d612074686f7573616e6420646563696d616c20646f742220736f757263653d223132332c3435362e373839222069736f3d223132333435362e37383922207461726765743d223132332e3435362c373839222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a715462514d61725630617732724e5541636f79425443307748614d586b364b74716145526f776653714b4b7057614f6841324f42714a6f59545533395567413d222f number internal-tag ownttip"><span title="&lt;1/&gt; CP: default with comma thousand decimal dot" class="short">&lt;1/&gt;</span><span data-originalid="number" data-length="11" data-source="123,456.789" data-target="123.456,789" class="full"></span></div>',
            'resultString' => 'Übersetzungsbüro [ ] 24translate <t5:n id="3" r="PUy5DYAwDFyG4k4iJA40zBKbig0oMbvjIIXmfl2GXn64gtDz3p6E0iTt5tJKquaf4Z8GVosm5BokY0BAl341kY55qE6uZH4B" n="2024-10-19"/> and <t5:n id="2" r="PUy5DYAwDFyG4k4iJA40zBKbig0oMbvjIIXmfl2GXn64gtDz3p6E0iTt5tJKquaf4Z8GVosm5BokY0BAl341kY55qE6uZH4B" n="2023-09-15"/> float here <t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA=" n="123,456.789"/>',
        ];

        yield 'different tag number' => [
            'source' => '<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c742067656e65726963207769746820636f6d6d612220736f757263653d22302c31222069736f3d22302e3122207461726765743d22302c31222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a4b30426b7a4570326a55476d6a7041536c4e444930595070464a4655374e4751776647416c45314d5a71612b715541222f number internal-tag ownttip" style=""><span class="short" title="<1/>; CP: default generic with comma" style="" id="ext-element-355">&lt;1/&gt;</span><span class="full" data-originalid="number" data-length="3" data-source="0,1" data-target="0,1" style="" id="ext-element-354"></span></div> ... <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c6520287769746820756e697473292220736f757263653d223130222069736f3d22313022207461726765743d223130222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a3061364a53644855314643744f6253684a71776d4e37636d4f796d7a704b6134707141347379597073616a474e7943784a74645255304d6a4f6b5a5078387261586a45575a4a434b706d594e694b714a306454554c775541222f number internal-tag ownttip" style=""><span class="short" title="<2/> CP: default simple" style="" id="ext-element-356">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="2" data-source="10" data-target="10" style="" id="ext-element-357"></span></div> bar elektrisch betätigt (Vorsteuerung durch Proportional-Druckregelventil mit LED-Anzeige, 7-Segment), Sollwert <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2230222069736f3d223022207461726765743d2230222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a3061364a53644855314e43496a744854736261795634774671565052314b7742555455786d7072367051413d222f number internal-tag ownttip" style=""><span class="short" title="<3/> CP: default simple" style="">&lt;3/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="0" data-target="0" style=""></span></div> ... <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c6520287769746820756e697473292220736f757263653d223130222069736f3d22313022207461726765743d223130222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a3061364a53644855314643744f6253684a71776d4e37636d4f796d7a704b6134707141347379597073616a474e7943784a74645255304d6a4f6b5a5078387261586a45575a4a434b706d594e694b714a306454554c775541222f number internal-tag ownttip" style=""><span class="short" title="<4/> CP: default simple" style="" id="ext-element-94">&lt;4/&gt;</span><span class="full" data-originalid="number" data-length="2" data-source="10" data-target="10" style="" id="ext-element-95"></span></div>V',
            'expected' => '<div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c742067656e65726963207769746820636f6d6d612220736f757263653d22302c31222069736f3d22302e3122207461726765743d22302c31222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a4b30426b7a4570326a55476d6a7041536c4e444930595070464a4655374e4751776647416c45314d5a71612b715541222f number internal-tag ownttip"><span title="&lt;/&gt; CP: default generic with comma" class="short">&lt;/&gt;</span><span data-originalid="number" data-length="3" data-source="0,1" data-target="0,1" class="full"></span></div> ... <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2230222069736f3d223022207461726765743d2230222072656765783d22303965494b61364a71346e52304e53493174574f746465494e7453316a49314a3061364a53644855314e43496a744854736261795634774671565052314b7742555455786d7072367051413d222f number internal-tag ownttip"><span title="&lt;/&gt; CP: default simple" class="short">&lt;/&gt;</span><span data-originalid="number" data-length="1" data-source="0" data-target="0" class="full"></span></div> bar elektrisch betätigt (Vorsteuerung durch Proportional-Druckregelventil mit LED-Anzeige, 7-Segment), Sollwert 4 ... 20mA',
            'resultString' => '<t5:n id="1" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA" n="0,1"/> ... <t5:n id="2" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="10"/> bar elektrisch betätigt (Vorsteuerung durch Proportional-Druckregelventil mit LED-Anzeige, 7-Segment), Sollwert <t5:n id="3" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="4"/> ... <t5:n id="4" r="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCIjtHTsbayV4wFqVPR1KwBUTUxmpr6pQA=" n="20"/>mA',
        ];
    }
}
