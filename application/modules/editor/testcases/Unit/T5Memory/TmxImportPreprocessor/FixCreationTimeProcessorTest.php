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
use MittagQI\Translate5\T5Memory\TmxImportPreprocessor\FixCreationTimeProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger;
use PHPUnit\Framework\TestCase;

class FixCreationTimeProcessorTest extends TestCase
{
    private FixCreationTimeProcessor $processor;

    public function setUp(): void
    {
        $this->processor = new FixCreationTimeProcessor();
    }

    public function testSupports(): void
    {
        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $targetLang = $this->createMock(editor_Models_Languages::class);

        $options = new ImportOptions(StripFramingTags::None, true, false);

        self::assertTrue($this->processor->supports($sourceLang, $targetLang, $options));
    }

    /**
     * @dataProvider casesProvider
     */
    public function testSuccess(string $tu, string $expected): void
    {
        $lang = $this->createMock(editor_Models_Languages::class);
        $options = new ImportOptions(StripFramingTags::None, false, false);
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
        yield 'tu with date before unix time' => [
            'tu' => <<<TU
    <tu creationdate="19000101T000000Z" creationid="testuser" changedate="19000101T000000Z" changeid="testuser" lastusagedate="19000101T000000Z">
      <prop type="x-LastUsedBy">testuser</prop>
      <prop type="x-Context">3724826454458105209, 8956964337464956188</prop>
      <prop type="x-ContextContent">some text | text | some other text</prop>
      <prop type="x-Origin">TM</prop>
      <tuv xml:lang="de-AT">
        <seg>Hallo welt</seg>
      </tuv>
      <tuv xml:lang="en-GB">
        <seg>Hello world</seg>
      </tuv>
    </tu>
TU,
            'expected' => <<<TU
    <tu creationdate="19800101T010000Z" creationid="testuser" changeid="testuser">
      <prop type="x-LastUsedBy">testuser</prop>
      <prop type="x-Context">3724826454458105209, 8956964337464956188</prop>
      <prop type="x-ContextContent">some text | text | some other text</prop>
      <prop type="x-Origin">TM</prop>
      <tuv xml:lang="de-AT">
        <seg>Hallo welt</seg>
      </tuv>
      <tuv xml:lang="en-GB">
        <seg>Hello world</seg>
      </tuv>
    </tu>
TU,
        ];

        yield 'valid unix time' => [
            'tu' => <<<TU
    <tu creationdate="20140101T000000Z" creationid="testuser" changedate="19000101T000000Z" changeid="testuser" lastusagedate="19000101T000000Z">
      <tuv xml:lang="de-AT">
        <seg>Hallo welt</seg>
      </tuv>
      <tuv xml:lang="en-GB">
        <seg>Hello world</seg>
      </tuv>
    </tu>
TU,
            'expected' => <<<TU
    <tu creationdate="20140101T000000Z" creationid="testuser" changeid="testuser">
      <tuv xml:lang="de-AT">
        <seg>Hallo welt</seg>
      </tuv>
      <tuv xml:lang="en-GB">
        <seg>Hello world</seg>
      </tuv>
    </tu>
TU,
        ];
    }
}
