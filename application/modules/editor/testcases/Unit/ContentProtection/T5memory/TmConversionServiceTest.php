<?php

namespace MittagQI\Translate5\Test\Unit\ContentProtection\T5memory;

use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use PHPUnit\Framework\TestCase;

class TmConversionServiceTest extends TestCase
{
    /**
     * @dataProvider t5memorySegmentsProvider
     */
    public function testConvertT5MemoryTagToContent(string $t5memorySegment, string $expected): void
    {
        $tmConversionService = TmConversionService::create();

        $actual = $tmConversionService->convertT5MemoryTagToContent($t5memorySegment);

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
            'This is a segment with CP tag <goba>.',
        ];

        yield 'segment with CP tag html entity' => [
            'This is a segment with CP tag <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="&copy;"/>',
            'This is a segment with CP tag ©',
        ];
    }

    /**
     * @dataProvider segmentsProvider
     */
    public function testConvertContentTagToT5MemoryTag(string $segment, string $expected, bool $isSource): void
    {
        $tmConversionService = TmConversionService::create();

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
            'This is a segment with one tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="ZGVmYXVsdCBZLW0tZA=="/>.',
            'This is a segment with one tag <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/>.',
            true,
        ];

        yield 'segment with one CP tag target' => [
            'This is a segment with one tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23"/>.',
            'This is a segment with one tag <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="15/09/23"/>.',
            false,
        ];

        yield 'segment with 2 CP tags' => [
            'This is a segment with CP tag <number type="date" name="default Y-m-d" source="2023-09-15" iso="2023-09-15" target="15/09/23" regex="ZGVmYXVsdCBZLW0tZA=="/> and <number type="date" name="default Y-m-d" source="2024-10-19" iso="2024-10-19" target="19/10/24" regex="ZGVmYXVsdCBZLW0tZA=="/>.',
            'This is a segment with CP tag <t5:n id="1" r="ZGVmYXVsdCBZLW0tZA==" n="2023-09-15"/> and <t5:n id="2" r="ZGVmYXVsdCBZLW0tZA==" n="2024-10-19"/>.',
            true,
        ];

        yield 'segment with tag-like content in CP tag' => [
            'This is a segment with CP tag <number type="keep-content" name="Goba" source="&lt;goba&gt;" iso="&lt;goba&gt;" target="&lt;goba&gt;" regex="U46xSc9PSoyxUwYA"/>.',
            'This is a segment with CP tag <t5:n id="1" r="U46xSc9PSoyxUwYA" n="*≺*goba*≻*"/>.',
            true,
        ];
    }
}
