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

use PHPHtmlParser\Dom\Node\HtmlNode;

/**
 * Abstraction to bundle the segment's text and it's internal tags
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving is covered with rendering / unparsing
 * The rendering will take care about interleaving and nested tags and may part a tag into chunks
 * When Markup is unserialized multiple chunks in a row of an internal tag will be joined to a single tag and the structure will be re-sequencialized
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the index of the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 * 
 * TODO/FIXME the prop "ttName" (representing the source field of the text in the datamodel) is used by the Termtagger only. Neccessary ??
 */
class editor_Segment_FieldTags implements JsonSerializable {
       
    /**
     * The counterpart to ::toJson: creates the tags from the serialized JSON data
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJson($jsonString) : editor_Segment_FieldTags {
        try {
            return self::fromJsonData(json_decode($jsonString));
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from JSON '.$jsonString);
        }
    }
    /**
     * Creates the tags from deserialized JSON data
     * @param stdClass $data
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJsonData(stdClass $data) : editor_Segment_FieldTags {
        try {
            $tags = new editor_Segment_FieldTags($data->segmentId, $data->field, $data->fieldText, $data->saveTo, $data->ttName);
            foreach($data->tags as $tag){
                $tags->addTag(editor_Segment_TagCreator::instance()->fromJsonData($tag));
            }
            return $tags;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from deserialized JSON-Data '.json_encode($data));
        }
    }
    /**
     * Helper to sort Internal tags or rendered tags by startIndex
     * @param editor_Segment_Tag $a
     * @param editor_Segment_Tag $b
     * @return int
     */
    public static function compare(editor_Segment_Tag $a, editor_Segment_Tag $b){
        if($a->startIndex === $b->startIndex){
            // crucial: we must make sure, that a "normal" tag may contain a single tag all at the same index (no text-content). Thus, the normal tags always must weight less
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
     * The id of the segment we refer to
     * @var int
     */
    private $segmentId;
    /**
     * The field our fieldtext comes from
     * @var string
     */
    private $field;
    /**
     * The text of the relevant segment field
     * @var string
     */
    private $fieldText;
    /**
     * The field of the segment's data we will be saved to
     * @var string
     */
    private $saveTo;
    /**
     * Only neccessary for the termtagger, will be used as the fieldname there. A target will be sent with it original field name (but saved to the edit-field) when importing
     * TODO: there is no obvious reason why this is done and this may is obsolete ...
     * @var string
     */
    private $ttName;
    /**
     * The tags and their positions within the segment
     * @var editor_Segment_Tag[]
     */
    private $tags = [];
    
    /**
     * 
     * @param int $segmentId
     * @param string $field
     * @param string $fieldText
     * @param string | string[] $saveTo
     * @param string $ttName
     */
    public function __construct(int $segmentId, string $field, string $fieldText, $saveTo, string $ttName=null) {
        $this->segmentId = $segmentId;
        $this->field = $field;
        $this->fieldText = '';
        $this->saveTo = is_array($saveTo) ? implode(',', $saveTo) : $saveTo;
        $this->ttName = (empty($ttName)) ? $this->getFirstSaveToField() : $ttName;
        // if HTML was passed as field text we have to unparse it
        if(!empty($fieldText) && $fieldText != strip_tags($fieldText)){
            $this->unparse($fieldText);
        } else {
            $this->fieldText = $fieldText;
        }
        // TODO REMOVE
        if($this->fieldText != strip_tags($fieldText)){
            error_log('=======================================');
            error_log('FAULTY FIELDTAGS FOR SEGMENT '.$this->segmentId.':');
            error_log('IN:  '.str_replace(array("\r","\n","\t"), '', htmlspecialchars($fieldText)));
            error_log('OUT: '.str_replace(array("\r","\n","\t"), '', htmlspecialchars($this->render())));
            error_log('TAGS: '.$this->toJson());
            error_log('=======================================');
        }
    } 
    /**
     * 
     * @return number
     */
    public function getSegmentId() : int {
        return $this->segmentId;
    }
    /**
     *
     * @return string
     */
    public function getField() : string {
        return $this->field;
    }
    /**
     * 
     * @return string
     */
    public function getFieldText() : string {
        return $this->fieldText;
    }
    /**
     *
     * @return string
     */
    public function getTermtaggerName() : string {
        return $this->ttName;
    }
    /**
     *
     * @return string[]
     */
    public function getSaveToFields() : array {
        if(empty($this->saveTo)){
            return [];
        }
        return explode(',', $this->saveTo);
    }
    /**
     * return string
     */
    public function getFirstSaveToField(){
        return $this->getSaveToFields()[0];
    }
    /**
     * 
     * @param string $fieldName
     */
    public function addSaveToField($fieldName){
        $fields = $this->getSaveToFields();
        $fields[] = $fieldName;
        $this->saveTo = implode(',', $fields);
    }
    /**
     * We expect the passed text to be identical
     * @param string $text
     * @return string
     */
    public function setTagsByText(string $text){
        // $textBefore = $this->fieldText;
        $this->fieldText = '';
        $this->tags = [];
        $this->unparse($text);
        /*
        if(false && $this->fieldText != $textBefore){
            $logger = Zend_Registry::get('logger')->cloneMe('editor.segment.fieldtags');
            $logger->warn(
                'E9999',
                'setting the FieldTags tags by text led to a changed text-content !',
                ['segmentId' => $this->segmentId, 'textBefore' => $textBefore, 'textAfter' => $this->fieldText ]
            );
        }
        */
    }
    /**
     *
     * @param editor_Segment_Tag $tag
     */
    public function addTag(editor_Segment_Tag $tag){
        $tag->tagIndex = count($this->tags);
        $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= mb_strlen($this->fieldText));
        $this->tags[] = $tag;
    }
    /**
     * Retrieves the tag at a certain index
     * @param int $index
     * @return editor_Segment_Tag|NULL
     */
    public function getAt($index){
        if($index < count($this->tags)){
            return $this->tags[$index];
        }
        return NULL;
    }
    /**
     * Retrieves the internal tags of a certain type
     * @param string $type
     * @return editor_Segment_Tag[]
     */
    public function getByType(string $type) : array {
        $result = [];
        foreach($this->tags as $tag){
            if($tag->getType() == $type){
                $result[] = $tag;
            }
        }
        return $result;
    }
    /**
     * Removes the internal tags of a certain type
     * @param string $type
     */
    public function removeByType(string $type){
        $result = [];
        $replace = false;
        foreach($this->tags as $tag){
            if($tag->getType() != $type){
                $result[] = $tag;
            } else {
                $replace = true;
            }
        }
        if($replace){
            $this->tags = $result;
        }
    }
    /**
     * 
     * @return bool
     */
    public function hasTags() : bool {
        return (count($this->tags) > 0);
    }
    /**
     * Checks if a internal tag of a certain type is present
     * @param string $type
     * @return boolean
     */
    public function hasType(string $type) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type){
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if a internal tag of a certain type and class is present
     * @param string $type
     * @param string $className
     * @return bool
     */
    public function hasTypeAndClass(string $type, string $className) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && $tag->hasClass($className)){
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if a internal tag of a certain type is present that has at least one of the given classnames
     * @param string $type
     * @param string[] $classNames
     * @return bool
     */
    public function hasTypeAndClasses(string $type, array $classNames) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && $tag->hasClasses($classNames)){
                return true;
            }
        }
        return false;
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
        $data->field = $this->field;
        $data->fieldText = $this->fieldText;
        $data->saveTo = $this->saveTo;
        $data->ttName = $this->ttName;
        return $data;
    }
    
    /* Rendering API */
    
    /**
     * 
     * @param string[] $skippedTypes: if set, internal tags of this type will not be rendered
     * @return string
     */
    public function render(array $skippedTypes=NULL) : string {
        $numTags = count($this->tags);
        if($numTags == 0){
            return $this->fieldText;
        }
        $this->sort();
        $rtags = [];
        /* @var $rtags editor_Segment_Tag[] */
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
        $holder = new editor_Segment_AnyTag(0, mb_strlen($this->fieldText));
        $container = $holder;
        foreach($rtags as $tag){
            $container->getNearestContainer($tag)->addChild($tag);
            $container = $tag;
        }
        $holder->addSegmentText($this);
        // finally, render the holder's children
        return $holder->renderChildren($skippedTypes);
    }
    /**
     * Retrieves a part of the segment-text by start & end index
     * Used by editor_Segment_Tag to fill in the segment-texts
     * @param int $start
     * @param int $end
     * @return string
     */
    public function getFieldTextPart(int $start, int $end) : string {
        return mb_substr($this->fieldText, $start, ($end - $start));
    }
    
    /* Unparsing API */

    public function unparse(string $html) {
        $dom = editor_Tag::createDomParser();
        // to make things easier we add a wrapper to hold all tags and only use it's children
        $dom->loadStr('<div>'.$html.'</div>');
        if($dom->countChildren() != 1){
            throw new Exception('Could not unparse Internal Tags from Markup '.$html);
        }
        $wrapper = $this->fromHtmlNode($dom->firstChild(), 0);
        $this->fieldText = $wrapper->getText();
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        $this->consolidate();
        // Crucial: finalize, set the tag-props
        $this->addTagProps();
    }
    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a HtmlNode structure
     * @param HtmlNode $node
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    private function fromHtmlNode(HtmlNode $node, int $startIndex){
        $tag = editor_Segment_TagCreator::instance()->fromHtmlNode($node, $startIndex);
        if($node->hasChildren()){
            foreach($node->getChildren() as $childNode){
                if($childNode->isTextNode() && !empty($childNode->text())){
                    $tag->addText($childNode->text());
                } else if(is_a($childNode, 'PHPHtmlParser\Dom\Node\HtmlNode')){
                    $tag->addChild($this->fromHtmlNode($childNode, $startIndex));
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
                // TODO: me only must compare the type and make sure it's not the "ANY" type to make tags equal
                if($tag->isEqualType($tag) && $tag->isEqual($last) && $last->endIndex == $tag->startIndex){
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
        $textLength = mb_strlen($this->fieldText);
        for($i=0; $i < $num; $i++){
            $this->tags[$i]->tagIndex = $i;
            $this->tags[$i]->isFullLength = ($this->tags[$i]->startIndex == 0 && $this->tags[$i]->endIndex >= $textLength);
        }
    }
    
    
    /**
     * ALTERNATIVE IMPLEMENTATION: UNPARSING CODE USING PHP'S DOM

    public function unparse(string $html) {
        $dom = new editor_Utils_Dom();
        // to make things easier we add a wrapper to hold all tags and only use it's children
        $element = $dom->loadUnicodeElement('<div>'.$html.'</div>');
        if($element != NULL){
            $wrapper = $this->fromDomElement($element, 0);
            // set our field text
            $this->fieldText = $wrapper->getText();
            // sequence the nested tags as our children
            $wrapper->sequenceChildren($this);
            $this->consolidate();
            // Crucial: finalize, set the tag-props
            $this->addTagProps();
        } else {
            throw new Exception('Could not unparse Internal Tags from Markup '.$html);
        }        
    }

    private function fromDomElement(DOMElement $element, int $startIndex){
        $tag = editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
        if($element->hasChildNodes()){
            for($i = 0; $i < $element->childNodes->length; $i++){
                $child = $element->childNodes->item($i);
                if($child->nodeType == XML_TEXT_NODE){
                    $tag->addText(htmlspecialchars($child->nodeValue, ENT_COMPAT));
                } else if($child->nodeType == XML_ELEMENT_NODE){
                    $tag->addChild($this->fromDomElement($child, $startIndex));
                }
                $startIndex += $tag->getLastChild()->getTextLength();
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }

    */
}
