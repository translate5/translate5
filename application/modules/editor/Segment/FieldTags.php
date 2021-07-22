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
 * Abstraction to bundle the segment's text and it's internal tags to an OOP accessible structure
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving is covered with rendering / unparsing
 * The rendering will take care about interleaving and nested tags and may part a tag into chunks
 * When Markup is unserialized multiple chunks in a row of an internal tag will be joined to a single tag and the structure will be re-sequenced
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * tags that are immediate siblings can be identified by having the same end/start index
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 * Tag types can be registered via the Quality Provider registry in editor_Segment_Quality_Manager or (if not quality related) directly in the editor_Segment_TagCreator registry
 * Most existing segment tags are known by the editor_Segment_TagCreator evaluation API
 * 
 * The creation of the internal tags is done in 3 phases
 * - the unparsing starts either on instantiation (setting the markup from a segment's field text - thus the class-name - or by calling setTagsByText($markup)
 * - the passed markup is unparsed with either PHPHtmlParser or DOMDocument as configured in editor_Tag
 * - this creates a deep nested tag structure => Phase I
 * - the nested Dom Tags then are converted into segment-tags as direct children of this class. These tags represent their position in the markup by start/end indexes pointing to the pure text-content of the markup (represented by the fieldText prop) => Phase II
 * - usually all DOM tags represent a segment tag as all markup should be encapsulated by internal tags
 * - Note that in this class the fieldText also contains the visible Markup of the internal Tags (=> length of FieldTags) whilst in the Segment entities API those chars do not count as textual content of the segment obviously
 * - All Tags, that can not be converted to an actual segnment-tag will end up as "any" internal tag "editor_Segment_AnyTag" Those tags will not be consolidated so they will be renderd the way they are and no further processing is applied
 * - After "flattening" all tags of the same type with the same properties (category, as defined in the corresponding tag classes) are joined to one tag. This is the consolidation Phase => Phase III
 * - In the consolidation phase those tags representing a "virtual tag" by consisting of two singular tags (img) with "open" and "close" classes (-> e.g. MQM) are identified and united as a single tag (pairing API)
 * - After the consolidation the properties of the segment tags we hold are consumable for other APIs
 * 
 * Rendering
 * - When rendering the markup the contained segment tags are maybe broken up into several parts because they may overlap.
 * - In this process usually the left overlapping tag will be broken into parts. Some tags like the internal tags can not be splitted (isSplitable API)
 * - that means, in the frontend we may have multiple chunks representing one segment tag !
 * - it is possible, that overlapping tags in the frontend (-> MQM), where the order is "user defined", may have a different branching after being processed. This can also happen, when more tags are added (Termtagger, ...)
 * - To retrieve a proper Markup - especially with the singular tags to be in a useful order - sorting the tags is crucial and ensures a correct structure
 * 
 * Compatibility Problems
 * - generally the AutoQA adds some data-attributes to existing classes
 * - The generated markup may be different then in earlier times (order of attributes!)
 * - This may creates problems with regex-based tag processing that relies on a fixed order of attributes or css-classes
 * - Generally, RegEx based processing of Markup often fails with nested Markup (especially when the expressions cover the start and end tag) and should be replaced with OOP code
 * 
 * 
 * TODO/FIXME the prop "ttName" (representing the source field of the text in the datamodel) is used by the Termtagger only. Investigate, if this is really neccessary...
 */
class editor_Segment_FieldTags implements JsonSerializable {
    
    /**
     * Can be used to validate the unparsing-process. Use only for Development !!
     * @var boolean
     */
    const VALIDATION_MODE = false;
    /**
     * The counterpart to ::toJson: creates the tags from the serialized JSON data
     * @param editor_Models_Task $task
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJson(editor_Models_Task $task, string $jsonString) : editor_Segment_FieldTags {
        try {
            return self::fromJsonData($task, json_decode($jsonString));
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from JSON '.$jsonString);
        }
    }
    /**
     * Creates the tags from deserialized JSON data
     * @param editor_Models_Task $task
     * @param stdClass $data
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJsonData(editor_Models_Task $task, stdClass $data) : editor_Segment_FieldTags {
        try {
            $tags = new editor_Segment_FieldTags($task, $data->segmentId, $data->field, $data->fieldText, $data->saveTo, $data->ttName);
            $creator = editor_Segment_TagCreator::instance();
            foreach($data->tags as $tag){
                $segmentTag = $creator->fromJsonData($tag);
                $tags->addTag($segmentTag, $segmentTag->order, $segmentTag->parentOrder);
            }
            // crucial: we do not serialize the deleted/inserted props as they're serialized indirectly with the trackchanges tags
            // so we have to re-evaluate these props now
            $tags->evaluateDeletedInserted();
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
            // only tags at the exact same position that do not contain each other will need the order-property evaluated when sorting !
            if($a->endIndex == $b->endIndex && $a->order > -1 && $b->order > -1 && $a->parentOrder != $b->order && $a->order != $b->parentOrder){
                return $a->order - $b->order;
            }
            // crucial: we must make sure, that a "normal" tag may contain a single tag at the same index (no text-content). Thus, the normal tags always must weight less / come first
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
     * The task the segments belong to
     * @var editor_Models_Task
     */
    private $task;
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
     * This text unfortunately covers the text-contents of Internal Tags
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
     * @var integer
     */
    private $orderIndex = -1;
    
    /**
     * @param editor_Models_Task $task
     * @param int $segmentId
     * @param string $field
     * @param string $fieldText
     * @param string | string[] $saveTo
     * @param string $ttName
     */
    public function __construct(editor_Models_Task $task, int $segmentId, string $field, ?string $fieldText, $saveTo, string $ttName=null) {
        $this->task = $task;
        $this->segmentId = $segmentId;
        $this->field = $field;
        $this->fieldText = '';
        $this->saveTo = is_array($saveTo) ? implode(',', $saveTo) : $saveTo;
        $this->ttName = (empty($ttName)) ? $this->getFirstSaveToField() : $ttName;
        // if HTML was passed as field text we have to unparse it
        if(!empty($fieldText) && $fieldText != editor_Segment_Tag::strip($fieldText)){
            $this->unparse($fieldText);
        } else if($fieldText !== NULL) {
            $this->fieldText = $fieldText;
        }
        // This debug can be used to evaluate the quality of the DOM parsing
        if(self::VALIDATION_MODE && $this->fieldText != editor_Segment_Tag::strip($fieldText)){
            error_log('=================== PARSED FIELD TEXT DID NOT MATCH PASSED HTML ===================='."\n");
            error_log('FAULTY FIELDTAGS FOR SEGMENT '.$this->segmentId."\n");
            error_log('RAW TEXT: '.editor_Segment_Tag::strip($fieldText)."\n");
            error_log('FIELD TEXT: '.$this->fieldText."\n");
            error_log('IN:  '.$fieldText."\n");
            error_log('OUT: '.$this->render()."\n");
            error_log('TAGS: '.$this->toJson()."\n");
            error_log('======================================='."\n");
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
     * Returns the field text (which covers the textual contents of internal tags as well !)
     * @return string
     */
    public function getFieldText() : string {
        return $this->fieldText;
    }
    /**
     *
     * @return string
     */
    public function getFieldTextLength() : int {
        return mb_strlen($this->fieldText);
    }
    /**
     *
     * @return bool
     */
    public function isFieldTextEmpty() : bool {
        return mb_strlen($this->fieldText) == 0;
    }
    /**
     * 
     * @return bool
     */
    public function isEmpty() : bool {
        return ($this->isFieldTextEmpty() && !$this->hasTags());
    }
    /**
     * Evaluates, if the bound field is a source field
     * @return bool
     */
    public function isSourceField() : bool {
        return (substr($this->field, 0, 6) == 'source');
    }
    /**
     * Evaluates, if the bound field is a target field
     * @return bool
     */
    public function isTargetField() : bool {
        return !$this->isSourceField();
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
    public function addSaveToField(string $fieldName){
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
        $textBefore = $this->fieldText;
        $this->fieldText = '';
        $this->tags = [];
        $this->orderIndex = -1;
        $this->unparse($text);
        // this checks if the new Tags may changed the text-content which must not happen during quality checks
        if($this->fieldText != $textBefore){
            $logger = Zend_Registry::get('logger')->cloneMe('editor.segment.fieldtags');
            $logger->warn(
                'E1343',
                'Setting the FieldTags tags by text led to a changed text-content presumably because the encoded tags have been improperly processed',
                ['segmentId' => $this->segmentId, 'textBefore' => $textBefore, 'textAfter' => $this->fieldText ]
            );
        }
    }
    /**
     * Adds a Segment tag. Note, that the nesting has to be reflected with the internal order of tags and the parent (referencing the order of the parent element)
     * @param editor_Segment_Tag $tag
     * @param int $order: Do only use if the internal order is know. If not provided, a tag is always added as the last thing at the tags startPosition (e.g. a single tag can not be added inside a present tag's send position without knowing the order)
     * @param int $parent: Order-index of the oparent element with nested tags. If not provided, the added tag will be rendered outside of other tags if possible
     */
    public function addTag(editor_Segment_Tag $tag, int $order=-1, int $parentOrder=-1){
        $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $this->getFieldTextLength());
        $tag->field = $this->field; // we transfer our field to the tag for easier handling of our segment-tags
        $tag->content = $this->getFieldTextPart($tag->startIndex, $tag->endIndex);
        if($order < 0){
            $this->orderIndex++;
            $tag->order = $this->orderIndex;
        } else {
            $tag->order = $order;
            $this->orderIndex = max($this->orderIndex, $order);
        }
        $tag->parentOrder = $parentOrder;
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
     * @param boolean $includeDeleted: if set, internal tags that represent deleted content will be processed as well
     * @return editor_Segment_Tag[]
     */
    public function getByType(string $type, bool $includeDeleted=false) : array {
        $result = [];
        foreach($this->tags as $tag){
            if($tag->getType() == $type && ($includeDeleted || !$tag->wasDeleted)){
                $result[] = $tag;
            }
        }
        return $result;
    }
    /**
     * Removes the internal tags of a certain type
     * @param string $type
     * @param boolean $includeDeleted: if set, field tags that represent deleted content will be processed as well
     */
    public function removeByType(string $type, bool $includeDeleted=false){
        $result = [];
        $replace = false;
        foreach($this->tags as $tag){
            if($tag->getType() != $type || (!$includeDeleted && $tag->wasDeleted)){
                $result[] = $tag;
            } else {
                $replace = true;
                if($tag->getType() == editor_Segment_Tag::TYPE_INTERNAL){
                    $this->removeInternalTagText($tag);
                }
            }
        }
        if($replace){
            $this->tags = $result;
        }
    }
    /**
     * Removes all tags, so only the raw text will be left
     */
    public function removeAll(){
        // CRUCIAL: remove the internal tags first to remove the text-contents of the internal tags as well
        $this->removeByType(editor_Segment_Tag::TYPE_INTERNAL, true);
        $this->tags = [];
        $this->orderIndex = -1;
    }
    /**
     * Removes the text belonging to the internal tag
     * All indexes of all following tags will be adjusted, the internal tag will be reset to contain no text
     * @param editor_Segment_Internal_Tag $tag
     */
    private function removeInternalTagText(editor_Segment_Internal_Tag $internalTag){
        $text = ($internalTag->startIndex == 0) ? '' : mb_substr($this->fieldText, 0, $internalTag->startIndex);
        if($internalTag->endIndex < $this->getFieldTextLength() - 1){
            $text .= mb_substr($this->fieldText, $internalTag->endIndex);
        }
        $this->fieldText = $text;
        // CRUCIAL: adjusting all text-indices of all following tags
        $cut = $internalTag->endIndex - $internalTag->startIndex;
        foreach($this->tags as $tag){
            if($tag->startIndex > $internalTag->startIndex){
                $discount = min(($tag->startIndex - $internalTag->startIndex), $cut);
                $tag->startIndex -= $discount;
            }
            if($tag->endIndex > $internalTag->startIndex){
                $discount = min(($tag->endIndex - $internalTag->startIndex), $cut);
                $tag->endIndex -= $discount;
            }
        }
        $internalTag->startIndex = 0;
        $internalTag->endIndex = 0;
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
     * @param boolean $includeDeleted: if set, internal tags that represent deleted content will be processed as well
     * @return boolean
     */
    public function hasType(string $type, bool $includeDeleted=false) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && ($includeDeleted || !$tag->wasDeleted)){
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
    public function hasTypeAndClass(string $type, string $className, bool $includeDeleted=false) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && $tag->hasClass($className) && ($includeDeleted || !$tag->wasDeleted)){
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
    public function hasTypeAndClasses(string $type, array $classNames, bool $includeDeleted=false) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && $tag->hasClasses($classNames) && ($includeDeleted || !$tag->wasDeleted)){
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
        if(count($this->tags) == 0){
            return $this->fieldText;
        }
        $this->sort();
        // first, clone our tags to have a disposable rendering model
        $clones = [];
        /* @var $clones editor_Segment_Tag[] */
        foreach($this->tags as $tag){
            $tag->addRenderingClone($clones);
        }
        // the final rendered model
        $rtags = [];
        /* @var $rtags editor_Segment_Tag[] */
        // creating a datamodel where the overlapping tags are segmented to pieces that do not overlap
        // therefore, all tags are compared with the tags after them and are cut into pieces if needed
        // this will lead to tags being cut into pieces not nesseccarily in the order as they have been added but in the order of their start-indexes / weight
        $numClones = count($clones);
        if($numClones > 1){
            while($numClones > 0){
                $tag = array_shift($clones);
                $numClones--;
                $rtags[] = $tag;
                $added = false;
                for($i = 0; $i < $numClones; $i++){
                    $compare = $clones[$i];
                      // if the tag to compare overlaps we have to cut one of the two in pieces
                    if($compare->startIndex < $tag->endIndex && $compare->endIndex > $tag->endIndex){
                        if($tag->isSplitable() || $compare->isSplitable()){
                            if($tag->isSplitable()){
                                // cut the current tag at the next tags start index
                                $part = $tag->cloneForRendering();
                                $tag->endIndex = $compare->startIndex;
                                $part->startIndex = $compare->startIndex;
                            } else {
                                // cut the following tag at the tag's end index
                                $part = $compare->cloneForRendering();
                                $compare->endIndex = $tag->endIndex;
                                $part->startIndex = $tag->endIndex;
                            }
                            // tricky: since we add the part at the end, it will not be evaluated again in the current camparision run
                            $clones[] = $part;
                            $added = true;
                        } else {
                            // we have an overlap with tags, that both are not allowed to overlap. this must not happen.
                            // TODO FIXME: Add Proper Exception
                            $logger = Zend_Registry::get('logger')->cloneMe('editor.segment.tags');
                            /* @var $logger ZfExtended_Logger */
                            $logger->error('E9999', 'Two non-splittable tags interleave each other. Segment-ID: '.$this->segmentId);
                            // we simply do not add the next tag which will not be rendered this way
                        }
                    }
                }
                // the number may changed when adding to the clones when following tags are cut. Also, reordering is needed then
                if($added){
                    usort($clones, array($this, 'compare'));
                    $numClones = count($clones);
                }
            }
            usort($rtags, array($this, 'compare'));
        } else {
            $rtags = $clones;
        }
        // now we create the nested data-model from the up to now sequential but sorted $rtags model. We also add the text-portions of the segment as text nodes
        // this container just acts as the master container 
        $holder = new editor_Segment_AnyTag(0, $this->getFieldTextLength());
        $container = $holder;
        foreach($rtags as $tag){
            $nearest = $container->getNearestContainer($tag);
            // Will log rendering problems
            if(self::VALIDATION_MODE && $nearest == null){
                error_log("\n============== HOLDER =============\n");
                error_log($holder->toJson());
                error_log("\n============== CONTAINER =============\n");
                error_log($container->toJson());
                error_log("\n============== TAG =============\n");
                error_log($tag->toJson());
                error_log("\n========================================\n");
            }
            $nearest->addChild($tag);
            $container = $tag;
        }
        // distributes the text-portions to the now re-nested structure
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
        // prevent any substr magic with negative offsets ...
        if($end > $start){
            return mb_substr($this->fieldText, $start, ($end - $start));
        }
        return '';
    }
    
    /* Unparsing API */

    /**
     * Unparses Segment markup into FieldTags
     * @param string $html
     * @throws Exception
     */
    public function unparse(string $html) {
        if(editor_Tag::USE_PHP_DOM){
            // implementation using PHP DOM
            $dom = new editor_Utils_Dom();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $element = $dom->loadUnicodeElement('<div>'.$html.'</div>');
            if(self::VALIDATION_MODE && substr($dom->saveHTML($element), 5, -6) != $html){
                error_log("\n============== UNPARSED PHP DOM DOES NOT MATCH =============\n");
                error_log(substr($dom->saveHTML($element), 5, -6));
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            if($element != NULL){
                $wrapper = $this->fromDomElement($element, 0);
             } else {
                throw new Exception('Could not unparse Internal Tags from Markup '.$html);
            }
        } else {
            // implementation using PHPHtmlParser
            $dom = editor_Tag::createDomParser();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $dom->loadStr('<div>'.$html.'</div>');
            if($dom->countChildren() != 1){
                throw new Exception('Could not unparse Internal Tags from Markup '.$html);
            }
            if(self::VALIDATION_MODE &&  $dom->firstChild()->innerHtml() != $html){
                error_log("\n============== UNPARSED HTML DOM DOES NOT MATCH =============\n");
                error_log($dom->firstChild()->innerHtml());
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            $wrapper = $this->fromHtmlNode($dom->firstChild(), 0);
        }
        // set our field text
        $this->fieldText = $wrapper->getText();
        if(self::VALIDATION_MODE){
            if($wrapper->getTextLength() != $this->getFieldTextLength()){ error_log("\n##### WRAPPER TEXT LENGTH ".$wrapper->getTextLength()." DOES NOT MATCH FIELD TEXT LENGTH: ".$this->getFieldTextLength()." #####\n"); }
            if($wrapper->endIndex != $this->getFieldTextLength()){ error_log("\n##### WRAPPER END INDEX ".$wrapper->endIndex." DOES NOT MATCH FIELD TEXT LENGTH: ".$this->getFieldTextLength()." #####\n"); }
        }
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        if(self::VALIDATION_MODE){
            $this->sort();
            $length = $this->getFieldTextLength();
            foreach($this->tags as $tag){
                if($tag->endIndex > $length){
                    error_log("\n============== SEGMENT TAG IS OUT OF BOUNDS (TEXT LENGTH: ".$length.") =============\n");
                    error_log($tag->toJson());
                    error_log("\n========================================\n");
                }
            }
        }
        $this->consolidate();
        // Crucial: finalize, set the tag-props
        $this->addTagProps();
    }
    /**
     * Clones the tags with only the types of tags specified
     * Note, that you will not be able to filter trackchanges-tags out, use ::cloneWithoutTrackChanges instead for this
     * @param array $includedTypes
     * @return editor_Segment_FieldTags
     */
    public function cloneFiltered(array $includedTypes=NULL) : editor_Segment_FieldTags {
        // UGLY: The Internal tags can not be filtered out but must be removed from the cloned tags if they should be filtered out due to the text-contents of the internal tags
        $cloneHasInternal = ($includedTypes == NULL || in_array(editor_Segment_Tag::TYPE_INTERNAL, $includedTypes));
        $clonedTags = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->field, $this->fieldText, $this->saveTo, $this->ttName);
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES || $tag->getType() == editor_Segment_Tag::TYPE_INTERNAL || ($includedTypes == NULL || in_array($tag->getType(), $includedTypes))){
                $clonedTags->addTag($tag->clone(true, true), $tag->order, $tag->parentOrder);
            }
        }
        if(!$cloneHasInternal){
            $clonedTags->removeByType(editor_Segment_Tag::TYPE_INTERNAL, true);
        }
        $clonedTags->evaluateDeletedInserted();
        return $clonedTags;
    }
    /**
     * Clones without trackchanges tags. Deleted contents (in del-tags) will be removed and all text-lengths/indices will be adjusted
     * @param array $includedTypes: if set, filters the existing types of tags to the specified 
     * @return editor_Segment_FieldTags
     */
    public function cloneWithoutTrackChanges(array $includedTypes=NULL) : editor_Segment_FieldTags {
        // UGLY: The Internal tags can not be filtered out but must be removed from the cloned tags if they should be filtered out due to the text-contents of the internal tags
        $cloneHasInternal = ($includedTypes == NULL || in_array(editor_Segment_Tag::TYPE_INTERNAL, $includedTypes));
        $deleteTags = [];
        $otherTags = [];
        $fieldText = '';
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES){
                if($tag->isDeleteTag()){
                    $del = $tag->clone(true, true);
                    // if the del-tag is surrounded by blanks, we remove one of the blanks to avoid doubled whitespace
                    if($del->startIndex > 0 && $del->endIndex < $this->getFieldTextLength() && $this->getFieldTextPart($del->startIndex - 1, $del->startIndex) == ' ' && $this->getFieldTextPart($del->endIndex, $del->endIndex + 1) == ' '){
                        $del->startIndex -= 1;
                    }
                    $deleteTags[] = $del;
                }
            } else if(!$tag->wasDeleted && ($tag->getType() == editor_Segment_Tag::TYPE_INTERNAL || $includedTypes == NULL || in_array($tag->getType(), $includedTypes))){
                $clone = $tag->clone(true, true);
                $clone->cloneOrder($tag);
                $otherTags[] = $clone;
            }
        }
        usort($deleteTags, array($this, 'compare'));
        $numDeleteTags = count($deleteTags);
        if($numDeleteTags > 0){
            // condense any overlapping del tags
            for($i=0; $i < $numDeleteTags; $i++){
                if($i > 0){
                    $before = $i - 1;
                    if($deleteTags[$i]->startIndex <= $deleteTags[$before]->endIndex){
                        $deleteTags[$before]->endIndex = max($deleteTags[$i]->endIndex, $deleteTags[$before]->endIndex);
                        $deleteTags[$i] = NULL;
                    }
                }
            }
            // cut the "holes" out of the tags and the field text
            $start = 0; // holds the next index to start with for the field text
            $gap = 0; // holds the sum of all holes, which must be substracted from each hole as the whole structure shifts to the left when "punching holes"
            for($i=0; $i < $numDeleteTags; $i++){
                if($deleteTags[$i] != NULL){
                    $this->cutIndicesOut($otherTags, ($deleteTags[$i]->startIndex - $gap), ($deleteTags[$i]->endIndex - $gap));
                    if($deleteTags[$i]->startIndex > $start){
                        $fieldText .= $this->getFieldTextPart($start, $deleteTags[$i]->startIndex);
                    }
                    $start = $deleteTags[$i]->endIndex;
                    $gap += ($deleteTags[$i]->endIndex - $deleteTags[$i]->startIndex);
                }
            }
            if($start < $this->getFieldTextLength()){
                $fieldText .= $this->getFieldTextPart($start, $this->getFieldTextLength());
            }
        } else {
            $fieldText = $this->fieldText;
        }
        // create the clone & add all non-trackchanges tags
        $clonedTags = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->field, $fieldText, $this->saveTo, $this->ttName);
        foreach($otherTags as $tag){
            // the "hole punching" may created more deleted tags - should not happen though
            if(!$tag->wasDeleted){
                $clonedTags->addTag($tag, $tag->order, $tag->parentOrder);
            }
        }
        if(!$cloneHasInternal){
            $clonedTags->removeByType(editor_Segment_Tag::TYPE_INTERNAL, true);
        }
        return $clonedTags;
    }
    /**
     * Removes a portion of the referenced Text of the passed segment-tags
     * @param editor_Segment_Tag[] $tags
     * @param int $start
     * @param int $end
     */
    private function cutIndicesOut(array &$tags, int $start, int $end){
        $dist = $end - $start;
        if($dist <= 0){
            return;
        }
        $numTags = count($tags);
        for($i=0; $i < $numTags; $i++){            
            // the tag is only affected if not completely  before the hole
            if($tags[$i]->endIndex > $start){                
                // if we're completely behind, just shift
                if($tags[$i]->startIndex >= $end){
                    $tags[$i]->startIndex -= $dist;
                    $tags[$i]->endIndex -= $dist;
                } else if($tags[$i]->startIndex >= $start && $tags[$i]->endIndex <= $end) {
                    // we should create an error here since this must not happen !
                    $tags[$i]->startIndex = $tags[$i]->endIndex = 0;
                    $tags[$i]->wasDeleted = true;
                } else {
                    // tag is somehow overlapping the hole
                    $tags[$i]->startIndex = ($tags[$i]->startIndex <= $start) ? $tags[$i]->startIndex : $start;
                    $tags[$i]->endIndex = ($tags[$i]->endIndex >= $end) ? ($tags[$i]->endIndex - $dist) : ($end - $dist);
                }
            }
        }
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
                if($childNode->isTextNode()){
                    if($tag->addText($childNode->text())){
                        $startIndex += $tag->getLastChild()->getTextLength();
                    }
                } else if(is_a($childNode, 'PHPHtmlParser\Dom\Node\HtmlNode')){
                    if($tag->addChild($this->fromHtmlNode($childNode, $startIndex))){
                        $startIndex += $tag->getLastChild()->getTextLength();
                    }
                } else if(self::VALIDATION_MODE){
                    error_log("\n##### FROM HTML NODE ADDS UNKNOWN NODE TYPE '".get_class($childNode)."' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a DOMElement structure
     * This is an alternative implementation using PHP DOM
     * see editor_Tag::USE_PHP_DOM
     * @param DOMElement $element
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    private function fromDomElement(DOMElement $element, int $startIndex){
        $tag = editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
        if($element->hasChildNodes()){
            for($i = 0; $i < $element->childNodes->length; $i++){
                $child = $element->childNodes->item($i);
                if($child->nodeType == XML_TEXT_NODE){
                    if($tag->addText(editor_Tag::convertDOMText($child->nodeValue))){
                        $startIndex += $tag->getLastChild()->getTextLength();
                    }
                } else if($child->nodeType == XML_ELEMENT_NODE){
                    if($tag->addChild($this->fromDomElement($child, $startIndex))){
                        $startIndex += $tag->getLastChild()->getTextLength();
                    }
                } else if(self::VALIDATION_MODE){
                    error_log("\n##### FROM DOM ELEMENT ADDS UNWANTED ELEMENT TYPE '".$child->nodeType."' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * Joins Tags that are equal and directly beneath each other
     * Also removes any internal connections between the tags
     * Joins paired tags, removes obsolete tags
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
                // when a tag is a paired opener we try to find it's counterpart and remove it from the chain
                if($last->isPairedOpener()){
                    for($j = $i; $j < $numTags; $j++){
                        // if we found the counterpart (the opener could pair it) this closer will be removed from our chain
                        if($this->tags[$j]->isPairedCloser() && $last->getType() == $this->tags[$j]->getType() && $last->pairWith($this->tags[$j])){
                            array_splice($this->tags, $j, 1);
                            $numTags--;
                            break;
                        }
                    }
                }
                
                // we may already removed the current element, so check
                if($i < $numTags){
                    $tag = $this->tags[$i];
                    // we join only tasks that are splitable of course ...
                    if($last->isSplitable() && $tag->isSplitable() && $tag->isEqual($last) && $last->endIndex == $tag->startIndex){
                        $last->endIndex = $tag->endIndex;
                    } else {
                        $last = $tag;
                        $last->resetChildren();
                        $tags[] = $last;
                    }
                }
            }
            // last step: remove obsolete tags and paired closers that found no counterpart
            $this->tags = [];
            foreach($tags as $tag){
                if($tag->isObsolete() || $tag->isPairedCloser()){
                    $tag->onConsolidationRemoval();
                } else {
                    $this->tags[] = $tag;
                }
            }
        }
    }
    /**
     * Adds the 'isFullLength' and 'field' prop to the tags, which are needed by consuming APIs and calls the finalize for the tags indicate, the tag creation phase is finished
     */
    private function addTagProps(){
        $num = count($this->tags);
        $textLength = $this->getFieldTextLength();
        for($i=0; $i < $num; $i++){
            $tag = $this->tags[$i];
            $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $textLength);
            $tag->field = $this->field;
            $tag->content = $this->getFieldTextPart($tag->startIndex, $tag->endIndex);
            $tag->finalize($this, $this->task);
        }
        // finally, we set the wasDeleted / wasInserted properties of our tags
        $this->evaluateDeletedInserted();
    }
    /**
     * Sets the deleted / inserted properties for all tags. 
     * This is the last step of unparsing the tags and deserialization from JSON
     * It is also crucial for evaluating qualities because only non-deleted tags will count
     */
    private function evaluateDeletedInserted(){
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES){
                $propName = ($tag->isDeleteTag()) ? 'wasDeleted' : 'wasInserted';
                $this->setContainedTagsProp($tag->startIndex, $tag->endIndex, $propName);
            }
        }
    }
    /**
     * Helper to set the del/ins properties
     * @param int $start
     * @param int $end
     * @param string $propName
     */
    private function setContainedTagsProp(int $start, int $end, string $propName){
        foreach($this->tags as $tag){
            if($tag->startIndex >= $start && $tag->endIndex <= $end && $tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES){
                $tag->$propName = true;
            }
        }
    }
    /**
     * Debug output
     * @return string
     */
    public function debug(){
        $newline = "\n";
        $debug = 'FIELD TEXT: "'.trim($this->fieldText).'"'.$newline;
          for($i=0; $i < count($this->tags); $i++){
              $debug .= 'TAG '.$i.':'.$newline.trim($this->tags[$i]->debug()).$newline;
        }
        return $debug;
    }
    /**
     * Debug state of our segment props
     * @return string
     */
    public function debugProps(){
        return '[ segment:'.$this->segmentId.' | field:'.$this->field.' | saveTo:'.$this->saveTo.' | ttName:'.$this->ttName.' ]';
    }
}
