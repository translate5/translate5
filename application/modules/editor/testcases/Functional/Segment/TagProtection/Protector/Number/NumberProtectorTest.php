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

namespace MittagQI\Translate5\Test\Functional\Segment\TagProtection\Protector;

use MittagQI\Translate5\Repository\LanguageNumberFormatRepository;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\DateProtector;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\FloatProtector;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\IntegerProtector;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\IPAddressProtector;
use MittagQI\Translate5\Segment\TagProtection\Protector\Number\MacAddressProtector;
use MittagQI\Translate5\Segment\TagProtection\Protector\NumberProtector;
use PHPUnit\Framework\TestCase;

class NumberProtectorTest extends TestCase
{
    /**
     * @dataProvider numbersProvider
     */
    public function test(string $node, string $expected): void
    {
        $numberFormatRepository = new LanguageNumberFormatRepository();
        $protectors = [
            new DateProtector($numberFormatRepository),
            new FloatProtector($numberFormatRepository),
            new IntegerProtector($numberFormatRepository),
            new IPAddressProtector($numberFormatRepository),
            new MacAddressProtector($numberFormatRepository),
        ];

        $protector = new NumberProtector(
            $protectors,
            $numberFormatRepository,
            new LanguageRepository()
        );

        self::assertTrue($protector->hasEntityToProtect($node));

        self::assertSame($expected, $protector->protect($node, null, null));
    }
    
    public function numbersProvider(): iterable
    {
        yield from $this->datesProvider();
        yield from $this->looksLikeDatesProvider();
        yield from $this->floatsProvider();
        yield from $this->looksLikeFloat();
        yield from $this->integersProvider();
        yield from $this->looksLikeIntegers();
        yield from $this->ipsProvider();
        yield from $this->looksLikeIpAddress();
        yield from $this->macsProvider();
        yield from $this->looksLikeMacAddress();
        yield from $this->trickyCasesProvider();
    }

    public function datesProvider(): iterable
    {
        yield [
            'string' => 'string 20231020 string',
            'expected' => 'string <number type="date" name="default" source="20231020" iso="2023-10-20" target=""/> string'
        ];
        yield [
            'string' => 'string 20231230 string',
            'expected' => 'string <number type="date" name="default" source="20231230" iso="2023-12-30" target=""/> string'
        ];

        yield [
            'string' => 'string 05/07/23 string',
            'expected' => 'string <number type="date" name="default" source="05/07/23" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 05/27/23 string',
            'expected' => 'string <number type="date" name="default" source="05/27/23" iso="2023-05-27" target=""/> string'
        ];
        yield [
            'string' => 'string 05/17/23 string',
            'expected' => 'string <number type="date" name="default" source="05/17/23" iso="2023-05-17" target=""/> string'
        ];
        yield [
            'string' => 'string 05/30/23 string',
            'expected' => 'string <number type="date" name="default" source="05/30/23" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 31/07/23 string',
            'expected' => 'string <number type="date" name="default" source="31/07/23" iso="2023-07-31" target=""/> string'
        ];
        yield [
            'string' => 'string 26/07/23 string',
            'expected' => 'string <number type="date" name="default" source="26/07/23" iso="2023-07-26" target=""/> string'
        ];
        yield [
            'string' => 'string 19/07/23 string',
            'expected' => 'string <number type="date" name="default" source="19/07/23" iso="2023-07-19" target=""/> string'
        ];
        yield [
            'string' => 'string 19/7/23 string',
            'expected' => 'string <number type="date" name="default" source="19/7/23" iso="2023-07-19" target=""/> string'
        ];
        yield [
            'string' => 'string 5/7/23 string',
            'expected' => 'string <number type="date" name="default" source="5/7/23" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 5/27/23 string',
            'expected' => 'string <number type="date" name="default" source="5/27/23" iso="2023-05-27" target=""/> string'
        ];
        yield [
            'string' => 'string 5/17/23 string',
            'expected' => 'string <number type="date" name="default" source="5/17/23" iso="2023-05-17" target=""/> string'
        ];
        yield [
            'string' => 'string 5/30/23 string',
            'expected' => 'string <number type="date" name="default" source="5/30/23" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 05/07/2023 string',
            'expected' => 'string <number type="date" name="default" source="05/07/2023" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 5/7/2023 string',
            'expected' => 'string <number type="date" name="default" source="5/7/2023" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 2023/05/07 string',
            'expected' => 'string <number type="date" name="default" source="2023/05/07" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 2023/5/7 string',
            'expected' => 'string <number type="date" name="default" source="2023/5/7" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 35/7/23 string',
            'expected' => 'string <number type="date" name="default" source="35/7/23" iso="2035-07-23" target=""/> string'
        ];
        yield [
            'string' => 'string 35/07/23 string',
            'expected' => 'string <number type="date" name="default" source="35/07/23" iso="2035-07-23" target=""/> string'
        ];

        yield [
            'string' => 'string 05-07-23 string',
            'expected' => 'string <number type="date" name="default" source="05-07-23" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 05-27-23 string',
            'expected' => 'string <number type="date" name="default" source="05-27-23" iso="2023-05-27" target=""/> string'
        ];
        yield [
            'string' => 'string 05-17-23 string',
            'expected' => 'string <number type="date" name="default" source="05-17-23" iso="2023-05-17" target=""/> string'
        ];
        yield [
            'string' => 'string 05-30-23 string',
            'expected' => 'string <number type="date" name="default" source="05-30-23" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 31-07-23 string',
            'expected' => 'string <number type="date" name="default" source="31-07-23" iso="2023-07-31" target=""/> string'
        ];
        yield [
            'string' => 'string 26-07-23 string',
            'expected' => 'string <number type="date" name="default" source="26-07-23" iso="2023-07-26" target=""/> string'
        ];
        yield [
            'string' => 'string 19-07-23 string',
            'expected' => 'string <number type="date" name="default" source="19-07-23" iso="2023-07-19" target=""/> string'
        ];
        yield [
            'string' => 'string 19-7-23 string',
            'expected' => 'string <number type="date" name="default" source="19-7-23" iso="2023-07-19" target=""/> string'
        ];
        yield [
            'string' => 'string 5-7-23 string',
            'expected' => 'string <number type="date" name="default" source="5-7-23" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 5-27-23 string',
            'expected' => 'string <number type="date" name="default" source="5-27-23" iso="2023-05-27" target=""/> string'
        ];
        yield [
            'string' => 'string 5-17-23 string',
            'expected' => 'string <number type="date" name="default" source="5-17-23" iso="2023-05-17" target=""/> string'
        ];
        yield [
            'string' => 'string 5-30-23 string',
            'expected' => 'string <number type="date" name="default" source="5-30-23" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 05-07-2023 string',
            'expected' => 'string <number type="date" name="default" source="05-07-2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 5-7-2023 string',
            'expected' => 'string <number type="date" name="default" source="5-7-2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 2023-05-07 string',
            'expected' => 'string <number type="date" name="default" source="2023-05-07" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 2023-5-7 string',
            'expected' => 'string <number type="date" name="default" source="2023-5-7" iso="2023-05-07" target=""/> string'
        ];

        yield [
            'string' => 'string 05.07.23 string',
            'expected' => 'string <number type="date" name="default" source="05.07.23" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 05.27.23 string',
            'expected' => 'string <number type="date" name="default" source="05.27.23" iso="2023-05-27" target=""/> string'
        ];
        yield [
            'string' => 'string 05.17.23 string',
            'expected' => 'string <number type="date" name="default" source="05.17.23" iso="2023-05-17" target=""/> string'
        ];
        yield [
            'string' => 'string 05.30.23 string',
            'expected' => 'string <number type="date" name="default" source="05.30.23" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 31.07.23 string',
            'expected' => 'string <number type="date" name="default" source="31.07.23" iso="2023-07-31" target=""/> string'
        ];

        yield [
            'string' => 'string 26.07.23 string',
            'expected' => 'string <number type="date" name="default" source="26.07.23" iso="2023-07-26" target=""/> string'
        ];
        yield [
            'string' => 'string 19.07.23 string',
            'expected' => 'string <number type="date" name="default" source="19.07.23" iso="2023-07-19" target=""/> string'
        ];
        yield [
            'string' => 'string 19.7.23 string',
            'expected' => 'string <number type="date" name="default" source="19.7.23" iso="2023-07-19" target=""/> string'
        ];
        yield [
            'string' => 'string 5.7.23 string',
            'expected' => 'string <number type="date" name="default" source="5.7.23" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 5.27.23 string',
            'expected' => 'string <number type="date" name="default" source="5.27.23" iso="2023-05-27" target=""/> string'
        ];
        yield [
            'string' => 'string 5.17.23 string',
            'expected' => 'string <number type="date" name="default" source="5.17.23" iso="2023-05-17" target=""/> string'
        ];
        yield [
            'string' => 'string 5.30.23 string',
            'expected' => 'string <number type="date" name="default" source="5.30.23" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 05.07.2023 string',
            'expected' => 'string <number type="date" name="default" source="05.07.2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 5.7.2023 string',
            'expected' => 'string <number type="date" name="default" source="5.7.2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 2023.05.07 string',
            'expected' => 'string <number type="date" name="default" source="2023.05.07" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 2023.5.7 string',
            'expected' => 'string <number type="date" name="default" source="2023.5.7" iso="2023-05-07" target=""/> string'
        ];

        yield [
            'string' => 'string 05 07 2023 string',
            'expected' => 'string <number type="date" name="default" source="05 07 2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 5 7 2023 string',
            'expected' => 'string <number type="date" name="default" source="5 7 2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 31 5 2023 string',
            'expected' => 'string <number type="date" name="default" source="31 5 2023" iso="2023-05-31" target=""/> string'
        ];
        yield [
            'string' => 'string 2023 05 07 string',
            'expected' => 'string <number type="date" name="default" source="2023 05 07" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 2023 5 7 string',
            'expected' => 'string <number type="date" name="default" source="2023 5 7" iso="2023-05-07" target=""/> string'
        ];
        yield [
            'string' => 'string 2023 5 30 string',
            'expected' => 'string <number type="date" name="default" source="2023 5 30" iso="2023-05-30" target=""/> string'
        ];
        yield [
            'string' => 'string 2023 12 31 string',
            'expected' => 'string <number type="date" name="default" source="2023 12 31" iso="2023-12-31" target=""/> string'
        ];

        yield [
            'string' => 'string 05/07 2023 string',
            'expected' => 'string <number type="date" name="default" source="05/07 2023" iso="2023-07-05" target=""/> string'
        ];
        yield [
            'string' => 'string 31/12 2023 string',
            'expected' => 'string <number type="date" name="default" source="31/12 2023" iso="2023-12-31" target=""/> string'
        ];
    }
    
    public function looksLikeDatesProvider(): iterable
    {
        yield [
            'string' => 'string 31 11 2023 string',
            'expected' => 'string <number type="integer" name="default" source="31" iso="31" target=""/> <number type="integer" name="default" source="11" iso="11" target=""/> <number type="integer" name="default" source="2023" iso="2023" target=""/> string'
        ];
        yield [
            'string' => 'string 20233108 string',
            'expected' => 'string <number type="integer" name="default" source="20233108" iso="20233108" target=""/> string'
        ];
        yield [
            'string' => 'string 05 07 23 string',
            'expected' => 'string 05 07 <number type="integer" name="default" source="23" iso="23" target=""/> string'
        ];
        yield [
            'string' => 'string 5 7 23 string',
            'expected' => 'string <number type="integer" name="default" source="5" iso="5" target=""/> <number type="integer" name="default" source="7" iso="7" target=""/> <number type="integer" name="default" source="23" iso="23" target=""/> string'
        ];
        yield [
            'string' => 'string 2023 32 3 string',
            'expected' => 'string <number type="integer" name="default" source="2023" iso="2023" target=""/> <number type="integer" name="default" source="32" iso="32" target=""/> <number type="integer" name="default" source="3" iso="3" target=""/> string'
        ];
        yield [
            'string' => 'string 05/07/123 string',
            'expected' => 'string 05/07/<number type="integer" name="default" source="123" iso="123" target=""/> string'
        ];
        yield [
            'string' => 'string 123/05/07 string',
            'expected' => 'string <number type="integer" name="default" source="123" iso="123" target=""/>/05/07 string'
        ];
        yield [
            'string' => 'string 35/7/2023 string',
            'expected' => 'string <number type="integer" name="default" source="35" iso="35" target=""/>/<number type="integer" name="default" source="7" iso="7" target=""/>/<number type="integer" name="default" source="2023" iso="2023" target=""/> string'
        ];
        yield [
            'string' => 'string 35/07/2023 string',
            'expected' => 'string <number type="integer" name="default" source="35" iso="35" target=""/>/07/<number type="integer" name="default" source="2023" iso="2023" target=""/> string'
        ];
        yield [
            'string' => 'string 2023 12/31 string',
            'expected' => 'string <number type="integer" name="default" source="2023" iso="2023" target=""/> <number type="integer" name="default" source="12" iso="12" target=""/>/<number type="integer" name="default" source="31" iso="31" target=""/> string'
        ];
        yield [
            'string' => 'This is <tag1><number type="integer" name="default" source="123" iso="123" target=""/><tag2>malicious 546.5</tag2>2035</tag1> text',
            'expected' => 'This is <tag1><number type="integer" name="default" source="123" iso="123" target=""/><tag2>malicious <number type="float" name="default" source="546.5" iso="546.5" target=""/></tag2><number type="integer" name="default" source="2035" iso="2035" target=""/></tag1> text'
        ];
        yield [
            'string' => 'string 05.07.123 string',
            'expected' => 'string 05.07.<number type="integer" name="default" source="123" iso="123" target=""/> string'
        ];
        yield [
            'string' => 'string 05-07-123 string',
            'expected' => 'string 05-07-<number type="integer" name="default" source="123" iso="123" target=""/> string'
        ];
        yield [
            'string' => 'string 35-7-2023 string',
            'expected' => 'string <number type="integer" name="default" source="35" iso="35" target=""/>-<number type="integer" name="default" source="7" iso="7" target=""/>-<number type="integer" name="default" source="2023" iso="2023" target=""/> string'
        ];
        yield [
            'string' => 'string 35-07-2023 string',
            'expected' => 'string <number type="integer" name="default" source="35" iso="35" target=""/>-07-<number type="integer" name="default" source="2023" iso="2023" target=""/> string'
        ];
    }

    public function floatsProvider(): iterable
    {
        yield [
            'string' => 'string 9.012345 string',
            'expected' => 'string <number type="float" name="default" source="9.012345" iso="9.012345" target=""/> string'
        ];
        yield [
            'string' => 'string 123456789.12345 string',
            'expected' => 'string <number type="float" name="default" source="123456789.12345" iso="123456789.12345" target=""/> string'
        ];
        yield [
            'string' => 'string 123456789,12345 string',
            'expected' => 'string <number type="float" name="default" source="123456789,12345" iso="123456789.12345" target=""/> string'
        ];
        yield [
            'string' => 'string 123456789·12345 string',
            'expected' => 'string <number type="float" name="default" source="123456789·12345" iso="123456789.12345" target=""/> string'
        ];

        yield [
            'string' => 'string 1,234,567.89 string',
            'expected' => 'string <number type="float" name="default" source="1,234,567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 1,234,567·89 string',
            'expected' => 'string <number type="float" name="default" source="1,234,567·89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 12,34,567.89 string',
            'expected' => 'string <number type="float" name="default" source="12,34,567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 123,4567.89 string',
            'expected' => 'string <number type="float" name="default" source="123,4567.89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default" source="1 234 567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default" source="1 234 567,89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default" source="1 234 567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default" source="1 234 567,89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default" source="1 234 567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default" source="1 234 567,89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => 'string 1˙234˙567.89 string',
            'expected' => 'string <number type="float" name="default" source="1˙234˙567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => 'string 1˙234˙567,89 string',
            'expected' => 'string <number type="float" name="default" source="1˙234˙567,89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => "string 1'234'567.89 string",
            'expected' => 'string <number type="float" name="default" source="1\'234\'567.89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => "string 1'234'567,89 string",
            'expected' => 'string <number type="float" name="default" source="1\'234\'567,89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => 'string 1.234.567,89 string',
            'expected' => 'string <number type="float" name="default" source="1.234.567,89" iso="1234567.89" target=""/> string'
        ];
        yield [
            'string' => "string 1.234.567'89 string",
            'expected' => 'string <number type="float" name="default" source="1.234.567\'89" iso="1234567.89" target=""/> string'
        ];

        yield [
            'string' => "string 1.23e12 string",
            'expected' => 'string <number type="float" name="default" source="1.23e12" iso="" target=""/> string'
        ];
        yield [
            'string' => "string 1.13e-15 string",
            'expected' => 'string <number type="float" name="default" source="1.13e-15" iso="" target=""/> string'
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧٫٨٩ string",
            'expected' => 'string <number type="float" name="default" source="١٬٢٣٤٬٥٦٧٫٨٩" iso="1234567.89" target=""/> string'
        ];
    }

    public function looksLikeFloat(): iterable
    {
        yield [
            'string' => 'string 0567,89 string',
            'expected' => 'string 0567,<number type="integer" name="default" source="89" iso="89" target=""/> string'
        ];
        yield [
            'string' => 'string 5.67,89.45 string',
            'expected' => 'string <number type="float" name="default" source="5.67" iso="5.67" target=""/>,<number type="float" name="default" source="89.45" iso="89.45" target=""/> string'
        ];
    }

    public function integersProvider(): iterable
    {
        yield [
            'string' => 'string 123456789 string',
            'expected' => 'string <number type="integer" name="default" source="123456789" iso="123456789" target=""/> string'
        ];

        yield [
            'string' => 'string 1,234,567 string',
            'expected' => 'string <number type="integer" name="default" source="1,234,567" iso="1234567" target=""/> string'
        ];
        yield [
            'string' => 'string 12,34,567 string',
            'expected' => 'string <number type="integer" name="default" source="12,34,567" iso="1234567" target=""/> string'
        ];
        yield [
            'string' => 'string 1,1234,4567 string',
            'expected' => 'string <number type="integer" name="default" source="1,1234,4567" iso="112344567" target=""/> string'
        ];

        yield [
            'string' => 'string 1 234 567 string',
            'expected' => 'string <number type="integer" name="default" source="1 234 567" iso="1234567" target=""/> string'
        ];

        yield [
            'string' => 'string 1 234 567 string',
            'expected' => 'string <number type="integer" name="default" source="1 234 567" iso="1234567" target=""/> string'
        ];

        yield [
            'string' => 'string 1˙234˙567 string',
            'expected' => 'string <number type="integer" name="default" source="1˙234˙567" iso="1234567" target=""/> string'
        ];

        yield [
            'string' => "string 1'234'567 string",
            'expected' => 'string <number type="integer" name="default" source="1\'234\'567" iso="1234567" target=""/> string'
        ];

        yield [
            'string' => 'string 1.234.567 string',
            'expected' => 'string <number type="integer" name="default" source="1.234.567" iso="1234567" target=""/> string'
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧ string",
            'expected' => 'string <number type="integer" name="default" source="١٬٢٣٤٬٥٦٧" iso="1234567" target=""/> string'
        ];
    }

    public function looksLikeIntegers(): iterable
    {
        yield [
            'string' => 'string 0567 string',
            'expected' => 'string 0567 string'
        ];
        yield [
            'string' => 'string 67 89 45 string',
            'expected' => 'string <number type="integer" name="default" source="67" iso="67" target=""/> <number type="integer" name="default" source="89" iso="89" target=""/> <number type="integer" name="default" source="45" iso="45" target=""/> string'
        ];
    }

    public function ipsProvider(): iterable
    {
        yield [
            'string' => 'string 127.0.0.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="127.0.0.1" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string 255.255.255.255 string',
            'expected' => 'string <number type="ip-address" name="default" source="255.255.255.255" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string 0.0.0.0 string',
            'expected' => 'string <number type="ip-address" name="default" source="0.0.0.0" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string 1.1.1.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="1.1.1.1" iso="" target=""/> string'
        ];
    }

    public function looksLikeIpAddress(): iterable
    {
        yield [
            'string' => 'string 1.0.0.1.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="1.0.0.1" iso="" target=""/>.<number type="integer" name="default" source="1" iso="1" target=""/> string'
        ];
        yield [
            'string' => 'string 1.1.1.256 string',
            'expected' => 'string <number type="float" name="default" source="1.1" iso="1.1" target=""/>.<number type="float" name="default" source="1.256" iso="1.256" target=""/> string'
        ];
        yield [
            'string' => 'string 256.0.0.1 string',
            'expected' => 'string <number type="float" name="default" source="256.0" iso="256" target=""/>.<number type="integer" name="default" source="0" iso="0" target=""/>.<number type="integer" name="default" source="1" iso="1" target=""/> string'
        ];
        yield [
            'string' => 'string 1.256.0.0 string',
            'expected' => 'string <number type="float" name="default" source="1.256" iso="1.256" target=""/>.<number type="integer" name="default" source="0" iso="0" target=""/>.<number type="integer" name="default" source="0" iso="0" target=""/> string'
        ];
        yield [
            'string' => 'string 1.0.256.1 string',
            'expected' => 'string <number type="float" name="default" source="1.0" iso="1" target=""/>.<number type="float" name="default" source="256.1" iso="256.1" target=""/> string'
        ];
    }

    public function macsProvider(): iterable
    {
        yield [
            'string' => 'string 01:02:03:04:ab:cd string',
            'expected' => 'string <number type="mac-address" name="default" source="01:02:03:04:ab:cd" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string 01-02-03-04-ab-cd string',
            'expected' => 'string <number type="mac-address" name="default" source="01-02-03-04-ab-cd" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string 00:00:00:00:00:00 string',
            'expected' => 'string <number type="mac-address" name="default" source="00:00:00:00:00:00" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string FF:FF:FF:FF:FF:FF string',
            'expected' => 'string <number type="mac-address" name="default" source="FF:FF:FF:FF:FF:FF" iso="" target=""/> string'
        ];
        yield [
            'string' => 'string FF-11-FF-33-FF-44 string',
            'expected' => 'string <number type="mac-address" name="default" source="FF-11-FF-33-FF-44" iso="" target=""/> string'
        ];
    }

    public function looksLikeMacAddress(): iterable
    {
        yield [
            'string' => 'string 01-02-03-04-ab-cd-11 string',
            'expected' => 'string <number type="mac-address" name="default" source="01-02-03-04-ab-cd" iso="" target=""/>-<number type="integer" name="default" source="11" iso="11" target=""/> string'
        ];
        yield [
            'string' => 'string FF:FF-FF:FF-FF:FF string',
            'expected' => 'string FF:FF-FF:FF-FF:FF string'
        ];
        yield [
            'string' => 'string FG:FG:FF:FF:FF:FF string',
            'expected' => 'string FG:FG:FF:FF:FF:FF string'
        ];
        yield [
            'string' => 'string 0H:00:00:00:00:00 string',
            'expected' => 'string 0H:00:00:00:00:00 string'
        ];
        yield [
            'string' => 'string 00:00:00:00:00:0I string',
            'expected' => 'string 00:00:00:00:00:0I string'
        ];
        yield [
            'string' => 'string 00:00:00:00:00 string',
            'expected' => 'string 00:00:00:00:00 string'
        ];
        yield [
            'string' => 'string 00:00:00:00:00:00:00 string',
            'expected' => 'string <number type="mac-address" name="default" source="00:00:00:00:00:00" iso="" target=""/>:00 string'
        ];
        yield [
            'string' => 'string F:FF:FF:FF:FF:FF string',
            'expected' => 'string F:FF:FF:FF:FF:FF string'
        ];
        yield [
            'string' => 'string FF:FF:FF:FF:F:FF string',
            'expected' => 'string FF:FF:FF:FF:F:FF string'
        ];
    }

    public function trickyCasesProvider(): iterable
    {
        yield [
            'string' => 'string<someTag/>123456789 string',
            'expected' => 'string<someTag/>123456789 string'
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/>string',
            'expected' => 'string <someTag/>123456789<someTag/>string'
        ];
        yield [
            'string' => 'string<someTag/>123456789<someTag/>string',
            'expected' => 'string<someTag/>123456789<someTag/>string'
        ];
        yield [
            'string' => 'string<someTag>123456789</someTag>string',
            'expected' => 'string<someTag>123456789</someTag>string'
        ];
        yield [
            'string' => 'string 123456789<someTag/>string',
            'expected' => 'string 123456789<someTag/>string'
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/> string',
            'expected' => 'string <someTag/><number type="integer" name="default" source="123456789" iso="123456789" target=""/><someTag/> string'
        ];
        yield [
            'string' => 'string <someTag>123456789</someTag> string',
            'expected' => 'string <someTag><number type="integer" name="default" source="123456789" iso="123456789" target=""/></someTag> string'
        ];
        yield [
            'string' => '123456789<someTag/> string',
            'expected' => '<number type="integer" name="default" source="123456789" iso="123456789" target=""/><someTag/> string'
        ];
        yield [
            'string' => '<someTag/>123456789<someTag/> string',
            'expected' => '<someTag/><number type="integer" name="default" source="123456789" iso="123456789" target=""/><someTag/> string'
        ];
        yield [
            'string' => 'string <someTag/>123456789',
            'expected' => 'string <someTag/><number type="integer" name="default" source="123456789" iso="123456789" target=""/>'
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/>',
            'expected' => 'string <someTag/><number type="integer" name="default" source="123456789" iso="123456789" target=""/><someTag/>'
        ];
        yield 'date at the beginning and end of text' => [
            'string' => '2023/18/07 some text with date in it 2023/18/07',
            'expected' => '<number type="date" name="default" source="2023/18/07" iso="2023-07-18" target=""/> some text with date in it <number type="date" name="default" source="2023/18/07" iso="2023-07-18" target=""/>',
        ];
        yield 'already protected number is safe' => [
            'string' => 'some text with date in it: <number type="date" name="test-default" source="2023/18/07" iso="2023-07-18" target="18.07.23"/>',
            'expected' => 'some text with date in it: <number type="date" name="test-default" source="2023/18/07" iso="2023-07-18" target="18.07.23"/>',
        ];
    }
}
