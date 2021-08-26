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
 * class with helper methods for import- and export-parsing (which are used in both)
 * 
 */
trait editor_Plugins_Transit_TraitParse {
    
    /**
     * 
     * @param string $text
     * @return boolean
     */
    protected function containsOnlyTagsOrEmpty($text){
        if(preg_replace(array('"<Tag .*?</Tag>"','"<FontTag .*?</FontTag>"'), array('',''), $text)===''){
            return true;
        }
        return false;
    }
    
    /**
     * checks if number of source and target-segments match - and logs it if not
     * @param string $taskGuid
     * @param string $source source file path
     * @param string $target target file path 
     * @return boolean
     */
    protected function isEvenLanguagePair($taskGuid, $source, $target) {
        if ($this->sourceDOM->getSegmentCount() === $this->targetDOM->getSegmentCount()){
            return true;
        }
        $msg = "The number of segments of source- and target-files are not identical. ";
        $msg .= "Transit LanguagePair can not be parsed properly. TaskGuid: ".$taskGuid." ";
        $msg .= "SourcePath: ".$source." ";
        $msg .= "TargetPath: ".$target." ";
        $this->log->logError($msg);
        return false;
    }
   
}
