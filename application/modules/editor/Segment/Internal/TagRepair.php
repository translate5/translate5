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

    public function __construct(editor_Segment_FieldTags $toRepair, editor_Segment_FieldTags $against = null)
    {
        parent::__construct($toRepair, $against);
        if($this->numCheckTags > 0){
            $this->fixStructure();
        }
    }

    /**
     * Checks if the structure of internal tags is correct, every opener has his corresponding closer without overlaps
     */
    private function fixStructure(): void
    {
        // most simple fix: swap all tags that are in the wrong order
        // But: without it, other fixes may fail as they implicitly expect the opening tag to be before the closing ...
        $this->fixWrongOrder($this->checkTags);

        // first fix overlaps in sequences on the same index
        $this->fixSameIndexSequences();

        $brokenOpeners = [];
        // every detected faulty tag with counterpart will be attempted to fix, incomplete will be discarded
        for($i=0; $i < $this->numCheckTags; $i++){
            $tag = $this->checkTags[$i];
            if($tag->isSingle()){
                // pass through singular tags
                $this->resultTags[] = $tag;
            } else if($tag->counterpart === null){
                // remove incomplete tags
                $this->numRemoved++;
            } else if($tag->isOpening() && !$this->isStructurallyValid($this->checkTags, $i)){
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
            for($i = 0; $i < $numBroken; $i++){
                if($this->insertTagsToResult($brokenOpeners[$i], $brokenOpeners[$i]->counterpart)){
                    $this->numFixed += 2;
                } else {
                    $this->numRemoved += 2;
                }
            }
        }
        if($this->hadErrors()){
            // remove the internal tags and merge the remaining tags with the new internal tags.
            // Then we reset the tags and re-add the evaluated
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
    private function insertTagsToResult(editor_Segment_Internal_Tag $start, editor_Segment_Internal_Tag $end): bool
    {
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
        // other special case: tags are in sequence / on the same text-index
        // then, they will be added immediately behind each other
        if($start->hasSameTextIndex($end)){
            // crucial: level the order so they are really rendered behind each other!
            $end->order = $start->order;
            $end->parentOrder = $start->parentOrder;
            $this->addToResult($start, $end);
            return true;
        }

        // standard case: look for the next word that matches
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
    private function hasNoOverlapsBetweenIndices(int $startTextIndex, int $endTextIndex): bool
    {
        foreach($this->resultTags as $tag){
            if($tag->startIndex < $endTextIndex
                && $tag->endIndex > $startTextIndex
                && ($tag->isOpening() || $tag->isClosing())){

                if($tag->endIndex > $endTextIndex
                    || $tag->startIndex < $startTextIndex
                    || $tag->counterpart->endIndex > $endTextIndex
                    || $tag->counterpart->startIndex < $startTextIndex){
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
     * Special fix for tags on the same text-indices
     * Example for such errors: "This is a <1><2><3/></1></2>segment."
     * @return void
     */
    private function fixSameIndexSequences(): void
    {
        // find the sequences that have problems
        $sameIndexSequences = $this->findSameIndexSequences($this->checkTags);
        foreach($sameIndexSequences as $sequence){
            $faultyIndices = $this->checkSameIndexSequence($sequence);
            // if there are fulty indices we cluster the faulty indices to clusters of tags that overlap
            if(count($faultyIndices) > 0){
                sort($faultyIndices, SORT_NUMERIC);
                $clusters = [];
                foreach($faultyIndices as $index){
                    $tag = $sequence[$index]; /* @var editor_Segment_Internal_Tag $tag */
                    if(count($clusters) === 0){
                        $clusters[] = $this->createNewCluster($tag);
                     } else {
                        if(!$this->addToClusters($clusters, $tag)){
                            $clusters[] = $this->createNewCluster($tag);
                        }
                    }
                }
                foreach($clusters as $cluster){
                    if(count($cluster['tags']) < 2){
                        throw new ZfExtended_Exception(
                            'Algorithmical Error in TagRepair: found cluster with less than 2 elements!');
                    }
                    $this->fixSameIndexClusterTags($cluster['tags']);
                }
            }

            // the cluster-repair may lead to a faulty tag-order, repair that first since some APIs expect ordered tags
            $this->fixWrongOrder($sequence);
            // order by _idx
            usort($sequence, 'editor_Segment_Internal_TagCheckBase::compareByIdx');

            // it turns out the "fix clusters" approach produces "nice" fixes but can not catch all faulty tags.
            // Therefore a second run fixes all still overlapping tags the hard way ...
            $this->numFixed += $this->fixSameIndexTagsForced($sequence, $this->checkTags);
        }
    }

    /**
     * The passed tags are overlapping with each other,
     * they are ordered by index in the sequence and all are only the opening tags
     * @param editor_Segment_Internal_Tag[] $tags
     * @return void
     */
    private function fixSameIndexClusterTags(array $tags): void
    {
        // create ordered array of counterpart-indices
        $numOpeners = count($tags);
        $counterpartIndices = [];
        $counterpartOrders = [];
        foreach($tags as $tag){
            $counterpartIndices[] = $tag->counterpart->_idx;
            $counterpartOrders[] = $tag->counterpart->order;
        }
        sort($counterpartIndices, SORT_NUMERIC);
        sort($counterpartOrders, SORT_NUMERIC);
        // map the current indices to those making sure, the first tag's counterpart is moved to the last index
        foreach($tags as $index => $tag){
            $opposingIdx = $numOpeners - $index - 1;
            $targetIdx = $counterpartIndices[$opposingIdx];
            $this->checkTags[$targetIdx] = $tag->counterpart;
            $tag->counterpart->_idx = $targetIdx;
            $tag->counterpart->order = $counterpartOrders[$opposingIdx];
        }
        $this->numFixed += $numOpeners;
    }

    /**
     * @param array $clusters
     * @param editor_Segment_Internal_Tag $tag
     * @return bool
     */
    private function addToClusters(array &$clusters, editor_Segment_Internal_Tag $tag): bool
    {
        for($i = 0; $i < count($clusters); $i++){
            // we have an overlap of either the tag or counterpart is in the cluster
            if(($clusters[$i]['lidx'] <= $tag->_idx && $clusters[$i]['ridx'] >= $tag->_idx) ||
                ($clusters[$i]['lidx'] <= $tag->counterpart->_idx && $clusters[$i]['ridx'] >= $tag->counterpart->_idx)){
                // add to existing cluster stretching it's boundaries
                $clusters[$i]['lidx'] = min($clusters[$i]['lidx'], $tag->_idx);
                $clusters[$i]['ridx'] = max($clusters[$i]['ridx'], $tag->counterpart->_idx);
                $clusters[$i]['tags'][] = $tag;
                return true;
            }
        }
        return false;
    }

    /**
     * @param editor_Segment_Internal_Tag $tag
     * @return array
     */
    private function createNewCluster(editor_Segment_Internal_Tag $tag): array
    {
        return [
            'lidx' => $tag->_idx,
            'ridx' => $tag->counterpart->_idx,
            'tags' => [ $tag ]
        ];
    }

    /**
     * Swaps all tags/counterparts that simply are in the wrong order
     * @return void
     */
    private function fixWrongOrder(array &$tags): void
    {
        $swaps = [];
        foreach($this->checkTags as $tag){
            if($tag->isOpening() && $tag->counterpart !== null && $tag->counterpart->_idx < $tag->_idx){
                $swaps[] = $tag;
            }
        }
        foreach($swaps as $tag){
            // completely swap the tag
            $tagIdx = $tag->counterpart->_idx;
            $tagOrder = $tag->counterpart->order;
            $counterIdx = $tag->_idx;
            $counterOrder = $tag->order;

            $tags[$tagIdx] = $tag;
            $tag->_idx = $tagIdx;
            $tag->order = $tagOrder;
            $tags[$counterIdx] = $tag->counterpart;
            $tag->counterpart->_idx = $counterIdx;
            $tag->counterpart->order = $counterOrder;

            $this->numFixed += 1;
        }
    }

    /**
     * API to force a fix of tags on either a whole structure or only a single-text-index sequence
     * The repair is achieved by simply put the end-tag directly behind the start-tag
     * This is by no means a nice fix and only a last ressort for faulty structures that cannot be fixed otherwise
     * It is expected, that the _idx prop refers to the index in the passed array
     * Another prequesite is, that all opening tags come before the closing tags
     * @param editor_Segment_Internal_Tag[] $tags
     * @param editor_Segment_Internal_Tag[] $referencedTags : the tags the indexes refer to (may be the same as the first param)
     * @return int: the number of repaired tags
     */
    private function fixSameIndexTagsForced(array $tags, array &$referencedTags): int
    {
        $fixed = 0;
        // at one point this api may be called multiple times
        foreach($tags as $tag){
            if(isset($tag->_sidx)){
                unset($tag->_sidx);
            }
        }
        $numTags = count($tags);
        $reordered = [];
        $isFaulty = false;
        foreach($tags as $index => $tag){ /* @var editor_Segment_Internal_Tag $tag */
            if(!isset($tag->_sidx)){
                $tag->_sidx = $index;
                $reordered[] = $tag;
                // if we have a tag tha has overlaps ...
                if($tag->counterpart != null
                    && $tag->isOpening()
                    && $this->isOverlappingInSequence($tag, $index, $tags, $numTags)){
                    // ... find the closing tag and add it immediately after
                    for($i = $index + 1; $i < $numTags; $i++){
                        if($tag->counterpart === $tags[$i]){
                            $tags[$i]->_sidx = $i;
                            $reordered[] = $tags[$i];
                            $isFaulty = true;
                            $fixed++;
                            break;
                        }
                    }
                }
            }
        }
        // if there have been faults, apply the ordering of the reorder
        // (which is virtual and we have to use the original sequence to find the target position)
        if($isFaulty){
            foreach($reordered as $index => $tag){
                $desiredIdx = $tags[$index]->_idx;
                if($tag->_idx !== $desiredIdx){
                    $referencedTags[$desiredIdx] = $tag;
                    $tag->_idx = $desiredIdx;
                }
            }
        }
        return $fixed;
    }

    /**
     * Retrieves if internal tags have been faulty and had to be removed or corrected
     * @return bool
     */
    public function hadErrors() : bool
    {
        return ($this->numFixed > 0 || $this->numRemoved > 0);
    }
}
