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

namespace MittagQI\Translate5\TMX;

class TmxSymbolsFixer
{
    private const CHUNK_SIZE = 1024 * 1024; // 1 MB chunks for memory efficiency

    public function fixInvalidXmlSymbols(string $filePath): void
    {
        $dirPath = APPLICATION_PATH . '/../data/TmxImportPreprocessing/';
        if (! is_dir($dirPath) && ! mkdir($dirPath) && ! is_dir($dirPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
        }
        $outputFilename = tempnam($dirPath, 'tmxfix_');

        // Character replacement mapping for C0 control characters
        $replacements = [
            0x00 => '',           // NUL - remove
            0x01 => '',           // SOH - remove
            0x02 => '',           // STX - remove
            0x03 => '',           // ETX - remove
            0x04 => '',           // EOT - remove
            0x05 => '',           // ENQ - remove
            0x06 => '',           // ACK - remove
            0x07 => '',           // BEL - remove
            0x08 => '',           // BS (Backspace) - remove
            0x0B => ' ',          // VT (Vertical Tab) - replace with space
            0x0C => ' ',          // FF (Form Feed) - replace with space
            0x0E => '',           // SO - remove
            0x0F => '',           // SI - remove
            0x10 => '',           // DLE - remove
            0x11 => '',           // DC1 - remove
            0x12 => '',           // DC2 - remove
            0x13 => '',           // DC3 - remove
            0x14 => '',           // DC4 - remove
            0x15 => '',           // NAK - remove
            0x16 => '',           // SYN - remove
            0x17 => '',           // ETB - remove
            0x18 => '',           // CAN - remove
            0x19 => '',           // EM - remove
            0x1A => '',           // SUB - remove
            0x1B => '',           // ESC - remove
            0x1C => ' ',          // FS (File Separator) - replace with space
            0x1D => ' ',          // GS (Group Separator) - replace with space
            0x1E => ' ',          // RS (Record Separator) - replace with space
            0x1F => ' ',          // US (Unit Separator) - replace with space
        ];

        // Process file in chunks to optimize memory usage
        $inputHandle = fopen($filePath, 'rb');
        $outputHandle = fopen($outputFilename, 'wb');

        if (! $inputHandle || ! $outputHandle) {
            throw new \RuntimeException("Error: Cannot open files for processing.");
        }

        $buffer = '';
        $overlap = 32; // Keep 32 bytes overlap to handle split character references

        while (! feof($inputHandle)) {
            // Read chunk and append to remaining buffer
            $chunk = fread($inputHandle, self::CHUNK_SIZE);
            if ($chunk === false) {
                break;
            }

            $buffer .= $chunk;

            // If not at end of file, keep the last $overlap bytes for the next iteration
            if (! feof($inputHandle)) {
                $content_to_process = substr($buffer, 0, -$overlap);
                $buffer = substr($buffer, -$overlap);
            } else {
                // Process remaining buffer at end of file
                $content_to_process = $buffer;
                $buffer = '';
            }

            // Replace character references in the content
            $processed = preg_replace_callback(
                '/&#x([0-9A-Fa-f]+);/',
                function ($matches) use ($replacements) {
                    $hex = strtoupper($matches[1]);
                    $codepoint = hexdec($hex);

                    // Skip valid XML characters (TAB, LF, CR)
                    if (in_array($codepoint, [0x09, 0x0A, 0x0D], true)) {
                        return $matches[0];
                    }

                    // Return replacement or empty string
                    return $replacements[$codepoint] ?? '';
                },
                $content_to_process
            );

            // Write processed content to output
            fwrite($outputHandle, $processed);
        }

        fclose($inputHandle);
        fclose($outputHandle);

        rename($outputFilename, $filePath);

        if (file_exists($outputFilename)) {
            throw new \RuntimeException("Failed to rename temporary file to original file path.");
        }
    }
}
