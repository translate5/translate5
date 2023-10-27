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

namespace MittagQI\Translate5\Test\Functional\NumberProtection;

use editor_Models_Languages;
use MittagQI\Translate5\NumberProtection\Model\InputMapping;
use MittagQI\Translate5\NumberProtection\Model\NumberFormatDto;
use MittagQI\Translate5\NumberProtection\Model\NumberRepository;
use MittagQI\Translate5\NumberProtection\Model\NumberRecognition;
use MittagQI\Translate5\NumberProtection\Model\OutputMapping;
use MittagQI\Translate5\NumberProtection\NumberProtector;
use MittagQI\Translate5\NumberProtection\Protector\DateProtector;
use MittagQI\Translate5\NumberProtection\Protector\FloatProtector;
use MittagQI\Translate5\NumberProtection\Protector\IntegerProtector;
use PHPUnit\Framework\TestCase;
use ZfExtended_Factory;

class NumberProtectorTest extends TestCase
{
    public function testCustomTargetFormatProcessed(): void
    {
        $langEn = ZfExtended_Factory::get(editor_Models_Languages::class);
        $langEn->loadByRfc5646('en');

        $langDe = ZfExtended_Factory::get(editor_Models_Languages::class);
        $langDe->loadByRfc5646('de');

        $numberFormat = ZfExtended_Factory::get(NumberRecognition::class);
        $numberFormat->setName('test');
        $numberFormat->setRegex('/^\d+ USD$/');
        $numberFormat->setType(IntegerProtector::getType());
        $numberFormat->setKeepAsIs(false);
        $numberFormat->setPriority(1000);
        $numberFormat->save();
        $numberFormat->refresh();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId($langEn->getId());
        $inputMapping->setNumberRecognitionId($numberFormat->getId());
        $inputMapping->save();

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        $outputMapping->setLanguageId($langDe->getId());
        $outputMapping->setNumberRecognitionId($numberFormat->getId());
        $outputMapping->setFormat('# EUR');
        $outputMapping->save();

        $protected = NumberProtector::create()->protect('12345 USD', (int)$langEn->getId(), (int)$langDe->getId());

        $numberFormat->delete();

        self::assertSame(
            '<number type="integer" name="test" source="12345 USD" iso="12345" target="12345 EUR"/>',
            $protected
        );
    }

    public function testMajorCustomTargetFormatProcessed(): void
    {
        $langEn = ZfExtended_Factory::get(editor_Models_Languages::class);
        $langEn->loadByRfc5646('en');

        $langDe = ZfExtended_Factory::get(editor_Models_Languages::class);
        $langDe->loadByRfc5646('de');

        $langDeAt = ZfExtended_Factory::get(editor_Models_Languages::class);
        $langDeAt->loadByRfc5646('de-at');

        $numberFormat = ZfExtended_Factory::get(NumberRecognition::class);
        $numberFormat->setName('test');
        $numberFormat->setRegex('/^\d+ USD$/');
        $numberFormat->setType(IntegerProtector::getType());
        $numberFormat->setKeepAsIs(false);
        $numberFormat->setPriority(1000);
        $numberFormat->save();
        $numberFormat->refresh();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId($langEn->getId());
        $inputMapping->setNumberRecognitionId($numberFormat->getId());
        $inputMapping->save();

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        $outputMapping->setLanguageId($langDe->getId());
        $outputMapping->setNumberRecognitionId($numberFormat->getId());
        $outputMapping->setFormat('# EUR');
        $outputMapping->save();

        $protected = NumberProtector::create()->protect('12345 USD', (int) $langEn->getId(), (int) $langDeAt->getId());

        $numberFormat->delete();

        self::assertSame(
            '<number type="integer" name="test" source="12345 USD" iso="12345" target="12345 EUR"/>',
            $protected
        );
    }

    /**
     * @dataProvider numbersProvider
     */
    public function testProtect(string $node, string $expected): void
    {
        $protector = NumberProtector::create($this->getNumberFormatRepository());

        self::assertTrue($protector->hasEntityToProtect($node));

        self::assertSame($expected, $protector->protect($node, 5, 6));
    }

    public function testProtectRepeatableNumbers(): void
    {
        $protector = NumberProtector::create($this->getNumberFormatRepository());

        self::assertSame(
            'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20"/> string',
            $protector->protect('string 20231020 string 20231020 string', 5, 6)
        );
    }

    /**
     * @dataProvider numbersProvider
     */
    public function testUnprotect(string $expected, string $node, bool $runTest = true): void
    {
        $protector = NumberProtector::create();

        if (!$runTest) {
            // Test case designed for `protect` test only
            self::assertTrue(true);

            return;
        }
        self::assertSame($expected, $protector->unprotect($node));
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
            'expected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20"/> string'
        ];
        yield [
            'string' => 'string 5 7 2023 string',
            'expected' => 'string <number type="date" name="default d m Y" source="5 7 2023" iso="2023-07-05" target="2023-07-05"/> string'
        ];
        yield [
            'string' => 'string 31 5 2023 string',
            'expected' => 'string <number type="date" name="default d m Y" source="31 5 2023" iso="2023-05-31" target="2023-05-31"/> string'
        ];
        yield [
            'string' => 'string 2023 05 07 string',
            'expected' => 'string <number type="date" name="default Y m d" source="2023 05 07" iso="2023-05-07" target="2023-05-07"/> string'
        ];
        yield [
            'string' => 'string 2023 5 7 string',
            'expected' => 'string <number type="date" name="default Y m d" source="2023 5 7" iso="2023-05-07" target="2023-05-07"/> string'
        ];
        yield [
            'string' => 'string 2023 5 30 string',
            'expected' => 'string <number type="date" name="default Y m d" source="2023 5 30" iso="2023-05-30" target="2023-05-30"/> string'
        ];
        yield [
            'string' => 'string 2023 12 31 string',
            'expected' => 'string <number type="date" name="default Y m d" source="2023 12 31" iso="2023-12-31" target="2023-12-31"/> string'
        ];

        yield [
            'string' => 'string 05/07 2023 string',
            'expected' => 'string <number type="date" name="default d/m Y" source="05/07 2023" iso="2023-07-05" target="2023-07-05"/> string'
        ];
        yield [
            'string' => 'string 31/12 2023 string',
            'expected' => 'string <number type="date" name="default d/m Y" source="31/12 2023" iso="2023-12-31" target="2023-12-31"/> string'
        ];
    }
    
    public function looksLikeDatesProvider(): iterable
    {
        yield [
            'string' => 'string 31 11 2023 string',
            'expected' => 'string <number type="integer" name="default simple" source="31" iso="31" target="31"/> <number type="integer" name="default simple" source="11" iso="11" target="11"/> <number type="integer" name="default simple" source="2023" iso="2023" target="2023"/> string'
        ];
        yield [
            'string' => 'string 20233108 string',
            'expected' => 'string <number type="integer" name="default simple" source="20233108" iso="20233108" target="20233108"/> string'
        ];
        yield [
            'string' => 'string 05 07 23 string',
            'expected' => 'string 05 07 <number type="integer" name="default simple" source="23" iso="23" target="23"/> string'
        ];
        yield [
            'string' => 'string 5 7 23 string',
            'expected' => 'string <number type="integer" name="default simple" source="5" iso="5" target="5"/> <number type="integer" name="default simple" source="7" iso="7" target="7"/> <number type="integer" name="default simple" source="23" iso="23" target="23"/> string'
        ];
        yield [
            'string' => 'string 2023 32 3 string',
            'expected' => 'string <number type="integer" name="default simple" source="2023" iso="2023" target="2023"/> <number type="integer" name="default simple" source="32" iso="32" target="32"/> <number type="integer" name="default simple" source="3" iso="3" target="3"/> string'
        ];
        yield [
            'string' => 'string 05/07/123 string',
            'expected' => 'string 05/07/123 string'
        ];
        yield [
            'string' => 'string 123/05/07 string',
            'expected' => 'string 123/05/07 string'
        ];
        yield [
            'string' => 'string 35/7/2023 string',
            'expected' => 'string 35/7/2023 string'
        ];
        yield [
            'string' => 'string 35/07/2023 string',
            'expected' => 'string 35/07/2023 string'
        ];
        yield [
            'string' => 'string 2023 12/31 string',
            'expected' => 'string <number type="integer" name="default simple" source="2023" iso="2023" target="2023"/> 12/31 string'
        ];
        yield [
            'string' => 'This is <tag1><number type="integer" name="default simple" source="123" iso="123" target="123"/><tag2>malicious 546.5</tag2>2035</tag1> text',
            'expected' => 'This is <tag1><number type="integer" name="default simple" source="123" iso="123" target="123"/><tag2>malicious <number type="float" name="default generic" source="546.5" iso="546.5" target="546.5"/></tag2><number type="integer" name="default simple" source="2035" iso="2035" target="2035"/></tag1> text',
            'useForUnprotectTest' => false,
        ];
        yield [
            'string' => 'string 05.07.123 string',
            'expected' => 'string 05.07.123 string'
        ];
        yield [
            'string' => 'string 05-07-123 string',
            'expected' => 'string 05-07-123 string'
        ];
        yield [
            'string' => 'string 35-7-2023 string',
            'expected' => 'string 35-7-2023 string'
        ];
        yield [
            'string' => 'string 35-07-2023 string',
            'expected' => 'string 35-07-2023 string'
        ];
    }

    public function floatsProvider(): iterable
    {
        yield [
            'string' => 'string 9.012345 string',
            'expected' => 'string <number type="float" name="default generic" source="9.012345" iso="9.012345" target="9.012345"/> string'
        ];
        yield [
            'string' => 'string 123456789.12345 string',
            'expected' => 'string <number type="float" name="default generic" source="123456789.12345" iso="123456789.12345" target="123456789.12345"/> string'
        ];
        yield [
            'string' => 'string 123456789,12345 string',
            'expected' => 'string <number type="float" name="default generic" source="123456789,12345" iso="123456789.12345" target="123456789.12345"/> string'
        ];
        yield [
            'string' => 'string 123456789·12345 string',
            'expected' => 'string <number type="float" name="default generic" source="123456789·12345" iso="123456789.12345" target="123456789.12345"/> string'
        ];

        yield [
            'string' => 'string 1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="1,234,567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 1,234,567·89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal middle dot" source="1,234,567·89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 12,34,567.89 string',
            'expected' => 'string <number type="float" name="default indian" source="12,34,567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 123,4567.89 string',
            'expected' => 'string <number type="float" name="default chinese" source="123,4567.89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with whitespace thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with whitespace thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with [THSP] thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with [THSP] thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with [NNBSP] thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with [NNBSP] thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => 'string 1˙234˙567.89 string',
            'expected' => 'string <number type="float" name="default with &quot;˙&quot; thousand decimal dot" source="1˙234˙567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => 'string 1˙234˙567,89 string',
            'expected' => 'string <number type="float" name="default with &quot;˙&quot; thousand decimal comma" source="1˙234˙567,89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => "string 1'234'567.89 string",
            'expected' => 'string <number type="float" name="default with &quot;\'&quot; thousand decimal dot" source="1\'234\'567.89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => "string 1'234'567,89 string",
            'expected' => 'string <number type="float" name="default with &quot;\'&quot; thousand decimal comma" source="1\'234\'567,89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => 'string 1.234.567,89 string',
            'expected' => 'string <number type="float" name="default with dot thousand decimal comma" source="1.234.567,89" iso="1234567.89" target="1234567.89"/> string'
        ];
        yield [
            'string' => "string 1.234.567'89 string",
            'expected' => 'string <number type="float" name="default with &quot;\'&quot; separator" source="1.234.567\'89" iso="1234567.89" target="1234567.89"/> string'
        ];

        yield [
            'string' => "string 1.23e12 string",
            'expected' => 'string <number type="float" name="default exponent" source="1.23e12" iso="1.23e12" target=""/> string'
        ];
        yield [
            'string' => "string 1.13e-15 string",
            'expected' => 'string <number type="float" name="default exponent" source="1.13e-15" iso="1.13e-15" target=""/> string'
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧٫٨٩ string",
            'expected' => 'string <number type="float" name="default arabian" source="١٬٢٣٤٬٥٦٧٫٨٩" iso="1234567.89" target="1234567.89"/> string'
        ];
    }

    public function looksLikeFloat(): iterable
    {
        yield [
            'string' => 'string 0567,89 string',
            'expected' => 'string 0567,89 string'
        ];
        yield [
            'string' => 'string 5.67,89.45 string',
            'expected' => 'string 5.67,89.45 string'
        ];
    }

    public function integersProvider(): iterable
    {
        yield [
            'string' => 'string 123456789 string',
            'expected' => 'string <number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/> string'
        ];

        yield [
            'string' => 'string 1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with separator" source="1,234,567" iso="1234567" target="1234567"/> string'
        ];
        yield [
            'string' => 'string 12,34,567 string',
            'expected' => 'string <number type="integer" name="default indian with comma thousand" source="12,34,567" iso="1234567" target="1234567"/> string'
        ];
        yield [
            'string' => 'string 1,1234,4567 string',
            'expected' => 'string <number type="integer" name="default chinese with comma thousand" source="1,1234,4567" iso="112344567" target="112344567"/> string'
        ];

        yield [
            'string' => 'string 11 234 567 string',
            'expected' => 'string <number type="integer" name="default generic with not standard separator" source="11 234 567" iso="11234567" target="11234567"/> string'
        ];

        yield [
            'string' => 'string 1 234 567 string',
            'expected' => 'string <number type="integer" name="default generic with not standard separator" source="1 234 567" iso="1234567" target="1234567"/> string'
        ];

        yield [
            'string' => 'string 1˙234˙567 string',
            'expected' => 'string <number type="integer" name="default generic with not standard separator" source="1˙234˙567" iso="1234567" target="1234567"/> string'
        ];

        yield [
            'string' => "string 1'234'567 string",
            'expected' => 'string <number type="integer" name="default generic with not standard separator" source="1\'234\'567" iso="1234567" target="1234567"/> string'
        ];

        yield [
            'string' => 'string 1.234.567 string',
            'expected' => 'string <number type="integer" name="default generic with separator" source="1.234.567" iso="1234567" target="1234567"/> string'
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧ string",
            'expected' => 'string <number type="integer" name="default arabian with separator" source="١٬٢٣٤٬٥٦٧" iso="1234567" target="1234567"/> string'
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
            'expected' => 'string <number type="integer" name="default simple" source="67" iso="67" target="67"/> <number type="integer" name="default simple" source="89" iso="89" target="89"/> <number type="integer" name="default simple" source="45" iso="45" target="45"/> string'
        ];
    }

    public function ipsProvider(): iterable
    {
        yield [
            'string' => 'string 127.0.0.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="127.0.0.1" iso="127.0.0.1" target=""/> string'
        ];
        yield [
            'string' => 'string 255.255.255.255 string',
            'expected' => 'string <number type="ip-address" name="default" source="255.255.255.255" iso="255.255.255.255" target=""/> string'
        ];
        yield [
            'string' => 'string 0.0.0.0 string',
            'expected' => 'string <number type="ip-address" name="default" source="0.0.0.0" iso="0.0.0.0" target=""/> string'
        ];
        yield [
            'string' => 'string 1.1.1.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="1.1.1.1" iso="1.1.1.1" target=""/> string'
        ];
    }

    public function looksLikeIpAddress(): iterable
    {
        yield [
            'string' => 'string 1.0.0.1.1 string',
            'expected' => 'string 1.0.0.1.1 string'
        ];
        yield [
            'string' => 'string 1.1.1.256 string',
            'expected' => 'string 1.1.1.256 string'
        ];
        yield [
            'string' => 'string 256.0.0.1 string',
            'expected' => 'string 256.0.0.1 string'
        ];
        yield [
            'string' => 'string 1.256.0.0 string',
            'expected' => 'string 1.256.0.0 string'
        ];
        yield [
            'string' => 'string 1.0.256.1 string',
            'expected' => 'string 1.0.256.1 string'
        ];
    }

    public function macsProvider(): iterable
    {
        yield [
            'string' => 'string 01:02:03:04:ab:cd string',
            'expected' => 'string <number type="mac-address" name="default" source="01:02:03:04:ab:cd" iso="01:02:03:04:ab:cd" target=""/> string'
        ];
        yield [
            'string' => 'string 01-02-03-04-ab-cd string',
            'expected' => 'string <number type="mac-address" name="default" source="01-02-03-04-ab-cd" iso="01-02-03-04-ab-cd" target=""/> string'
        ];
        yield [
            'string' => 'string 00:00:00:00:00:00 string',
            'expected' => 'string <number type="mac-address" name="default" source="00:00:00:00:00:00" iso="00:00:00:00:00:00" target=""/> string'
        ];
        yield [
            'string' => 'string FF:FF:FF:FF:FF:FF string',
            'expected' => 'string <number type="mac-address" name="default" source="FF:FF:FF:FF:FF:FF" iso="FF:FF:FF:FF:FF:FF" target=""/> string'
        ];
        yield [
            'string' => 'string FF-11-FF-33-FF-44 string',
            'expected' => 'string <number type="mac-address" name="default" source="FF-11-FF-33-FF-44" iso="FF-11-FF-33-FF-44" target=""/> string'
        ];
    }

    public function looksLikeMacAddress(): iterable
    {
        yield [
            'string' => 'string 01-02-03-04-ab-cd-11 string',
            'expected' => 'string 01-02-03-04-ab-cd-11 string'
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
            'expected' => 'string 00:00:00:00:00:00:00 string'
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
            'string' => 'string &Alpha;123456789&quot; string',
            'expected' => 'string &Alpha;123456789&quot; string'
        ];
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
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/><someTag/> string'
        ];
        yield [
            'string' => 'string <someTag>123456789</someTag> string',
            'expected' => 'string <someTag><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/></someTag> string'
        ];
        yield [
            'string' => '123456789<someTag/> string',
            'expected' => '<number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/><someTag/> string'
        ];
        yield [
            'string' => '<someTag/>123456789<someTag/> string',
            'expected' => '<someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/><someTag/> string'
        ];
        yield [
            'string' => 'string <someTag/>123456789',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/>'
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/>',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789"/><someTag/>'
        ];
        yield 'date at the beginning and end of text' => [
            'string' => '2023/18/07 some text with date in it 2023/18/07',
            'expected' => '<number type="date" name="default Y/d/m" source="2023/18/07" iso="2023-07-18" target="2023-07-18"/> some text with date in it <number type="date" name="default Y/d/m" source="2023/18/07" iso="2023-07-18" target="2023-07-18"/>',
        ];
        yield 'already protected number is safe' => [
            'string' => 'some text with date in it: <number type="date" name="test-default" source="2023/18/07" iso="2023-07-18" target="18.07.23"/>',
            'expected' => 'some text with date in it: <number type="date" name="test-default" source="2023/18/07" iso="2023-07-18" target="18.07.23"/>',
            'useForUnprotectTest' => false,
        ];
    }

    private function getNumberFormatRepository(): NumberRepository
    {
        $dbNumberRecognition = ZfExtended_Factory::get(NumberRecognition::class)->db;
        $numberRecognitionTable = $dbNumberRecognition->info($dbNumberRecognition::NAME);
        $select = $dbNumberRecognition->select()
            ->from(['recognition' => $numberRecognitionTable], ['recognition.*'])
            ->where('isDefault = true')
            ->where('enabled = true')
            ->order('priority desc');

        $getAll = function ($select) use ($dbNumberRecognition) {
            foreach ($dbNumberRecognition->fetchAll($select) as $formatData) {
                $dto = NumberFormatDto::fromRow($formatData);
                yield $dto;
            }
        };

        $findOutputFormat = function () {
            return match (func_get_args()[1]) {
                DateProtector::getType() => 'Y-m-d',
                FloatProtector::getType() => '#.#',
                IntegerProtector::getType() => '#',
                default => null
            };
        };

        $numberRepository = $this->createConfiguredMock(
            NumberRepository::class,
            [
                'getAll' => $getAll($select),
            ]
        );
        $numberRepository->method('findOutputFormat')->will($this->returnCallback($findOutputFormat));

        return $numberRepository;
    }
}
