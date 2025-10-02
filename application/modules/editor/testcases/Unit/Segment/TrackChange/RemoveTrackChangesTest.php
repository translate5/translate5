<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Test\Unit\Segment\TrackChange;

use MittagQI\Translate5\Segment\TrackChange\RemoveTrackChanges;
use PHPUnit\Framework\TestCase;

class RemoveTrackChangesTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testRemove(string $text, string $expected): void
    {
        $remover = new RemoveTrackChanges();

        self::assertSame($expected, $remover->remove($text));
    }

    public function cases(): iterable
    {
        yield 'no changes' => [
            'This is a test.',
            'This is a test.',
        ];

        yield 'only deletions' => [
            'This is a <del>simple </del>test.',
            'This is a test.',
        ];

        yield 'only insertions' => [
            'This is a <ins>simple </ins>test.',
            'This is a simple test.',
        ];

        yield 'mixed changes' => [
            'This is a <del>simple </del><ins>complex </ins>test.',
            'This is a complex test.',
        ];

        yield 'nested changes' => [
            'This is a <del>si<ins>mple</ins> </del><ins>complex </ins>test.',
            'This is a complex test.',
        ];

        yield 'nested changes 2' => [
            'This is a <del>si<ins>mple</ins> </del><ins><del>some </del>complex </ins>test.',
            'This is a complex test.',
        ];

        yield 'multiple changes' => [
            '<del>This </del>is a <ins>simple </ins>test with <del>some </del><ins>various </ins>changes.',
            'is a simple test with various changes.',
        ];

        yield 'img tag' => [
            '<img class="content-tag" src="2" alt="TaggingError" />Ichbin<ins class="trackchanges ownttip" data-userguid="{00000000-0000-0000-C100-CCDDEE000002}" data-username="lector test" data-usercssnr="usernr2" data-workflowstep="reviewing1" data-timestamp="2018-11-20T10:38:16+01:00">einTerm Ichbin</ins>einTerm',
            '<img class="content-tag" src="2" alt="TaggingError" />IchbineinTerm IchbineinTerm',
        ];
    }
}
