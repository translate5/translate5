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

namespace MittagQI\Translate5\Test\Integration\ContentProtection;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\DateProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\FloatProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IntegerProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\KeepContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtector;
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

        $contentRecognition1 = ZfExtended_Factory::get(ContentRecognition::class);
        $contentRecognition1->setName('test usd');
        $contentRecognition1->setRegex('/^\d+ USD$/');
        $contentRecognition1->setType(IntegerProtector::getType());
        $contentRecognition1->setEnabled(true);
        $contentRecognition1->setKeepAsIs(false);
        $contentRecognition1->save();

        $contentRecognition2 = ZfExtended_Factory::get(ContentRecognition::class);
        $contentRecognition2->setName('test eur');
        $contentRecognition2->setRegex('/^\d+ EUR/');
        $contentRecognition2->setType(IntegerProtector::getType());
        $contentRecognition2->setEnabled(true);
        $contentRecognition2->setKeepAsIs(false);
        $contentRecognition2->setFormat('# EUR');
        $contentRecognition2->save();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId((int) $langEn->getId());
        $inputMapping->setContentRecognitionId($contentRecognition1->getId());
        $inputMapping->setPriority(1000);
        $inputMapping->save();

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        $outputMapping->setLanguageId((int) $langDe->getId());
        $outputMapping->setInputContentRecognitionId($contentRecognition1->getId());
        $outputMapping->setOutputContentRecognitionId($contentRecognition2->getId());
        $outputMapping->save();

        $protected = NumberProtector::create()->protect('12345 USD', true, (int) $langEn->getId(), (int) $langDe->getId());

        $contentRecognition1->delete();
        $contentRecognition2->delete();

        self::assertSame(
            '<number type="integer" name="test usd" source="12345 USD" iso="12345" target="12345 EUR" regex="04+LSdFWCA12UdEHAA=="/>',
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

        $contentRecognition1 = ZfExtended_Factory::get(ContentRecognition::class);
        $contentRecognition1->setName('test usd');
        $contentRecognition1->setRegex('/^\d+ USD$/');
        $contentRecognition1->setType(IntegerProtector::getType());
        $contentRecognition1->setEnabled(true);
        $contentRecognition1->setKeepAsIs(false);
        $contentRecognition1->save();

        $contentRecognition2 = ZfExtended_Factory::get(ContentRecognition::class);
        $contentRecognition2->setName('test eur');
        $contentRecognition2->setRegex('/^\d+ EUR/');
        $contentRecognition2->setType(IntegerProtector::getType());
        $contentRecognition2->setEnabled(true);
        $contentRecognition2->setKeepAsIs(false);
        $contentRecognition2->setFormat('# EUR');
        $contentRecognition2->save();

        $inputMapping = ZfExtended_Factory::get(InputMapping::class);
        $inputMapping->setLanguageId((int) $langEn->getId());
        $inputMapping->setContentRecognitionId($contentRecognition1->getId());
        $inputMapping->setPriority(1000);
        $inputMapping->save();

        $outputMapping = ZfExtended_Factory::get(OutputMapping::class);
        $outputMapping->setLanguageId((int) $langDe->getId());
        $outputMapping->setInputContentRecognitionId($contentRecognition1->getId());
        $outputMapping->setOutputContentRecognitionId($contentRecognition2->getId());
        $outputMapping->save();

        $protected = NumberProtector::create()->protect('12345 USD', true, (int) $langEn->getId(), (int) $langDeAt->getId());

        $contentRecognition1->delete();
        $contentRecognition2->delete();

        self::assertSame(
            '<number type="integer" name="test usd" source="12345 USD" iso="12345" target="12345 EUR" regex="04+LSdFWCA12UdEHAA=="/>',
            $protected
        );
    }

    /**
     * @dataProvider numbersProvider
     */
    public function testProtect(string $node, string $expected): void
    {
        preg_match_all('/name="(.+)"/U', $expected, $matches);
        $names = $matches[1] ?? '';

        $protector = NumberProtector::create($this->getNumberFormatRepository(...$names));

        self::assertTrue($protector->hasEntityToProtect($node));

        self::assertSame($expected, $protector->protect($node, true, 5, 6));
    }

    public function testProtectRepeatableNumbers(): void
    {
        $protector = NumberProtector::create($this->getNumberFormatRepository("default Ymd"));

        self::assertSame(
            'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            $protector->protect('string 20231020 string 20231020 string', true, 5, 6)
        );
    }

    public function testProtectWithNumericRegex(): void
    {
        $dto = new ContentProtectionDto(
            KeepContentProtector::getType(),
            'default',
            '/\d+((\s|\.|\,)(\d)+)*/',
            0,
            null,
            true,
            null,
            0
        );

        $repo = $this->createConfiguredMock(
            ContentProtectionRepository::class,
            [
                'getAllForSource' => [$dto],
                'hasActiveTextRules' => true,
            ]
        );

        $protector = NumberProtector::create($repo);

        $segment = 'Temperaturklasse, maximale Oberflächentemperatur &lt; 135 °C; 275 °F';

        self::assertTrue($protector->hasEntityToProtect($segment));

        $expected = 'Temperaturklasse, maximale Oberflächentemperatur &lt; <number type="keep-content" name="default" source="135" iso="135" target="135" regex="049J0dbQiCmuidGridHR1IhJ0dTW1NIHAA=="/> °C; <number type="keep-content" name="default" source="275" iso="275" target="275" regex="049J0dbQiCmuidGridHR1IhJ0dTW1NIHAA=="/> °F';

        self::assertSame($expected, $protector->protect($segment, true, 5, 6));
    }

    public function testProtectWholeSegmentRegex(): void
    {
        $dto1 = new ContentProtectionDto(
            KeepContentProtector::getType(),
            'default',
            '/text/',
            0,
            null,
            true,
            null,
            0
        );

        $dto2 = new ContentProtectionDto(
            KeepContentProtector::getType(),
            'default',
            '/^((([0-9]\/[0-9])|([0-9])|([0-9][\s ][0-9]\/[0-9]))(,[\s ])?)+$/',
            0,
            null,
            true,
            null,
            0
        );

        $repo = $this->createConfiguredMock(
            ContentProtectionRepository::class,
            [
                'getAllForSource' => [
                    $dto1,
                    $dto2,
                ],
                'hasActiveTextRules' => true,
            ]
        );

        $protector = NumberProtector::create($repo);

        $segment = '12';

        self::assertTrue($protector->hasEntityToProtect($segment));

        $expected = '<number type="keep-content" name="default" source="12" iso="12" target="12" regex="04/T0NCINtC1jI3RB1OaNRoodHRMsUIsigJNDR2woKa9praKPgA="/>';

        self::assertSame($expected, $protector->protect($segment, true, 5, 6));

        $segmentWithText = 'text12';
        $expectedWithText = '<number type="keep-content" name="default" source="text" iso="text" target="text" regex="0y9JrSjRBwA="/>12';

        self::assertSame($expectedWithText, $protector->protect($segmentWithText, true, 5, 6));
    }

    /**
     * @dataProvider numbersProvider
     */
    public function testUnprotect(string $expected, string $node, bool $runTest = true): void
    {
        $protector = NumberProtector::create();

        if (! $runTest) {
            // Test case designed for `protect` test only
            self::assertTrue(true);

            return;
        }
        self::assertSame($expected, $protector->unprotect($node, true));
    }

    public function testUnprotectForTarget(): void
    {
        $protector = NumberProtector::create();

        self::assertSame(
            'string 1.23e12 string',
            $protector->unprotect('string <number type="float" name="test" source="1.23e12" iso="1.23e12" target="1.23e12"/> string', false)
        );

        self::assertSame(
            'string 1,23 string',
            $protector->unprotect('string <number type="float" name="test" source="1.23" iso="1.23" target="1,23"/> string', false)
        );
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
        yield from $this->keepContentProvider();
        yield from $this->replaceContentProvider();
    }

    public function datesProvider(): iterable
    {
        yield [
            'string' => 'string 20231020 string',
            'expected' => 'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
        ];
        yield [
            'string' => 'string 5 7 2023 string',
            'expected' => 'string <number type="date" name="default d m Y" source="5 7 2023" iso="2023-07-05" target="2023-07-05" regex="09eIKa6Jq4nR0NTQMIg21LWMrQGSRrHRBiCmMZAyBItYxmoqwBQYAkWN4KIxKdUmtUDdMXogo1Q0NWs0dGAsEFUTo6mpDwA="/> string',
        ];
        yield [
            'string' => 'string 31 5 2023 string',
            'expected' => 'string <number type="date" name="default d m Y" source="31 5 2023" iso="2023-05-31" target="2023-05-31" regex="09eIKa6Jq4nR0NTQMIg21LWMrQGSRrHRBiCmMZAyBItYxmoqwBQYAkWN4KIxKdUmtUDdMXogo1Q0NWs0dGAsEFUTo6mpDwA="/> string',
        ];
        yield [
            'string' => 'string 2023 05 07 string',
            'expected' => 'string <number type="date" name="default Y d m" source="2023 05 07" iso="2023-07-05" target="2023-07-05" regex="09eIKa6Jq4nR0NSISak2qVXQMIg21LWMrQGSRrHRBiCmMZAyBItYxmrCFRgCRY1gopoaGjF6IKNUNDVrNHRgLBBVE6OpqQ8A"/> string',
        ];
        yield [
            'string' => 'string 2023 5 7 string',
            'expected' => 'string <number type="date" name="default Y d m" source="2023 5 7" iso="2023-07-05" target="2023-07-05" regex="09eIKa6Jq4nR0NSISak2qVXQMIg21LWMrQGSRrHRBiCmMZAyBItYxmrCFRgCRY1gopoaGjF6IKNUNDVrNHRgLBBVE6OpqQ8A"/> string',
        ];
        yield [
            'string' => 'string 2023 5 30 string',
            'expected' => 'string <number type="date" name="default Y m d" source="2023 5 30" iso="2023-05-30" target="2023-05-30" regex="09eIKa6Jq4nR0NSISak2qVXQMIg21LWMrTGMNtA1iq0BczThokDSKBYoA2QaAylDmAJNDY0YPZBRKpqaNRo6MBaIqonR1NQHAA=="/> string',
        ];
        yield [
            'string' => 'string 2023 12 31 string',
            'expected' => 'string <number type="date" name="default Y m d" source="2023 12 31" iso="2023-12-31" target="2023-12-31" regex="09eIKa6Jq4nR0NSISak2qVXQMIg21LWMrTGMNtA1iq0BczThokDSKBYoA2QaAylDmAJNDY0YPZBRKpqaNRo6MBaIqonR1NQHAA=="/> string',
        ];

        yield [
            'string' => 'string 05/07 2023 string',
            'expected' => 'string <number type="date" name="default d/m Y" source="05/07 2023" iso="2023-07-05" target="2023-07-05" regex="09eIKa6Jq4nR0NTQMIg21LWMrQGSRrHRBiCmMZAyBItYxmrG6MNUGAKFjWDCCjEp1Sa1QO0xeiCzVDQ1azR0YCwQVROjqakPAA=="/> string',
        ];
        yield [
            'string' => 'string 31/12 2023 string',
            'expected' => 'string <number type="date" name="default d/m Y" source="31/12 2023" iso="2023-12-31" target="2023-12-31" regex="09eIKa6Jq4nR0NTQMIg21LWMrQGSRrHRBiCmMZAyBItYxmrG6MNUGAKFjWDCCjEp1Sa1QO0xeiCzVDQ1azR0YCwQVROjqakPAA=="/> string',
        ];
    }

    public function looksLikeDatesProvider(): iterable
    {
        yield [
            'string' => 'string 31 11 2023 string',
            'expected' => 'string <number type="integer" name="default simple" source="31" iso="31" target="31" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="11" iso="11" target="11" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="2023" iso="2023" target="2023" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string 20233108 string',
            'expected' => 'string <number type="integer" name="default simple" source="20233108" iso="20233108" target="20233108" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string 05 07 23 string',
            'expected' => 'string 05 07 <number type="integer" name="default simple" source="23" iso="23" target="23" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string 5 7 23 string',
            'expected' => 'string <number type="integer" name="default simple" source="5" iso="5" target="5" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="7" iso="7" target="7" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="23" iso="23" target="23" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string 2023 32 3 string',
            'expected' => 'string <number type="integer" name="default simple" source="2023" iso="2023" target="2023" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="32" iso="32" target="32" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="3" iso="3" target="3" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string 05/07/123 string',
            'expected' => 'string 05/07/123 string',
        ];
        yield [
            'string' => 'string 123/05/07 string',
            'expected' => 'string 123/05/07 string',
        ];
        yield [
            'string' => 'string 35/7/2023 string',
            'expected' => 'string 35/7/2023 string',
        ];
        yield [
            'string' => 'string 35/07/2023 string',
            'expected' => 'string 35/07/2023 string',
        ];
        yield [
            'string' => 'string 2023 12/31 string',
            'expected' => 'string <number type="integer" name="default simple" source="2023" iso="2023" target="2023" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> 12/31 string',
        ];
        yield [
            'string' => 'This is <tag1><number type="integer" name="default simple" source="123" iso="123" target="123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/><tag2>malicious 546.5</tag2>2035</tag1> text',
            'expected' => 'This is <tag1><number type="integer" name="default simple" source="123" iso="123" target="123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/><tag2>malicious <number type="float" name="default generic with dot" source="546.5" iso="546.5" target="546.5" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/></tag2><number type="integer" name="default simple" source="2035" iso="2035" target="2035" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/></tag1> text',
            'useForUnprotectTest' => false,
        ];
        yield [
            'string' => 'string 05.07.123 string',
            'expected' => 'string 05.07.123 string',
        ];
        yield [
            'string' => 'string 05-07-123 string',
            'expected' => 'string 05-07-123 string',
        ];
        yield [
            'string' => 'string 35-7-2023 string',
            'expected' => 'string 35-7-2023 string',
        ];
        yield [
            'string' => 'string 35-07-2023 string',
            'expected' => 'string 35-07-2023 string',
        ];
    }

    public function floatsProvider(): iterable
    {
        yield [
            'string' => 'string 9.012345 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="9.012345" iso="9.012345" target="9.012345" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/> string',
        ];
        yield [
            'string' => 'string 123456789.12345 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="123456789.12345" iso="123456789.12345" target="123456789.12345" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/> string',
        ];
        yield [
            'string' => 'string 0.123 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="0.123" iso="0.123" target="0.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/> string',
        ];
        yield [
            'string' => 'string -0.123 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="-0.123" iso="-0.123" target="-0.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/> string',
        ];
        yield [
            'string' => 'string +0.123 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="+0.123" iso="+0.123" target="+0.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/> string',
        ];
        yield [
            'string' => 'string 1.0 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="1.0" iso="1.0" target="1.0" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaMboAWlNDY3oGD0dayt7xViQJhVNzRoQVROjqalfCgA="/> string',
        ];
        yield [
            'string' => 'string 0,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="0,123" iso="0.123" target="0.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
        yield [
            'string' => 'string -0,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="-0,123" iso="-0.123" target="-0.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
        yield [
            'string' => 'string -12,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="-12,123" iso="-12.123" target="-12.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
        yield [
            'string' => 'string +12,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="+12,123" iso="+12.123" target="+12.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
        yield [
            'string' => 'string 1,0 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="1,0" iso="1.0" target="1.0" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
        yield [
            'string' => 'string 123456789,12345 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="123456789,12345" iso="123456789.12345" target="123456789.12345" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
        yield [
            'string' => 'string 123456789·12345 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="123456789·12345" iso="123456789.12345" target="123456789.12345" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaB7aDqQ1NTSiY/R0rK3sFWNBmlQ0NWtAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string 0·123 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="0·123" iso="0.123" target="0.123" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaB7aDqQ1NTSiY/R0rK3sFWNBmlQ0NWtAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string -0·12345 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="-0·12345" iso="-0.12345" target="-0.12345" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaB7aDqQ1NTSiY/R0rK3sFWNBmlQ0NWtAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string +0·12345 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="+0·12345" iso="+0.12345" target="+0.12345" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMrYGTMakaNcYaB7aDqQ1NTSiY/R0rK3sFWNBmlQ0NWtAVE2MpqZ+KQA="/> string',
        ];

        yield [
            'string' => 'string 1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="1,234,567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWR7PasFYDyDEGMrXAdIxeTIq2poZGdIyejrWVvWIsyAgVTc0aEFUTo6mpXwoA"/> string',
        ];
        yield [
            'string' => 'string -1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="-1,234,567.89" iso="-1234567.89" target="-1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWR7PasFYDyDEGMrXAdIxeTIq2poZGdIyejrWVvWIsyAgVTc0aEFUTo6mpXwoA"/> string',
        ];
        yield [
            'string' => 'string +1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="+1,234,567.89" iso="+1234567.89" target="+1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWR7PasFYDyDEGMrXAdIxeTIq2poZGdIyejrWVvWIsyAgVTc0aEFUTo6mpXwoA"/> string',
        ];
        yield [
            'string' => 'string 1,234,567·89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal middle dot" source="1,234,567·89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWR7PasFYDyDEGMrXA9KHtMSnamhoa0TF6OtZW9oqxICNUNDVrQFRNjKamfikA"/> string',
        ];
        yield [
            'string' => 'string 12,34,567.89 string',
            'expected' => 'string <number type="float" name="default indian" source="12,34,567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jJkVHE0RWG9XqaGqDGMa1mjF6MSnamhoa0TF6OtZW9oqxII0qmpo1IKomRlNTvxQA"/> string',
        ];
        yield [
            'string' => 'string 123,4567.89 string',
            'expected' => 'string <number type="float" name="default chinese" source="123,4567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jJqXaUMekVkez2rAWxAExtcB0jF5MiramhkZ0jJ6OtZW9YixIt4qmZg2IqonR1NQvBQA="/> string',
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with whitespace thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWQbPasFYDyDEGMrXAdIxeTIq2poZGdIyejrWVvWIsyAgVTc0aEFUTo6mpXwoA"/> string',
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with whitespace thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWQbPasFYDyDEGMrXAtE5MiramhkZ0jJ6OtZW9YizIBBVNzRoQVROjqalfCgA="/> string',
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with [THSP] thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6g2pqLayMDAslaz2rBWAyhmjBDRgnD1YlK0NTU0omP0dKyt7BVjQQaqaGrWgKiaGE1N/VIA"/> string',
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with [THSP] thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6g2pqLayMDAslaz2rBWAyhmjBDRAnN1YlK0NTU0omP0dKyt7BVjQeapaGrWgKiaGE1N/VIA"/> string',
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with [NNBSP] thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6g2pqLayMDIrVaz2rBWAyhmjBDRgnD1YlK0NTU0omP0dKyt7BVjQQaqaGrWgKiaGE1N/VIA"/> string',
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with [NNBSP] thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6g2pqLayMDIrVaz2rBWAyhmjBDRAnN1YlK0NTU0omP0dKyt7BVjQeapaGrWgKiaGE1N/VIA"/> string',
        ];

        yield [
            'string' => 'string 1˙234˙567.89 string',
            'expected' => 'string <number type="float" name="default with &quot;˙&quot; thousand decimal dot" source="1˙234˙567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6j29EzNasNaDSDPGMTWAjNi9GJStDU1NKJj9HSsrewVY0GGqGhq1oComhhNTf1SAA=="/> string',
        ];
        yield [
            'string' => 'string 1˙234˙567,89 string',
            'expected' => 'string <number type="float" name="default with &quot;˙&quot; thousand decimal comma" source="1˙234˙567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6j29EzNasNaDSDPGMTWAjN0YlK0NTU0omP0dKyt7BVjQWaoaGrWgKiaGE1N/VIA"/> string',
        ];

        yield [
            'string' => "string 1'234'567.89 string",
            'expected' => 'string <number type="float" name="default with &quot;&#039;&quot; thousand decimal dot" source="1\'234\'567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWXbPasFYDyDEGMrXAdIxeTIq2poZGdIyejrWVvWIsyAgVTc0aEFUTo6mpXwoA"/> string',
        ];
        yield [
            'string' => "string 1'234'567,89 string",
            'expected' => 'string <number type="float" name="default with &quot;&#039;&quot; thousand decimal comma" source="1\'234\'567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6hWXbPasFYDyDEGMrXAtE5MiramhkZ0jJ6OtZW9YizIBBVNzRoQVROjqalfCgA="/> string',
        ];

        yield [
            'string' => 'string 1.234.567,89 string',
            'expected' => 'string <number type="float" name="default with dot thousand decimal comma" source="1.234.567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6g2Rk+z2rBWA8gzBrG1wAydmBRtTQ2N6Bg9HWsre8VYkBkqmpo1IKomRlNTvxQA"/> string',
        ];
        yield [
            'string' => "string 1.234.567'89 string",
            'expected' => 'string <number type="float" name="default with &quot;&#039;&quot; separator" source="1.234.567\'89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx6g2Rk+z2rBWA8gzBrG1wAz1mBRtTQ2N6Bg9HWsre8VYkBkqmpo1IKomRlNTvxQA"/> string',
        ];

        yield [
            'string' => "string 1.23e12 string",
            'expected' => 'string <number type="float" name="default exponent" source="1.23e12" iso="1.23e12" target="1.23e12" regex="09eIKa6Jq4nR0NSISdHS0KmJ0dOMSdGOTnWN1bUHMjQ1NGL0QGpUNDVrNHRgLBBVE6OpqQ8A"/> string',
        ];
        yield [
            'string' => "string 1.13e-15 string",
            'expected' => 'string <number type="float" name="default exponent" source="1.13e-15" iso="1.13e-15" target="1.13e-15" regex="09eIKa6Jq4nR0NSISdHS0KmJ0dOMSdGOTnWN1bUHMjQ1NGL0QGpUNDVrNHRgLBBVE6OpqQ8A"/> string',
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧٫٨٩ string",
            'expected' => 'string <number type="float" name="default arabian" source="١٬٢٣٤٬٥٦٧٫٨٩" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NTQiL658Oaim4tvLrm59Oaym8tvrri5MrbaUMe49uYazWrDWo2YlGowWwvCWB2Tog3UFaMHMkJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];

        yield [
            'string' => 'string 0,0 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="0,0" iso="0.0" target="0.0" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4jGkIa6lrG1oDJmBTtGgNNHSClqamhER2jp2NtZa8YC9KqoqlZA6JqYjQ19UsB"/> string',
        ];
    }

    public function looksLikeFloat(): iterable
    {
        yield [
            'string' => 'string 0567,89 string',
            'expected' => 'string 0567,89 string',
        ];
        yield [
            'string' => 'string 5.67,89.45 string',
            'expected' => 'string 5.67,89.45 string',
        ];
    }

    public function integersProvider(): iterable
    {
        yield [
            'string' => 'string 123456789 string',
            'expected' => 'string <number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string -123456789 string',
            'expected' => 'string <number type="integer" name="default simple" source="-123456789" iso="-123456789" target="-123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string +123456789 string',
            'expected' => 'string <number type="integer" name="default simple" source="+123456789" iso="+123456789" target="+123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];

        yield [
            'string' => 'string 1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with comma separator" source="1,234,567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZDQ0dTA8g2rtXR1ALTmvaaGhrRMXo61lb2irEg3SqamjUgqiZGU1O/FAA="/> string',
        ];
        yield [
            'string' => 'string -1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with comma separator" source="-1,234,567" iso="-1234567" target="-1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZDQ0dTA8g2rtXR1ALTmvaaGhrRMXo61lb2irEg3SqamjUgqiZGU1O/FAA="/> string',
        ];
        yield [
            'string' => 'string +1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with comma separator" source="+1,234,567" iso="+1234567" target="+1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZDQ0dTA8g2rtXR1ALTmvaaGhrRMXo61lb2irEg3SqamjUgqiZGU1O/FAA="/> string',
        ];

        yield [
            'string' => 'string 12,34,567 string',
            'expected' => 'string <number type="integer" name="default indian with comma thousand" source="12,34,567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmpdpAx7BWQ0dT014DyDGqjTHR1AIyjGs1NTSiY/R0rK3sFWNB2lU0NWtAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string 1,1234,4567 string',
            'expected' => 'string <number type="integer" name="default chinese with comma thousand" source="1,1234,4567" iso="112344567" target="112344567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHuFZDR9NeA8g2qY0x1tTSsLex1dEEczU1NKJj9HSsrewVY0FGqGhq1oComhhNTf1SAA=="/> string',
        ];

        yield [
            'string' => 'string 1˙234˙567 string',
            'expected' => 'string <number type="integer" name="default generic with dot above separator" source="1˙234˙567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZD4/RMTQ0gx7gWyNACMzTtNTU0omP0dKyt7BVjQfpVNDVrQFRNjKamfikA"/> string',
        ];

        yield [
            'string' => "string 1'234'567 string",
            'expected' => 'string <number type="integer" name="default generic with apostrophe separator" source="1\'234\'567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZDQ11TA8g2rlXX1ALTmvaaGhrRMXo61lb2irEg3SqamjUgqiZGU1O/FAA="/> string',
        ];

        yield [
            'string' => 'string 1.234.567 string',
            'expected' => 'string <number type="integer" name="default generic with dot" source="1.234.567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZDI0ZPUwPIMa4FMrTADE17TQ2N6Bg9HWsre8VYkH4VTc0aEFUTo6mpXwoA"/> string',
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧ string",
            'expected' => 'string <number type="integer" name="default arabian with separator" source="١٬٢٣٤٬٥٦٧" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSIPrQxRlc71j765sKbi24uvrnk5tKby24uv7ni5srYagMdo9qba+w1sEoaA6U0tXBIaWpoRMfo6Vhb2SvGgixT0dSsAVE1MZqa+qUA"/> string',
        ];
    }

    public function looksLikeIntegers(): iterable
    {
        yield [
            'string' => 'string 0567 string',
            'expected' => 'string 0567 string',
        ];
        yield [
            'string' => 'string 67 89 45 string',
            'expected' => 'string <number type="integer" name="default simple" source="67" iso="67" target="67" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="89" iso="89" target="89" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="45" iso="45" target="45" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
    }

    public function ipsProvider(): iterable
    {
        yield [
            'string' => 'string 127.0.0.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="127.0.0.1" iso="127.0.0.1" target="127.0.0.1" regex="09eIKa6Jq4nR0NTQMDKNNtA1ja3RMALSJrE1hjEpNdGGupaxNZoxKZoaMXoEVGhWG9cCjQGqA5qpoqlZo6EDY4GomhhNTX0A"/> string',
        ];
        yield [
            'string' => 'string 255.255.255.255 string',
            'expected' => 'string <number type="ip-address" name="default" source="255.255.255.255" iso="255.255.255.255" target="255.255.255.255" regex="09eIKa6Jq4nR0NTQMDKNNtA1ja3RMALSJrE1hjEpNdGGupaxNZoxKZoaMXoEVGhWG9cCjQGqA5qpoqlZo6EDY4GomhhNTX0A"/> string',
        ];
        yield [
            'string' => 'string 0.0.0.0 string',
            'expected' => 'string <number type="ip-address" name="default" source="0.0.0.0" iso="0.0.0.0" target="0.0.0.0" regex="09eIKa6Jq4nR0NTQMDKNNtA1ja3RMALSJrE1hjEpNdGGupaxNZoxKZoaMXoEVGhWG9cCjQGqA5qpoqlZo6EDY4GomhhNTX0A"/> string',
        ];
        yield [
            'string' => 'string 1.1.1.1 string',
            'expected' => 'string <number type="ip-address" name="default" source="1.1.1.1" iso="1.1.1.1" target="1.1.1.1" regex="09eIKa6Jq4nR0NTQMDKNNtA1ja3RMALSJrE1hjEpNdGGupaxNZoxKZoaMXoEVGhWG9cCjQGqA5qpoqlZo6EDY4GomhhNTX0A"/> string',
        ];
    }

    public function looksLikeIpAddress(): iterable
    {
        yield [
            'string' => 'string 1.0.0.1.1 string',
            'expected' => 'string 1.0.0.1.1 string',
        ];
        yield [
            'string' => 'string 1.1.1.256 string',
            'expected' => 'string 1.1.1.256 string',
        ];
        yield [
            'string' => 'string 256.0.0.1 string',
            'expected' => 'string 256.0.0.1 string',
        ];
        yield [
            'string' => 'string 1.256.0.0 string',
            'expected' => 'string 1.256.0.0 string',
        ];
        yield [
            'string' => 'string 1.0.256.1 string',
            'expected' => 'string 1.0.256.1 string',
        ];
    }

    public function macsProvider(): iterable
    {
        yield [
            'string' => 'string 01:02:03:04:ab:cd string',
            'expected' => 'string <number type="mac-address" name="default" source="01:02:03:04:ab:cd" iso="01:02:03:04:ab:cd" target="01:02:03:04:ab:cd" regex="09eIKa6Jq4nR0NTQsLeKjraqSMlMzyyxio2tNqrViNa1itXUxJSIMdasNqlFEwSaEKMHMk5FU7NGQwfGAlE1MZqa+gA="/> string',
        ];
        yield [
            'string' => 'string 01-02-03-04-ab-cd string',
            'expected' => 'string <number type="mac-address" name="default" source="01-02-03-04-ab-cd" iso="01-02-03-04-ab-cd" target="01-02-03-04-ab-cd" regex="09eIKa6Jq4nR0NTQsLeKjraqSMlMzyyxio2tNqrViNa1itXUxJSIMdasNqlFEwSaEKMHMk5FU7NGQwfGAlE1MZqa+gA="/> string',
        ];
        yield [
            'string' => 'string 00:00:00:00:00:00 string',
            'expected' => 'string <number type="mac-address" name="default" source="00:00:00:00:00:00" iso="00:00:00:00:00:00" target="00:00:00:00:00:00" regex="09eIKa6Jq4nR0NTQsLeKjraqSMlMzyyxio2tNqrViNa1itXUxJSIMdasNqlFEwSaEKMHMk5FU7NGQwfGAlE1MZqa+gA="/> string',
        ];
        yield [
            'string' => 'string FF:FF:FF:FF:FF:FF string',
            'expected' => 'string <number type="mac-address" name="default" source="FF:FF:FF:FF:FF:FF" iso="FF:FF:FF:FF:FF:FF" target="FF:FF:FF:FF:FF:FF" regex="09eIKa6Jq4nR0NTQsLeKjraqSMlMzyyxio2tNqrViNa1itXUxJSIMdasNqlFEwSaEKMHMk5FU7NGQwfGAlE1MZqa+gA="/> string',
        ];
        yield [
            'string' => 'string FF-11-FF-33-FF-44 string',
            'expected' => 'string <number type="mac-address" name="default" source="FF-11-FF-33-FF-44" iso="FF-11-FF-33-FF-44" target="FF-11-FF-33-FF-44" regex="09eIKa6Jq4nR0NTQsLeKjraqSMlMzyyxio2tNqrViNa1itXUxJSIMdasNqlFEwSaEKMHMk5FU7NGQwfGAlE1MZqa+gA="/> string',
        ];
    }

    public function looksLikeMacAddress(): iterable
    {
        yield [
            'string' => 'string 01-02-03-04-ab-cd-11 string',
            'expected' => 'string 01-02-03-04-ab-cd-11 string',
        ];
        yield [
            'string' => 'string FF:FF-FF:FF-FF:FF string',
            'expected' => 'string FF:FF-FF:FF-FF:FF string',
        ];
        yield [
            'string' => 'string FG:FG:FF:FF:FF:FF string',
            'expected' => 'string FG:FG:FF:FF:FF:FF string',
        ];
        yield [
            'string' => 'string 0H:00:00:00:00:00 string',
            'expected' => 'string 0H:00:00:00:00:00 string',
        ];
        yield [
            'string' => 'string 00:00:00:00:00:0I string',
            'expected' => 'string 00:00:00:00:00:0I string',
        ];
        yield [
            'string' => 'string 00:00:00:00:00 string',
            'expected' => 'string 00:00:00:00:00 string',
        ];
        yield [
            'string' => 'string 00:00:00:00:00:00:00 string',
            'expected' => 'string 00:00:00:00:00:00:00 string',
        ];
        yield [
            'string' => 'string F:FF:FF:FF:FF:FF string',
            'expected' => 'string F:FF:FF:FF:FF:FF string',
        ];
        yield [
            'string' => 'string FF:FF:FF:FF:F:FF string',
            'expected' => 'string FF:FF:FF:FF:F:FF string',
        ];
    }

    public function keepContentProvider(): iterable
    {
        yield [
            'string' => 'string KEEP TEXT string',
            'expected' => 'string <number type="keep-content" name="default" source="KEEP TEXT" iso="KEEP TEXT" target="KEEP TEXT" regex="0/d2dQ1QCHGNCNEHAA=="/> string',
        ];

        yield [
            'string' => '#OVERLAY9_TITLE=In diesem Bereich werfen wir einen Blick in die Zukunft.',
            'expected' => '<number type="keep-content" name="default" source="#OVERLAY9_TITLE=" iso="#OVERLAY9_TITLE=" target="#OVERLAY9_TITLE=" regex="01f2D3MN8nGMjEnRitdw8neJrAnxDPFx1bTVBwA="/>In diesem Bereich werfen wir einen Blick in die Zukunft.',
        ];

        yield [
            'string' => '#OVERLAY123_BODY=In diesem Bereich werfen wir einen Blick in die Zukunft.',
            'expected' => '<number type="keep-content" name="default" source="#OVERLAY123_BODY=" iso="#OVERLAY123_BODY=" target="#OVERLAY123_BODY=" regex="01f2D3MN8nGMjEnRitdw8neJrAnxDPFx1bTVBwA="/>In diesem Bereich werfen wir einen Blick in die Zukunft.',
        ];
    }

    public function replaceContentProvider(): iterable
    {
        yield [
            'string' => 'string REPLACE TEXT string',
            'expected' => 'string <number type="replace-content" name="default" source="REPLACE TEXT" iso="OTHER TEXT:REPLACE TEXT" target="OTHER TEXT" regex="0w9yDfBxdHZVCHGNCNEHAA=="/> string',
        ];
    }

    public function trickyCasesProvider(): iterable
    {
        yield [
            'string' => '1 ... 10 Temperaturklasse 0 ... 10V',
            'expected' => '<number type="integer" name="default simple" source="1" iso="1" target="1" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> ... <number type="integer" name="default simple" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> Temperaturklasse <number type="integer" name="default simple" source="0" iso="0" target="0" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> ... <number type="integer" name="default simple (with units)" source="10" iso="10" target="10" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1FCtObShJqwmN7cmOymzpKa4pqA4syYpsajGNyCxJtdRU0MjOkZPx8raXjEWZJCKpmYNiKqJ0dTULwUA"/>V',
        ];
        yield [
            'string' => 'string 3 3 3 string',
            'expected' => 'string <number type="integer" name="default simple" source="3" iso="3" target="3" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="3" iso="3" target="3" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> <number type="integer" name="default simple" source="3" iso="3" target="3" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string &lt;goba&gt; string',
            'expected' => 'string <number type="keep-content" name="Goba" source="&lt;goba&gt;" iso="&lt;goba&gt;" target="&lt;goba&gt;" regex="04+xSc9PSoyx0wcA"/> string',
            'useForUnprotectTest' => false,
        ];
        yield [
            'string' => 'string &Alpha;12345678&quot; string',
            'expected' => 'string &Alpha;12345678&quot; string',
        ];
        yield [
            'string' => 'string<someTag/>123456789 string',
            'expected' => 'string<someTag/>123456789 string',
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/>string',
            'expected' => 'string <someTag/>123456789<someTag/>string',
        ];
        yield [
            'string' => 'string<someTag/>123456789<someTag/>string',
            'expected' => 'string<someTag/>123456789<someTag/>string',
        ];
        yield [
            'string' => 'string<someTag>123456789</someTag>string',
            'expected' => 'string<someTag>123456789</someTag>string',
        ];
        yield [
            'string' => 'string 123456789<someTag/>string',
            'expected' => 'string 123456789<someTag/>string',
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/> string',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/><someTag/> string',
        ];
        yield [
            'string' => 'string <someTag>123456789</someTag> string',
            'expected' => 'string <someTag><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/></someTag> string',
        ];
        yield [
            'string' => '123456789<someTag/> string',
            'expected' => '<number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/><someTag/> string',
        ];
        yield [
            'string' => '<someTag/>123456789<someTag/> string',
            'expected' => '<someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/><someTag/> string',
        ];
        yield [
            'string' => 'string <someTag/>123456789',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/>',
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/>',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/><someTag/>',
        ];
        yield 'date at the beginning and end of text' => [
            'string' => '2023/18/07 some text with date in it 2023/18/07',
            'expected' => '<number type="date" name="default Y/d/m" source="2023/18/07" iso="2023-07-18" target="2023-07-18" regex="09eIKa6Jq4nR0NSISak2qY3R1zCINtS1jK0Bkkax0QYgpjGQMgSLWMZqIlQYAoWNYMKaGhoxeiDDVDQ1azR0YCwQVROjqakPAA=="/> some text with date in it <number type="date" name="default Y/d/m" source="2023/18/07" iso="2023-07-18" target="2023-07-18" regex="09eIKa6Jq4nR0NSISak2qY3R1zCINtS1jK0Bkkax0QYgpjGQMgSLWMZqIlQYAoWNYMKaGhoxeiDDVDQ1azR0YCwQVROjqakPAA=="/>',
        ];
        yield 'already protected number is safe' => [
            'string' => 'some text with date in it: <number type="date" name="test-default" source="2023/18/07" iso="2023-07-18" target="18.07.23"/>',
            'expected' => 'some text with date in it: <number type="date" name="test-default" source="2023/18/07" iso="2023-07-18" target="18.07.23"/>',
            'useForUnprotectTest' => false,
        ];
        yield [
            'string' => 'string **12345678** string',
            'expected' => 'string **12345678** string',
        ];
        yield [
            'string' => 'string 123456789 mm',
            'expected' => 'string <number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/> mm',
        ];
        yield [
            'string' => "string 123456789 mm \r\n",
            'expected' => "string <number type=\"integer\" name=\"default simple\" source=\"123456789\" iso=\"123456789\" target=\"123456789\" regex=\"09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA\"/> mm \r\n",
        ];
        yield [
            'string' => 'string < 123456789',
            'expected' => 'string < <number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/>',
        ];
        yield [
            'string' => 'string &lt; 123456789',
            'expected' => 'string &lt; <number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/>',
        ];
        yield [
            'string' => 'Busprotokoll und Geräteprofil <div class="close 6570742069643d2234223e266c743b2f74726164656d61726b2e7265662667743b3c2f657074 internal-tag ownttip"><span title="&lt;/trademark.ref&gt;" class="short">&lt;/4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;/trademark.ref&gt;</span></div>4042',
            'expected' => 'Busprotokoll und Geräteprofil <div class="close 6570742069643d2234223e266c743b2f74726164656d61726b2e7265662667743b3c2f657074 internal-tag ownttip"><span title="&lt;/trademark.ref&gt;" class="short">&lt;/4&gt;</span><span data-originalid="4" data-length="-1" class="full">&lt;/trademark.ref&gt;</span></div><number type="integer" name="default simple" source="4042" iso="4042" target="4042" regex="09eIKa6Jq4nR0NSIPrQxRlc71l4j2lDXMjYmRbsmJkVTU0MjOkZPx9rKXjEWpFRFU7MGRNXEaGrqlwIA"/>',
        ];
    }

    private function getNumberFormatRepository(string ...$names): ContentProtectionRepository
    {
        return $this->createConfiguredMock(
            ContentProtectionRepository::class,
            [
                'getAllForSource' => $this->getProtectionDtos(...$names),
                'hasActiveTextRules' => true,
            ]
        );
    }

    private function getProtectionDtos(string ...$names): iterable
    {
        $names = array_map(
            static fn ($name) => str_replace('"', '\"', html_entity_decode($name)),
            $names
        );
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $getAll = function ($select) use ($dbContentRecognition) {
            foreach ($dbContentRecognition->fetchAll($select, order: 'id ASC') as $formatData) {
                $formatData = $formatData->toArray();
                $formatData['outputFormat'] = match ($formatData['type']) {
                    DateProtector::getType() => 'Y-m-d',
                    FloatProtector::getType() => '#.#0',
                    IntegerProtector::getType() => '#',
                    default => null
                };
                $formatData['priority'] = 1;

                yield ContentProtectionDto::fromRow($formatData);
            }
        };

        $selectIps = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "ip-address"')
            ->where('name IN ("' . implode('","', $names) . '")');

        yield from $getAll($selectIps);

        $selectMacs = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "mac-address"')
            ->where('name IN ("' . implode('","', $names) . '")');

        yield from $getAll($selectMacs);

        $selectDates = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "date"')
            ->where('name IN ("' . implode('","', $names) . '")');

        yield from $getAll($selectDates);

        $selectFloats = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "float"')
            ->where('name IN ("' . implode('","', $names) . '")');

        yield from $getAll($selectFloats);

        $selectIntegers = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "integer"')
            ->where('name IN ("' . implode('","', $names) . '")');

        yield from $getAll($selectIntegers);

        yield new ContentProtectionDto(
            'keep-content',
            'default',
            '/KEEP TEXT/',
            0,
            null,
            true,
            null,
            0
        );

        yield new ContentProtectionDto(
            'replace-content',
            'default',
            '/REPLACE TEXT/',
            0,
            'REPLACE TEXT',
            true,
            'OTHER TEXT',
            0
        );

        yield new ContentProtectionDto(
            'keep-content',
            'default',
            '/#OVERLAY\d*_(BODY|TITLE)=/',
            0,
            null,
            true,
            null,
            0
        );

        yield new ContentProtectionDto(
            'keep-content',
            'Goba',
            '/\<goba\>/',
            0,
            null,
            true,
            null,
            0
        );

        yield new ContentProtectionDto(
            'integer',
            'default simple (with units)',
            '/(\s|^|\()([-+]?([1-9]\d+|\d))(%|°|V|mm|kbit|s|psi|bar|MPa|mA)(([\.,:;?!](\s|$))|\s|$|\))/u',
            2,
            '#',
            false,
            '#',
            0
        );
    }
}
