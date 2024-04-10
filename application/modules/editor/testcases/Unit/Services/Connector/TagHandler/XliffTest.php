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
    private const TAGS = [
        '<1>' => '<div class="open 1234 internal-tag ownttip"><span class="short" title="<Variable>&quot;&gt;&lt;1&gt;</span><span class="full" data-originalid="8" data-length="-1">&lt;Variable&gt;</span></div>',
        '<2/>' => '<div class="single 1234 internal-tag ownttip"><span class="short" title="<fct:Variable />&quot;&gt;&lt;2/&gt;</span><span class="full" data-originalid="9" data-length="-1">&lt;fct:Variable /&gt;</span></div>',
        '</1>' => '<div class="close 1234 internal-tag ownttip"><span class="short" title="</Variable>&quot;&gt;&lt;/1&gt;</span><span class="full" data-originalid="8" data-length="-1">&lt;/Variable&gt;</span></div>',
        '<3>' => '<div class="open 1234 internal-tag ownttip"><span class="short" title="<Variable>&quot;&gt;&lt;3&gt;</span><span class="full" data-originalid="10" data-length="-1">&lt;Variable&gt;</span></div>',
        '<4/>' => '<div class="single 1234 internal-tag ownttip"><span class="short" title="<fct:Variable />&quot;&gt;&lt;4/&gt;</span><span class="full" data-originalid="11" data-length="-1">&lt;fct:Variable /&gt;</span></div>',
        '</3>' => '<div class="close 1234 internal-tag ownttip"><span class="short" title="</Variable>&quot;&gt;&lt;/3&gt;</span><span class="full" data-originalid="10" data-length="-1">&lt;/Variable&gt;</span></div>',
        '<5/>' => '<div class="single 1234 internal-tag ownttip"><span class="short" title="<fct:Variable />&quot;&gt;&lt;5/&gt;</span><span class="full" data-originalid="12" data-length="-1">another tag</span></div>',
        '<6/>' => '<div class="single 1234 internal-tag ownttip"><span class="short" title="<fct:Variable />&quot;&gt;&lt;6/&gt;</span><span class="full" data-originalid="13" data-length="-1">another tag</span></div>',
    ];

    private Xliff $xliffUnderTestPaired;

    private Xliff $xliffUnderTest;

    protected function setUp(): void
    {
        $this->xliffUnderTest = new Xliff([
            'gTagPairing' => false,
        ]);
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
        ],
            [
                'queriesToTest' => 'plain text',
                'expectedQueries' => 'plain text',
                'expectedQueriesPaired' => 'plain text',
                'resultsToQueries' => 'plain  text <it>converted to  a single tag</it>translated',
                'restoredResults' => 'plain <div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;1/&gt;: Thin Space" class="short">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>text <div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>translated',
                'restoredResultsPaired' => 'plain <div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;1/&gt;: Thin Space" class="short">&lt;1/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>text <div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="toignore-1" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>translated',
            ],
            [
                //complex test of bpt and ept tags and whitespace tag
                'queriesToTest' => '<div class="open 6270742069643d2231223e266c743b636f6e74656e742d312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;content-1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;content-1&gt;</span></div>Δ<div class="close 6570742069643d2231223e266c743b2f636f6e74656e742d312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/content-1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/content-1&gt;</span></div>H = 1.00<div class="single 70682069643d2232223e266c743b636f6e74656e742d322f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;content-2/&gt;">&lt;2/&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;content-2/&gt;</span></div>m text.',
                'expectedQueries' => '<bx id="1" rid="1"/>Δ<ex id="2" rid="1"/>H = 1.00<x id="3"/>m text.',
                'expectedQueriesPaired' => '<g id="1">Δ</g>H = 1.00<x id="3"/>m text.',
                'resultsToQueries' => '<bx id="1" rid="1"/>Δ<ex id="2" rid="1"/><bpt id="6" rid="4"/>H = 1,00 m translatedtext.<ept id="7" rid="5"/>',
                'restoredResults' => '<div class="open 6270742069643d2231223e266c743b636f6e74656e742d312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;content-1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;content-1&gt;</span></div>Δ<div class="close 6570742069643d2231223e266c743b2f636f6e74656e742d312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/content-1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/content-1&gt;</span></div><div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;4/&gt;</span><span data-originalid="toignore-4" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>H = 1,00<div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;3/&gt;: Thin Space" class="short">&lt;3/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>m translatedtext.<div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;5/&gt;</span><span data-originalid="toignore-5" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>',
                'restoredResultsPaired' => '<div class="open 6270742069643d2231223e266c743b636f6e74656e742d312667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;content-1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;content-1&gt;</span></div>Δ<div class="close 6570742069643d2231223e266c743b2f636f6e74656e742d312667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/content-1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/content-1&gt;</span></div><div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;4/&gt;</span><span data-originalid="toignore-4" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>H = 1,00<div class="single 636861722074733d2265323830383922206c656e6774683d2231222f char internal-tag ownttip"><span title="&lt;3/&gt;: Thin Space" class="short">&lt;3/&gt;</span><span data-originalid="char" data-length="1" class="full">□</span></div>m translatedtext.<div class="single ignoreInEditor internal-tag ownttip"><span title="&lt;AdditionalTagFromTM/&gt;" class="short">&lt;5/&gt;</span><span data-originalid="toignore-5" data-length="-1" class="full">&lt;AdditionalTagFromTM/&gt;</span></div>',
            ],
            [
                'queriesToTest' => 'Test <1><2/>to the <3><4/></3> Text.</1>',
                'expectedQueries' => 'Test <bx id="1" rid="1"/><x id="2"/>to the <bx id="3" rid="2"/><x id="4"/><ex id="5" rid="2"/> Text.<ex id="6" rid="1"/>',
                'expectedQueriesPaired' => 'Test <g id="1"><x id="2"/>to the <g id="3"><x id="4"/></g> Text.</g>',
                'resultsToQueries' => 'Test <bx id="1" rid="1"/><x id="2"/>to the <bx id="3" rid="2"/><x id="4"/><ex id="5" rid="2"/> Text.<ex id="6" rid="1"/>',
                'restoredResults' => 'Test <1><2/>to the <3><4/></3> Text.</1>',
                'restoredResultsPaired' => 'Test <1><2/>to the <3><4/></3> Text.</1>',
            ],
            'TRANSLATE-3745 test flipped tag positions in TM result' => [
                'queriesToTest' => 'Test <1><2/></1>to the <3><4/></3> Text.',
                'expectedQueries' => 'Test <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/>to the <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedQueriesPaired' => 'Test <g id="1"><x id="2"/></g>to the <g id="4"><x id="5"/></g> Text.',
                'resultsToQueries' => 'Test <bx id="4" rid="2"/><x id="2"/><ex id="6" rid="2"/>to the <bx id="1" rid="1"/><x id="5"/><ex id="3" rid="1"/> Text.',
                'restoredResults' => 'Test <3><2/></3>to the <1><4/></1> Text.',
                'restoredResultsPaired' => 'Test <3><2/></3>to the <1><4/></1> Text.',
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
    ): void {
        //since the tag map is stored internally, we have to test query and result restore directly after each other
        $this->assertEquals(
            $expectedQueries,
            $this->xliffUnderTest->prepareQuery($this->convertToInternalTags($queriesToTest)),
            'prepared query is not as expected!'
        );
        $this->assertEquals(
            $this->convertToInternalTags($restoredResults),
            $this->xliffUnderTest->restoreInResult($resultsToQueries),
            'restored result is not as expected!'
        );

        $this->assertEquals(
            $expectedQueriesPaired,
            $this->xliffUnderTestPaired->prepareQuery($this->convertToInternalTags($queriesToTest)),
            'prepared paired query is not as expected!'
        );
        $this->assertEquals(
            $this->convertToInternalTags($restoredResultsPaired),
            $this->xliffUnderTestPaired->restoreInResult($resultsToQueries),
            'restored paired result is not as expected!'
        );
    }

    public function provideDataInputMap(): array
    {
        return [
            'raw text test' => [
                'source' => 'Ich bin ein Test',
                'target' => 'I am a test',
                'expectedSource' => 'Ich bin ein Test',
                'expectedTarget' => 'I am a test',
            ],
            'apply source tags to target' => [
                'source' => $this->convertToInternalTags('Teste <1><2/></1> den <3><4/></3> Text.'),
                'target' => $this->convertToInternalTags('test <1><2/></1> the <3><4/></3> text.'),
                'expectedSource' => 'Teste <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> den <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedTarget' => 'test <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> the <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> text.',
            ],
            'apply source tags to target flipped - pairs only' => [
                'source' => $this->convertToInternalTags('Teste <1><2/></1> den <3><4/></3> Text.'),
                'target' => $this->convertToInternalTags('test <3><2/></3> the <1><4/></1> text.'),
                //for t5memory the bx/ex/x tags must have the same IDs for the same tas, so when flipping internal tags
                // the resultung bx/ex must be flipped too
                'expectedSource' => 'Teste <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> den <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedTarget' => 'test <bx id="4" rid="2"/><x id="2"/><ex id="6" rid="2"/> the <bx id="1" rid="1"/><x id="5"/><ex id="3" rid="1"/> text.',
            ],
            'apply source tags to target flipped - singles only' => [
                'source' => $this->convertToInternalTags('Teste <1><2/></1> den <3><4/></3> Text.'),
                'target' => $this->convertToInternalTags('test <1><4/></1> the <3><2/></3> text.'),
                //for t5memory the bx/ex/x tags must have the same IDs for the same tas, so when flipping internal tags
                // the resultung bx/ex must be flipped too
                'expectedSource' => 'Teste <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> den <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedTarget' => 'test <bx id="1" rid="1"/><x id="5"/><ex id="3" rid="1"/> the <bx id="4" rid="2"/><x id="2"/><ex id="6" rid="2"/> text.',
            ],
            'apply source tags to target flipped - both and additional tags' => [
                'source' => $this->convertToInternalTags('Teste <1><2/></1> den <3><4/></3> Text.'),
                'target' => $this->convertToInternalTags('test <6/><3><4/></3> <5/>the <1><2/></1> text.'),
                //for t5memory the bx/ex/x tags must have the same IDs for the same tas, so when flipping internal tags
                // the resultung bx/ex must be flipped too
                'expectedSource' => 'Teste <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> den <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedTarget' => 'test <x id="7"/><bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> <x id="8"/>the <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> text.',
            ],
            'apply source tags to target flipped - both and missing tags' => [
                'source' => $this->convertToInternalTags('Teste <6/><1><2/></1> <5/>den <3><4/></3> Text.'),
                'target' => $this->convertToInternalTags('test <3><4/></3> the <1><2/></1> text.'),
                //for t5memory the bx/ex/x tags must have the same IDs for the same tas, so when flipping internal tags
                // the resultung bx/ex must be flipped too
                'expectedSource' => 'Teste <x id="1"/><bx id="2" rid="1"/><x id="3"/><ex id="4" rid="1"/> <x id="5"/>den <bx id="6" rid="2"/><x id="7"/><ex id="8" rid="2"/> Text.',
                'expectedTarget' => 'test <bx id="6" rid="2"/><x id="7"/><ex id="8" rid="2"/> the <bx id="2" rid="1"/><x id="3"/><ex id="4" rid="1"/> text.',
            ],
            'test XML strict vs loose' => [
                'source' => str_replace('Variable&gt;', 'Variable>', $this->convertToInternalTags('Teste <1><2/></1> den <3><4/></3> Text.')),
                //it may happen that XML in source is loose with > instead &lt; at the end of replaced tags, but in target its strict
                'target' => $this->convertToInternalTags('test <3><2/></3> the <1><4/></1> text.'),
                'expectedSource' => 'Teste <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> den <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedTarget' => 'test <bx id="4" rid="2"/><x id="2"/><ex id="6" rid="2"/> the <bx id="1" rid="1"/><x id="5"/><ex id="3" rid="1"/> text.',
            ],
            'test XML loose vs strict' => [
                'source' => $this->convertToInternalTags('Teste <1><2/></1> den <3><4/></3> Text.'),
                //it may happen that XML in source is loose with > instead &lt; at the end of replaced tags, but in target its strict
                'target' => str_replace('Variable&gt;', 'Variable>', $this->convertToInternalTags('test <3><2/></3> the <1><4/></1> text.')),
                'expectedSource' => 'Teste <bx id="1" rid="1"/><x id="2"/><ex id="3" rid="1"/> den <bx id="4" rid="2"/><x id="5"/><ex id="6" rid="2"/> Text.',
                'expectedTarget' => 'test <bx id="4" rid="2"/><x id="2"/><ex id="6" rid="2"/> the <bx id="1" rid="1"/><x id="5"/><ex id="3" rid="1"/> text.',
            ],
        ];
    }

    /**
     * @dataProvider provideDataInputMap
     */
    public function testInputTagMap(string $source, string $target, string $expectedSource, string $expectedTarget): void
    {
        $source = $this->xliffUnderTest->prepareQuery($source);
        $this->xliffUnderTest->setInputTagMap($this->xliffUnderTest->getTagMap());
        $target = $this->xliffUnderTest->prepareQuery($target);

        $this->assertEquals($expectedSource, $source, 'prepared source');
        $this->assertEquals($expectedTarget, $target, 'prepared target with source tags');
    }

    private function convertToInternalTags(string $query): string
    {
        return str_replace(array_keys(self::TAGS), array_values(self::TAGS), $query);
    }
}
