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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

  /**
 * Zeichnet Änderungen zwischen ursprünglichem target und dem edited-Feld sdlxliff-spezifisch aus
 */

class editor_Models_Export_DiffTagger_Sdlxliff extends editor_Models_Export_DiffTagger {

    /**
     * @var string Regex which returns in the matches array of a preg_match the id and the type (closing or opening) of a g-tag
     */
    public $_findGTagsIdAndTypeRegex = '"^<(/?)g [^>]*id=\"([^\"]+)\"[^>]*>$"';

    /**
     * @var int
     */
    protected $_diffResPartCount = NULL;

    /**
     * @var string
     */
    protected $_changeTimestamp = NULL;
    /**
     * @var string
     */
    protected $_userName = NULL;

    /**
     * zeichnet ein einzelnes Segment aus
     *
     * @param string $target bereits in die Ursprungssyntax zurückgebautes target-Segment
     * @param string $edited bereits in die Ursprungssyntax zurückgebautes editiertes target-Segment (edited-Spalte)
     * @param string $changeTimestamp Zeitpunkt der letzten Änderung des Segments
     * @param string $userName Benutzername des Lektors
     * @return string $edited mit diff-Syntax fertig ausgezeichnet
     */
    public function diffSegment($target, $edited, $changeTimestamp, $userName) {
        $escapedGTagsTarget = array();
        $escapedGTagsEdited = array();

        $this->_changeTimestamp = $changeTimestamp;
        $this->_userName = $userName;

        $targetArr = $this->tagBreakUp($target);
        $editedArr = $this->tagBreakUp($edited);

        $targetArr = $this->escapeSingleGTags($targetArr, $escapedGTagsTarget);
        $editedArr = $this->escapeSingleGTags($editedArr, $escapedGTagsEdited);

        $targetArr = $this->replacePairGTagsTarget($targetArr);
        $editedArr = $this->replacePairGTagsEdited($editedArr);

        $targetArr = $this->replacePairMrkTags($targetArr);
        $editedArr = $this->replacePairMrkTags($editedArr);

        $targetArr = $this->wordBreakUp($targetArr);
        $editedArr = $this->wordBreakUp($editedArr);

        $targetArr = $this->joinTerms($targetArr);
        $editedArr = $this->joinTerms($editedArr);

        $diff = ZfExtended_Factory::get('ZfExtended_Diff');
        $diffRes = $diff->process($targetArr, $editedArr);

        $this->_diffResPartCount = count($diffRes);

        $getGTagsInformation = $this->getGTagsInformation($diffRes);
        $getGTagsToComplement = $this->getGTagsToComplement($getGTagsInformation);
        foreach ($diffRes as $key => &$val) {
            if (is_array($val)) {
                $val['i'] = $this->markAddition($val['i']);
                $val['d'] = $this->markDeletion($val['d'], $getGTagsToComplement, $key);
                $val = implode('', $val);
            }
            //adds sdl:end-attributes to opening G-Tags, which corresponding closing g-Tags changed positions (the others are done in $this->markDeletion)
            elseif (preg_match($this->_findGTagsIdAndTypeRegex, $val, $matches) === 1 and
                    strpos($val, 'sdl:end') === false and
                    strpos($val, 'sdl:start') === false and //to avoid marking tags whose corresponding tag already is missing since the last edit in Studio itself
                    $matches[1] === '' and
                    isset($getGTagsToComplement[$matches[2]][$key]['opening']['noChange'])
            ) {
                $val = str_replace('>', ' sdl:end="false">', $val); //no trailing slash, because according to sdlxliff there is a closing g-tag inserted after the deletion-mrk-Tag to complete the opening tag
            }
        }
        $diffRes = str_replace('g id="term___mrkTag___', 'g id="___mrkTag___', $diffRes);//remove the mark, that it had been a term-Tag, because we do not need it anymore
        $diffRes = preg_replace_callback('"<g id=\"___mrkTag___([^\"]+)\""', function ($match) {
                return '<mrk '.pack('H*', $match[1]); }, $diffRes);
        $diffRes = preg_replace('"</g id=\"___mrkTag___[^\"]+\""', '</mrk', $diffRes);
        $diffRes = preg_replace('"</g id=\"[^\"]+\""', '</g', $diffRes);
        $edited = implode('', $diffRes);
        return $this->restoreSingleGTags($edited, $escapedGTagsEdited);
    }

    /**
     * restores all tags escaped by escapeSingleGTags
     *
     * @param array $segment
     * @param array $escapedIds
     * @return array $segment
     */
    protected function restoreSingleGTags($segment,$escapedIds){
        foreach($escapedIds as $id){
            $insId = mb_substr($id,0,  mb_strlen($id)-4);
            $segment = preg_replace('"(<)x([^>]*id=)\"'.$id.'(\"[^>]*/>)"s','\\1g\\2"'.$insId.'\\3', $segment);
        }
        return $segment;
    }
    /**
     * converts all single (self-closing) g-tags to a x-tag to work around problems in diff-tagging
     * must be used after tagBreakUp
     *
     * @param array $segment
     * @param array $escapedIds
     * @return array $segment
     */
    protected function escapeSingleGTags($segment,&$escapedIds){
        $regex = '"(.*<)g([^>]*id=\")([^\"]*)(\"[^>]*/>.*)"s';
        if(count(preg_grep($regex, $segment))==0){
            return $segment;
        }
        foreach($segment as $part){
            if(preg_match($regex, $part)){
                $escapedIds[] = preg_replace($regex,'\\3_esc', $part);
            }
        }
        return preg_replace($regex,'\\1x\\2\\3_esc\\4', $segment);
    }

    /**
     * joins for the diff the tags, which mark a term and all words within to one
     * string to avoid invalid xml after the diff (and it makes sense anyway, cause
     * a changed term should be marked as a whole, even if it consists of several words)
     *
     * @param array $segment
     * @return array $segment
     */
    protected function joinTerms($segment) {
        $termStart = false;
        $count = count($segment);
        for ($i = 0; $i < $count; $i++) {
            if (strpos($segment[$i], '<g id="term___mrkTag___') !== false) {
                $termStart = $segment[$i];
                unset($segment[$i]);
                continue;
            }
            if(strpos($segment[$i], '<mrk ') !== false || //if there is another none-Terminology-mrk-Tag inside the term-Tag
                strpos($segment[$i], '</g id="term___mrkTag___') !== false) {
                $segment[$i] = $termStart . $segment[$i];
                $termStart = false;
                continue;
            }
            if ($termStart) {
                $termStart .= $segment[$i];
                unset($segment[$i]);
            }
        }
        return $segment;
    }

    /**
     * returns array which gives information, which opening and which closing g-Tags have been changed or not changed where
     *
     * @param array $diffRes as returned by ZfExtended_Diff  - not modified by $this->markDeletion and this->markAddition
     * @return array $getGTagsInformation array with the formats
     * 	$getGTagsInformation[tagId][diffKey]['opening']['noChange']=''
     * 	$getGTagsInformation[tagId][diffKey]['opening']['added']=''
     *  $getGTagsInformation[tagId][diffKey]['opening']['deleted']=''
     * 	$getGTagsInformation[tagId][diffKey]['closing']['noChange']=''
     * 	$getGTagsInformation[tagId][diffKey]['closing']['added']=''
     *  $getGTagsInformation[tagId][diffKey]['closing']['deleted']=''
     */
    protected function getGTagsInformation($diffRes) {
        $self = $this;
        $getThem = function($getGTagsInformation, $parts, $key, $type)use($self) {
                    foreach ($parts as $part) {
                        $matches = array();
                        if (preg_match($self->_findGTagsIdAndTypeRegex, $part, $matches) === 1) {//Termtags are always defined as being moved as a whole. Even if an internal tag inside the term has been moved
                            if ($matches[1] === "") {
                                $getGTagsInformation[$matches[2]][$key]['opening'][$type] = ''; //format: $getGTagsInformation[tagId][diffKey]['opening']['noChange']=''
                            } else {
                                $getGTagsInformation[$matches[2]][$key]['closing'][$type] = '';
                            }
                        }
                    }
                    return $getGTagsInformation;
                };

        $getGTagsInformation = array();
        foreach ($diffRes as $key => $val) {
            if (is_string($val)) {
                $matches = array();
                if (preg_match($this->_findGTagsIdAndTypeRegex, $val, $matches) === 1) {//Termtags are always defined as being moved as a whole. Even if an internal tag inside the term has been moved
                    //there can only be one tag in a non-Termtag-diffPart, because of tagBreakUp and absence of a joined term-tag in this diffPart
                    if ($matches[1] === "") {
                        $getGTagsInformation[$matches[2]][$key]['opening']['noChange'] = ''; //format: $getGTagsInformation[tagId][diffKey]['opening']['noChange']=''
                    } else {
                        $getGTagsInformation[$matches[2]][$key]['closing']['noChange'] = '';
                    }
                }
            } elseif (is_array($val)) {
                $getGTagsInformation = $getThem($getGTagsInformation, $val['i'], $key, 'added');
                $getGTagsInformation = $getThem($getGTagsInformation, $val['d'], $key, 'deleted');
            } else {
                throw new Zend_Exception('val has an not defined variable-type');
            }
        }
        return $getGTagsInformation;
    }

    /**
     * this function has to take care of the fact, that tag-ids in a segment do not have to be unique. Therefore it is complex
     *
     * @param array $getGTagsInformation structure as returned by function getGTagsInformation
     * @return array getGTagsToComplement same array-format like getGTagsInformation, but only the tagIds included, for which some of the tags have been changed and some have not
     */
    protected function getGTagsToComplement($getGTagsInformation) {
        $getGTagsToComplement = array();
        foreach ($getGTagsInformation as $tagId => $val) {
            $lastOpeningDeleted = NULL;
            $lastClosingDeleted = NULL;
            $deletedClosingCounter = 0;
            $deletedOpeningCounter = 0;
            $addedOpening2Comp = array();
            $addedClosing2Comp = array();
            $lastOpeningNoChangeDiffres = false;
            $lastOpeningDeletedDiffres = false;
            for ($i = 0; $i < $this->_diffResPartCount; $i++) {
                if (isset($val[$i])) {
                    if (isset($val[$i]['opening'])) {
                        if (isset($val[$i]['opening']['deleted'])) {
                            $deletedOpeningCounter++;
                            if ($lastClosingDeleted === true) {//if last closing tag had been marked as deleted and had not already be matched with another opening tag to a pair, it matches with this one and will NOT be included in $getGTagsToComplement
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                #echo 'deleted$lastClosingDeleted === true';
                            } elseif ($lastClosingDeleted === false) {//if last closing tag had been marked as NOT deleted and had not already be matched with another opening tag to a pair, it will be included in $getGTagsToComplement
                                $getGTagsToComplement[$tagId][$i]['opening']['deleted'] = '';
                                //add the corresponding not changed closing tag to $getGTagsToComplement
                                if ($lastClosingNoChangeDiffres === false) {
                                    throw new Zend_Exception('lastClosingNoChangeDiffres had been false, but should be set. tagId: ' . $tagId . ' diffRes:' . $i);
                                }
                                $getGTagsToComplement[$tagId][$lastClosingNoChangeDiffres]['closing']['noChange'] = '';
                                $lastClosingNoChangeDiffres = false;
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                $addedOpening2Comp[$deletedOpeningCounter] = ''; //mark which in the order of the opening tags has to be completed too
                                #echo 'deleted$lastClosingDeleted === false';
                            } else {//there is not closing tag marked so far and not completed to a tag pair. Mark this opening tag as to be completed.
                                $lastOpeningDeleted = true;
                                $lastOpeningDeletedDiffres = $i;
                                $lastDeletedOpeningCounter = $deletedOpeningCounter;
                                #echo 'deleted$lastOpeningDeleted=true';
                            }
                        } elseif (isset($val[$i]['opening']['noChange'])) {
                            if ($lastClosingDeleted === true) {//if last closing tag had been marked as deleted and had not already be matched with another opening tag to a pair, it will be included in $getGTagsToComplement
                                $getGTagsToComplement[$tagId][$i]['opening']['noChange'] = '';
                                if ($lastClosingDeletedDiffres === false) {
                                    throw new Zend_Exception('lastClosingDeletedDiffres had been false, but should be set. tagId: ' . $tagId . ' diffRes:' . $i);
                                }
                                $getGTagsToComplement[$tagId][$lastClosingDeletedDiffres]['closing']['deleted'] = '';
                                $lastClosingDeletedDiffres = false;
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                $addedClosing2Comp[$lastDeletedClosingCounter] = ''; //mark which in the order of the opening tags has to be completed too
                                #echo 'noChange$lastClosingDeleted === true';
                            } elseif ($lastClosingDeleted === false) {//if last closing tag had been marked as NOT deleted and had not already be matched with another opening tag to a pair, it matches with this one and will NOT be included in $getGTagsToComplement
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                #echo 'noChange$lastClosingDeleted === false';
                            } else {//there is not closing tag marked so far and not completed to a tag pair. Mark this opening tag as to be completed.
                                $lastOpeningDeleted = false;
                                $lastOpeningNoChangeDiffres = $i;
                                #echo 'noChange$lastOpeningDeleted=false';
                            }
                        } elseif (!isset($val[$i]['opening']['added'])) {
                            throw new Zend_Exception('opening had been wether deleted nor added nor noChange');
                        }
                    }
                    if (isset($val[$i]['closing'])) {
                        //we only have a look for deleted and not for added tags, since always one added and one deleted should match one noChange-Tag. Or there is not added tag and a tag had only be deleted.
                        //The case that a tag had only be added but not deleted (and since additionally added) will not be handeled, since it should be made impossible by a future feature to verify tags in the browser.
                        if (isset($val[$i]['closing']['deleted'])) {
                            $deletedClosingCounter++;
                            if ($lastOpeningDeleted === true) {//if last opening tag had been marked as deleted and had not already be matched with another opening tag to a pair, it matches with this one and will NOT be included in $getGTagsToComplement
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                #echo 'deleted$lastOpeningDeleted === true';
                            } elseif ($lastOpeningDeleted === false) {//if last opening tag had been marked as NOT deleted and had not already be matched with another opening tag to a pair, it will be included in $getGTagsToComplement
                                $getGTagsToComplement[$tagId][$i]['closing']['deleted'] = '';
                                //add the corresponding not changed opening tag to $getGTagsToComplement
                                if ($lastOpeningNoChangeDiffres === false) {
                                    throw new Zend_Exception('lastOpeningNoChangeDiffres had been false, but should be set. tagId: ' . $tagId . ' diffRes:' . $i);
                                }
                                $getGTagsToComplement[$tagId][$lastOpeningNoChangeDiffres]['opening']['noChange'] = '';
                                $lastOpeningNoChangeDiffres = false;
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                $addedClosing2Comp[$deletedClosingCounter] = ''; //mark which in the order of the opening tags has to be completed too
                                #echo 'deleted$lastOpeningDeleted === false';
                            } else {//there is not opening tag marked so far and not completed to a tag pair. Mark this closing tag as to be completed.
                                $lastClosingDeleted = true;
                                $lastClosingDeletedDiffres = $i;
                                $lastDeletedClosingCounter = $lastOpeningDeletedDiffres;
                                #echo 'deleted$lastClosingDeleted=true';
                            }
                        } elseif (isset($val[$i]['closing']['noChange'])) {
                            if ($lastOpeningDeleted === true) {//if last opening tag had been marked as deleted and had not already be matched with another opening tag to a pair, it will be included in $getGTagsToComplement
                                $getGTagsToComplement[$tagId][$i]['closing']['noChange'] = '';
                                //add the corresponding changed opening tag to $getGTagsToComplement
                                if ($lastOpeningDeletedDiffres === false) {
                                    throw new Zend_Exception('lastOpeningDeletedDiffres had been false, but should be set. tagId: ' . $tagId . ' diffRes:' . $i);
                                }
                                $getGTagsToComplement[$tagId][$lastOpeningDeletedDiffres]['opening']['deleted'] = '';
                                $lastOpeningDeletedDiffres = false;
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                $addedOpening2Comp[$lastDeletedOpeningCounter] = ''; //mark which in the order of the opening tags has to be completed too
                                #echo 'noChange$lastOpeningDeleted === true';
                            } elseif ($lastOpeningDeleted === false) {//if last opening tag had been marked as NOT deleted and had not already be matched with another opening tag to a pair, it matches with this one and will NOT be included in $getGTagsToComplement
                                $lastClosingDeleted = NULL;
                                $lastOpeningDeleted = NULL;
                                #echo 'noChange$lastOpeningDeleted === false';
                            } else {//there is not opening tag marked so far and not completed to a tag pair. Mark this closing tag as to be completed.
                                $lastClosingDeleted = false;
                                $lastClosingNoChangeDiffres = $i;
                                #echo 'noChange$lastClosingDeleted=false';
                            }
                        } elseif (!isset($val[$i]['closing']['added'])) {
                            throw new Zend_Exception('closing had been wether deleted nor added nor noChange');
                        }
                    }

                    if (!(isset($val[$i]['opening']) or isset($val[$i]['closing']))) {
                        throw new Zend_Exception('wether opening nor closing had been defined');
                    }
                }
            }
            if ($lastOpeningDeleted) {
                throw new Zend_Exception('there are more opening tags then closing tags for the tags with the id ' . $tagId);
            }
            if ($lastClosingDeleted) {
                throw new Zend_Exception('there are more closing tags then opening tags for the tags with the id ' . $tagId);
            }
            $addedOpening2CompCounter = 0;
            $addedClosing2CompCounter = 0;
            for ($i = 0; $i < $this->_diffResPartCount; $i++) {//now have a look for added tags to be completed
                if (isset($val[$i])) {
                    if (isset($val[$i]['closing']['added'])) {
                        $addedClosing2CompCounter++;
                        if (isset($addedClosing2Comp[$addedClosing2CompCounter])) {
                            $getGTagsToComplement[$tagId][$i]['closing']['added'] = '';
                            unset($addedClosing2Comp[$addedClosing2CompCounter]);
                        }
                    } elseif (isset($val[$i]['opening']['added'])) {
                        $addedOpening2CompCounter++;
                        if (isset($addedOpening2Comp[$addedOpening2CompCounter])) {
                            $getGTagsToComplement[$tagId][$i]['opening']['added'] = '';
                            unset($addedOpening2Comp[$addedOpening2CompCounter]);
                        }
                    }
                }
            }
            if (count($addedClosing2Comp) > 0) {
                throw new Zend_Exception('not all added closing tags have been found for all deleted closing tags');
            }
            if (count($addedOpening2Comp) > 0) {
                throw new Zend_Exception('not all added opening tags have been found for all deleted opening tags');
            }
        }
        return $getGTagsToComplement;
    }

    /**
     * add the sourounding mrk-Tags for the sdl-change-markers to the i-subarray
     * of a specific changed part of a segment in the return-array of ZfExtended_Diff
     *
     * @param array $i Array('hinzugefügt')
     * @return string additions inclosed by '<mrk mtype="x-sdl-added" sdl:revid="'.$guid.'">'addition'</mrk>' or empty string if no Addition
     */
    protected function markAddition($i) {
        if (count($i) > 0) {
            $openingEndFalseArr = array();
            foreach ($i as $key => &$val) {
                if($val === '')continue;
                if (preg_match($this->_findGTagsIdAndTypeRegex, $val, $matches) === 1 and
                        strpos($val, 'sdl:end') === false and
                        strpos($val, 'sdl:start') === false) {//a termtag is always only in a diff-part as a complete termtag. g-tags inside of it must also be complete, because terms with incomplete tags inside it do not get marked
                    if ($matches[1] === '') {//if opening g-Tag
                        $openingEndFalseArr[$matches[2]][$key] = '';
                    } elseif ($matches[1] === '/') {//if closing g-tag
                        if (isset($openingEndFalseArr[$matches[2]])) {//if there is an opening tag with the same id, do not mark this one as start=false
                            array_pop($openingEndFalseArr[$matches[2]]);
                        } else {
                            $val = str_replace(array('</g', '>'), array('<g', ' sdl:start="false" />'), $val);
                        }
                    } else {
                        throw new Zend_Exception('tag had wether been an opening nor a closing g-Tag, but the pattern ' .
                                $this->_findGTagsIdAndTypeRegex . ' matched :-(');
                    }
                }
            }
            foreach ($i as $key => &$val) {
                if($val === '')continue;
                if (preg_match($this->_findGTagsIdAndTypeRegex, $val, $matches) === 1 //a termtag is always only in a diff-part as a complete termtag. g-tags inside of it must also be complete, because terms with incomplete tags inside it do not get marked
                        and $matches[1] === '' and isset($openingEndFalseArr[$matches[2]][$key])) {
                    $val = str_replace('>', ' sdl:end="false" />', $val);
                }
            }
            $addition = implode('', $i);
            if($addition === '') {
                return '';
            }
            return '<mrk mtype="x-sdl-added" sdl:revid="' .
                $this->addRevision($this->_changeTimestamp, $this->_userName, true)
                    . '">' . $addition . '</mrk>';
        }
        return '';
    }

    /**
     * add the surounding mrk-Tags for the sdl-change-markers to the d-subarray
     * of a specific changed part of a segment in the return-array of ZfExtended_Diff
     *
     * -  adds sdl:start and sdl:end-attributes to G-Tags, which changed positions (the others are done directly in $this->diffSegment)
     *
     * @param array $d Array('deleted')
     * @param array $getGTagsToComplement array as returned by function getGTagsToComplement
     * @param int $diffKey
     * @return string deletions inclosed by <mrk mtype="x-sdl-deleted" sdl:revid="REVID">'deletions'</mrk>' )
     */
    protected function markDeletion($d, $getGTagsToComplement, $diffKey) {
        if (count($d) > 0) {
            $end = '</mrk>';
            $openingEndFalseArr = array();
            foreach ($d as $key => &$val) {
                if($val === '')continue;
                if (preg_match($this->_findGTagsIdAndTypeRegex, $val, $matches) === 1 and
                        strpos($val, 'sdl:end') === false and
                        strpos($val, 'sdl:start') === false) {
                    if ($matches[1] === '') {//if opening g-Tag
                        $openingEndFalseArr[$matches[2]][$key] = '';
                    } elseif ($matches[1] === '/') {//if closing g-tag
                        if (isset($openingEndFalseArr[$matches[2]])) {//if there is an opening tag with the same id, do not mark this one as start=false
                            array_pop($openingEndFalseArr[$matches[2]]);
                        } else {
                            if (isset($getGTagsToComplement[$matches[2]][$diffKey]['closing']['deleted'])) {//if only the closing g-tag is deleted or moved
                                if(strpos($val, '___mrkTag___') !== false){//if the tag had been a ___mrkTag___
                                    $end .= '</mrk>';
                                }
                                else{
                                    $end .= '</g>';
                                }

                            }
                            $val = str_replace(array('</g', '>'), array('<g', ' sdl:start="false" />'), $val);
                        }
                    } else {
                        throw new Zend_Exception('tag had wether been an opening nor a closing g-Tag, but the pattern ' .
                                $this->_findGTagsIdAndTypeRegex . ' matched :-(');
                    }
                }
            }
            foreach ($d as $key => &$val) {
                if($val === '')continue;
                if (preg_match($this->_findGTagsIdAndTypeRegex, $val, $matches) === 1 //a termtag is always only in a diff-part as a complete termtag. g-tags inside of it must also be complete, because terms with incomplete tags inside it do not get marked
                        and $matches[1] === '' and isset($openingEndFalseArr[$matches[2]][$key])) {
                    if (isset($getGTagsToComplement[$matches[2]][$diffKey]['opening']['deleted'])) {//if only the open g-tag is deleted or moved, add in accordance with sdlxliff another opening g-Tag
                        $end .= str_replace('>', ' sdl:start="false">', $val);
                    }
                    $val = str_replace('>', ' sdl:end="false" />', $val);
                }
            }
            $deletion = implode('', $d);
            if($deletion === ''){
                return '';
            }
            return '<mrk mtype="x-sdl-deleted" sdl:revid="' .
                $this->addRevision($this->_changeTimestamp, $this->_userName, false) . '">' .
                    $deletion . $end;
        }
        return '';
    }

    /**
     * ersetzt die gepaarten <mrk> Tags (also nicht in sich geschlossen) im Inhalt des Target-Segments. Dabei ist lediglich erkenntlich, welcher
     * End Tag zu welchem Start Tag gehört. Die Tags sind im Diff wie g-Tags zu behandeln, daher werden sie der Einfachheit halber hier in solche ersetzt
     * Aus foo<mrk RESTOFTAG>bar</g> wird
     * foo<g id="unpack('H*', 'RESTOFTAG')">bar</g id="unpack('H*', 'RESTOFTAG')">
     * Sinn ist bei Verschiebungen im diff erkennen zu können, welcher schließende
     * Tag zu welchem öffnenden gehört.
     * @param array $segment
     * @return array $segment
     */
    protected function replacePairMrkTags($segment) {
        $count = count($segment);
        $open = 0;
        $openIds = array();
        //parse nur die ungeraden Arrayelemente, denn dies sind die Tags
        for ($i = 1; $i < $count; $i++) {
            if (strpos($segment[$i], '<mrk ') !== false and strrpos($segment[$i], '/>') === false) {//strrpos($segment[$i], '/>')===false   kann entfallen, sobald keine vor dem 31.2.2013 importierten Projekte mehr in der DB sind
                $open++;
                $id = '___mrkTag___'.implode(',', unpack('H*', preg_replace('".*<mrk ([^>]*)>.*"', '\\1', $segment[$i])));
                if (strpos($segment[$i], 'mtype="x-term') !== false) {
                    $id = 'term'.$id;
                }
                $segment[$i] = '<g id="'.$id.'">';
                $openIds[$open] = $id;
            } elseif (strpos($segment[$i], '</mrk>') !== false) {
                if (!isset($openIds[$open])) {
                    //In this segment for one closing tag no corresponding opening tag exists - or the tagorder had been syntactically incorrect already before the import in the editor. Therefore it is not possible to create an export with sdl-change-marks in it. Try to export without change-marks. The Segment had been: "{segment}"
                    throw new editor_Models_Export_DiffTagger_Exception('E1089',[
                        'segment' => implode('', $segment),
                    ]);
                }
                $segment[$i] = str_replace('</mrk', '</g id="' . $openIds[$open] . '"', $segment[$i]);
                unset($openIds[$open]);
                $open--;
            }
            $i++;
        }
        if (count($openIds) > 0) {
            //The number of opening and closing g-Tags had not been the same!
            throw new editor_Models_Export_DiffTagger_Exception('E1090',[
                'segment' => implode('', $segment),
            ]);
        }
        return $segment;
    }

    /**
     * ersetzt die <g> Tags im Inhalt des Target-Segments. Dabei ist lediglich erkenntlich, welcher
     * End Tag zu welchem Start Tag gehört.
     * Aus foo<g id="X">bar</g> wird
     * foo<g id="X">bar</g id="X">
     * Sinn ist bei Verschiebungen im diff erkennen zu können, welcher schließende
     * Tag zu welchem öffnenden gehört.
     * @param array $segment
     * @return array $segment
     */
    protected function replacePairGTagsTarget($segment) {
        $count = count($segment);
        $open = 0;
        $openIds = array();
        //parse nur die ungeraden Arrayelemente, denn dies sind die Tags
        for ($i = 1; $i < $count; $i++) {
            if (strpos($segment[$i], '<g ') !== false and strrpos($segment[$i], '/>') === false) {//strrpos($segment[$i], '/>')===false   kann entfallen, sobald keine vor dem 31.2.2013 importierten Projekte mehr in der DB sind
                $open++;
                $openIds[$open] = preg_replace('".*<g[^>]*id=\"([^\"]+)\".*"', '\\1', $segment[$i]);
            } elseif (strpos($segment[$i], '</g>') !== false) {
                if (!isset($openIds[$open])) {
                    //In this segment for one closing tag no corresponding opening tag exists - or the tagorder had been syntactically incorrect already before the import in the editor. Therefore it is not possible to create an export with sdl-change-marks in it. Try to export without change-marks.
                    throw new editor_Models_Export_DiffTagger_Exception('E1091',[
                        'segment' => implode('', $segment),
                    ]);
                }
                $segment[$i] = str_replace('</g', '</g id="' . $openIds[$open] . '"', $segment[$i]);
                unset($openIds[$open]);
                $open--;
            }
            $i++;
        }
        if (count($openIds) > 0) {
            //The number of opening and closing g-Tags had not been the same! The Segment had been: ' . implode('', $segment), E_USER_ERROR);
            throw new editor_Models_Export_DiffTagger_Exception('E1092',[
                'segment' => implode('', $segment),
            ]);
        }
        return $segment;
    }

    /**
     * ersetzt die <g> Tags im Inhalt des Edited-Segments. Dabei ist lediglich erkenntlich, welcher
     * End Tag zu welchem Start Tag gehört.
     * Aus foo<g id="X">bar</g> wird
     * foo<g id="X">bar</g id="X">
     * Sinn ist bei Verschiebungen im diff erkennen zu können, welcher schließende
     * Tag zu welchem öffnenden gehört.
     * Um schliessende G-Tags in der richtigen Reihenfolge IDs der öffenden Tags
     * zuweise zu können und trotzdem auch mit syntaktisch fehlerhaften Verschiebungen
     * umgehen zu können (schließender Tag vor zugehörigem öffnenden) werden alle
     * schließenden g-Tags, die nicht direkt einem öffnenden zugeordnet werden konnten
     * einem beliebigen noch nicht geschlossenen g-Tag zugeordnet.
     * @param array $segment
     * @return array $segment
     */
    protected function replacePairGTagsEdited($segment) {
        $count = count($segment);
        $open = 0;
        $openIds = array();
        $still2Mark = array();
        //parse nur die ungeraden Arrayelemente, denn dies sind die Tags
        for ($i = 1; $i < $count; $i++) {
            if (strpos($segment[$i], '<g ') !== false and strrpos($segment[$i], '/>') === false) {//strrpos($segment[$i], '/>')===false   kann entfallen, sobald keine vor dem 31.2.2013 importierten Projekte mehr in der DB sind
                $open++;
                $openIds[$open] = preg_replace('".*<g[^>]*id=\"([^\"]+)\".*"', '\\1', $segment[$i]);
            } elseif (strpos($segment[$i], '</g>') !== false) {
                if (count($openIds) > 0) {
                    $id = array_pop($openIds);
                    $segment[$i] = str_replace('</g', '</g id="' . $id . '"', $segment[$i]);
                    $open--;
                } else {
                    $still2Mark[$i] = '';
                }
            }
            $i++;
        }
        foreach ($still2Mark as $i => $val) {
            if (count($openIds) < 1) {
                //The number of opening and closing g-Tags had not been the same!
                throw new editor_Models_Export_DiffTagger_Exception('E1093',[
                    'segment' => implode('', $segment),
                ]);
            }
            $id = array_pop($openIds);
            $segment[$i] = str_replace('</g', '</g id="' . $id . '"', $segment[$i]);
            $open--;
        }
        return $segment;
    }

    /**
     * splits the segment up into HTML tags / entities on one side and plain text on the other side
     * The order in the array is important for the following wordBreakUp, since there are HTML tags and entities ignored.
     * Caution: The ignoring is done by the array index calculation there!
     * So make no array structure changing things between word and tag break up!
     *
     * Sdlxliff has different regex as the csv.
     *
     * @param string $segment
     * @return array $segment
     */
    protected  function tagBreakUp($segment){
        return editor_Utils::tagBreakUp($segment,'/(<[^>]*>|&[^;]+;)/');
    }

    /**
     * wrapper function to be called by the testsuite in a worker to test an endless loop in the diff algorithm
     * @param string|null $taskGuid
     * @param array $params
     */
    public function diffTestCall(?string $taskGuid, array $params) {
        $result = $this->diffSegment($params['target'], $params['edited'], $params['date'], $params['name']);
        if(preg_replace('/sdl:revid="[^"]+"/', 'sdl:revid="XXX"', $result) !== $params['result']) {
            throw new Exception('The result is not as expected.');
        }
    }
}
