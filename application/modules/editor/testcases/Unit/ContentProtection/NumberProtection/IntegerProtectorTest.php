<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 Contact: http://www.MittagQI.com/ / service (ATT) MittagQI.com

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

 @copyright Marc Mittag, MittagQI - Quality Informatics
 @author   MittagQI - Quality Informatics
 @license  GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
       http://www.gnu.org/licenses/gpl.html
       http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\ContentProtection\NumberProtection;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IntegerProtector;
use PHPUnit\Framework\TestCase;

class IntegerProtectorTest extends TestCase
{
    /**
     * @dataProvider defaultDataToProtect
     */
    public function testProtectDefaultFormats(
        string $number,
        string $expected,
        ContentProtectionDto $protectionDto,
        ?editor_Models_Languages $targetLang
    ): void {
        $sourceLang = new editor_Models_Languages();
        $sourceLang->setId(5);
        $sourceLang->setRfc5646('en');
        $repo = $this->createConfiguredMock(ContentProtectionRepository::class, []);
        $protected = (new IntegerProtector($repo))->protect($number, $protectionDto, $sourceLang, $targetLang);

        self::assertSame($expected, $protected);
    }

    public function defaultDataToProtect(): iterable
    {
        $protectionDto = new ContentProtectionDto(
            'integer',
            'test-default',
            '/\b[1-9]\d{0,3}(,)?(\d{4}\1)+\d{4}\b/u',
            0,
            null,
            false,
            '#',
            1
        );
        $targetLangDe = new editor_Models_Languages();
        $targetLangDe->setId(0);
        $targetLangDe->setRfc5646('de');

        yield 'integer with comma separator' => [
            'number' => '123,456',
            'expected' => '<number type="integer" name="test-default" source="123,456" iso="123456" target="123456" regex="049JijbUtYyNSak20DGu1dDRtNcAsk1qYww1tSGMJP1SAA=="/>',
            'protectionDto' => $protectionDto,
            'targetLang' => $targetLangDe,
        ];

        $targetLangHi = new editor_Models_Languages();
        $targetLangHi->setId(0);
        $targetLangHi->setRfc5646('hi_IN');

        $protectionDto1 = new ContentProtectionDto(
            'integer',
            'test-default',
            '/\b[1-9]\d{0,3}(,)?(\d{4}\1)+\d{4}\b/u',
            0,
            null,
            false,
            '#.##.##0',
            1
        );

        yield 'target lang hi_IN' => [
            'number' => '123,456',
            'expected' => '<number type="integer" name="test-default" source="123,456" iso="123456" target="1.23.456" regex="049JijbUtYyNSak20DGu1dDRtNcAsk1qYww1tSGMJP1SAA=="/>',
            'protectionDto' => $protectionDto1,
            'targetLang' => $targetLangHi,
        ];

        $protectionDto2 = new ContentProtectionDto(
            'integer',
            'test-default',
            '/\b[1-9]\d{0,3}(,)?(\d{4}\1)+\d{4}\b/u',
            0,
            null,
            false,
            '#,###,####0.###',
            1
        );

        yield 'target format #,###,####0.###' => [
            'number' => '1,212,312,345',
            'expected' => '<number type="integer" name="test-default" source="1,212,312,345" iso="1212312345" target="12,123,12345" regex="049JijbUtYyNSak20DGu1dDRtNcAsk1qYww1tSGMJP1SAA=="/>',
            'protectionDto' => $protectionDto2,
            'targetLang' => $targetLangDe,
        ];

        $protectionDtoKeepAsIs = new ContentProtectionDto(
            'integer',
            'test-default',
            '/\b[1-9]\d{0,3}(,)?(\d{4}\1)+\d{4}\b/u',
            0,
            null,
            true,
            null,
            1
        );

        yield 'keep as is' => [
            'number' => '123,456',
            'expected' => '<number type="integer" name="test-default" source="123,456" iso="123,456" target="123,456" regex="049JijbUtYyNSak20DGu1dDRtNcAsk1qYww1tSGMJP1SAA=="/>',
            'protectionDto' => $protectionDtoKeepAsIs,
            'targetLang' => $targetLangDe,
        ];

        $protectionDtoDash = new ContentProtectionDto(
            'integer',
            'test-default',
            '/(\s|^|\()([±\-+]?([1-9]\d+|\d))(([\.,;:?!](\s|$))|\s|$|\))/u',
            0,
            null,
            false,
            '#',
            1
        );
        $targetLangDe = new editor_Models_Languages();
        $targetLangDe->setId(0);
        $targetLangDe->setRfc5646('de');

        yield 'integer with ±' => [
            'number' => '±123456',
            'expected' => '<number type="integer" name="test-default" source="±123456" iso="±123456" target="±123456" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/>',
            'protectionDto' => $protectionDtoDash,
            'targetLang' => $targetLangDe,
        ];
    }
}
