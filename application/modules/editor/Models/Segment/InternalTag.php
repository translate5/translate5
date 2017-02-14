<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Segment Internal Tag Helper Class
 * This class contains the regex definition and related helper methods to internal tags of translate5
 * 
 * TO BE COMPLETED: There are several more places in translate5 which can make use of this class
 * 
 */
class editor_Models_Segment_InternalTag {
    const REGEX_INTERNAL_TAGS = '#<div\s*class="([a-z]*)\s+([gxA-Fa-f0-9]*)"\s*.*?(?!</div>)<span[^>]*id="([^-]*)-.*?(?!</div>).</div>#s';
    
    /**
     * replaces internal tags with either the callback or the given scalar
     * @see preg_replace
     * @see preg_replace_callback
     * @param string $segment
     * @param Closure|string $replacer
     * @param int $limit optional
     * @param int $count optional, returns the replace count
     * @return mixed 
     */
    public function replace(string $segment, $replacer, $limit = -1, &$count = null) {
        if(is_callable($replacer)) {
            return preg_replace_callback(self::REGEX_INTERNAL_TAGS, $replacer, $segment, $limit, $count);
        }
        return preg_replace(self::REGEX_INTERNAL_TAGS, $replacer, $segment, $limit, $count);
    }
    
    /**
     * returns all tags
     * @param string $segment
     * @return array
     */
    public function get(string $segment) {
        preg_match_all(self::REGEX_INTERNAL_TAGS, $segment, $matches);
        return $matches[0];
    }
    
    /**
     * Counts the internal tags in the given segment string
     * @param string $segment
     * @return number
     */
    public function count(string $segment) {
        return preg_match_all(self::REGEX_INTERNAL_TAGS, $segment);
    }
    
    /**
     * Checks if the two given segment texts are equal, ignores the auto calculated tag IDs therefore
     * @param unknown $segmentOne
     * @param unknown $segmentTwo
     * @return string
     */
    public function equalsIdIgnored($segmentOne, $segmentTwo) {
        $replacer = function($match) {
            return preg_replace('#<span id="[^"]+"#', '<span id=""', $match[0]);
        };
        return $this->replace($segmentOne, $replacer) === $this->replace($segmentTwo, $replacer);
    }
    
    /**
     * restores the original escaped tag
     */
    public function restore(string $segment) {
        return $this->replace($segment, function($match){
            $type = $match[1];
            $data = $match[2];
            //restore packed data
            $result = pack('H*', $data);
            
            //if single-tag is regex-tag no <> encapsulation is needed
            if ($type === "regex") {
                return $result;
            }
            
            //the following search and replace is needed for TRANSLATE-464
            //backwards compatibility of already imported tasks
            $search = array('hardReturn /','softReturn /','macReturn /');
            $replace = array('hardReturn/','softReturn/','macReturn/');
            $result = str_replace($search, $replace, $result);
            
            //the original data is without <> 
            return '<' . $result .'>';
        });
    }
}