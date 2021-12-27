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
 * evaluates the quality state of a segment regarding length-restrictions
 * NOTE: Currently we only evaluate the pixel length and the fullfillment of the maxLength (either if a segment is longer or not long enough relative to the max-length
 *
 */
class editor_Segment_Empty_Check {
    
    /**
     * @var string
     */
    const IS_EMPTY = 'empty'; // same as editor_Segment_Empty_QualityProvider::$type

    /**
     * @var string[]
     */
    private $states = [];

    /**
     * 
     * @param editor_Segment_FieldTags $fieldTags
     * @param editor_Models_Segment $segment
     * @param stdClass $lengthRestriction
     */
    public function __construct(editor_Segment_FieldTags $fieldTags, editor_Models_Segment $segment, string $chars) {//, editor_Segment_Empty_Restriction $lengthRestriction){

        // Get source text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $source = $segment->getSourceToSort();
        $source = strip_tags($source);
        $source = str_replace(['&lt;', '&gt;'], ['<', '>'], $source);
        $source = preg_replace('~[\s' .  preg_quote($chars, '~'). ']~', '', $source);

        // Get target text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $target = $segment->getTargetEditToSort();
        $target = strip_tags($target);
        $target = str_replace(['&lt;', '&gt;'], ['<', '>'], $target);
        $target = preg_replace('~[\s' .  preg_quote($chars, '~'). ']~', '', $target);

        // If $source is still non zero-length, but $target is  - flag it's empty
        if (!strlen($target) && strlen($source)) $this->states[] = self::IS_EMPTY;
    }

    /**
     * Retrieves the evaluated states
     * @return string[]
     */
    public function getStates(){
        return $this->states;
    }

    /**
     * 
     * @return boolean
     */
    public function hasStates() {
        return count($this->states) > 0;
    }
}
