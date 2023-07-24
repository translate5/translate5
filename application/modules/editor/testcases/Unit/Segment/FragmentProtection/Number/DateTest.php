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

namespace MittagQI\Translate5\Test\Unit\Segment\FragmentProtection\Number;

use editor_Test_UnitTest;
use MittagQI\Translate5\Repository\LanguageNumberFormatRepository;
use MittagQI\Translate5\Segment\FragmentProtection\Number\DateProtection;

class DateTest extends editor_Test_UnitTest
{
    /**
     * @dataProvider datesProvider
     */
    public function estHasEntityToProtectWithNoSourceLang(string $date, bool $valid): void
    {
        $repo = $this->createConfiguredMock(
            LanguageNumberFormatRepository::class,
            ['getByLanguageIdAndType' => []]
        );
        self::assertSame($valid, (new DateProtection($repo))->hasEntityToProtect($date, null));
    }

    public function datesProvider(): iterable
    {
        yield ['date' => 'string 20231020 string', 'valid' => true];
        yield ['date' => 'string 20231230 string', 'valid' => true];
        yield ['date' => 'string 20233108 string', 'valid' => false];

        yield ['date' => 'string 05/07/23 string', 'valid' => true];
        yield ['date' => 'string 05/27/23 string', 'valid' => true];
        yield ['date' => 'string 05/17/23 string', 'valid' => true];
        yield ['date' => 'string 05/30/23 string', 'valid' => true];
        yield ['date' => 'string 31/07/23 string', 'valid' => true];
        yield ['date' => 'string 26/07/23 string', 'valid' => true];
        yield ['date' => 'string 19/07/23 string', 'valid' => true];
        yield ['date' => 'string 19/7/23 string', 'valid' => true];
        yield ['date' => 'string 5/7/23 string', 'valid' => true];
        yield ['date' => 'string 5/27/23 string', 'valid' => true];
        yield ['date' => 'string 5/17/23 string', 'valid' => true];
        yield ['date' => 'string 5/30/23 string', 'valid' => true];
        yield ['date' => 'string 05/07/2023 string', 'valid' => true];
        yield ['date' => 'string 5/7/2023 string', 'valid' => true];
        yield ['date' => 'string 2023/05/07 string', 'valid' => true];
        yield ['date' => 'string 2023/5/7 string', 'valid' => true];
        yield ['date' => 'string 35/7/23 string', 'valid' => true];
        yield ['date' => 'string 35/07/23 string', 'valid' => true];

        yield ['date' => 'string 05/07/123 string', 'valid' => false];
        yield ['date' => 'string 123/05/07 string', 'valid' => false];
        yield ['date' => 'string 35/7/2023 string', 'valid' => false];
        yield ['date' => 'string 35/07/2023 string', 'valid' => false];
        yield ['date' => 'string 30/17/2023 string', 'valid' => false];
        yield ['date' => 'string 13/31/2023 string', 'valid' => false];
        yield ['date' => 'string 2023/5/37 string', 'valid' => false];
        yield ['date' => 'string 2023/15/30 string', 'valid' => false];
        yield ['date' => 'string 2023/30/13 string', 'valid' => false];
        yield ['date' => 'string 2023/32/3 string', 'valid' => false];
        yield ['date' => 'string 30/17/23 string', 'valid' => false];
        yield ['date' => 'string 13/31/23 string', 'valid' => false];
        yield ['date' => 'string 33/5/37 string', 'valid' => false];
        yield ['date' => 'string 23/15/30 string', 'valid' => false];
        yield ['date' => 'string 23/30/13 string', 'valid' => false];
        yield ['date' => 'string 23/32/3 string', 'valid' => false];

        yield ['date' => 'string 05-07-23 string', 'valid' => true];
        yield ['date' => 'string 05-27-23 string', 'valid' => true];
        yield ['date' => 'string 05-17-23 string', 'valid' => true];
        yield ['date' => 'string 05-30-23 string', 'valid' => true];
        yield ['date' => 'string 31-07-23 string', 'valid' => true];
        yield ['date' => 'string 26-07-23 string', 'valid' => true];
        yield ['date' => 'string 19-07-23 string', 'valid' => true];
        yield ['date' => 'string 19-7-23 string', 'valid' => true];
        yield ['date' => 'string 5-7-23 string', 'valid' => true];
        yield ['date' => 'string 5-27-23 string', 'valid' => true];
        yield ['date' => 'string 5-17-23 string', 'valid' => true];
        yield ['date' => 'string 5-30-23 string', 'valid' => true];
        yield ['date' => 'string 05-07-2023 string', 'valid' => true];
        yield ['date' => 'string 5-7-2023 string', 'valid' => true];
        yield ['date' => 'string 2023-05-07 string', 'valid' => true];
        yield ['date' => 'string 2023-5-7 string', 'valid' => true];
        yield ['date' => 'string 05-07-123 string', 'valid' => false];
        yield ['date' => 'string 123-05-07 string', 'valid' => false];
        yield ['date' => 'string 35-7-2023 string', 'valid' => false];
        yield ['date' => 'string 35-07-2023 string', 'valid' => false];
        yield ['date' => 'string 30-17-2023 string', 'valid' => false];
        yield ['date' => 'string 13-31-2023 string', 'valid' => false];
        yield ['date' => 'string 2023-5-37 string', 'valid' => false];
        yield ['date' => 'string 2023-15-30 string', 'valid' => false];
        yield ['date' => 'string 2023-30-13 string', 'valid' => false];
        yield ['date' => 'string 2023-32-3 string', 'valid' => false];
        yield ['date' => 'string 35-7-23 string', 'valid' => false];
        yield ['date' => 'string 35-07-23 string', 'valid' => false];
        yield ['date' => 'string 30-17-23 string', 'valid' => false];
        yield ['date' => 'string 13-31-23 string', 'valid' => false];
        yield ['date' => 'string 33-5-37 string', 'valid' => false];
        yield ['date' => 'string 23-15-30 string', 'valid' => false];
        yield ['date' => 'string 23-30-13 string', 'valid' => false];
        yield ['date' => 'string 23-32-3 string', 'valid' => false];

        yield ['date' => 'string 05.07.23 string', 'valid' => true];
        yield ['date' => 'string 05.27.23 string', 'valid' => true];
        yield ['date' => 'string 05.17.23 string', 'valid' => true];
        yield ['date' => 'string 05.30.23 string', 'valid' => true];
        yield ['date' => 'string 31.07.23 string', 'valid' => true];
        yield ['date' => 'string 26.07.23 string', 'valid' => true];
        yield ['date' => 'string 19.07.23 string', 'valid' => true];
        yield ['date' => 'string 19.7.23 string', 'valid' => true];
        yield ['date' => 'string 5.7.23 string', 'valid' => true];
        yield ['date' => 'string 5.27.23 string', 'valid' => true];
        yield ['date' => 'string 5.17.23 string', 'valid' => true];
        yield ['date' => 'string 5.30.23 string', 'valid' => true];
        yield ['date' => 'string 05.07.2023 string', 'valid' => true];
        yield ['date' => 'string 5.7.2023 string', 'valid' => true];
        yield ['date' => 'string 2023.05.07 string', 'valid' => true];
        yield ['date' => 'string 2023.5.7 string', 'valid' => true];

        yield ['date' => 'string 05.07.123 string', 'valid' => false];
        yield ['date' => 'string 123.05.07 string', 'valid' => false];
        yield ['date' => 'string 35.7.2023 string', 'valid' => false];
        yield ['date' => 'string 35.07.2023 string', 'valid' => false];
        yield ['date' => 'string 30.17.2023 string', 'valid' => false];
        yield ['date' => 'string 13.31.2023 string', 'valid' => false];
        yield ['date' => 'string 2023.5.37 string', 'valid' => false];
        yield ['date' => 'string 2023.15.30 string', 'valid' => false];
        yield ['date' => 'string 2023.30.13 string', 'valid' => false];
        yield ['date' => 'string 2023.32.3 string', 'valid' => false];
        yield ['date' => 'string 35.7.23 string', 'valid' => false];
        yield ['date' => 'string 35.07.23 string', 'valid' => false];
        yield ['date' => 'string 30.17.23 string', 'valid' => false];
        yield ['date' => 'string 13.31.23 string', 'valid' => false];
        yield ['date' => 'string 33.5.37 string', 'valid' => false];
        yield ['date' => 'string 23.15.30 string', 'valid' => false];
        yield ['date' => 'string 23.30.13 string', 'valid' => false];
        yield ['date' => 'string 23.32.3 string', 'valid' => false];

        yield ['date' => 'string 05 07 2023 string', 'valid' => true];
        yield ['date' => 'string 5 7 2023 string', 'valid' => true];
        yield ['date' => 'string 31 11 2023 string', 'valid' => true];
        yield ['date' => 'string 31 5 2023 string', 'valid' => true];
        yield ['date' => 'string 2023 05 07 string', 'valid' => true];
        yield ['date' => 'string 2023 5 7 string', 'valid' => true];
        yield ['date' => 'string 2023 5 30 string', 'valid' => true];
        yield ['date' => 'string 2023 12 31 string', 'valid' => true];

        yield ['date' => 'string 05 07 23 string', 'valid' => false];
        yield ['date' => 'string 05 27 23 string', 'valid' => false];
        yield ['date' => 'string 05 17 23 string', 'valid' => false];
        yield ['date' => 'string 05 30 23 string', 'valid' => false];
        yield ['date' => 'string 31 07 23 string', 'valid' => false];
        yield ['date' => 'string 26 07 23 string', 'valid' => false];
        yield ['date' => 'string 19 07 23 string', 'valid' => false];
        yield ['date' => 'string 19 7 23 string', 'valid' => false];
        yield ['date' => 'string 5 7 23 string', 'valid' => false];
        yield ['date' => 'string 5 27 23 string', 'valid' => false];
        yield ['date' => 'string 5 17 23 string', 'valid' => false];
        yield ['date' => 'string 5 30 23 string', 'valid' => false];
        yield ['date' => 'string 05 07 123 string', 'valid' => false];
        yield ['date' => 'string 123 05 07 string', 'valid' => false];
        yield ['date' => 'string 35 7 2023 string', 'valid' => false];
        yield ['date' => 'string 35 07 2023 string', 'valid' => false];
        yield ['date' => 'string 30 17 2023 string', 'valid' => false];
        yield ['date' => 'string 13 31 2023 string', 'valid' => false];
        yield ['date' => 'string 2023 5 37 string', 'valid' => false];
        yield ['date' => 'string 2023 15 30 string', 'valid' => false];
        yield ['date' => 'string 2023 30 13 string', 'valid' => false];
        yield ['date' => 'string 2023 32 3 string', 'valid' => false];
        yield ['date' => 'string 35 7 23 string', 'valid' => false];
        yield ['date' => 'string 35 07 23 string', 'valid' => false];
        yield ['date' => 'string 30 17 23 string', 'valid' => false];
        yield ['date' => 'string 13 31 23 string', 'valid' => false];
        yield ['date' => 'string 33 5 37 string', 'valid' => false];
        yield ['date' => 'string 23 15 30 string', 'valid' => false];
        yield ['date' => 'string 23 30 13 string', 'valid' => false];
        yield ['date' => 'string 23 32 3 string', 'valid' => false];
    }

    /**
     * @dataProvider defaultDataToProtect
     */
    public function testProtectWithNoSourceAndTargetLang(string $textNode, string $expected): void
    {
        $repo = $this->createConfiguredMock(LanguageNumberFormatRepository::class, []);
        self::assertSame($expected, (new DateProtection($repo))->protect($textNode, null, null));
    }

    public function defaultDataToProtect(): iterable
    {
        yield 'date in the middle of text' => [
            'textNode' => 'some text with date in it: 2023-07-18. in the middle',
            'expected' => 'some text with date in it: <number type="date" name="default" source="2023-07-18" iso="2023-07-18" target="" />. in the middle'
        ];

        yield 'date at the end of text' => [
            'textNode' => 'some text with date in it: 18-07-2023',
            'expected' => 'some text with date in it: <number type="date" name="default" source="18-07-2023" iso="2023-07-18" target="" />'
        ];

        yield 'date at the beginning of text' => [
            'textNode' => '07-18-2023 some text with date in it',
            'expected' => '<number type="date" name="default" source="07-18-2023" iso="2023-07-18" target="" /> some text with date in it'
        ];

        yield 'date at the beginning and end of text' => [
            'textNode' => '07.18.23 some text with date in it 18/07/2023',
            'expected' => '<number type="date" name="default" source="07.18.23" iso="2023-07-18" target="" /> some text with date in it <number type="date" name="default" source="18/07/2023" iso="2023-07-18" target="" />'
        ];

        yield 'a lot of different dates inside text' => [
            'textNode' => '07/18/23 some text with date in it: 2023-07-18. in the middle 07.18.2023 and some more text 07 18 2023 and more 20231231',
            'expected' => '<number type="date" name="default" source="07/18/23" iso="2023-07-18" target="" /> some text with date in it: <number type="date" name="default" source="2023-07-18" iso="2023-07-18" target="" />. in the middle <number type="date" name="default" source="07.18.2023" iso="2023-07-18" target="" /> and some more text <number type="date" name="default" source="07 18 2023" iso="2023-07-18" target="" /> and more <number type="date" name="default" source="20231231" iso="2023-12-31" target="" />'
        ];
    }
}