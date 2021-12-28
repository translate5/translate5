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
    const SOURCE = 'source123';

    /**
     * @var string
     */
    const TARGET = 'target123';

    /**
     * @var string[]
     */
    private $states = [];

    /**
     * @var array
     */
    public static $segments = null;

    /**
     * 
     * @param editor_Segment_FieldTags $fieldTags
     * @param editor_Models_Segment $segment
     * @param stdClass $lengthRestriction
     */
    public function __construct(editor_Segment_FieldTags $fieldTags, editor_Models_Segment $segment) {

        // Get all segments in current task
        if (self::$segments === null) {
            self::$segments = $segment->getMaterializedViewData();
        }

        // Foreach segments collect the info regarding same sources for different targets, and upside down
        // with current $segment usage where need, and it is not yet saved into database, and therefore data
        // in self::$segments is not up to date for current $segment for our 'same'-stuff detection
        foreach (self::$segments as $segmentI) {
            if (!$segmentI['target']) continue;
            if ($segmentI['id'] == $segment->getId()) $prevTargetEditToSort = $segmentI['target'];
            $same['source']['was'][$segmentI['source']] [$segmentI['target']] [$segmentI['id']] = true;
            $same['target']['was'][$segmentI['target']] [$segmentI['source']] [$segmentI['id']] = true;
        }
        foreach (self::$segments as $segmentI) {
            if ($segmentI['id'] == $segment->getId()) $segmentI['target'] = $segment->getTargetEditToSort();
            if (!$segmentI['target']) continue;
            $same['source']['now'][$segmentI['source']] [$segmentI['target']] [$segmentI['id']] = true;
            $same['target']['now'][$segmentI['target']] [$segmentI['source']] [$segmentI['id']] = true;
        }

        class_exists('editor_Utils');
        //i($check->getStates(), 'a');
        i($same, 'a');

        $was['source'] = $same['source']['was'][$segment->getSourceToSort()] ?? [];
        $was['target'] = $same['target']['was'][$prevTargetEditToSort ?? ''] ?? [];

        $now['source'] = $same['source']['now'][$segment->getSourceToSort()];
        $now['target'] = $same['target']['now'][$segment->getTargetEditToSort()];

        // If we now have same sources for different targets - apply 'Inconsistent target' state
        if (count($now['source']) > 1) {

            // If same sources qty is now exactly 2, it means we need to append
            // same quality to the segment, that current segment is same as
            if (count($now['source']) == 2) {

                // So we merge arrays of ids of segments having same sources
                $merged = []; foreach ($now['source'] as $idA) $merged += $idA;

                // Unset current one
                unset($merged[$segment->getId()]);

                // And get single one remaining
                $this->states[self::TARGET] = key($merged);

            // Else just pust state
            } else {
                $this->states[self::TARGET] = true;
            }

        // Else if current segment was involved in the same sources plenty, and that plenty size was exactly 2
        // it mean that we need remove same quality from remaining segment
        } else if (count($was['source'] ?? []) == 2) {

            // So we merge arrays of ids of segments having same sources
            $merged = []; foreach ($was['source'] as $idA) $merged += $idA;

            // Unset current one
            unset($merged[$segment->getId()]);

            // And get single one remaining, with negative sign, indicating that
            // we need to remove state (quality category) from that segment
            $this->states[self::TARGET] = key($merged) * -1;
        }

        // If we have same targets for different sources - apply 'Inconsistent source' state
        if (count($now['target']) > 1) {

            // If same targets qty is now exactly 2, it means we need to append
            // same quality to the segment, that current segment is same as
            if (count($now['target']) == 2) {

                // So we merge arrays of ids of segments having same targets
                $merged = []; foreach ($now['target'] as $idA) $merged += $idA;

                // Unset current one
                unset($merged[$segment->getId()]);

                // And get single one remaining
                $this->states[self::SOURCE] = key($merged);

            // Else just pust state
            } else {
                $this->states[self::SOURCE] = true;
            }

        // Else if current segment was involved in the same targets plenty, and that plenty size was exactly 2
        // it mean that we need remove same quality from remaining segment
        } else if (count($was['target'] ?? []) == 2) {

            // So we merge arrays of ids of segments having same sources
            $merged = []; foreach ($was['target'] as $idA) $merged += $idA;

            // Unset current one
            unset($merged[$segment->getId()]);

            // And get single one remaining, with negative sign, indicating that
            // we need to remove state (quality category) from that segment
            $this->states[self::SOURCE] = key($merged) * -1;
        }
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
