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
 * Segment Internal Tag Helper Class
 * This class contains the regex definition and related helper methods to internal tags of translate5
 * 
 * TO BE COMPLETED: There are several more places in translate5 which can make use of this class
 * 
 */
class editor_Models_Segment_InternalTag extends editor_Models_Segment_TagAbstract {
    
    /**
     * match 0: as usual the whole string
     * match 1: the tag type (single, open, close, regex, space etc.)
     * match 2: the packed data
     * match 3: the original id
     * match 4: the rest of the generated id
     * 
     * @var string
     */
    const REGEX_INTERNAL_TAGS = '#<div\s*class="([a-z]*)\s+([gxA-Fa-f0-9]*)[^"]*"\s*.*?(?!</div>)<span[^>]*data-originalid="([^"]*).*?(?!</div>).</div>#s';
    const REGEX_STARTTAG = '#^<div class="open.+class="short">&lt;([0-9]+)&gt;</span>.+</div>$#';
    const REGEX_ENDTAG = '#^<div class="close.+class="short">&lt;/([0-9]+)&gt;</span>.+</div>$#';
    const REGEX_SINGLETAG = '#^<div class="single.+class="short">&lt;([0-9]+)/&gt;</span>.+</div>$#';
    
    /***
     * Internal tag placeholder template
     * @var string
     */
    const PLACEHOLDER_TEMPLATE='<translate5:escaped id="%s" />';
    const PLACEHOLDER_TAG='<translate5:escaped>';
    
    public function __construct(){
        $this->replacerRegex=self::REGEX_INTERNAL_TAGS;
        $this->placeholderTemplate=self::PLACEHOLDER_TEMPLATE;
    }
    
    /**
     * returns all tags
     * @param string $segment
     * @return array
     */
    public function get(string $segment) {
        $matches = null;
        preg_match_all(self::REGEX_INTERNAL_TAGS, $segment, $matches);
        return $matches[0];
    }
    
    /**
     * Get all real (non whitespace) tags
     * @param string $segment
     */
    public function getRealTags(string $segment) {
        $matches = null;
        preg_match_all(self::REGEX_INTERNAL_TAGS, $segment, $matches);
        $realTags = array_filter($matches[3], function($value){
            return !in_array($value, editor_Models_Segment_Whitespace::WHITESPACE_TAGS);
        });
        //return the real tags (with cleaned index) from matches[0] by the keys from the found real tags above
        return array_values(array_intersect_key($matches[0], $realTags));
    }
    
    /**
     * returns the stored length of the given tag node
     * @param string $tag the tag as div/span construct
     * @return integer returns the length of the tag or -1 of no length configured
     */
    public function getLength($tag) {
        if(preg_match('/<span[^>]+data-length="([^"]*)"[^>]+>/', $tag, $matches)) {
            return $matches[1];
        }
        return -1;
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
     * returns an array with several different tag counts to the given segment content
     * Example result:
     * (
     *     [open] => 0          → the content contains no open tags
     *     [close] => 0         → the content contains no close tags
     *     [single] => 3        → the content contains 3 single tags
     *     [whitespace] => 1    → the content contains 1 whitespace tag
     *     [tag] => 2           → the content contains 2 normal tags
     *     [all] => 3           → the content contains 3 internal tags at all (equals to count method)
     * )
     * 
     * @param string $segment
     * @return array
     */
    public function statistic(string $segment) {
        $result = [
            'open' => 0,
            'close' => 0,
            'single' => 0,
            'whitespace' => 0,
            'tag' => 0,
            'all' => 0,
        ];
        $matches = null;
        $result['all'] = preg_match_all(self::REGEX_INTERNAL_TAGS, $segment, $matches);
        if(!$result['all']) {
            return $result;
        }
        //count whitespace and "normal" tags
        $result['whitespace'] = count(array_filter($matches[3], function($id){
            return in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS);
        }));
        $result['tag'] = $result['all'] - $result['whitespace'];
        
        //count single|open|close types:
        return array_merge($result, array_count_values($matches[1]));
    }
    
    /**
     * restores the original escaped tag
     * @param string $segment
     * @param bool $whitespaceOnly optional, if true restore whitespace tags only 
     * @return mixed
     */
    public function restore(string $segment, $whitespaceOnly = false) {
        //TODO extend $whitespaceOnly filter so that we can filter for one ore more of the CSS classes (nbsp, newline, tab, space) 
        return $this->replace($segment, function($match) use ($whitespaceOnly) {
            $id = $match[3];
            
            if($whitespaceOnly && !in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
                return $match[0];
            }
            
            $data = $match[2];
            //restore packed data
            $result = pack('H*', $data);
            
            //if single-tag is regex-tag no <> encapsulation is needed
            if ($id === "regex") {
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
    
    /**
     * converts the given string (mainly the internal tags in the string) into valid xliff tags without content
     * The third parameter $replaceMap can be used to return a mapping between the inserted xliff tags 
     *  and the replaced original tags. Warning: it is no usual key => value map, to be compatible with toXliffPaired (details see there)
     *  
     * @param string $segment
     * @param bool $removeOther optional, removes per default all other tags (mqm, terms, etc)
     * @param array &$replaceMap optional, returns by reference a mapping between the inserted xliff tags and the replaced original
     * @param int &$newid defaults to 1, is given as reference to provide a different startid of the internal tags
     * @return string segment with xliff tags
     */
    public function toXliff(string $segment, $removeOther = true, &$replaceMap = null, &$newid = 1) {
        //xliff 1.2 needs an id for bpt/bx and ept/ex tags.
        // matching of bpt/bx and ept/ex is done by separate rid, which is filled with the original ID
        
        //if not external map given, we init it internally, although we don't need it
        if(is_null($replaceMap)) {
            $replaceMap = [];
        }
        
        $result = $this->replace($segment, function($match) use (&$newid, &$replaceMap){
            //original id coming from import format
            $id = $match[3];
            $type = $match[1];
            $tag = ['open' => 'bx', 'close' => 'ex', 'single' => 'x']; 
            //xliff tags:
            // bpt ept → begin and end tag as standalone tags in one segment
            // bx ex → start and end tag of tag pairs where the tags are distributed to different segments
            // g tag → direct representation of a tag pair, 
            //  disadvantage: the closing g tag contains no information about semantic, so for reappling our internal tags a XML parser would be necessary
            
            //as tag id the here generated newid must be used,
            // since the original $id is coming from imported data, it can happen 
            // that not unique tags are produced (multiple space tags for example) 
            // not unique tags are bad for the replaceMap
            if($type == 'single') {
                $result = sprintf('<x id="%s"/>', $newid++);
            }
            else {
                //for matching bx and ex tags the original $id is fine
                $result = sprintf('<%s id="%s" rid="%s"/>', $tag[$type], $newid++, $id);
            }
            $replaceMap[$result] = [$result, $match[0]];
            return $result;
        });
        
        if($removeOther) {
            return strip_tags($result, '<x><x/><bpt><bpt/><ept><ept/><bx><bx/><ex><ex/><it><it/>');
        }
        return $result;
    }
    
    /**
     * Converts internal tags to xliff2 format
     * @see editor_Models_Segment_InternalTag::toXliff for details see toXliff
     * @param string $segment
     * @param bool $removeOther
     * @param array $replaceMap
     * @param number $newid
     * @return string|mixed
     */
    public function toXliff2(string $segment, $removeOther = true, &$replaceMap = null, &$newid = 1) {
        //if not external map given, we init it internally, although we don't need it
        if(is_null($replaceMap)) {
            $replaceMap = [];
        }
        
        //we can not just loop (with replace) over the internal tags, due id and startRef generation
        // the problem exists only if a end tag (which needs the start tags id as startRef) comes before his start tag
        // so we abuse "protect" to mask all tags, loop over the gathered tags, modify the internal stored original tags
        // and finally we unprotect the tags to restore the replaced ones
        $segment = $this->protect($segment);
        $origTags = [];
        $openTagIds = [];
        $closeTags = [];
        //loop over the found internal tags, replace them with the XLIFF2 tags 
        foreach($this->originalTags as $key => $tag) {
            //use replace on the single tag to replace the internal tag with the xliff2 tag
            $this->originalTags[$key] = $this->replace($tag, function($match) use ($key, &$newid, &$origTags, &$openTagIds, &$closeTags){
                $originalId = $match[3];
                $type = $match[1];
                if($type == 'single') {
                    $result = sprintf('<ph id="%s"/>', $newid++);
                }
                elseif($type == 'open') {
                    $result = sprintf('<sc id="%s"/>', $newid);
                    //store the open tag id to the original id (latter one is used to map start and close tag)
                    $openTagIds[$originalId] = $newid++;
                }
                else {
                    $result = sprintf('<ec id="%s" startRef="XXX" />', $newid++);
                    $closeTags[$key] = $originalId;
                }
                $origTags[$key] = $match[0];
                return $result;
            });
        }
        
        //loop over the close tags and inject the id of the start tag as startRef attribute
        foreach($closeTags as $key => $originalId) {
            if(empty($openTagIds[$originalId])) {
                //remove optional startRef attribute if no start tag exists
                $this->originalTags[$key] = str_replace(' startRef="XXX" ', '', $this->originalTags[$key]);
            }
            else {
                $this->originalTags[$key] = str_replace('startRef="XXX"', 'startRef="'.$openTagIds[$originalId].'"', $this->originalTags[$key]);
            }
        }
        
        //fill replaceMap
        foreach($this->originalTags as $key => $value) {
            $replaceMap[$value] = [$value, $origTags[$key]];
        }
        
        $result = $this->unprotect($segment);
        if($removeOther) {
            return strip_tags($result, '<cp><cp/><ph><ph/><pc><pc/><sc><sc/><ec><ec/><mrk><mrk/><sm><sm/><em><em/>');
        }
        return $result;
    }

    /**
     * converts the given string (mainly the internal tags in the string) into valid xliff tags without content
     * The third parameter $replaceMap can be used to return a mapping between the inserted xliff tags 
     *  and the replaced original tags. Warning: it is no usual key => value map, since keys can be duplicated (</g>) tags
     *  There fore the map is a 2d array: [[</g>, replacement 1],[</g>, replacement 2]] 
     *  
     * @param string $segment
     * @param bool $removeOther optional, removes per default all other tags (mqm, terms, etc)
     * @param array &$replaceMap optional, returns by reference a mapping between the inserted xliff tags and the replaced original
     * @param int &$newid defaults to 1, is given as reference to provide a different startid of the internal tags
     * @return string segment with xliff tags
     */
    public function toXliffPaired(string $segment, $removeOther = true, &$replaceMap = null, &$newid = 1) {
        $result = $this->toXliff($segment, $removeOther, $replaceMap, $newid);
        $xml = ZfExtended_Factory::get('editor_Models_Converter_XmlPairer');
        /* @var $xml editor_Models_Converter_XmlPairer */
        
        return $this->pairTags($result, $replaceMap, $xml);
    }
    
    /**
     * @see self::toXliffPaired
     * @param string $segment
     * @param bool $removeOther
     * @param array &$replaceMap
     * @param number &$newid
     * @return string segment with xliff2 tags
     */
    public function toXliff2Paired(string $segment, $removeOther = true, &$replaceMap = null, &$newid = 1) {
        $result = $this->toXliff2($segment, $removeOther, $replaceMap, $newid);
        $xml = ZfExtended_Factory::get('editor_Models_Converter_Xliff2Pairer');
        /* @var $xml editor_Models_Converter_Xliff2Pairer */
        
        return $this->pairTags($result, $replaceMap, $xml);
    }
    
    protected function pairTags($result, &$replaceMap, $xml) {
        $pairedContent = $xml->pairTags($result);
        $pairedReplace = $xml->getReplaceList();
        $pairMap = $xml->getPairMap();
        
        foreach($replaceMap as $key => &$replaced) {
            if(!empty($pairedReplace[$key])) {
                //replace the bx-ex/sc-ec through the g/pc tag in the replace map
                $replaced[0] = $pairedReplace[$key];
            }
            if(!empty($pairMap[$key])) {
                $replaced[2] = $pairMap[$key];
            }
        }
        return $pairedContent;
    }
    
    /**
     * restores the internal tags into the given string by the given 2d map
     * Warning: the reapplying is currently position based! 
     * That means if the original xliff contained 
     * → Discuss with Marc!!!!
     * 
     * @param string $segment
     * @param array $map not a a key:value map, but a 2d array, since keys can exist multiple times
     */
    public function reapply2dMap(string $segment, array $map) {
        foreach($map as $tupel) {
            $key = $tupel[0];
            $value = $tupel[1];
            //since $key may not be unique, we cannot use str_replace here, str_replace would replace all occurences
            $pos = mb_strpos($segment, $key);
            if ($pos !== false) {
                $segment = mb_substr($segment, 0, $pos).$value.mb_substr($segment, $pos + mb_strlen($key));
            }
        }
        return trim($segment);
    }
    
    /**
     * Compares the internal tag differences of two strings containing internal tags
     * The diff is done by the whole tag
     * 
     * @param string $segment1 The string to compare from
     * @param string $segment2 A string to compare against
     * @return array an array containing all the internal tags from $segment1 that are not present in $segment2
     */
    public function diff(string $segment1, string $segment2) {
        $allMatches1 = $this->get($segment1);
        $allMatches2 = $this->get($segment2);
        
        $result = [];
        foreach($allMatches1 as $inFirst) {
            $found = array_search($inFirst, $allMatches2);
            if($found === false) {
                //if the tag is not in the second list, we want it as result
                $result[] = $inFirst;
            }
            else {
                //since the count of the matches is also important we have to delete already found elements here
                unset($allMatches2[$found]);
            }
        }
        return $result;
    }
    
}