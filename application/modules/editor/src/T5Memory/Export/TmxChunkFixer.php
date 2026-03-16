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

namespace MittagQI\Translate5\T5Memory\Export;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

class TmxChunkFixer
{
    public static function create(): self
    {
        return new self();
    }

    public function fixChunk(StreamInterface $stream): StreamInterface
    {
        // Create a temporary stream in memory
        $outputStream = fopen('php://temp', 'rb+');

        if ($outputStream === false) {
            throw new \RuntimeException('Failed to create temporary stream');
        }

        // Ensure the input stream is at the beginning
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        // Read the input stream line by line
        while (! $stream->eof()) {
            $line = '';

            // Read character by character until we find a newline or reach EOF
            while (! $stream->eof()) { // @phpstan-ignore-line
                $char = $stream->read(1);
                $line .= $char;

                if ($char === "\n") {
                    break;
                }
            }

            if ($line === '') {
                break;
            }

            if (! str_contains($line, '<tu')) {
                fwrite($outputStream, $line);

                continue;
            }

            // Fix unescaped XML in attribute values of <tu> tags
            // The problem is that attribute values might contain unescaped XML entities like:
            // creationid="<BX ID="1" RID="1"/>[-]<EX ID="2" RID="
            // where the quotes inside are not escaped, causing malformed XML

            // Valid TMX <tu> attributes that might contain problematic values
            $attributes = [
                'tuid',
                'o-tmf',
                'srclang',
                'datatype',
                'usagecount',
                'lastusagedate',
                'creationtool',
                'creationtoolversion',
                'creationdate',
                'creationid',
                'changedate',
                'changeid',
                'segtype',
                'o-encoding',
            ];

            // Process each attribute that might contain XML special characters
            // We look for patterns like: attributename="value_with_<_or_>"
            // The value might span until we find an actual attribute boundary or tag end
            foreach ($attributes as $attr) {
                // This pattern looks for:
                // 1. The attribute name followed by ="
                // 2. Content (captured non-greedily)
                // 3. A closing " that is followed by either:
                //    - A space and another known attribute name with = (positive lookahead)
                //    - The end of the tag >
                $nextAttrPattern = '(?:\s+(?:' . implode('|', $attributes) . ')=|>)';
                $pattern = '/' . preg_quote($attr, '/') . '="(.*?)"(?=' . $nextAttrPattern . ')/s';

                $line = preg_replace_callback(
                    $pattern,
                    function (array $matches) use ($attr) {
                        $value = $matches[1];

                        // Check if value contains XML special characters that need escaping
                        if (! preg_match('/[<>&"]/', $value)) {
                            return $matches[0];
                        }

                        // Decode any already-escaped entities to avoid double-escaping
                        $decoded = html_entity_decode($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

                        // Re-escape properly for XML (this will escape <, >, &, and ")
                        $escaped = htmlspecialchars($decoded, ENT_XML1 | ENT_QUOTES, 'UTF-8', false);

                        return $attr . '="' . $escaped . '"';
                    },
                    $line
                );
            }

            fwrite($outputStream, $line);
        }

        rewind($outputStream);

        return new Stream($outputStream);
    }
}
