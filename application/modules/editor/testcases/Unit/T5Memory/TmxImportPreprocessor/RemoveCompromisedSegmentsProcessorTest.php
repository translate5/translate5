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
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor\RemoveCompromisedSegmentsProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;
use MittagQI\Translate5\TMX\TransUnitParser;
use MittagQI\Translate5\TMX\TransUnitStructure;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Config;

class RemoveCompromisedSegmentsProcessorTest extends TestCase
{
    private MockObject|TransUnitParser $transUnitParser;

    private MockObject|Zend_Config $config;

    private RemoveCompromisedSegmentsProcessor $processor;

    public function setUp(): void
    {
        $this->transUnitParser = $this->createMock(TransUnitParser::class);
        $this->config = $config = new Zend_Config([
            'runtimeOptions' => [
                'tmxImportProcessor' => [
                    'removeCompromisedSegmentsRegex' => '/problematic/',
                ],
            ],
        ]);

        $this->processor = new RemoveCompromisedSegmentsProcessor(
            $this->config,
            $this->transUnitParser,
        );
    }

    public function testSupports(): void
    {
        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $targetLang = $this->createMock(editor_Models_Languages::class);

        $options = new ImportOptions(StripFramingTags::None, true);

        self::assertTrue($this->processor->supports($sourceLang, $targetLang, $options));
    }

    /**
     * @dataProvider casesProvider
     */
    public function testSuccess(string $tu, TransUnitStructure $structure, bool $valid): void
    {
        $this->transUnitParser->method('extractStructure')->willReturn($structure);

        $lang = $this->createMock(editor_Models_Languages::class);
        $options = new ImportOptions(StripFramingTags::None, false);
        $logger = $this->createMock(BrokenTranslationUnitLogger::class);

        $tus = $this->processor->process( // @phpstan-ignore-line
            $tu,
            $lang,
            $lang,
            $options,
            $logger,
        );

        if ($valid) {
            foreach ($tus as $processed) {
                self::assertSame($tu, $processed);
            }

            return;
        }

        $count = 0;
        foreach ($tus as $processed) {
            $count++;
        }

        self::assertSame(0, $count);
    }

    public function casesProvider(): iterable
    {
        yield 'valid tu' => [
            'tu' => 'valid_tu',
            'structure' => new TransUnitStructure(
                json_encode([
                    'source' => TransUnitStructure::SOURCE_PLACEHOLDER,
                    'target' => TransUnitStructure::TARGET_PLACEHOLDER,
                ]),
                'source',
                'target',
            ),
            'valid' => true,
        ];

        yield 'problematic in source tu' => [
            'tu' => 'problematic_in_source_tu',
            'structure' => new TransUnitStructure(
                json_encode([
                    'source' => TransUnitStructure::SOURCE_PLACEHOLDER,
                    'target' => TransUnitStructure::TARGET_PLACEHOLDER,
                ]),
                'source problematic',
                'target',
            ),
            'valid' => false,
        ];

        yield 'problematic in target tu' => [
            'tu' => 'problematic_in_source_tu',
            'structure' => new TransUnitStructure(
                json_encode([
                    'source' => TransUnitStructure::SOURCE_PLACEHOLDER,
                    'target' => TransUnitStructure::TARGET_PLACEHOLDER,
                ]),
                'source',
                'target problematic',
            ),
            'valid' => false,
        ];
    }
}
