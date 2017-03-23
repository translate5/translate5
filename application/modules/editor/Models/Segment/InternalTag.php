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
    
    /**
     * match 0: as usual the whole string
     * match 1: the tag type (single, open, close, regex, space etc.)
     * match 2: the packed data
     * match 3: the original id
     * match 4: the rest of the generated id
     * 
     * @var string
     */
    const REGEX_INTERNAL_TAGS = '#<div\s*class="([a-z]*)\s+([gxA-Fa-f0-9]*)"\s*.*?(?!</div>)<span[^>]*data-originalid="([^"]*).*?(?!</div>).</div>#s';
    
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
     * restores the original escaped tag
     */
    public function restore(string $segment) {
        return $this->replace($segment, function($match){
            $id = $match[3];
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
     *  and the replaced original tags. Warning: it is no usual key => value map, since keys can be duplicated (</g>) tags
     *  There fore the map is a 2d array: [[</g>, replacement 1],[</g>, replacement 2]] 
     *  
     * @param string $segment
     * @param boolean $removeOther optional, removes per default all other tags (mqm, terms, etc)
     * @param array &$replaceMap optional, returns by reference a mapping between the inserted xliff tags and the replaced original
     */
    public function toXliff(string $segment, $removeOther = true, &$replaceMap = null) {
        //xliff 1.2 needs an id for ex and bx tags.
        // matching of bx and ex is done by separate rid, which is filled with the original ID
        $newid = 1;
        
        //if not external map given, we init it internally, although we don't need it
        if(is_null($replaceMap)) {
            $replaceMap = [];
        }
        
        $result = $this->replace($segment, function($match) use (&$newid, &$replaceMap){
            //original id coming from import format
            $id = $match[3];
            $type = $match[1];
            $tag = ['open' => 'bx', 'close' => 'ex', 'single' => 'x'];
            
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
            $result = strip_tags($result, '<x><x/><bx><bx/><ex><ex/>');
        }
        
        $xml = ZfExtended_Factory::get('editor_Models_Converter_XmlPairer');
        /* @var $xml editor_Models_Converter_XmlPairer */
        
        //remove all other tags, allow <x> <bx> and <ex> 
        $pairedContent = $xml->pairTags($result);
        $pairedReplace = $xml->getReplaceList();
        
        foreach($replaceMap as $key => &$replaced) {
            if(!empty($pairedReplace[$key])) {
                //replace the bx/ex through the g tag in the replace map
                $replaced[0] = $pairedReplace[$key];
            }
        }
        return $pairedContent;
    }
    
    /**
     * restores the internal tags into the given string by the given 2d map
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
                $segment = substr_replace($segment, $value, $pos, mb_strlen($key));
            }
        }
        return $segment;
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