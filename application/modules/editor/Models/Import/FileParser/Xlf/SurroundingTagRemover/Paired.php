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
 * calculates and removes leading and trailing paired and special single tags
 * removing means: the tags are not imported, they are added directly to the skeleton file and are not changeable therefore
 */
class editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Paired extends editor_Models_Import_FileParser_Xlf_SurroundingTagRemover_Abstract {
    
    /**
     * the following single tags are also to be considered to be moved out of the segment,
     * since they are place holders for isolated paired tags
     * @var array
     */
    protected array $isolatedPairedTags = ['it', 'bx', 'ex'];

    protected function _calculate(array $sourceChunks, array $targetChunks): bool {
        return $this->hasSameStartAndEndTags($sourceChunks, $targetChunks);
    }
    
    /**
     * Checks recursivly if target and source starts/ends with the same chunks,
     *   if there are some tags in the start/end chunks it checks if they are paired tags.
     *   if source and target start and ends just with that paired tags (no other content expect whitespace) then the tags are ignored in import
     * @param array $source
     * @param array $target
     * @param bool $foundTag used for recursive call
     * @return boolean returns false if there are no matching leading/trailing tags at all
     */
    protected function hasSameStartAndEndTags(array $source, array $target, bool $foundTag = false): bool {
        //source and target must have at least a start or end tag and inbetween text content, that means at least 2 chunks:
        if(count($source) < 3 || count($target) < 3){
            return $foundTag;
        }
        
        //init variables with empty string for loop in trimCount
        $sourceStart = $sourceEnd = '';
        /* @var $sourceStart editor_Models_Import_FileParser_Tag */
        /* @var $sourceEnd editor_Models_Import_FileParser_Tag */
        
        //trim from start
        $startShiftCountTrim = $this->trimAndCount($sourceStart, $source, $target);
        
        //check if we trimmed something and was the last trimmed chunk a tag chunk
        $startIsTag = $startShiftCountTrim > 0 && $sourceStart instanceof editor_Models_Import_FileParser_Tag;
        
        // if it is an single tag standing for a isolated paired tag, we cut it off too
        if($startIsTag && $sourceStart->isSingle() && in_array($sourceStart->tag, $this->isolatedPairedTags)) {
            $this->startShiftCount = $this->startShiftCount + $startShiftCountTrim;

            //if we got a tag to be cut off, start new iteration with found tag = true
            // remove the starting elements as counted above
            return $this->hasSameStartAndEndTags(
                array_slice($source, $startShiftCountTrim),
                array_slice($target, $startShiftCountTrim),
                true);
        }
        
        //trim from end
        $endShiftCountTrim = $this->trimAndCount($sourceEnd, $source, $target, false);
        
        //check next non empty/whitespace chunk from behind
        $endIsTag = $endShiftCountTrim > 0 && $sourceEnd instanceof editor_Models_Import_FileParser_Tag;
        
        // if it is an single tag standing for a isolated paired tag, we cut it off too
        if($endIsTag && $sourceEnd->isSingle() && in_array($sourceEnd->tag, $this->isolatedPairedTags)) {
            $this->endShiftCount = $this->endShiftCount + $endShiftCountTrim;
            
            //if we got a tag to be cut off, start new iteration with found tag = true
            // remove the ending elements as counted above
            return $this->hasSameStartAndEndTags(
                array_slice($source, 0, -$endShiftCountTrim),
                array_slice($target, 0, -$endShiftCountTrim),
                true);
        }
        
        //until here we cut of the allowed single tags, now we cut of paired tags
        
        // if start is no tag or no opening tag or end not tag or closing tag → nothing to cut
        if(!( $startIsTag && $sourceStart->isOpen() && $endIsTag && $sourceEnd->isClose())) {
            return $foundTag;
        }
        
        //if tag pairs from start to end does not match or shiftCounts are negative, then exit
        if($sourceStart->tagNr !== $sourceEnd->tagNr || $startShiftCountTrim < 0 || $endShiftCountTrim < 0) {
            return $foundTag;
        }
        $this->startShiftCount = $this->startShiftCount + $startShiftCountTrim;
        $this->endShiftCount = $this->endShiftCount + $endShiftCountTrim;
        
        //start recursivly for more than one tag pair,
        // we have found at least one tag pair so set $foundTag to true for next iteration
        // remove the start and ending elements as counted above
        return $this->hasSameStartAndEndTags(
            array_slice($source, $startShiftCountTrim, -$endShiftCountTrim),
            array_slice($target, $startShiftCountTrim, -$endShiftCountTrim),
            true);
    }
    
    /**
     * if sourceChunk is an empty string, get the next chunk pairs (source to target pair)
     *   if we do NOT preserveWhitespace, and sourceChunk is whitespace then we also get the next pair
     *   or in other words: if we preserveWhitespace only tags without whitespace inbetween are considered as having sameStartAndEndTags
     * @param mixed $sourceChunk
     * @param array $source
     * @param array $target
     * @param bool $fromStart true if trim from start, false to trim from end
     * @return int  returns -1 if source and targetChunk do not match!
     */
    protected function trimAndCount(mixed &$sourceChunk, array $source, array $target, bool $fromStart = true): int {
        $shiftCount = 0;

        while(!is_null($sourceChunk) && strlen($sourceChunk) == 0 || (!$this->preserveWhitespace && preg_match('#^\s+$#', $sourceChunk))) {
            if($fromStart) {
                $sourceChunk = array_shift($source);
                $targetChunk = array_shift($target);
            }
            else {
                $sourceChunk = array_pop($source);
                $targetChunk = array_pop($target);
            }
            //make a string cast here, so that the rendered tag content is used of tags (tags are objects here)
            if((string)$sourceChunk !== (string)$targetChunk) {
                return -1;
            }
            //inc internal start shift count
            $shiftCount++;
        }

        return $shiftCount;
    }
}