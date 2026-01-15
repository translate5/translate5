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

/**
 * Extract string-arguments from known translation-functions
 * Will extract single strings or concatenated strings that need to have an identical quote
 * so it wil find '...' . '...' but not '...' . "..."
 */
class PhpExtractor
{
    /**
     * Extractor for argument delimited by single quotes
     * see https://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
     */
    private string $regexSingleQuoted = "'[^'\\\\]*(?:\\\\.[^'\\\\]*)*'";

    /**
     * Extractor for argument delimited by single quotes
     * see https://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
     */
    private string $regexDoubleQuoted = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';

    /**
     * Defines the function-signatures we are searching for
     */
    private array $signatures = [
        // calls like Localization::trans(...)
        [
            'before' => 'Localization\s*::\s*trans\s*\(',
            'after' => '[,)]{1}',
            'replacement' => 'Test::test()',
        ],
        // calls like translate->_(...)
        [
            'before' => '->\s*_\s*\(',
            'after' => '[,)]{1}',
            'replacement' => '->test()',
        ],
        // calls like view->templateApply(...)
        [
            'before' => '->\s*templateApply\s*\(',
            'after' => '[,)]{1}',
            'replacement' => '->testApply()',
        ],
    ];

    private array $brokenMatches = [];

    public function __construct(
        private readonly string $absoluteFilePath
    ) {
    }

    /**
     * Extracts the localized source-strings from a PHP or PHTML file
     * @throws \ZfExtended_Exception
     */
    public function extract(): array
    {
        $strings = [];
        $content = file_get_contents($this->absoluteFilePath);

        if ($content === false) {
            throw new \ZfExtended_Exception('Could not read file ' . $this->absoluteFilePath);
        }

        // first, extract PHP localization attributes - only in php files
        if (str_ends_with($this->absoluteFilePath, '.php')) {
            $attributeExtractor = new PhpAttributeExtractor($content, $this->absoluteFilePath);
            $strings = $attributeExtractor->extract();
            $this->brokenMatches = $attributeExtractor->getBrokenMatches();
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
                    function ($matches) use (&$strings, $signature, $quote, $regex) {
                        $numMatches = count($matches);
                        $string = null;
                        if ($numMatches > 2) {
                            // since we capture only the first and last match, we need to recapture
                            // PREG_SET_ORDER -> result will be an array of arrays with the parts of the string
                            $string = '';
                            $innerMatches = [];
                            preg_match_all('~' . $regex . '~s', $matches[0], $innerMatches, PREG_SET_ORDER);
                            foreach ($innerMatches as $match) {
                                $string .= $this->prepareMatch($match[0], $quote);
                            }
                        } elseif ($numMatches > 1) {
                            // simple single argument
                            $string = $this->prepareMatch($matches[1], $quote);
                        }
                        if ($string !== null) {
                            $strings[] = $string;
                        }

                        return $signature['replacement'];
                    },
                    $content
                );
            }
        }

        // find misconfigured (= not yet catched) matches for the known translation-function signatures
        foreach ($this->signatures as $signature) {
            preg_replace_callback(
                '~' . $signature['before'] . '\s*(.*?)\s*' . $signature['after'] . '~s',
                function ($matches) {
                    if ($matches[1] !== '...') { // ignoring comments in definition of signatures ;-)
                        $this->brokenMatches[] = $matches[0] . ' in file ' . $this->absoluteFilePath;
                    }

                    return 'misconfigured()';
                },
                $content
            );
        }

        return array_values(array_unique($strings));
    }

    /**
     * Retrieves files where matches were found that contained no strings - but presumably variables or other stuff
     */
    public function getBrokenMatches(): array
    {
        return $this->brokenMatches;
    }

    private function prepareMatch(string $match, string $quote): ?string
    {
        $string = trim(trim($match), $quote);
        // important: in double-quoted strings, we need to expand them programmatically
        if ($quote === '"') {
            $string = str_replace(['\n', '\r', '\t'], ["\n", "\r", "\t"], $string);
        }
        // in case the string only contains markup & placeholders, it must not be translated ...
        if (trim(strip_tags(preg_replace('~{[a-zA-Z0-9.]+}~', '', $string))) === '') {
            return null;
        }

        return $string;
    }
}
