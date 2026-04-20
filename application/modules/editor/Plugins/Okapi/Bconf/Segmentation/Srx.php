<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation;

use DOMElement;
use MittagQI\Translate5\Plugins\Okapi\Bconf\ResourceFile;
use ZfExtended_Dom;

/**
 * Class representing a SRX file
 * A SRX is an xml with a defined structure containing nodes with language specific RegEx rules
 * for more documentation, see Segmentation
 */
final class Srx extends ResourceFile
{
    public const EXTENSION = 'srx';

    /**
     * Maps Across language-Settings language-codes to OKAPI Language Names
     */
    public const array ACROSS_OKAPI_LANG_MAP = [
        'af' => 'Afrikaans',
        'ak' => 'Twi',
        'ar' => 'Arabic',
        'az' => 'Azerbaijani',
        'be' => 'Belarusian',
        'bg' => 'Bulgarian',
        'br' => 'Breton',
        'ca' => 'Catalan',
        'cs' => 'Czech',
        'da' => 'Danish',
        'de' => 'German',
        'el' => 'Greek',
        'en' => 'English',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'fi' => 'Finnish',
        'fo' => 'Faroese',
        'fr' => 'French',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hr' => 'Croatian',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'id' => 'Indonesian',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'ka' => 'Georgian',
        'kk' => 'Kazakh',
        'ko' => 'Korean',
        'kok' => 'Konkani',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'mk' => 'Macedonian',
        'mr' => 'Marathi',
        'ms' => 'Malaysian',
        'nl' => 'Dutch',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sa' => 'Sanskrit',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'tt' => 'Tatar',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'vi' => 'Vietnamese',
        'zh-Hans' => 'Chinese',
    ];

    // a SRX is generally a XML variant
    protected string $mime = 'text/xml';

    /**
     * Validates a SRX
     * TODO FIXME: this basic validation can be improved
     */
    public function validate(bool $forImport = false): bool
    {
        $parser = new ZfExtended_Dom();
        $parser->loadXML($this->content);
        // sloppy checking here as we do not know how tolerant longhorn actually is
        if ($parser->isValid()) {
            $rootTag = strtolower($parser->firstChild?->tagName);
            if ($rootTag === 'srx') {
                return true;
            } else {
                // DEBUG
                if ($this->doDebug) {
                    error_log('SRX FILE ' . basename($this->path) . ' is invalid: No "srx" root tag found');
                }
                $this->validationError = 'No "srx" root tag found';
            }
        } else {
            // DEBUG
            if ($this->doDebug) {
                error_log('SRX FILE ' . basename($this->path) . ' is invalid: Invalid XML');
            }
            $this->validationError = 'Invalid XML';
        }

        return false;
    }

    /**
     * Updates the contents of a SRX
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Updates our path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Get array of [prev, next] pairs, grouped by purpose
     * [
     *     'insert' => [
     *         ['prev' => 'regex1', 'next' => 'regex2'],
     *         ['prev' => 'regex3', 'next' => 'regex4'],
     *     ],
     *     'delete' => [
     *         ['prev' => 'regex5', 'next' => 'regex6'],
     *         ['prev' => 'regex7', 'next' => 'regex8'],
     *     ]
     * ]
     */
    public function getSegmentationRules(string $rfc5646): array
    {
        // Get srx file contents and convert to php-array
        $srx = simplexml_load_string($this->getContent());
        $srx = json_encode($srx);
        $srx = json_decode($srx, true);
        $rules = [];

        // Check whether any rules exist for the given $rfc5646,
        // and if yes - get the language name, that is used within srx-file
        // to map with the segmentation rules
        foreach ($srx['body']['maprules']['languagemap'] as $languagemap) {
            $attr = $languagemap['@attributes'];
            if (preg_match('~' . $attr['languagepattern'] . '~', $rfc5646)) {
                $languagerulename = $attr['languagerulename'];
            }
        }

        // If it was not possible to find <languagemap>-node for given $rfc5646 - return false
        if (! isset($languagerulename)) {
            return $rules;
        }

        // Rules array, grouped by purpose e.g insert/delete delimiter, based on break="yes|no"
        foreach ($srx['body']['languagerules']['languagerule'] as $languagerule) {
            // If those are rules NOT for the language we need - skip
            if ($languagerule['@attributes']['languagerulename'] !== $languagerulename) {
                continue;
            }

            // Else foreach rule
            foreach ($languagerule['rule'] as $rule) {
                // If <beforebreak> and/or <afterbreak> is empty - it's represented as empty array,
                // so convert to string, else trim newlines/whitespaces
                foreach ([
                    'beforebreak' => 'prev',
                    'afterbreak' => 'next',
                ] as $node => $side) {
                    $rule[$side] = is_array($rule[$node]) ? '' : trim($rule[$node]);
                    unset($rule[$node]);
                }

                // Get purpose
                $purpose = $rule['@attributes']['break'] === 'yes' ? 'insert' : 'delete';

                // Unset @attributes-prop
                unset($rule['@attributes']);

                // Skip things we don't need
                if (preg_match('~T5-IGNORE-(START|END)~', join('', $rule))) {
                    continue;
                }

                // Collect rules
                $rules[$purpose][] = $rule;
            }
        }

        // Return rules grouped by purpose
        return $rules;
    }

    /**
     * Split given $text to segments based on array of rules given by $rules arg
     */
    public function splitTextToSegments(string $text, array $rules): array
    {
        // Prepare arrays of regexes to be used for delimiter insertion and deletion
        $rex = [];
        foreach (['insert', 'delete'] as $purpose) {
            // Define as empty array
            $rex[$purpose] = [];

            // Foreach [prev, next] regex pair
            foreach ($rules[$purpose] as $rule) {
                // Build regex that will help to insert delimiter between prev and next
                if ($purpose === 'insert') {
                    $expr = "~(?<prev>{$rule['prev']})(?<next>{$rule['next']})~u";

                    // Build regex that will help to delete delimiter, that was previously inserted between prev and next
                } else {
                    $expr = "~(?<prev>{$rule['prev']})<delimiter/>(?<next>{$rule['next']})~u";
                }

                // If it's supported by PHP's PCRE2 - append to $rex array
                if (@preg_match($expr, '') !== false) {
                    $rex[$purpose][] = $expr;
                }
            }
        }

        // Insert <delimiter/> between segments
        $text = preg_replace_callback($rex['insert'], fn ($m) => "{$m['prev']}<delimiter/>{$m['next']}", $text);

        // Delete <delimiter/> between segments, if those are, so to say, false-positives
        $text = preg_replace_callback($rex['delete'], fn ($m) => "{$m['prev']}{$m['next']}", $text);

        // ...and split
        return explode('<delimiter/>', $text);
    }

    /**
     * Adds the given Abbrevations to the given across-locale in the SRXs languagerules
     * - if the mapped locale exists in the languagerule
     * - simple existing abbrevations (that are not encoded plainly) will be filtered away
     * Returned will be null, if everything worked, or the error that occured
     */
    public function addAcrossAbbrevationsForLanguage(
        string $acrossLocale,
        array $abbrevations,
        bool $doDebug = false
    ): ?string {
        if (! array_key_exists($acrossLocale, self::ACROSS_OKAPI_LANG_MAP)) {
            $msg = 'No OKAPI language-name found for across-language “' . $acrossLocale . '”';
            if ($doDebug) {
                error_log($msg);
            }

            return $msg;
        }
        $languageName = self::ACROSS_OKAPI_LANG_MAP[$acrossLocale];
        $parser = new ZfExtended_Dom();
        $parser->loadXML($this->content);
        /** @var DOMElement $rule */
        foreach ($parser->getElementsByTagName('languagerule') as $rule) {
            if ($rule->hasAttributes() && $rule->attributes->getNamedItem('languagerulename') !== null) {
                $ruleName = $rule->attributes->getNamedItem('languagerulename')->nodeValue;
                if (strtolower($ruleName) === strtolower($languageName)) {
                    $existingAbbrevs = $this->extractAbbrevations($rule);
                    $additionalAbbrevs = [];
                    // remove trailing dot, exclude abbrevs already in the SRX
                    foreach ($abbrevations as $abbrev) {
                        $abbrev = rtrim($abbrev, '.');
                        if (! in_array($abbrev, $existingAbbrevs)) {
                            // for okapi-regex, dot has to be escaped ...
                            $additionalAbbrevs[] = str_replace('.', '\.', $abbrev);
                        } elseif ($doDebug) {
                            error_log('Abbrevation "' . $abbrev . '" already exists in "' . $languageName . '"');
                        }
                    }

                    // ugly: search/replace with regex to implant the new rules
                    $search = '~<languagerule\s+languagerulename\s*=\s*"' . $languageName . '"\s*>~';
                    $replace = '<languagerule languagerulename="' . $languageName . '">';

                    $batches = (count($additionalAbbrevs) > 50) ?
                        array_chunk($additionalAbbrevs, 50) : [$additionalAbbrevs];

                    // Special handling of German rules - which work differently due to translate5 adjustments / added rules
                    $beforeBreak = ($languageName === 'German') ? '\.' : '\.\s';
                    $afterBreak = ($languageName === 'German') ? '\s' : '';

                    foreach ($batches as $batch) {
                        $replace .=
                            "\n" . '<!-- additional abbrevations from ACROSS - ' . $acrossLocale . ' -->' . "\n" .
                            '<rule break="no">' . "\n" .
                            '<beforebreak>\b(' . implode('|', $batch) . ')' . $beforeBreak . '</beforebreak>' . "\n" .
                            '<afterbreak>' . $afterBreak . '</afterbreak>' . "\n" .
                            '</rule>';
                    }

                    $patchedContent = preg_replace($search, $replace, $this->content, 1);
                    if ($patchedContent === null || ! str_contains($patchedContent, implode('|', $batches[0]))) {
                        $msg = 'Patching apprevations to “' . $languageName . '” did not work, pattern: ' . $search;
                        if ($doDebug) {
                            error_log($msg);
                        }

                        return $msg;
                    }

                    $this->content = $patchedContent;

                    if ($doDebug) {
                        error_log('Added abbrevations to  "' . $languageName . '": ' . implode(', ', $additionalAbbrevs));
                    }

                    return null;
                }
            }
        }
        $msg = 'No language-rule found for language “' . $languageName . '”';
        if ($doDebug) {
            error_log($msg);
        }

        return $msg;
    }

    /**
     * Extracts existing no.break rules with Abbrevations from the given ruleset. These usually look like:
     *
     * <rule break="no">
     * <beforebreak>\b(Mr|Mrs|No|pp|St|no|Sr|Jr|Bros|etc|vs|esp|[Ff]ig|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Okt|Nov|Dec|PhD|al|cf|Inc|Ms|Gen|Sen|Prof|Corp|Co|Ltd)\.\s</beforebreak>
     * <afterbreak></afterbreak>
     * </rule>
     *
     * or
     *
     * <rule break="no">
     * <beforebreak>\b(Inc|Ltd|Corp|DC|US|lds|pm|am|[Nn]o|pp|p|[Ff]igs?|[Dd]ept|[Gg]ovt|[Ii]bid|comp|[Bb]ros|Dist|Co|Ph\.?D|et\b\s\bal|etc)\.</beforebreak>
     * <afterbreak>\s\P{Lu}</afterbreak>
     * </rule>
     *
     * @return string[]
     */
    private function extractAbbrevations(DOMElement $languageRules): array
    {
        $abbrevs = [];
        /** @var DOMElement $rule */
        foreach ($languageRules->getElementsByTagName('rule') as $rule) {
            if ($rule->hasAttributes() &&
                $rule->attributes->getNamedItem('break')->nodeValue === 'no' &&
                $rule->firstElementChild !== null &&
                $rule->firstElementChild->nodeName === 'beforebreak' &&
                $rule->lastElementChild !== null &&
                $rule->lastElementChild->nodeName === 'afterbreak' &&
                str_starts_with($rule->firstElementChild->nodeValue, '\b') &&
                (
                    (
                        str_ends_with($rule->firstElementChild->nodeValue, '\.\s') &&
                        $rule->lastElementChild->nodeValue === ''
                    ) || (
                        str_ends_with($rule->firstElementChild->nodeValue, '\.') &&
                        $rule->lastElementChild->nodeValue === '\s\P{Lu}'
                    ) || (
                        str_ends_with($rule->firstElementChild->nodeValue, '\.') &&
                        $rule->lastElementChild->nodeValue === '\s'
                    )
                )
            ) {
                // we found potential abbrevation-rules and now need to extract the non-RegEx abbrevs
                // remove leading \b and trailing \. or \.\s
                $regEx = substr($rule->firstElementChild->nodeValue, 2);
                if (str_ends_with($regEx, '\.\s')) {
                    $regEx = substr($regEx, 0, -4);
                } else {
                    $regEx = substr($regEx, 0, -2);
                }
                if (str_starts_with($regEx, '(') && str_ends_with($regEx, ')') && str_contains($regEx, '|')) {
                    // probably list of abbrevs
                    $list = explode('|', trim($regEx, '()'));
                    foreach ($list as $abbrev) {
                        if (preg_match('~^[^/*?()\[\]{}\\\]+$~', $abbrev) === 1) {
                            $abbrevs[] = str_replace('\.', '.', $abbrev);
                        }
                    }
                } elseif (preg_match('~^[^/*?()\[\]{}\\\]+$~', $regEx) === 1) {
                    // simple rule containing abbrev directly
                    $abbrevs[] = str_replace('\.', '.', $regEx);
                }
            }
        }

        return $abbrevs;
    }
}
