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
 * Exchanges the strings replaced in the PHP-files in the JavaScript-code - where they are prefixed with "#UT#"
 */
class JavaScriptExchanger extends PhpExchanger
{
    public const STRING_PREFIX = '#UT#';

    /**
     * Creates the exchangedStrings of the Code-Exchange as prefixed map as expected in the JavaScript-files
     * @param array<string, string> $sourceMap
     * @return array<string, string>
     */
    public static function createSourceMap(array $sourceMap): array
    {
        $jsMap = [];
        foreach ($sourceMap as $source => $target) {
            $jsMap[self::STRING_PREFIX . $source] = self::STRING_PREFIX . $target;
        }
        // manual FIX for a hackery in one JS file ...
        $jsMap['#UT#Originalformat, übersetzt/lektoriert'] = '#UT#Original format, translated/reviewed';

        return $jsMap;
    }

    protected string $regexSingleQuoted = "'#UT#[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";

    /**
     * Extractor for argument delimited by single quotes
     * see https://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
     */
    protected string $regexDoubleQuoted = '"#UT#[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';

    /**
     * Exchanges the extracted string according to the given source-map
     * Does not count anything or generates errors, in JavaScript-files we replace only what we can and that's it
     * @param array<string, string> $sourceMap must be included with all the whitespace present in the original content!
     * @throws \Exception
     * @throws FileWriteException
     * @throws \ZfExtended_Exception
     */
    public function exchange(array $sourceMap, bool $doWriteFile = false): bool
    {
        $content = $toSave = file_get_contents($this->absoluteFilePath);
        $doSave = false;
        $this->exchangedStrings = [];

        if ($content === false) {
            throw new \ZfExtended_Exception('Could not read file ' . $this->absoluteFilePath);
        }

        if (! str_contains($content, self::STRING_PREFIX)) {
            return false;
        }

        // find matches for the known translation-function signatures
        $regexes = [
            "'" => $this->regexSingleQuoted,
            '"' => $this->regexDoubleQuoted,
        ];
        foreach ($regexes as $quote => $regex) {
            $content = preg_replace_callback(
                // find the function-signature with the current regex or a concatenated repitition
                // of the curret regex as argument
                '~[:=(]\s*(' . $regex . ')(\s*\.\s*' . $regex . ')*~s',
                function ($matches) use (
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
                            // crucial: exclude matches being replaced with the already existing ...
                            if ($exists && $string !== $sourceMap[$string]) {
                                $searches = $parts;
                                $joins = $this->evaluateJoins($matches[0], $parts, $quote);
                                $replacements = $this->splitStringToEqualLengthParts($sourceMap[$string], count($parts));
                            } elseif (! $exists) {
                                $this->brokenMatches[] =
                                    'JS string not found in replaced strings: “' . $string . '” in ' .
                                    $this->absoluteFilePath;
                            }
                        }
                    } elseif ($numMatches > 1) {
                        // simple single argument
                        $string = $this->prepareMatch($matches[1], $quote);
                        $exists = array_key_exists($string, $sourceMap);
                        // crucial: exclude matches being replaced with the already existing ...
                        if ($exists && $string !== $sourceMap[$string]) {
                            $searches = [$string];
                            $replacements = [$sourceMap[$string]];
                        } elseif (! $exists) {
                            $this->brokenMatches[] =
                                'JS string not found in localizations: “' . $string . '” in ' .
                                $this->absoluteFilePath;
                        }
                    }
                    if (count($replacements) > 0) {
                        $joined = join('', $searches);
                        if (! array_key_exists($joined, $this->exchangedStrings) &&
                            $joined !== join('', $replacements)
                        ) {
                            $replaced = self::searchReplaceInCode(
                                $toSave,
                                $searches,
                                $replacements,
                                $joins,
                                $quote
                            );
                            if ($replaced < 1) {
                                $this->brokenMatches[] =
                                    'Failed to replace “' . $joined . '” in ' . $this->absoluteFilePath;
                            } else {
                                $doSave = true;
                                $this->exchangedStrings[$joined] = 1;
                            }
                        }
                    }

                    return '\'REPLACED_STRING\'';
                },
                $content
            );
        }

        // overwrite if anything was changed
        if ($doSave && $doWriteFile) {
            if (file_put_contents($this->absoluteFilePath, $toSave) === false) {
                throw new FileWriteException($this->absoluteFilePath);
            }
        }

        return $doSave;
    }
}
