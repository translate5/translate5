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

namespace MittagQI\Translate5\TMX\Filter;

use Generator;
use MittagQI\Translate5\T5Memory\DTO\TmxFilterOptions;
use MittagQI\Translate5\T5Memory\Exception\TmxFilterException;
use MittagQI\Translate5\TMX\Exception\TmxUtilsException;
use MittagQI\Translate5\TMX\TmxUtilsWrapper;
use XMLReader;
use XMLWriter;

class TmxFilter
{
    private const int TMX_BEGIN = 1;

    private const int TMX_TU = 2;

    private const int TMX_END = 3;

    public function __construct(
        private readonly TmxUtilsWrapper $tmxUtilsWrapper,
        private readonly \Zend_Config $config,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TmxUtilsWrapper::create(),
            \Zend_Registry::get('config'),
        );
    }

    public function filterFile(string $tmxFile, TmxFilterOptions $filterOptions): void
    {
        $resultingFile = dirname($tmxFile) . '/' . basename($tmxFile, '.tmx') . '.filtered.tmx';

        if ($this->config->runtimeOptions->LanguageResources->t5memory->useTmxUtilsFilter) {
            $resultingFile = dirname($tmxFile) . '/' . basename($tmxFile, '.tmx') . '.filtered.tmx';

            try {
                $this->tmxUtilsWrapper->filter($tmxFile, $resultingFile, $filterOptions);
            } catch (TmxUtilsException $e) {
                throw new TmxFilterException($e->getMessage());
            }

            rename($resultingFile, $tmxFile);

            return;
        }

        $writer = new XMLWriter();

        if (! $writer->openURI($resultingFile)) {
            throw new TmxFilterException('Unable to open file: ' . $resultingFile);
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);

        foreach ($this->filterWithPhp($tmxFile, $filterOptions) as [$node, $isTu]) {
            $writer->writeRaw($node);
        }

        $writer->flush();

        rename($resultingFile, $tmxFile);
    }

    /**
     * @return iterable<array{string, bool}> Yields node as string and a bool indicating whether it is a TU or not
     * @throws TmxFilterException
     */
    public function filter(string $tmxFile, TmxFilterOptions $filterOptions): iterable
    {
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        if ($this->config->runtimeOptions->LanguageResources->t5memory?->useTmxUtilsFilter) {
            $resultingFile = dirname($tmxFile) . '/' . basename($tmxFile, '.tmx') . '.filtered.tmx';

            try {
                $this->tmxUtilsWrapper->filter($tmxFile, $resultingFile, $filterOptions, false);
            } catch (TmxUtilsException $e) {
                error_reporting($errorLevel);

                throw new TmxFilterException($e->getMessage());
            }

            foreach ($this->iterateThroughTmx($resultingFile)  as [$node, $type]) {
                yield [$node, self::TMX_TU === $type];
            }

            unlink($resultingFile);
            error_reporting($errorLevel);

            return;
        }

        foreach ($this->filterWithPhp($tmxFile, $filterOptions) as [$node, $isTu]) {
            yield [$node, $isTu];
        }

        error_reporting($errorLevel);
    }

    private function filterWithPhp(string $tmxFile, TmxFilterOptions $filterOptions): iterable
    {
        $segments = [];
        $sourceLang = null;

        foreach ($this->iterateThroughTmx($tmxFile) as [$node, $type]) {
            if (self::TMX_BEGIN === $type) {
                yield [$node, false];

                continue;
            }

            if (self::TMX_TU === $type) {
                $author = '';
                $date = 0;
                $docname = '';
                $context = '-';
                $sourceSegment = '';
                $targetSegment = '';

                $tu = $node;
                $xml = XMLReader::XML($tu);

                if (is_bool($xml)) {
                    continue;
                }

                while ($xml->read()) {
                    if ($xml->nodeType !== XMLReader::ELEMENT) {
                        continue;
                    }

                    if ($xml->name === 'tu') {
                        if ($xml->hasAttributes) {
                            while ($xml->moveToNextAttribute()) {
                                // @phpstan-ignore-next-line
                                if ($xml->name === 'creationdate') {
                                    $date = strtotime($xml->value);
                                }

                                // @phpstan-ignore-next-line
                                if ($xml->name === 'creationid') {
                                    $author = strtoupper($xml->value);
                                }
                            }
                        }

                        continue;
                    }

                    if ($xml->name === 'prop') {
                        // @phpstan-ignore-next-line
                        if ($xml->getAttribute('type') === 'tmgr:docname') {
                            $docname = $xml->readInnerXml();
                        }

                        // @phpstan-ignore-next-line
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

                $hashParts = [
                    $sourceSegment,
                ];

                if (! $filterOptions->skipAuthor) {
                    $hashParts[] = $author;
                }
                if (! $filterOptions->skipDocument) {
                    $hashParts[] = $docname;
                }
                if (! $filterOptions->skipContext) {
                    $hashParts[] = $context;
                }
                if ($filterOptions->preserveTargets) {
                    $hashParts[] = $targetSegment;
                }

                $hash = md5(implode('|', $hashParts));

                // @phpstan-ignore-next-line
                if (isset($segments[$hash]) && $segments[$hash]['timestamp'] >= $date) {
                    continue;
                }

                $segments[$hash] = [
                    'timestamp' => $date,
                    'tu' => gzcompress($tu),
                ];
            }
        }

        usort($segments, static fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        foreach ($segments as ['timestamp' => $date, 'tu' => $tu]) {
            yield [
                gzuncompress($tu),
                true,
            ];
        }

        yield ['</body>' . PHP_EOL . '</tmx>', false];
    }

    /**
     * @throws TmxFilterException
     */
    private function getReader(string $tmxFile): XMLReader
    {
        $reader = XMLReader::open($tmxFile, flags: LIBXML_NONET);
        if (! $reader) {
            throw new TmxFilterException('Could not open TMX file ' . $tmxFile);
        }

        return $reader;
    }

    private function iterateThroughTmx(string $tmxFile): Generator
    {
        $errorLevel = error_reporting();
        error_reporting($errorLevel & ~E_WARNING);

        try {
            $reader = $this->getReader($tmxFile);
        } catch (TmxFilterException $e) {
            error_reporting($errorLevel);

            throw $e;
        }

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'header') {
                yield [
                    '<tmx version="1.4">' . PHP_EOL . $reader->readOuterXML() . PHP_EOL . '<body>' . PHP_EOL,
                    self::TMX_BEGIN,
                ];

                continue;
            }

            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name === 'tu') {
                yield [
                    $reader->readOuterXML() . PHP_EOL,
                    self::TMX_TU,
                ];
            }
        }

        error_reporting($errorLevel);

        yield ['</body>' . PHP_EOL . '</tmx>', self::TMX_END];
    }
}
