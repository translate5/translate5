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

use MittagQI\Translate5\Segment\SegmentLevenshtein;
use PHPUnit\Framework\TestCase;

class SegmentLevenshteinTest extends TestCase
{
    public function provideString(): array
    {
        $internalTag = '<div class="single 13 newline internal-tag ownttip"><span title="&lt;4/&gt;: Newline" class="short">&lt;4/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>';
        // Attributes may change their location: class="short", class="full"
        $internalTagVolatile1 = '<div class="single 100 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;">&lt;3/&gt;</span><span class="full" data-originalid="0" data-length="-1">&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;</span></div>';
        $internalTagVolatile2 = '<div class="single 100 internal-tag ownttip"><span title="&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;" class="short">&lt;3/&gt;</span><span data-originalid="0" data-length="-1" class="full">&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;</span></div>';

        $changeTrackingTag1 = '<ins class="trackchanges ownttip" data-usertrackingid="78" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-12-17T16:44:42+01:00">A</ins>';
        $changeTrackingTag2 = '<del class="trackchanges ownttip deleted" data-usertrackingid="78" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-12-17T17:36:29+01:00">E</del>';
        $mqmOpen = '<img class="open critical qmflag ownttip qmflag-1" data-t5qid="ext-1" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-left.png" />';
        $mqmClose = '<img class="close critical qmflag ownttip qmflag-1" data-t5qid="ext-1" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-right.png" />';
        $cjkChars = self::buildCjkChars(200);
        $cjkString = implode('', $cjkChars);
        $asciiCollision = 'A' . $cjkString;
        $cjkCollision = $cjkChars[193] . $cjkString;

        $longInternalTag1 = <<<'TAG'
<div class="open 6270742069643d2231223e266c743b647261773a6672616d6520647261773a7374796c652d6e616d653d2671756f743b6672342671756f743b20647261773a6e616d653d2671756f743b5472616e736c617465352671756f743b20746578743a616e63686f722d747970653d2671756f743b636861722671756f743b207376673a783d2671756f743b302e303134636d2671756f743b207376673a793d2671756f743b302e313332636d2671756f743b207376673a77696474683d2671756f743b352e323932636d2671756f743b207376673a6865696768743d2671756f743b332e373232636d2671756f743b20647261773a7a2d696e6465783d2671756f743b312671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;draw:frame draw:style-name=&quot;fr4&quot; draw:name=&quot;Translate5&quot; text:anchor-type=&quot;char&quot; svg:x=&quot;0.014cm&quot; svg:y=&quot;0.132cm&quot; svg:width=&quot;5.292cm&quot; svg:height=&quot;3.722cm&quot; draw:z-index=&quot;1&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;draw:frame draw:style-name=&quot;fr4&quot; draw:name=&quot;Translate5&quot; text:anchor-type=&quot;char&quot; svg:x=&quot;0.014cm&quot; svg:y=&quot;0.132cm&quot; svg:width=&quot;5.292cm&quot; svg:height=&quot;3.722cm&quot; draw:z-index=&quot;1&quot;&gt;</span></div><div class="open 6270742069643d2232223e266c743b647261773a696d61676520786c696e6b3a687265663d2671756f743b50696374757265732f313030303032303130303030303132433030303030304433383533353944383237383134413742352e706e672671756f743b20786c696e6b3a747970653d2671756f743b73696d706c652671756f743b20786c696e6b3a73686f773d2671756f743b656d6265642671756f743b20786c696e6b3a616374756174653d2671756f743b6f6e4c6f61642671756f743b206c6f6578743a6d696d652d747970653d2671756f743b696d6167652f706e672671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;draw:image xlink:href=&quot;Pictures/100002010000012C000000D385359D827814A7B5.png&quot; xlink:type=&quot;simple&quot; xlink:show=&quot;embed&quot; xlink:actuate=&quot;onLoad&quot; loext:mime-type=&quot;image/png&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;draw:image xlink:href=&quot;Pictures/100002010000012C000000D385359D827814A7B5.png&quot; xlink:type=&quot;simple&quot; xlink:show=&quot;embed&quot; xlink:actuate=&quot;onLoad&quot; loext:mime-type=&quot;image/png&quot;&gt;</span></div><div class="close 6570742069643d2232223e266c743b2f647261773a696d6167652667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/draw:image&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/draw:image&gt;</span></div><div class="open 6270742069643d2233223e266c743b7376673a7469746c652667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;svg:title&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;svg:title&gt;</span></div>Translate5<div class="close 6570742069643d2233223e266c743b2f7376673a7469746c652667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/svg:title&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/svg:title&gt;</span></div><div class="close 6570742069643d2231223e266c743b2f647261773a6672616d652667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/draw:frame&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/draw:frame&gt;</span></div><div class="open 6270742069643d2234223e266c743b746578743a7370616e20746578743a7374796c652d6e616d653d2671756f743b54312671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;text:span text:style-name=&quot;T1&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;text:span text:style-name=&quot;T1&quot;&gt;</span></div>Open Source Translation System<div class="close 6570742069643d2234223e266c743b2f746578743a7370616e2667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/text:span&gt;">&lt;/4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;/text:span&gt;</span></div>
TAG;
        $longInternalTag2 = <<<'TAG'
<div class="open 6270742069643d2231223e266c743b647261773a6672616d6520647261773a7374796c652d6e616d653d2671756f743b6672342671756f743b20647261773a6e616d653d2671756f743b5472616e736c617465352671756f743b20746578743a616e63686f722d747970653d2671756f743b636861722671756f743b207376673a783d2671756f743b302e303134636d2671756f743b207376673a793d2671756f743b302e313332636d2671756f743b207376673a77696474683d2671756f743b352e323932636d2671756f743b207376673a6865696768743d2671756f743b332e373232636d2671756f743b20647261773a7a2d696e6465783d2671756f743b312671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;draw:frame draw:style-name=&quot;fr4&quot; draw:name=&quot;Translate5&quot; text:anchor-type=&quot;char&quot; svg:x=&quot;0.014cm&quot; svg:y=&quot;0.132cm&quot; svg:width=&quot;5.292cm&quot; svg:height=&quot;3.722cm&quot; draw:z-index=&quot;1&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;draw:frame draw:style-name="fr4" draw:name="Translate5" text:anchor-type="char" svg:x="0.014cm" svg:y="0.132cm" svg:width="5.292cm" svg:height="3.722cm" draw:z-index="1"&gt;</span></div><div class="open 6270742069643d2232223e266c743b647261773a696d61676520786c696e6b3a687265663d2671756f743b50696374757265732f313030303032303130303030303132433030303030304433383533353944383237383134413742352e706e672671756f743b20786c696e6b3a747970653d2671756f743b73696d706c652671756f743b20786c696e6b3a73686f773d2671756f743b656d6265642671756f743b20786c696e6b3a616374756174653d2671756f743b6f6e4c6f61642671756f743b206c6f6578743a6d696d652d747970653d2671756f743b696d6167652f706e672671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;draw:image xlink:href=&quot;Pictures/100002010000012C000000D385359D827814A7B5.png&quot; xlink:type=&quot;simple&quot; xlink:show=&quot;embed&quot; xlink:actuate=&quot;onLoad&quot; loext:mime-type=&quot;image/png&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;draw:image xlink:href="Pictures/100002010000012C000000D385359D827814A7B5.png" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad" loext:mime-type="image/png"&gt;</span></div><div class="close 6570742069643d2232223e266c743b2f647261773a696d6167652667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/draw:image&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/draw:image&gt;</span></div><div class="open 6270742069643d2233223e266c743b7376673a7469746c652667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;svg:title&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;svg:title&gt;</span></div>Translate5<div class="close 6570742069643d2233223e266c743b2f7376673a7469746c652667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/svg:title&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/svg:title&gt;</span></div><div class="close 6570742069643d2231223e266c743b2f647261773a6672616d652667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/draw:frame&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/draw:frame&gt;</span></div><div class="open 6270742069643d2234223e266c743b746578743a7370616e20746578743a7374796c652d6e616d653d2671756f743b54312671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;text:span text:style-name=&quot;T1&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;text:span text:style-name="T1"&gt;</span></div>Open Source Translation System<div class="close 6570742069643d2234223e266c743b2f746578743a7370616e2667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/text:span&gt;">&lt;/4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;/text:span&gt;</span></div>
TAG;

        return [
            'ascii-basic' => ['kitten', 'sitting', 3],
            'ascii-same' => ['plain ascii', 'plain ascii', 0],
            'utf8-acute' => ['café', 'cafe', 1],
            'utf8-diaeresis' => ['naïve', 'naive', 1],
            'utf8-same' => ['atitinkančios apsauginės', 'atitinkančios apsauginės', 0],
            'utf8-normalize-2' => ['atitinkančios apsauginės', 'atitinkancios apsaugines', 2],
            'utf8-normalize-3' => ['atitinkančios apsauginės', 'atitinkancios apsaugine', 3],
            'internal-tag-order' => ['Apsauginės platformo' . $internalTag, $internalTag . 'Apsauginės platformoč', 2],
            'internal-tag-volatile' => [
                $internalTagVolatile1 . 'Two words',
                $internalTagVolatile2 . 'Two' . $internalTag . 'words',
                1,
            ],
            'trackchanges-ins' => ['Two words', 'Two words' . $changeTrackingTag1, 1],
            'trackchanges-switch' => ['Two words' . $changeTrackingTag1, 'Two words' . $changeTrackingTag2, 1],
            'mqm-tags' => ['MQM issue', 'MQM ' . $mqmOpen . 'issue' . $mqmClose, 0],
            'cjk-collision' => [$asciiCollision, $cjkCollision, 1, true],
            'internal-tag-encoding' => [$longInternalTag1, $longInternalTag2, 0],
        ];
    }

    /**
     * @dataProvider provideString
     */
    public function testUTF8(string $s1, string $s2, int $expected, bool $requiresGrapheme = false): void
    {
        if ($requiresGrapheme && PHP_VERSION_ID < 80500) {
            $this->markTestSkipped('Requires grapheme_levenshtein (PHP 8.5+).');
        }

        self::assertEquals($expected, SegmentLevenshtein::calcDistance($s1, $s2));
    }

    /**
     * @return string[]
     */
    private static function buildCjkChars(int $length, int $start = 0x4E00): array
    {
        $chars = [];
        for ($i = 0; $i < $length; $i++) {
            $chars[] = html_entity_decode('&#x' . dechex($start + $i) . ';', ENT_NOQUOTES, 'UTF-8');
        }

        return $chars;
    }
}
