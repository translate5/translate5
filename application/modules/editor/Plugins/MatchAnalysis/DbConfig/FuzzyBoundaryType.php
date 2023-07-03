<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Contains the config handler for core types
 */
class editor_Plugins_MatchAnalysis_DbConfig_FuzzyBoundaryType extends ZfExtended_DbConfig_Type_CoreTypes {
    /**
     * returns the GUI view class to be used or null for default handling
     * @return string|null
     */
    public function getGuiViewCls(): ?string {
        return 'Editor.plugins.MatchAnalysis.view.FuzzyBoundaryConfig';
    }

    public function validateValue(editor_Models_Config $config, &$newvalue, ?string &$errorStr): bool
    {
        $rawType = parent::validateValue($config, $newvalue, $errorStr);

        // if the raw type is not correct fail validation
        if(!$rawType) {
            return false;
        }

        $err = '';
        $confVal = (array) $this->jsonDecode($newvalue, $err); //from parent validate we still get a string
        ksort($confVal); //sort by the keys, from the lowest to the biggest

        //the following values are mandatory and must be listed in the configuration
        // the values are set to true, if handled in the config
        $mandatoriesFound = [
            100 => false,
            101 => false,
            102 => false,
            103 => false,
            104 => false,
        ];

        //loop over the values and check for gaps, overlapping, mandatories and so on
        $lastEnd = 0;
        foreach($confVal as $begin => $end) {
            settype($begin, 'integer');
            settype($end, 'integer');
            if($begin > $end) {
                $errorStr = 'Start value '.$begin.' must not be bigger as end value '.$end;
                return false;
            }

            if($lastEnd > 0 && ($lastEnd + 1) !== $begin) {
                $errorStr = 'Gaps and overlaps are not allowed! There is a gap or overlap between '.$lastEnd.' and '.$begin;
                return false;
            }

            //check the mandatory values
            if($begin >= 100 || $end >= 100) {
                foreach($mandatoriesFound as $idx => $val) {
                    //ignore already found mandatories
                    if($val) {
                        continue;
                    }
                    $mandatoriesFound[$idx] = ($begin <= $idx && $idx <= $end);
                }
            }

            $lastEnd = $end;
        }

        if(array_search(false, $mandatoriesFound) || $lastEnd !== 104) {
            $errorStr = 'At least all values >= 100 must be configured! This is not the case.';
            return false;
        }

        return true;
    }
}
