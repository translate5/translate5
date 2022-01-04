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
class editor_Segment_Consistent_Check {
    
    /**
     * @var string
     */
    const SOURCE = 'source';

    /**
     * @var string
     */
    const TARGET = 'target';

    /**
     * @var array
     */
    private $states = [];

    /**
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task) {

        // Get arrays of comma-separated ids of segments having inconsistent sources/targets
        $byCategory = $task->getInconsistentSegmentIds(); class_exists('editor_Utils');

        // Collect states for each segmentId
        foreach ($byCategory as $category => $byTarget)
            foreach ($byTarget as $target => $segmentIdListA)
                foreach ($segmentIdListA as $list)
                    foreach (explode(',', $list) as $segmentId)
                        $this->states[$segmentId][$category] = $category;

        // Convert keys
        foreach ($this->states as &$states) $states = array_values($states);
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
