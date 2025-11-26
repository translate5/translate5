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
use editor_Segment_FieldTags;
use editor_Segment_Internal_Tag;
use editor_Segment_Tag;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use MittagQI\Translate5\Plugins\SpellCheck\LanguageTool\Adapter;
use MittagQI\Translate5\Tag\TagSequence;
use stdClass;
use Zend_Exception;
use ZfExtended_ErrorCodeException;
use ZfExtended_Exception;

/**
 * Checks the consistency of translations: Segments with an identical target
 * but different sources or with identical sources but different targets
 * This Check can only be done for all segments of a task at once
 */
class Check
{
    // Css classes
    public const string CSS_GROUP_GENERAL = 't5general';

    public const string CSS_GROUP_STYLE = 't5style';

    public const string CSS_GRAMMAR = 't5grammar';

    public const string CSS_MISSPELLING = 't5misspelling';

    public const string CSS_TYPOGRAPHICAL = 't5typographical';

    // General error types
    public const string GROUP_GENERAL = 'group-general';

    public const string CHARACTERS = 'characters';

    public const string DUPLICATION = 'duplication';

    public const string INCONSISTENCY = 'inconsistency';

    public const string LEGAL = 'legal';

    public const string UNCATEGORIZED = 'uncategorized';

    // Style error types
    public const string GROUP_STYLE = 'group-style';

    public const string REGISTER = 'register';

    public const string LOCALE_SPECIFIC_CONTENT = 'locale-specific-content';

    public const string LOCALE_VIOLATION = 'locale-violation';

    public const string GENERAL_STYLE = 'style';

    public const string PATTERN_PROBLEM = 'pattern-problem';

    public const string WHITESPACE = 'whitespace';

    public const string TERMINOLOGY = 'terminology';

    public const string INTERNATIONALIZATION = 'internationalization';

    public const string NON_CONFORMANCE = 'non-conformance';

    public const string NUMBERS = 'numbers';

    // Remaining error types
    public const string GRAMMAR = 'grammar';

    public const string MISSPELLING = 'misspelling';

    public const string TYPOGRAPHICAL = 'typographical';

    /**
     * Mappings for issueType-values provided by LanguageTool API response
     */
    public static array $css = [
        // General
        self::CHARACTERS => self::CSS_GROUP_GENERAL,
        self::DUPLICATION => self::CSS_GROUP_GENERAL,
        self::INCONSISTENCY => self::CSS_GROUP_GENERAL,
        self::LEGAL => self::CSS_GROUP_GENERAL,
        self::UNCATEGORIZED => self::CSS_GROUP_GENERAL,

        // Style
        self::REGISTER => self::CSS_GROUP_STYLE,
        self::LOCALE_SPECIFIC_CONTENT => self::CSS_GROUP_STYLE,
        self::LOCALE_VIOLATION => self::CSS_GROUP_STYLE,
        self::GENERAL_STYLE => self::CSS_GROUP_STYLE,
        self::PATTERN_PROBLEM => self::CSS_GROUP_STYLE,
        self::WHITESPACE => self::CSS_GROUP_STYLE,
        self::TERMINOLOGY => self::CSS_GROUP_STYLE,
        self::INTERNATIONALIZATION => self::CSS_GROUP_STYLE,
        self::NON_CONFORMANCE => self::CSS_GROUP_STYLE,
        self::NUMBERS => self::CSS_GROUP_STYLE,

        // Remaining
        self::GRAMMAR => self::CSS_GRAMMAR,
        self::MISSPELLING => self::CSS_MISSPELLING,
        self::TYPOGRAPHICAL => self::CSS_TYPOGRAPHICAL,
    ];

    public static array $map = [
        // General error types
        'characters' => self::CHARACTERS,
        'duplication' => self::DUPLICATION,
        'inconsistency' => self::INCONSISTENCY,
        'legal' => self::LEGAL,
        'uncategorized' => self::UNCATEGORIZED,

        // Style error types
        'register' => self::REGISTER,
        'locale-specific-content' => self::LOCALE_SPECIFIC_CONTENT,
        'locale-violation' => self::LOCALE_VIOLATION,
        'style' => self::GENERAL_STYLE,
        'pattern-problem' => self::PATTERN_PROBLEM,
        'whitespace' => self::WHITESPACE,
        'terminology' => self::TERMINOLOGY,
        'internationalization' => self::INTERNATIONALIZATION,
        'non-conformance' => self::NON_CONFORMANCE,
        'numbers' => self::NUMBERS,

        // Remaining error types
        'grammar' => self::GRAMMAR,
        'misspelling' => self::MISSPELLING,
        'typographical' => self::TYPOGRAPHICAL,
    ];

    /**
     * Qualities
     */
    private array $states = [];

    /**
     * If we're in batch mode, this array will contain keys 'target' and 'result'
     */
    private static array $batch = [];

    /**
     * @throws Zend_Exception
     * @throws DownException
     * @throws MalfunctionException
     * @throws RequestException
     * @throws TimeOutException
     */
    public function __construct(
        editor_Models_Segment $segment,
        editor_Segment_FieldTags $target,
        Adapter $adapter,
        string $spellCheckLang
    ) {
        // If we're in batch-mode
        if (self::$batch) {
            // If matches were detected for the current segment
            if ($matches = self::$batch['result'][$segment->getSegmentNrInTask()] ?? 0) {
                // Prepare $data stdClass instance having those pre-detected matches
                $data = new stdClass();
                $data->matches = $matches;
            } else {
                return;
            }

            // Else if we're not in batch-mode
        } else {
            // Prepare target
            $targetText = self::prepareTarget($segment, $target);

            // If empty target - return
            if (strlen($targetText) === 0) {
                return;
            }

            // Get LanguageTool response
            $data = $adapter->getMatches($targetText, $spellCheckLang);
        }

        // Foreach match given by LanguageTool API response
        foreach ($data->matches as $index => $match) {
            // If match's issueType is known to Translate5
            if ($category = self::$map[$match->rule->issueType] ?? 0) {
                // IMPORTANT: if we have whitespace-errors when single internal tags are
                // these single internal tags usually represent a variable or placeable (which
                // must be terminated by whitespace). Therefor we ignore such errors
                // QUIRK: technically it would be more correct to not send the double whitespace
                // but this would require a far better data-model in the frontend
                // ANOTHER QUIRK: sometimes these whitespace-errors are reported as "uncategorized"
                if (($category === self::WHITESPACE || $category === self::UNCATEGORIZED)
                    && $target->hasTypeAndClassBetweenIndices(
                        editor_Segment_Tag::TYPE_INTERNAL,
                        editor_Segment_Internal_Tag::CSS_CLASS_SINGLE,
                        $match->offset,
                        $match->offset + $match->length
                    )) {
                    continue;
                }

                // Convert into special data structure
                $this->states[$category][] = (object) [
                    'content' => mb_substr($match->sentence, $match->offset, $match->length),
                    'matchIndex' => $index,                                                 // Integer
                    'range' => [
                        // Text coordinates
                        'start' => $match->offset,
                        'end' => $match->offset + $match->context->length,
                    ],
                    'message' => $match->message,                                                   // String
                    'replacements' => array_column($match->replacements ?? [], 'value'), // Array
                    'infoURLs' => array_column($match->rule->urls ?? [], 'value'), // Array
                    'cssClassErrorType' => self::$css[$category],                                              // String
                ];

                // Else log that detected error is of a kind previously unknown to translate5 app
            } else {
                $segment->getTask()->logger('editor.task.autoqa')
                    ->warn(
                        'E1418',
                        'LanguageTool (which stands behind AutoQA Spell Check)'
                        . ' detected an error of a kind previously unknown to translate5 app',
                        [
                            'lang' => $spellCheckLang,
                            'text' => $targetText ?? '',
                            'match' => $match,
                        ]
                    );
            }
        }
    }

    /**
     * Retrieves the evaluated states
     *
     * @return array<string, array<object>>
     */
    public function getStates(): array
    {
        return $this->states;
    }

    /**
     * @return boolean
     */
    public function hasStates(): bool
    {
        return count($this->states) > 0;
    }

    /**
     * Clear batch-data
     */
    public static function purgeBatch(): void
    {
        self::$batch = [
            'target' => [],
            'result' => [],
        ];
    }

    /**
     * Append the value of $segment's $targetField to the list of to be processed
     *
     * @throws ZfExtended_Exception
     */
    public static function addBatchTarget(editor_Models_Segment $segment, editor_Segment_FieldTags $target): void
    {
        self::$batch['target'][$segment->getSegmentNrInTask()] = self::prepareTarget($segment, $target);
    }

    /**
     * Get value of $segment's $targetField applicable to be sent to LanguageTool
     *
     * @throws Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    public static function prepareTarget(editor_Models_Segment $segment, editor_Segment_FieldTags $target): string
    {
        // Get target text with all tags being either stripped or
        // replaced with their original contents (in case of whitespace)
        $targetText = $target->renderReplaced(TagSequence::MODE_ORIGINAL);

        // replace escaped entities: TODO FIXME: This will create trouble with text-indices !!
        // Return string applicable to be sent to LanguageTool
        return str_replace(['&lt;', '&gt;'], ['<', '>'], $targetText);
    }

    /**
     * @throws DownException
     * @throws RequestException
     * @throws TimeOutException
     */
    public static function runBatchAndSplitResults(Adapter $adapter, string $spellCheckLang): void
    {
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
        foreach ($data->matches as $match) {
            // Get text from the beginning and up to the reported word/phrase, inclusively
            $text = mb_substr($whole, 0, $match->offset + $match->length);

            // Get segment index as quantity of occurrences of separator in that text
            $segmentIdx = preg_match_all("~$separator~", $text);

            // Get quantity of characters that we should shift originally reported offset by
            $shiftOffsetBy = mb_strrpos($text, $separator) + ($segmentIdx ? strlen($separator) : 0);

            // Amend props for them to relate to certain segment only
            $match->context->text = explode($separator, $whole)[$segmentIdx];
            $match->context->offset -= $shiftOffsetBy;
            $match->offset -= $shiftOffsetBy;

            // Append to the list of problems for the certain segment
            self::$batch['result'][$segmentNrByIndex[$segmentIdx]][] = $match;
        }
    }
}
