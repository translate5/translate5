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

use MittagQI\Translate5\Segment\LevenshteinUTF8;
use PHPUnit\Framework\TestCase;

class LevenshteinUTF8Test extends TestCase
{
    public function provideString(): array
    {
        $internalTag = '<div class="single 13 newline internal-tag ownttip"><span title="&lt;4/&gt;: Newline" class="short">&lt;4/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>';
        // Attributes may change their location: class="short", class="full"
        $internalTagVolatile1 = '<div class="single 100 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;">&lt;3/&gt;</span><span class="full" data-originalid="0" data-length="-1">&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;</span></div>';
        $internalTagVolatile2 = '<div class="single 100 internal-tag ownttip"><span title="&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;" class="short">&lt;3/&gt;</span><span data-originalid="0" data-length="-1" class="full">&lt;ph id=&quot;5&quot; ax:element-id=&quot;0&quot;&gt;Drawing object&lt;/ph&gt;</span></div>';

        $changeTrackingTag1 = '<ins class="trackchanges ownttip" data-usertrackingid="78" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-12-17T16:44:42+01:00">A</ins>';
        $changeTrackingTag2 = '<del class="trackchanges ownttip deleted" data-usertrackingid="78" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-12-17T17:36:29+01:00">E</del>';

        return [
            ['atitinkančios apsauginės', 'atitinkančios apsauginės', 0],
            ['atitinkančios apsauginės', 'atitinkancios apsaugines', 2],
            ['atitinkančios apsauginės', 'atitinkancios apsaugine', 3],
            ['Apsauginės platformo' . $internalTag, $internalTag . 'Apsauginės platformoč', 2],
            [
                $internalTagVolatile1 . 'Two words',
                $internalTagVolatile2 . 'Two' . $internalTag . 'words',
                1,
            ],
            ['Two words', 'Two words' . $changeTrackingTag1, 1],
            ['Two words' . $changeTrackingTag1, 'Two words' . $changeTrackingTag2, 1],
        ];
    }

    /**
     * @dataProvider provideString
     */
    public function testUTF8(string $s1, string $s2, int $expected): void
    {
        self::assertEquals($expected, LevenshteinUTF8::calcDistance($s1, $s2));
    }
}
