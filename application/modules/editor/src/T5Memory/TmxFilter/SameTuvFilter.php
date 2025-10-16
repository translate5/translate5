<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory\TmxFilter;

use MittagQI\Translate5\T5Memory\Exception\TmxFilterException;
use XMLReader;

class SameTuvFilter
{
    public static function create(): self
    {
        return new self();
    }

    public function filter(string $tmxFile): void
    {
        $reader = new XMLReader();
        if (! $reader->open($tmxFile)) {
            throw new TmxFilterException('Could not open TMX file ' . $tmxFile);
        }

        $resultingFile = basename($tmxFile, '.tmx') . '.filtered.tmx';
        $filterFolder = APPLICATION_DATA . '/tmx-filter/' . bin2hex(random_bytes(8));

        if (! @mkdir($filterFolder, 0777, true) && ! is_dir($filterFolder)) {
            throw new TmxFilterException('Could not create temporary folder ' . $filterFolder);
        }
        $path = $filterFolder . '/' . $resultingFile;

        file_put_contents(
            $path,
            '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL,
            FILE_APPEND
        );

        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        /** @var array<string, array{timestamp: int, tu: string}> $segments */
        $segments = [];
        $sourceLang = null;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                file_put_contents(
                    $path,
                    '<tmx version="1.4">' . PHP_EOL . $reader->readOuterXML() . PHP_EOL . '<body>' . PHP_EOL,
                    FILE_APPEND
                );

                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                $author = '';
                $date = 0;
                $docname = '';
                $context = '';
                $sourceSegment = '';
                $targetSegment = '';

                $tu = $reader->readOuterXML();
                $xml = XMLReader::XML($tu);

                if (is_bool($xml)) {
                    continue;
                }

                while ($xml->read()) {
                    if ($xml->nodeType !== XMLReader::ELEMENT) {
                        continue;
                    }

                    if ($xml->name === 'prop') {
                        if ($xml->getAttribute('type') === 'tmgr:docname') {
                            $docname = $xml->readInnerXml();
                        }

                        if ($xml->getAttribute('type') === 'tmgr:context') {
                            $context = $xml->readInnerXml();
                        }

                        continue;
                    }

                    if ($xml->name !== 'tuv') {
                        continue;
                    }

                    $lang = strtolower($xml->getAttribute('xml:lang'));

                    if (null === $sourceLang) {
                        $sourceLang = $lang;
                    }

                    $segment = str_replace(['<seg>', '</seg>'], '', trim($xml->readInnerXml()));

                    if (strtolower($sourceLang) === $lang) {
                        $sourceSegment = $segment;
                    } else {
                        $targetSegment = $segment;
                    }
                }

                if ($reader->hasAttributes) {
                    while ($reader->moveToNextAttribute()) {
                        // @phpstan-ignore-next-line
                        if ($reader->name === 'creationdate') {
                            $date = strtotime($reader->value);
                        }

                        // @phpstan-ignore-next-line
                        if ($reader->name === 'creationid') {
                            $author = $reader->value;
                        }
                    }
                }

                $hashParts = [
                    $sourceSegment,
                    $author,
                    $docname,
                    $context,
                    $targetSegment,
                ];

                $hash = md5(implode('|', $hashParts));

                if (isset($segments[$hash]) && $segments[$hash]['timestamp'] >= $date) {
                    continue;
                }

                $segments[$hash] = [
                    'timestamp' => $date,
                    'tu' => gzcompress($tu),
                ];
            }
        }

        foreach ($segments as ['timestamp' => $date, 'tu' => $tu]) {
            file_put_contents(
                $path,
                gzuncompress($tu) . PHP_EOL,
                FILE_APPEND
            );
        }

        file_put_contents(
            $path,
            '</body>' . PHP_EOL . '</tmx>',
            FILE_APPEND
        );

        unlink($tmxFile);
        rename($path, $tmxFile);
        rmdir($filterFolder);
        error_reporting($errorLevel);
    }
}
