<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\L10n;

use MittagQI\ZfExtended\FileWriteException;

/**
 * Exchanges the PHP-strings in the source-code, singular & multipart strings
 */
class PhpExchanger extends PhpExtractor
{
    public const bool REPORT_MULTI_RPLACEMENTS = false;

    /**
     * Checks an extracted string if it seems a valid translateable string
     * UGLY: There are many special cases that actually must not be changed
     */
    public static function seemsValidTranslation(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }
        if (str_starts_with($text, 'T5::') || str_starts_with($text, 'translate5-')) {
            return false;
        }

        return true;
    }

    /**
     * searches/replaces the given contents in PHP-content.
     * If these are multipart-strings, the joines between the parts (usually " . ")
     * @throws \Exception
     */
    public static function searchReplaceInCode(
        string &$content,
        array $searches,
        array $replacements,
        array $joins,
        string $quote
    ): int {
        if (count($searches) !== count($replacements)) {
            throw new \Exception('number searches must match number replacements');
        }
        $search = self::joinMultipartString($searches, $joins, $quote);
        $numMatches = substr_count($content, $search);
        if ($numMatches > 0) {
            $replace = self::joinMultipartString($replacements, $joins, '\'');
            $content = str_replace($search, $replace, $content);
        }
        // very special: some strings with double-quotes use "\n" as newline ...
        if ($numMatches === 0 && $quote === '"' && str_contains($search, "\n")) {
            $search = str_replace("\n", '\n', $search);
            $numMatches = substr_count($content, $search);
            if ($numMatches > 0) {
                $replace = self::joinMultipartString($replacements, $joins, '\'');
                $content = str_replace($search, $replace, $content);
            }
        }

        return $numMatches;
    }

    /**
     * Recreates a concatenated string construct like '...' . '...' . '...'
     * @throws \Exception
     */
    public static function joinMultipartString(array $strings, array $joins, $quote): string
    {
        if (count($strings) > count($joins) + 1) {
            throw new \Exception('number joins must be at least 1 less than number strings');
        }
        $string = '';
        for ($i = 0; $i < count($strings); $i++) {
            if ($i > 0) {
                $string .= $joins[$i - 1];
            }
            $string .= ($quote === '\'') ?
                self::toSingleQuotedString($strings[$i]) :
                self::toDoubleQuotedString($strings[$i]);
        }

        return $string;
    }

    public static function toSingleQuotedString(string $string): string
    {
        return '\'' . str_replace('\'', '\\\'', $string) . '\'';
    }

    public static function toDoubleQuotedString(string $string): string
    {
        return '"' . str_replace('"', '\\"', $string) . '"';
    }

    public static function prepareForDebug(array $strings): string
    {
        $string = implode('', $strings);
        if (mb_strlen($string) > 100) {
            $string = substr($string, 0, 97) . ' ...';
        }

        return str_replace("\n", '\n', $string);
    }

    protected array $exchangedStrings = [];

    /**
     * Exchanges the extracted string according to the given source-map
     * @param array<string, string> $sourceMap must be included with all the whitespace present in the original content!
     * @throws \Exception
     * @throws FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function exchange(array $sourceMap, bool $doWriteFile = false): bool
    {
        // no need to process files of the localization API itself
        if ($this->isLocalizationApiFile()) {
            return false;
        }

        $content = $toSave = file_get_contents($this->absoluteFilePath);
        $doSave = false;
        $this->exchangedStrings = [];

        if ($content === false) {
            throw new \ZfExtended_Exception('Could not read file ' . $this->absoluteFilePath);
        }

        // first, echange PHP localization attributes - only for php files
        if (str_ends_with($this->absoluteFilePath, '.php')) {
            $attributeExchanger = new PhpAttributeExchanger($content, $this->absoluteFilePath);
            if ($attributeExchanger->exchange($sourceMap)) {
                $toSave = $attributeExchanger->getContent();
                $this->exchangedStrings = $attributeExchanger->getExchangedStrings();
                $doSave = true;
            }
            $this->brokenMatches = $attributeExchanger->getBrokenMatches();
        }

        // find matches for the known translation-function signatures
        $regexes = [
            "'" => $this->regexSingleQuoted,
            '"' => $this->regexDoubleQuoted,
        ];
        foreach ($this->signatures as $signature) {
            foreach ($regexes as $quote => $regex) {
                $content = preg_replace_callback(
                    // find the function-signature with the current regex or a concatenated repitition
                    // of the curret regex as argument
                    '~' . $signature['before'] . '\s*(' . $regex . ')(\s*\.\s*' . $regex . ')*\s*' . $signature['after'] . '~s',
                    function ($matches) use (
                        $signature,
                        $quote,
                        $regex,
                        $sourceMap,
                        &$doSave,
                        &$toSave
                    ) {
                        $numMatches = count($matches);
                        $searches = [];
                        $replacements = [];
                        $joins = [];
                        if ($numMatches > 2) {
                            // since we capture only the first and last match, we need to recapture
                            // PREG_SET_ORDER -> result will be an array of arrays with the parts of the string
                            $parts = [];
                            $innerMatches = [];
                            preg_match_all('~' . $regex . '~s', $matches[0], $innerMatches, PREG_SET_ORDER);
                            foreach ($innerMatches as $match) {
                                $parts[] = $this->prepareMatch($match[0], $quote);
                            }
                            if (count($parts) > 0) {
                                $string = join('', $parts);
                                $exists = array_key_exists($string, $sourceMap);
                                $valid = self::seemsValidTranslation($string);
                                // crucial: exclude matches being replaced with the already existing ...
                                if ($valid && $exists && $string !== $sourceMap[$string]) {
                                    $searches = $parts;
                                    $joins = $this->evaluateJoins($matches[0], $parts, $quote);
                                    $replacements = $this->splitStringToEqualLengthParts($sourceMap[$string], count($parts));
                                } elseif ($valid && ! $exists) {
                                    throw new \Exception(
                                        'STRING "' . $string . '" not found in source-map, file ' .
                                        $this->absoluteFilePath
                                    );
                                }
                            }
                        } elseif ($numMatches > 1) {
                            // simple single argument
                            $string = $this->prepareMatch($matches[1], $quote);
                            $exists = array_key_exists($string, $sourceMap);
                            $valid = self::seemsValidTranslation($string);
                            // crucial: exclude matches being replaced with the already existing ...
                            if ($valid && $exists && $string !== $sourceMap[$string]) {
                                $searches = [$string];
                                $replacements = [$sourceMap[$string]];
                            } elseif ($valid && ! $exists) {
                                throw new \Exception(
                                    'STRING "' . $string . '" not found in source-map, file ' .
                                    $this->absoluteFilePath
                                );
                            }
                        }
                        if (count($replacements) > 0) {
                            $joined = join('', $searches);
                            if (! array_key_exists($joined, $this->exchangedStrings)) {
                                $replaced = self::searchReplaceInCode(
                                    $toSave,
                                    $searches,
                                    $replacements,
                                    $joins,
                                    $quote
                                );
                                if ($replaced === 0) {
                                    throw new \Exception(
                                        'STRING "' . self::joinMultipartString($searches, $joins, $quote) .
                                        '" extracted but not found in file ' . $this->absoluteFilePath
                                    );
                                } elseif ($replaced > 1) {
                                    if (self::REPORT_MULTI_RPLACEMENTS) { // @phpstan-ignore-line
                                        // happens frequently
                                        $this->brokenMatches[] =
                                            'String "' . self::prepareForDebug($searches) .
                                            '" replaced more than once in file ' . $this->absoluteFilePath;
                                    }
                                }
                                $doSave = true;
                                $this->exchangedStrings[$joined] = 1;
                            }
                        }

                        return $signature['replacement'];
                    },
                    $content
                );
            }
        }

        // overwrite if anything was changed
        if ($doSave && $doWriteFile) {
            if (file_put_contents($this->absoluteFilePath, $toSave) === false) {
                throw new FileWriteException($this->absoluteFilePath);
            }
        }

        return $doSave;
    }

    /**
     * @return string[]
     */
    public function getExchangedStrings(): array
    {
        return $this->exchangedStrings;
    }

    /**
     * Extracts the inbetween parts of a multipart string
     */
    protected function evaluateJoins(string $string, array $parts, string $quote): array
    {
        $joins = [];
        $isFirst = true;
        foreach ($parts as $part) {
            $leftRight = explode($part, $string, 2);
            $string = $leftRight[1];
            if ($isFirst) {
                // first left part is what is before the string
                $isFirst = false;
            } else {
                // crucial: remove the quoting
                $joins[] = trim($leftRight[0], $quote);
            }
        }

        return $joins;
    }

    /**
     * Extracts the inbetween parts of a multipart string
     */
    protected function splitStringToEqualLengthParts(string $replacement, int $numPparts): array
    {
        if ($numPparts === 1) {
            return [$replacement];
        }
        /** @var array<int, array{ 0: string, 1:int }> $splitMap */
        $splitMap = preg_split('/\s+/', $replacement, -1, PREG_SPLIT_OFFSET_CAPTURE);
        /* gives a structure like
        0 => [
            0 => string
            1 => index
         ]  ...
        */
        // we need one more split than we want parts
        $numSplits = count($splitMap);
        if ($numSplits > ($numPparts + 1)) {
            // if we have to many splits, we reduce them to the matching ones
            $partLen = round(mb_strlen($replacement) / $numPparts);
            $nextLen = $partLen;
            $lastPos = 0;
            $oldMap = $splitMap;
            $splitMap = [$oldMap[0]];
            for ($i = 1; $i < $numSplits; $i++) {
                // add splits up to num needed parts ...
                if (count($splitMap) < ($numPparts + 1)) {
                    if ($i === $numSplits - 1) {
                        // may we are the last split and do not have enough, add it
                        $splitMap[] = $oldMap[$i];
                    } elseif ($oldMap[$i][1] > $lastPos && $oldMap[$i + 1][1] >= $nextLen) {
                        // when split covers avg length of part or is already after the wanted length, add the nearest
                        if ($nextLen - $oldMap[$i][1] <= $oldMap[$i + 1][1] - $nextLen) {
                            $splitMap[] = $oldMap[$i];
                            $lastPos = $oldMap[$i][1];
                        } else {
                            $splitMap[] = $oldMap[$i + 1];
                            $lastPos = $oldMap[$i + 1][1];
                        }
                        $nextLen += $partLen;
                    }
                }
            }
            $numSplits = count($splitMap);
        }
        if ($numPparts < 2) {
            return [$replacement];
        }
        // now generate the parts from the split map
        $parts = [];
        for ($i = 1; $i < $numSplits; $i++) {
            if ($i === 1) {
                $parts[] = substr($replacement, 0, $splitMap[$i][1]);
            } elseif ($i === $numSplits - 1) {
                $start = $splitMap[$i - 1][1];
                $parts[] = substr($replacement, $start);
            } else {
                $start = $splitMap[$i - 1][1];
                $end = $splitMap[$i][1];
                $parts[] = substr($replacement, $start, $end - $start);
            }
        }

        return $parts;
    }
}
