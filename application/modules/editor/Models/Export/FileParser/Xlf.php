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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Export for Xlf uses same parser as Sdlxliff
 */

class editor_Models_Export_FileParser_Xlf extends editor_Models_Export_FileParser_Sdlxliff{
    /**
     * dedicated to write the match-Rate to the right position in the target format
     * @param array $file that contains file as array as splitted by parse function
     * @param integer $i position of current segment in the file array
     * @return string
     */
    protected function writeMatchRate(array $file, integer $i) {
        $matchRate = $this->_segmentEntity->getMatchRate();
        $midArr = explode('_', $this->_segmentEntity->getMid());
        $mid = $midArr[0];
        $segPart =& $file[$i-1];
        //example string
        //<trans-unit id="3" translate="yes" tmgr:segstatus="XLATED" tmgr:matchinfo="AUTOSUBST" tmgr:matchratio="100">
        if(preg_match('#<trans-unit[^>]* id="'.$mid.'"[^>]*tmgr:matchratio="\d+"#', $segPart)===1){
            //if percent attribute is already defined
            $segPart = preg_replace('#(<trans-unit[^>]* id="'.$mid.'"[^>]*tmgr:matchratio=)"\d+"#', '\\1"'.$matchRate.'"', $segPart);
            return $file;
        }
        $segPart = preg_replace('#(<trans-unit[^>]* id="'.$mid.'" *)#', '\\1 tmgr:matchratio="'.$matchRate.'" ', $segPart);
        return $file;
    }
}
