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

namespace MittagQI\Translate5\Plugins\TMMaintenance\TmxFilter;

use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\Exception\TmxFilterException;
use XMLReader;

class SearchFilter
{
    private const EXACT_SEARCH = 'exact';

    public static function create(): self
    {
        return new self();
    }

    public function filter(string $tmxFile, SearchDTO $searchDTO): void
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

        $sourceLang = null;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                $header = $reader->readOuterXML();
                file_put_contents(
                    $path,
                    '<tmx version="1.4">' . PHP_EOL . $header . PHP_EOL . '<body>' . PHP_EOL,
                    FILE_APPEND
                );

                if (! preg_match('/srclang="(.+)"/U', $header, $matches)) {
                    throw new TmxFilterException('TMX header has no srclang attribute');
                }

                $sourceLang = $matches[1];

                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'tu') {
                if (null === $sourceLang) {
                    throw new TmxFilterException('TMX header not found before TUs');
                }

                $tu = $reader->readOuterXML();

                if ($this->tuShouldBeDropped($tu, $reader, $searchDTO, $sourceLang)) {
                    continue;
                }

                file_put_contents(
                    $path,
                    $tu . PHP_EOL,
                    FILE_APPEND
                );
            }
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

    private function tuShouldBeDropped(
        string $tu,
        XMLReader $reader,
        SearchDTO $searchDTO,
        string $sourceLang
    ): bool {
        $author = '';
        $date = '';
        $docname = '';
        $context = '';
        $sourceSegment = '';
        $targetSegment = '';

        $xml = XMLReader::XML($tu);

        if (is_bool($xml)) {
            return false;
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

            $segment = str_replace(['<seg>', '</seg>'], '', trim($xml->readInnerXml()));

            if (strtolower($sourceLang) === $lang) {
                $sourceSegment = $segment;
            } else {
                $targetSegment = $segment;
            }
        }

        if ($reader->hasAttributes) {
            while ($reader->moveToNextAttribute()) {
                if ($reader->name === 'creationdate') {
                    $date = strtotime($reader->value);
                }

                if ($reader->name === 'creationid') {
                    $author = $reader->value;
                }
            }
        }

        if ('' !== $searchDTO->author) {
            $searchAuthor = $searchDTO->author;

            if (! $searchDTO->caseSensitive) {
                $author = strtolower($author);
                $searchAuthor = strtolower($searchAuthor);
            }

            if ($searchDTO->authorMode === self::EXACT_SEARCH && $author === $searchAuthor) {
                return true;
            }

            return str_contains($author, $searchAuthor);
        }

        if ('' !== $searchDTO->document) {
            $searchDocument = $searchDTO->document;

            if (! $searchDTO->caseSensitive) {
                $docname = strtolower($docname);
                $searchDocument = strtolower($searchDocument);
            }

            if ($searchDTO->documentMode === self::EXACT_SEARCH && $docname === $searchDocument) {
                return true;
            }

            return str_contains($docname, $searchDocument);
        }

        if ('' !== $searchDTO->context) {
            $searchContext = $searchDTO->context;

            if (! $searchDTO->caseSensitive) {
                $context = strtolower($context);
                $searchContext = strtolower($searchContext);
            }

            if ($searchDTO->contextMode === self::EXACT_SEARCH && $context === $searchContext) {
                return true;
            }

            return str_contains($context, $searchContext);
        }

        if ('' !== $searchDTO->source) {
            $searchSource = $searchDTO->source;

            if (! $searchDTO->caseSensitive) {
                $sourceSegment = strtolower($sourceSegment);
                $searchSource = strtolower($searchSource);
            }

            if ($searchDTO->sourceMode === self::EXACT_SEARCH && $sourceSegment === $searchSource) {
                return true;
            }

            return str_contains($sourceSegment, $searchSource);
        }

        if ('' !== $searchDTO->target) {
            $searchTarget = $searchDTO->target;

            if (! $searchDTO->caseSensitive) {
                $targetSegment = strtolower($targetSegment);
                $searchTarget = strtolower($searchTarget);
            }

            if ($searchDTO->targetMode === self::EXACT_SEARCH && $targetSegment === $searchTarget) {
                return true;
            }

            return str_contains($targetSegment, $searchTarget);
        }

        if (0 !== $searchDTO->creationDateFrom && $date <= $searchDTO->creationDateFrom) {
            return false;
        }

        if (0 !== $searchDTO->creationDateTo && $date >= $searchDTO->creationDateTo) {
            return false;
        }

        return true;
    }
}
