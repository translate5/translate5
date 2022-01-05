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
 *
 */
class editor_Segment_Empty_Check {
    
    /**
     * @var string[]
     */
    private $states = [];

    /**
     * Check whether target is empty or contains only spaces, punctuation, or alike
     *
     * @param editor_Models_Task $task
     * @param string $targetField
     * @param editor_Models_Segment $segment
     * @param string $chars
     */
    public function __construct(editor_Models_Task $task, $targetField, editor_Models_Segment $segment, string $chars) {

        // Get source text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $source = $task->getEnableSourceEditing() ? $segment->getSourceEditToSort() : $segment->getSourceToSort();
        $source = strip_tags($source);
        $source = str_replace(['&lt;', '&gt;'], ['<', '>'], $source);
        $source = preg_replace('~[\s' .  preg_quote($chars, '~'). ']~', '', $source);

        // Get target text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $target = $segment->{'get' . ucfirst($targetField) . 'EditToSort'}();
        $target = strip_tags($target);
        $target = str_replace(['&lt;', '&gt;'], ['<', '>'], $target);
        $target = preg_replace('~[\s' .  preg_quote($chars, '~'). ']~', '', $target);

        // If $source is still non zero-length, but $target is  - flag it's empty
        if (!strlen($target) && strlen($source)) {

            // Get quality shortcut
            $state = editor_Segment_Empty_QualityProvider::qualityType();

            // Append to states
            $this->states[$state] = $state;
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
     * Check whether this instance has non-empty states array
     *
     * @return boolean
     */
    public function hasStates() {
        return count($this->states) > 0;
    }
}
