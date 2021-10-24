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
 * Removes structural Tag-Errors from the passed field tags.
 * Relies on the base-code used for the detection of tag errors
 * Currently this does not remove duplicate tags like <1> ... </1> ... <1> ... </1> it only corrects faulty structure
 */
class editor_Segment_Internal_TagRepair extends editor_Segment_Internal_TagCheckBase {
    /**
     *
     * @var int;
     */
    private $numRemoved = 0;
    /**
     *
     * @var int;
     */
    private $numFixed = 0;
    /**
     * Holds the ending word-boundries
     * @var array
     */
    private $wordEnds = [];
    /**
     *
     * @var int
     */
    private $textLength = -1;
    /**
     *
     * @var editor_Segment_Internal_Tag[]
     */
    private $resultTags = [];

    public function __construct(editor_Segment_FieldTags $toRepair, editor_Segment_FieldTags $against=NULL){
        parent::__construct($toRepair, $against);
        $this->fixStructure();
    }
    /**
     * Checks if the structure of internal tags is correct, every opener has his corresponding closer without overlaps
     */
    private function fixStructure(){
        if($this->numCheckTags == 0){
            return;
        }
        $brokenOpeners = [];
        // every detected faulty tag with counterpart will be attempted to fix, incomplete will be discarded
        for($i=0; $i < $this->numCheckTags; $i++){
            $tag = $this->checkTags[$i];
            if($tag->isSingle()){
                // pass through singular tags
                $this->resultTags[] = $tag;
            } else if($tag->counterpart == NULL){
                // remove incomplete tags
                $this->numRemoved++;
            } else if($tag->isOpening() && !$this->isStructurallyValid($tag)){
                // cache broken pairs with incorrect structure
                $brokenOpeners[] = $tag;
            } else if($tag->isOpening()){
                $this->resultTags[] = $tag;
                $this->resultTags[] = $tag->counterpart;
            }
        }
        $numBroken = count($brokenOpeners);
        if($numBroken > 0){
            $this->findBoundries();
            usort($this->resultTags, array($this->fieldTags, 'compare'));
            for($i=0; $i < $numBroken; $i++){
                if($this->insertTagsToResult($brokenOpeners[$i], $brokenOpeners[$i]->counterpart)){
                    $this->numFixed += 2;
                } else {
                    $this->numRemoved += 2;
                }
            }
        }
        if($this->hadErrors()){
            // remove the internal tags and merge the remaining tags with the new internal tags. Then we reset the tags and re-add the evaluated
            // this is needed to ensure the order in the Fieldtags is correct
            $this->fieldTags->removeByType(editor_Segment_Tag::TYPE_INTERNAL);
            $all = array_merge($this->fieldTags->getAll(), $this->resultTags);
            usort($all, array($this->fieldTags, 'compare'));
            $this->fieldTags->removeAll();
            foreach($all as $tag){
                $this->fieldTags->addTag($tag);
            }
        }
    }
    /**
     * Tries to insert the end-tag of a pair at the current field's text word-ends
     * @param editor_Segment_Internal_Tag $start
     * @param editor_Segment_Internal_Tag $end
     * @return boolean
     */
    private function insertTagsToResult(editor_Segment_Internal_Tag $start, editor_Segment_Internal_Tag $end){
        // special case: tags are swapped
        if($start->startIndex > $end->endIndex && $this->hasNoOverlapsBetweenIndices($start->endIndex, $end->startIndex)){
            $startIdx = $start->startIndex;
            $endIdx = $start->endIndex;
            $start->startIndex = $end->startIndex;
            $start->endIndex = $end->endIndex;
            $end->startIndex = $startIdx;
            $end->endIndex = $endIdx;
            $this->addToResult($end, $start);
            return true;
        }
        foreach($this->wordEnds as $textIndex){
            if($textIndex > $start->endIndex && $this->hasNoOverlapsBetweenIndices($start->endIndex, $textIndex)){
                $end->startIndex = $end->endIndex = $textIndex;
                $this->addToResult($start, $end);
                return true;
            }
        }
        return false;
    }
    /**
     * Just adds tags to the result array and sorts the result afterwards
     * @param editor_Segment_Internal_Tag $start
     * @param editor_Segment_Internal_Tag $end
     * @return boolean
     */
    private function addToResult(editor_Segment_Internal_Tag $start, editor_Segment_Internal_Tag $end){
        $this->resultTags[] = $start;
        $this->resultTags[] = $end;
        usort($this->resultTags, array($this->fieldTags, 'compare'));
    }
    /**
     * Checks, if there are overlapping tags between tag-indices
     * @param int $startTextIndex
     * @param int $endTextIndex
     * @return bool
     */
    private function hasNoOverlapsBetweenIndices(int $startTextIndex, int $endTextIndex) : bool {
        foreach($this->resultTags as $tag){
            if($tag->startIndex < $endTextIndex && $tag->endIndex > $startTextIndex && ($tag->isOpening() || $tag->isClosing())){
                if($tag->endIndex > $endTextIndex || $tag->startIndex < $startTextIndex || $tag->counterpart->endIndex > $endTextIndex || $tag->counterpart->startIndex < $startTextIndex){
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Finds the word-boundries within the fieldtext
     * Note that this is only a very rough approach and is no "exact science" like a real segmentation
     */
    private function findBoundries(){
        if($this->textLength == -1){
            $text = $this->fieldTags->getFieldText();
            $this->textLength = $this->fieldTags->getFieldTextLength();
            for($i=0; $i < $this->textLength; $i++){
                if($text[$i] == ' ' || ctype_punct($text[$i])){
                    if($i > 0 && $text[$i - 1] != ' ' && !ctype_punct($text[$i - 1])){
                        $this->wordEnds[] = $i;
                    }
                }
            }
        }
    }
    /**
     * Retrieves if internal tags have been faulty and had to be removed or corrected
     * @return bool
     */
    public function hadErrors() : bool {
        return ($this->numFixed > 0 || $this->numRemoved > 0);
    }
}
