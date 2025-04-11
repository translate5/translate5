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

use editor_Models_Segment_Utility as SegmentUtility;
use MittagQI\Translate5\Segment\EntityHandlingMode;
use PHPUnit\Framework\TestCase;

class ImportWhitespaceProtectionTest extends TestCase
{
    public function provideString(): array
    {
        return [
            ['&szlig;', 'ß', '&amp;szlig;'],
            ['&apos;', "'", '&amp;apos;'],
            ['&amp;szlig;', '&amp;szlig;', '&amp;amp;szlig;'],
            ['10 &gt; 4', '10 &gt; 4', '10 &amp;gt; 4'],
            ['    ', ' <space ts="202020" length="3"/>', '    '],
            ['&#x22;', '"', '&amp;#x22;'],
            ['&#034;', '"', '&amp;#034;'],
            ['&#038;', '&amp;', '&amp;#038;'],
            ['&#039;', "'", '&amp;#039;'],
            ['&#10;', "<softReturn/>", '&amp;#10;'],
            ['&#09;', '<tab ts="09" length="1"/>', '&amp;#09;'],
            ['&#x0c;', '<char ts="0c" length="1"/>', '&amp;#x0c;'],
            ['&#x1680;', '<char ts="e19a80" length="1"/>', '&amp;#x1680;'],
            ['>', '&gt;', '&gt;'],
            ['&', '&amp;', '&amp;'],
            ['&mdash;', '—', '&amp;mdash;'],
            ['&shy;', '<char ts="c2ad" length="1"/>', '&amp;shy;'],
            ['&#x00AD;', '<char ts="c2ad" length="1"/>', '&amp;#x00AD;'],
            ['&rdquo;', '”', '&amp;rdquo;'],
            ['&#x263a;', '☺', '&amp;#x263a;'],

            ['&#11;', '<char ts="0b" length="1"/>', '&amp;#11;'],
            ['&#13;', "<macReturn/>", '&amp;#13;'],
            ['&#146;', '<char ts="26233134363b" length="1"/>', '&amp;#146;'],
            ['&#128;', '<char ts="26233132383b" length="1"/>', '&amp;#128;'], //invalid XML char
        ];
    }

    /**
     * @dataProvider provideString
     */
    public function testEntityCleanup(string $textWithEntities, string $expectedXml, string $expectedNonXml): void
    {
        $whitespace = new \editor_Models_Segment_Whitespace();
        self::assertEquals($expectedXml, $whitespace->protectWhitespace(SegmentUtility::entityCleanup($textWithEntities)));
        self::assertEquals($expectedNonXml, SegmentUtility::entityCleanup($textWithEntities, EntityHandlingMode::Keep));
        self::assertEquals($textWithEntities, SegmentUtility::entityCleanup($textWithEntities, EntityHandlingMode::Off));
    }
}
