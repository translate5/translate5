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
 * Base class for comparing tags or repairing tag faults
 * This code seperates the internal tags from the field tags and finds the counterpart for every non-single internal tag - if there is one
 * This is the base for the Tag-Comparision in the AutoQA as well as the automatic tag repair
 */
class editor_Segment_Internal_TagCheckBase {
    
    /**
     *
     * @var editor_Segment_Internal_Tag[]
     */
    protected $checkTags = [];
    /**
     *
     * @var int
     */
    protected $numCheckTags = 0;
    /**
     * 
     * @var editor_Segment_FieldTags
     */
    protected $fieldTags;
    /**
     * 
     * @param editor_Segment_FieldTags $toCheck
     * @param editor_Segment_FieldTags $against
     */
    public function __construct(editor_Segment_FieldTags $toCheck, editor_Segment_FieldTags $against=NULL){
        $this->fieldTags = $toCheck;
        $this->fieldTags->sort();
        $this->checkTags = $toCheck->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        $this->numCheckTags = count($this->checkTags);
        // the structural check can be done without against tags
        $this->findCounterparts();
    }
    /**
     * Finds for an opener the corresponding closer, no matter if there are overlaps or anything else
     */
    protected function findCounterparts() {
        for($i=0; $i < $this->numCheckTags; $i++){
            $this->checkTags[$i]->_idx = $i;
            if($this->checkTags[$i]->isOpening() && $this->checkTags[$i]->counterpart == NULL){
                $tagIndex = $this->checkTags[$i]->getTagIndex();
                if($tagIndex > -1){
                    // finding counterpart forward
                    if($i < $this->numCheckTags - 1){
                        for($j = $i + 1; $j < $this->numCheckTags; $j++){
                            if($j != $i && $this->checkTags[$j]->isClosing() && $this->checkTags[$j]->counterpart == NULL && $this->checkTags[$j]->getTagIndex() === $tagIndex){
                                $this->checkTags[$i]->counterpart = $this->checkTags[$j];
                                $this->checkTags[$j]->counterpart = $this->checkTags[$i];
                                break;
                            }
                        }
                    }
                    // finding counterpart backwards if forward didn't work
                    if($this->checkTags[$i]->counterpart == NULL && $i > 1){
                        for($j = $i - 1; $j >= 0; $j--){
                            if($j != $i && $this->checkTags[$j]->isClosing() && $this->checkTags[$j]->counterpart == NULL && $this->checkTags[$j]->getTagIndex() === $tagIndex){
                                $this->checkTags[$i]->counterpart = $this->checkTags[$j];
                                $this->checkTags[$j]->counterpart = $this->checkTags[$i];
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * Checks, if the given internal tag is structurally valid. That means, the tag is either single or it has a counterpart that comes behind the tag (for a opener) and there are no overlapping tags in-between
     * @param editor_Segment_Internal_Tag $tag
     * @return bool
     */
    protected function isStructurallyValid(editor_Segment_Internal_Tag $tag) : bool {
        // single tags are always valid
        if($tag->isSingle()){
            return true;
        }
        // ... and double-tags without counterpart always invalid
        if($tag->counterpart == NULL){
            return false;
        }
        if($tag->isOpening()){
            if($tag->counterpart->_idx < $tag->_idx || $tag->counterpart->startIndex < $tag->endIndex){
                return false;
            }
            return $this->hasNoOverlaps($tag->_idx, $tag->counterpart->_idx, $tag->endIndex, $tag->counterpart->startIndex);
        } else {
            if($tag->_idx < $tag->counterpart->_idx || $tag->startIndex < $tag->counterpart->endIndex){
                return false;
            }
            return $this->hasNoOverlaps($tag->counterpart->_idx, $tag->_idx, $tag->counterpart->endIndex, $tag->startIndex);
        }
    }
    /**
     * Evaluates if all tags from the given start to the given end index are between the given text-indices
     * It does not take care, if the tags in-between are not valid in terms of structure
     * @param int $startIndex
     * @param int $endIndex
     * @param int $startTextIndex
     * @param int $endTextIndex
     * @return bool
     */
    protected function hasNoOverlaps(int $startIndex, int $endIndex, int $startTextIndex, int $endTextIndex) : bool {
        if($startIndex < ($endIndex - 1)){
            for($i = $startIndex + 1; $i < $endIndex; $i++){
                $tag = $this->checkTags[$i];
                if($tag->startIndex < $startTextIndex || $tag->endIndex > $endTextIndex){
                    return false;
                }
                if($tag->counterpart != NULL && ($tag->counterpart->startIndex < $startTextIndex || $tag->counterpart->endIndex > $endTextIndex)){
                    return false;
                }
            }
        }
        return true;
    }
}
