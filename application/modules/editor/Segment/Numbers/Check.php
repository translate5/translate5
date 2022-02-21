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
 * Numbers check
 */
class editor_Segment_Numbers_Check {
    
    /**
     * @var string
     */
    const NUM1 = 'num1';

    /**
     * @var string
     */
    const NUM2 = 'num2';

    /**
     * @var array
     */
    private $states = [];

    /**
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task, $targetField, editor_Models_Segment $segment) {

        // Get source text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $source = $task->getEnableSourceEditing() ? $segment->getSourceEditToSort() : $segment->getSourceToSort();
        $source = strip_tags($source);

        // Get target text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $target = $segment->{'get' . ucfirst($targetField) . 'EditToSort'}();
        $target = strip_tags($target);

        //
        $this->states += numbers_check($source, $target, 'en', 'de');
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
