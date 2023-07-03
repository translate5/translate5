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

namespace MittagQI\Translate5\Plugins\SpellCheck\Segment;

use editor_Models_Segment;
use editor_Models_Segment_Whitespace as Whitespace;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Adapter;
use Zend_Exception;

/**
 *
 * Checks the consistency of translations: Segments with an identical target but different sources or with identical sources but different targets
 * This Check can only be done for all segments of a task at once
 *
 */
class Check {

    // Css classes
    const CSS_GROUP_GENERAL     = 't5general';
    const CSS_GROUP_STYLE       = 't5style';
    const CSS_GRAMMAR           = 't5grammar';
    const CSS_MISSPELLING       = 't5misspelling';
    const CSS_TYPOGRAPHICAL     = 't5typographical';

    // General error types
    const GROUP_GENERAL         = 'group-general';
    const CHARACTERS            = 'characters';
    const DUPLICATION           = 'duplication';
    const INCONSISTENCY         = 'inconsistency';
    const LEGAL                 = 'legal';
    const UNCATEGORIZED         = 'uncategorized';

    // Style error types
    const GROUP_STYLE             = 'group-style';
    const REGISTER                = 'register';
    const LOCALE_SPECIFIC_CONTENT = 'locale-specific-content';
    const LOCALE_VIOLATION        = 'locale-violation';
    const GENERAL_STYLE           = 'style';
    const PATTERN_PROBLEM         = 'pattern-problem';
    const WHITESPACE              = 'whitespace';
    const TERMINOLOGY             = 'terminology';
    const INTERNATIONALIZATION    = 'internationalization';
    const NON_CONFORMANCE         = 'non-conformance';

    // Remaining error types
    const GRAMMAR         = 'grammar';
    const MISSPELLING     = 'misspelling';
    const TYPOGRAPHICAL   = 'typographical';

    /**
     * Mappings for issueType-values provided by LanguageTool API response
     *
     * @var array
     */
    public static $css = [

        // General
        self::CHARACTERS            => self::CSS_GROUP_GENERAL,
        self::DUPLICATION           => self::CSS_GROUP_GENERAL,
        self::INCONSISTENCY         => self::CSS_GROUP_GENERAL,
        self::LEGAL                 => self::CSS_GROUP_GENERAL,
        self::UNCATEGORIZED         => self::CSS_GROUP_GENERAL,

        // Style
        self::REGISTER                => self::CSS_GROUP_STYLE,
        self::LOCALE_SPECIFIC_CONTENT => self::CSS_GROUP_STYLE,
        self::LOCALE_VIOLATION        => self::CSS_GROUP_STYLE,
        self::GENERAL_STYLE           => self::CSS_GROUP_STYLE,
        self::PATTERN_PROBLEM         => self::CSS_GROUP_STYLE,
        self::WHITESPACE              => self::CSS_GROUP_STYLE,
        self::TERMINOLOGY             => self::CSS_GROUP_STYLE,
        self::INTERNATIONALIZATION    => self::CSS_GROUP_STYLE,
        self::NON_CONFORMANCE         => self::CSS_GROUP_STYLE,

        // Remaining
        self::GRAMMAR       => self::CSS_GRAMMAR,
        self::MISSPELLING   => self::CSS_MISSPELLING,
        self::TYPOGRAPHICAL => self::CSS_TYPOGRAPHICAL,
    ];

    public static $map = [

        // General error types
        'characters'            => self::CHARACTERS,
        'duplication'           => self::DUPLICATION,
        'inconsistency'         => self::INCONSISTENCY,
        'legal'                 => self::LEGAL,
        'uncategorized'         => self::UNCATEGORIZED,

        // Style error types
        'register'                => self::REGISTER,
        'locale-specific-content' => self::LOCALE_SPECIFIC_CONTENT,
        'locale-violation'        => self::LOCALE_VIOLATION,
        'style'                   => self::GENERAL_STYLE,
        'pattern-problem'         => self::PATTERN_PROBLEM,
        'whitespace'              => self::WHITESPACE,
        'terminology'             => self::TERMINOLOGY,
        'internationalization'    => self::INTERNATIONALIZATION,
        'non-conformance'         => self::NON_CONFORMANCE,

        // Remaining error types
        'grammar'                 => self::GRAMMAR,
        'misspelling'             => self::MISSPELLING,
        'typographical'           => self::TYPOGRAPHICAL,
    ];

    /**
     * Qualities
     *
     * @var array
     */
    private $states = [];

    /**
     * If we're in batch mode, this array will contain keys 'target' and 'result'
     *
     * @var array
     */
    private static $batch = [];

    /**
     * @param editor_Models_Segment $segment
     * @param $targetField
     * @param Adapter $adapter
     * @param string $spellCheckLang
     * @throws Zend_Exception
     * @throws DownException
     * @throws MalfunctionException
     * @throws RequestException
     * @throws TimeOutException
     */
    public function __construct(editor_Models_Segment $segment, $targetField, Adapter $adapter, string $spellCheckLang) {

        // If we're in batch-mode
        if (self::$batch) {

            // If matches were detected for the current segment
            if ($matches = self::$batch['result'][$segment->getSegmentNrInTask()] ?? 0) {

                // Prepare $data stdClass instance having those pre-detected matches
                $data = new \stdClass();
                $data->matches = $matches;

            } else {
                return;
            }

        // Else if we're not in batch-mode
        } else {

            // Prepare target
            $target = self::prepareTarget($segment, $targetField);

            // If empty target - return
            if (strlen($target) === 0) {
                return;
            }

            // Get LanguageTool response
            $data = $adapter->getMatches($target, $spellCheckLang);
        }

        // Foreach match given by LanguageTool API response
        foreach ($data->matches as $index => $match) {

            // If match's issueType is known to Translate5
            if ($category = self::$map[$match->rule->issueType] ?? 0) {

                // Convert into special data structure
                $this->states[$category] []= (object) [
                    'content'           => mb_substr(
                        $match->context->text,
                        $match->context->offset,
                        $match->context->length),
                    'matchIndex'        => $index,                                                       // Integer
                    'range'             => [                                                             // Rangy bookmark
                        'start' => $match->offset,
                        'end'   => $match->offset + $match->context->length
                    ],
                    'message'           => $match->message,                                              // String
                    'replacements'      => array_column($match->replacements ?? [], 'value'),            // Array
                    'infoURLs'          => array_column($match->rule->urls   ?? [], 'value'),            // Array
                    'cssClassErrorType' => self::$css[$category]                                         // String
                ];

            // Else log that detected error is of a kind previously unknown to translate5 app
            } else {
                $segment->getTask()->logger('editor.task.autoqa')->warn('E1418', 'LanguageTool (which stands behind AutoQA Spell Check) detected an error of a kind previously unknown to translate5 app', [
                    'lang' => $spellCheckLang,
                    'text' => $target,
                    'match' => $match
                ]);
            }
        }
    }

    /**
     * Retrieves the evaluated states
     *
     * @return string[][]
     */
    public function getStates(): array {
        return $this->states;
    }

    /**
     * @return boolean
     */
    public function hasStates(): bool {
        return count($this->states) > 0;
    }

    /**
     * Clear batch-data
     */
    public static function purgeBatch() {
        self::$batch = [
            'target' => [],
            'result' => []
        ];
    }

    /**
     * Append the value of $segment's $targetField to the list of to be processed
     *
     * @param editor_Models_Segment $segment
     * @param string $targetField
     */
    public static function addBatchTarget(editor_Models_Segment $segment, string $targetField) {
        self::$batch['target'][$segment->getSegmentNrInTask()] = self::prepareTarget($segment, $targetField);
    }

    /**
     * Get value of $segment's $targetField applicable to be sent to LanguageTool
     *
     * @param editor_Models_Segment $segment
     * @param string $targetField
     * @return string
     */
    public static function prepareTarget(editor_Models_Segment $segment, string $targetField) : string {

        // Get target text, strip tags, replace htmlentities
        $target = $segment->{'get' . ucfirst($targetField) . 'EditToSort'}();
        $target = str_replace(['&lt;', '&gt;'], ['<', '>'], $target);

        // Replace whitespace-placeholders with the actual characters they represent
        $target = Whitespace::replaceLabelledCharacters($target);

        // Return string applicable to be sent to LanguageTool
        return $target;
    }

    /**
     * @param Adapter $adapter
     * @param string $spellCheckLang
     * @throws DownException
     * @throws MalfunctionException
     * @throws RequestException
     * @throws TimeOutException
     */
    public static function runBatchAndSplitResults(Adapter $adapter, string $spellCheckLang) {

        // Separator
        $separator = Adapter::BATCH_SEPARATOR;

        // Sort by segmentNrInTask (keys)
        ksort(self::$batch['target']);

        // Get LanguageTool response
        $data = $adapter->getMatches(self::$batch['target'], $spellCheckLang);

        // Get whole text contents sent for spellchecking
        $whole = join($separator, self::$batch['target']);

        // Get array of [batch-index => segmentNrInTask] pairs
        $segmentNrByIndex = array_keys(self::$batch['target']);

        // Foreach match given by LanguageTool API response
        foreach ($data->matches as $index => $match) {

            // Get text from the beginning and up to the reported word/phrase, inclusively
            $text = mb_substr($whole, 0, $match->offset + $match->length);

            // Get segment index as quantity of occurrences of separator in that text
            $segmentIdx = preg_match_all("~$separator~", $text);

            // Get quantity of characters that we should shift originally reported offset by
            $shiftOffsetBy = mb_strrpos($text, $separator) + ($segmentIdx ? strlen($separator) : 0);

            // Amend props for them to relate to certain segment only
            $match->context->text = explode($separator, $whole)[$segmentIdx];
            $match->context->offset -= $shiftOffsetBy;
            $match->offset          -= $shiftOffsetBy;

            // Append to the list of problems for the certain segment
            self::$batch['result'][ $segmentNrByIndex[$segmentIdx] ] []= $match;
        }
    }
}
