<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Test\Integration\T5Memory;

use editor_Models_Languages as Language;
use MittagQI\Translate5\Repository\LanguageRepository;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;
use PHPUnit\Framework\TestCase;

class TmxImportPreprocessorTest extends TestCase
{
    public function testProcessWithEmptyProcessorList(): void
    {
        $languageRepository = $this->createMock(LanguageRepository::class);
        $logger = $this->createMock(\ZfExtended_Logger::class);

        $processor = new TmxImportPreprocessor(
            $languageRepository,
            [],
            $logger,
        );

        $filename = 'small.tmx';

        $options = new ImportOptions(StripFramingTags::None, true);

        self::assertSame($filename, $processor->process($filename, 1, 2, $options));
    }

    public function testConvertTMXForImport(): void
    {
        $languageRepository = $this->createMock(LanguageRepository::class);
        $logger = $this->createMock(\ZfExtended_Logger::class);

        $languageRepository
            ->method('find')
            ->willReturn(new Language());

        $tu1 = <<<TU
<tu tuid="1" creationdate="20240430T152704Z" creationid="[ONEWORD GMBH]">
    <prop type="tmgr:segId">1</prop>
    <prop type="t5:InternalKey">7:1</prop>
    <prop type="tmgr:markup">OTMXUXLF</prop>
    <prop type="tmgr:docname">none</prop>
    <tuv xml:lang="de-DE">
        <seg>eins zwei</seg>
    </tuv>
    <tuv xml:lang="en-US">
        <seg>one two</seg>
    </tuv>
</tu>
TU;

        $processor1 = new class($tu1) extends TmxImportPreprocessor\Processor {
            public function __construct(
                private string $tu1,
            ) {
            }

            protected function processTu(
                string $tu,
                Language $sourceLang,
                Language $targetLang,
                ImportOptions $importOptions,
                BrokenTranslationUnitLogger $brokenTranslationUnitIndicator,
            ): iterable {
                return [$this->tu1];
            }

            public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool
            {
                return true;
            }

            public function order(): int
            {
                return 1;
            }
        };

        $processor2 = new class($tu1) extends TmxImportPreprocessor\Processor {
            public function __construct(
                private string $tu1,
            ) {
            }

            protected function processTu(
                string $tu,
                Language $sourceLang,
                Language $targetLang,
                ImportOptions $importOptions,
                BrokenTranslationUnitLogger $brokenTranslationUnitIndicator,
            ): iterable {
                TestCase::assertSame($this->tu1, $tu);

                foreach (['one', 'two'] as $number) {
                    yield <<<TU
<tu tuid="1" creationdate="20240430T152704Z" creationid="[ONEWORD GMBH]">
    <prop type="tmgr:segId">1</prop>
    <prop type="t5:InternalKey">7:1</prop>
    <prop type="tmgr:markup">OTMXUXLF</prop>
    <prop type="tmgr:docname">none</prop>
    <tuv xml:lang="de-DE">
        <seg>number</seg>
    </tuv>
    <tuv xml:lang="en-US">
        <seg>$number</seg>
    </tuv>
</tu>
TU;
                }
            }

            public function supports(Language $sourceLang, Language $targetLang, ImportOptions $importOptions): bool
            {
                return true;
            }

            public function order(): int
            {
                return 2;
            }
        };

        $service = new TmxImportPreprocessor(
            $languageRepository,
            [
                $processor1,
                $processor2,
            ],
            $logger,
        );

        $options = new ImportOptions(StripFramingTags::None, true);

        $file = $service->process(__DIR__ . '/TmConversionServiceTest/small.tmx', 1, 2, $options);

        self::assertFileEquals(__DIR__ . '/TmConversionServiceTest/expected_small.tmx', $file);

        unlink($file); // Clean up the temporary file created during the test
    }
}
