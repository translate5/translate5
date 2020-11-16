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
 * Holds a segment and the internal tags as objects and provide to render this structure or to recreate it from the rendered markup
 */
/**
 * Abstraction to bundle the segment's text and it's internal tags
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving ic covered with rendering / unparsing
 * The rendering will take care about interleaving and nested tags and may part a tag into chunks
 * When Markup is unserialized multiple chunks in a row of an internal tag will be joined to a single tag and the structure will be re-sequencialized
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the index of the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 */
class editor_Segment_Tags implements JsonSerializable {
        
    /**
     * The counterpart to ::toJson: creates the tags from the serialized json data
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_Tags
     */
    public static function fromJson($jsonString) : editor_Segment_Tags {
        try {
            $data = json_decode($jsonString);
            $tags = new editor_Segment_Tags($data->segmentId, $data->segmentText, $data->field);
            foreach($data->tags as $tag){
                $tags->addTag(editor_Segment_TagCreator::instance()->fromJsonData($tag));
            }
            return $tags;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_Tags from JSON-Object '.json_encode($data));
        }
    }
    /**
     * Helper to sort Internal tags or rendered tags by startIndex
     * @param editor_Segment_InternalTag $a
     * @param editor_Segment_InternalTag $b
     * @return int
     */
    public static function compare(editor_Segment_InternalTag $a, editor_Segment_InternalTag $b){
        if($a->startIndex === $b->startIndex){
            // crucial: we must make sure, that a "normal" tag may contain a single tag all at the same index (no text-content). Thius, the normal tags always must weight less
            if($a->isSingular() && !$b->isSingular()){
                return 1;
            } else if(!$a->isSingular() && $b->isSingular()){
                return -1;
            }
            return $b->endIndex - $a->endIndex;
        }
        return $a->startIndex - $b->startIndex;
    }
    /**
     * The relevant segment-text
     * @var int
     */
    private $segmentId;
    /**
     * The text of the relevant segment
     * @var string
     */
    private $segmentText;
    /**
     * The field of the segment's data we refer to
     * @var string
     */
    private $field;
    /**
     * The tags and their positions within the segment
     * @var editor_Segment_InternalTag[]
     */
    private $tags = [];
    
    public function __construct(int $segmentId, string $segmentText, string $field) {
        $this->segmentId = $segmentId;
        $this->segmentText = $segmentText;
        $this->field = $field;
    } 
    /**
     * 
     * @return number
     */
    public function getSegmentId(){
        return $this->segmentId;
    }
    /**
     * 
     * @return string
     */
    public function getSegmentText(){
        return $this->segmentText;
    }
    /**
     * 
     * @return string
     */
    public function getField(){
        return $this->field;
    }
    /**
     *
     * @param editor_Segment_InternalTag $tag
     */
    public function addTag(editor_Segment_InternalTag $tag){
        $tag->tagIndex = count($this->tags);
        $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= mb_strlen($this->segmentText));
        $this->tags[] = $tag;
    }
    /**
     * Retrieves the tag at a certain index
     * @param int $index
     * @return editor_Segment_InternalTag|NULL
     */
    public function getAt($index){
        if($index < count($this->tags)){
            return $this->tags[$index];
        }
        return NULL;
    }
    /**
     * Retrieves the tags of a certain type
     * @param string $type
     * @return editor_Segment_InternalTag[]
     */
    public function getByType(string $type){
        $result = array();
        foreach($this->tags as $tag){
            if($tag->getType() == $type){
                $result[] = $tag;
            }
        }
        return $result;
    }
    /**
     * Sorts the items ascending, takes the second index into account when items have the same startIndex
     */
    public function sort(){
        usort($this->tags, array($this, 'compare'));
    }
    
    /* Serialization API */
    
    /**
     *
     * @return string
     */
    public function toJson(){
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    
    public function jsonSerialize(){
        $data = new stdClass();
        $this->sort();
        $data->tags = [];
        foreach($this->tags as $tag){
            $data->tags[] = $tag->jsonSerialize();
        }
        $data->segmentId = $this->segmentId;
        $data->segmentText = $this->segmentText;
        $data->field = $this->field;
        return $data;        
    }
    
    /* Rendering API */
    
    public function render(){
        $numTags = count($this->tags);
        if($numTags == 0){
            return $this->segmentText;
        }
        $this->sort();
        $rtags = [];
        /* @var $rtags editor_Segment_InternalTag[] */
        $numRtags = 0;
        // creating a datamodel where the overlapping tags are segmented to pieces that do not overlap
        // therefore, all tags are compared with the tags after them and are cut into pieces if needed
        // this will lead to tags being cut into pieces not necceccarily in the order as they have been added but in the order of their start-indexes / weight
        if($numTags > 1){
            for($i = 0; $i < $numTags; $i++){
                $tag = $this->tags[$i];
                $last = $tag->clone(true);
                $rtags[$numRtags] = $last;
                $numRtags++;
                if(($i + 1) < $numTags){
                    for($j = $i + 1; $j < $numTags; $j++){
                        $compare = $this->tags[$j];
                        // if the tag to compare overlaps we cut at the start-index
                        if($compare->startIndex < $tag->endIndex && $compare->endIndex > $tag->endIndex){
                            $cut = $compare->startIndex;
                            $last->endIndex = $cut;
                            $last = $tag->clone(false);
                            $last->startIndex = $cut;
                            $rtags[$numRtags] = $last;
                            $numRtags++;
                        }
                    }
                }
            }
            usort($rtags, array($this, 'compare'));
         
        } else {
            
            $rtags[$numRtags] = $this->tags[0]->clone(true);
            $numRtags++;
        }
        // now we create the nested data-model from the up to now sequential but sorted $rtags model. We also add the text-portions of the segment as text nodes
        // this container just acts as the master container 
        $holder = new editor_Segment_AnyInternalTag(0, mb_strlen($this->segmentText));
        $container = $holder;
        foreach($rtags as $tag){
            $container->getNearestContainer($tag)->addChild($tag);
            $container = $tag;
        }
        $holder->addSegmentText($this);
        // finally, render the holder's children
        return $holder->renderChildren();
    }
    /**
     * Retrieves a part of the segment-text by start & end index
     * Used by editor_Segment_InternalTag to fill in the segment-texts
     * @param int $start
     * @param int $end
     * @return string
     */
    public function getSegmentTextPart(int $start, int $end) : string {
        return substr($this->segmentText, $start, ($end - $start));
    }
    
    /* Unparsing API */

    public function unparse(string $html) {
        $dom = new editor_Utils_Dom();
        // to make things easier we add a wrapper to hold all tags and only use it's children
        $element = $dom->loadUnicodeElement('<div>'.$html.'</div>');
        if($element != NULL){
            $wrapper = $this->fromDomElement($element, 0);
            // sequence the nested tags as our children
            $wrapper->sequenceChildren($this);
            $this->consolidate();
            // Crucial: finalize, set the tag-props
            $this->addTagProps();
        } else {
            throw new Exception('Could not unparse Internal Tags from Markup '.$html);
        }        
    }
    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a n DOMElement structure
     * @param DOMElement $element
     * @param int $startIndex
     * @return editor_Segment_InternalTag
     */
    private function fromDomElement(DOMElement $element, int $startIndex){
        $tag = editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
        if($element->hasChildNodes()){
            for($i = 0; $i < $element->childNodes->length; $i++){
                $child = $element->childNodes->item($i);
                if($child->nodeType == XML_TEXT_NODE){
                    $tag->addText($child->nodeValue);
                } else if($child->nodeType == XML_ELEMENT_NODE){
                    $tag->addChild($this->fromDomElement($child, $startIndex));
                }
                $startIndex += $tag->getLastChild()->getTextLength();
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * Joins Tags that are equal and directly beneath each other
     * Also removes any internal connections between the tags
     */
    private function consolidate(){
        $this->sort();
        $numTags = count($this->tags);
        if($numTags > 1){
            $tags = [];
            $last = $this->tags[0];
            $last->resetChildren();
            $tags[] = $last;
            for($i=1; $i < $numTags; $i++){
                $tag = $this->tags[$i];
                if($tag->isEqual($last) && $last->endIndex == $tag->startIndex){
                    $last->endIndex = $tag->endIndex;
                } else {
                    $last = $tag;
                    $last->resetChildren();
                    $tags[] = $last;
                }
            }
            $this->tags = $tags;
        }
    }
    /**
     * Adds the properties 'tagIndex' and 'isFullLength' to the tags, which are needed by consuming APIs
     */
    private function addTagProps(){
        $num = count($this->tags);
        $textLength = mb_strlen($this->segmentText);
        for($i=0; $i < $num; $i++){
            $this->tags[$i]->tagIndex = $i;
            $this->tags[$i]->isFullLength = ($this->tags[$i]->startIndex == 0 && $this->tags[$i]->endIndex >= $textLength);
        }
    }
}
