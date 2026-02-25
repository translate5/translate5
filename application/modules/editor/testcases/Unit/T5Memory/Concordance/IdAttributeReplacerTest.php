<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Concordance;

use MittagQI\Translate5\T5Memory\Concordance\IdAttributeReplacer;
use PHPUnit\Framework\TestCase;

class IdAttributeReplacerTest extends TestCase
{
    private IdAttributeReplacer $replacer;

    protected function setUp(): void
    {
        $this->replacer = new IdAttributeReplacer();
    }

    public function testEmptyMarkupReturnsEmpty(): void
    {
        $result = $this->replacer->replace('');
        $this->assertSame('', $result);
    }

    public function testWhitespaceOnlyMarkupReturnsEmpty(): void
    {
        $result = $this->replacer->replace('   ');
        $this->assertSame('   ', $result);
    }

    /**
     * @dataProvider selfClosingTagProvider
     */
    public function testReplacesIdInSelfClosingTags(string $input, string $expected): void
    {
        $result = $this->replacer->replace($input);
        $this->assertSame($expected, $result);
    }

    public function selfClosingTagProvider(): array
    {
        return [
            'simple self-closing tag' => [
                'test <ph id="101"/>',
                'test <ph x="101"/>',
            ],
            'self-closing tag with multiple attributes' => [
                '<el id="5" class="foo"/>',
                '<el x="5" class="foo"/>',
            ],
            'self-closing x tag' => [
                'prefix <x id="1"/> suffix',
                'prefix <x x="1"/> suffix',
            ],
            'self-closing bpt and ept tag' => [
                'prefix <bpt id="501" rid="2"/> suffix<ept rid="2"/>',
                'prefix <bpt x="501" i="2"/> suffix<ept i="2"/>',
            ],
        ];
    }

    /**
     * @dataProvider openingClosingTagProvider
     */
    public function testReplacesIdInOpeningClosingTags(string $input, string $expected): void
    {
        $result = $this->replacer->replace($input);
        $this->assertSame($expected, $result);
    }

    public function openingClosingTagProvider(): array
    {
        return [
            'opening/closing tags with double quotes' => [
                '<div id="test">content</div>',
                '<div x="test">content</div>',
            ],
            'opening/closing tags with single quotes' => [
                '<tag id=\'single\'>text</tag>',
                '<tag x=\'single\'>text</tag>',
            ],
        ];
    }

    /**
     * @dataProvider idInTextContentProvider
     */
    public function testDoesNotReplaceIdInTextContent(string $input): void
    {
        $result = $this->replacer->replace($input);
        $this->assertSame($input, $result);
    }

    public function idInTextContentProvider(): array
    {
        return [
            'id in text without tags' => [
                'Some text saying id="blabla" without tags',
            ],
            'rid in text without tags' => [
                'Some text saying rid="blabla" without tags',
            ],
        ];
    }

    /**
     * @dataProvider mixedCasesProvider
     */
    public function testHandlesMixedCases(string $input, string $expected): void
    {
        $result = $this->replacer->replace($input);
        $this->assertSame($expected, $result);
    }

    public function mixedCasesProvider(): array
    {
        return [
            'text with id and tag with id' => [
                'Some id="text" here <ph id="101"/>',
                'Some id="text" here <ph x="101"/>',
            ],
            'tag with id and text with id' => [
                '<tag id="1"/> and id="notintag" text',
                '<tag x="1"/> and id="notintag" text',
            ],
        ];
    }

    public function testPreservesQuoteStyle(): void
    {
        $doubleQuotes = $this->replacer->replace('<div id="test">content</div>');
        $this->assertStringContainsString('x="test"', $doubleQuotes);

        $singleQuotes = $this->replacer->replace('<div id=\'test\'>content</div>');
        $this->assertStringContainsString("x='test'", $singleQuotes);
    }

    public function testPreservesSelfClosingTags(): void
    {
        $result = $this->replacer->replace('<ph id="101"/>');
        $this->assertStringEndsWith('/>', $result);
    }

    /**
     * @dataProvider t5nTagProvider
     */
    public function testPreservesIdAttributeInT5nTags(string $input, string $expected): void
    {
        $result = $this->replacer->replace($input);
        $this->assertSame($expected, $result);
    }

    public function t5nTagProvider(): array
    {
        return [
            't5:n tag with id attribute should preserve id' => [
                '<t5:n id="1"/>',
                '<t5:n id="1"/>',
            ],
            't5:n tag with single quotes' => [
                '<t5:n id=\'1\'/>',
                '<t5:n id=\'1\'/>',
            ],
            't5:n tag with multiple attributes' => [
                '<t5:n id="2" r="test" n="1"/>',
                '<t5:n id="2" r="test" n="1"/>',
            ],
            'mixed: t5:n tag with id and other tag with id' => [
                '<t5:n id="1"/> <ph id="101"/>',
                '<t5:n id="1"/> <ph x="101"/>',
            ],
        ];
    }
}
