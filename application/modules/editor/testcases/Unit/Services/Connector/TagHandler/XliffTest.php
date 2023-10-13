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

namespace MittagQI\Translate5\Test\Unit\Services\Connector\TagHandler;

use editor_Services_Connector_TagHandler_Xliff as Xliff;

/**
 * Testcase for TRANSLATE-2895 tests the boundary / framing tag removing in the XLF import
 * For details see the issue.
 */
class XliffTest extends \editor_Test_UnitTest
{

    private Xliff $xliffUnderTestPaired;
    private Xliff $xliffUnderTest;

    protected function setUp(): void
    {
        $this->xliffUnderTest = new Xliff(['gTagPairing' => false]);
        $this->xliffUnderTestPaired = new Xliff();
    }

    public function provideData(): array
    {
        return [[
            'queriesToTest' => 'plain text',
            'expectedQueries' => 'plain text',
            'expectedQueriesPaired' => 'plain text',
            'resultsToQueries' => 'plain text translated',
            'restoredResults' => 'plain text translated',
            'restoredResultsPaired' => 'plain text translated',
        ],[
            'queriesToTest' => 'plain text',
            'expectedQueries' => 'plain text',
            'expectedQueriesPaired' => 'plain text',
            'resultsToQueries' => 'plain  text <it>converted to  a single tag</it>translated',
            'restoredResults' => 'plain <div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;1/&gt;: Thin Space" class="short">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>text <div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>translated',
            'restoredResultsPaired' => 'plain <div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;1/&gt;: Thin Space" class="short">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>text <div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>translated',
        ],[
            //complex test of bpt and ept tags and whitespace tag
            'queriesToTest' => '<div class="open 6270742069643d2231223e266c743b636f6e74656e742d312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;content-1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;content-1&gt;</span></div>Δ<div class="close 6570742069643d2231223e266c743b2f636f6e74656e742d312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/content-1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/content-1&gt;</span></div>H = 1.00<div class="single 70682069643d2232223e266c743b636f6e74656e742d322f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;content-2/&gt;">&lt;2/&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;content-2/&gt;</span></div>m text.',
            'expectedQueries' => '<bx id="1" rid="1"/>Δ<ex id="2" rid="1"/>H = 1.00<x id="3"/>m text.',
            'expectedQueriesPaired' => '<g id="1">Δ</g>H = 1.00<x id="3"/>m text.',
            'resultsToQueries' => '<bx id="1" rid="1"/>Δ<ex id="2" rid="1"/><bpt id="6" rid="4"/>H = 1,00 m translatedtext.<ept id="7" rid="5"/>',
            'restoredResults' => '<div class="open 6270742069643d2231223e266c743b636f6e74656e742d312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;content-1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;content-1&gt;</span></div>Δ<div class="close 6570742069643d2231223e266c743b2f636f6e74656e742d312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/content-1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/content-1&gt;</span></div><div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;4/&gt;</span><span data-originalid="toignore-4" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>H = <div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c742067656e657269632220736f757263653d22312c3030222069736f3d223122207461726765743d22222f number internal-tag ownttip"><span title="&lt;4/&gt;: Number" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="-1" data-source="1,00" data-target="" class="full"></span></div><div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;3/&gt;: Thin Space" class="short">&lt;3/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>m translatedtext.<div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;5/&gt;</span><span data-originalid="toignore-5" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>',
            'restoredResultsPaired' => '<div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;4/&gt;</span><span data-originalid="toignore-4" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>Δ<div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;5/&gt;</span><span data-originalid="toignore-5" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div><div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;6/&gt;</span><span data-originalid="toignore-6" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>H = <div class="single 6e756d62657220747970653d22666c6f617422206e616d653d2264656661756c742067656e657269632220736f757263653d22312c3030222069736f3d223122207461726765743d22222f number internal-tag ownttip"><span title="&lt;4/&gt;: Number" class="short">&lt;4/&gt;</span><span data-originalid="number" data-length="-1" data-source="1,00" data-target="" class="full"></span></div><div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;3/&gt;: Thin Space" class="short">&lt;3/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>m translatedtext.<div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;7/&gt;</span><span data-originalid="toignore-7" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>',
        ]];
    }

    /**
     * @dataProvider provideData
     */
    public function testPrepareQuery(
        string $queriesToTest,
        string $expectedQueries,
        string $expectedQueriesPaired,
        string $resultsToQueries,
        string $restoredResults,
        string $restoredResultsPaired
    )
    {
        //since the tag map is stored internally, we have to test query and result restore directly after each other
        $this->assertEquals(
            $expectedQueries,
            $this->xliffUnderTest->prepareQuery($queriesToTest),
            'prepared query is not as expected!'
        );
        $this->assertEquals(
            $restoredResults,
            $this->xliffUnderTest->restoreInResult($resultsToQueries),
            'restored result is not as expected!'
        );

        $this->assertEquals(
            $expectedQueriesPaired,
            $this->xliffUnderTestPaired->prepareQuery($queriesToTest),
            'prepared paired query is not as expected!'
        );
        $this->assertEquals(
            $restoredResultsPaired,
            $this->xliffUnderTestPaired->restoreInResult($resultsToQueries),
            'restored paired result is not as expected!'
        );
    }
}
