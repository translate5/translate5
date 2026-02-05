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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\ZfExtended;

use MittagQI\ZfExtended\Sanitizer;
use MittagQI\ZfExtended\Sanitizer\SegmentContentException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Zend_Exception;
use ZfExtended_BadRequest;
use ZfExtended_SecurityException;

class HttpSanitizerTest extends TestCase
{
    /**
     * @dataProvider stringData
     */
    public function testString($input, $output): void
    {
        //print_r(Sanitizer::markup("<ins class=\"trackchanges ownttip\" data-usertrackingid=\"101759\" data-usercssnr=\"usernr2\" data-workflowstep=\"translatorCheck2\" data-timestamp=\"2025-03-18T11:22:41+00:00\">F\u00e5 </ins><ins class=\"trackchanges ownttip\" data-usertrackingid=\"101357\" data-usercssnr=\"usernr1\" data-workflowstep=\"reviewing1\" data-timestamp=\"2025-03-11T13:39:44+00:00\" id=\"ext-element-523\">Miele</ins><ins class=\"trackchanges ownttip\" data-usertrackingid=\"101357\" data-usercssnr=\"usernr1\" data-workflowstep=\"reviewing1\" data-timestamp=\"2025-03-11T13:39:44+00:00\" id=\"ext-element-523\"> Assistant</ins><del class=\"trackchanges ownttip deleted\" data-usertrackingid=\"101759\" data-usercssnr=\"usernr2\" data-workflowstep=\"translatorCheck2\" data-timestamp=\"2025-03-18T11:22:29+00:00\" data-historylist=\"1741700384000\" data-action_history_1741700384000=\"INS\" data-usertrackingid_history_1741700384000=\"101357\">F\u00e5<\/del><del class=\"trackchanges ownttip deleted\" data-usertrackingid=\"101357\" data-usercssnr=\"usernr1\" data-workflowstep=\"reviewing1\" data-timestamp=\"2025-03-11T14:39:53+01:00\" style=\"visibility: visible;\">Overvind dem med lidt smart hj\u00e6lp.</del><img src=\"data:image\/gif;base64,R0lGODlhAQABAID\/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==\" class=\"duplicatesavecheck\" data-segmentid=\"19510384\" data-fieldname=\"targetEdit\">"));
        $this->assertEquals($output, Sanitizer::string($input), 'Sanitized result is not as expected');
    }

    /**
     * @dataProvider markupData
     */
    public function testMarkup(string $input, ?string $exception): void
    {
        if ($exception !== null) {
            $this->expectException($exception);
        }
        $this->assertEquals($input, Sanitizer::markup($input), 'Sanitized result is not as expected');
    }

    public function markupData(): array
    {
        return [
            'plain' => [
                'Hello World',
                null,
            ],
            'ordinary HTML' => [
                '<b>BOLD</b>',
                null,
            ],
            'evil HTML' => [
                '<b><img src=x onerror=\'alert(1)\' >BOLD</b>',
                ZfExtended_SecurityException::class,
            ],
        ];
    }

    /**
     * @dataProvider validMarkupData
     * @throws SegmentContentException
     * @throws ZfExtended_BadRequest
     * @throws Zend_Exception
     */
    public function testSegmentContent(string $input, ?string $expected = null): void
    {
        $expected ??= $input;

        try {
            $this->assertEquals($expected, Sanitizer::segmentContent($input), 'Sanitized result is not as expected');
        } catch (SegmentContentException $e) {
            $this->fail(
                $e->getMessage() . ': ' .
                $this->makeDiff($e->getExtra('input'), $e->getExtra('cleaned'))
            );
        }
    }

    private function makeDiff(string $input, string $cleaned): string
    {
        $regex = '#(</?[a-zA-Z_](?:[^>"\']|"[^"]*"|\'[^\']*\')*>)#i';
        $input = implode("\n", preg_split($regex, $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));
        $cleaned = implode("\n", preg_split($regex, $cleaned, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));
        $differ = new Differ(new UnifiedDiffOutputBuilder());

        return $differ->diff($input, $cleaned);
    }

    /**
     * @dataProvider invalidMarkupCausingException
     */
    public function testInvalidSegmentContent(string $input): void
    {
        $this->expectException(SegmentContentException::class);
        echo Sanitizer::segmentContent($input);
    }

    /**
     * @dataProvider invalidMarkupCausingBadRequestException
     */
    public function testInvalidTagContent(string $input): void
    {
        $this->expectException(ZfExtended_BadRequest::class);
        echo Sanitizer::segmentContent($input);
    }

    /**
     * @dataProvider invalidMarkupStripped
     */
    public function testInvalidSegmentContentStripped(string $input, string $output): void
    {
        $this->assertSame($output, Sanitizer::segmentContent($input), 'Sanitized result is not as expected');
    }

    public function stringData(): array
    {
        return [[
            'Hello World', 'Hello World',
        ], [
            'Hello <img>World', 'Hello World',
        ], [
            "Hello <img src=x onerror='alert(1)' >World", 'Hello World',
        ]];
    }

    public function validMarkupData(): array
    {
        return [
            // ---------- NEUTRAL / SIMPLE ----------
            ['Hello World öäü &amp; &szlig;'],
            ['<div>Hello <span>World</span></div>'],
            ['Text <ins class="trackchanges ownttip" data-usertrackingid="12477" data-usercssnr="usernr1" data-workflowstep="review1ndlanguage1" data-timestamp="2022-03-11T11:13:07+02:00">added</ins> and <del class="trackchanges ownttip deleted" data-usertrackingid="4270" data-usercssnr="usernr3" data-workflowstep="review1sttechnical4" data-timestamp="2024-07-05T14:14:44+02:00">removed</del>'],
            ['<div class="single 70682069643d2231222063747970653d226c62223e266c743b746578743a6c696e652d627265616b2f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot; ctype=&quot;lb&quot;&gt;&amp;lt;text:line-break/&amp;gt;&lt;/ph&gt;">&lt;1/&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ph id="1" ctype="lb"&gt;&amp;lt;text:line-break/&amp;gt;&lt;/ph&gt;</span></div> rooted in community'],

            // ---------- REAL TRANSLATE5 SEGMENTS (internal, MQM, term tags) ----------
            ['<img class="open critical qmflag ownttip qmflag-1" data-t5qid="111" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-left.png" />Issue<img class="close critical qmflag ownttip qmflag-1" data-t5qid="111" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-right.png" />'],
            ['<div class="term preferredTerm transNotDefined exact" data-tbxid="term_444" title="">Lorem <div class="term admittedTerm transFound" data-tbxid="term_555" title="">ipsum</div> dolor</div>'],
            ['<div class="term standardizedTerm" data-tbxid="91a5458c-c4b3-49e9-af3b-c4222b91275a" title="term3">Outer<div class="term supersededTerm transNotFound" data-tbxid="term_777" title="">inner</div></div>'],
            ['<div class="open 54455354 internal-tag ownttip"><span class="short" title="TEST">&lt;1&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>Lorem<div class="close 54455354 internal-tag ownttip"><span class="short" title="TEST">&lt;/1&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>'],
            ['<ins class="trackchanges ownttip" data-usertrackingid="101759" data-usercssnr="usernr2" data-workflowstep="translatorCheck2" data-timestamp="2025-03-18T11:22:41+00:00">Få </ins><del class="trackchanges ownttip deleted" data-usertrackingid="101759" data-usercssnr="usernr2" data-workflowstep="translatorCheck2" data-timestamp="2025-03-18T11:22:29+00:00">Få</del><img class="open critical qmflag ownttip qmflag-2" data-t5qid="222" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-2-left.png" />flag<img class="close critical qmflag ownttip qmflag-2" data-t5qid="222" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-2-right.png" />'],
            ['<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000001}" data-username="Manager Test" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2018-05-16T11:10:28+02:00">X</ins>'],

            // ---------- REAL TRANSLATE5 SEGMENTS complained by E1764 warnOnly check ----------
            ['<ins class="trackchanges ownttip" data-usertrackingid="10447" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2026-02-04T19:19:15+01:00">TEST</ins><del class="trackchanges ownttip deleted" data-usertrackingid="10447" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2026-02-04T19:19:17+01:00">K</del><del class="trackchanges ownttip deleted" data-usertrackingid="10447" data-usercssnr="usernr1" data-workflowstep="translation1" data-timestamp="2026-02-04T19:19:18+01:00">XTEST</del> k&#318;&#250;&#269; <div class="single 62e number internal-tag ownttip"><span class="short" title="&lt;1/&gt; CP: KeepProtectedStrings">&lt;1/&gt;</span><span class="full" data-originalid="number" data-length="2" data-source="SW" data-target="SW"></span></div> <div class="single 6e3 number internal-tag ownttip"><span class="short" title="&lt;2/&gt; CP: default generic with whitespace">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="1" data-source="9" data-target="9"></span></div>
'],

            // ---------- MALICIOUS FRAGMENTS THAT SHOULD BE STRIPPED ----------
            [
                '<div class="term preferredTerm transNotDefined exact" data-tbxid="term_444" title="">Safe<script>alert(1)</script> text</div>',
                '<div class="term preferredTerm transNotDefined exact" data-tbxid="term_444" title="">Safealert(1) text</div>',
            ],
            [
                'Safe <img class="open critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-left.png" /><script>alert(1)</script><img class="close critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-right.png" />',
                'Safe <img class="open critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-left.png" />alert(1)<img class="close critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-right.png" />',
            ],
            [
                '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;8&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST<script>evil()</script></span><iframe src="https://evil"></iframe></div>',
                '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;8&gt;</span><span class="full" data-originalid="126" data-length="-1">TESTevil()</span></div>',
            ],
            ['Satz 1. - edited1<img src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" class="duplicatesavecheck" data-segmentid="2451" data-fieldname="targetEdit">'],
        ];
    }

    public function invalidMarkupStripped(): array
    {
        return [
            // ---------- NEGATIVE (direct prohibited tags) ----------
            ['Hello <script>alert(1)</script>', 'Hello alert(1)'],
            ['Hello <iframe></iframe>', 'Hello '],
            ['<object data="x"></object>', ''],

            // ---------- NEGATIVE (prohibited attributes - cleaned by strip_tags so stripped instead exception) ------
            // fragment payload
            ['<img src="img.png#<script>alert(1)</script>">', '<img src="img.png#scriptalert(1)/script">'],

            // ---------- CLICK-TRIGGERED ATTACK (FORM SUBMIT) ----------
            // Attack by user-click/submit — form with javascript:-action (should be forbidden)
            ['<form action="javascript:alert(1)"><button type="submit">Click me</button></form>', 'Click me'],
            // Form with an input type=image pointing to potential SVG (clicking the image submits)
            ['<form action="/do"><input type="image" src="/uploads/evil.svg" alt="go"></form>', ''],

            // ---------- SANITY (still negative because it uses HTML-like constructs) ----------
            ['<div><script type="text/template">not executed here</script></div>', '<div>not executed here</div>'],
            ['<span><iframe srcdoc="<svg><script>alert(1)</script></svg>"></iframe></span>', '<span></span>'],
        ];
    }

    public function invalidMarkupCausingException(): array
    {
        return [
            // ---------- VALID CONTENT MIXED WITH INVALID -------------
            ['Safe <img class="open critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="javascript:alert(1)" /><script>alert(1)</script><img class="close critical qmflag ownttip qmflag-3" data-t5qid="333" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-3-right.png" />'],

            // ---------- NEGATIVE (erlaubte Tags mit verbotenen Attributen / Werte) ----------
            ['<img src="javascript:alert(1)">'], // scheme-injection
            ['<img src="javascript:alert(1)"/>'], // scheme-injection with proper HTML structure
            ['<img src="javascript:alert(1)" />'], // scheme-injection with proper HTML structure
            ['<img src="data:text/html,&lt;script&gt;alert(1)&lt;/script&gt;">'], // data:-URI
            ['<img onerror="alert(1)" src="img/x.png">'], // event handler
            ['<div onclick="alert(1)">Click</div>'], // event on allowed element
            ['<span href="javascript:evil()">Hi</span>'], // bogus attribute with js scheme
            ['<img src="img.png" srcset="evil 1x">'], // srcset attempt
            ['<img src="img.png" style="background-image:url(\'javascript:alert(1)\')">'], // css-based attempt
            ['<img src="x" xmlns="http://www.w3.org/2000/svg">'], // SVG masquerade via namespace
            ['<span xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="javascript:alert(1)">X</span>'],

            // ---------- HARTE / ENCODED / OBSCURED VERSIONS ----------
            // mixed-case scheme trying to bypass naive checks
            ['<img src="JaVaScRiPt:alert(1)">'],
            // HTML-entity encoded "javascript:" (common bypass attempt)
            ['<img src="jav&#x61;script:alert(1)">'],
            // double-encoded
            ['<img src="data:text/html,%3Csvg%3E%3Cscript%3Ealert(1)%3C/script%3E%3C/svg%3E">'],
            // URL-encoded fragment / percent encodings inside src
            ['<img src="uploads/%3Cscript%3Ealert(1)%3C/script%3E.svg">'],

            // ---------- MIME / Host / Path Trickery ----------
            // relative path that points into upload-area which could contain SVG/HTML
            ['<img src="/uploads/user/evil.svg">'],
            // path with traversal attempts (server-side canonicalization needed)
            ['<img src="/uploads/../uploads/user/evil.svg">'],

            // ---------- EDGE / BOUNDARY CASES ----------
            // empty tag attributes, malformed attributes
            ['<img src="">'], // empty src should be validated / rejected
            ['<img src="/relative/path.png" unknownattr="1">'], // unknown attributes ideally stripped/forbidden
            // extremely long attribute values (fuzzing / DoS-ish)
            ['<img src="' . str_repeat('a', 10000) . '">'],

        ];
    }

    public function invalidMarkupCausingBadRequestException(): array
    {
        return [
            ['<svg><script>alert(1)</script></svg>'],
            ['<math><mi>x</mi></math>'], // XML namespace
        ];
    }
}
