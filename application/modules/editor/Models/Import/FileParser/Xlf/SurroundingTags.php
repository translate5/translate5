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
 * calculates and removes leading and trailing paired and special single tags
 * removing means: the tags are not imported, they are added directly to the skeleton file and are not changeable therefore
 */
class editor_Models_Import_FileParser_Xlf_SurroundingTags {
    
    /**
     * @var Zend_Config
     */
    protected $config;
    
    /**
     * @var boolean
     */
    protected $preserveWhitespace;
    
    private $startShiftCount = 0;
    private $endShiftCount = 0;
    
    protected $leadingTags = '';
    protected $trailingTags = '';
    
    /**
     * the following single tags are also to be considered to be moved out of the segment,
     * since they are place holders for isolated paired tags
     * @var array
     */
    protected $isolatedPairedTags = ['it', 'bx', 'ex'];
    
    public function __construct(Zend_Config $config) {
        $this->config = $config;
    }
    
    /**
     * calculates the tags to be cut off
     * @param boolean $preserveWhitespace
     * @param array $sourceChunks
     * @param array $targetChunks
     * @param editor_Models_Import_FileParser_XmlParser $xmlparser
     */
    public function calculate(bool $preserveWhitespace, array $sourceChunks, array $targetChunks, editor_Models_Import_FileParser_XmlParser $xmlparser) {
        $this->leadingTags = '';
        $this->trailingTags = '';
        $this->preserveWhitespace = $preserveWhitespace;
        
        //if target is empty, we assume the target = source so that the feature can be used at all
        // 1. because it checks for same tags in source and target
        // 2. because we need the tags from source to be added as leading / trailing in target
        $target = $xmlparser->join($targetChunks);
        if(empty($target) && $target !== "0") {
            $targetChunks = $sourceChunks;
        }
        
        //reset start/end shift count.
        // the counts are set by hasSameStartAndEndTags to > 0,
        // then the start/end offset where the placeHolder is placed is shifted
        // to exclude tags leading and trailing tags in the segment
        $this->startShiftCount = 0;
        $this->endShiftCount = 0;
        //if preserveWhitespace is enabled, hasSameStartAndEndTags should not hide tags,
        // since potential whitespace tags does matter then in the content
        //since we are calling the leading/trailing tag stuff on the already fetched source segments,
        // we have no ability here to conserve content outside the mrk tags - which also should not be on preserveWhitespace
        if(!$this->hasSameStartAndEndTags($sourceChunks, $targetChunks)) {
            //if there is just leading/trailing whitespace but no tags we reset the counter
            // since then we dont want to cut off something
            //if there is whitespace between or before the leading / after the trailing tags,
            // this whitespace is ignored depending the preserveWhitespace setting.
            // above $sourceChunks $targetChunks does not contain any irrelevant whitespace (only empty chunks)
            $this->startShiftCount = 0;
            $this->endShiftCount = 0;
            return;
        }
        
        $prevMode = editor_Models_Import_FileParser_Tag::setMode(editor_Models_Import_FileParser_Tag::RETURN_MODE_ORIGINAL);
        //we get and store the leading target tags for later insertion
        $this->leadingTags = $xmlparser->join(array_slice($targetChunks, 0, $this->startShiftCount));
        
        //we get and store the trailing target tags for later insertion
        if($this->endShiftCount > 0) {
            $this->trailingTags = $xmlparser->join(array_slice($targetChunks, -$this->endShiftCount));
        }
        editor_Models_Import_FileParser_Tag::setMode($prevMode);
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
    protected function hasSameStartAndEndTags(array $source, array $target, $foundTag = false) {
        //source and target must have at least a start or end tag and inbetween text content, that means at least 2 chunks:
        // if the feature is disabled no framing tags are ignored
        if(!$this->config->runtimeOptions->import->xlf->ignoreFramingTags || count($source) < 3 || count($target) < 3){
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
    protected function trimAndCount(&$sourceChunk, array $source, array $target, $fromStart = true): int {
        $shiftCount = 0;
        
        while(!is_null($sourceChunk) && empty($sourceChunk) || (!$this->preserveWhitespace && preg_match('#^\s+$#', $sourceChunk))) {
            if($fromStart) {
                $sourceChunk = array_shift($source);
                $targetChunk = array_shift($target);
            }
            else {
                $sourceChunk = array_pop($source);
                $targetChunk = array_pop($target);
            }
            if($sourceChunk != $targetChunk) {
                return -1;
            }
            //inc internal start shift count
            $shiftCount++;
        }
        
        return $shiftCount;
    }
    
    /**
     * removes the leading and trailing tags as calculated before
     * @param array $chunks
     * @return array
     */
    public function sliceTags(array $chunks): array {
        return array_slice($chunks, $this->startShiftCount, count($chunks) - $this->startShiftCount - $this->endShiftCount);
    }
    
    /**
     * get the leading cut of tags in original import format as string
     * @return string
     */
    public function getLeading(): string {
        return $this->leadingTags;
    }
    
    /**
     * get the trailing cut of tags in original import format as string
     * @return string
     */
    public function getTrailing(): string {
        return $this->trailingTags;
    }
}