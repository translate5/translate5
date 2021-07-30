<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * This model collects some internal handling of the segmentrange-column in the
 * taskUserAssoc-table (= for all DB-related methods see editor_Models_TaskUserAssoc).
 * Segmentranges can be assigned to user as known from assigning pages when printing
 * ("1-3,5,8-9"). This class handles everything that is related to the syntax and semantics
 * of the segmentranges.
 * "Role" refers to the "role" in the TaskUserAssoc; these are workflow-roles, not user-roles.
 */
class editor_Models_TaskUserAssoc_Segmentrange {
    
    /**
     * Remove characters we don't need for further handling.
     * - remove whitespace
     * - replace ";" with ","
     * @param string $segmentRanges
     * @return string
     */
    private static function prepare(string $segmentRanges) : string {
        // Example for "1-3;5, 8-9 ":
        // return "1-3,5,8-9"
        $segmentRanges = trim(preg_replace('/\s+/','', $segmentRanges));
        $segmentRanges = preg_replace('/;/', ',', $segmentRanges);
        return $segmentRanges;
    }
    
    /**
     * Returns the segmentGroups separated according to the prepared $segmentRanges.
     * @param string $segmentRanges
     * @return array
     */
    private static function getAllSegmentGroups(string $segmentRanges) : array {
        // With the prepared $segmentRanges, the segmentGroups are seperated with ",".
        return explode(",", $segmentRanges);
    }
    
    /**
     * Is the format of the given $segmentRanges valid? (Empty values are ok.)
     * @param string $segmentRanges
     * @return bool
     */
    public function validateSyntax(string $segmentRanges) : bool {
        // valid: ""
        // valid: "   "
        // valid: "1-3,5,6-7"
        // valid: "1-3,5;6-7 "
        // not valid: "1-3,5,6+7"
        // not valid: "1-3,5,,6-7"
        
        $segmentRanges = self::prepare($segmentRanges);
        
        if ($segmentRanges == '') {
            return true;
        }
        
        if (!preg_match('/[0-9,-;]+/', $segmentRanges)) {
            return false;
        }
        
        $allSegmentGroups = self::getAllSegmentGroups($segmentRanges);
        foreach ($allSegmentGroups as $segmentGroup) {
            $segmentGroupLimits = explode("-", $segmentGroup);
            if (!preg_match('/[0-9]+/', implode('',$segmentGroupLimits))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Values must not be in wrong order or overlapping (= neither in itself nor 
     * with other users of the same role).
     * @param string $segmentRanges
     * @param string $assignedSegments
     * @return bool
     */
    public function validateSemantics(string $segmentRanges, array $assignedSegments) : bool {
        // valid: "1-3,5,6-7"
        // not valid: "1-3,5,2-7"
        // not valid: "3-1,5,6-7"
        // not valid: "1-3,5,6-7" for userX when userY has the same role with "2-4"
        
        $segmentRanges = self::prepare($segmentRanges);
        $allSegmentGroups = self::getAllSegmentGroups($segmentRanges);
        
        $segmentNumbers = [];
        foreach ($allSegmentGroups as $segmentGroup) {
            $segmentGroupLimits = explode("-", $segmentGroup);
            $segmentGroupStart = (int)reset($segmentGroupLimits);
            $segmentGroupEnd = (int)end($segmentGroupLimits);
            if ($segmentGroupStart > $segmentGroupEnd) {
                return false;
            }
            for ($nr = $segmentGroupStart; $nr <= $segmentGroupEnd; $nr++) {
                if(in_array($nr, $assignedSegments)) {
                    return false;
                }
                if(in_array($nr, $segmentNumbers)) {
                    return false;
                }
                $segmentNumbers[] = $nr;
            }
        }
        
        return true;
    }
    
    /**
     * Return an array with the numbers of the segments that are set in the given 
     * array of tua-rows.
     * @param array $tuaRows
     * @return array
     */
    public static function getSegmentNumbersFromRows(array $tuaRows) : array {
        // Example for:
        // - translator {94ff4a53-dae0-4793-beae-1f09968c3c93}: "1-3;5"
        // - translator {c77edcf5-3c55-4c29-a73d-da80d4dcfb36}: "7-8"
        // $segmentNumbers = [1,2,3,5,7,8]
        $segmentNumbers = [];
        foreach ($tuaRows as $row) {
            $segmentsNumbersInRange = self::getNumbers($row['segmentrange']);
            $segmentNumbers  = array_merge($segmentNumbers, $segmentsNumbersInRange);
        }
        return $segmentNumbers;
    }
    
    /**
     * Return an array with the numbers of the segments that are set in the given
     * single range.
     * @param string $segmentRanges
     * @return array
     */
    public static function getNumbers(string $segmentRanges) : array {
        // Example for "1-3;5, 8-9 ":
        // $segmentNumbers = [1,2,3,5,8,9]
        $segmentNumbers = [];
        if(empty($segmentRanges)){
            return $segmentNumbers;
        }
        $segmentRanges = self::prepare($segmentRanges);
        $allSegmentGroups = self::getAllSegmentGroups($segmentRanges);
        foreach ($allSegmentGroups as $segmentGroup) {
            $segmentGroupLimits = explode("-", $segmentGroup);
            for ($nr = reset($segmentGroupLimits); $nr <= end($segmentGroupLimits); $nr++) {
                $segmentNumbers[] = (int)$nr;
            }
        }
        return $segmentNumbers;
    }
    
    /**
     * Return ranges from numbers.
     * @param string $segmentNumbers
     * @return string
     */
    public static function getRanges(array $segmentNumbers) : string {
        // Example for [1,2,3,5,8,9]:
        // $segmentRanges = "1-3,5,8-9"
        // from https://codereview.stackexchange.com/a/103767
        $segmentNumbers = array_unique( $segmentNumbers);
        sort($segmentNumbers);
        $allSegmentGroups = array();
        for( $i = 0; $i < count($segmentNumbers); $i++ ) {
            if( $i > 0 && ($segmentNumbers[$i-1] == $segmentNumbers[$i] - 1)) {
                array_push($allSegmentGroups[count($allSegmentGroups)-1], $segmentNumbers[$i]);
            } else {
                array_push($allSegmentGroups, array( $segmentNumbers[$i]));
            }
        }
        $segmentRanges = array();
        foreach( $allSegmentGroups as $segmentGroup) {
            if( count($segmentGroup) == 1 ) {
                $segmentRanges[] = $segmentGroup[0];
            } else {
                $segmentRanges[] = $segmentGroup[0] . '-' . $segmentGroup[count($segmentGroup)-1];
            }
        }
        return implode( ',', $segmentRanges);
    }
}