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

use editor_Models_Segment_Whitespace as Whitespace;

/**
 *
 * Checks the consistency of translations: Segments with an identical target but different sources or with identical sources but different targets
 * This Check can only be done for all segments of a task at once
 *
 */
class editor_Plugins_SpellCheck_Check {

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

        // Remaining
        self::GRAMMAR       => self::CSS_GRAMMAR,
        self::MISSPELLING   => self::CSS_MISSPELLING,
        self::TYPOGRAPHICAL => self::CSS_TYPOGRAPHICAL,
    ];

    public static $map = [

        // General error types
        'characters'            => SELF::CHARACTERS,
        'duplication'           => SELF::DUPLICATION,
        'inconsistency'         => SELF::INCONSISTENCY,
        'legal'                 => SELF::LEGAL,
        'uncategorized'         => SELF::UNCATEGORIZED,

        // Style error types
        'register'                => SELF::REGISTER,
        'locale-specific-content' => SELF::LOCALE_SPECIFIC_CONTENT,
        'locale-violation'        => SELF::LOCALE_VIOLATION,
        'style'                   => SELF::GENERAL_STYLE,
        'pattern-problem'         => SELF::PATTERN_PROBLEM,
        'whitespace'              => SELF::WHITESPACE,
        'terminology'             => SELF::TERMINOLOGY,
        'internationalization'    => SELF::INTERNATIONALIZATION,

        // Remaining error types
        'grammar'                 => SELF::GRAMMAR,
        'misspelling'             => SELF::MISSPELLING,
        'typographical'           => SELF::TYPOGRAPHICAL,
    ];

    /**
     * Qualities
     *
     * @var array
     */
    private $states = [];

    /**
     * editor_Plugins_SpellCheck_Check constructor.
     *
     * @param editor_Models_Segment $segment
     * @param $targetField
     * @param editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter $connector
     * @param $spellCheckLang
     * @throws editor_Plugins_SpellCheck_Exception_Down
     * @throws editor_Plugins_SpellCheck_Exception_Malfunction
     * @throws editor_Plugins_SpellCheck_Exception_Request
     * @throws editor_Plugins_SpellCheck_Exception_TimeOut
     */
    public function __construct(editor_Models_Segment $segment, $targetField,
                                editor_Plugins_SpellCheck_Adapter_LanguageTool_Adapter $connector, $spellCheckLang) {

        // Get target text, strip tags, replace htmlentities
        $target = $segment->{'get' . ucfirst($targetField) . 'EditToSort'}();
        $target = str_replace(['&lt;', '&gt;'], ['<', '>'], $target);

        // Replace whitespace-placeholders with the actual characters they represent
        $target = Whitespace::replaceLabelledCharacters($target);

        // Get LanguageTool response
        $data = $connector->getMatches($target, $spellCheckLang);

        // Foreach match given by LanguageTool API response
        foreach ($data->matches as $index => $match) {

            // If match's issueType is known to Translate5
            if ($category = self::$map[$match->rule->issueType] ?? 0) {

                // Convert into special data structure
                $this->states[$category] []= (object) [
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
     * @return string[]
     */
    public function getStates(){
        return $this->states;
    }

    /**
     * @return boolean
     */
    public function hasStates() {
        return count($this->states) > 0;
    }
}
