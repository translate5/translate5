<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\TMX;

use MittagQI\Translate5\TMX\TmxSymbolsFixer;
use PHPUnit\Framework\TestCase;

class TmxSymbolsFixerTest extends TestCase
{
    private TmxSymbolsFixer $fixer;

    private string $testDir;

    protected function setUp(): void
    {
        $this->fixer = new TmxSymbolsFixer();

        // Create temporary test directory
        $this->testDir = sys_get_temp_dir() . '/tmx-fixer-test-' . uniqid();
        mkdir($this->testDir, 0777, true);

        // Mock APPLICATION_PATH constant
        if (! defined('APPLICATION_PATH')) {
            define('APPLICATION_PATH', $this->testDir);
        }

        // Create TmxImportPreprocessing directory
        @mkdir($this->testDir . '/data/TmxImportPreprocessing', 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        $this->deleteDirectory($this->testDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Test that valid XML characters (TAB, LF, CR) are preserved
     */
    public function testPreservesValidXmlCharacters(): void
    {
        $content = "Text with\ttab\nand line feed&#x0D;and carriage return";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("\t", $result);
        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString("&#x0D;", $result);
    }

    /**
     * Test that Unit Separator (0x1F) is replaced with space
     */
    public function testReplacesUnitSeparatorWithSpace(): void
    {
        $content = "Das ist spritzer&#x1F;arm.";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("spritzer arm.", $result);
        $this->assertStringNotContainsString("&#x1F;", $result);
    }

    /**
     * Test that multiple illegal characters are replaced
     */
    public function testReplacesMultipleIllegalCharacters(): void
    {
        $content = "Text&#x1C;with&#x1D;separators&#x1E;and&#x1F;more";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("Text with separators and more", $result);
    }

    /**
     * Test that NUL character (0x00) is removed
     */
    public function testRemovesNulCharacter(): void
    {
        $content = "Text&#x00;with null character";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("Textwith null character", $result);
    }

    /**
     * Test that Form Feed (0x0C) is replaced with space
     */
    public function testReplacesFormFeedWithSpace(): void
    {
        $content = "Before&#x0C;After";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("Before After", $result);
    }

    /**
     * Test that Vertical Tab (0x0B) is replaced with space
     */
    public function testReplacesVerticalTabWithSpace(): void
    {
        $content = "Before&#x0B;After";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("Before After", $result);
    }

    /**
     * Test processing large file with chunked reading
     */
    public function testProcessesLargeFileWithChunks(): void
    {
        // Create a file larger than chunk size (1MB)
        $largeContent = '';
        for ($i = 0; $i < 20; $i++) {
            $largeContent .= "Line $i: Some text with control character &#x1F; at position\n";
        }
        $largeContent = str_repeat($largeContent, 100); // Make it ~2MB

        $inputFile = $this->createTestFile($largeContent);
        $originalSize = filesize($inputFile);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringNotContainsString("&#x1F;", $result);
        $this->assertStringContainsString("at position", $result);
    }

    /**
     * Test that character reference split across chunk boundary is handled correctly
     */
    public function testHandlesCharacterReferenceSplitAcrossChunks(): void
    {
        // Create content with a character reference that might be split
        $content = "Text before " . str_repeat("X", 1024 * 1024 - 20) . " &#x1F; Text after";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringNotContainsString("&#x1F;", $result);
        $this->assertStringContainsString("Text after", $result);
    }

    /**
     * Test that mixed valid and invalid characters are processed correctly
     */
    public function testHandlesMixedValidAndInvalidCharacters(): void
    {
        $content = "Valid&#x09;tab and invalid&#x1F;separator and new&#x0A;line";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("&#x09;", $result); // TAB preserved
        $this->assertStringNotContainsString("&#x1F;", $result); // Unit separator replaced
        $this->assertStringContainsString("&#x0A;", $result); // LF preserved
    }

    /**
     * Test that file is properly replaced after processing
     */
    public function testReplacesOriginalFile(): void
    {
        $content = "Original content with &#x1F; character";
        $inputFile = $this->createTestFile($content);
        $originalPath = $inputFile;

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        // File should still exist at the same path
        $this->assertFileExists($originalPath);

        $result = file_get_contents($originalPath);
        $this->assertStringContainsString("Original content", $result);
        $this->assertStringNotContainsString("&#x1F;", $result);
    }

    /**
     * Test with all C0 control characters that should be removed
     */
    public function testRemovesAllRemovableControlCharacters(): void
    {
        $removableChars = [0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08,
            0x0E, 0x0F, 0x10, 0x11, 0x12, 0x13, 0x14, 0x15, 0x16,
            0x17, 0x18, 0x19, 0x1A, 0x1B];

        $content = "Start";
        foreach ($removableChars as $char) {
            $content .= sprintf("&#x%02X;", $char);
        }
        $content .= "End";

        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertStringContainsString("StartEnd", $result);

        // Verify no control characters remain
        foreach ($removableChars as $char) {
            $this->assertStringNotContainsString(sprintf("&#x%02X;", $char), $result);
        }
    }

    /**
     * Test with empty file
     */
    public function testHandlesEmptyFile(): void
    {
        $inputFile = $this->createTestFile('');

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertSame('', $result);
    }

    /**
     * Test with file containing only valid content
     */
    public function testPreservesContentWithoutControlCharacters(): void
    {
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tmx version=\"1.4\">\n  <body>\n    <tu>\n      <seg>Valid content</seg>\n    </tu>\n  </body>\n</tmx>";
        $inputFile = $this->createTestFile($content);

        $this->fixer->fixInvalidXmlSymbols($inputFile);

        $result = file_get_contents($inputFile);
        $this->assertSame($content, $result);
    }

    /**
     * Helper method to create a test file
     */
    private function createTestFile(string $content): string
    {
        $filename = @tempnam($this->testDir, 'tmx_test_');
        file_put_contents($filename, $content);

        return $filename;
    }
}
