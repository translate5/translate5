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
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Import\TmxImportPreprocessor\TransformTuProcessor;
use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\Contract\BrokenTranslationUnitLoggerInterface;
use PHPUnit\Framework\TestCase;

class TransformTuProcessorTest extends TestCase
{
    public function testSupports(): void
    {
        $config = new \Zend_Config([
            'tmxImportProcessor' => [
                'transformTusMapping' => [
                    'test' => 'test',
                ],
            ],
        ]);

        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $targetLang = $this->createMock(editor_Models_Languages::class);

        $options = new ImportOptions(
            StripFramingTags::None,
            new TmxFilterOptions(),
        );

        $processor = new TransformTuProcessor(
            $config,
            $this->createMock(CustomerRepository::class),
        );

        self::assertTrue($processor->supports($sourceLang, $targetLang, $options));
    }

    public function testTuWithPropsButNoMappingIsCollectedAsProblematic(): void
    {
        $config = new \Zend_Config([
            'runtimeOptions' => [
                'tmxImportProcessor' => [],
            ],
        ]);

        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $sourceLang->method('__call')->willReturn('de');

        $targetLang = $this->createMock(editor_Models_Languages::class);
        $targetLang->method('__call')->willReturn('en-gb');

        $options = new ImportOptions(
            StripFramingTags::None,
            new TmxFilterOptions(),
        );

        $logger = $this->createMock(BrokenTranslationUnitLoggerInterface::class);
        $logger->expects($this->once())
            ->method('collectProblematicTU')
            ->with(
                'E1779',
                $this->stringContains('<prop type="created_by">DavidMckeown</prop>')
            );

        $customerRepository = $this->createMock(CustomerRepository::class);

        $processor = new TransformTuProcessor(
            $config,
            $customerRepository,
        );

        $tu = <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm">
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb" creationdate="20180821T131628Z" changedate="20180821T131628Z">
    <prop type="project">12153555</prop>
    <prop type="created_by">DavidMckeown</prop>
    <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX;

        $tus = $processor->process( // @phpstan-ignore-line
            $tu,
            $sourceLang,
            $targetLang,
            $options,
            $logger,
        );

        // Should yield no TUs since it's collected as problematic
        $result = iterator_to_array($tus);
        self::assertEmpty($result);
    }

    public function testTuWithoutPropsAndNoMappingIsPassedThrough(): void
    {
        $config = new \Zend_Config([
            'runtimeOptions' => [
                'tmxImportProcessor' => [],
            ],
        ]);

        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $sourceLang->method('__call')->willReturn('de');

        $targetLang = $this->createMock(editor_Models_Languages::class);
        $targetLang->method('__call')->willReturn('en-gb');

        $options = new ImportOptions(
            StripFramingTags::None,
            new TmxFilterOptions(),
        );

        $logger = $this->createMock(BrokenTranslationUnitLoggerInterface::class);
        $logger->expects($this->never())
            ->method('collectProblematicTU');

        $customerRepository = $this->createMock(CustomerRepository::class);

        $processor = new TransformTuProcessor(
            $config,
            $customerRepository,
        );

        $tu = <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm">
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb">
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX;

        $tus = $processor->process( // @phpstan-ignore-line
            $tu,
            $sourceLang,
            $targetLang,
            $options,
            $logger,
        );

        // Should yield the TU unchanged
        $result = iterator_to_array($tus);
        self::assertCount(1, $result);
        self::assertSame($tu, $result[0]);
    }

    /**
     * @dataProvider casesProvider
     */
    public function testSuccess(array $transformTusMapping, string $tu, string $expected): void
    {
        $config = new \Zend_Config([
            'runtimeOptions' => [
                'tmxImportProcessor' => [
                    'transformTusMapping' => $transformTusMapping,
                ],
            ],
        ]);

        $sourceLang = $this->createMock(editor_Models_Languages::class);
        $sourceLang->method('__call')->willReturn('de');

        $targetLang = $this->createMock(editor_Models_Languages::class);
        $targetLang->method('__call')->willReturn('en-gb');

        $options = new ImportOptions(
            StripFramingTags::None,
            new TmxFilterOptions(),
        );
        $logger = $this->createMock(BrokenTranslationUnitLoggerInterface::class);

        $customerRepository = $this->createMock(CustomerRepository::class);

        $processor = new TransformTuProcessor(
            $config,
            $customerRepository,
        );

        $tus = $processor->process( // @phpstan-ignore-line
            $tu,
            $sourceLang,
            $targetLang,
            $options,
            $logger,
        );

        foreach ($tus as $processed) {
            self::assertSame($expected, $processed);
        }
    }

    public function casesProvider(): iterable
    {
        yield 'standard TU' => [
            'transformTusMapping' => [
                'field' => 'toField',
            ],
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
      <tuv xml:lang="de-de">
        <seg>Neue Außenhülle wie De Luxe</seg>
      </tuv>
      <tuv xml:lang="nl-nl">
        <seg>Nieuwe buitenbeplating als De Luxe</seg>
      </tuv>
</tu>
TMX,
        ];

        yield 'use all mappings' => [
            'transformTusMapping' => [
                'creationDate' => '//tuv[@xml:lang="en-gb"]/@creationdate',
                'author' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="created_by"]',
                'document' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="filename"]',
            ],
            'tu' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm">
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb" creationdate="20180821T131628Z" changedate="20180821T131628Z">
    <prop type="project">12153555</prop>
    <prop type="reviewed">false</prop>
    <prop type="aligned">false</prop>
    <prop type="created_by">DavidMckeown</prop>
    <prop type="modified_by">DavidMckeown</prop>
    <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationdate="20180821T131628Z" creationid="DAVIDMCKEOWN">
  <prop type="tmgr:docname">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
<tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb">
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
        ];

        yield 'author is already set' => [
            'transformTusMapping' => [
                'creationDate' => '//tuv[@xml:lang="en-gb"]/@creationdate',
                'author' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="created_by"]',
                'document' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="filename"]',
            ],
            'tu' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationid="TEST">
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb" creationdate="20180821T131628Z" changedate="20180821T131628Z">
    <prop type="project">12153555</prop>
    <prop type="reviewed">false</prop>
    <prop type="aligned">false</prop>
    <prop type="created_by">DavidMckeown</prop>
    <prop type="modified_by">DavidMckeown</prop>
    <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationid="TEST" creationdate="20180821T131628Z">
  <prop type="tmgr:docname">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
<tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb">
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
        ];

        yield 'creation date is already set' => [
            'transformTusMapping' => [
                'creationDate' => '//tuv[@xml:lang="en-gb"]/@creationdate',
                'author' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="created_by"]',
                'document' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="filename"]',
            ],
            'tu' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationdate="20100101T101010Z">
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb" creationdate="20180821T131628Z" changedate="20180821T131628Z">
    <prop type="project">12153555</prop>
    <prop type="reviewed">false</prop>
    <prop type="aligned">false</prop>
    <prop type="created_by">DavidMckeown</prop>
    <prop type="modified_by">DavidMckeown</prop>
    <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationdate="20100101T101010Z" creationid="DAVIDMCKEOWN">
  <prop type="tmgr:docname">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
<tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb">
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
        ];

        yield 'docname is already set' => [
            'transformTusMapping' => [
                'creationDate' => '//tuv[@xml:lang="en-gb"]/@creationdate',
                'author' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="created_by"]',
                'document' => '//tu/tuv[@xml:lang="en-gb"]/prop[@type="filename"]',
            ],
            'tu' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm">
  <prop type="tmgr:docname">test.txt</prop>
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-GB" creationdate="20180821T131628Z" changedate="20180821T131628Z">
    <prop type="project">12153555</prop>
    <prop type="reviewed">false</prop>
    <prop type="aligned">false</prop>
    <prop type="created_by">DavidMckeown</prop>
    <prop type="modified_by">DavidMckeown</prop>
    <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationdate="20180821T131628Z" creationid="DAVIDMCKEOWN">
  <prop type="tmgr:docname">test.txt</prop>
  <tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb">
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
        ];

        yield 'use all mappings with placeholders in path' => [
            'transformTusMapping' => [
                'creationDate' => '//tuv[@xml:lang="{targetLang}"]/@creationdate',
                'author' => '//tu/tuv[@xml:lang="{sourceLang}"]/prop[@type="created_by"]',
                'document' => '//tu/tuv[@xml:lang="{targetLang}"]/prop[@type="filename"]',
            ],
            'tu' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm">
  <tuv xml:lang="DE">
    <prop type="created_by">DavidMckeown</prop>
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-GB" creationdate="20180821T131628Z" changedate="20180821T131628Z">
    <prop type="project">12153555</prop>
    <prop type="reviewed">false</prop>
    <prop type="aligned">false</prop>
    <prop type="modified_by">DavidMckeown</prop>
    <prop type="filename">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
            'expected' => <<<TMX
<tu tuid="_0000ShBleQXBw1620oZMFEDDm" creationdate="20180821T131628Z" creationid="DAVIDMCKEOWN">
  <prop type="tmgr:docname">DIE BUNDESTAGSFRAKTION IN DER 19.docx</prop>
<tuv xml:lang="de">
    <seg>drei Kinder, Baden-Württemberg</seg>
  </tuv>
  <tuv xml:lang="en-gb">
    <seg>three children, Baden-Württemberg</seg>
  </tuv>
</tu>
TMX,
        ];

        yield 'utf-8 symbols in author name' => [
            'transformTusMapping' => [
                'creationDate' => '//tuv[@xml:lang="{targetLang}"]/@creationdate',
                'author' => '//tu/tuv[@xml:lang="{targetLang}"]/prop[@type="created_by"]',
                'document' => '//tu/tuv[@xml:lang="{targetLang}"]/prop[@type="filename"]',
            ],
            'tu' => <<<TMX
<tu tuid="_lucwQanE0SB5Mk9M5jxkpojo1">
      <tuv xml:lang="de">
        <seg>Gewerberaummietvertrag</seg>
      </tuv>
      <tuv xml:lang="en-gb" creationdate="20161118T121348Z" changedate="20161119T144213Z">
        <prop type="project">1559481</prop>
        <prop type="reviewed">false</prop>
        <prop type="aligned">false</prop>
        <prop type="created_by">Paul.Döhr</prop>
        <prop type="modified_by">Paul.Döhr</prop>
        <prop type="filename">Gewerbemietvertrag ehem. Offiziersheim - Wedlich Servicegruppe - Medi Prosthetics - 01.01.2017.doc</prop>
        <seg>Rental agreement for commercial facilities</seg>
      </tuv>
    </tu>
TMX,
            'expected' => <<<TMX
<tu tuid="_lucwQanE0SB5Mk9M5jxkpojo1" creationdate="20161118T121348Z" creationid="PAUL.D&#246;HR">
      <prop type="tmgr:docname">Gewerbemietvertrag ehem. Offiziersheim - Wedlich Servicegruppe - Medi Prosthetics - 01.01.2017.doc</prop>
<tuv xml:lang="de">
        <seg>Gewerberaummietvertrag</seg>
      </tuv>
      <tuv xml:lang="en-gb">
        <seg>Rental agreement for commercial facilities</seg>
      </tuv>
    </tu>
TMX,
        ];
    }
}
