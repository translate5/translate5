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

use MittagQI\Translate5\Plugins\Okapi\Bconf\ResourceFile;
use ZfExtended_Dom;
use ZfExtended_Exception;

/**
 * Class representing a SRX file
 * A SRX is an xml with a defined structure containing nodes with language specific RegEx rules
 * for more documentation, see Segmentation
 */
final class Srx extends ResourceFile
{
    public const EXTENSION = 'srx';

    /**
     * @var string
     */
    public const SYSTEM_TARGET_SRX = '/application/modules/editor/Plugins/Okapi/data/srx/translate5/languages-2.srx';

    /**
     * Create and return self instance using SYSTEM_TARGET_SRX as path
     *
     * @throws ZfExtended_Exception
     */
    public static function createSystemTargetSrx(): Srx
    {
        return new self(APPLICATION_ROOT . self::SYSTEM_TARGET_SRX);
    }

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
    public function getSegmentationRules(string $rfc5646): array|bool
    {
        // Get srx file contents and convert to php-array
        $srx = simplexml_load_string($this->getContent());
        $srx = json_encode($srx);
        $srx = json_decode($srx, true);

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
            return false;
        }

        // Rules array, grouped by purpose e.g insert/delete delimiter, based on break="yes|no"
        $ruleA = [];
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
                $ruleA[$purpose][] = $rule;
            }
        }

        // Return rules grouped by purpose
        return $ruleA;
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

        // Use basic splitting
        return explode('<delimiter/>', $text);
    }
}
