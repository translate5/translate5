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

namespace MittagQI\Translate5\Test\Unit\T5Memory\Export;

use GuzzleHttp\Psr7\Stream;
use MittagQI\Translate5\T5Memory\Export\TmxChunkFixer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class TmxChunkFixerTest extends TestCase
{
    private TmxChunkFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = TmxChunkFixer::create();
    }

    /**
     * @dataProvider tuTagsDataProvider
     */
    public function testFixChunkEscapesUnescapedXmlInAttributes(string $input, string $expected): void
    {
        $stream = $this->createStream($input);

        $result = $this->fixer->fixChunk($stream);

        $this->assertSame($expected, $result->getContents());
    }

    public static function tuTagsDataProvider(): iterable
    {
        yield 'Unescaped XML with quotes in creationid' => [
            '<tu tuid="5" creationdate="20250106T233949Z" creationid="<BX ID="1" RID="1"/>[-]<EX ID="2" RID="">' . "\n",
            '<tu tuid="5" creationdate="20250106T233949Z" creationid="&lt;BX ID=&quot;1&quot; RID=&quot;1&quot;/&gt;[-]&lt;EX ID=&quot;2&quot; RID=&quot;">' . "\n",
        ];

        yield 'Partially escaped XML' => [
            '<tu creationid="&lt;BX ID="1" RID="1"/&gt;[-]&lt;EX ID="2" RID="" changedate="20250106T233949Z">' . "\n",
            '<tu creationid="&lt;BX ID=&quot;1&quot; RID=&quot;1&quot;/&gt;[-]&lt;EX ID=&quot;2&quot; RID=&quot;" changedate="20250106T233949Z">' . "\n",
        ];

        yield 'Normal attributes without special characters' => [
            '<tu tuid="5" creationdate="20250106T233949Z" creationid="simple_value">' . "\n",
            '<tu tuid="5" creationdate="20250106T233949Z" creationid="simple_value">' . "\n",
        ];

        yield 'Mixed escaped and unescaped' => [
            '<tu creationid="&lt;BX ID="1"/>[-]<EX ID="2" RID=""" segtype="block">' . "\n",
            '<tu creationid="&lt;BX ID=&quot;1&quot;/&gt;[-]&lt;EX ID=&quot;2&quot; RID=&quot;&quot;" segtype="block">' . "\n",
        ];

        yield 'Multiple TU tags' => [
            '<tu creationid="<test>">' . "\n" . '<tu changeid="value">' . "\n",
            '<tu creationid="&lt;test&gt;">' . "\n" . '<tu changeid="value">' . "\n",
        ];

        yield 'Non-TU lines remain unchanged' => [
            '<tuv xml:lang="en-US">' . "\n" . '<seg>Text with <ph/> tag</seg>' . "\n",
            '<tuv xml:lang="en-US">' . "\n" . '<seg>Text with <ph/> tag</seg>' . "\n",
        ];

        yield 'TU with ampersand in attribute' => [
            '<tu creationid="A&B">' . "\n",
            '<tu creationid="A&amp;B">' . "\n",
        ];

        yield 'Double-escaped entities are normalized' => [
            '<tu creationid="&amp;lt;test&amp;gt;">' . "\n",
            '<tu creationid="&lt;test&gt;">' . "\n",
        ];

        yield 'TU with o-tmf attribute' => [
            '<tu o-tmf="<format>">' . "\n",
            '<tu o-tmf="&lt;format&gt;">' . "\n",
        ];

        yield 'TU with multiple attributes containing special chars' => [
            '<tu creationid="<id>" changeid="<change>" tuid="123">' . "\n",
            '<tu creationid="&lt;id&gt;" changeid="&lt;change&gt;" tuid="123">' . "\n",
        ];
    }

    public function testFixChunkReturnsStreamInterface(): void
    {
        $input = '<tu tuid="1">' . "\n";
        $stream = $this->createStream($input);

        $result = $this->fixer->fixChunk($stream);

        $this->assertInstanceOf(StreamInterface::class, $result);
    }

    public function testFixChunkRewindsInputStream(): void
    {
        $input = '<tu tuid="1">' . "\n";
        $stream = $this->createStream($input);

        // Read some content to move the pointer
        $stream->read(5);
        $this->assertGreaterThan(0, $stream->tell());

        // Fix chunk should rewind the stream
        $result = $this->fixer->fixChunk($stream);

        $this->assertSame($input, $result->getContents());
    }

    public function testFixChunkHandlesEmptyStream(): void
    {
        $stream = $this->createStream('');

        $result = $this->fixer->fixChunk($stream);

        $this->assertSame('', $result->getContents());
    }

    public function testFixChunkHandlesMultilineContent(): void
    {
        $input = <<<TMX
<?xml version="1.0" encoding="UTF-8"?>
<tmx version="1.4">
<tu creationid="<test>">
  <tuv xml:lang="en">
    <seg>Test</seg>
  </tuv>
</tu>
</tmx>
TMX;

        $expected = <<<TMX
<?xml version="1.0" encoding="UTF-8"?>
<tmx version="1.4">
<tu creationid="&lt;test&gt;">
  <tuv xml:lang="en">
    <seg>Test</seg>
  </tuv>
</tu>
</tmx>
TMX;

        $stream = $this->createStream($input);

        $result = $this->fixer->fixChunk($stream);

        $this->assertSame($expected, $result->getContents());
    }

    private function createStream(string $content): Stream
    {
        $resource = fopen('php://temp', 'rb+');
        fwrite($resource, $content);
        rewind($resource);

        return new Stream($resource);
    }
}
