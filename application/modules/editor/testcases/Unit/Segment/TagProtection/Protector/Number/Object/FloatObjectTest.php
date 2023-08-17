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

namespace MittagQI\Translate5\Test\Unit\Segment\TagProtection\Protector\Number\Object;

use MittagQI\Translate5\Segment\TagProtection\Protector\Number\Object\FloatObject;
use PHPUnit\Framework\TestCase;

class FloatObjectTest extends TestCase
{
    /**
     * @dataProvider parseProvider
     */
    public function testParse(string $float, FloatObject $object): void
    {
        self::assertEquals($object, FloatObject::parse($float));
    }

    public function parseProvider(): iterable
    {
        #region #,#.#
        yield [
            'float' => '567.078',
            'object' => new FloatObject(567.078, 3)
        ];
        yield [
            'float' => '123,234,567.890987654345678',
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => '123,234,567.89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '123,234,567.89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '12,234,567.89',
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => '1,234,567.89',
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => '234,567.89',
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => '34,567.89',
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => '4,567.89',
            'object' => new FloatObject(4567.89, 2)
        ];
        yield [
            'float' => '567.89',
            'object' => new FloatObject(567.89, 2)
        ];
        yield [
            'float' => '67.89',
            'object' => new FloatObject(67.89, 2)
        ];
        yield [
            'float' => '7.89',
            'object' => new FloatObject(7.89, 2)
        ];
        yield [
            'float' => '0.89',
            'object' => new FloatObject(0.89, 2)
        ];
        #endregion #,#.#

        #region #.#,#
        yield [
            'float' => '123.234.567,890987654345678',
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => '123.234.567,89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '123.234.567,89',
            'object' => new FloatObject(123234567.89, 2 )
        ];

        yield [
            'float' => '12.234.567,89',
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => '1.234.567,89',
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => '234.567,89',
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => '34.567,89',
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => '4.567,89',
            'object' => new FloatObject(4567.89, 2)
        ];
        yield [
            'float' => '567,89',
            'object' => new FloatObject(567.89, 2)
        ];
        yield [
            'float' => '67,89',
            'object' => new FloatObject(67.89, 2)
        ];
        yield [
            'float' => '7,89',
            'object' => new FloatObject(7.89, 2)
        ];
        yield [
            'float' => '0,89',
            'object' => new FloatObject(0.89, 2)
        ];
        #endregion #.#,#

        #region # #.#
        yield [
            'float' => '123 234 567.890987654345678',
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => '123 234 567.89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '123 234 567.89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '12 234 567.89',
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => '1 234 567.89',
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => '234 567.89',
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => '34 567.89',
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => '4 567.89',
            'object' => new FloatObject(4567.89, 2)
        ];
        #endregion # #.#

        #region # #,#
        yield [
            'float' => '123 234 567,890987654345678',
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => '123 234 567,89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '123 234 567,89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '12 234 567,89',
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => '1 234 567,89',
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => '234 567,89',
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => '34 567,89',
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => '4 567,89',
            'object' => new FloatObject(4567.89, 2)
        ];
        #endregion # #,#

        #region #,#·#
        yield [
            'float' => '123,234,567·890987654345678',
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => '123,234,567·89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '123,234,567·89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '12,234,567·89',
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => '1,234,567·89',
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => '234,567·89',
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => '34,567·89',
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => '4,567·89',
            'object' => new FloatObject(4567.89, 2)
        ];
        yield [
            'float' => '567·89',
            'object' => new FloatObject(567.89, 2)
        ];
        yield [
            'float' => '67·89',
            'object' => new FloatObject(67.89, 2)
        ];
        yield [
            'float' => '7·89',
            'object' => new FloatObject(7.89, 2)
        ];
        yield [
            'float' => '0·89',
            'object' => new FloatObject(0.89, 2)
        ];
        #endregion #,#·#

        #region #'#.#
        yield [
            'float' => "123'234'567.890987654345678",
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => "123'234'567.89",
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => "123'234'567.89",
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => "12'234'567.89",
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => "1'234'567.89",
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => "234'567.89",
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => "34'567.89",
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => "4'567.89",
            'object' => new FloatObject(4567.89, 2)
        ];
        yield [
            'float' => "567.89",
            'object' => new FloatObject(567.89, 2)
        ];
        yield [
            'float' => "67.89",
            'object' => new FloatObject(67.89, 2)
        ];
        yield [
            'float' => "7.89",
            'object' => new FloatObject(7.89, 2)
        ];
        #endregion #'#.#

        #region #.#'#
        yield [
            'float' => "123.234.567'890987654345678",
            'object' => new FloatObject(123234567.890987654345678, 15)
        ];
        yield [
            'float' => "123.234.567'89",
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => "123.234.567'89",
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => "12.234.567'89",
            'object' => new FloatObject(12234567.89, 2)
        ];
        yield [
            'float' => "1.234.567'89",
            'object' => new FloatObject(1234567.89, 2)
        ];
        yield [
            'float' => "234.567'89",
            'object' => new FloatObject(234567.89, 2)
        ];
        yield [
            'float' => "34.567'89",
            'object' => new FloatObject(34567.89, 2)
        ];
        yield [
            'float' => "4.567'89",
            'object' => new FloatObject(4567.89, 2)
        ];
        yield [
            'float' => "567'89",
            'object' => new FloatObject(567.89, 2)
        ];
        yield [
            'float' => "67'89",
            'object' => new FloatObject(67.89, 2)
        ];
        yield [
            'float' => "7'89",
            'object' => new FloatObject(7.89, 2)
        ];
        yield [
            'float' => "0'89",
            'object' => new FloatObject(0.89, 2)
        ];
        #endregion #'#.#

        yield [
            'float' => '1,2323,4567.89',
            'object' => new FloatObject(123234567.89, 2)
        ];
        yield [
            'float' => '12,32,34,567.89',
            'object' => new FloatObject(123234567.89, 2)
        ];
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFormat(float $float, string $locale, ?string $format, string $expected): void
    {
        self::assertEquals($expected, FloatObject::parse((string)$float)->format($locale, $format));
    }

    public function formatProvider(): iterable
    {
        yield [
            'float' => 1234567.123456,
            'locale' => 'en',
            'format' => null,
            'expected' => '1,234,567.123456'
        ];
        yield [
            'float' => 1234567.123456,
            'locale' => 'ar_EG',
            'format' => null,
            'expected' => '١٬٢٣٤٬٥٦٧٫١٢٣٤٥٦'
        ];
        yield [
            'float' => 1234567.123456,
            'locale' => 'hi-IN',
            'format' => null,
            'expected' => '12,34,567.123456'
        ];

        yield [
            'float' => 1234567.123456,
            'locale' => 'en',
            'format' => '#,##,##0.#',
            'expected' => '12,34,567.123456'
        ];
        yield [
            'float' => 1234567.123456,
            'locale' => 'ar_EG',
            'format' => '#,##,##0.#',
            'expected' => '١٢٬٣٤٬٥٦٧٫١٢٣٤٥٦'
        ];
        yield [
            'float' => 1234567.123456,
            'locale' => 'en',
            'format' => '#,###0.#',
            'expected' => '123,4567.123456'
        ];
    }
}