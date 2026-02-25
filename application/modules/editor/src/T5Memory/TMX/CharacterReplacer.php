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

namespace MittagQI\Translate5\T5Memory\TMX;

use MittagQI\Translate5\ContentProtection\T5memory\T5NTag;

class CharacterReplacer
{
    private const TAG_TYPE = 'utf-char';

    /**
     * Mapping of codepoint (decimal) to character alias based on Unicode/XML standards.
     *
     * According to XML 1.0/1.1 specifications, valid characters are:
     *   #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
     *
     * This means:
     *   - TAB (0x09), LF (0x0A), CR (0x0D) are valid ✓
     *   - NBSP (0xA0) is valid ✓ (within #x20-#xD7FF)
     *   - SHY (0xAD) is valid ✓ (within #x20-#xD7FF)
     *   - ALL Unicode space characters (0x1680, 0x2000-0x202F, etc.) are valid ✓ (within #x20-#xD7FF)
     *   - NUL (0x00) is NEVER valid in XML ✗ (not even as &#x00; or \u0000)
     *   - All other C0 controls (0x00-0x08, 0x0B-0x0C, 0x0E-0x1F) are invalid ✗
     *   - DEL (0x7F) is invalid ✗
     *   - C1 controls (0x80-0x9F) are invalid ✗
     *
     * This class maps only TRULY INVALID characters to t5:n tags for safe storage.
     *
     * Reference: https://www.w3.org/TR/xml/#charsets
     */
    private const INVALID_CHAR_MAP = [
        // C0 Control Characters (0x00-0x1F)
        0x00 => '00',   // NUL (Null)
        0x01 => '01',   // SOH (Start of Heading)
        0x02 => '02',   // STX (Start of Text)
        0x03 => '03',   // ETX (End of Text)
        0x04 => '04',   // EOT (End of Transmission)
        0x05 => '05',   // ENQ (Enquiry)
        0x06 => '06',   // ACK (Acknowledge)
        0x07 => '07',   // BEL (Bell)
        0x08 => '08',   // BS (Backspace)
        // 0x09 => TAB (VALID in XML)
        // 0x0A => LF (VALID in XML)
        0x0B => '0b',   // VT (Vertical Tab)
        0x0C => '0c',   // FF (Form Feed)
        // 0x0D => CR (VALID in XML)
        0x0E => '0e',   // SO (Shift Out)
        0x0F => '0f',   // SI (Shift In)
        0x10 => '10',   // DLE (Data Link Escape)
        0x11 => '11',   // DC1 (Device Control 1)
        0x12 => '12',   // DC2 (Device Control 2)
        0x13 => '13',   // DC3 (Device Control 3)
        0x14 => '14',   // DC4 (Device Control 4)
        0x15 => '15',   // NAK (Negative Acknowledge)
        0x16 => '16',   // SYN (Synchronous Idle)
        0x17 => '17',   // ETB (End of Transmission Block)
        0x18 => '18',   // CAN (Cancel)
        0x19 => '19',   // EM (End of Medium)
        0x1A => '1a',   // SUB (Substitute)
        0x1B => '1b',   // ESC (Escape)
        0x1C => '1c',   // FS (File Separator)
        0x1D => '1d',   // GS (Group Separator)
        0x1E => '1e',   // RS (Record Separator)
        0x1F => '1f',   // US (Unit Separator)
        0x7F => '7f',   // DEL (Delete)
    ];

    /**
     * Reverse mapping: alias => codepoint
     */
    private array $aliasToCodepointMap;

    public function __construct()
    {
        $this->aliasToCodepointMap = array_flip(self::INVALID_CHAR_MAP);
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param bool $preserve if false - invalid characters are removed instead of replaced
     */
    public function replaceInvalidXmlCharacters(string $text, int &$indexCounter = 1, bool $preserve = true): string
    {
        // First fix invalid UTF-8 symbols
        $text = $this->replaceInvalidUtf8Characters($text, $indexCounter, $preserve);

        // Then replace control character references
        $text = $this->replaceInvalidCharacterReferences($text, $indexCounter, $preserve);

        return $text;
    }

    public function revertToInvalidXmlCharacters(string $text): string
    {
        // Revert t5:n tags back to their original form based on type
        $text = preg_replace_callback(
            T5NTag::fullTagRegex(),
            function ($matches) {
                $t5nTag = T5NTag::fromMatch($matches);
                $alias = strtolower($t5nTag->content); // Convert alias to lowercase for case-insensitive lookup

                if ($t5nTag->rule !== self::TAG_TYPE) {
                    // If the rule/type doesn't match, return the tag as-is
                    return $matches[0];
                }

                // Look up the codepoint from the alias
                if (! isset($this->aliasToCodepointMap[$alias])) {
                    // If not found, return the tag as-is
                    return $matches[0];
                }

                return mb_chr($this->aliasToCodepointMap[$alias]);
            },
            $text
        );

        return $text;
    }

    /**
     * Fix invalid UTF-8 symbols by replacing them with t5:n tags.
     *
     * Valid UTF-8 characters like Ö (U+00D6) are preserved as-is.
     * Invalid control characters are replaced with t5:n tags containing their alias.
     *
     * Handles three forms of invalid characters:
     * 1. Raw/literal control bytes (e.g., \x00, \x0B)
     * 2. Unicode escape sequences (e.g., \u0003, \u000B)
     * 3. Character references (handled by replaceInvalidCharacterReferences)
     *
     * @param bool $preserve if false - invalid characters are removed instead of replaced
     * @return string The text with invalid UTF-8 characters replaced
     */
    private function replaceInvalidUtf8Characters(string $text, int &$indexCounter, bool $preserve): string
    {
        // First, replace raw/literal control bytes with t5:n tags
        $text = preg_replace_callback(
            '/./u',
            function ($matches) use (&$indexCounter, $preserve) {
                $char = $matches[0];
                $codepoint = mb_ord($char);

                // Keep valid XML characters (TAB, LF, CR)
                if (in_array($codepoint, [0x09, 0x0A, 0x0D], true)) {
                    return $char;
                }

                // Check if this is an invalid character
                if (isset(self::INVALID_CHAR_MAP[$codepoint])) {
                    if (! $preserve) {
                        // If not preserving, remove the invalid character
                        return '';
                    }

                    $alias = self::INVALID_CHAR_MAP[$codepoint];
                    $t5nTag = new T5NTag(1000 + $indexCounter++, self::TAG_TYPE, $alias);

                    return $t5nTag->toString();
                }

                // Keep all other characters as-is
                return $char;
            },
            $text
        );

        // Then handle Unicode escape sequences like \u0003
        $text = preg_replace_callback(
            '/\\\\u([0-9A-Fa-f]{4})/',
            function ($matches) use (&$indexCounter) {
                $codepoint = hexdec($matches[1]);

                // Valid XML/UTF-8 characters (TAB, LF, CR are valid in XML)
                // Keep escape sequences for these as-is (don't convert to raw chars)
                if (in_array($codepoint, [0x09, 0x0A, 0x0D], true)) {
                    return $matches[0];  // Keep the escape sequence as-is
                }

                // Check if this is an invalid character
                if (isset(self::INVALID_CHAR_MAP[$codepoint])) {
                    $alias = self::INVALID_CHAR_MAP[$codepoint];
                    $t5nTag = new T5NTag(1000 + $indexCounter++, self::TAG_TYPE, $alias);

                    return $t5nTag->toString();
                }

                // For all other characters, keep the escape sequence as-is
                return $matches[0];
            },
            $text
        );

        return $text;
    }

    /**
     * Replace invalid control character references with t5:n tags.
     *
     * @param bool $preserve if false - invalid character references are removed instead of replaced
     * @return string The text with control character references replaced
     */
    private function replaceInvalidCharacterReferences(string $text, int &$indexCounter, bool $preserve): string
    {
        // Replace character references in the content
        return preg_replace_callback(
            '/&#x([0-9A-Fa-f]+);/',
            function ($matches) use (&$indexCounter, $preserve) {
                $hex = strtoupper($matches[1]);
                $codepoint = hexdec($hex);

                // Skip valid XML characters (TAB, LF, CR)
                if (in_array($codepoint, [0x09, 0x0A, 0x0D], true)) {
                    return $matches[0];
                }

                // Check if this is an invalid character
                if (isset(self::INVALID_CHAR_MAP[$codepoint])) {
                    if (! $preserve) {
                        // If not preserving, remove the invalid character reference
                        return '';
                    }
                    $alias = self::INVALID_CHAR_MAP[$codepoint];
                    $t5nTag = new T5NTag(1000 + $indexCounter++, self::TAG_TYPE, $alias);

                    return $t5nTag->toString();
                }

                // Keep all other characters as-is (valid UTF-8)
                return $matches[0];
            },
            $text
        );
    }
}
