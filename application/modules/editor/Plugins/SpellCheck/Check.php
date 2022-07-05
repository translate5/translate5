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

/**
 * 
 * Checks the consistency of translations: Segments with an identical target but different sources or with identical sources but different targets
 * This Check can only be done for all segments of a task at once
 *
 */
class editor_Plugins_SpellCheck_Check {

    /**
     * Css class and quality categoáry name for spell errors
     */
    const SPELL   = 'spellError';

    /**
     * Css class and quality category name for grammar errors
     */
    const GRAMMAR = 'grammarError';

    /**
     * Css class and quality category name for style suggestions
     */
    const STYLE   = 'suggestion';

    /**
     * Mappings for issueType-values provided by LanguageTool API response
     *
     * @var array
     */
    public static $map = [
        'misspelling'   => self::SPELL,
        'register'      => self::STYLE,
        'typographical' => self::GRAMMAR,
        'uncategorized' => self::GRAMMAR,
        'whitespace'    => self::GRAMMAR,
        'default'       => ''
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
     * @param editor_Plugins_SpellCheck_LanguageTool_Connector $connector
     * @param $spellCheckLang
     * @throws editor_Plugins_SpellCheck_Exception_Down
     * @throws editor_Plugins_SpellCheck_Exception_Malfunction
     * @throws editor_Plugins_SpellCheck_Exception_Request
     * @throws editor_Plugins_SpellCheck_Exception_TimeOut
     */
    public function __construct(editor_Models_Segment $segment, $targetField,
                                editor_Plugins_SpellCheck_LanguageTool_Connector $connector, $spellCheckLang) {

        // Get target text, strip tags, replace htmlentities
        $target = $segment->{'get' . ucfirst($targetField) . 'EditToSort'}();
        //$target = strip_tags($target);
        $target = str_replace(['&lt;', '&gt;'], ['<', '>'], $target);

        // Get LanguageTool response
        $data = $connector->getMatches($target, $spellCheckLang);

        // Foreach match given by LanguageTool API response
        foreach ($data->matches as $index => $match) {

            // Get quality category
            $category = self::$map[$match->rule->issueType] ?? 'default';

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
                'cssClassErrorType' => $category                                                     // String
            ];
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
