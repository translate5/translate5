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

namespace MittagQI\Translate5\Test\Unit\T5Memory\Import\TmxImportPreprocessor;

use editor_Models_Languages;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\RemovePropsFromSegLevelProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\BrokenTranslationUnitLogger;
use PHPUnit\Framework\TestCase;

class RemovePropsFromSegLevelProcessorTest extends TestCase
{
    private RemovePropsFromSegLevelProcessor $processor;

    public function setUp(): void
    {
        $this->processor = RemovePropsFromSegLevelProcessor::create();
    }

    public function testSupports(): void
    {
        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $targetLang = $this->createMock(editor_Models_Languages::class);

        $options = new ImportOptions(
            StripFramingTags::None,
            new TmxFilterOptions(),
        );

        self::assertTrue($this->processor->supports($sourceLang, $targetLang, $options));
    }

    /**
     * @dataProvider casesProvider
     */
    public function testRemovePropsFromSeg(string $tu, string $expected): void
    {
        $lang = $this->createMock(editor_Models_Languages::class);
        $options = new ImportOptions(
            StripFramingTags::None,
            new TmxFilterOptions(),
        );
        $logger = $this->createMock(BrokenTranslationUnitLogger::class);

        $tus = $this->processor->process( // @phpstan-ignore-line
            $tu,
            $lang,
            $lang,
            $options,
            $logger,
        );

        foreach ($tus as $processed) {
            self::assertSame($expected, $processed);
        }
    }

    public function casesProvider(): iterable
    {
        yield 'props inside seg tags are removed' => [
            'tu' => <<<'TMX'
    <tu tuid="1" creationdate="20260127T081449Z" creationid="TRANS LATOR ONE">
      <prop type="tmgr:docname">Word 124.docx</prop>
      <prop type="tmgr:context">-</prop>
      <tuv xml:lang="de">
        <seg><prop type="user-def">segId1</prop>Hallo Welt,</seg>
      </tuv>
      <tuv xml:lang="en">
        <seg><prop type="user-def">segId1</prop>Hello world,</seg>
      </tuv>
    </tu>
TMX,
            'expected' => <<<'TMX'
    <tu tuid="1" creationdate="20260127T081449Z" creationid="TRANS LATOR ONE">
      <prop type="tmgr:docname">Word 124.docx</prop>
      <prop type="tmgr:context">-</prop>
      <tuv xml:lang="de">
        <seg>Hallo Welt,</seg>
      </tuv>
      <tuv xml:lang="en">
        <seg>Hello world,</seg>
      </tuv>
    </tu>
TMX,
        ];

        yield 'multiple props inside seg' => [
            'tu' => <<<'TMX'
    <tu tuid="2">
      <tuv xml:lang="en">
        <seg><prop type="x-id">123</prop><prop type="x-context">test</prop>Some text here</seg>
      </tuv>
    </tu>
TMX,
            'expected' => <<<'TMX'
    <tu tuid="2">
      <tuv xml:lang="en">
        <seg>Some text here</seg>
      </tuv>
    </tu>
TMX,
        ];

        yield 'props at tu level preserved' => [
            'tu' => <<<'TMX'
    <tu tuid="3">
      <prop type="tmgr:docname">Document.docx</prop>
      <tuv xml:lang="en">
        <seg>Text without props in seg</seg>
      </tuv>
    </tu>
TMX,
            'expected' => <<<'TMX'
    <tu tuid="3">
      <prop type="tmgr:docname">Document.docx</prop>
      <tuv xml:lang="en">
        <seg>Text without props in seg</seg>
      </tuv>
    </tu>
TMX,
        ];

        yield 'no props in seg - no changes' => [
            'tu' => <<<'TMX'
    <tu tuid="4">
      <tuv xml:lang="en">
        <seg>Just plain text</seg>
      </tuv>
    </tu>
TMX,
            'expected' => <<<'TMX'
    <tu tuid="4">
      <tuv xml:lang="en">
        <seg>Just plain text</seg>
      </tuv>
    </tu>
TMX,
        ];

        yield 'props with multiline content in seg' => [
            'tu' => <<<'TMX'
    <tu tuid="5">
      <tuv xml:lang="en">
        <seg><prop type="x-long">
          Some
          multiline
          content
        </prop>Actual segment text</seg>
      </tuv>
    </tu>
TMX,
            'expected' => <<<'TMX'
    <tu tuid="5">
      <tuv xml:lang="en">
        <seg>Actual segment text</seg>
      </tuv>
    </tu>
TMX,
        ];
    }
}
