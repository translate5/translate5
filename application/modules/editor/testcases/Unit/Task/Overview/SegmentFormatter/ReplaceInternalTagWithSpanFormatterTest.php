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

namespace MittagQI\Translate5\Test\Unit\Task\Overview\SegmentFormatter;

use editor_Models_Task;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\ReplaceInternalTagWithSpanFormatter;
use PHPUnit\Framework\TestCase;

class ReplaceInternalTagWithSpanFormatterTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function test(string $segment, string $expected, string $messageAttr, string $color, bool $isSource): void
    {
        $formatter = new ReplaceInternalTagWithSpanFormatter($messageAttr, $color);

        $task = $this->createMock(editor_Models_Task::class);

        self::assertSame($expected, $formatter($task, $segment, $isSource));
    }

    public function cases(): iterable
    {
        yield 'no changes' => [
            'segment' => 'some test segment',
            'expected' => 'some test segment',
            'messageAttr' => 'data-message',
            'color' => 'red',
            'isSource' => true,
        ];

        yield 'complex real life segment' => [
            'segment' => <<<HTML
Benutzer {U} hat Daten des Datenpunktes '{T}' ({A})<div class="single 6861726452657475726e2f newline internal-tag ownttip"><span class="short" title="<1/>: Newline" id="ext-element-53">&lt;1/&gt;</span><span class="full" data-originalid="hardReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="<2/>: 27 whitespace characters" id="ext-element-52">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="27" id="ext-element-62">···························</span></div>geändert<div class="single 6861726452657475726e2f newline internal-tag ownttip"><span class="short" title="<3/>: Newline" id="ext-element-54">&lt;3/&gt;</span><span class="full" data-originalid="hardReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d223230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223233222f space internal-tag ownttip"><span class="short" title="<4/>: 23 whitespace characters" id="ext-element-55">&lt;4/&gt;</span><span class="full" data-originalid="space" data-length="23">·······················</span></div>
HTML,
            'expected' => <<<HTML
Benutzer {U} hat Daten des Datenpunktes '{T}' ({A})<span data-message="<1/>: Newline" style="background-color: red">↵</span> <span data-message="<2/>: 27 whitespace characters" style="background-color: red">···························</span>geändert<span data-message="<3/>: Newline" style="background-color: red">↵</span> <span data-message="<4/>: 23 whitespace characters" style="background-color: red">·······················</span>
HTML,
            'messageAttr' => 'data-message',
            'color' => 'red',
            'isSource' => true,
        ];

        yield 'segment with protected content' => [
            'segment' => <<<HTML
Und noch ein Satz mit <div class="single 6e756d62657220747970653d226b6565702d636f6e74656e7422206e616d653d2274657374766f6c6b65722220736f757263653d22266c743b4b6f7069652667743b222069736f3d22266c743b4b6f7069652667743b22207461726765743d22266c743b4b6f7069652667743b222f number internal-tag ownttip"><span class="short" title="<1/>: Number">&lt;1/&gt;</span><span class="full" data-originalid="number" data-length="13" data-source="<Kopie>" data-target="<Kopie>"></span></div>.
HTML,
            'expected' => <<<HTML
Und noch ein Satz mit <span data-message="<1/>: Number" style="background-color: pink">&lt;Kopie&gt;</span>.
HTML,
            'messageAttr' => 'data-message',
            'color' => 'pink',
            'isSource' => true,
        ];

        yield 'segment with protected number' => [
            'segment' => <<<HTML
<div class="single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip"><span class="short" title="<1/>: 3 whitespace characters">&lt;1/&gt;</span><span class="full" data-originalid="space" data-length="3">···</span></div>• <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d223134222069736f3d22313422207461726765743d223134222f number internal-tag ownttip"><span class="short" title="<2/>: Number">&lt;2/&gt;</span><span class="full" data-originalid="number" data-length="2" data-source="14" data-target="14"></span></div>. Februar <div class="single 6e756d62657220747970653d22696e746567657222206e616d653d2264656661756c742073696d706c652220736f757263653d2232303033222069736f3d223230303322207461726765743d2232303033222f number internal-tag ownttip"><span class="short" title="<3/>: Number" id="ext-element-59">&lt;3/&gt;</span><span class="full" data-originalid="number" data-length="4" data-source="2003" data-target="2003"></span></div> (<div class="single 6e756d62657220747970653d226461746522206e616d653d2264656661756c7420642e6d2e592220736f757263653d2231342e30322e32303033222069736f3d22323030332d30322d313422207461726765743d2230322f31342f32303033222f number internal-tag ownttip"><span class="short" title="<4/>: Number" id="ext-element-65">&lt;4/&gt;</span><span class="full" data-originalid="number" data-length="10" data-source="14.02.2003" data-target="02/14/2003" id="ext-element-66"></span></div> unter Zugrundelegung der im deutschsprachigen Raum weiterhin gebräuchlichen Reihenfolge nach DIN 1355-1)
HTML,
            'expected' => <<<HTML
<span title="<1/>: 3 whitespace characters" style="background-color: green">···</span>• <span title="<2/>: Number" style="background-color: green">14</span>. Februar <span title="<3/>: Number" style="background-color: green">2003</span> (<span title="<4/>: Number" style="background-color: green">02/14/2003</span> unter Zugrundelegung der im deutschsprachigen Raum weiterhin gebräuchlichen Reihenfolge nach DIN 1355-1)
HTML,
            'messageAttr' => 'title',
            'color' => 'green',
            'isSource' => false,
        ];
    }
}
