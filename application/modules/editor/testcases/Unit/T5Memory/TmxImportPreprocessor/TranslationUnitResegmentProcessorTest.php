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

namespace MittagQI\Translate5\Test\Unit\T5Memory\TmxImportPreprocessor;

use editor_Models_Languages;
use MittagQI\Translate5\Segment\SegmentationService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor\TranslationUnitResegmentProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;
use MittagQI\Translate5\TMX\TransUnitParser;
use MittagQI\Translate5\TMX\TransUnitStructure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;

class TranslationUnitResegmentProcessorTest extends TestCase
{
    private MockObject|SegmentationService $segmentationService;

    private MockObject|TransUnitParser $transUnitParser;

    private MockObject|Zend_Config $config;

    private TranslationUnitResegmentProcessor $processor;

    public function setUp(): void
    {
        $this->segmentationService = $this->createMock(SegmentationService::class);
        $this->transUnitParser = $this->createMock(TransUnitParser::class);
        $this->config = $this->createMock(Zend_Config::class);

        $this->processor = new TranslationUnitResegmentProcessor(
            $this->segmentationService,
            $this->config,
            $this->transUnitParser,
        );
    }

    public function trueFalseProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider trueFalseProvider
     */
    public function testSupports(bool $supports): void
    {
        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $targetLang = $this->createMock(editor_Models_Languages::class);

        $options = new ImportOptions(StripFramingTags::None, $supports);

        self::assertSame(
            $supports,
            $this->processor->supports($sourceLang, $targetLang, $options)
        );
    }

    public function testSuccess(): void
    {
        $this->transUnitParser->method('extractStructure')
            ->willReturn(new TransUnitStructure(
                json_encode([
                    'source' => TransUnitStructure::SOURCE_PLACEHOLDER,
                    'target' => TransUnitStructure::TARGET_PLACEHOLDER,
                ]),
                'source',
                'target',
            ));

        $this->segmentationService->method('splitTextToSegments')
            ->willReturnOnConsecutiveCalls(
                ['converted_source_1', 'converted_source_2'],
                ['converted_target_1', 'converted_target_2'],
            );

        $lang = $this->createMock(editor_Models_Languages::class);
        $lang->method('__call')->willReturnMap([
            ['getRfc5646', [], 'en'],
        ]);

        $options = new ImportOptions(StripFramingTags::None, true);

        $tus = $this->processor->process( // @phpstan-ignore-line
            'tu',
            $lang,
            $lang,
            $options,
            BrokenTranslationUnitLogger::create(
                $this->createMock(\ZfExtended_Logger::class)
            )
        );

        $i = 1;

        foreach ($tus as $tu) {
            self::assertSame(
                json_encode([
                    'source' => 'converted_source_' . $i,
                    'target' => 'converted_target_' . $i,
                ]),
                $tu
            );

            $i++;
        }
    }
}
