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
        if (self::$segments === null) self::$segments = $segment->getMaterializedViewData();

        // We need to build two snapshots of the 'same'-targets/sources info,
        // First one is without respect to current segment's new target, second one is with
        foreach (['was', 'now'] as $snapshot) {

            // Build the dictionaries with the info regarding same sources for different targets
            // if $snapshot is 'now' - current segment's new target will be respected
            foreach (self::$segments as $segmentI) {

                // If iterated segment is current segment - spoof old target with new one
                if ($snapshot == 'now' && $segmentI['id'] == $segment->getId()) $segmentI['target'] = $segment->getTargetEditToSort();

                // If empty target - skip
                if (!$segmentI['target']) continue;

                // If iterated segment is current segment - detect old target
                if ($snapshot == 'was' && $segmentI['id'] == $segment->getId()) $prevTargetEditToSort = $segmentI['target'];

                // Build array containing different targets for same sources
                $same['source'][$snapshot][$segmentI['source']] [$segmentI['target']] [$segmentI['id']] = true;

                // Build array containing different sources for same targets
                $same['target'][$snapshot][$segmentI['target']] [$segmentI['source']] [$segmentI['id']] = true;
            }
        }

        // Get 'was'-dictionary data for current segment
        $was['source'] = $same['source']['was'][$segment->getSourceToSort()] ?? [];
        $was['target'] = $same['target']['was'][$prevTargetEditToSort ?? ''] ?? [];

        // Get 'now'-dictionary data for current segment
        $now['source'] = $same['source']['now'][$segment->getSourceToSort()];
        $now['target'] = $same['target']['now'][$segment->getTargetEditToSort()];

        // Foreach [category => prop] pair
        foreach ([self::SOURCE => 'target', self::TARGET => 'source'] as $category => $prop) {

            // Reset arrays
            $now['merged'] = $was['merged'] = [];

            // If we have same targets for different sources - apply 'Inconsistent source' state
            if (count($now[$prop]) > 1) {

                // Key 'own' mean that we need to add $category-quality to the current segment independently
                // on whether we'll additionally need to add/remove that quality to/from other segments
                $this->states[$category]['own'] = $segment->getId();

                // So we merge arrays of ids of segments having same targets
                foreach ($now[$prop] as $idA) $now['merged'] += $idA;

                // If 'same'-qty is now exactly 2, it means we need to append
                // same quality to the segment, that current segment is same as
                if (count($now[$prop]) == 2) {

                    // Unset current one
                    unset($now['merged'][$segment->getId()]);

                    // And get single one remaining
                    $this->states[$category]['ins'] = array_keys($now['merged']);
                }
            }

            // Else if current segment was counted in the 'same'-qty, and that qty
            // was exactly 2 it mean that we need remove quality from remaining segment
            if (count($was[$prop] ?? []) == 2 && count($now[$prop]) <= 2) {

                // So we merge arrays of ids of segments having same sources
                foreach ($was[$prop] as $idA) $was['merged'] += $idA;

                // Unset current one
                unset($was['merged'][$segment->getId()]);

                // If $was['merged'] is equal to $now['merged'], it means that the change that was made to the target
                // is not affecting the presence of current segment in 'same'-plenties
                if (count($now['merged']) == count($was['merged'])
                    && count($now['merged']) == count(array_intersect_key($now['merged'], $was['merged'])))
                    continue;

                // And get single one remaining, with negative sign, indicating that
                // we need to remove state (quality category) from that segment
                $this->states[$category]['del'] = array_keys($was['merged']);
            }
        }

        class_exists('editor_Utils');
        i($this->states);
        i($was['target'], 'a');
        i($now['target'], 'a');
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
