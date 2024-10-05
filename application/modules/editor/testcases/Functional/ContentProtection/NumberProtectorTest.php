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

namespace MittagQI\Translate5\Test\Functional\ContentProtection;

use editor_Models_Languages;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionDto;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\Model\ContentRecognition;
use MittagQI\Translate5\ContentProtection\Model\InputMapping;
use MittagQI\Translate5\ContentProtection\Model\OutputMapping;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\DateProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\FloatProtector;
use MittagQI\Translate5\ContentProtection\NumberProtection\Protector\IntegerProtector;
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
        $protector = NumberProtector::create($this->getNumberFormatRepository());

        self::assertTrue($protector->hasEntityToProtect($node));

        self::assertSame($expected, $protector->protect($node, true, 5, 6));
    }

    public function testProtectRepeatableNumbers(): void
    {
        $protector = NumberProtector::create($this->getNumberFormatRepository());

        self::assertSame(
            'string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string <number type="date" name="default Ymd" source="20231020" iso="2023-10-20" target="2023-10-20" regex="09eIKa6Jq4nR0NSISak2qdUwiDbUtYytMYw20DWK1YRxgaRRLFAIyDQGUoaxmpoaGjF6IM0qmpo1GjowFoiqidHU1AcA"/> string',
            $protector->protect('string 20231020 string 20231020 string', true, 5, 6)
        );
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
            'expected' => 'string <number type="integer" name="default simple" source="31" iso="31" target="31" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="11" iso="11" target="11" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="2023" iso="2023" target="2023" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string 20233108 string',
            'expected' => 'string <number type="integer" name="default simple" source="20233108" iso="20233108" target="20233108" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string 05 07 23 string',
            'expected' => 'string 05 07 <number type="integer" name="default simple" source="23" iso="23" target="23" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string 5 7 23 string',
            'expected' => 'string <number type="integer" name="default simple" source="5" iso="5" target="5" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="7" iso="7" target="7" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="23" iso="23" target="23" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string 2023 32 3 string',
            'expected' => 'string <number type="integer" name="default simple" source="2023" iso="2023" target="2023" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="32" iso="32" target="32" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="3" iso="3" target="3" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
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
            'expected' => 'string <number type="integer" name="default simple" source="2023" iso="2023" target="2023" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> 12/31 string',
        ];
        yield [
            'string' => 'This is <tag1><number type="integer" name="default simple" source="123" iso="123" target="123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/><tag2>malicious 546.5</tag2>2035</tag1> text',
            'expected' => 'This is <tag1><number type="integer" name="default simple" source="123" iso="123" target="123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/><tag2>malicious <number type="float" name="default generic with dot" source="546.5" iso="546.5" target="546.5" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/></tag2><number type="integer" name="default simple" source="2035" iso="2035" target="2035" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/></tag1> text',
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
            'expected' => 'string <number type="float" name="default generic with dot" source="9.012345" iso="9.012345" target="9.012345" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/> string',
        ];
        yield [
            'string' => 'string 123456789.12345 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="123456789.12345" iso="123456789.12345" target="123456789.12345" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/> string',
        ];
        yield [
            'string' => 'string 0.123 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="0.123" iso="0.123" target="0.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/> string',
        ];
        yield [
            'string' => 'string -0.123 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="-0.123" iso="-0.123" target="-0.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/> string',
        ];
        yield [
            'string' => 'string +0.123 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="+0.123" iso="+0.123" target="+0.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/> string',
        ];
        yield [
            'string' => 'string 1.0 string',
            'expected' => 'string <number type="float" name="default generic with dot" source="1.0" iso="1.0" target="1.0" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjF6QFpTQyNGD6RURVOzRkMHxgJRNTGamvqlAA=="/> string',
        ];
        yield [
            'string' => 'string 0,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="0,123" iso="0.123" target="0.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];
        yield [
            'string' => 'string -0,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="-0,123" iso="-0.123" target="-0.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];
        yield [
            'string' => 'string -12,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="-12,123" iso="-12.123" target="-12.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];
        yield [
            'string' => 'string +12,123 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="+12,123" iso="+12.123" target="+12.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];
        yield [
            'string' => 'string 1,0 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="1,0" iso="1.0" target="1.0" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];
        yield [
            'string' => 'string 123456789,12345 string',
            'expected' => 'string <number type="float" name="default generic with comma" source="123456789,12345" iso="123456789.12345" target="123456789.12345" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmjpASlNDI0YPpFJFU7NGQwfGAlE1MZqa+qUA"/> string',
        ];
        yield [
            'string' => 'string 123456789·12345 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="123456789·12345" iso="123456789.12345" target="123456789.12345" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmoe2A2lNDY0YPZBSFU3NGg0dGAtE1cRoauqXAgA="/> string',
        ];
        yield [
            'string' => 'string 0·123 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="0·123" iso="0.123" target="0.123" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmoe2A2lNDY0YPZBSFU3NGg0dGAtE1cRoauqXAgA="/> string',
        ];
        yield [
            'string' => 'string -0·12345 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="-0·12345" iso="-0.12345" target="-0.12345" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmoe2A2lNDY0YPZBSFU3NGg0dGAtE1cRoauqXAgA="/> string',
        ];
        yield [
            'string' => 'string +0·12345 string',
            'expected' => 'string <number type="float" name="default generic with middle dot" source="+0·12345" iso="+0.12345" target="+0.12345" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jK0BkzEp2jUGmoe2A2lNDY0YPZBSFU3NGg0dGAtE1cRoauqXAgA="/> string',
        ];

        yield [
            'string' => 'string 1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="1,234,567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/> string',
        ];
        yield [
            'string' => 'string -1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="-1,234,567.89" iso="-1234567.89" target="-1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/> string',
        ];
        yield [
            'string' => 'string +1,234,567.89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal dot" source="+1,234,567.89" iso="+1234567.89" target="+1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/> string',
        ];
        yield [
            'string' => 'string 1,234,567·89 string',
            'expected' => 'string <number type="float" name="default with comma thousand decimal middle dot" source="1,234,567·89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarV0aw2rNUAcoyBTC0wfWh7TIq2poZGjB5Io4qmZo2GDowFompiNDX1SwE="/> string',
        ];
        yield [
            'string' => 'string 12,34,567.89 string',
            'expected' => 'string <number type="float" name="default indian" source="12,34,567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeISdHRBJHVRrU6mtoghnGtZoxeTIq2poZGjB5IuYqmZo2GDowFompiNDX1SwE="/> string',
        ];
        yield [
            'string' => 'string 123,4567.89 string',
            'expected' => 'string <number type="float" name="default chinese" source="123,4567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeISak21DGp1dGsNqwFcUBMLTAdoxeToq2poRGjB9KjoqlZo6EDY4GomhhNTf1SAA=="/> string',
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with whitespace thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMapV0Kw2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/> string',
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with whitespace thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMapV0Kw2rNUAcoyBTC0wrROToq2poRGjB9KnoqlZo6EDY4GomhhNTf1SAA=="/> string',
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with [THSP] thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMaqNqag2MjCwrNWsNqzVAIoZI0S0IFy9mBRtTQ2NGD2QMSqamjUaOjAWiKqJ0dTULwUA"/> string',
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with [THSP] thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMaqNqag2MjCwrNWsNqzVAIoZI0S0wFydmBRtTQ2NGD2QKSqamjUaOjAWmNIvBQA="/> string',
        ];

        yield [
            'string' => 'string 1 234 567.89 string',
            'expected' => 'string <number type="float" name="default with [NNBSP] thousand decimal dot" source="1 234 567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMaqNqag2MjByq9WsNqzVAIoZI0S0IFy9mBRtTQ2NGD2QMSqamjUaOjAWiKqJ0dTULwUA"/> string',
        ];
        yield [
            'string' => 'string 1 234 567,89 string',
            'expected' => 'string <number type="float" name="default with [NNBSP] thousand decimal comma" source="1 234 567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMaqNqag2MjByq9WsNqzVAIoZI0S0wFydmBRtTQ2NGD2QKSqamjUaOjAWiKqJ0dTULwUA"/> string',
        ];

        yield [
            'string' => 'string 1˙234˙567.89 string',
            'expected' => 'string <number type="float" name="default with &quot;˙&quot; thousand decimal dot" source="1˙234˙567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMao9PVOz2rBWA8gzBrG1wIwYvZgUbU0NjRg9kFYVTc0aDR0YC0TVxGhq6pcCAA=="/> string',
        ];
        yield [
            'string' => 'string 1˙234˙567,89 string',
            'expected' => 'string <number type="float" name="default with &quot;˙&quot; thousand decimal comma" source="1˙234˙567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMao9PVOz2rBWA8gzBrG1wAydmBRtTQ2NGD2QThVNzRoNHRgLRNXEaGrqlwIA"/> string',
        ];

        yield [
            'string' => "string 1'234'567.89 string",
            'expected' => 'string <number type="float" name="default with &quot;\'&quot; thousand decimal dot" source="1\'234\'567.89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMapV16w2rNUAcoyBTC0wHaMXk6KtqaERowfSqKKpWaOhA2OBqJoYTU39UgA="/> string',
        ];
        yield [
            'string' => "string 1'234'567,89 string",
            'expected' => 'string <number type="float" name="default with &quot;\'&quot; thousand decimal comma" source="1\'234\'567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMapV16w2rNUAcoyBTC0wrROToq2poRGjB9KnoqlZo6EDY4GomhhNTf1SAA=="/> string',
        ];

        yield [
            'string' => 'string 1.234.567,89 string',
            'expected' => 'string <number type="float" name="default with dot thousand decimal comma" source="1.234.567,89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMaqN0dOsNqzVAPKMQWwtMEMnJkVbU0MjRg+kU0VTs0ZDB8YCUTUxmpr6pQA="/> string',
        ];
        yield [
            'string' => "string 1.234.567'89 string",
            'expected' => 'string <number type="float" name="default with &quot;\'&quot; separator" source="1.234.567\'89" iso="1234567.89" target="1234567.89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMaqN0dOsNqzVAPKMQWwtMEM9JkVbU0MjRg+kU0VTs0ZDB8YCUTUxmpr6pQA="/> string',
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
            'expected' => 'string <number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string -123456789 string',
            'expected' => 'string <number type="integer" name="default simple" source="-123456789" iso="-123456789" target="-123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];
        yield [
            'string' => 'string +123456789 string',
            'expected' => 'string <number type="integer" name="default simple" source="+123456789" iso="+123456789" target="+123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
        ];

        yield [
            'string' => 'string 1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with comma separator" source="1,234,567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSI1tWOtY821LWMjUmpNtAxqtXQ0dQAMo1rY4w1tcAMTQ2NGD2QDhVNzRoNHRgLRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string -1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with comma separator" source="-1,234,567" iso="-1234567" target="-1234567" regex="09eIKa6Jq4nR0NSI1tWOtY821LWMjUmpNtAxqtXQ0dQAMo1rY4w1tcAMTQ2NGD2QDhVNzRoNHRgLRNXEaGrqlwIA"/> string',
        ];
        yield [
            'string' => 'string +1,234,567 string',
            'expected' => 'string <number type="integer" name="default generic with comma separator" source="+1,234,567" iso="+1234567" target="+1234567" regex="09eIKa6Jq4nR0NSI1tWOtY821LWMjUmpNtAxqtXQ0dQAMo1rY4w1tcAMTQ2NGD2QDhVNzRoNHRgLRNXEaGrqlwIA"/> string',
        ];

        yield [
            'string' => 'string 12,34,567 string',
            'expected' => 'string <number type="integer" name="default indian with comma thousand" source="12,34,567" iso="1234567" target="1234567" regex="NYsxFoAgDMUu4/C/FhF1ceIgFCdv4HOyvbswOCUZEqG3naYgSphqRknhqHq9iySHkBktVtedY5PNCejcp4E0yG8dpmR8Pg=="/> string',
        ];
        yield [
            'string' => 'string 1,1234,4567 string',
            'expected' => 'string <number type="integer" name="default chinese with comma thousand" source="1,1234,4567" iso="112344567" target="112344567" regex="09eIKa6Jq4nR0NSI1tWOtY821LWMjUmpNtAxrtXQ0bTXALJNamOMNbU07G1sdTTBXE0NjRg9kEYVTc0aDR0YC0TVxGhq6pcCAA=="/> string',
        ];

        yield [
            'string' => 'string 1˙234˙567 string',
            'expected' => 'string <number type="integer" name="default generic with dot above separator" source="1˙234˙567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSI1tWOtY821LWMjUmpNtAxqtU4PVNTA8g2ro0x1tQCMzQ1NGL0QFpUNDVrNHRgLBBVE6OpqV8KAA=="/> string',
        ];

        yield [
            'string' => "string 1'234'567 string",
            'expected' => 'string <number type="integer" name="default generic with apostrophe separator" source="1\'234\'567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSI1tWOtY821LWMjUmpNtAxqtVQ19QAMo1rY4w1tcAMTQ2NGD2QDhVNzRoNHRgLRNXEaGrqlwIA"/> string',
        ];

        yield [
            'string' => 'string 1.234.567 string',
            'expected' => 'string <number type="integer" name="default generic with dot" source="1.234.567" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1JqTbQMarViNHT1LTXAPKMa2NMNLXADE0NoDBIl4qmZo2GDowFompiNDX1AQ=="/> string',
        ];

        yield [
            'string' => "string ١٬٢٣٤٬٥٦٧ string",
            'expected' => 'string <number type="integer" name="default arabian with separator" source="١٬٢٣٤٬٥٦٧" iso="1234567" target="1234567" regex="09eIKa6Jq4nR0NSI1tWOtY++ufDmopuLby65ufTmspvLb664uTK22kDHqPbmGnsNrJLGQClNLRxSmhoaMXogK1Q0NWs0dGAsEFUTo6mpXwoA"/> string',
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
            'expected' => 'string <number type="integer" name="default simple" source="67" iso="67" target="67" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="89" iso="89" target="89" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> <number type="integer" name="default simple" source="45" iso="45" target="45" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/> string',
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
            'string' => 'string &lt;goba&gt; string',
            'expected' => 'string <number type="keep-content" name="Goba" source="&lt;goba&gt;" iso="&lt;goba&gt;" target="&lt;goba&gt;" regex="04+xSc9PSoyx0wcA"/> string',
            'useForUnprotectTest' => false,
        ];
        yield [
            'string' => 'string &Alpha;123456789&quot; string',
            'expected' => 'string &Alpha;123456789&quot; string',
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
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/><someTag/> string',
        ];
        yield [
            'string' => 'string <someTag>123456789</someTag> string',
            'expected' => 'string <someTag><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/></someTag> string',
        ];
        yield [
            'string' => '123456789<someTag/> string',
            'expected' => '<number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/><someTag/> string',
        ];
        yield [
            'string' => '<someTag/>123456789<someTag/> string',
            'expected' => '<someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/><someTag/> string',
        ];
        yield [
            'string' => 'string <someTag/>123456789',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/>',
        ];
        yield [
            'string' => 'string <someTag/>123456789<someTag/>',
            'expected' => 'string <someTag/><number type="integer" name="default simple" source="123456789" iso="123456789" target="123456789" regex="09eIKa6Jq4nR0NSI1tWOtdeINtS1jI1J0a6JSdHU1NCI0QMpUNHUrNHQgbFAVE2MpqZ+KQA="/><someTag/>',
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
    }

    private function getNumberFormatRepository(): ContentProtectionRepository
    {
        return $this->createConfiguredMock(
            ContentProtectionRepository::class,
            [
                'getAllForSource' => $this->getProtectionDtos(),
                'hasActiveTextRules' => true,
            ]
        );
    }

    private function getProtectionDtos(): iterable
    {
        $dbContentRecognition = ZfExtended_Factory::get(ContentRecognition::class)->db;
        $contentRecognitionTable = $dbContentRecognition->info($dbContentRecognition::NAME);

        $getAll = function ($select) use ($dbContentRecognition) {
            foreach ($dbContentRecognition->fetchAll($select) as $formatData) {
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
            ->where('type = "ip-address"');

        yield from $getAll($selectIps);

        $selectMacs = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "mac-address"');

        yield from $getAll($selectMacs);

        $selectDates = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "date"');

        yield from $getAll($selectDates);

        $selectFloats = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "float"');

        yield from $getAll($selectFloats);

        $selectIntegers = $dbContentRecognition->select()
            ->from([
                'recognition' => $contentRecognitionTable,
            ], ['recognition.*'])
            ->where('isDefault = true')
            ->where('type = "integer"');

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
    }
}
