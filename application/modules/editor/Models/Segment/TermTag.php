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

/**
 * Segment Term Tag Helper Class
 * This class contains the regex definition and related helper methods to term tags of translate5
 * 
 * TO BE COMPLETED: There are several more places in translate5 which can make use of this class
 * 
 */
class editor_Models_Segment_TermTag {
    
    /**
     * @var string
     */
    const REGEX_TERM_TAG_START = '/<div[^>]+((class="([^"]*)"[^>]+data-tbxid="([^"]*)")|(data-tbxid="([^"]*)"[^>]+class="([^"]*)"))[^>]*>/';
    const STRING_TERM_TAG_END = '</div>';
    
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
        //FIXME implement me: $replacer must be always a Closure, since we have to replace a start and a end tag
        throw new ZfExtended_Exception("Parameter preserveInternal not implemented yet!");
    }
    
    /**
     * Removes term tags. Warning: if unsure if your content contains internal tags set parameter preserveInternal to true!
     * @param string $segment the segment content
     * @param boolean $preserveInternal if true, internal tags are masked before removing term tags.
     */
    public function remove(string $segment, $preserveInternal = false) {
        if($preserveInternal) {
            //FIXME when using this switch, all internal tags has to be masked and unmasked after removing the term tags
            throw new ZfExtended_Exception("Parameter preserveInternal not implemented yet!");
        }
        $segment = preg_replace(self::REGEX_TERM_TAG_START, '', $segment);
        //This str_replace destroys our internal tags! so ensure that the content does not contain internal tags!
        // Either by masking or by removing before
        return str_replace(self::STRING_TERM_TAG_END, '', $segment);
    }
}