<?php

namespace MittagQI\Translate5\Test\Unit\ContentProtection\T5memory;

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\Model\ContentProtectionRepository;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\T5Memory\TMX\CharacterReplacer;
use PHPUnit\Framework\TestCase;

class ConvertT5MemoryTagServiceTest extends TestCase
{
    /**
     * @dataProvider t5memorySegmentsProvider
     */
    public function testConvertT5MemoryTagToContent(string $t5memorySegment, string $expected): void
    {
        $contentProtector = $this->createMock(ContentProtector::class);
        $logger = $this->createMock(\ZfExtended_Logger::class);
        $contentProtectionRepository = $this->createMock(ContentProtectionRepository::class);

        $service = new ConvertT5MemoryTagService(
            $contentProtector,
            CharacterReplacer::create(),
            $logger,
            $contentProtectionRepository,
        );

        $actual = $service->convertT5MemoryTagToContent($t5memorySegment);

        self::assertSame($expected, $actual);
    }

    public function t5memorySegmentsProvider(): iterable
    {
        yield 'empty segment' => ['', ''];

        yield 'segment with no tags' => [
            'This is a segment with no tags.',
            'This is a segment with no tags.',
        ];

        yield 'segment with one CP tag' => [
            'This is a segment with one tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/>.',
            'This is a segment with one tag 2023-09-15.',
        ];

        yield 'segment with 2 CP tags' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/> and <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/>.',
            'This is a segment with CP tag 2023-09-15 and 2023-09-15.',
        ];

        yield 'segment with tag-like content in CP tag' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="*≺*goba*≻*"/>.',
            'This is a segment with CP tag &lt;goba&gt;.',
        ];

        yield 'segment with CP tag html entity' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="&copy;"/>',
            'This is a segment with CP tag &copy;',
        ];

        yield 'ampersand in the TU' => [
            'input' => <<<TU
<tu tuid="64" creationdate="20240429T101513Z" creationid="ITL POSTEDIT ES">
      <prop type="tmgr:segId">0</prop>
      <prop type="t5:InternalKey">978:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">MASTER_Mover_GA_2024-04_18_DE_bereinigt.idml</prop>
      <prop type="tmgr:context">a7e4fc30273e5e1a206dd7018ada3d5c_mrk-0</prop>
      <tuv xml:lang="de">
        <seg>Maximale &amp; Geschwindigkeit</seg>
      </tuv>
      <tuv xml:lang="es">
        <seg>Velocidad &amp; máxima</seg>
      </tuv>
</tu>
TU,
            'expected' => <<<TU
<tu tuid="64" creationdate="20240429T101513Z" creationid="ITL POSTEDIT ES">
      <prop type="tmgr:segId">0</prop>
      <prop type="t5:InternalKey">978:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">MASTER_Mover_GA_2024-04_18_DE_bereinigt.idml</prop>
      <prop type="tmgr:context">a7e4fc30273e5e1a206dd7018ada3d5c_mrk-0</prop>
      <tuv xml:lang="de">
        <seg>Maximale &amp; Geschwindigkeit</seg>
      </tuv>
      <tuv xml:lang="es">
        <seg>Velocidad &amp; máxima</seg>
      </tuv>
</tu>
TU,
        ];

        yield 'ampersand in the TU + content protection tag' => [
            'input' => <<<TU
<tu tuid="64" creationdate="20240429T101513Z" creationid="ITL POSTEDIT ES">
      <prop type="tmgr:segId">0</prop>
      <prop type="t5:InternalKey">978:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">MASTER_Mover_GA_2024-04_18_DE_bereinigt.idml</prop>
      <prop type="tmgr:context">a7e4fc30273e5e1a206dd7018ada3d5c_mrk-0</prop>
      <tuv xml:lang="de">
        <seg>Maximale &amp; Geschwindigkeit <t5:n id="1" r="09eIKa6Jq" n="H&amp;M"/></seg>
      </tuv>
      <tuv xml:lang="es">
        <seg>Velocidad &amp; máxima</seg>
      </tuv>
</tu>
TU,
            'expected' => <<<TU
<tu tuid="64" creationdate="20240429T101513Z" creationid="ITL POSTEDIT ES">
      <prop type="tmgr:segId">0</prop>
      <prop type="t5:InternalKey">978:1</prop>
      <prop type="tmgr:markup">OTMXUXLF</prop>
      <prop type="tmgr:docname">MASTER_Mover_GA_2024-04_18_DE_bereinigt.idml</prop>
      <prop type="tmgr:context">a7e4fc30273e5e1a206dd7018ada3d5c_mrk-0</prop>
      <tuv xml:lang="de">
        <seg>Maximale &amp; Geschwindigkeit H&amp;M</seg>
      </tuv>
      <tuv xml:lang="es">
        <seg>Velocidad &amp; máxima</seg>
      </tuv>
</tu>
TU,
        ];

        yield 'segment with CP tag html entity and CP tag invalid XML escaped char' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="&copy;"/> and <t5:n id="3" r="utf-char" n="03"/>',
            "This is a segment with CP tag &copy; and \x03",
        ];

        yield 'segment with CP tag html entity and CP tag invalid XML char' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="&copy;"/> and <t5:n id="3" r="utf-char" n="00"/>',
            "This is a segment with CP tag &copy; and \x00",
        ];

        yield 'segment with CP tag html entity and CP tag invalid XML control char' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="&copy;"/> and <t5:n id="3" r="utf-char" n="0b"/>',
            "This is a segment with CP tag &copy; and \x0B",
        ];
    }

    /**
     * @dataProvider segmentsProvider
     */
    public function testConvertContentTagToT5MemoryTag(string $segment, string $expected, bool $isSource): void
    {
        $contentProtectionRepository = $this->createMock(ContentProtectionRepository::class);
        $contentProtectionRepository->method('getRegexToKeyMap')->willReturn([
            '/(\s|^|\()(\d{4}-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9]))((\.(\s|$))|(,(\s|$))|\s|$|\))/' => 'aaa',
            '/(\s|^|\()([±\-+]?[1-9]\d{0,2}((\.)(\d{3}\.)*\d{3})?)(([\.,;:?!](\s|$))|\s|$|\))/u' => 'bbb',
        ]);

        $contentProtector = ContentProtector::create();
        $logger = $this->createMock(\ZfExtended_Logger::class);

        $tmConversionService = new ConvertT5MemoryTagService(
            $contentProtector,
            CharacterReplacer::create(),
            $logger,
            $contentProtectionRepository,
        );

        $actual = $tmConversionService->convertContentTagToT5MemoryTag($segment, $isSource);

        self::assertSame($expected, $actual);
    }

    public function segmentsProvider(): iterable
    {
        yield 'empty segment' => ['', '', true];

        yield 'segment with no tags' => [
            'This is a segment with no tags.',
            'This is a segment with no tags.',
            true,
        ];

        yield 'segment with one CP tag' => [
            'This is a segment with one tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="09eIKa6Jq4nR0NSISak2qdXVMIg21LWMrQGSRrHRBiCmMZAyBItYxmrCFRgCRY1gopoaGjF6IKNUNDVrNHRgLBBVE6OpqQ8A"/>.',
            'This is a segment with one tag <t5:n id="1" r="aaa" n="2023-09-15"/>.',
            true,
        ];

        yield 'segment with one CP tag target' => [
            'This is a segment with one tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="09eIKa6Jq4nR0NSISak2qdXVMIg21LWMrQGSRrHRBiCmMZAyBItYxmrCFRgCRY1gopoaGjF6IKNUNDVrNHRgLBBVE6OpqQ8A"/>.',
            'This is a segment with one tag <t5:n id="1" r="aaa" n="15/09/23"/>.',
            false,
        ];

        yield 'segment with 2 CP tags' => [
            'This is a segment with CP tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="09eIKa6Jq4nR0NSISak2qdXVMIg21LWMrQGSRrHRBiCmMZAyBItYxmrCFRgCRY1gopoaGjF6IKNUNDVrNHRgLBBVE6OpqQ8A"/> and <number type="date" name="default Y-m-d" source="2024-10-19" iso="2024-10-19" target="19/10/24" regex="09eIKa6Jq4nR0NSIPrQxRlc71j7aUNcyNial2kDHqFZDI0ZPUwPIMa4FMrTADE17TQ2N6Bg9HWsre8VYkH4VTc0aEFUTo6mpXwoA"/>.',
            'This is a segment with CP tag <t5:n id="1" r="aaa" n="2023-09-15"/> and <t5:n id="2" r="bbb" n="2024-10-19"/>.',
            true,
        ];

        yield 'segment with tag-like content in CP tag' => [
            'This is a segment with CP tag <number type="keep-content" name="Goba" source="&lt;goba&gt;" iso="&lt;goba&gt;" target="&lt;goba&gt;" regex="U46xSc9PSoyxUwYA" key="ccc"/>.',
            'This is a segment with CP tag <t5:n id="1" r="ccc" n="*≺*goba*≻*"/>.',
            true,
        ];

        yield 'segment with CP tag number and CP tag invalid XML escaped char' => [
            'This is a segment with CP tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="U46xSc9PSoyxUwiurehfoierhioYA" key="aaa"/> and \\u0003',
            'This is a segment with CP tag <t5:n id="2" r="aaa" n="2023-09-15"/> and <t5:n id="1001" r="utf-char" n="03"/>',
            true,
        ];

        yield 'segment with CP tag number and CP tag invalid XML char' => [
            "This is a segment with CP tag <number type=\"date\" name=\"default Y-m-d\" source=\"2023-09-15\" iso=\"2023-09-15\" target=\"15/09/23\" regex=\"U46xSc9PSoyxUwiurehfoierhioYA\" key=\"aaa\"/> and \x00",
            'This is a segment with CP tag <t5:n id="2" r="aaa" n="2023-09-15"/> and <t5:n id="1001" r="utf-char" n="00"/>',
            true,
        ];

        yield 'segment with CP tag number and CP tag invalid XML control char' => [
            'This is a segment with CP tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="U46xSc9PSoyxUwiurehfoierhioYA" key="aaa"/> and &#x0B;',
            'This is a segment with CP tag <t5:n id="2" r="aaa" n="2023-09-15"/> and <t5:n id="1001" r="utf-char" n="0b"/>',
            true,
        ];
    }
}
