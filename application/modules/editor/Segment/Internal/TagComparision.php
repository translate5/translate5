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
 * Compares two field-tags if they have the same amount and type of internal tags and if these tags have a valid structure (no overlapy, proper nesting)
 */
class editor_Segment_Internal_TagComparision extends editor_Segment_Internal_TagCheckBase {
    
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
    private $againstTags = [];
    /**
     *
     * @var int
     */
    private $numAgainstTags = 0;

    public function __construct(editor_Segment_FieldTags $toCheck, editor_Segment_FieldTags $against=NULL){
        parent::__construct($toCheck, $against);
        // the structural check can be done without against tags
        $this->checkStructure();
        // there is a against and it is not empty and toCheck also is not empty
        if($against != NULL && !$against->isEmpty() && !$toCheck->isEmpty()){
            $against->sort();
            $this->againstTags = $this->extractRelevantTags($against);
            $this->numAgainstTags = count($this->againstTags);
            // for the completeness check we need something to check against
            $this->checkCompleteness();
        }
        $this->stati = array_unique($this->stati);
    }

    /**
     * For Tag-Comparision special characters protected as tags are irrelevant
     * @param editor_Segment_FieldTags $fieldTags
     * @return editor_Segment_Internal_Tag[]
     */
    protected function extractRelevantTags(editor_Segment_FieldTags $fieldTags) : array
    {
        $relevantTags = [];
        foreach ($fieldTags->getByType(editor_Segment_Tag::TYPE_INTERNAL) as $internalTag) {
            /* @var $internalTag editor_Segment_Internal_Tag */
            if (!$internalTag->isSpecialCharacter()) {
                $relevantTags[] = $internalTag;
            }
        }
        return $relevantTags;
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
                $hashesWhitespaceCheck[] = $tag->getComparisionHash();
            } else {
                $hashesInternalCheck[] = $tag->getComparisionHash();
            }
        }
        foreach($this->againstTags as $tag){
            /* @var $tag editor_Segment_Internal_Tag */
            if($tag->isWhitespace()){
                $hashesWhitespaceAgainst[] = $tag->getComparisionHash();
            } else {
                $hashesInternalAgainst[] = $tag->getComparisionHash();
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
        // check every opener if it has a corresponding closer with no overlaps inbetween
        // The idea of this algorithm is, if every existing tag-pair is checked, so that is has no overlapping tags in between (which are not checked for overlaps between them), the whole structure must be valid
        // closing tags can be skipped in this test, only orphan closers are obvious faults
        for($i=0; $i < $this->numCheckTags; $i++){
            // check the tags in a loop, where closing tags with counterparts can be excluded to avoid duplicate checks, they are checked with their opening counterpart
            if((!$this->checkTags[$i]->isSingle() && $this->checkTags[$i]->counterpart == NULL) || ($this->checkTags[$i]->isOpening() && !$this->isStructurallyValid($this->checkTags[$i]))){
                $this->stati[] = self::TAG_STRUCTURE_FAULTY;
                return;
            }
        }
    }
    /**
     * Retrieves the internal tag states of the field tags to compare
     * @return string[]
     */
    public function getStati(): array {
        return $this->stati;
    }

    /**
     * Retrieves if the field tags have faulty internal tags
     * @return bool
     */
    public function hasFaults(): bool {
        return in_array(self::TAG_STRUCTURE_FAULTY, $this->stati);
    }
}
