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
    // just for historcal documentation: in the export the following regex was used: $termRegex = '/<div[^>]+class="term([^"]+)"\s+data-tbxid="([^"]+)"[^>]*>/s';
    const STRING_TERM_TAG_END = '</div>';
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTags;
    
    /**
     * Optional internalTag Instance, if not given it is created internally
     * @param editor_Models_Segment_InternalTag $internalTag
     */
    public function __construct(editor_Models_Segment_InternalTag $internalTag = null) {
        if(!empty($internalTag)) {
            $this->internalTags = $internalTag;
        }
    }
    
    /**
     * Lazy instantiation of the internal tags helper
     */
    protected function initInternalTagHelper() {
        //FIXME: the old check make no sence
        if(empty($this->internalTags)) {
            $this->internalTags = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        }
    }
    
    /**
     * replaces term tags with either the callback or the given scalar
     * see preg_replace
     * see preg_replace_callback
     * @param string $segment
     * @param string|Callable $startTagReplacer If callable, parameters: $wholeMatch, $tbxId, array $cssClasses, $wholeSegment
     * @param string $endTagReplacer scalar only, since str_replace is used insted of preg_replace
     * @return mixed 
     */
    public function replace(string $segment, $startTagReplacer, string $endTagReplacer, $preserveInternal = false) {
        if($preserveInternal) {
            $this->initInternalTagHelper();
            $segment = $this->internalTags->protect($segment);
        }
        //if using a callback, we have to prepare matches to be the parameters
        if(!is_string($startTagReplacer) && is_callable($startTagReplacer)) {
            $replacer = function($match) use ($startTagReplacer, $segment) {
                $result = array_values($this->parseMatches($match));
                array_unshift($result, $match[0]);
                $result[] = $segment;
                return call_user_func_array($startTagReplacer, $result);
            };
        }
        else {
            $replacer = $startTagReplacer;
        }
        
        $segment = preg_replace_callback(self::REGEX_TERM_TAG_START, $replacer, $segment);
        $segment = str_replace(self::STRING_TERM_TAG_END, $endTagReplacer, $segment);
        if($preserveInternal) {
            return $this->internalTags->unprotect($segment);
        }
        return $segment;
    }
    
    /**
     * Removes term tags. Warning: if unsure if your content contains internal tags set parameter preserveInternal to true!
     * @param string $segment the segment content
     * @param bool $preserveInternal if true, internal tags are masked before removing term tags.
     */
    public function remove(string $segment, $preserveInternal = false) {
        
        if($preserveInternal) {
            $this->initInternalTagHelper();
            //FIXME: why ??
            $segment = $this->internalTags->protect($segment);
        }
        $segment = preg_replace(self::REGEX_TERM_TAG_START, '', $segment);
        //This str_replace destroys our internal tags! so ensure that the content does not contain internal tags!
        // Either by masking or by removing before
        $segment = str_replace(self::STRING_TERM_TAG_END, '', $segment);
        if($preserveInternal) {
            return $this->internalTags->unprotect($segment);
        }
        return $segment;
    }
    
    /**
     * parses all term tags and returns a list with the tbxid (mid) and the css classes as array
     * @param string $segment
     * @return array
     */
    public function getInfos($segment) {
        preg_match_all(self::REGEX_TERM_TAG_START, $segment, $matches, PREG_SET_ORDER);
        $result = array();
        foreach($matches as $match) {
            $result[] = $this->parseMatches($match);
        }
        return $result;
    }
    
    /**
     * parses the preg_ matches array for the term start tag regex
     * @param array $matches
     */
    protected function parseMatches(array $match) {
        //class before data-tbxid
        if(empty($match[5])) {
            $mid = $match[4];
            $classes = $match[3];
        }
        //data-tbxid before class 
        else {
            $mid = $match[6];
            $classes = $match[7];
        }
        return array('mid' => $mid, 'classes' => explode(' ', $classes));
    }
}
