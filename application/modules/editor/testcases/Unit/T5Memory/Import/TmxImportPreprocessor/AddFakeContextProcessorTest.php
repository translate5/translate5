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
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\AddFakeContextProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\BrokenTranslationUnitLogger;
use PHPUnit\Framework\TestCase;

class AddFakeContextProcessorTest extends TestCase
{
    private AddFakeContextProcessor $processor;

    public function setUp(): void
    {
        $this->processor = new AddFakeContextProcessor();
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
    public function testSuccess(string $tu, string $expected): void
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
        yield 'tu with context' => [
            'tu' => <<<TMX
<tu tuid="909" creationdate="20170714T121014Z" creationid="BENJAMIN FRIEDRICHS">
      <prop type="tmgr:segId">11909</prop>
      <prop type="t5:InternalKey">9574:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">none</prop>
      <prop type="tmgr:context">SegmentNr: 14340</prop>
      <tuv xml:lang="de-DE">
        <seg>Neue Außenhülle wie De Luxe</seg>
      </tuv>
      <tuv xml:lang="nl-NL">
        <seg>Nieuwe buitenbeplating als De Luxe</seg>
      </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="909" creationdate="20170714T121014Z" creationid="BENJAMIN FRIEDRICHS">
      <prop type="tmgr:segId">11909</prop>
      <prop type="t5:InternalKey">9574:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">none</prop>
      <prop type="tmgr:context">SegmentNr: 14340</prop>
      <tuv xml:lang="de-DE">
        <seg>Neue Außenhülle wie De Luxe</seg>
      </tuv>
      <tuv xml:lang="nl-NL">
        <seg>Nieuwe buitenbeplating als De Luxe</seg>
      </tuv>
</tu>
TMX,
        ];

        yield 'tu without context' => [
            'tu' => <<<TMX
<tu tuid="921" creationdate="20170714T121013Z" creationid="BENJAMIN FRIEDRICHS">
      <prop type="tmgr:segId">11921</prop>
      <prop type="t5:InternalKey">9586:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">none</prop>
      <tuv xml:lang="de-DE">
        <seg>NEU</seg>
      </tuv>
      <tuv xml:lang="nl-NL">
        <seg>NIEUW</seg>
      </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="921" creationdate="20170714T121013Z" creationid="BENJAMIN FRIEDRICHS">
<prop type="tmgr:context">-</prop>
      <prop type="tmgr:segId">11921</prop>
      <prop type="t5:InternalKey">9586:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">none</prop>
      <tuv xml:lang="de-DE">
        <seg>NEU</seg>
      </tuv>
      <tuv xml:lang="nl-NL">
        <seg>NIEUW</seg>
      </tuv>
</tu>
TMX,
        ];

        yield 'tu without context and docname' => [
            'tu' => <<<TMX
<tu tuid="921" creationdate="20170714T121013Z" creationid="BENJAMIN FRIEDRICHS">
      <prop type="tmgr:segId">11921</prop>
      <prop type="t5:InternalKey">9586:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <tuv xml:lang="de-DE">
        <seg>NEU</seg>
      </tuv>
      <tuv xml:lang="nl-NL">
        <seg>NIEUW</seg>
      </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="921" creationdate="20170714T121013Z" creationid="BENJAMIN FRIEDRICHS">
<prop type="tmgr:context">-</prop>
      <prop type="tmgr:segId">11921</prop>
      <prop type="t5:InternalKey">9586:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <tuv xml:lang="de-DE">
        <seg>NEU</seg>
      </tuv>
      <tuv xml:lang="nl-NL">
        <seg>NIEUW</seg>
      </tuv>
</tu>
TMX,
        ];
    }
}
