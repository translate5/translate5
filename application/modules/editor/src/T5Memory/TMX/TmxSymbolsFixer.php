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

class TmxSymbolsFixer
{
    public function __construct(
        private readonly CharacterReplacer $characterReplacer,
    ) {
    }

    public static function create(): self
    {
        return new self(
            CharacterReplacer::create(),
        );
    }

    public function fixInvalidXmlSymbols(string $filePath): void
    {
        $dirPath = APPLICATION_PATH . '/../data/TmxImportPreprocessing/';
        if (! is_dir($dirPath) && ! mkdir($dirPath) && ! is_dir($dirPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirPath));
        }
        $outputFilename = tempnam($dirPath, 'tmxfix_');

        // Process file in chunks to optimize memory usage
        $inputHandle = fopen($filePath, 'rb');
        $outputHandle = fopen($outputFilename, 'wb');

        if (! $inputHandle || ! $outputHandle) {
            throw new \RuntimeException("Error: Cannot open files for processing.");
        }

        $insideTuv = false;

        while ($line = fgets($inputHandle)) {
            $oneLineTuv = strrpos($line, '<tuv') && strrpos($line, '</tuv>');
            $insideTuv = $oneLineTuv || strrpos($line, '<tuv') || $insideTuv;

            if (! $oneLineTuv && strrpos($line, '</tuv>')) {
                $insideTuv = false;
            }

            // Replace character references in the content
            $processed = $this->characterReplacer->replaceInvalidXmlCharacters($line, preserve: $insideTuv);

            if ($oneLineTuv) {
                $insideTuv = false;
            }

            $fixHtmlEntitiesCallback = function (array $matches) {
                return htmlentities(html_entity_decode($matches[0]), ENT_XML1, 'UTF-8');
            };

            $htmlEntitiesPattern = '/&[a-zA-Z0-9#]+;/';

            $processed = preg_replace_callback($htmlEntitiesPattern, $fixHtmlEntitiesCallback, $processed);

            // Write processed content to output
            fwrite($outputHandle, $processed);
        }

        if (! feof($inputHandle)) {
            throw new \RuntimeException('File was not read completely.');
        }

        fclose($inputHandle);
        fclose($outputHandle);

        rename($outputFilename, $filePath);

        if (file_exists($outputFilename)) {
            throw new \RuntimeException("Failed to rename temporary file to original file path.");
        }
    }
}
