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
    const REGEX_INTERNAL_TAGS = '#<div\s*class="(open|close|single)\s+([gxA-Fa-f0-9]*)[^"]*"\s*.*?(?!</div>)<span[^>]*data-originalid="([^"]*).*?(?!</div>).</div>#s';
    const REGEX_STARTTAG = '#^<div class="open.+class="short"[^>]*>&lt;([0-9]+)&gt;</span>.+</div>$#';
    const REGEX_ENDTAG = '#^<div class="close.+class="short"[^>]*>&lt;/([0-9]+)&gt;</span>.+</div>$#';
    const REGEX_SINGLETAG = '#^<div class="single.+class="short"[^>]*>&lt;([0-9]+)/&gt;</span>.+</div>$#';
    
    /***
     * Internal tag placeholder template
     * @var string
     */
    const PLACEHOLDER_TEMPLATE='<translate5:escaped id="%s" />';
    const PLACEHOLDER_TAG='<translate5:escaped>';
    
    const IGNORE_CLASS = 'ignoreInEditor';
    const IGNORE_ID_PREFIX = 'toignore-';
    
    /**
     * Used as tag id for regex based internal tags
     */
    const TYPE_REGEX = 'regex';

    public function __construct($replacerTemplate = null){
        $this->replacerRegex = self::REGEX_INTERNAL_TAGS;
        $this->placeholderTemplate = $replacerTemplate ?? self::PLACEHOLDER_TEMPLATE;
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
     * Get all "lines" in the segment as indicated by newline-tags (= "hardReturn" or "softReturn").
     * @param string $segment
     * @return array
     */
    public function getLinesAccordingToNewlineTags(string $segment) {
        $replacer = function($match) {
            if (in_array('hardReturn', $match) || in_array('softReturn', $match)) {
                return '<hardReturn/>';
            }
            return $match[0];
        };
        $segmentWithHardReturns = preg_replace_callback(self::REGEX_INTERNAL_TAGS, $replacer, $segment);
        return explode('<hardReturn/>',$segmentWithHardReturns);
    }
    
    /**
     * returns the stored length of the given tag node
     * @param string $tag the tag as div/span construct
     * @return integer returns the length of the tag or -1 of no length configured
     */
    public function getLength($tag) {
        $matches = [];
        if(preg_match('/<span[^>]+data-length="([^"]*)"[^>]*>/', $tag, $matches)) {
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
     * removes the tags to be ignored from the segment
     * @param string $segment
     * @return string
     */
    public function removeIgnoredTags(string $segment) {
        return $this->replace($segment, function($match) {
            if($this->matchIsIgnoredTag($match)) {
                return '';
            }
            return $match[0];
        });
    }
    
    /**
     * returns true if the given preg_match match array belongs to an ignored tag
     * @param array $match
     * @return boolean
     */
    protected function matchIsIgnoredTag(array $match) {
        return strpos($match[3], self::IGNORE_ID_PREFIX) === 0 && strpos($match[0], self::IGNORE_CLASS) !== false;
    }
    
    /**
     * restores the original escaped tag
     * @param string $segment
     * @param bool $whitespaceOnly optional, if true restore whitespace tags only
     * @param int &$highestTagNr if provided, it will be filled with the highest short tag number of all tags in $segment
     * @param array $whitespaceMap if provided, it will be filled with a 2d map of replaced whitespace/special characters and their used tag numbers
     * @return mixed
     */
    public function restore(string $segment, $whitespaceOnly = false, &$highestTagNr = 0, array &$whitespaceMap = []) {
        //TODO extend $whitespaceOnly filter so that we can filter for one ore more of the CSS classes (nbsp, newline, tab, space)
        return $this->replace($segment, function($match) use ($whitespaceOnly, &$highestTagNr, &$whitespaceMap) {
            $id = $match[3];
            $tagNr = $this->getTagNumber($match[0]);
            
            //determine the highest used short tag number
            $highestTagNr = max($tagNr, $highestTagNr);
            
            //FIXME HERE das auskommentieren um den Okapi Export Fehler Bug zu testen!
            //if the tag is tag to be ignored, we just remove it
            if($this->matchIsIgnoredTag($match)) {
                return '';
            }
            
            if($whitespaceOnly && !in_array($id, editor_Models_Segment_Whitespace::WHITESPACE_TAGS)) {
                return $match[0];
            }
            
            $data = $match[2];
            //restore packed data
            $result = pack('H*', $data);
            
            //if single-tag is regex-tag no <> encapsulation is needed
            if ($id === self::TYPE_REGEX) {
                return $result;
            }
            
            //the following search and replace is needed for TRANSLATE-464
            //backwards compatibility of already imported tasks
            $search = array('hardReturn /','softReturn /','macReturn /');
            $replace = array('hardReturn/','softReturn/','macReturn/');
            $result = str_replace($search, $replace, $result);
            
            
            //the original data is without <>
            $result = '<' . $result .'>';
            if(!array_key_exists($result, $whitespaceMap)) {
                $whitespaceMap[$result] = [];
            }
            $whitespaceMap[$result][] = $tagNr;
            return $result;
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
     * converts the given string (mainly the internal tags in the string) into excel tag-placeholder.
     * Sample:
     * <img class=".. open .." .. /> => <1>
     * <img class=".. close .." .. /> => </1>
     * <img class=".. single .." .. /> => <1 />
     * because <123> are no real tags, the function uses an "internal tag" <excel 123>. So first all tags are converted to <excel 123> tags.
     * this "real" tags can be used to exclude them from beeing removed by function strip_tags if $removeOther is true.
     * After this a simple str_replace is used to convert the internal <excel 123> to the wanted <123> tags
     *
     * The third parameter $replaceMap can be used to return a mapping between the inserted xliff tags
     * and the replaced original tags. Warning: it is no usual key => value map, to be compatible with toXliffPaired (details see there)
     *
     * @param string $segment
     * @param array &$replaceMap optional, returns by reference a mapping between the inserted xliff tags and the replaced original
     * @return string segment with excel pseudo tags
     */
    public function toExcel(string $segment, &$replaceMap = null) {
        //if not external map given, we init it internally, although we don't need it
        if(is_null($replaceMap)) {
            $replaceMap = [];
        }
        
        // remove TrackChanges Tags
        $taghelperTrackChanges = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $taghelperTrackChanges editor_Models_Segment_TrackChangeTag */
        $segment = $taghelperTrackChanges->removeTrackChanges($segment);
       
        $result = $this->replace($segment, function($match) use (&$replaceMap) {
            //original id coming from import format
            $type = $match[1];
            $shortCutNr = $this->getTagNumber($match[0]);
            switch($type) {
                case 'open':
                    $result = sprintf('<excel %s>', $shortCutNr);
                    $resultId = sprintf('<%s>', $shortCutNr);
                    break;
                case 'close':
                    $result = sprintf('<excel /%s>', $shortCutNr);
                    $resultId = sprintf('</%s>', $shortCutNr);
                    break;
                case 'single':
                default:
                    $result = sprintf('<excel %s />', $shortCutNr);
                    $resultId = sprintf('<%s />', $shortCutNr);
                    break;
            }
            
            $replaceMap[$resultId] = [$resultId, $match[0]];
            return $result;
        });
        
        // prevent internal excel tags from beeing removed
        $result = strip_tags($result, '<excel>');
        
        // convert the internal excel tags to the wanted form
        $result = str_replace('<excel ', '<', $result);
        
        return html_entity_decode($result, ENT_XML1);
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
                
                //if newid is null, calculation is disabled and we have to use the original ID
                $useOriginalId = is_null($newid);
                    
                $originalId = $match[3];
                if($useOriginalId) {
                    $newid = $originalId;
                }
                
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
                    $result = sprintf('<ec id="%1$s" startRef="%1$s" />', $newid++);
                    $closeTags[$key] = $originalId;
                }
                
                if($useOriginalId) {
                    //set to null again for next iteration
                    $newid = null;
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
    public function diff(string $segment1, string $segment2): array {
        return $this->diffArray($this->get($segment1), $this->get($segment2));
    }
    
    /**
     * Compares the internal tag differences of two strings containing internal tags
     * The diff is done by the whole tag
     * Same as self::diff, just working on tag arrays instead segment strings
     * @see self::diff
     *
     * @param array $segment1Tags The ones segment tags
     * @param array $segment2Tags The others segment tags
     * @return array an array containing all the internal tags from $segment1Tags that are not present in $segment2Tags
     */
    public function diffArray(array $segment1Tags, array $segment2Tags): array {
        //we can not use array_diff, since the count of same tags is important too. This would be ignored by array_diff
        $result = [];
        //ensure that we have numeric numbered arrays:
        $segment1Tags = array_values($segment1Tags);
        $segment2Tags = array_values($segment2Tags);
        
        //Problem: the fulltag content of a tag may contain special characters like umlauts.
        //After Import they are in HTML entity notation: &auml; after saving from browser they are stored as their plain character ä
        //Example encoded:       <div class="single 123 internal-tag ownttip"><span title="&lt;ph ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Geräte, Detailmaß A&amp;lt;/variable&amp;gt;&lt;/ph&gt;" class="short">&lt;1/&gt;</span><span data-originalid="6f18ea87a8e0306f7c809cb4f06842eb" data-length="-1" class="full">&lt;ph id=&quot;1&quot; ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Geräte Detailmaß A&amp;lt;/variable&amp;gt;&lt;/ph&gt;</span></div>
        //Example from Browser:  <div class="single 123 internal-tag ownttip"><span title="&lt;ph id=&quot;1&quot; ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Ger&auml;te, Detailma&szlig; A&amp;lt;/variable&amp;gt;&lt;/ph&gt;" class="short">&lt;1/&gt;</span><span data-originalid="6f18ea87a8e0306f7c809cb4f06842eb" data-length="-1" class="full">&lt;ph id=&quot;1&quot; ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Ger&auml;te, Detailma&szlig; A&amp;lt;/variable&amp;gt;&lt;/ph&gt;</span></div>
        //This is so far no problem, only for comparing the tags.
        //For that reason we have to compare tags without their full text content. The information of content equality is contained in the payload in the css class.
        
        $segment1TagsSanitized = preg_replace('#span title="[^"]*|class="full">[^<]*<\/#', '', $segment1Tags);
        $segment2TagsSanitized = preg_replace('#span title="[^"]*|class="full">[^<]*<\/#', '', $segment2Tags);
        
        foreach($segment1TagsSanitized as $idx => $inFirst) {
            $found = array_search($inFirst, $segment2TagsSanitized);
            if($found === false) {
                //if the tag is not in the second list, we want it as result
                $result[] = $segment1Tags[$idx];
            }
            else {
                //since the count of the matches is also important we have to delete already found elements here
                unset($segment2Tags[$found]);
                unset($segment2TagsSanitized[$found]);
            }
        }
        return $result;
    }
    
    /**
     * When using TMs it can happen, that the TM result contains more tags as we can use in the segment.
     * For visual reasons in the GUI we have to add them as "additional tags", but such tags may not be saved to the DB then.
     * @param int $shortTag
     * @return string the generated tag as string
     */
    public function makeAdditionalHtmlTag(int $shortTag) {
        if(empty($this->singleTag)) {
            //lazy and anonymous instantiation, since only needed here
            $this->singleTag = ZfExtended_Factory::get('editor_ImageTag_Single');
        }
        /* @var $this->singleTag editor_ImageTag_Single */
        return $this->singleTag->getHtmlTag([
            'class' => self::IGNORE_CLASS,
            'text' => '&lt;AdditionalTagFromTM/&gt;',
            'id' => self::IGNORE_ID_PREFIX.$shortTag,
            'shortTag' => $shortTag
        ]);
    }
    
    /**
     * Returns the short tag number to a given tag string
     * @param string $tag
     * @return int|NULL
     */
    public function getTagNumber(string $tag): ?int {
        $match = null;
        if(preg_match('#class="short"[^>]*>&lt;/?([0-9]+)/?&gt;</span#', $tag, $match)) {
            return (int) $match[1];
        }
        return null;
    }
    
    /**
     * replaces a short tag number with another one
     * @param string $tag
     * @param int $newShortNr
     * @return string
     */
    public function replaceTagNumber(string $tag, int $newShortNr): string {
        $count = 0;
        $res = preg_replace('#(class="short">&lt;/?)([0-9]+)(/?&gt;</span)#', '${1}'.$newShortNr.'${3}', $tag, -1, $count);
        if($count == 0) {
            return $res;
        }
        //in whitespace tags the number is also in the title
        return preg_replace('#(ownttip"><span title="&lt;/?)([0-9]+)(/?&gt;:)#', '${1}'.$newShortNr.'${3}', $tag);
    }
    
    /**
     * Same as getTagNumber just on an array of tag strings
     * @param array $tags
     * @return array
     */
    public function getTagNumbers(array $tags) {
        return array_map([$this, 'getTagNumber'], $tags);
    }

    /**
     * Encodes a raw tag so that it can be stored internally
     * @param string $originalTag
     * @return string
     */
    public static function encodeTagContent(string $originalTag): string {
        return implode('', unpack('H*', $originalTag));
    }
}