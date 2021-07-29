<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Compares two field-tags if they have the same amount of internal tags in the same order
 */
class editor_Segment_Internal_TagComparision {
    
    /**
     * Evaluates if the given quality type & cateogory represents a fault
     * This is to keep the code flexible in terms of evaluating if a task or segment has faulty internal tags
     * @param string $type
     * @param string $category
     * @return bool
     */
    public static function isFault(string $type, string $category) : bool {
        // we take the virtual category TAG_STRUCTURE_FAULTY_NONEDITABLE also into account to keep the API independent of the usage context
        return ($type == editor_Segment_Tag::TYPE_INTERNAL && ($category == self::TAG_STRUCTURE_FAULTY || $category == self::TAG_STRUCTURE_FAULTY_NONEDITABLE));
    }
    /**
     * @var string
     */
    const TAGS_MISSING = 'internal_tags_missing';
    /**
     * @var string
     */
    const WHITESPACE_MISSING = 'whitespace_tags_missing';
    /**
     * @var string
     */    
    const TAGS_ADDED = 'internal_tags_added';
    /**
     * @var string
     */
    const WHITESPACE_ADDED = 'whitespace_tags_added';
    /**
     * @var string
     */
    const TAG_STRUCTURE_FAULTY = 'internal_tag_structure_faulty';
    /**
     * This is a purely virtual category that is created when fetching qualities, it will not be used in the database
     * Non-editable/locked segments will have a different category when having tag-errors
     * @var string
     */
    const TAG_STRUCTURE_FAULTY_NONEDITABLE = 'internal_tag_structure_faulty_noneditable'; 
    /**
     * 
     * @var string;
     */
    private $stati = [];
    /**
     * 
     * @var editor_Segment_Internal_Tag[]
     */    
    private $checkTags = [];
    /**
     * 
     * @var int
     */
    private $numCheckTags = 0;
    /**
     *
     * @var editor_Segment_Internal_Tag[]
     */ 
    private $againstTags = [];
    /**
     *
     * @var int
     */
    private $numAgainstTags = 0;
    /**
     * 
     * @param editor_Segment_FieldTags $toCheck
     * @param editor_Segment_FieldTags $against
     */
    public function __construct(editor_Segment_FieldTags $toCheck, ?editor_Segment_FieldTags $against){
        $this->status = array();
        $toCheck->sort();
        $this->checkTags = $toCheck->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        $this->numCheckTags = count($this->checkTags);
        // the structural check can be done without against tags
        $this->checkStructure();
        // there is a against and it is not empty and toCheck also is not empty
        if($against != NULL && !$against->isEmpty() && !$toCheck->isEmpty()){
            $against->sort();
            $this->againstTags = $against->getByType(editor_Segment_Tag::TYPE_INTERNAL);
            $this->numAgainstTags = count($this->againstTags);
            // for the completeness check we need something to check against
            $this->checkCompleteness();
        }
    }
    /**
     * Here we check if all tags from checkAgainst are present in the check tags
     */
    private function checkCompleteness(){
        $states = [];
        $hashesInternalCheck = [];
        $hashesWhitespaceCheck = [];
        $hashesInternalAgainst = [];
        $hashesWhitespaceAgainst = [];        
        foreach($this->checkTags as $tag){
            /* @var $tag editor_Segment_Internal_Tag */
            if($tag->isWhitespace()){
                $hashesWhitespaceCheck[] = $tag->getHash();
            } else {
                $hashesInternalCheck[] = $tag->getHash();
            }
        }
        foreach($this->againstTags as $tag){
            /* @var $tag editor_Segment_Internal_Tag */
            if($tag->isWhitespace()){
                $hashesWhitespaceAgainst[] = $tag->getHash();
            } else {
                $hashesInternalAgainst[] = $tag->getHash();
            }
        } 
        // first, we compare 'check' against 'against', this will give uns added tags
        $diffInternal = array_diff($hashesInternalCheck, $hashesInternalAgainst);
        $diffWhitespace = array_diff($hashesWhitespaceCheck, $hashesWhitespaceAgainst);
        if(count($diffInternal) > 0){
            $states[self::TAGS_ADDED] = true;
        }
        if(count($diffWhitespace) > 0){
            $states[self::WHITESPACE_ADDED] = true;
        }
        
        $diffInternal = array_diff($hashesInternalAgainst, $hashesInternalCheck);
        $diffWhitespace = array_diff($hashesWhitespaceAgainst, $hashesWhitespaceCheck);
        if(count($diffInternal) > 0){
            $states[self::TAGS_MISSING] = true;
        }
        if(count($diffWhitespace) > 0){
            $states[self::WHITESPACE_MISSING] = true;
        }
        if(count($states) > 0){
            $this->stati = array_merge($this->stati, array_keys($states));
        } else if($this->numCheckTags != $this->numAgainstTags) {
            // if we could not find any differences but the number of tags is different we must assume, that there are some identical tags (presumably through duplication)
            // for now this is also a 'TAG_STRUCTURE_FAULTY' But we could be more specific here
            // TODO AutoQA: we may better add a state "duplicated_tags_present" here
            $this->stati[] = self::TAG_STRUCTURE_FAULTY;
        }
    }
    /**
     * Checks if the structure of internal tags is correct, every opener has his corresponding closer without overlaps
     */
    private function checkStructure(){
        if($this->numCheckTags == 0){
            return;
        }
        $numOpeners = 0;
        // check every opener if it has a corresponding closer with no overlaps inbetween
        for($i=0; $i < $this->numCheckTags; $i++){
            if($this->checkTags[$i]->isOpening()){
                $numOpeners++;
                if(!$this->nextClosingMatches($this->checkTags[$i]->getTagIndex(), $i + 1)){
                    $this->stati[] = self::TAG_STRUCTURE_FAULTY;
                    return;
                }
            }
        }
        // if we come that close we just check if we have the same amount of openers/closers t make sure there is no orphan closer ...
        $numClosers = 0;
        for($i=0; $i < $this->numCheckTags; $i++){
            if($this->checkTags[$i]->isClosing()){
                $numClosers++;
            }
        }
        if($numOpeners != $numClosers){
            $this->stati[] = self::TAG_STRUCTURE_FAULTY;
        }
    }
    /**
     * Finds for an opener the corresponding closer, accepts openers/closers inbetween as long as they the number of openers & closers equals
     * @param int $tagIndex
     * @param int $start
     * @return bool
     */
    private function nextClosingMatches(int $tagIndex, int $start) : bool {
        if($start >= $this->numCheckTags){
            return false;
        }
        $numOpen = 0;
        for($i=$start; $i < $this->numCheckTags; $i++){            
            if($this->checkTags[$i]->isClosing() && $this->checkTags[$i]->getTagIndex() == $tagIndex){
                // we only are the "correct" closer if no other tags are open or closed (the value will be negative then)
                // Note that we do not check the opened/closed tags for validity, this will be handled by the checks for those openers ...
                return ($numOpen === 0);
            } else if($this->checkTags[$i]->isOpening()) {
                $numOpen++;
            } else if($this->checkTags[$i]->isClosing()){
                $numOpen--;
                if($numOpen < 0){
                    // as soon as we have an closing tag (no the one we are searching for) and no opening tag before this is a structural fault
                    return false;
                }
            }
        }
        // closer not found
        return false;
    }
    /**
     * 
     * @return string[]
     */
    public function getStati(){
        return $this->stati;
    }
}
